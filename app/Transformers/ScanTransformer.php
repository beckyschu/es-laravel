<?php

namespace App\Transformers;

use App\Models\Scan;
use League\Fractal\TransformerAbstract;

class ScanTransformer extends TransformerAbstract
{
    protected $availableIncludes = ['enforcer','discoveries'];

	public function transform(Scan $scan)
	{
	    return [
	        'id'         => $scan->id,
            'status'     => $scan->status,
            'started_at' => $scan->started_at ? $scan->started_at->toIso8601String() : null,
            'ended_at'   => $scan->ended_at ? $scan->ended_at->toIso8601String() : null
	    ];
	}

    public function includeEnforcer(Scan $scan)
    {
        return $this->item($scan->enforcer, new EnforcerTransformer, 'enforcers');
    }

    public function includeDiscoveries(Scan $scan)
    {
        $discoveries = app('App\Contracts\DiscoveryRepositoryInterface')
            ->getPendingForPlatform($scan->enforcer->platform);

        return $this->collection($discoveries, new DiscoveryTransformer, 'discoveries');
    }
}
