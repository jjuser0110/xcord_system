<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Route;

Route::prefix('/transaction')->as('transaction.')->middleware(['auth'])->group(function() {
    Route::get('/index', 'TransactionController@index')->name('index');
    Route::get('/create', 'TransactionController@create')->name('create');
    Route::post('/store', 'TransactionController@store')->name('store');
});
