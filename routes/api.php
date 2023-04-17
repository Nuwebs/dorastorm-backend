<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\QuotationController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return true;
});

Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::post('/quotations', [QuotationController::class, 'store'])->name('quotation.store');

Route::middleware('guest')->group(function () {
    // Guest only routes
    Route::post('/forgot-password', [AuthController::class, 'sendResetPasswordLink'])->name('password.reset');
});

Route::middleware('auth:api')->group(function () {
    // Protected routes
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])
        ->middleware('signed')->name('verification.verify');

    Route::get('/me', [UserController::class, 'showMe'])->name('me');
    Route::get('/users/rolesbelow', [UserController::class, 'rolesBelow'])->name('users.rolesBelow');
    Route::patch('/users/{user}/password', [UserController::class, 'updatePassword'])->name('users.updatePassword');
    Route::apiResource('/users', UserController::class);

    Route::apiResource('/roles', RoleController::class);

    Route::resource('/posts', PostController::class)->except('create');

    Route::apiResource('/quotations', QuotationController::class)->except(['update', 'store']);
});