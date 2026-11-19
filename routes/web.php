<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\User\HomeController as UserHomeController;
use App\Http\Controllers\User\ConfigController;
use App\Http\Controllers\User\DocumentController;
use App\Http\Controllers\User\VectorController;
use App\Http\Controllers\User\ElaborateController;
use App\Http\Controllers\User\ResultController;
use App\Http\Controllers\Admin\HomeController as AdminHomeController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\StatsController;

/**
 * Root reindirizza al login (nessuna landing page pubblica)
 */
Route::get('/', function () {
    return redirect()->route('login');
});

/**
 * Autenticazione
 */
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

/**
 * Area Utente (richiede autenticazione)
 */
Route::middleware(['auth', 'status'])->prefix('user')->name('user.')->group(function () {
    // Dashboard
    Route::get('/home', [UserHomeController::class, 'index'])->name('home');

    // Configurazione API Keys e Ollama
    Route::get('/config', [ConfigController::class, 'index'])->name('config');
    Route::post('/config/api-keys', [ConfigController::class, 'storeApiKey'])->name('config.api-keys.store');
    Route::delete('/config/api-keys/{apiKey}', [ConfigController::class, 'destroyApiKey'])->name('config.api-keys.destroy');
    Route::put('/config/ollama', [ConfigController::class, 'updateOllama'])->name('config.ollama.update');

    // Documenti
    Route::get('/documents', [DocumentController::class, 'index'])->name('documents');
    Route::post('/documents', [DocumentController::class, 'store'])->name('documents.store');
    Route::delete('/documents/{document}', [DocumentController::class, 'destroy'])->name('documents.destroy');
    Route::get('/documents/{document}/download', [DocumentController::class, 'download'])->name('documents.download');

    // Vettori
    Route::get('/vectors', [VectorController::class, 'index'])->name('vectors');
    Route::post('/vectors', [VectorController::class, 'store'])->name('vectors.store');
    Route::get('/vectors/{vector}/edit', [VectorController::class, 'edit'])->name('vectors.edit');
    Route::put('/vectors/{vector}', [VectorController::class, 'update'])->name('vectors.update');
    Route::delete('/vectors/{vector}', [VectorController::class, 'destroy'])->name('vectors.destroy');

    // Elaborazione
    Route::get('/elaborate', [ElaborateController::class, 'index'])->name('elaborate');
    Route::post('/elaborate/start', [ElaborateController::class, 'start'])->name('elaborate.start');
    Route::get('/elaborate/status/{result}', [ElaborateController::class, 'status'])->name('elaborate.status');
    Route::post('/elaborate/ollama-models', [ElaborateController::class, 'fetchOllamaModels'])->name('elaborate.ollama-models');
    Route::post('/elaborate/cloud-models', [ElaborateController::class, 'fetchCloudModels'])->name('elaborate.cloud-models');

    // Risultati
    Route::get('/results', [ResultController::class, 'index'])->name('results');
    Route::get('/results/{result}', [ResultController::class, 'show'])->name('results.show');
    Route::get('/results/{result}/download/{format}', [ResultController::class, 'download'])->name('results.download');
    Route::delete('/results/{result}', [ResultController::class, 'destroy'])->name('results.destroy');
});

/**
 * Area Admin (richiede autenticazione + ruolo admin)
 */
Route::middleware(['auth', 'role:admin', 'status'])->prefix('admin')->name('admin.')->group(function () {
    // Dashboard Admin
    Route::get('/users', [AdminUserController::class, 'index'])->name('users');
    Route::get('/users/{user}/edit', [AdminUserController::class, 'edit'])->name('users.edit');
    Route::put('/users/{user}', [AdminUserController::class, 'update'])->name('users.update');
    Route::delete('/users/{user}', [AdminUserController::class, 'destroy'])->name('users.destroy');
    Route::post('/users/{user}/toggle-role', [AdminUserController::class, 'toggleRole'])->name('users.toggle-role');

    // Statistiche globali
    Route::get('/stats', [StatsController::class, 'index'])->name('stats');
});

/**
 * API per modelli Ollama (pubblica per chiamata AJAX da elaborazione)
 */
Route::post('/api/ollama/models', [ElaborateController::class, 'fetchOllamaModels'])->name('api.ollama.models');