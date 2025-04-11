<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;

// Routes për përdoruesit dhe informacionet që mund të kërkohen pa u identifikuar
Route::post('register', [UserController::class, 'register']);