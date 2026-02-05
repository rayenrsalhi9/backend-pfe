<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UpdateController;

Route::get('/', [UpdateController::class, 'index']);
Route::post('run', [UpdateController::class, 'update']);
