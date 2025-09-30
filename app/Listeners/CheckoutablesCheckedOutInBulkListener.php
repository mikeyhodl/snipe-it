<?php

namespace App\Listeners;

use App\Events\CheckoutablesCheckedOutInBulk;

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
        //
    }
}
