<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\QuotationController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use App\Utils\DsFeature;
use Illuminate\Support\Facades\Artisan;
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
    return [
        'app' => config('app.name'),
        'backend' => 'Dorastorm 3 by Nuwebs'
    ];
});

Route::get('/config-cache', function () {
    Artisan::call('config:cache');
    Artisan::call('route:cache');
});

if (DsFeature::enabled(DsFeature::AUTH)) {
    // Login and token refresh
    Route::post('/login', [AuthController::class, 'login'])->name('auth.login');
    Route::get('/token', [AuthController::class, 'refreshToken'])->name('auth.refresh');

    Route::get('/email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])
        ->middleware('signed')->name('verification.verify');

    Route::middleware('guest')->group(function () {
        // Guest only routes
        Route::post('/forgot-password', [AuthController::class, 'sendResetPasswordLink'])->name('password.reset');
        Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.update');
    });

    if (DsFeature::enabled(DsFeature::QUOTATIONS_MODULE)) {
        Route::post('/quotations', [QuotationController::class, 'store'])->name('quotation.store');
    }

    if (DsFeature::enabled(DsFeature::POSTS_MODULE)) {
        Route::get('/posts', [PostController::class, 'index'])->name('posts.index');
        Route::get('/posts/{post_slug}', [PostController::class, 'show'])->name('posts.show');
    }

    Route::middleware('auth:api')->group(function () {
        // Protected routes
        Route::post('/logout', [AuthController::class, 'logout'])->name('auth.logout');

        Route::get('/email/verification', [AuthController::class, 'resendEmailVerification'])->name('verification.resend');

        Route::get('/me', [UserController::class, 'showMe'])->name('me');
        Route::get('/users/rolesbelow', [UserController::class, 'rolesBelow'])->name('users.rolesBelow');
        Route::patch('/users/{user}/password', [UserController::class, 'updatePassword'])->name('users.updatePassword');
        Route::apiResource('/users', UserController::class);

        Route::apiResource('/roles', RoleController::class);

        if (DsFeature::enabled(DsFeature::QUOTATIONS_MODULE)) {
            Route::resource('/posts', PostController::class)->except('create', 'index', 'show');
        }

        if (DsFeature::enabled(DsFeature::POSTS_MODULE)) {
            Route::apiResource('/quotations', QuotationController::class)->except(['update', 'store']);
        }
    });
}
