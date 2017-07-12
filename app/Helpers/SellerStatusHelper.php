<?php

namespace App\Helpers;

use Illuminate\Http\Request;

class SellerStatusHelper
{
    protected $statuses = [
        'mixed'      => 'Mixed',
        'new'        => 'New seller',
        'enforce'    => 'Enforce',
        'pending'    => 'Pending',
        'closed'     => 'Closed',
        'repeat'     => 'Repeat',
        'authorized' => 'Authorized',
        'flagged'    => 'Flagged',
        'rejected'   => 'Rejected',
        'consumer'   => 'Consumer',
        'takedown'   => 'Takedown'
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
