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
Route::get('/rooms', [RoomController::class, 'index'])->name('rooms.index');


Route::post('/book-room', [ReservationController::class, 'bookRoom']);
Route::post('/reservations', [ReservationController::class, 'store']);
Route::post('/checkout', [CheckoutController::class, 'processCheckout']);
Route::post("/checkout", [RoomController::class, "checkout"]);
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/checkout', [ReservationController::class, 'checkout']);
    Route::get('/reservations/user', [ReservationController::class, 'indexApi']);
    Route::put('/reservations/{reservation}', [ReservationController::class, 'updateApi']);
    Route::delete('/reservations/{reservation}', [ReservationController::class, 'destroy']);
    Route::post('/book-room', [ReservationController::class, 'bookRoom']); // Mbajtur për konsistencë
});

Route::get('cleaner/rooms', [CleanerController::class, 'getDirtyRooms']);

// Route për të markuar dhomën si të pastruar
Route::put('/cleaner/rooms/{roomId}/clean', [CleanerController::class, 'markRoomAsClean']);

// Route për të marrë të gjitha dhomat
Route::get('/cleaner/rooms/all', [CleanerController::class, 'getAllRooms']);
