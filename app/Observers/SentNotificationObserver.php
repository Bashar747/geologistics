<?php

namespace App\Observers;

use App\Events\NotificationCreated;
use App\Models\SentNotification;

class SentNotificationObserver
{
    /**
     * Handle the SentNotification "created" event.
     */
    public function created(SentNotification $sentNotification): void
    {
        NotificationCreated::dispatch($sentNotification);
    }

    /**
     * Handle the SentNotification "updated" event.
     */
    public function updated(SentNotification $sentNotification): void
    {
        //
    }

    /**
     * Handle the SentNotification "deleted" event.
     */
    public function deleted(SentNotification $sentNotification): void
    {
        //
    }

    /**
     * Handle the SentNotification "restored" event.
     */
    public function restored(SentNotification $sentNotification): void
    {
        //
    }

    /**
     * Handle the SentNotification "force deleted" event.
     */
    public function forceDeleted(SentNotification $sentNotification): void
    {
        //
    }
}