<?php

namespace App\Repositories\Eloquent;

use Firebase\JWT\JWT;
use App\Models\Crawler;
use App\Contracts\CrawlerRepositoryInterface;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class CrawlerRepository implements CrawlerRepositoryInterface
{
    public function find($id)
    {
        return Crawler::findOrFail($id);
    }

    public function all()
    {
        return Crawler::orderBy('platform', 'ASC')->get();
    }

    public function healthy()
    {
        return Crawler::where('status', '!=', 'failure')->get();
    }

    public function reset($id)
    {
        $crawler = $this->find($id);

        if (! in_array($crawler->status, ['failure', 'timeout'])) {
            throw new ConflictException('A crawler must be in either failure or timeout status to reset.');
        }

        $crawler->status = 'healthy';
        $crawler->save();

        return $crawler;
    }

    public function generateToken($crawler)
    {
        if (! $crawler instanceof Crawler) {
            $crawler = $this->find($crawler);
        }

        return JWT::encode([
            'sub' => $crawler->id,
            'per' => $crawler->getRole()->getPermissions(),
            'typ' => 'crawler'
        ], config('app.key'));
    }

    public function update(Crawler $crawler, $attributes)
    {
        $crawler->fill($attributes);
        $crawler->save();
    }
}
