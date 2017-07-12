<?php

namespace App\Models;

use Carbon\Carbon;
use Ramsey\Uuid\Uuid;
use Illuminate\Database\Eloquent\Model;

class Sighting extends Model
{
    public $timestamps = false;

    public $dates = ['created_at'];

    protected $fillable = ['source_type', 'source_id'];

    protected static function boot()
    {
        static::creating(function ($sighting) {
            $sighting->id = Uuid::uuid4();
            $sighting->created_at = Carbon::now();
        });
    }

    public function discovery()
    {
        return $this->belongsTo(Discovery::class);
    }
}
