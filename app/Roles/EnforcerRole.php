<?php

namespace App\Roles;

use App\Contracts\RoleInterface;

class EnforcerRole extends AbstractRole implements RoleInterface
{
    public function getPermissions()
    {
        return array_merge(parent::getPermissions(), [

            'discoveries:write'

        ]);
    }
}
