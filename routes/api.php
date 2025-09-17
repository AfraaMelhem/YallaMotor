<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\ListingController;
use App\Http\Controllers\API\DealerController;


// Car marketplace API routes - Exact specification implementation
Route::prefix('v1')->group(function () {

    // Admin endpoints - require API key
    Route::prefix('admin')->middleware('api.key')->group(function () {
        Route::post('cache/purge', [App\Http\Controllers\API\Admin\CacheController::class, 'purge']);
        Route::get('cache/status', [App\Http\Controllers\API\Admin\CacheController::class, 'status']);
    });
});


