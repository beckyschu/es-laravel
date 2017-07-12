<?php

namespace App\Facades;

use App\AuthStore;
use Illuminate\Support\Facades\Facade;

class Auth extends Facade
{
    protected static function getFacadeAccessor()
    {
        return AuthStore::class;
    }
}
