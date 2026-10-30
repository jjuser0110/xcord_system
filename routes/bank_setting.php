<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Route;

Route::prefix('/bank_setting')->as('bank_setting.')->middleware(['auth'])->group(function() {
    Route::get('/index', 'BankSettingController@index')->name('index');
    Route::post('/reorder', 'BankSettingController@reorder')->name('reorder');
    Route::get('/create', 'BankSettingController@create')->name('create');
    Route::post('/store', 'BankSettingController@store')->name('store');
    Route::get('/edit/{bank_setting}', 'BankSettingController@edit')->name('edit');
    Route::post('/update/{bank_setting}', 'BankSettingController@update')->name('update');
    Route::post('/inactive/{bank_setting}', 'BankSettingController@inactive')->name('inactive');
    Route::post('/active/{bank_setting}', 'BankSettingController@active')->name('active');
    Route::get('/destroy/{bank_setting}', 'BankSettingController@destroy')->name('destroy');
    Route::get('/log/{bank_setting}', 'BankSettingController@log')->name('log');
    Route::post('/{bank_setting}/update-amount', 'BankSettingController@updateAmount')->name('updateAmount');
});
