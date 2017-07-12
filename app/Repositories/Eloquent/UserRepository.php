<?php

namespace App\Repositories\Eloquent;

use Hash;
use Carbon\Carbon;
use App\Models\User;
use App\UpdateResult;
use Firebase\JWT\JWT;
use App\Models\Account;
use App\Exceptions\ValidationException;
use App\Contracts\UserRepositoryInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class UserRepository implements UserRepositoryInterface
{
    public function find($id)
    {
        return User::findOrFail($id);
    }

    public function authenticate($email, $password)
    {
        if (
            (! $user = User::where('email', $email)->first())
            || ! $user->checkPassword($password)
        ) {
            throw new AccessDeniedHttpException('Either your email address or password is incorrect.');
        }

        return $user;
    }

    public function generateTokenForUser(User $user)
    {
        return JWT::encode([
            'sub' => $user->id,
            'exp' => Carbon::now()->addDay()->timestamp,
            'per' => $user->getRole()->getPermissions(),
            'typ' => 'user'
        ], config('app.key'));
    }

    public function findForAccount($account)
    {
        if (! $account instanceof Account) {
            $account = $this->find($account);
        }

        return $account->users;
    }

    public function all()
    {
        return User::query()
            ->orderBy('first_name', 'ASC')
            ->orderBy('last_name', 'ASC')
            ->get();
    }

    public function get($filters = [])
    {
        $query = User::query();

        if (array_key_exists('search', $filters)) {
            $query
                ->where('first_name', 'LIKE', '%'.$filters['search'].'%')
                ->orWhere('last_name', 'LIKE', '%'.$filters['search'].'%')
                ->orWhere('email', 'LIKE', '%'.$filters['search'].'%');
        }

        $query
            ->orderBy('first_name', 'ASC')
            ->orderBy('last_name', 'ASC');

        return $query->get();
    }

    public function create($attributes)
    {
        $user = new User($attributes);

        //Generate and assign a random password
        if (
            array_key_exists('password', $attributes)
            && ($password = trim($attributes['password']))
        ) {
            $user->password = Hash::make($password);
        } else {
            $password = strtolower(str_random(10));
            $user->password = Hash::make($password);
        }

        if (! $user->save()) {
            throw new ValidationException($user->validationErrors);
        }

        return new UpdateResult([
            'user'     => $user,
            'password' => $password
        ]);
    }

    public function update($user, $attributes)
    {
        if (! $user instanceof User) {
            $user = $this->find($user);
        }

        if (! $user->update($attributes)) {
            throw new ValidationException($user->validationErrors);
        }

        if (
            array_key_exists('password', $attributes)
            && ($password = trim($attributes['password']))
        ) {
            $user->password = Hash::make($password);
            $user->save();
        }

        return $user;
    }

    /**
     * Delete the provided user.
     *
     * @param $user
     * @param $permanent
     * @return bool
     */
    public function delete($user, $permanent = false)
    {
        if (! $user instanceof User) {
            $user = $this->find($user);
        }

        if ($permanent) {
            $user->forceDelete();
        } else {
            $user->delete();
        }

        return true;
    }

    public function resetPassword($id)
    {
        $password = strtolower(str_random(10));

        $user = $this->find($id);

        $user->password = Hash::make($password);

        if (! $user->save()) {
            throw new ValidationException($user->validationErrors);
        }

        return $password;
    }

    public function detachAccounts($id, array $accounts)
    {
        $this->find($id)->accounts()->detach($accounts);
        return true;
    }

    public function attachAccounts($id, array $accounts)
    {
        $this->find($id)->accounts()->sync($accounts, false);
        return true;
    }
}
