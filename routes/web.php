<?php

use App\Http\Controllers\ClientController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

// ─── Public Onboarding ────────────────────────────────────────────────────────
Route::redirect('/onboarding', '/');
Route::get('/onboarding', [OnboardingController::class, 'show'])->name('onboarding.show');
Route::post('/onboarding', [OnboardingController::class, 'store'])->name('onboarding.store');
Route::get('/onboarding/thanks', [OnboardingController::class, 'thanks'])->name('onboarding.thanks');

// ─── Admin: Clients (protected by auth middleware) ────────────────────────────
Route::middleware(['auth'])->prefix('admin')->name('clients.')->group(function () {
    Route::get('/clients', [ClientController::class, 'index'])->name('index');
    Route::get('/clients/{client}', [ClientController::class, 'show'])->name('show');
    Route::get('/clients/{client}/edit', [ClientController::class, 'edit'])->name('edit');
    Route::put('/clients/{client}', [ClientController::class, 'update'])->name('update');
    Route::delete('/clients/{client}', [ClientController::class, 'destroy'])->name('destroy');
    Route::patch('/clients/{client}/status', [ClientController::class, 'updateStatus'])->name('status');
});

