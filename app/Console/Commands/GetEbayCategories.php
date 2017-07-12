<?php

namespace App\Console\Commands;

use DTS\eBaySDK;
use App\Models\EbayCategory;
use Illuminate\Console\Command;

class GetEbayCategories extends Command
{
    protected $signature = 'shark:get-ebay-categories';

    protected $description = 'Refresh categories from eBay API';

    public function handle()
    {
        // Instantiate the eBay shopping service
        $service = new eBaySDK\Shopping\Services\ShoppingService([
            'credentials' => config('services.ebay.credentials')
        ]);

        // Fetch categories
        $rootCategory = $service->getCategoryInfo(new eBaySDK\Shopping\Types\GetCategoryInfoRequestType([
            'CategoryID'      => '-1',
            'IncludeSelector' => 'ChildCategories'
        ]));

        // Loop and add categories
        foreach ($rootCategory->CategoryArray->Category as $category) {

            // Do not add root category
            if ('Root' == $category->CategoryName) continue;

            // Create category if not exists
            if (! $cat = EbayCategory::find($category->CategoryID)) {
                $cat = new EbayCategory;
                $cat->id = $category->CategoryID;
            }

            // Update name
            $cat->name = $category->CategoryName;

            // Save category
            $cat->save();

        }

        // Output debug
        $this->info('eBay categories refreshed successfully');
    }
}
