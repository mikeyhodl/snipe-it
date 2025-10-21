<?php

namespace App\Listeners;

use App\Events\CheckoutablesCheckedOutInBulk;
use App\Mail\BulkAssetCheckoutMail;
use Illuminate\Support\Facades\Mail;

class CheckoutablesCheckedOutInBulkListener
{

    public function subscribe($events)
    {
        $events->listen(
            CheckoutablesCheckedOutInBulk::class,
            CheckoutablesCheckedOutInBulkListener::class
        );
    }

    public function handle(CheckoutablesCheckedOutInBulk $event): void
    {
        if ($event->target->email) {
            Mail::to($event->target)->send(new BulkAssetCheckoutMail(
                $event->assets,
                $event->target,
                $event->admin,
                $event->checkout_at,
                $event->expected_checkin,
                $event->note,
            ));
        }

        // @todo: check CheckoutableListener::onCheckedOut() for implementation

        // @todo: create and attach acceptance? Might be handled in CheckoutableListener::getCheckoutAcceptance() already.
    }
}
