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

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();

        $this->settings->disableAdminCC();
        $this->settings->disableAdminCCAlways();

        $this->assets = Asset::factory()->requiresAcceptance()->count(2)->create();
        $this->target = User::factory()->create(['email' => 'someone@example.com']);
        $this->admin = User::factory()->create();
    }

    public function test_email_is_sent_to_user()
    {
        $this->dispatchEvent();

        Mail::assertNotSent(CheckoutAssetMail::class);

        Mail::assertSent(BulkAssetCheckoutMail::class, 1);

        Mail::assertSent(BulkAssetCheckoutMail::class, function (BulkAssetCheckoutMail $mail) {
            return $mail->hasTo($this->target->email);
        });
    }

    public function test_email_is_not_sent_to_user_when_user_does_not_have_email_address()
    {
        $this->target = User::factory()->create(['email' => null]);

        $this->dispatchEvent();

        Mail::assertNotSent(CheckoutAssetMail::class);
        Mail::assertNotSent(BulkAssetCheckoutMail::class);
    }

    public function test_email_is_not_sent_to_user_if_assets_do_not_require_acceptance()
    {
        $this->assets = Asset::factory()->count(2)->create();

        $this->dispatchEvent();

        Mail::assertNotSent(CheckoutAssetMail::class);
        Mail::assertNotSent(BulkAssetCheckoutMail::class);
    }

    public function test_email_is_sent_to_cc_address()
    {
        $this->settings->enableAdminCC('cc@example.com');

        $this->dispatchEvent();

        Mail::assertNotSent(CheckoutAssetMail::class);

        Mail::assertSent(BulkAssetCheckoutMail::class, 2);

        Mail::assertSent(BulkAssetCheckoutMail::class, function (BulkAssetCheckoutMail $mail) {
            return $mail->hasTo($this->target->email);
        });

        Mail::assertSent(BulkAssetCheckoutMail::class, function (BulkAssetCheckoutMail $mail) {
            return $mail->hasTo('cc@example.com');
        });
    }

    public function test_email_is_not_sent_to_cc_address_when_assets_do_not_require_acceptance()
    {
        $this->settings->enableAdminCC('cc@example.com');
        $this->settings->disableAdminCCAlways();

        $this->assets = Asset::factory()->count(2)->create();

        $this->dispatchEvent();

        Mail::assertNotSent(CheckoutAssetMail::class);
        Mail::assertNotSent(BulkAssetCheckoutMail::class);
    }

    public function test_email_is_sent_to_cc_address_when_assets_do_not_require_acceptance_but_admin_cc_always_enabled()
    {
        $this->settings->enableAdminCC('cc@example.com');
        $this->settings->enableAdminCCAlways();

        $this->assets = Asset::factory()->count(2)->create();

        $this->dispatchEvent();

        Mail::assertNotSent(CheckoutAssetMail::class);

        Mail::assertSent(BulkAssetCheckoutMail::class, 1);

        Mail::assertSent(BulkAssetCheckoutMail::class, function (BulkAssetCheckoutMail $mail) {
            return $mail->hasTo('cc@example.com');
        });
    }

    private function dispatchEvent(): void
    {
        CheckoutablesCheckedOutInBulk::dispatch(
            $this->assets,
            $this->target,
            $this->admin,
            date('Y-m-d H:i:s'),
            '',
            'A note here',
        );
    }
}
