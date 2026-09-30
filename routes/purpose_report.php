<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Route;

Route::prefix('/purpose_report')->as('purpose_report.')->middleware(['auth'])->group(function() {
    Route::get('/index', 'PurposeReportController@index')->name('index');

});
