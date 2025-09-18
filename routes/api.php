<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\ListingController;
use App\Http\Controllers\API\DealerController;

Route::get('/user', fn(Request $request) => $request->user())->middleware('auth:sanctum');

// Car marketplace API routes - Exact specification implementation
Route::prefix('v1')->group(function () {
    // Cars endpoints - Main specification
    Route::get('cars', [App\Http\Controllers\API\CarController::class, 'index']);
    Route::get('cars/{id}', [App\Http\Controllers\API\CarController::class, 'show']);

    // Debug endpoint for testing filters
    Route::get('cars/debug/filters', [App\Http\Controllers\API\CarController::class, 'testFilters']);

    // Lead submission with background scoring
    Route::post('leads', [App\Http\Controllers\API\LeadController::class, 'store'])
        ->middleware('throttle:leads');

    // Admin/Legacy endpoints - Command pattern
    Route::get('listings', [ListingController::class, 'getPaginatedList']);
    Route::get('listings/{id}', [ListingController::class, 'show']);
    Route::get('listings/fast-browse', [ListingController::class, 'fastBrowse']);
    Route::get('listings/popular-makes', [ListingController::class, 'popularMakes']);
    Route::patch('listings/{id}/price', [ListingController::class, 'updatePrice']);
    Route::patch('listings/{id}/status', [ListingController::class, 'updateStatus']);

    Route::get('dealers', [DealerController::class, 'getPaginatedList']);
    Route::get('dealers/{id}', [DealerController::class, 'show']);
    Route::post('dealers', [DealerController::class, 'create']);
    Route::put('dealers/{id}', [DealerController::class, 'update']);
    Route::delete('dealers/{id}', [DealerController::class, 'delete']);
    Route::get('dealers/country/{countryCode}', [DealerController::class, 'byCountry']);

    // Admin endpoints - require API key
    Route::prefix('admin')->middleware('api.key')->group(function () {
        Route::post('cache/purge', [App\Http\Controllers\API\Admin\CacheController::class, 'purge']);
        Route::get('cache/status', [App\Http\Controllers\API\Admin\CacheController::class, 'status']);
    });
});


