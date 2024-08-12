<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        JsonResource::withoutWrapping();

        /**
         * Since DS3 is exclusively a decoupled backend,
         * in order to use the methods related to password reset
         * and email verification included in Laravel,
         * it is necessary to modify the links of the sent notifications.
         * For this, the URL of the frontend is taken and
         * the necessary data is sent to it as parameters.
         */
        $furl = config('app.frontend_url');
        ResetPassword::createUrlUsing(function (User $user, string $token) use ($furl) {
            $email = urlencode($user->email);
            // Note that the frontend must have a reset-password route.
            return "$furl/reset-password?token=$token&email=$email";
        });

        VerifyEmail::toMailUsing(function (object $notifiable, string $url) use ($furl) {
            $url = urlencode($url);
            // Note that the frontend must have a verify-email route.
            $customUrl = "$furl/verify-email?api=$url";
            return (new MailMessage)
                ->subject(__('emails.email_verification.subject'))
                ->line(__('emails.email_verification.body'))
                ->action(__('emails.email_verification.action'), $customUrl)
                ->line(__('emails.email_verification.footer'));
        });
    }
}
