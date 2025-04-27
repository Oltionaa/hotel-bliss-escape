<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\RoomController;


Route::post('register', [UserController::class, 'register']);
Route::get('/test', function () {
    return response()->json(['status' => 'Backend connected!']);
});
Route::post('/login', [AuthController::class, 'login']);
Route::post('/search-rooms', [RoomController::class, 'search']);
Route::get('/rooms', [RoomController::class, 'index'])->name('rooms.index');