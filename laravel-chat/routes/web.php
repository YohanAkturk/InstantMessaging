<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Middleware\AuthReplay;
use App\Http\Middleware\ChatReplay;
use App\Http\Middleware\XSS;

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

Route::get('/', function () {
    return view('welcome');
});

// Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

Route::get('/chat', [App\Http\Controllers\ChatsController::class, 'index']);

Route::middleware([AuthReplay::class])->group(function() {
    Auth::routes();
});

Route::middleware([ChatReplay::class, XSS::class])->group(function() {
    Route::get('/chat/messages/{id}', [App\Http\Controllers\ChatsController::class, 'fetchMessages']);
    Route::post('/chat/messages', [App\Http\Controllers\ChatsController::class, 'sendMessage']);
    Route::post('/chat/friends', [App\Http\Controllers\UserCtrl::class, 'deleteFriend']);
    Route::post('/chat/request', [App\Http\Controllers\RequestCtrl::class, 'sendRequest']);
    Route::post('/chat/request/refuse', [App\Http\Controllers\RequestCtrl::class, 'refuseRequest']);
    Route::post('/chat/request/accept', [App\Http\Controllers\RequestCtrl::class, 'acceptRequest']);
});