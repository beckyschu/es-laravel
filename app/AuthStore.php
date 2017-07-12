<?php

namespace App;

use App\Exceptions;
use App\Models\User;
use Firebase\JWT\JWT;
use App\Models\Account;
use App\Models\Crawler;
use App\Models\Enforcer;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class AuthStore
{
    protected $rawToken;

    protected $token;

    protected $user;

    protected $account;

    public function getRawToken()
    {
        return $this->rawToken;
    }

    public function getToken()
    {
        return $this->token;
    }

    public function processToken($token)
    {
        if (starts_with($token, 'Bearer ')) {
            $token = str_replace('Bearer ', '', $token);
        }

        try {
            $this->rawToken = $token;
            $this->token    = JWT::decode($token, config('app.key'), ['HS256']);
        } catch (\Firebase\JWT\ExpiredException $e) {
            throw new Exceptions\TokenExpiredException('Your session has expired.');
        }

        if ('user' == $this->token->typ) {
            return $this->user = app('App\Contracts\UserRepositoryInterface')->find($this->token->sub);
        }

        if ('crawler' == $this->token->typ) {
            return $this->user = app('App\Contracts\CrawlerRepositoryInterface')->find($this->token->sub);
        }

        if ('enforcer' == $this->token->typ) {
            return $this->user = app('App\Contracts\EnforcerRepositoryInterface')->find($this->token->sub);
        }
    }

    public function getUser()
    {
        return $this->user;
    }

    public function getUserId()
    {
        if ($user = $this->getUser()) {
            return $user->id;
        }

        return null;
    }

    public function getAccount()
    {
        return $this->account;
    }

    public function getAccountId()
    {
        if ($account = $this->getAccount()) {
            return $account->id;
        }

        return null;
    }

    public function setAccount($account)
    {
        if (! $account instanceof Account) {
            $account = app('App\Contracts\AccountRepositoryInterface')->find($account);
        }

        if (! $this->canAccessAccount($account)) {
            throw new AccessDeniedHttpException('Authenticated user does not have access to account '.$account->id.'.');
        }

        $this->account = $account;
        return $this;
    }

    public function isAuthenticated()
    {
        return $this->user !== null;
    }

    public function isUser()
    {
        return $this->user instanceof User;
    }

    public function isCrawler()
    {
        return $this->user instanceof Crawler;
    }

    public function isEnforcer()
    {
        return $this->user instanceof Enforcer;
    }

    public function can($permission)
    {
        if (! $this->isAuthenticated()) {
            return false;
        }

        return $this->getUser()->getRole()->can($permission);
    }

    public function canAccessAccount($account)
    {
        if ($account instanceof Account) {
            $account = $account->id;
        }

        return $this->getUser()->canAccessAccount($account);
    }
}
