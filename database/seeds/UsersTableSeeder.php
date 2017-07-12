<?php

use App\Models\User;
use Illuminate\Database\Seeder;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        User::create([
            'first_name' => 'Dan',
            'last_name'  => 'Greaves',
            'email'      => 'dan@dangreaves.com',
            'password'   => Hash::make('password'),
            'role'       => 'admin',
            'status'     => 'active'
        ]);
    }
}
