<?php

/*
|--------------------------------------------------------------------------
| Model Factories
|--------------------------------------------------------------------------
|
| Here you may define all of your model factories. Model factories give
| you a convenient way to create models for testing and seeding your
| database. Just tell the factory how a default model should look.
|
*/

$factory->define(App\BillingGroup::class, function (Faker\Generator $faker)
{
    return [
        'price_group_name'                  => 'Price group '.$faker->randomDigitNotNull,
        'discovery_monthly_price'           => 20.00,
        'discovery_monthly_limit'           => 20.00,
        'discovery_price_per_listing'       => null,
        'infringe_action_price'             => null,
        'infringe_action_limit'             => null,
        'infringe_action_price_after_limit' => null
    ];
});

$factory->define(App\AssetType::class, function (Faker\Generator $faker)
{
    return [
        'accounts_assets_type_name' => $faker->word,
    ];
});

$factory->defineAs(App\AssetType::class, 'concrete', function () use ($factory)
{
    return [
        'accounts_assets_type_name' => 'Test asset type',
    ];
});

$factory->define(App\Discovery::class, function (Faker\Generator $faker)
{
    return [
        'accounts_assets_id'         => $faker->numberBetween(1,5),
        'seller_id'                  => $faker->numberBetween(1,6),
        'discovery_search_keyword'   => $faker->words(3, true),
        'discovery_data_platform_id' => $faker->numberBetween(1,3),
        'url'                        => $faker->url,
        'product_price'              => $faker->randomFloat(2, 1, 1000),
        'picture_url'                => $faker->imageUrl(300, 300, 'cats'),
        'product_sku'                => $faker->word,
        'listing_title'              => $faker->sentence,
        'product_availability'       => $faker->randomElement([null, 'Usually ships within '.$faker->numberBetween(1, 5).' to '.$faker->numberBetween(6, 10).' days.']),
        'shipping_information'       => $faker->randomElement([null, '+ $'.$faker->randomFloat(2, 1, 50).' shipping']),
        'seller_information'         => "[{u'Website': u'No. 111, Building M07, Metal Zone, China South City, Pinghu Town, Longgang District, Shenzhen, Guangdong, China (Mainland)', u'Website on alibaba.com': u'http://www.smokecm.com', u'Operational Address': u'Shenzhen Su Yun Kang Technology Co., Ltd.'}]",
        'place_of_origin'            => $faker->city.', '.$faker->country,
        'category_of_product'        => $faker->words(3, true),
        'product_stock'              => $faker->randomElement([null, 'Only '.$faker->numberBetween(1,100).' left in stock.', 'In stock.']),
        'product_details'            => "[u'Properties: Other', u'color: copper for Penny Mechanical Mod', u'shipping: DHL/EMS/UPS/TNT etc', u'Model Number: penny Mod', u'size: 65mm*25mm*24mm', u'Battery type: 18650', u'produce name: 2014 Newest Mechanical Mod Red Copper Penny Mod ecigs penny copper mod', u'Brand Name: conzay', u'package:: Gift Box', u'510/ego thread: 510', u'Place of Origin: Guangdong, China (Mainland)', u'material: copper', u'pin: copper pin', u'Warrenty: 6 mounths']",
        'date_created'               => $faker->dateTimeBetween('-1 year')->format('Y-m-d H:i:s'),
        'date_modified'              => $faker->randomElement([null, $faker->dateTimeBetween('-1 year')->format('Y-m-d H:i:s')]),
        'date_last_seen'             => $faker->dateTimeBetween('-1 year')->format('Y-m-d H:i:s'),
        'discovery_data_status'      => $faker->numberBetween(1,6),
        'discovery_data_comments'    => $faker->randomElement([null, $faker->paragraph])
    ];
});

