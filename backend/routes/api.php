<?php

use App\Http\Controllers\AdminAnalyticsController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\RatingController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\RestaurantController;
use App\Http\Controllers\TableController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// Javne rute
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::get('/restaurants', [RestaurantController::class, 'index']);
Route::get('/restaurants/{restaurant}', [RestaurantController::class, 'show']);
Route::get('/restaurants/{restaurant}/availability', [ReservationController::class, 'availability']);
Route::get('/restaurants/{restaurant}/tables', [TableController::class, 'index']);

// Zaštićene rute (Sanctum token)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    Route::apiResource('reservations', ReservationController::class)->except(['show']);
    Route::post('/ratings', [RatingController::class, 'store']);

    // Admin rute (dodatna provera role unutar kontrolera)
    Route::post('/restaurants', [RestaurantController::class, 'store']);
    Route::put('/restaurants/{restaurant}', [RestaurantController::class, 'update']);
    Route::delete('/restaurants/{restaurant}', [RestaurantController::class, 'destroy']);

    Route::post('/restaurants/{restaurant}/tables', [TableController::class, 'store']);
    Route::put('/tables/{table}', [TableController::class, 'update']);
    Route::delete('/tables/{table}', [TableController::class, 'destroy']);

    Route::post('/menus/{menu}/dishes', [MenuController::class, 'storeDish']);
    Route::put('/dishes/{dish}', [MenuController::class, 'updateDish']);
    Route::delete('/dishes/{dish}', [MenuController::class, 'destroyDish']);

    Route::get('/users', [UserController::class, 'index']);
    Route::get('/users/{user}', [UserController::class, 'show']);
    Route::put('/users/{user}', [UserController::class, 'update']);
    Route::delete('/users/{user}', [UserController::class, 'destroy']);

    Route::get('/admin/analytics/summary', [AdminAnalyticsController::class, 'summary']);
    Route::get('/admin/analytics/export', [AdminAnalyticsController::class, 'exportCsv']);
});
