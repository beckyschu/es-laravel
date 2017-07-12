<?php

namespace App\Repositories\Eloquent;

use App\Models, App\Repositories;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class DiscoveryRuleRepository
{
    public function find($id)
    {
        return Models\DiscoveryRule::findOrFail($id);
    }

    public function all()
    {
        return Models\DiscoveryRule::query()
            ->orderBy('priority', 'ASC')
            ->get();
    }

    public function update(Models\DiscoveryRule $rule, $attributes)
    {
        $rule = $this->fill($rule, $attributes);

        $rule->save();

        return $rule;
    }

    public function create($attributes)
    {
        $rule = new Models\DiscoveryRule;

        $rule = $this->fill($rule, $attributes);

        $rule->save();

        return $rule;
    }

    protected function fill(Models\DiscoveryRule $rule, $attributes)
    {
        $rule->fill($attributes);

        // Ensure that rule is nulled if empty
        if (! $rule->rule) {
            $rule->rule = null;
        }

        return $rule;
    }
}
