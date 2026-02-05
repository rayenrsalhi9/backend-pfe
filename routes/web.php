<?php

use App\Events\ConversationEvent;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AngularController;
use App\Http\Controllers\EmployeController;


/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// Route::get('/', function () {
//     return view('angular');
// });



Route::any('/{any}', [AngularController::class, 'index'])
    ->where('any', '^(?!api).*$')
    ->where('any', '^(?!install|update).*$');
    // ->where('any', '^(?!update).*$');
// Route::get('/category', [CategoryController::class, 'index']);
// Route::get('/category',function(){
// });
