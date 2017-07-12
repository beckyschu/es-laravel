<?php

use App\Models\Account;
use Illuminate\Database\Seeder;

class AccountsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Account::create([
            'name'         => 'Beyond Vape',
            'primary_user' => 1,
            'status'       => 'active'
        ]);
    }
}
