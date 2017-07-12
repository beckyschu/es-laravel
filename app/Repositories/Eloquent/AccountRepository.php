<?php

namespace App\Repositories\Eloquent;

use App\Models;
use App\Contracts;
use App\Exceptions;
use App\Repositories;

class AccountRepository implements Contracts\AccountRepositoryInterface
{
    /**
     * Return account model with the given ID.
     *
     * @param  int $id
     * @return App\Models\Account
     */
    public function find($id)
    {
        return Models\Account::findOrFail($id);
    }

    /**
     * Return collection containing all account models.
     *
     * @return Illuminate\Database\Eloquent\Collection
     */
    public function all()
    {
        return Models\Account::orderBy('name', 'ASC')->get();
    }

    /**
     * Update account with the given attributes.
     *
     * @param  App\Models\Account|int $account
     * @param  array                  $attributes
     * @return App\Models\Account
     * @throws App\Exceptions\ValidationException
     */
    public function update($account, $attributes)
    {
        if (! $account instanceof Models\Account) {
            $account = $this->find($account);
        }

        $account->fill($attributes);

        if (! $account->updateUniques()) {
            throw new Exceptions\ValidationException($account->validationErrors);
        }

        return $account;
    }

    /**
     * Create a new account with the provided attributes.
     *
     * @param  array $attributes
     * @return App\Models\Account
     * @throws App\Exceptions\ValidationException
     */
    public function create($attributes)
    {
        $account = new Models\Account($attributes);

        if (! $account->save()) {
            throw new Exceptions\ValidationException($account->validationErrors);
        }

        return $account;
    }

    /**
     * Attach provided collection of users to the account.
     *
     * @param  App\Models\Account|int              $account
     * @param  Illuminate\Support\Collection|array $users
     * @return App\Models\Account
     */
    public function attachUsers($account, $users)
    {
        if (! $account instanceof Models\Account) {
            $account = $this->find($account);
        }

        if ($users instanceof \Illuminate\Support\Collection) {
            $users = $users->pluck('id')->toArray();
        }

        $account->users()->sync($users, false);

        return $account;
    }

    /**
     * Detach provided collection of users from the account.
     *
     * @param  App\Models\Account|int              $account
     * @param  Illuminate\Support\Collection|array $users
     * @return App\Models\Account
     */
    public function detachUsers($account, $users)
    {
        if (! $account instanceof Models\Account) {
            $account = $this->find($account);
        }

        if ($users instanceof \Illuminate\Support\Collection) {
            $users = $users->pluck('id')->toArray();
        }

        $account->users()->detach($users);

        return $account;
    }

    /**
     * Delete the provided account.
     *
     * @param  $account
     * @param  $permanent Force delete entities
     * @return bool
     */
    public function delete($account, $permanent = false)
    {
        if (! $account instanceof Models\Account) {
            $account = $this->find($account);
        }

        foreach ($account->assets as $asset) {
            app(Repositories\Eloquent\AssetRepository::class)->delete($asset, $permanent);
        }

        if ($permanent) {
            $account->sellers()->forceDelete();
            $account->forceDelete();
        } else {
            $account->delete();
        }

        return true;
    }
}
