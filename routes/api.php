<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\ListingController;
use App\Http\Controllers\API\DealerController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Car marketplace API routes
Route::prefix('v1')->group(function () {
    // Fast browse endpoint for high-performance car discovery (must come before resource routes)
    Route::get('listings/fast-browse', [ListingController::class, 'fastBrowse']);

    // Popular makes endpoint for filtering assistance
    Route::get('listings/popular-makes', [ListingController::class, 'popularMakes']);

    // Listings routes
    Route::apiResource('listings', ListingController::class)->only(['index', 'show']);

    // Admin routes for managing listings (price/status updates)
    Route::patch('listings/{id}/price', [ListingController::class, 'updatePrice']);
    Route::patch('listings/{id}/status', [ListingController::class, 'updateStatus']);

    // Dealers routes
    Route::apiResource('dealers', DealerController::class)->only(['index', 'show', 'store', 'update']);
    Route::get('dealers/country/{countryCode}', [DealerController::class, 'byCountry']);
});