$factory->defineAs(App\Discovery::class, 'concrete', function () use ($factory)
{
    return [
        'accounts_assets_id'         => 1,
        'seller_id'                  => 5,
        'discovery_search_keyword'   => 'Foobar Keyword',
        'discovery_data_platform_id' => 2,
        'url'                        => 'http://example.com/dodgy-products/foobar',
        'product_price'              => 67.50,
        'picture_url'                => 'http://example.com/images/foobar.jpg',
        'product_sku'                => 'ABC123',
        'listing_title'              => 'Amazing dodgy Foobar products',
        'product_availability'       => 'Usually ships within 2 days.',
        'shipping_information'       => '+ $10 shipping',
        'seller_information'         => "[{u'Website': u'No. 111, Building M07, Metal Zone, China South City, Pinghu Town, Longgang District, Shenzhen, Guangdong, China (Mainland)', u'Website on alibaba.com': u'http://www.smokecm.com', u'Operational Address': u'Shenzhen Su Yun Kang Technology Co., Ltd.'}]",
        'place_of_origin'            => 'Dodgetown, China',
        'category_of_product'        => 'Dodgy Products',
        'product_stock'              => 'Only 6 left in stock.',
        'product_details'            => "[u'Properties: Other', u'color: copper for Penny Mechanical Mod', u'shipping: DHL/EMS/UPS/TNT etc', u'Model Number: penny Mod', u'size: 65mm*25mm*24mm', u'Battery type: 18650', u'produce name: 2014 Newest Mechanical Mod Red Copper Penny Mod ecigs penny copper mod', u'Brand Name: conzay', u'package:: Gift Box', u'510/ego thread: 510', u'Place of Origin: Guangdong, China (Mainland)', u'material: copper', u'pin: copper pin', u'Warrenty: 6 mounths']",
        'date_created'               => '2016-01-01 15:00:00',
        'date_modified'              => '2016-01-05 16:00:00',
        'date_last_seen'             => '2016-01-06 17:00:00',
        'discovery_data_status'      => 2,
        'discovery_data_comments'    => 'Here are the finders comments. And another sentence.'
    ];
});

$factory->define(App\DiscoveryPlatform::class, function (Faker\Generator $faker)
{
    return [
        'platform_name' => $faker->word.' platform',
    ];
});

$factory->defineAs(App\DiscoveryPlatform::class, 'concrete', function () use ($factory)
{
    return [
        'platform_name' => 'Test platform',
    ];
});

$factory->define(App\DiscoveryStatus::class, function (Faker\Generator $faker)
{
    return [
        'status_name'  => $faker->word,
        'status_color' => $faker->randomElement(['blue', 'green', 'grey', 'orange', 'black', 'red']),
        'is_flagged'   => $faker->numberBetween(0,1)
    ];
});

$factory->defineAs(App\DiscoveryStatus::class, 'concrete', function () use ($factory)
{
    return [
        'status_name'  => 'Test status',
        'status_color' => 'blue',
        'is_flagged'   => 1
    ];
});

$factory->define(App\DiscoveryStatusUpdate::class, function (Faker\Generator $faker)
{
    return [
        'discovery_id'            => $faker->numberBetween(125800,125900),
        'old_discovery_status_id' => $faker->numberBetween(1,6),
        'new_discovery_status_id' => $faker->numberBetween(1,6),
        'date_added'              => $faker->dateTimeBetween('-1 year')->format('Y-m-d H:i:s'),
        'comments'                => $faker->randomElement([null, $faker->paragraph]),
        'users_id'                => $faker->numberBetween(1,5)
    ];
});

$factory->defineAs(App\DiscoveryStatusUpdate::class, 'concrete', function () use ($factory)
{
    return [
        'discovery_id'            => 500,
        'old_discovery_status_id' => 1,
        'new_discovery_status_id' => 2,
        'date_added'              => '2016-01-01 15:00:00',
        'comments'                => 'Foobar comment',
        'users_id'                => 2
    ];
});

$factory->define(App\Seller::class, function (Faker\Generator $faker)
{
    return [
        'platform_id'     => $faker->numberBetween(1,3),
        'name'            => $faker->company,
        'company'         => $faker->company,
        'website'         => $faker->url,
        'address_line1'   => $faker->streetAddress,
        'address_line2'   => $faker->secondaryAddress,
        'address_city'    => $faker->city,
        'address_state'   => $faker->stateAbbr,
        'address_zipcode' => $faker->postcode,
        'address_country' => $faker->countryCode,
        'status'          => $faker->randomElement(['new', 'enforce', 'pending', 'closed']),
        'created_at'      => $faker->dateTimeBetween('-1 year')->format('Y-m-d H:i:s'),
        'updated_at'      => $faker->dateTimeBetween('-1 year')->format('Y-m-d H:i:s'),
    ];
});

$factory->defineAs(App\Seller::class, 'concrete', function () use ($factory)
{
    return [
        'platform_id'     => 2,
        'name'            => 'ACME Seller',
        'company'         => 'ACME Company',
        'website'         => 'http://example.com/ACME',
        'address_line1'   => '123 Sample Street',
        'address_line2'   => '80 Line 2',
        'address_city'    => 'Southend',
        'address_state'   => 'Essex',
        'address_zipcode' => 'SS68AG',
        'address_country' => 'UK',
        'status'          => 'new',
        'created_at'      => '2015-01-05 12:01:02',
        'updated_at'      => '2015-01-07 10:32:09',
    ];
});
