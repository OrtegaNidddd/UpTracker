<?php

use App\Http\Controllers\Api\AuthApiController;
use App\Http\Controllers\Api\NotificationChannelApiController;
use App\Http\Controllers\Api\ServiceApiController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
| Rutas centralizadas para la API RESTful de UpTracker.
| Provee operaciones CRUD/ABM sobre servicios/URLs, canales de notificación,
| consulta de históricos (latencias e incidentes) y autenticación vía tokens Bearer.
*/

// Rutas públicas de autenticación API
Route::post('/register', [AuthApiController::class, 'register'])->name('api.register');
Route::post('/login', [AuthApiController::class, 'login'])->name('api.login');

// Rutas protegidas mediante tokens de Sanctum
Route::middleware('auth:sanctum')->group(function () {
    // Perfil y sesión
    Route::get('/user', [AuthApiController::class, 'me'])->name('api.user');
    Route::patch('/user', [AuthApiController::class, 'updateProfile'])->name('api.user.update');
    Route::post('/logout', [AuthApiController::class, 'logout'])->name('api.logout');

    // CRUD / ABM de URLs y Servicios
    Route::apiResource('services', ServiceApiController::class)->names('api.services');
    Route::get('/services/{service}/logs', [ServiceApiController::class, 'logs'])->name('api.services.logs');
    Route::get('/services/{service}/incidents', [ServiceApiController::class, 'incidents'])->name('api.services.incidents');

    // CRUD de Canales de Notificación
    Route::apiResource('notification-channels', NotificationChannelApiController::class)->names('api.notification-channels');
});
