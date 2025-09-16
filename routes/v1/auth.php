<?php

use App\Http\Controllers\Admin\AuthController;
use Illuminate\Support\Facades\Route;

Route::controller(AuthController::class)->group(function ($route) {
    $route->post('super-admin', 'loginSuperAdmin');
    $route->post('register', 'register');
    $route->post('login', 'login');
    $route->post('check-login','checkLogin');
    $route->post('login-staff','loginStaff');
    $route->post('send-otp','sendOTP');
    $route->post('verify-otp','verifyOTP');
});


// Authenticated routes
Route::middleware('auth:sanctum')->controller(AuthController::class)->group(function ($route) {
    $route->post('logout', 'logout');
    $route->post('logout-staff', 'logoutStaff');
});

