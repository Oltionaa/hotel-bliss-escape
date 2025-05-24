<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{
    AuthController,
    UserController,
    RoomController,
    ReservationController,
    CleanerController,
    DashboardController,
    CheckoutController,
    ScheduleController, 
    CleanerScheduleController
};

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/sanctum/csrf-cookie', [\Laravel\Sanctum\Http\Controllers\CsrfCookieController::class, 'show']);
Route::get('/test', fn() => response()->json(['status' => 'Backend connected!']));
Route::post('/search-rooms', [RoomController::class, 'search']);
Route::get('/rooms', [ReservationController::class, 'indexRooms']); // Për të parë dhomat në dispozicion
Route::post('/book-room', [ReservationController::class, 'bookRoom']);
Route::post('/checkout', [CheckoutController::class, 'processCheckout']);


// Rrugët e mbrojtura (kërkojnë autentifikim me Sanctum)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    // Rezervimet (për klientët e autentifikuar)
    Route::prefix('reservations')->group(function () {
        Route::get('/user', [ReservationController::class, 'indexApi']); // Rezervimet e përdoruesit të kyçur
        Route::post('/checkout', [ReservationController::class, 'checkout']); // Përfundo procesin e pagesës
        Route::put('/{reservation}', [ReservationController::class, 'update']);
        Route::delete('/{reservation}', [ReservationController::class, 'destroy']);
    });

    Route::prefix('cleaner')->group(function () {
        Route::get('/dashboard', [CleanerController::class, 'dashboard']);
        Route::get('/rooms', [CleanerController::class, 'getDirtyRooms']); // Merr dhomat që duhen pastruar
        Route::put('/rooms/{room}/clean', [CleanerController::class, 'markRoomAsClean']); // Shëno dhomën si të pastruar
        Route::get('/rooms/all', [CleanerController::class, 'getAllRooms']); // Të gjitha dhomat
       
         Route::get('/schedules/my', [CleanerScheduleController::class, 'getMySchedules']); // Oraret e pastruesit të kyçur
        // Kjo rrugë poshtë mungonte ose ishte jashtë bllokut të duhur
        Route::put('/schedules/{schedule}/status', [CleanerScheduleController::class, 'updateStatus']); // Ndrysho statusin e orarit të pastruesit

    });


    // 💼 Recepsionist (Receptionist)
    Route::prefix('receptionist')->group(function () {
        // Rrugët për menaxhimin e rezervimeve nga recepsionisti
        Route::get('/reservations', [ReservationController::class, 'indexAdmin']);
        Route::post('/reservations', [ReservationController::class, 'storeAdmin']);
        Route::put('/reservations/{reservation}', [ReservationController::class, 'updateAdmin']);
        Route::delete('/reservations/{reservation}', [ReservationController::class, 'destroyAdmin']);
        Route::put('/rooms/{room}/status', [ReservationController::class, 'updateRoomStatus']); // Ndrysho statusin e dhomës

        // Rrugët për oraret e recepsionistëve
        Route::get('/schedules/my', [ScheduleController::class, 'getMySchedules']); // Orari i recepsionistit të kyçur
        Route::get('/schedules/all', [ScheduleController::class, 'getAllSchedules']); // Të gjitha oraret e recepsionistëve
        Route::put('/schedules/{schedule}/status', [ScheduleController::class, 'updateStatus']); 
    });

    Route::prefix('admin')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'dashboard']);
        // Rrugët për menaxhimin e përdoruesve
        Route::get('/users', [UserController::class, 'index']);
        Route::post('/users', [UserController::class, 'store']);
        Route::get('/users/{id}', [UserController::class, 'show']);
        Route::put('/users/{id}', [UserController::class, 'update']);
        Route::delete('/users/{id}', [UserController::class, 'destroy']);
    });

});