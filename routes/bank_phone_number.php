<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Route;

Route::prefix('/bank_phone_number')->as('bank_phone_number.')->middleware(['auth'])->group(function() {
    Route::get('/index', 'BankPhoneNumberController@index')->name('index');
    Route::get('/edit/{bank_phone_number}', 'BankPhoneNumberController@edit')->name('edit');
    Route::post('/update/{bank_phone_number}', 'BankPhoneNumberController@update')->name('update');

});
