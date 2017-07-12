<?php

namespace App\Helpers;

use Illuminate\Http\Request;

class DiscoveryStatusHelper
{
    protected $statuses = [
        'discovered' => 'Discovered',
        'enforce'    => 'Enforce',
        'pending'    => 'Pending',
        'closed'     => 'Closed',
        'authorized' => 'Authorized',
        'flagged'    => 'Flagged',
        'price'      => 'Price flag',
        'resubmit'   => 'Resubmit',
        'rejected'   => 'Rejected',
        'regressed'  => 'Regressed',
        'consumer'   => 'Consumer'
    ];

    public function getStatuses()
    {
        return $this->statuses;
    }

    public function getKeys()
    {
        return array_keys($this->statuses);
    }
}
