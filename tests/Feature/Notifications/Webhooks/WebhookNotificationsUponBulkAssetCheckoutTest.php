<?php

namespace Tests\Feature\Notifications\Webhooks;

use App\Events\CheckoutablesCheckedOutInBulk;
use App\Models\Asset;
use App\Models\User;
use App\Notifications\CheckoutAssetNotification;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class WebhookNotificationsUponBulkAssetCheckoutTest extends TestCase
{
    public function test_webbook_is_sent_upon_bulk_asset_checkout()
    {
        $this->markTestIncomplete();

        Notification::fake();

        $this->settings->enableSlackWebhook();

        $assets = Asset::factory()->requiresAcceptance()->count(2)->create();
        $target = User::factory()->create(['email' => 'someone@example.com']);
        $admin = User::factory()->create();
        $checkout_at = date('Y-m-d H:i:s');
        $expected_checkin = '';

        CheckoutablesCheckedOutInBulk::dispatch(
            $assets,
            $target,
            $admin,
            $checkout_at,
            $expected_checkin,
            'A note here',
        );

        Notification::assertNothingSentTo(CheckoutAssetNotification::class);
        Notification::assertSentTimes(BulkAssetCheckoutNotification::class, 1);

        $this->assertSlackNotificationSent(BulkAssetCheckoutNotification::class);
    }
}
