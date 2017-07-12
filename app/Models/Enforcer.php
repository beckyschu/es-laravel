<?php

namespace App\Models;

use DB;
use Illuminate\Database\Eloquent\Model;

class Enforcer extends Model
{
    protected $guarded = [];

    public function scans()
    {
        return $this->hasMany(Scan::class);
    }

    public function getAvgMemoryUsageAttribute()
    {
        return (int) $this->scans()->avg('memory_usage');
    }

    public function getAvgMemoryUsageHumanAttribute()
    {
        return format_bytes($this->avg_memory_usage);
    }

    public function getAvgDurationAttribute()
    {
        return (int) $this->scans()->avg(DB::raw('TIMESTAMPDIFF(SECOND, started_at, ended_at)'));
    }

    public function getAvgDurationHumanAttribute()
    {
        return format_seconds($this->avg_duration);
    }

    public function getLastHealthyScanAttribute()
    {
        $scan = $this->scans()
            ->where('status', 'complete')
            ->orderBy('ended_at', 'DESC')
            ->first();

        if ($scan) {
            return $scan->ended_at;
        }

        return null;
    }

    public function isScanning()
    {
        return 'scanning' == $this->status;
    }

    public function hasFailed()
    {
        return in_array($this->status, ['failure', 'timeout']);
    }

    public function getRole()
    {
        return app('App\Roles\EnforcerRole');
    }

    public function getLogAttribute()
    {
        return base_path('storage/logs/scans/'.$this->id.'.txt');
    }
}
