<?php

$factory->define(App\Models\Account::class, function (Faker\Generator $faker)
{
    return [
        'name'            => $faker->company,
        'address_line1'   => $faker->streetAddress,
        'address_line2'   => $faker->secondaryAddress,
        'address_city'    => $faker->city,
        'address_state'   => $faker->stateAbbr,
        'address_zip'     => $faker->postcode,
        'address_country' => $faker->countryCode,
        'status'          => 'active'
    ];
});
