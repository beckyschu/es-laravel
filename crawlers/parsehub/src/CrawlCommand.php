<?php

namespace Shark\Parsehub;

use App\Models\Crawl;
use League\Csv\Reader;
use DanGreaves\ParseHub;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Repositories\Eloquent\CrawlRepository;
use App\Repositories\Eloquent\DiscoveryRepository;

class CrawlCommand extends Command
{
    protected $signature = '
        crawl:parsehub
        {crawl : Unique ID for crawl}
    ';

    protected $description = 'Run a crawl at Parsehub';

    public function handle()
    {
        // Capture crawl ID
        $crawlId = $this->argument('crawl');

        // Fetch crawl with given ID
        if (! $crawl = Crawl::find($crawlId)) {
            Log::error('[Shark\Parsehub\CrawlCommand] [Crawl '.$crawlId.'] Crawl not found.');
            $this->error('Crawl '.$crawlId.' not found.');
            return 1;
        }

        // Check this crawler supports ParseHub
        if (! $crawl->crawler->parsehub_project_token) {
            Log::error('[Shark\Parsehub\CrawlCommand] [Crawl '.$crawlId.'] Crawler does not support ParseHub.');
            $this->error('Crawler does not support ParseHub.');
            return 1;
        }

        // Mark the crawl as started
        app(CrawlRepository::class)->start($crawl);
        Log::info('[Shark\Parsehub\CrawlCommand] [Crawl '.$crawlId.'] Crawl started.');

        // Instantiate ParseHub SDK
        $parsehub = new ParseHub\ParseHub(config('services.parsehub.token'));

        // Generate a payload for ParseHub
        $payload = [
            'start_url'            => $crawl->setting ? $crawl->setting->start_url : null,
            'start_template'       => $crawl->setting ? $crawl->setting->start_template : null,
            'start_value_override' => ['keyword' => $crawl->keyword->keyword]
        ];

        // Output some useful debug info
        Log::info('[Shark\Parsehub\CrawlCommand] [Crawl '.$crawlId.'] Contacting ParseHub to run project "'.$crawl->crawler->parsehub_project_token.'" with payload '.json_encode($payload));
        $this->info('Contacting ParseHub to run project "'.$crawl->crawler->parsehub_project_token.'" with payload '.json_encode($payload));

        // JSON encode the payload start value
        $payload['start_value_override'] = json_encode($payload['start_value_override']);

        // Run the ParseHub project
        $run = $parsehub->runProject($crawl->crawler->parsehub_project_token, $payload);

        // Output info line
        Log::info('[Shark\Parsehub\CrawlCommand] [Crawl '.$crawlId.'] Run "'.$run->run_token.'" running at ParseHub, waiting for 3.5 minutes...');
        $this->info('Run "'.$run->run_token.'" running at ParseHub, waiting for 3.5 minutes...');

        // Poll project once every 3.5 minutes (ParseHub only allows polling once
        // every 3 minutes)
        while (true) {

            // Sleep for 3.5 minutes
            sleep(210);

            // Re-fetch the run
            $run = $parsehub->getRun($run->run_token);

            // Data is ready for collection
            if ((bool) $run->data_ready) {
                Log::info('[Shark\Parsehub\CrawlCommand] [Crawl '.$crawlId.'] Run complete, data ready for collection');
                $this->info('Run complete, data ready for collection');
                break;
            }

            // Output info line
            Log::info('[Shark\Parsehub\CrawlCommand] [Crawl '.$crawlId.'] Run "'.$run->run_token.'" still running at ParseHub ('.$run->pages.' pages crawled), waiting a further 3.5 minutes...');
            $this->info('Run "'.$run->run_token.'" still running at ParseHub ('.$run->pages.' pages crawled), waiting a further 3.5 minutes...');

        }

        // Compute a path to store the run data CSV at
        $dataPath = storage_path('app/tmp/parsehub_'.$run->run_token.'.csv');

        // Fetch and store the CSV run data
        Log::info('[Shark\Parsehub\CrawlCommand] [Crawl '.$crawlId.'] Fetching CSV data to store at "'.$dataPath.'"');
        $this->info('Fetching CSV data to store at "'.$dataPath.'"');
        $parsehub->storeRunData($run->run_token, $dataPath);
        Log::info('[Shark\Parsehub\CrawlCommand] [Crawl '.$crawlId.'] CSV data successfully stored');
        $this->info('CSV data successfully stored');

        // Instantiate CSV reader
        $reader = Reader::createFromPath($dataPath);

        // Generate a total row count
        $rowCount = $reader->setOffset(1)->each(function () {return true;});

        // Output row count info
        Log::info('[Shark\Parsehub\CrawlCommand] [Crawl '.$crawlId.'] '.$rowCount.' rows found in CSV data');
        $this->info($rowCount.' rows found in CSV data');

        // No results found, bail out
        if (! $rowCount) {
            app(CrawlRepository::class)->stop($crawl);
            Log::info('[Shark\Parsehub\CrawlCommand] [Crawl '.$crawlId.'] Crawl complete with no results.');
            $this->info('Crawl complete with no results.');
            return 0;
        }

        // Set crawl result count
        app(CrawlRepository::class)->update($crawl, ['predicted_count' => $rowCount]);

        // Output debug info
        Log::info('[Shark\Parsehub\CrawlCommand] [Crawl '.$crawlId.'] Submitting each row as a discovery...');
        $this->info('Submitting each row as a discovery...');

        // Create an output progress bar
        $bar = $this->output->createProgressBar($rowCount);

        // Loop through CSV, using offset 0 for headers
        foreach ($reader->fetchAssoc(0) as $row)
        {
            // This disco doesn't have a title and/or URL, skip it
            if (empty($row['results_name']) || empty($row['results_url'])) continue;

            // Compute seller
            if (! empty($row['results_sellers_name'])) $seller = $row['results_sellers_name'];
            elseif (! empty($row['results_seller']))   $seller = $row['results_seller'];
            else $seller = null;

            // Compute price
            if (! empty($row['results_sellers_price'])) $price = $row['results_sellers_price'];
            elseif (! empty($row['results_price']))     $price = $row['results_price'];
            else $price = null;

            // Submit discovery data
            app(DiscoveryRepository::class)->discover([
                'title'    => $row['results_name'],
                'url'      => $row['results_url'],
                'sku'      => (! empty($row['results_itemnumber'])) ? $row['results_itemnumber'] : null,
                'picture'  => (! empty($row['results_image_url']))  ? $row['results_image_url'] :  null,
                'category' => (! empty($row['results_category']))   ? $row['results_category'] :   null,
                'keyword'  => $crawl->keyword->keyword,
                'price'    => $price,
                'seller'   => $seller
            ], $crawl);

            // Advance progress bar
            $bar->advance();
        }

        // Complete the progress bar
        $bar->finish();

        // Delete the CSV data file
        Log::info('[Shark\Parsehub\CrawlCommand] [Crawl '.$crawlId.'] Processing complete, cleaning up CSV data file...');
        $this->info('Processing complete, cleaning up CSV data file...');
        unlink($dataPath);

        // Wrap everything up
        Log::info('[Shark\Parsehub\CrawlCommand] [Crawl '.$crawlId.'] Data file removed, stopping crawl...');
        $this->info('Data file removed, stopping crawl...');

        // Stop crawl
        app(CrawlRepository::class)->stop($crawl);

        // Output debug
        Log::info('[Shark\Parsehub\CrawlCommand] [Crawl '.$crawlId.'] Crawl complete.');

        // Return happy
        return 0;
    }
}