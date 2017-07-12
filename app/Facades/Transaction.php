<?php

namespace App\Facades;

use App\TransactionManager;
use Illuminate\Support\Facades\Facade;

class Transaction extends Facade
{
    protected static function getFacadeAccessor()
    {
        return TransactionManager::class;
    }
}
