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
    ScheduleController, // Ky është për oraret e recepsionistëve
    CleanerScheduleController // Ky është për oraret e pastruesve
};

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/sanctum/csrf-cookie', [\Laravel\Sanctum\Http\Controllers\CsrfCookieController::class, 'show']);
Route::get('/test', fn() => response()->json(['status' => 'Backend connected!']));
Route::post('/search-rooms', [RoomController::class, 'search']);
Route::get('/rooms', [ReservationController::class, 'indexRooms']);
Route::post('/book-room', [ReservationController::class, 'bookRoom']);
Route::post('/checkout', [CheckoutController::class, 'processCheckout']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::prefix('reservations')->group(function () {
        Route::get('/user', [ReservationController::class, 'indexApi']);
        Route::post('/checkout', [ReservationController::class, 'checkout']);
        Route::put('/{reservation}', [ReservationController::class, 'update']);
        Route::delete('/{reservation}', [ReservationController::class, 'destroy']);
    });

    Route::prefix('cleaner')->group(function () {
        Route::get('/dashboard', [CleanerController::class, 'dashboard']);
        Route::get('/rooms', [CleanerController::class, 'getDirtyRooms']);
        Route::put('/rooms/{room}/clean', [CleanerController::class, 'markRoomAsClean']);
        Route::get('/rooms/all', [CleanerController::class, 'getAllRooms']);
        Route::get('/schedules/my', [CleanerScheduleController::class, 'getMySchedules']); // Pastruesi shikon oraret e veta
        Route::put('/schedules/{schedule}/status', [CleanerScheduleController::class, 'updateStatus']); // Pastruesi azhurnon statusin e orarit të vet
    });

    Route::prefix('receptionist')->group(function () {
        Route::get('/reservations', [ReservationController::class, 'indexAdmin']);
        Route::post('/reservations', [ReservationController::class, 'storeAdmin']);
        Route::put('/reservations/{reservation}', [ReservationController::class, 'updateAdmin']);
        Route::delete('/reservations/{reservation}', [ReservationController::class, 'destroyAdmin']);
        Route::put('/rooms/{room}/status', [ReservationController::class, 'updateRoomStatus']);
        Route::get('/schedules/my', [ScheduleController::class, 'getMySchedules']); // Recepsionisti shikon oraret e veta
        Route::get('/schedules/all', [ScheduleController::class, 'getAllSchedules']); // Recepsionisti shikon oraret e veta (ose të gjitha nëse është admin)
        Route::put('/schedules/{schedule}/status', [ScheduleController::class, 'updateStatus']); // Recepsionisti azhurnon statusin e orarit të vet
    });

    Route::prefix('admin')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'dashboard']);
        Route::get('/users', [UserController::class, 'index']);
        Route::post('/users', [UserController::class, 'store']);
        Route::get('/users/{id}', [UserController::class, 'show']);
        Route::put('/users/{id}', [UserController::class, 'update']);
        Route::delete('/users/{id}', [UserController::class, 'destroy']);

        // Oraret e Recepsionistëve (menaxhohen nga Admin)
        Route::post('/receptionist/schedules', [ScheduleController::class, 'store']);
        Route::put('/receptionist/schedules/{schedule}', [ScheduleController::class, 'update']); // Sigurohu që metoda update në ScheduleController ekziston

        // Oraret e Pastruesve (menaxhohen nga Admin)
        Route::post('/cleaner/schedules', [CleanerScheduleController::class, 'store']);
        Route::put('/cleaner/schedules/{cleanerSchedule}', [CleanerScheduleController::class, 'update']); // Kjo duhet të jetë ajo që e thërret frontendi
    });

    
});