<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DiscoveryRule extends Model
{
    use SoftDeletes;

    protected $dates = ['created_at', 'updated_at', 'deleted_at'];

    protected $fillable = ['platform', 'comment', 'status', 'priority', 'rule', 'is_active'];

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

    public function getRuleAttribute()
    {
        if (empty($this->attributes['rule'])) {
            return null;
        }

        return json_decode($this->attributes['rule']);
    }

    public function setRuleAttribute($rule)
    {
        if (! $rule) {
            $this->attributes['rule'] = null;
            return;
        }

        $this->attributes['rule'] = json_encode($rule);
    }
}
