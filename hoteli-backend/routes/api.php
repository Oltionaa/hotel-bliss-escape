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
};

Route::middleware('auth:sanctum')->post('/logout', [AuthController::class, 'logout']);

// 🔓 Public Routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/sanctum/csrf-cookie', [\Laravel\Sanctum\Http\Controllers\CsrfCookieController::class, 'show']);
Route::get('/test', fn() => response()->json(['status' => 'Backend connected!']));
Route::post('/search-rooms', [RoomController::class, 'search']);
Route::get('/rooms', [ReservationController::class, 'indexRooms']);
Route::post('/book-room', [ReservationController::class, 'bookRoom']);
Route::post('/checkout', [CheckoutController::class, 'processCheckout']);

// ✅ Protected Routes
Route::middleware(['auth:sanctum'])->group(function () {
    // 📦 Reservations (për klientët)
    Route::get('/reservations/user', [ReservationController::class, 'indexApi']);
    Route::post('/reservations/checkout', [ReservationController::class, 'checkout']);
    Route::put('/reservations/{reservation}', [ReservationController::class, 'update']);
    Route::delete('/reservations/{reservation}', [ReservationController::class, 'destroy']);

    // 🧹 Cleaner
    Route::prefix('cleaner')->group(function () {
        Route::get('/dashboard', [CleanerController::class, 'dashboard']);
        Route::get('/rooms', [CleanerController::class, 'getDirtyRooms']);
        Route::put('/rooms/{room}/clean', [CleanerController::class, 'markRoomAsClean']);
        Route::get('/rooms/all', [CleanerController::class, 'getAllRooms']);
    });

    // 💼 Receptionist
    Route::prefix('receptionist')->group(function () {
        Route::get('/reservations', [ReservationController::class, 'indexAdmin']);
        Route::post('/reservations', [ReservationController::class, 'storeAdmin']);
        Route::put('/reservations/{reservation}', [ReservationController::class, 'updateAdmin']);
        Route::delete('/reservations/{reservation}', [ReservationController::class, 'destroyAdmin']);
        Route::put('/rooms/{room}/status', [ReservationController::class, 'updateRoomStatus']);
    });

    // 🛠 Admin
    Route::prefix('admin')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'dashboard']);
        Route::get('/users', [UserController::class, 'index']);
        Route::post('/users', [UserController::class, 'store']);
        Route::get('/users/{id}', [UserController::class, 'show']);
        Route::put('/users/{id}', [UserController::class, 'update']);
        Route::delete('/users/{id}', [UserController::class, 'destroy']);
    });
});