<?php

namespace App\Listeners;

use App\Events\QuotationReceived;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;

class SendEmailQuotationNotification implements ShouldQueue
{
    /**
     * Handle the event.
     */
    public function handle(QuotationReceived $event): void
    {
        Mail::to($event->quotation->email)->send(new \App\Mail\QuotationReceived($event->quotation->name));
        Mail::to(config('mail.admin.address'))->send(new \App\Mail\QuotationNotification());
    }
}