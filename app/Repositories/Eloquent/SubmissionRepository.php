<?php

namespace App\Repositories\Eloquent;
use DB;
use Closure;
use Carbon\Carbon;
use App\Models\Crawl;
use App\Models\Import;
use App\Models\Submission;
use App\Jobs;
use Illuminate\Support\MessageBag;
use App\Exceptions\ValidationException;
use LaravelArdent\Ardent\InvalidModelException;
use App\Contracts\SubmissionRepositoryInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class SubmissionRepository implements SubmissionRepositoryInterface
{
    public function find($id)
    {
        return Submission::findOrFail($id);
    }

    public function create($attributes)
    {
        $submission = new Submission($attributes);

        // Crawl ID provided
        if (isset($attributes['crawl_id'])) {

            // Invalid crawl ID provided
            if (! $crawl = app('App\Contracts\CrawlRepositoryInterface')->find($attributes['crawl_id'])) {
                throw new BadRequestHttpException('You must select a valid crawl to associate with a submission.');
            }

            // Associate the crawl
            $submission->crawl()->associate($crawl);
        }

        // Import ID provided
        if (isset($attributes['import_id'])) {

            // Invalid import ID provided
            if (! $import = app('App\Contracts\ImportRepositoryInterface')->find($attributes['import_id'])) {
                throw new BadRequestHttpException('You must select a valid import to associate with a submission.');
            }

            // Associate the import
            $submission->import()->associate($import);
        }

        $submission->status = 'pending';

        $submission->save();

        return $submission;
    }

    public function processImport(Import $import)
    {
        // Update import status
        $import->status = 'processing';
        $import->save();

        // Loop and process submissions
        $failures = 0;
        foreach ($import->submissions as $submission) {
            try {
                $this->process($submission);
            } catch (ValidationException $e) {
                dd($submission, $e);
                // Do nothing, errors have been logged
                $failures++;
            }
        }

        // There was at least one failure in this import
        if (0 < $failures) {
            $import->status = 'failure';
            $import->save();
        }

        // There were no failures, mark complete
        else {
            $import->status = 'complete';
            $import->save();
        }

        // Queue submission processing
	app('App\Contracts\DiscoveryRepositoryInterface')->processDiscoveries($import);
        //dispatch(new Jobs\ProcessDiscoveryJob($import));//'import', $import->id));
    }

    /**
     * Process the given submission (transfer it to discoveries).
     *
     * @param  Submission $submission
     * @return true
     */
    public function process(Submission $submission)
    {
        // This submission is not approved and can therefore not be processed
        if ('approved' !== $submission->status) {
            return true;
        }

        // Grab attached entity
        $entity = ($submission->crawl) ? $submission->crawl : $submission->import;

        // Grab relationships
        $asset    = $entity->asset;
        $account  = $asset->account;
        $platform = $entity->platform ? $entity->platform : $entity->crawler->platform;

        // Setup repositories
        $sellers     = app('App\Contracts\SellerRepositoryInterface')->setAccount($account);
        $discoveries = app('App\Contracts\DiscoveryRepositoryInterface')->setAccount($account);

        // First, try creating a seller if we collected one
        $seller = null;
        if ($submission->seller) {
            $seller = $sellers->findOrCreate($submission->seller, $platform);
        }

        // Then create a discovery
        $discovery = $discoveries->discover($submission->url, [
	            	'account_id'    => $account->id,
	            	'asset_id'      => $asset->id,
	            	'seller_id'     => $seller ? $seller->id : null,
		        'platform'      => $platform,
	        	'title'         => $submission->title,
	            	'sku'           => $submission->sku,
	            	'category'      => $submission->category,
	            	'keyword'       => $submission->keyword,
	            	'origin'        => $submission->origin,
	            	'price'         => $submission->price,
	            	'picture'       => $submission->picture,
	            	'url'           => $submission->url,
	            	'listing_url'   => $submission->listing_url,
	            	'qty_available' => $submission->qty_available,
	            	'qty_sold'      => $submission->qty_sold,
	            	'status'		=> 'discovered',
	            	'comment'		=> 'Initiate: From '.($submission->crawl ? 'crawl '.$submission->crawl->id : 'import '.$submission->import->id)
		],
		$submission->crawl ? 'crawl' : 'import',
		$submission->crawl ? $submission->crawl->id : $submission->import->id
        );

        // Delete the submission
        $submission->delete();

        // Everything went great
        return true;
    }

    /**
     * Fail the given submission with a collection of errors.
     *
     * @param  Submission $submission
     * @param  MessageBag $errors
     * @return Submission
     */
    public function fail($submission, MessageBag $errors)
    {
        if (! $submission instanceof Submission) {
            $submission = $this->find($submission);
        }

        // Disable validation exception
        $submission->throwOnValidation = false;

        // Save errors and status to submission
        $submission->errors = $errors->toJson();
        $submission->status = 'failure';
        $submission->forceSave(); // Ignore validation rules
    }

    /**
     * Approve the given submission.
     *
     * @param  Submission $submission
     * @return Submission
     */
    public function approve($submission)
    {
        if (! $submission instanceof Submission) {
            $submission = $this->find($submission);
        }

        $submission->status = 'approved';
        $submission->save();
    }
}
