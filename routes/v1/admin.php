<?php


use Illuminate\Support\Facades\Route;


Route::prefix('admins')
    ->controller(\App\Http\Controllers\Admin\AdminController::class)
    ->group(function ($route) {
        $route->get('/', 'getPaginatedList');
        $route->get('{id}', 'show');
        $route->post('/', 'create');
        $route->post('{id}', 'update');
        $route->delete('{id}', 'delete');
    });




Route::prefix('free-services')
    ->middleware(['auth:staff'])
    ->controller(\App\Http\Controllers\Admin\FreeServicesController::class)
    ->group(function ($route) {
        $route->get('/', 'getPaginatedList');
        $route->get('/{id}', 'show');
        $route->post('/', 'create');
        $route->post('{id}', 'update');
        $route->delete('{id}', 'delete');
        $route->get('/free/best', 'getBest');


    });





Route::prefix('notifications')
    ->middleware(['auth:staff'])
    ->controller(\App\Http\Controllers\Admin\NotificationController::class)
    ->group(function ($route) {
        $route->get('/', 'all');
        $route->post('/create', 'create');
        $route->delete('/delete/{id}', 'delete');
    });


Route::controller(\App\Http\Controllers\Admin\UserController::class)->group(function ($route) {
    $route->get('/', 'getPaginatedList');
    $route->get('{id}', 'show');
    $route->post('/', 'create');
    $route->post('{id}', 'update');
    $route->delete('{id}', 'delete');
});
