<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\ListingController;
use App\Http\Controllers\API\DealerController;


Route::prefix('v1')->group(function () {
    Route::get('cars', [App\Http\Controllers\API\CarController::class, 'getPaginatedList']);
    Route::get('cars/popular-makes', [App\Http\Controllers\API\CarController::class, 'popularMakes']);
    Route::get('cars/{id}', [App\Http\Controllers\API\CarController::class, 'show']);

    Route::post('leads', [App\Http\Controllers\API\LeadController::class, 'store'])
        ->middleware('throttle:leads');

    Route::get('listings', [ListingController::class, 'getPaginatedList']);
    Route::get('listings/fast-browse', [ListingController::class, 'fastBrowse']);
    Route::get('listings/popular-makes', [ListingController::class, 'popularMakes']);
    Route::get('listings/{id}', [ListingController::class, 'show']);
    Route::post('listings/{id}/price', [ListingController::class, 'updatePrice']);
    Route::post('listings/{id}/status', [ListingController::class, 'updateStatus']);

    Route::get('dealers', [DealerController::class, 'getPaginatedList']);
    Route::get('dealers/{id}', [DealerController::class, 'show']);
    Route::post('dealers', [DealerController::class, 'create']);
    Route::post('dealers/{id}', [DealerController::class, 'update']);
    Route::delete('dealers/{id}', [DealerController::class, 'delete']);
    Route::get('dealers/country/{countryCode}', [DealerController::class, 'byCountry']);

    Route::prefix('admin')->middleware('api.key')->group(function () {
        Route::post('cache/purge', [App\Http\Controllers\API\Admin\CacheController::class, 'purge']);
        Route::get('cache/status', [App\Http\Controllers\API\Admin\CacheController::class, 'status']);
    });
});


