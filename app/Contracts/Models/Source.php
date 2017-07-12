<?php

namespace App\Contracts\Models;

interface Source
{
    /**
     * Increment the submission count for this source.
     *
     * @return void
     */
    public function incrementSubmissionCount();

    /**
     * Increment the accepted count for this source.
     *
     * @return void
     */
    public function incrementAcceptedCount();

    /**
     * Increment the rejected count for this source.
     *
     * @return void
     */
    public function incrementRejectedCount();

    /**
     * Return an asset for this source.
     *
     * @return App\Models\Asset
     */
    public function getAsset();

    /**
     * Return an platform for this source.
     *
     * @return string
     */
    public function getPlatform();

    /**
     * Get the discovery comment that should be emmited for this source.
     *
     * @return string
     */
    public function getDiscoveryComment();
}
