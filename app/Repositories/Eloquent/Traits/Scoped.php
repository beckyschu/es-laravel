<?php

namespace App\Repositories\Eloquent\Traits;

use Illuminate\Support\Fluent;

trait Scoped
{
    protected $scope;

    public function setScope(array $scope)
    {
        $this->scope = new Fluent($scope);
        return $this;
    }

    public function getScope()
    {
        if (! $this->scope) {
            $this->scope = new Fluent;
        }

        return $this->scope;
    }

    public function resetScope()
    {
        $this->scope = new Fluent;
    }
}
