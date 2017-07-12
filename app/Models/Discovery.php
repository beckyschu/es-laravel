<?php

namespace App\Models;

use App;
use Exception;
use Carbon\Carbon;
use Cake\Chronos\Date;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Discovery extends Model
{
    public $incrementing = false;

    protected $fillable = [
        'platform',
        'title',
        'sku',
        'category',
        'keyword',
        'origin',
        'country',
        'price',
        'picture',
        'url',
        'listing_url',
        'qty_available',
        'qty_sold'
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'last_seen_at'
    ];

    // Yes, these are supposed to be public
    public $resolvedStatus;
    public $pendingStatus;

    public static function boot()
    {
        parent::boot();

        // Ensure that we save pending status after save
        static::saved(function ($discovery) {

            // We don't have a pending status
            if (! $discovery->pendingStatus) return true;

            // Associate and create the pending status
            $discovery->pendingStatus->discovery()->associate($discovery);
            $discovery->pendingStatus->save();

            // Ping transaction to realise it
            \Transaction::ping();

            // Continue with the save process
            return true;

        });
    }

    public function scopeValidOn(Builder $query, \Cake\Chronos\Date $date)
    {
        return $query
            ->whereRaw("CAST('".$date->format('Y-m-d')."' AS DATETIME) BETWEEN discovery_statuses.valid_from AND IFNULL(discovery_statuses.valid_to, NOW())");
    }

    public function scopeValidNow(Builder $query)
    {
        return $query->whereNull('discovery_statuses.valid_to');
    }

    public function account()
    {
        return $this->belongsTo(Account::class)->withTrashed();
    }

    public function asset()
    {
        return $this->belongsTo(Asset::class)->withTrashed();
    }

    public function seller()
    {
        return $this->belongsTo(Seller::class);
    }

    public function sightings()
    {
        return $this->hasMany(Sighting::class);
    }

    public function statuses()
    {
        return $this->hasMany(DiscoveryStatus::class);
    }

    public function addComment($comment)
    {
        $comments = $this->comment;
        $comment = str_replace(["\r", "\n"], '', $comment);
        array_push($comments, Carbon::now()->toIso8601String().': '.$comment);
        $this->comment = implode(PHP_EOL, $comments);
    }

    public function getCommentAttribute()
    {
        if (array_key_exists('comment', $this->attributes)) {
            return explode(PHP_EOL, $this->attributes['comment']);
        }

        return [];
    }

    public function setStatusAttribute()
    {
        throw new Exception('You must use the setStatus() method to set a new status.');
    }

    public function getStatusAttribute()
    {
        // Status may have already been resolved in query
        if (! empty($this->attributes['status'])) {
            return $this->attributes['status'];
        }

        // Return cached status if we have one
        if (! empty($this->attributes['cached_status'])) {
            return $this->attributes['cached_status'];
        }

        // Return the status object
        return $this->status_object;
    }

    public function getStatusObjectAttribute()
    {
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
            return false;
        }

        // Datetime that the new status should be valid from
        $time = Carbon::now();

        // Create a pending status
        $pendingStatus = new DiscoveryStatus([
            'status'       => $status,
            'comment'      => $comment
        ]);

        // Add transaction ID if available
        $pendingStatus->transaction_id = \Transaction::currentId();

        // Set pending status for saving later (see boot method)
        $this->pendingStatus = $pendingStatus;

        // Update cached status
        $this->cached_status = $status;

        // Return self
        return $this;
    }

    public function getRuleDataAttribute()
    {
        // Collect initial rule data
        $ruleData = [
            'discovery' =>
                $this->getAttributes()
                + [
                    'status'              => $this->status_object ? (string) $this->status_object : null,
                    'status_updated_at'   => $this->status_object ? $this->status_object->valid_from->toIso8601String() : null,
                    'status_updated_days' => $this->status_object ? $this->status_object->valid_from->diffInDays(\Carbon\Carbon::now()) : null
                ],
            'seller' => [
                ($this->seller ? $this->seller->getAttributes() : [])
                + [
                    'flag'            => $this->seller ? $this->seller->flag : null,
                    'discovery_count' => $this->seller ? $this->seller->discoveries()->count() : null
                ]
            ]
        ];

        // Standardise keyword and title for comparison
        $ruleData['discovery']['title']   = strtolower($ruleData['discovery']['title']);
        $ruleData['discovery']['keyword'] = strtolower($ruleData['discovery']['keyword']);

        // Return rule data
        return $ruleData;
    }





    public function getLastSeenAtAttribute()
    {
        if (isset($this->attributes['last_seen_at'])) {
            return Carbon::parse($this->attributes['last_seen_at']);
        }

	if(!$this->sighting){
		$this->sighting = $this->sightings()->orderBy('created_at', 'DESC')->first();
	}

	return $this->sighting ? Carbon::parse($this->sighting->created_at) : null;

        //if ($sighting = $this->sightings()->orderBy('created_at', 'DESC')->first()) {
        //    return $sighting->created_at;
        //}

        //return null;
    }

    public function getSourceTypeAttribute()
    {
	if(!$this->sighting){
		$this->sighting = $this->sightings()->orderBy('created_at', 'DESC')->first();
	}
	return $this->sighting ? $this->sighting->source_type : null;
    }

    public function getSourceIdAttribute()
    {
	if(!$this->sighting){
		$this->sighting = $this->sightings()->orderBy('created_at', 'DESC')->first();
	}
	return $this->sighting ? $this->sighting->source_id : null;
    }

    public function getWhenStatusSet($status)
    {
        foreach ($this->revisionHistory->reverse() as $entry)
        {
            if ('status' == $entry->key && $status == $entry->new_value) {
                return $entry->created_at;
            }
        }

        return null;
    }
}
