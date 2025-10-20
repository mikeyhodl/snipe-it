<?php

namespace Tests\Feature\Notifications\Email;

use App\Events\CheckoutablesCheckedOutInBulk;
use App\Mail\BulkAssetCheckoutMail;
use App\Models\Asset;
use App\Models\User;
use App\Notifications\CheckoutAssetNotification;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class BulkCheckoutEmailTest extends TestCase
{
    public static function scenarios()
    {
        // 'User has email address set
        // 'User does not have address set'
        // 'CC email is set'
        // 'webhook is set'
    }

    public function test_email_is_sent()
    {
        $this->settings->enableAdminCC('cc@example.com');

        Mail::fake();

        $assets = Asset::factory()->count(2)->create();
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
}
