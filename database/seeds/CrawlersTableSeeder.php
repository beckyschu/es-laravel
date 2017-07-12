<?php

use App\Models\Crawler;
use Illuminate\Database\Seeder;

class CrawlersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Crawler::create([
            'platform' => 'alibaba',
            'status'   => 'healthy'
        ]);

        Crawler::create([
            'platform' => 'aliexpress',
            'status'   => 'healthy'
        ]);

        Crawler::create([
            'platform' => 'amazon',
            'status'   => 'healthy'
        ]);

        Crawler::create([
            'platform' => 'dhgate',
            'status'   => 'healthy'
        ]);

        Crawler::create([
            'platform' => 'ebay',
            'status'   => 'healthy'
        ]);

        Crawler::create([
            'platform' => 'fasttech',
            'status'   => 'healthy'
        ]);

        Crawler::create([
            'platform' => 'wish',
            'status'   => 'healthy'
        ]);

        Crawler::create([
            'platform' => 'lelong',
            'status'   => 'healthy'
        ]);

        Crawler::create([
            'platform' => 'taobao',
            'status'   => 'healthy'
        ]);

        Crawler::create([
            'platform' => 'made-in-china',
            'status'   => 'healthy'
        ]);

        Crawler::create([
            'platform' => 'bukalapak',
            'status'   => 'healthy'
        ]);

        Crawler::create([
            'platform' => '1688',
            'status'   => 'healthy'
        ]);

        Crawler::create([
            'platform' => 'lazada',
            'status'   => 'healthy'
        ]);

        Crawler::create([
            'platform' => 'everychina',
            'status'   => 'healthy'
        ]);

        Crawler::create([
            'platform' => 'diytrade',
            'status'   => 'healthy'
        ]);

        Crawler::create([
            'platform' => 'instagram',
            'status'   => 'healthy'
        ]);

        Crawler::create([
            'platform' => 'website',
            'status'   => 'healthy'
        ]);

    }
}
