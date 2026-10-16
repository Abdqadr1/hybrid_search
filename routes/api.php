<?php

use App\Http\Controllers\Api\DocumentController;
use App\Http\Controllers\Api\SearchController;
use Illuminate\Support\Facades\Route;

Route::post('/documents', [DocumentController::class, 'store'])
    ->middleware('throttle:30,1')
    ->name('documents.store');

Route::get('/search', SearchController::class)
    ->middleware('throttle:60,1')
    ->name('search.index');
