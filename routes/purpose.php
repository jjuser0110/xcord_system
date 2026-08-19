<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Route;

Route::prefix('/purpose')->as('purpose.')->middleware(['auth'])->group(function() {
    Route::get('/index', 'PurposeController@index')->name('index');
    Route::get('/create', 'PurposeController@create')->name('create');
    Route::post('/store', 'PurposeController@store')->name('store');
    Route::get('/edit/{purpose}', 'PurposeController@edit')->name('edit');
    Route::post('/update/{purpose}', 'PurposeController@update')->name('update');
    Route::get('/destroy/{purpose}', 'PurposeController@destroy')->name('destroy');
});
