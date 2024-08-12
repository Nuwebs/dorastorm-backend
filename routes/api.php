<?php

use App\Http\Controllers\ApiKeyController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\QuotationController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use App\Http\Middleware\EnsureMaintenanceKey;
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

/**
 * Maintenance routes.
 * Do NOT change the prefix. If necessary, you will need to change the EnsureMaintenanceKey
 * middleware code and the PreventRequestsDuringMaintenance middleware $except array.
 */
if (DsFeature::enabled(DsFeature::MAINTENANCE_ROUTES)) {
    Route::prefix('mtc/{key}')->middleware(EnsureMaintenanceKey::class)->group(function () {
        Route::get('/cache-config', function () {
            Artisan::call('config:cache');
            Artisan::call('route:cache');
        });

        Route::get('/cache-clear', function () {
            Artisan::call('cache:clear');
        });

        Route::get('/migrate', function () {
            Artisan::call('migrate');
        });

        Route::get('/down', function () {
            Artisan::call('down');
            return 'The service has been shutdown correctly';
        });

        Route::get('/up', function () {
            Artisan::call('up');
            return 'The service is up again';
        });
    });
}

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

        if (DsFeature::enabled(DsFeature::ALLOW_SIGNUPS)) {
            Route::post('/signup', [UserController::class, 'signUp'])->name('user.signup');
        }
    });

    Route::middleware('auth:api')->group(function () {
        // Protected routes
        Route::post('/logout', [AuthController::class, 'logout'])->name('auth.logout');

        Route::get('/email/verification', [AuthController::class, 'resendEmailVerification'])->name('verification.resend');

        Route::get('/me', [UserController::class, 'showMe'])->name('me');
        Route::get('/me/sessions', [UserController::class, 'activeSessions'])->name('me.sessions');
        Route::post('/me/sessions/revoke/{token}', [UserController::class, 'revokeSession'])->name('me.sessions.revoke');

        Route::get('/users/rolesbelow', [UserController::class, 'rolesBelow'])->name('users.rolesBelow');
        Route::patch('/users/{user}/password', [UserController::class, 'updatePassword'])->name('users.updatePassword');
        Route::apiResource('/users', UserController::class);

        Route::apiResource('/roles', RoleController::class);

        if (DsFeature::enabled(DsFeature::ALLOW_API_KEYS)) {
            Route::post('/api-key', [ApiKeyController::class, 'store'])->name('api-key.store');
            Route::delete('/api-key/{token}', [ApiKeyController::class, 'revoke'])->name('api-key.revoke');
        }
    });
}
