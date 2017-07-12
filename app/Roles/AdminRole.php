<?php

namespace App\Roles;

use App\Contracts\RoleInterface;

class AdminRole extends UserRole implements RoleInterface
{
    public function getPermissions()
    {
        return array_merge(parent::getPermissions(), [

            /////////////////////
            // New permissions //
            /////////////////////

            'access_global_account',

            'discoveries:read',
            'discoveries:write',

            /////////////////////
            // Old permissions //
            /////////////////////

            //Accounts
            'accounts.create',
            'accounts.read',
            'accounts.update',
            'accounts.delete',

            //Assets
            'assets.create',
            'assets.read',
            'assets.update',
            'assets.delete',

            //Users
            'users.create',
            'users.read',
            'users.update',
            'users.delete',

            //Crawlers
            'crawlers.create',
            'crawlers.read',
            'crawlers.update',
            'crawlers.delete',

            //Discoveries
            'discoveries.read',
            'discoveries.update',
            'discoveries.import',

            //Sellers
            'sellers.update',

            //Crawlers
            'crawlers.manage',

            //Enforcers
            'manage_enforcers',

            //Admin Section In General
            'manage_admin',

            //Reports
            'reports.read',
            'reports.log',
            'reports.generate'

        ]);
    }
}
