<?php

namespace Tests\Feature\Notifications\Email;

use App\Mail\BulkAssetCheckoutMail;
use App\Mail\CheckoutAssetMail;
use App\Models\Asset;
use App\Models\Category;
use App\Models\Location;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Group;
use RuntimeException;
use Tests\TestCase;

#[Group('notifications')]
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
        $this->admin = User::factory()->checkoutAssets()->viewAssets()->create();
    }

    public function test_email_is_sent_to_user()
    {
        $this->sendRequest();

        Mail::assertNotSent(CheckoutAssetMail::class);

        Mail::assertSent(BulkAssetCheckoutMail::class, 1);

        Mail::assertSent(BulkAssetCheckoutMail::class, function (BulkAssetCheckoutMail $mail) {
            return $mail->hasTo($this->target->email)
                && $mail->assertSeeInText('Assets have been checked out to you');
        });
    }

    public function test_email_is_sent_to_location_manager()
    {
        // todo: migrate this into a data provider?

        $manager = User::factory()->create();

        $this->target = Location::factory()->for($manager, 'manager')->create();

        $this->sendRequest();

        Mail::assertNotSent(CheckoutAssetMail::class);

        Mail::assertSent(BulkAssetCheckoutMail::class, 1);

        Mail::assertSent(BulkAssetCheckoutMail::class, function (BulkAssetCheckoutMail $mail) use ($manager) {
            return $mail->hasTo($manager->email)
                && $mail->assertSeeInText('Assets have been checked out to ' . $this->target->name);
        });
    }

    public function test_email_is_sent_to_user_asset_is_checked_out_to()
    {
        // todo: migrate this into a data provider?

        $user = User::factory()->create();

        $this->target = Asset::factory()->assignedToUser($user)->create();

        $this->sendRequest();

        Mail::assertNotSent(CheckoutAssetMail::class);

        Mail::assertSent(BulkAssetCheckoutMail::class, 1);

        Mail::assertSent(BulkAssetCheckoutMail::class, function (BulkAssetCheckoutMail $mail) use ($user) {
            return $mail->hasTo($user->email);
        });
    }

    public function test_email_is_not_sent_to_user_when_user_does_not_have_email_address()
    {
        $this->target = User::factory()->create(['email' => null]);

        $this->sendRequest();

        Mail::assertNotSent(CheckoutAssetMail::class);
        Mail::assertNotSent(BulkAssetCheckoutMail::class);
    }

    public function test_email_is_not_sent_to_user_if_assets_do_not_require_acceptance()
    {
        $this->assets = Asset::factory()->doesNotRequireAcceptance()->count(2)->create();

        $this->sendRequest();

        Mail::assertNotSent(CheckoutAssetMail::class);
        Mail::assertNotSent(BulkAssetCheckoutMail::class);
    }

    public function test_email_is_sent_when_assets_do_not_require_acceptance_but_have_a_eula()
    {
        $this->assets = Asset::factory()->count(2)->create();

        $category = Category::factory()
            ->doesNotRequireAcceptance()
            ->doesNotSendCheckinEmail()
            ->hasLocalEula()
            ->create();

        $this->assets->each(fn($asset) => $asset->model->category()->associate($category)->save());

        $this->sendRequest();

        Mail::assertNotSent(CheckoutAssetMail::class);

        Mail::assertSent(BulkAssetCheckoutMail::class, 1);

        Mail::assertSent(BulkAssetCheckoutMail::class, function (BulkAssetCheckoutMail $mail) {
            return $mail->hasTo($this->target->email)
                && $mail->assertSeeInText('Assets have been checked out to you')
                && $mail->assertDontSeeInText('Click here to review the terms of use and accept');
        });
    }

    public function test_email_is_sent_when_assets_do_not_require_acceptance_or_have_a_eula_but_category_is_set_to_send_email()
    {
        $this->assets = Asset::factory()->count(2)->create();

        $category = Category::factory()
            ->doesNotRequireAcceptance()
            ->withNoLocalOrGlobalEula()
            ->sendsCheckinEmail()
            ->create();

        $this->assets->each(fn($asset) => $asset->model->category()->associate($category)->save());

        $this->sendRequest();

        Mail::assertNotSent(CheckoutAssetMail::class);

        Mail::assertSent(BulkAssetCheckoutMail::class, 1);

        Mail::assertSent(BulkAssetCheckoutMail::class, function (BulkAssetCheckoutMail $mail) {
            return $mail->hasTo($this->target->email)
                && $mail->assertSeeInText('Assets have been checked out to you')
                && $mail->assertDontSeeInText('review the terms');
        });
    }

    public function test_email_is_sent_to_cc_address()
    {
        $this->settings->enableAdminCC('cc@example.com');

        $this->sendRequest();

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

        $this->sendRequest();

        Mail::assertNotSent(CheckoutAssetMail::class);
        Mail::assertNotSent(BulkAssetCheckoutMail::class);
    }

    public function test_email_is_sent_to_cc_address_when_assets_do_not_require_acceptance_but_admin_cc_always_enabled()
    {
        $this->settings->enableAdminCC('cc@example.com');
        $this->settings->enableAdminCCAlways();

        $this->assets = Asset::factory()->count(2)->create();

        $this->sendRequest();

        Mail::assertNotSent(CheckoutAssetMail::class);

        Mail::assertSent(BulkAssetCheckoutMail::class, 1);

        Mail::assertSent(BulkAssetCheckoutMail::class, function (BulkAssetCheckoutMail $mail) {
            return $mail->hasTo('cc@example.com');
        });
    }

    private function sendRequest()
    {
        $this->actingAs($this->admin)
            ->followingRedirects()
            ->post(route('hardware.bulkcheckout.store'), array_merge([
                'selected_assets' => $this->assets->pluck('id')->toArray(),
                'checkout_at' => now()->subWeek()->format('Y-m-d'),
                'expected_checkin' => now()->addWeek()->format('Y-m-d'),
                'note' => null,
            ], $this->getAssignedArray()))
            ->assertOk();
    }

    private function getAssignedArray(): array
    {
        if ($this->target instanceof User) {
            return [
                'checkout_to_type' => 'user',
                'assigned_user' => $this->target->id,
            ];
        }

        if ($this->target instanceof Location) {
            return [
                'checkout_to_type' => 'location',
                'assigned_location' => $this->target->id,
            ];
        }

        if ($this->target instanceof Asset) {
            return [
                'checkout_to_type' => 'asset',
                'assigned_asset' => $this->target->id,
            ];
        }

        throw new RuntimeException('invalid target type');
    }
}
