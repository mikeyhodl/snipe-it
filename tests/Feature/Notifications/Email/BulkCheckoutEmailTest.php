<?php

namespace Tests\Feature\Notifications\Email;

use App\Events\CheckoutablesCheckedOutInBulk;
use App\Mail\BulkAssetCheckoutMail;
use App\Mail\CheckoutAssetMail;
use App\Models\Asset;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class BulkCheckoutEmailTest extends TestCase
{
    private $assets;
    private $target;
    private $admin;
    private $checkout_at;
    private $expected_checkin;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();

        $this->assets = Asset::factory()->count(2)->create();
        $this->target = User::factory()->create(['email' => 'someone@example.com']);
        $this->admin = User::factory()->create();
        $this->checkout_at = date('Y-m-d H:i:s');
        $this->expected_checkin = '';
    }

    // @todo:
    public static function scenarios()
    {
        // 'User has email address set
        // 'User does not have address set'
        // 'CC email is set'
        // 'webhook is set'
    }

    public function test_email_is_sent_to_user()
    {
        $this->settings->disableAdminCC();

        $this->dispatchEvent();

        Mail::assertNotSent(CheckoutAssetMail::class);

        Mail::assertSent(BulkAssetCheckoutMail::class, 1);

        Mail::assertSent(BulkAssetCheckoutMail::class, function (BulkAssetCheckoutMail $mail) {
            // @todo: assert contents
            return $mail->hasTo($this->target->email);
        });
    }

    public function test_email_is_sent_to_cc_address()
    {
        $this->settings->enableAdminCC('cc@example.com');

        $this->dispatchEvent();

        Mail::assertNotSent(CheckoutAssetMail::class);

        Mail::assertSent(BulkAssetCheckoutMail::class, 2);

        Mail::assertSent(BulkAssetCheckoutMail::class, function (BulkAssetCheckoutMail $mail) {
            // @todo: assert contents
            return $mail->hasTo($this->target->email);
        });

        Mail::assertSent(BulkAssetCheckoutMail::class, function (BulkAssetCheckoutMail $mail) {
            // @todo: assert contents
            return $mail->hasTo('cc@example.com');
        });
    }

    public function test_webbook_is_sent()
    {
        $this->markTestIncomplete();
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
