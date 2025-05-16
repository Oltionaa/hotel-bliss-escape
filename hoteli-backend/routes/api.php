<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\CleanerController;

// Routat ekzistues
Route::post('/register', [AuthController::class, 'register']);
Route::get('/test', function () {
    return response()->json(['status' => 'Backend connected!']);
});
Route::post('/login', [AuthController::class, 'login']);
Route::post('/search-rooms', [RoomController::class, 'search']);
Route::get('/rooms', [ReservationController::class, 'indexRooms'])->name('rooms.index');

Route::post('/book-room', [ReservationController::class, 'bookRoom']);
Route::post('/reservations', [ReservationController::class, 'store']);
Route::post('/checkout', [CheckoutController::class, 'processCheckout']); // Mbajtur për momentin, mund të hiqet nëse është e panevojshme

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/checkout', [ReservationController::class, 'checkout']);
    Route::get('/reservations/user', [ReservationController::class, 'indexApi']);
    Route::put('/reservations/{reservation}', [ReservationController::class, 'updateApi']);
    Route::delete('/reservations/{reservation}', [ReservationController::class, 'destroy']);
    Route::post('/book-room', [ReservationController::class, 'bookRoom']); // Mbajtur për konsistencë
});

// Për pastruesit
Route::get('/cleaner/rooms', [CleanerController::class, 'getDirtyRooms']);
Route::put('/cleaner/rooms/{roomId}/clean', [CleanerController::class, 'markRoomAsClean']);
Route::get('/cleaner/rooms/all', [CleanerController::class, 'getAllRooms']);

// Për recepsionistët
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/admin/reservations', [ReservationController::class, 'indexAdmin']);
    Route::post('/admin/reservations', [ReservationController::class, 'storeAdmin']);
    Route::put('/admin/reservations/{reservation}', [ReservationController::class, 'updateAdmin']);
    Route::delete('/admin/reservations/{reservation}', [ReservationController::class, 'destroyAdmin']);
    Route::put('/admin/rooms/{room}/status', [ReservationController::class, 'updateRoomStatus']);
});