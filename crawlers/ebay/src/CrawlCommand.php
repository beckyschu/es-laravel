<?php

namespace Shark\eBay;

use DTS\eBaySDK;
use App\Models\Crawl;
use Illuminate\Console\Command;
use App\Repositories\Eloquent\CrawlRepository;
use App\Repositories\Eloquent\DiscoveryRepository;

class CrawlCommand extends Command
{
    /**
     * Command signature.
     *
     * @var string
     */
    protected $signature = '
        crawl:ebay
        {crawl : Unique ID for crawl}
    ';

    /**
     * Command description.
     *
     * @var string
     */
    protected $description = 'Run a crawl at eBay';

    /**
     * Array of [categoryId => rootCategoryName] for lookups.
     *
     * @var array
     */
    protected $categories = [];

    /**
     * Handler function for command.
     *
     * @return int
     */
    public function handle()
    {
        // Capture crawl ID
        $crawlId = $this->argument('crawl');

        // Fetch crawl with given ID
        if (! $crawl = Crawl::find($crawlId)) {
            $this->error('Crawl '.$crawlId.' not found.');
            return 1;
        }

        // Check this crawl is destined for Ebay
        if ('ebay' !== $crawl->crawler->platform) {
            $this->error('Crawler does not support eBay.');
            return 1;
        }

        // Mark the crawl as started
        app(CrawlRepository::class)->start($crawl);

        // Instantiate the eBay finding service
        $findingService = new eBaySDK\Finding\Services\FindingService([
            'credentials' => config('services.ebay.credentials')
        ]);

        // Instantiate a new find-by-keywords request
        $findingRequest = new eBaySDK\Finding\Types\FindItemsAdvancedRequest([
            'keywords'       => $crawl->keyword->keyword,
            'outputSelector' => ['SellerInfo', 'StoreInfo']
        ]);

        // Paginate the request
        $findingRequest->paginationInput = new eBaySDK\Finding\Types\PaginationInput;
        $findingRequest->paginationInput->entriesPerPage = 100;

        // Include only specified category if set
        if ($crawl->asset->ebay_category) {
            $findingRequest->categoryId[] = (string) $crawl->asset->ebay_category;
        }

        // Hide duplicate items
        $findingRequest->itemFilter[] = new eBaySDK\Finding\Types\ItemFilter([
            'name'  => 'HideDuplicateItems',
            'value' => ['true']
        ]);

        // Only fetch new items
        $findingRequest->itemFilter[] = new eBaySDK\Finding\Types\ItemFilter([
            'name'  => 'Condition',
            'value' => ['New']
        ]);

        // Instantiate the eBay shopping service
        $shoppingService = new eBaySDK\Shopping\Services\ShoppingService([
            'credentials' => config('services.ebay.credentials')
        ]);

        // Create a new shopping request
        $shoppingRequest = new eBaySDK\Shopping\Types\GetMultipleItemsRequestType;

        // Instantiate page number
        $page = 1;

        // Instantiate total count
        $totalCount = null;

        // Initiate processed count
        $processedCount = null;

        // Instantiate collection for items on this page
        $pageItems = [];

        // Initiate consecutive failure counter
        $consecutiveFailures = 0;

        // Output debug
        $this->info('---- Crawl Started ----');

        // Loop continuously until out of results
        while (null == $totalCount || ($totalCount > $processedCount && $processedCount < 10000)) {

            // Loop has already failed more than 5 times, bail out
            if (5 < $consecutiveFailures) {
                app(CrawlRepository::class)->fail($crawl);
                $this->error('More than 5 API failures encountered. Bailing out.');
                return 1;
            }

            // Output debug
            $this->info('Crawling page '.$page.'...');

            // Set current page number
            $findingRequest->paginationInput->pageNumber = $page;

            // Fetch items from finding service
            $response = $findingService->findItemsAdvanced($findingRequest);

            // Finding request failed
            if ('Success' !== $response->ack && 'Warning' !== $response->ack) {
                if ($response->errorMessage) {
                    foreach ($response->errorMessage->error as $error) {
                        $this->error($error->message);
                    }
                } else {
                    $this->error('Encountered unknown eBay API gateway error');
                }
                $consecutiveFailures++;
                continue;
            }

            // This is the first page, set estimated result count
            if (1 == $page) {
                $totalCount = $response->paginationOutput->totalEntries;
                app(CrawlRepository::class)->update($crawl, [
                    'predicted_count' => $totalCount
                ]);
                $this->info($totalCount.' total listings found');
            }

            // Total count is zero, bail out
            if (0 == $totalCount) break;

            // Output debug
            $this->info($response->searchResult->count.' listings found for this page');

            // Loop result listings
            foreach ($response->searchResult->item as $result) {

                // Root category not cached yet
                if (! array_key_exists($result->primaryCategory->categoryId, $this->categories)) {

                    // Send API request for category path
                    $category = $shoppingService->getCategoryInfo(new eBaySDK\Shopping\Types\GetCategoryInfoRequestType([
                        'CategoryID' => $result->primaryCategory->categoryId
                    ]));

                    // Resolve a root category name
                    if ('Success' == $category->Ack || 'Warning' == $category->Ack) {
                        $rootCategoryName = explode(':', $category->CategoryArray->Category[0]->CategoryNamePath)[0];
                    } else {
                        $rootCategoryName = $result->primaryCategory->categoryName;
                    }

                    // Cache root category
                    $this->categories[$result->primaryCategory->categoryId] = $rootCategoryName;

                }

                // Remove Emojis from Ebay title
                $cleanTitle = trim( preg_replace('/[\x00-\x1F\x80-\xFF]/', '', mb_convert_encoding( $result->title, "UTF-8" ) ) );

                // Add item ID to page collection
                array_push($pageItems, [
                    'ebay_id'       => $result->itemId,
                    'title'         => $cleanTitle,
                    'sku'           => $result->itemId,
                    'seller'        => $result->sellerInfo->sellerUserName,
                    'category'      => $this->categories[$result->primaryCategory->categoryId],
                    'keyword'       => $crawl->keyword->keyword,
                    'origin'        => $result->location,
                    'price'         => $result->sellingStatus->convertedCurrentPrice->value,
                    'picture'       => $result->galleryURL,
                    'url'           => $result->viewItemURL,
                    'listing_url'   => $result->viewItemURL,
                    'qty_available' => 0,
                    'qty_sold'      => 0
                ]);

            }

            // Process variations, 20 at a time (API max limit)
            while (! empty($pageItems)) {

                // Shift off 20 items for lookup
                $itemsForLookup = [];
                for ($i = 1; $i <= 20; $i++) {
                    if (! $item = array_shift($pageItems)) break;
                    array_push($itemsForLookup, $item);
                }

                // Set item IDs for variation lookup
                $shoppingRequest->ItemID = array_pluck($itemsForLookup, 'ebay_id');

                // Define which fields to be returned
                $shoppingRequest->IncludeSelector = 'Variations,Details';

                // Fetch variation details
                $response = $shoppingService->getMultipleItems($shoppingRequest);

                // Output gateway error
                if ('Success' !== $response->Ack && 'Warning' !== $response->Ack) {
                    if ($response->Errors) {
                        foreach ($response->Errors as $error) {
                            $this->error($error->LongMessage);
                        }
                    } else {
                        $this->error('Encountered unknown eBay API gateway error');
                    }
                    $consecutiveFailures++;
                    continue(2);
                }

                // Loop each item
                foreach ($response->Item as $detail) {

                    // Get data for submit
                    $item = array_except(array_first($itemsForLookup, function ($item) use ($detail) {
                        return $item['ebay_id'] == $detail->ItemID;
                    }), ['ebay_id']);

                    // This item has variations, loop and increment quantities
                    if (isset($detail->Variations->Variation)) {
                        foreach ($detail->Variations->Variation as $variation) {
                            $item['qty_available'] += $variation->Quantity;
                            $item['qty_sold']      += $variation->SellingStatus->QuantitySold;
                        }
                    }

                    // Submit item to platform
                    app(DiscoveryRepository::class)->discover($item, $crawl);

                    // Increment processed count
                    $processedCount++;

                }

            }

            // Increment page number
            $page++;

            // Reset consecutive failure counter
            $consecutiveFailures = 0;
        }

        // Output debug
        $this->info((int) $processedCount.' items processed. Crawl complete.');

        // Stop crawl
        app(CrawlRepository::class)->stop($crawl);

        // Return happy
        return 0;
    }
}
