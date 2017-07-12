<?php

$factory->define(App\Models\User::class, function (Faker\Generator $faker)
{
    return [
        'first_name' => $faker->firstName,
        'last_name'  => $faker->lastName,
        'email'      => $faker->safeEmail,
        'password'   => Hash::make('password'),
        'role'       => 'admin',
        'status'     => 'active'
    ];
});
