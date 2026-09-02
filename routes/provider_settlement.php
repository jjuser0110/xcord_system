<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Route;

Route::prefix('/provider_settlement')->as('provider_settlement.')->middleware(['auth'])->group(function() {
    Route::get('/index', 'ProviderSettlementController@index')->name('index');
    Route::get('/{provider_settlement}', 'ProviderSettlementController@show')->name('show');
});
