<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class SellerStatus extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    protected $dates = ['valid_from', 'valid_to'];

    public function seller()
    {
        return $this->belongsTo(Seller::class);
    }

    public function __toString()
    {
        return $this->status;
    }

    public static function boot()
    {
        parent::boot();

        // Ensure that we set validation dates and invalidate old rows
        static::creating(function ($status) {

            // Datetime that the new status should be valid from
            $time = Carbon::now();

            // Update validation datetimes
            $status->valid_from = $time;
            $status->valid_to   = null;

            // Invalidate the previous rows
            static::query()
                ->where('seller_id', '=', $status->seller_id)
                ->update([
                    'valid_to' => $time->copy()->subSeconds(1)
                ]);

            // Continue with the creation process
            return true;

        });
    }
}
