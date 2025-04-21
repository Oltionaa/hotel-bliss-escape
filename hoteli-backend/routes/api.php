<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AuthController;

Route::post('register', [UserController::class, 'register']);
Route::get('/test', function () {
    return response()->json(['status' => 'Backend connected!']);
});
Route::post('/login', [AuthController::class, 'login']);

Route::post('/available-rooms', [RoomController::class, 'availableRooms']);
