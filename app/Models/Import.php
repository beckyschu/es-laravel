<?php

namespace App\Models;

use App\Contracts;
use App\Repositories;
use Illuminate\Database\Eloquent\Model;

class Import extends Model implements Contracts\Models\Source
{
    public $guarded = [];

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

    /**
     * Increment the submission count for this source.
     *
     * @return void
     */
    public function incrementSubmissionCount()
    {
        return app(Repositories\Eloquent\ImportRepository::class)->increment($this, 'submission_count');
    }

    /**
     * Increment the accepted count for this source.
     *
     * @return void
     */
    public function incrementAcceptedCount()
    {
        return app(Repositories\Eloquent\ImportRepository::class)->increment($this, 'accepted_count');
    }

    /**
     * Increment the rejected count for this source.
     *
     * @return void
     */
    public function incrementRejectedCount()
    {
        return app(Repositories\Eloquent\ImportRepository::class)->increment($this, 'rejected_count');
    }

    /**
     * Return an asset for this source.
     *
     * @return App\Models\Asset
     */
    public function getAsset()
    {
        return null;
    }

    /**
     * Return an platform for this source.
     *
     * @return string
     */
    public function getPlatform()
    {
        return $this->platform;
    }

    /**
     * Get the discovery comment that should be emmited for this source.
     *
     * @return string
     */
    public function getDiscoveryComment()
    {
        return 'Discovered in import '.$this->id;
    }
}
