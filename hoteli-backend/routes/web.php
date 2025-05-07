<?php

use App\Http\Controllers\ReservationController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->group(function () {
    // Shfaq listën e rezervimeve
    Route::get('/reservations', [ReservationController::class, 'indexApi'])->name('api.reservations.index');
    
    // Krijimi i rezervimit
    Route::post('/reservations/book', [ReservationController::class, 'bookRoom'])->name('api.reservations.book');
    
    // Përditësimi i rezervimit
    Route::put('/reservations/{reservation}', [ReservationController::class, 'updateApi'])->name('api.reservations.update');
    
    // Fshirja e rezervimit
    Route::delete('/reservations/{reservation}', [ReservationController::class, 'destroy'])->name('api.reservations.destroy');
});

?>