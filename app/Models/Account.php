<?php

namespace App\Models;

use LaravelArdent\Ardent\Ardent;
use Illuminate\Database\Eloquent\SoftDeletes;

class Account extends Ardent
{
    use SoftDeletes;

    protected $guarded = ['created_at', 'updated_at'];

    public static $rules = [
        'name'   => 'required|unique:accounts,name',
        'status' => 'required',
    ];

    public function assets()
    {
        return $this->hasMany(Asset::class);
    }

    public function sellers()
    {
        return $this->hasMany(Seller::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class);
    }
}
