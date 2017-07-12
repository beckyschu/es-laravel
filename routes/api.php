<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::group(['middleware' => 'auth'], function ()
{
    ///////////
    // Users //
    ///////////

    Route::get('me', 'UsersController@me');
    Route::get('users', 'UsersController@listing');
    Route::get('users/{id}', 'UsersController@show');
    Route::post('users', 'UsersController@create');
    Route::patch('users/{id}', 'UsersController@update');
    Route::delete('users/{id}', 'UsersController@delete');
    Route::post('users/{id}/image', 'UsersController@updateImage');
    Route::post('users/{id}/reset-password', 'UsersController@resetPassword');
    Route::delete('users/{id}/relationships/accounts', 'UsersController@detachAccounts');
    Route::post('users/{id}/relationships/accounts', 'UsersController@attachAccounts');
    Route::get('users/{id}/events', 'UsersController@showEvents');

    //////////////
    // Accounts //
    //////////////

    Route::get('accounts', 'AccountsController@listing');
    Route::get('accounts', 'AccountsController@listing');
    Route::get('accounts/{id}', 'AccountsController@show');
    Route::patch('accounts/{id}', 'AccountsController@update');
    Route::post('accounts', 'AccountsController@create');
    Route::delete('accounts/{id}/relationships/users', 'AccountsController@detachUsers');
    Route::post('accounts/{id}/relationships/users', 'AccountsController@attachUsers');
    Route::get('accounts/{id}/assets', 'AssetsController@listingForAccount');
    Route::delete('accounts/{id}', 'AccountsController@delete');

    ////////////
    // Assets //
    ////////////

    Route::get('assets', 'AssetsController@listing');
    Route::get('assets/{id}', 'AssetsController@show');
    Route::patch('assets/{id}', 'AssetsController@update');
    Route::delete('assets/{id}', 'AssetsController@delete');
    Route::post('assets', 'AssetsController@create');


    //////////////////////
    // Keyword Settings //
    //////////////////////

    Route::patch('keywords/{keywordId}/settings/{id}', 'KeywordsController@updateSetting');
    Route::post('keywords/{keywordId}/settings', 'KeywordsController@createSetting');

    ///////////////////////
    // Keyword Schedules //
    ///////////////////////

    Route::patch('keywords/{keywordId}/schedules/{id}', 'KeywordsController@updateSchedule');
    Route::post('keywords/{keywordId}/schedules', 'KeywordsController@createSchedule');

    //////////////
    // Keywords //
    //////////////

    Route::get('keywords/{id}', 'KeywordsController@show');
    Route::patch('keywords/{id}', 'KeywordsController@update');
    Route::delete('keywords/{id}', 'KeywordsController@delete');
    Route::post('keywords', 'KeywordsController@create');

    ///////////
    // Rules //
    ///////////

    Route::get('rules', 'RulesController@listing');
    Route::get('rules/{id}', 'RulesController@show');
    Route::patch('rules/{id}', 'RulesController@update');
    Route::post('rules', 'RulesController@create');

    /////////////////
    // Discoveries //
    /////////////////

    Route::get('discoveries', 'DiscoveriesController@listing');
    Route::get('discoveries/export', 'DiscoveriesController@export');
    Route::patch('discoveries', 'DiscoveriesController@massUpdateStatus');
    Route::get('discoveries/filters/{slug}', 'DiscoveriesController@searchFilterOptions');
    Route::post('discoveries/filters/{slug}', 'DiscoveriesController@searchFilterOptions');
    Route::get('discoveries/{id}', 'DiscoveriesController@show');
    Route::patch('discoveries/{id}', 'DiscoveriesController@update');
    Route::group(['middleware' => ['auth-crawler']], function () {
        Route::post('discoveries', 'DiscoveriesController@discover');
    });

    /////////////////
    // Submissions //
    /////////////////

    Route::post('submissions', 'SubmissionsController@create');

    /////////////
    // Sellers //
    /////////////

    Route::get('sellers', 'SellersController@listing');
    Route::patch('sellers', 'SellersController@massUpdateFlags');
    Route::get('sellers/{id}', 'SellersController@show');
    Route::patch('sellers/{id}', 'SellersController@update');

    ////////////
    // Events //
    ////////////

    Route::get('transactions', 'TransactionsController@listing');
    Route::post('transactions/{id}/undo', 'TransactionsController@undo');

    /////////////
    // Reports //
    /////////////

    Route::get('reports/discovery-statuses', 'ReportsController@discoveryStatuses');
    Route::get('reports/daily-discovery-statuses', 'ReportsController@dailyDiscoveryStatuses');
    Route::get('reports/seller-statuses', 'ReportsController@sellerStatuses');
    Route::get('reports/daily-seller-statuses', 'ReportsController@dailySellerStatuses');
    Route::get('reports/daily-avg-prices', 'ReportsController@dailyAvgPrices');
    Route::get('reports/platform-status-counts', 'ReportsController@platformStatusCounts');
    Route::get('reports/top-sellers', 'ReportsController@topSellers');
    Route::get('reports/location-breakdown', 'ReportsController@locationBreakdown');

    //////////////////////
    // Report Generator //
    /////////////////////

    Route::get('reports', 'ReportGeneratorController@getReports');
    Route::get('reports/{id}', 'ReportGeneratorController@getReport');
    Route::post('reports', 'ReportGeneratorController@saveReport');
    Route::delete('reports', 'ReportGeneratorController@deleteReports');
    Route::post('reports/pdf', 'ReportGeneratorController@generatePdf');
    Route::post('reports/download', 'ReportGeneratorController@downloadReports');
    Route::post('reports/logos', 'ReportGeneratorController@uploadLogo');
    Route::post('reports/{id}', 'ReportGeneratorController@saveReport');
    Route::post('reports/{id}/pdf', 'ReportGeneratorController@generatePdf');

    //////////////
    // Crawlers //
    //////////////

    Route::get('crawlers', 'CrawlersController@listing');
    Route::get('crawlers/{id}', 'CrawlersController@show');
    Route::get('crawlers/{id}/token', 'CrawlersController@token');
    Route::post('crawlers/{id}/reset', 'CrawlersController@reset');

    ////////////
    // Crawls //
    ////////////

    Route::get('crawls/{id}', 'CrawlsController@show');
    Route::get('crawls/{id}/log', 'CrawlsController@showLog');
    Route::post('crawls/{id}/cancel', 'CrawlsController@cancel');
    Route::post('crawls/schedule', 'CrawlsController@schedule');
    Route::group(['middleware' => ['auth-crawler']], function () {
        Route::post('crawls/{id}/start', 'CrawlsController@start');
        Route::post('crawls/{id}/stop', 'CrawlsController@stop');
        Route::patch('crawls/{id}', 'CrawlsController@update');
    });

    /////////////
    // Imports //
    /////////////

    Route::post('imports', 'ImportsController@import');

    ///////////////
    // Enforcers //
    ///////////////

    Route::get('enforcers', 'EnforcersController@listing');
    Route::get('enforcers/{id}', 'EnforcersController@show');
    Route::get('enforcers/{id}/token', 'EnforcersController@token');
    Route::post('enforcers/{id}/reset', 'EnforcersController@reset');

    ///////////
    // Scans //
    ///////////

    Route::post('scans', 'ScansController@schedule');
    Route::get('scans/{id}', 'ScansController@show');
    Route::get('scans/{id}/log', 'ScansController@showLog');
    Route::post('scans/{id}/cancel', 'ScansController@cancel');
    Route::group(['middleware' => ['auth-enforcer']], function () {
        Route::post('scans/{id}/start', 'ScansController@start');
        Route::post('scans/{id}/stop', 'ScansController@stop');
    });

});

////////////////////
// Authentication //
////////////////////

Route::post('auth', 'AuthController@auth');

////////////////////////////////
// Public Reports (PDF Layer) //
////////////////////////////////

Route::get('reports/{id}/render', 'ReportGeneratorController@renderPdf');