<?php

namespace App\Repositories\Eloquent;

use Firebase\JWT\JWT;
use App\Models\Enforcer;
use App\Contracts\EnforcerRepositoryInterface;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class EnforcerRepository implements EnforcerRepositoryInterface
{
    public function find($id)
    {
        return Enforcer::findOrFail($id);
    }

    public function all()
    {
        return Enforcer::orderBy('platform', 'ASC')->get();
    }

    public function healthy()
    {
        return Enforcer::where('status', 'healthy')->get();
    }

    public function reset($id)
    {
        $enforcer = $this->find($id);

        if (! in_array($enforcer->status, ['failure', 'timeout'])) {
            throw new ConflictHttpException('An enforcer must be in either failure or timeout status to reset.');
        }

        $enforcer->status = 'healthy';
        $enforcer->save();

        return $enforcer;
    }

    public function generateToken($enforcer)
    {
        if (! $enforcer instanceof Enforcer) {
            $enforcer = $this->find($enforcer);
        }

        return JWT::encode([
            'sub' => $enforcer->id,
            'per' => $enforcer->getRole()->getPermissions(),
            'typ' => 'enforcer'
        ], config('app.key'));
    }
}
