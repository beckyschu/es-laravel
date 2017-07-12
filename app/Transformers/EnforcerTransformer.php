<?php

namespace App\Transformers;

use App\Models\Enforcer;
use League\Fractal\TransformerAbstract;

class EnforcerTransformer extends TransformerAbstract
{
    protected $availableIncludes = ['scans'];

	public function transform(Enforcer $enforcer)
	{
	    return [
	        'id'                     => $enforcer->id,
	        'platform'               => $enforcer->platform,
            'avg_memory_usage'       => $enforcer->avg_memory_usage,
            'avg_memory_usage_human' => $enforcer->avg_memory_usage_human,
            'avg_duration'           => $enforcer->avg_duration,
            'avg_duration_human'     => $enforcer->avg_duration_human,
            'last_healthy_scan'      => $enforcer->last_healthy_scan ? $enforcer->last_healthy_scan->toIso8601String() : null,
            'status'                 => $enforcer->status,
            'created_at'             => $enforcer->created_at->toIso8601String(),
            'updated_at'             => $enforcer->updated_at->toIso8601String()
	    ];
	}

    public function includeScans(Enforcer $enforcer)
    {
        $scans = $enforcer->scans()->orderBy('id', 'DESC')->take(10)->get();

        return $this->collection($scans, new ScanTransformer, 'scans');
    }
}
