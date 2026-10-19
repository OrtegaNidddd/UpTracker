<?php

use App\Http\Controllers\Api\PublicStatusController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\NotificationChannelWebController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ServiceController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Página de Estado Pública (Frontend Web)
Route::get('/status', function () {
    return view('status-page');
})->name('status.page');

// Endpoint público para página de estado de clientes (Backend JSON)
Route::get('/status/public', PublicStatusController::class)->name('status.public');

// Generador de Sitemap XML para SEO y rastreadores
Route::get('/sitemap.xml', function () {
    $baseUrl = url('/');
    $lastMod = now()->toAtomString();

    $xml = '<?xml version="1.0" encoding="UTF-8"?>';
    $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
    $xml .= "<url><loc>{$baseUrl}/</loc><lastmod>{$lastMod}</lastmod><changefreq>daily</changefreq><priority>1.0</priority></url>";
    $xml .= "<url><loc>{$baseUrl}/status</loc><lastmod>{$lastMod}</lastmod><changefreq>hourly</changefreq><priority>0.9</priority></url>";
    $xml .= "<url><loc>{$baseUrl}/status/public</loc><lastmod>{$lastMod}</lastmod><changefreq>hourly</changefreq><priority>0.9</priority></url>";
    $xml .= "<url><loc>{$baseUrl}/login</loc><lastmod>{$lastMod}</lastmod><changefreq>monthly</changefreq><priority>0.5</priority></url>";
    $xml .= "<url><loc>{$baseUrl}/register</loc><lastmod>{$lastMod}</lastmod><changefreq>monthly</changefreq><priority>0.5</priority></url>";
    $xml .= '</urlset>';

    return response($xml, 200)->header('Content-Type', 'application/xml');
})->name('sitemap');

Route::middleware(['auth', 'verified'])->group(function () {
    // Perfil de usuario (Breeze)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Módulo UpTracker - Servicios
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/services', [ServiceController::class, 'index'])->name('services.index');
    Route::post('/services', [ServiceController::class, 'store'])->name('services.store');
    Route::get('/services/{service}', [ServiceController::class, 'show'])->name('services.show');
    Route::put('/services/{service}', [ServiceController::class, 'update'])->name('services.update');
    Route::delete('/services/{service}', [ServiceController::class, 'destroy'])->name('services.destroy');

    // Módulo de Canales de Alerta y Notificación (Correo, Discord, Telegram)
    Route::get('/notification-channels', [NotificationChannelWebController::class, 'index'])->name('notification-channels.index');
    Route::post('/notification-channels', [NotificationChannelWebController::class, 'store'])->name('notification-channels.store');
    Route::patch('/notification-channels/{notificationChannel}', [NotificationChannelWebController::class, 'update'])->name('notification-channels.update');
    Route::delete('/notification-channels/{notificationChannel}', [NotificationChannelWebController::class, 'destroy'])->name('notification-channels.destroy');
    Route::post('/notification-channels/{notificationChannel}/test', [NotificationChannelWebController::class, 'test'])->name('notification-channels.test');
});

require __DIR__.'/auth.php';