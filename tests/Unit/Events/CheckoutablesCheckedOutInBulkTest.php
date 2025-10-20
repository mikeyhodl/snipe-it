<?php

namespace Tests\Unit\Events;

use App\Events\CheckoutablesCheckedOutInBulk;
use App\Mail\BulkAssetCheckoutMail;
use App\Models\Asset;
use App\Models\User;
use App\Notifications\CheckoutAssetNotification;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

class CheckoutablesCheckedOutInBulkTest extends TestCase
{
    private $assets;
    private $target;
    private $admin;
    private $checkout_at;
    private $expected_checkin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->assets = Asset::factory()->count(2)->create();
        $this->target = User::factory()->create(['email' => 'someone@example.com']);
        $this->admin = User::factory()->create();
        $this->checkout_at = date('Y-m-d H:i:s');
        $this->expected_checkin = '';
    }

    public function test_action_log_entries()
    {
        $this->markTestIncomplete();

        $this->dispatchEvent();

        $this->assets->each(function ($asset) {
            $asset->assignedTo()->is($this->target);
            $asset->last_checkout = $this->checkout_at;
            $asset->expected_checkin = $this->expected_checkin;
            $this->assertHasTheseActionLogs($asset, ['create', 'checkout']); //Note: '$this' gets auto-bound in closures, so this does work.
        });
    }

    public function test_checkout_acceptance_creation()
    {
        $this->markTestIncomplete();
    }

    #[Group('notifications')]
    public function test_emails()
    {
        $this->markTestIncomplete();

        Mail::fake();

        $this->settings->enableAdminCC('cc@example.com');

        $this->dispatchEvent();

        // we shouldn't send the "single" checkout mailable
        Mail::assertNotSent(CheckoutAssetNotification::class);

        Mail::assertSent(BulkAssetCheckoutMail::class, 2);

        Mail::assertSent(BulkAssetCheckoutMail::class, function (BulkAssetCheckoutMail $mail) {
            // @todo: assert contents
            return $mail->hasTo('someone@example.com');
        });

        Mail::assertSent(BulkAssetCheckoutMail::class, function (BulkAssetCheckoutMail $mail) {
            // @todo: assert contents
            return $mail->hasTo('cc@example.com');
        });
    }

    #[Group('notifications')]
    public function test_webhooks()
    {
        $this->markTestIncomplete();

        Notification::fake();
    }

    private function dispatchEvent(): void
    {
        CheckoutablesCheckedOutInBulk::dispatch(
            $this->assets,
            $this->target,
            $this->admin,
            $this->checkout_at,
            $this->expected_checkin,
            'A note here',
        );
    }
}
