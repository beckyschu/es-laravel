<?php

namespace App\Repositories\Eloquent;

use App\Jobs;
use App\Models;
use Carbon\Carbon;
use App\Models\Scan;
use App\Models\Discovery;
use App\Events\Broadcast\ScanWasUpdated;
use App\Contracts\ScanRepositoryInterface;
use App\Contracts\EnforcerRepositoryInterface;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class ScanRepository implements ScanRepositoryInterface
{
    public function find($id)
    {
        return Scan::findOrFail($id);
    }

    public function schedule($enforcers)
    {
        // Accept single enforcer
        if (! is_array($enforcers) && ! $enforcers instanceof \Illuminate\Support\Collection) {
            $enforcers = [$enforcers];
        }

        // Loop provided enforcers
        foreach ($enforcers as $enforcer)
        {
            // Find enforcer
            if (! $enforcer instanceof Models\Enforcer) {
                $enforcer = app('App\Contracts\EnforcerRepositoryInterface')->find($enforcer);
            }

            // Create the scan
            $scan = $enforcer->scans()->create([
                'status' => 'scheduled'
            ]);

            // Queue the job
            dispatch(new Jobs\ScanPlatformJob($scan));
        }

        return true;
    }

    public function start($id, $attributes = [])
    {
        $scan = $this->find($id);

        // Scan has already started
        if ('scheduled' !== $scan->status) {
            throw new ConflictHttpException('Scan has already started');
        }

        // Update scan status
        $this->update($scan, [
            'status'     => 'scanning',
            'started_at' => Carbon::now()
        ]);

        // Update enforcer status
        $scan->enforcer->status = 'scanning';
        $scan->enforcer->save();

        // Return our new scan
        return $scan;
    }

    public function stop($id, $attributes = [])
    {
        $scan = $this->find($id);

        // Scan has already completed
        if ('scanning' !== $scan->status) {
            throw new ConflictHttpException('Scan has already completed');
        }

        // Update scan data
        $this->update($scan, [
            'status'   => 'complete',
            'ended_at' => Carbon::now()
        ]);

        // Update enforcer status
        $scan->enforcer->status = 'healthy';
        $scan->enforcer->save();

        return $scan;
    }

    public function fail(Scan $scan)
    {
        $this->update($scan, [
            'status' => 'failure'
        ]);

        $scan->enforcer->status = 'failure';
        $scan->enforcer->save();

        return $scan;
    }

    public function update(Scan $scan, $attributes)
    {
        $scan->fill($attributes);
        $scan->save();

        event(new ScanWasUpdated($scan));
    }
}
