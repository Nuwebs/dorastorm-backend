<?php

namespace App\Listeners;

use App\Events\QuotationReceived;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendEmailQuotationNotification implements ShouldQueue
{
    /**
     * Handle the event.
     */
    public function handle(QuotationReceived $event): void
    {
        
    }
}
