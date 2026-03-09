<?php

use App\Http\Controllers\SaftVerifierController;
use Illuminate\Support\Facades\Route;

Route::get('/', [SaftVerifierController::class, 'index'])->name('saft.upload');
Route::post('/verify', [SaftVerifierController::class, 'verify'])->name('saft.verify');
