<?php

use App\Http\Controllers\Api\AuthApiController;
use App\Http\Controllers\Api\NotificationChannelApiController;
use App\Http\Controllers\Api\PublicStatusController;
use App\Http\Controllers\Api\ServiceApiController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
| Rutas centralizadas para la API RESTful de UpTracker.
| Provee operaciones CRUD/ABM sobre servicios/URLs, canales de notificación,
| consulta de históricos (latencias e incidentes), página de estado pública
| y autenticación vía tokens Bearer con limitación de tasa (Rate Limiting).
*/

// Endpoint público de página de estado (Rate limit: 30 req/min)
Route::get('/status/public', PublicStatusController::class)
    ->middleware('throttle:api.public')
    ->name('api.status.public');

// Rutas públicas de autenticación API protegidas contra fuerza bruta (Rate limit: 10 req/min)
Route::middleware('throttle:api.auth')->group(function () {
    Route::post('/register', [AuthApiController::class, 'register'])->name('api.register');
    Route::post('/login', [AuthApiController::class, 'login'])->name('api.login');
});

// Rutas protegidas mediante tokens de Sanctum (Rate limit: 60 req/min por usuario)
Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {
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
