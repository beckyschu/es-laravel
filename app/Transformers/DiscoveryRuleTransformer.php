<?php

namespace App\Transformers;

use App\Models;
use League\Fractal\TransformerAbstract;

class DiscoveryRuleTransformer extends TransformerAbstract
{
	public function transform(Models\DiscoveryRule $rule)
	{
	    return [
	        'id'         => $rule->id,
	        'rule'       => $rule->rule,
            'status'     => $rule->status,
            'priority'   => $rule->priority,
	        'comment'    => $rule->comment,
	        'is_locked'  => (bool) $rule->is_locked,
	        'is_active'  => (bool) $rule->is_active,
	        'created_at' => $rule->created_at->toIso8601String(),
	        'updated_at' => $rule->updated_at->toIso8601String()
	    ];
	}
}
