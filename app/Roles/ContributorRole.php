<?php

namespace App\Roles;

use App\Contracts\RoleInterface;

class ContributorRole extends AbstractRole implements RoleInterface
{
    public function getPermissions()
    {
        return [

            //Discoveries
            'discoveries.read',
            'discoveries.update',

            'discoveries:read',
            'discoveries:write',

        ];
    }
}
