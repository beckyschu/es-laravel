<?php

namespace App\Repositories\Eloquent;

use App\Models;
use App\Contracts;
use App\Exceptions;

use App\Models\Asset;
use App\Exceptions\ValidationException;
use App\Contracts\AssetRepositoryInterface;
use App\Contracts\AccountRepositoryInterface;
use App\Repositories\Eloquent\Traits\Accountable;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class AssetRepository implements Contracts\AssetRepositoryInterface
{
    use Accountable;

    public function find($id)
    {
        return Asset::findOrFail($id);
    }

    /**
     * Return first asset with the provided keyword.
     *
     * @param  $keyword
     * @return Models\Asset|null
     */
    public function findByKeyword($keyword)
    {
        $keyword = Models\Keyword::query()
            ->where(\DB::raw("LOWER(keyword)"), strtolower($keyword))
            ->first();

        if ($keyword) {
            return $keyword->asset;
        }

	    return null;
	}

    public function all()
    {
        $query = Asset::query();

        if ($asset = $this->getAccount()) {
            $query = $query->where('account_id', $asset->id);
        }

        return $query
            ->orderBy('name', 'ASC')
            ->get();
    }

    public function allActive()
    {
        $query = Asset::query();

        if ($asset = $this->getAccount()) {
            $query = $query->where('account_id', $asset->id);
        }

        return $query
            ->where('status', 'active')
            ->orderBy('name', 'ASC')
            ->get();
    }

    public function update($asset, $attributes)
    {
        if (! $asset instanceof Asset) {
            $asset = $this->find($asset);
        }

        //New account ID provided
        if (isset($attributes['account']) && $attributes['account'])
        {
            if (! $account = app(AccountRepositoryInterface::class)->find($attributes['account'])) {
                throw new BadRequestHttpException('You must select a valid account to associate with an asset.');
            }

            $asset->account()->associate($account);
        }

        if (! $asset->update($attributes)) {
            throw new ValidationException($asset->validationErrors);
        }

        return $asset;
    }

    public function create($attributes)
    {
        $asset = new Asset($attributes);

        //Account ID not provided or invalid
        if (
            ! isset($attributes['account'])
            || (! $account = app(AccountRepositoryInterface::class)->find($attributes['account']))
        ) {
            throw new BadRequestHttpException('You must select a valid account to associate with an asset.');
        }

        $asset->account()->associate($account);

        if (! $asset->save()) {
            throw new ValidationException($asset->validationErrors);
        }

        return $asset;
    }

    /**
     * Delete the provided asset.
     *
     * @param  $asset
     * @param  $permanent Force delete entities
     * @return bool
     */
    public function delete($asset, $permanent = false)
    {
        if (!$asset instanceof Asset) {
            $asset = $this->find($asset);
        }

        if ($permanent) {
            $asset->discoveries()->forceDelete();
            $asset->crawls()->forceDelete();
            $asset->keywords()->forceDelete();
            $asset->forceDelete();
        } else {
            $asset->delete();
        }

        return true;
    }

    public function searchAssets($search, $limit = 100)
    {
        $query = $this->buildQuery();

        if ($limit) {
            $query = $query->limit($limit);
        }

        return $query->select(['id', 'name'])
            ->where('name', 'LIKE', '%'.$search.'%')
            ->orderBy('name', 'ASC')
            ->get();
    }

    protected function buildQuery()
    {
        $query = Asset::query();

        if ($asset = $this->getAccount()) {
            $query = $query->where('account_id', $asset->id);
        }

        return $query;
    }
}
