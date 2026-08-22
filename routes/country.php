<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Route;

Route::prefix('/country')->as('country.')->middleware(['auth'])->group(function() {
    Route::get('/index', 'CountryController@index')->name('index');
    Route::get('/create', 'CountryController@create')->name('create');
    Route::post('/store', 'CountryController@store')->name('store');
    Route::get('/edit/{country}', 'CountryController@edit')->name('edit');
    Route::post('/update/{country}', 'CountryController@update')->name('update');
    Route::get('/destroy/{country}', 'CountryController@destroy')->name('destroy');
    Route::get('/switch/{id}', 'CountryController@switch')->name('switch');
});
