<?php

namespace App\Roles;

use App\Contracts\RoleInterface;

class CrawlerRole extends AbstractRole implements RoleInterface
{
    public function getPermissions()
    {
        return array_merge(parent::getPermissions(), [

            //Submissions
            'submissions.create'

        ]);
    }
}
