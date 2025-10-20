<?php

namespace Tests\Unit\Listeners;

use App\Events\CheckoutablesCheckedOutInBulk;
use App\Listeners\CheckoutablesCheckedOutInBulkListener;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class CheckoutablesCheckedOutInBulkListenerTest extends TestCase
{
    public function test_listener_registered()
    {
        Event::fake();
        Event::assertListening(
            CheckoutablesCheckedOutInBulk::class,
            CheckoutablesCheckedOutInBulkListener::class,
        );
    }
}
