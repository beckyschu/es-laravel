<?php

namespace App\Roles;

abstract class AbstractRole
{
    public function can($permission)
    {
        return in_array($permission, $this->getPermissions());
    }

    public function cannot($permission)
    {
        return ! $this->can($permission);
    }

    public function getPermissions()
    {
        return [];
    }
}
