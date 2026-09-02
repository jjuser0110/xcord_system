<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Route;

Route::prefix('/bank_snapshot')->as('bank_snapshot.')->middleware(['auth'])->group(function() {
    Route::get('/index', 'BankSnapshotController@index')->name('index');

});
