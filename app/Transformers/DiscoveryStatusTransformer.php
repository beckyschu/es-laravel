<?php

namespace App\Transformers;

use App\Models;
use League\Fractal\TransformerAbstract;

class DiscoveryStatusTransformer extends TransformerAbstract
{
	public function transform(Models\DiscoveryStatus $status)
	{
	    return [
            'id'         => $status->id,
            'valid_from' => $status->valid_from->toIso8601String(),
            'valid_to'   => $status->valid_to ? $status->valid_to->toIso8601String() : null,
            'status'     => $status->status,
            'comment'    => $status->comment,
	    ];
	}
}
