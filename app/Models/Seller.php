<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Seller extends Model
{
    protected $fillable = ['platform', 'name', 'username', 'flag', 'status', 'needs_refresh', 'last_seen_at'];

    protected $dates = ['last_seen_at', 'created_at', 'updated_at'];

    // Yes, these are supposed to be public
    public $resolvedStatus;
    public $pendingStatus;

    public static function boot()
    {
        parent::boot();

        // Ensure that we save pending status after save
        static::saved(function ($seller) {

            // We don't have a pending status
            if (! $seller->pendingStatus) return true;

            // Associate and create the pending status
            $seller->pendingStatus->seller()->associate($seller);
            $seller->pendingStatus->save();

            // Ping transaction to realise it
            \Transaction::ping();

            // Continue with the save process
            return true;

        });
    }

    public function scopeValidOn(\Illuminate\Database\Eloquent\Builder $query, \Cake\Chronos\Date $date)
    {
        return $query
            ->whereRaw("CAST('".$date->format('Y-m-d')."' AS DATETIME) BETWEEN seller_statuses.valid_from AND IFNULL(seller_statuses.valid_to, NOW())");
    }

    public function scopeValidNow(\Illuminate\Database\Eloquent\Builder $query)
    {
        return $query->whereNull('seller_statuses.valid_to');
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function discoveries()
    {
        return $this->hasMany(Discovery::class);
    }

    public function isPossibleConsumer()
    {
        return 1 == $this->discoveries()->count();
    }

    public function statuses()
    {
        return $this->hasMany(SellerStatus::class);
    }

    public function setStatusAttribute()
    {
        throw new \Exception('You must use the setStatus() method to set a new status.');
    }

    public function getStatusAttribute()
    {
        // Status may have already been resolved in query
        if (isset($this->attributes['status'])) {
            return $this->attributes['status'];
        }

        // Return cached status if we have one
        if (! empty($this->attributes['cached_status'])) {
            return $this->attributes['cached_status'];
        }

        // We don't have a resolved status yet, fetch it
        if (! $this->resolvedStatus) {
            $this->resolvedStatus = $this->statuses()->whereNull('valid_to')->first();
        }

        // Return the resolved status
        return $this->resolvedStatus;
    }

    public function setStatus($status, $comment = null)
    {
        // This status is already set
        if ($status == (string) $this->status) {
            return;
        }

        // Datetime that the new status should be valid from
        $time = Carbon::now();

        // Create a pending status
        $pendingStatus = new SellerStatus([
            'status'  => $status,
            'comment' => $comment
        ]);

        // Set pending status for saving later (see boot method)
        $this->pendingStatus = $pendingStatus;

        // Update cached status
        $this->cached_status = $status;

        // Return the pending status
        return $this->pendingStatus;
    }
}
