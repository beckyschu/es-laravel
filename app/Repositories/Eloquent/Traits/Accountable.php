<?php

namespace App\Repositories\Eloquent\Traits;

use App\Models\Account;
use App\Contracts\AccountRepositoryInterface;

trait Accountable
{
    protected $account;

    public function setAccount($account)
    {
        if ($account && ! $account instanceof Account) {
            $account = app(AccountRepositoryInterface::class)->find($account);
        }

        $this->account = $account;

        return $this;
    }

    public function getAccount()
    {
        return $this->account;
    }
}
