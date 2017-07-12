<?php

namespace App\Roles;

use App\Contracts\RoleInterface;

class UserRole extends AbstractRole implements RoleInterface
{
    public function getPermissions()
    {
        return array_merge(parent::getPermissions(), [

            //Accounts
            'accounts.read',
            'accounts.update',

            //Assets
            'assets.read',
            'assets.update',

            //Users
            'users.read',
            'users.update',

            //Reports
            'reports.read',

            //Discoveries
            'discoveries:read',
            'discoveries:write:limited',

        ]);
    }
}
