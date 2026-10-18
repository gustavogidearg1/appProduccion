<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\PlanProduccionController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});



Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('/productos', [ProductoController::class, 'index'])->name('productos.index');
    Route::resource('clientes', ClienteController::class)
        ->except(['show']);

    Route::get('/planes', [PlanProduccionController::class, 'index'])
        ->name('planes.index');

    Route::get('/planes/create', [PlanProduccionController::class, 'create'])
        ->name('planes.create');

    Route::post('/planes', [PlanProduccionController::class, 'store'])
        ->name('planes.store');

    Route::get('/planes/{plan}', [PlanProduccionController::class, 'show'])
        ->name('planes.show');
});

require __DIR__ . '/auth.php';
