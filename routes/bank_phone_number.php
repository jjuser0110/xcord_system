<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Route;

Route::prefix('/bank_phone_number')->as('bank_phone_number.')->middleware(['auth'])->group(function() {
    Route::get('/index', 'BankPhoneNumberController@index')->name('index');

});
