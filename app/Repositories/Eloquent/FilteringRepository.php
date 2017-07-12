<?php

namespace App\Repositories\Eloquent;

use Redis;
use Ramsey\Uuid\Uuid;
use Predis\Pipeline\Pipeline;
use App\Helpers\CountryHelper;
use App\Contracts\AssetRepositoryInterface;
use App\Contracts\SellerRepositoryInterface;
use App\Contracts\DiscoveryRepositoryInterface;
use App\Repositories\Eloquent\Traits\Accountable;

class FilteringRepository
{
    use Accountable;

    protected $assets;

    public function __construct(
        AssetRepositoryInterface $assets,
        DiscoveryRepositoryInterface $discoveries,
        SellerRepositoryInterface $sellers
    ) {
        $this->assets      = $assets;
        $this->discoveries = $discoveries;
        $this->sellers     = $sellers;
    }

    public function searchFilterOptions($slug, $query)
    {
        // Apply account constraint to discoveries repo
        // @todo Implement some sort of dependency cascade on the "setAccount"
        // accountable method
        if ($account = $this->getAccount()) {
            $this->assets->setAccount($account);
            $this->discoveries->setAccount($account);
            $this->sellers->setAccount($account);
        }

        // No query provided, disable limit
        $limit = $query == null ? null : 100;

        if ('category' == $slug) {
            return $this->searchCategoryOptions($query, $limit);
        }

        if ('asset' == $slug) {
            return $this->searchAssetOptions($query, $limit);
        }

        if ('seller' == $slug) {
            return $this->searchSellerOptions($query, $limit);
        }

        if ('origin' == $slug) {
            return $this->searchOriginOptions($query, $limit);
        }

        return [];
    }

    protected function searchCategoryOptions($query = null, $limit = 100)
    {
        // Fetch 100 categories for query
        return $this->discoveries->searchCategories($query, $limit);
    }

    protected function searchAssetOptions($query = null, $limit = 100)
    {
        // Fetch 100 assets for query
        $assets = $this->assets->searchAssets($query, $limit);

        // Return array of assets
        return $assets->map(function ($asset) {
            return [
                'id'    => $asset->id,
                'label' => $asset->name
            ];
        });
    }

    protected function searchSellerOptions($query = null, $limit = 100)
    {
        // Fetch 100 assets for query
        $sellers = $this->sellers->searchSellers($query, $limit);

        // Return array of assets
        return $sellers->map(function ($seller) {
            return [
                'id'    => $seller->id,
                'label' => $seller->name.' ('.$seller->platform.')'
            ];
        });
    }

    protected function searchOriginOptions($query = null, $limit = 100)
    {
        // Fetch 100 origins for query
        return $this->discoveries->searchOrigins($query, $limit);
    }
}
