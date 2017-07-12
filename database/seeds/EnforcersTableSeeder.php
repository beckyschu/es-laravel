<?php

use App\Models\Enforcer;
use Illuminate\Database\Seeder;

class EnforcersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Enforcer::create([
            'platform' => 'alibaba',
            'status'   => 'healthy'
        ]);

        Enforcer::create([
            'platform' => 'aliexpress',
            'status'   => 'healthy'
        ]);

        Enforcer::create([
            'platform' => 'amazon',
            'status'   => 'healthy'
        ]);

        Enforcer::create([
            'platform' => 'dhgate',
            'status'   => 'healthy'
        ]);

        Enforcer::create([
            'platform' => 'ebay',
            'status'   => 'healthy'
        ]);

        Enforcer::create([
            'platform' => 'fasttech',
            'status'   => 'healthy'
        ]);

        Enforcer::create([
            'platform' => 'sample',
            'status'   => 'healthy'
        ]);
    }
}
