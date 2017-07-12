<?php

use App\Models\Asset;
use Illuminate\Database\Seeder;

class AssetsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Asset::create([
            'account_id' => 1,
            'name'       => 'Sample Asset',
            'counter_keywords'   => ['foo','bar'],
            'status'     => 'active'
        ]);
    }
}
