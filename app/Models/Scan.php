<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Scan extends Model
{
    public $guarded = [];

    public $timestamps = false;

    public $dates = ['started_at', 'ended_at'];

    public function enforcer()
    {
        return $this->belongsTo(Enforcer::class);
    }

    public function discoveries()
    {
        return $this->belongsTo(Enforcer::class);
    }

    public function getMemoryUsageHumanAttribute()
    {
        if (! $this->memory_usage) {
            return null;
        }

        return format_bytes($this->memory_usage);
    }

    public function getDurationAttribute()
    {
        if (! $this->started_at || ! $this->ended_at) {
            return null;
        }

        return $this->started_at->diffInSeconds($this->ended_at);
    }

    public function getDurationHumanAttribute()
    {
        if (! $this->duration) {
            return null;
        }

        return format_seconds($this->duration);
    }

    public function getLogAttribute()
    {
        return base_path('storage/logs/scans/'.$this->id.'.txt');
    }

    public function getLogContentsAttribute()
    {
        if (file_exists($this->log)) {
            $contents = file_get_contents($this->log);
            $modified = \Carbon\Carbon::createFromTimestamp(filemtime($this->log), 'UTC');

            return [$contents,$modified];
        }

        return null;
    }
}
