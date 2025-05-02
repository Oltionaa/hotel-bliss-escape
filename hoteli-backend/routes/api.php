<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\CheckoutController;

Route::post('register', [UserController::class, 'register']);
Route::get('/test', function () {
    return response()->json(['status' => 'Backend connected!']);
});
Route::post('/login', [AuthController::class, 'login']);
Route::post('/search-rooms', [RoomController::class, 'search']);
Route::get('/rooms', [RoomController::class, 'index'])->name('rooms.index');

Route::post('/book-room', [ReservationController::class, 'bookRoom']);
Route::post('/reservations', [ReservationController::class, 'store']);
<<<<<<< HEAD
Route::post('/checkout', [CheckoutController::class, 'processCheckout']);
Route::post("/checkout", [RoomController::class, "checkout"]);
=======
Route::post('/checkout', [CheckoutController::class, 'processCheckout']);
>>>>>>> 7939a173dd73ea95795fb154841479ed00e5f408
