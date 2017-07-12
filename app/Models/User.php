<?php

namespace App\Models;

use Hash;
use Tiny;
use Storage;
use LaravelArdent\Ardent\Ardent;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Ardent
{
    use SoftDeletes;

    public static $rules = [
        'first_name' => 'required',
        'last_name'  => 'required',
        'email'      => 'required|email|unique:users',
        'password'   => 'required',
        'role'       => 'required',
        'status'     => 'required',
    ];

    public $fillable = [
        'first_name',
        'last_name',
        'email',
        'role',
        'default_account',
        'status'
    ];

    public function checkPassword($password)
    {
        return Hash::check($password, $this->password);
    }

    public function canAccessAccount($account)
    {
        if ($account instanceof Account) {
            $account = $account->id;
        }

        $accounts = $this->accessible_accounts->pluck('id');

        return in_array($account, $accounts->toArray());
    }

    public function getAccessibleAccountsAttribute()
    {
        if ('admin' == $this->role) {
            return app('App\Contracts\AccountRepositoryInterface')->all();
        }

        return $this->accounts;
    }

    public function accounts()
    {
        return $this->belongsToMany(Account::class);
    }

    public function getNameAttribute()
    {
        return $this->attributes['first_name'].' '.$this->attributes['last_name'];
    }

    public function getImageAttribute()
    {
        $filename = 'profile_'.Tiny::to($this->id).'.jpg';

        if (Storage::disk('public')->has('profiles/'.$filename)) {
            return asset('storage/profiles/'.$filename);
        }

        return null;
    }

    public function getRole()
    {
        return app('App\Roles\\'.ucfirst($this->role).'Role');
    }
}
