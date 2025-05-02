<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ReservationController;

Route::get('/register', [AuthController::class, 'showForm'])->name('register.form');
Route::post('/register', [UserController::class, 'register'])->name('register.store');

Route::get('/', function () {
    return view('register');
});
Route::get('/test-route', function () {
    return response()->json(['status' => 'Working without API prefix!']);
});

Route::post('/book-room', [ReservationController::class, 'bookRoom']);

Route::post('/reservations', [ReservationController::class, 'bookRoom']);
Route::post('book-room', [ReservationController::class, 'bookRoom']);
