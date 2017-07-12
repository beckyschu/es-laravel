<?php

$factory->define(App\Models\Asset::class, function (Faker\Generator $faker)
{
    return [
        'name'             => $faker->bs,
        'description'      => $faker->paragraph,
        'keywords'         => ['foo','bar'],
        'counter_keywords' => ['twit','twoo'],
        'status'           => 'active'
    ];
});
