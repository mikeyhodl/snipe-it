<?php

namespace Tests\Feature\LicenseSeats\Api;

use App\Models\Asset;
use App\Models\License;
use App\Models\LicenseSeat;
use App\Models\User;
use Tests\TestCase;

class LicenseSeatUpdateTest extends TestCase
{
    public function test_requires_permission()
    {
        $licenseSeat = LicenseSeat::factory()->create();

        $this->actingAsForApi(User::factory()->create())
            ->patchJson($this->route($licenseSeat), [])
            ->assertForbidden();
    }

    /**
     * @link [rb-20713]
     */
    public function test_assigned_to_cannot_be_array()
    {
        $licenseSeat = LicenseSeat::factory()->create(['assigned_to' => null]);

        $targets = User::factory()->count(2)->create();

        $this->actingAsForApi(User::factory()->checkoutLicenses()->create())
            ->patchJson($this->route($licenseSeat), [
                'assigned_to' => [
                    $targets[0]->id,
                    $targets[1]->id,
                ],
                'notes' => '',
            ])
            ->assertStatus(200)
            ->assertStatusMessageIs('error')
            ->assertMessagesContains('assigned_to');
    }

    public function test_assigned_to_must_be_valid_user()
    {
        $licenseSeat = LicenseSeat::factory()->create(['assigned_to' => null]);

        $softDeletedUser = User::factory()->trashed()->create();

        $this->actingAsForApi(User::factory()->checkoutLicenses()->create())
            ->patchJson($this->route($licenseSeat), [
                'assigned_to' => $softDeletedUser->id,
                'notes' => '',
            ])
            ->assertStatus(200)
            ->assertStatusMessageIs('error')
            ->assertMessagesContains('assigned_to');
    }

    public function test_asset_id_must_be_a_valid_asset()
    {
        $licenseSeat = LicenseSeat::factory()->create(['assigned_to' => null]);

        $softDeletedAsset = Asset::factory()->trashed()->create();

        $this->actingAsForApi(User::factory()->checkoutLicenses()->create())
            ->patchJson($this->route($licenseSeat), [
                'asset_id' => $softDeletedAsset->id,
                'notes' => '',
            ])
            ->assertStatus(200)
            ->assertStatusMessageIs('error')
            ->assertMessagesContains('asset_id');
    }

    public function test_assigned_to_and_asset_id_cannot_be_provided_together()
    {
        $licenseSeat = LicenseSeat::factory()->create(['assigned_to' => null]);

        $this->actingAsForApi(User::factory()->checkoutLicenses()->create())
            ->patchJson($this->route($licenseSeat), [
                'assigned_to' => User::factory()->create()->id,
                'asset_id' => Asset::factory()->create()->id,
                'notes' => '',
            ])
            ->assertStatus(200)
            ->assertStatusMessageIs('error')
            ->assertMessagesContains('assigned_to')
            ->assertMessagesContains('asset_id');
    }

    public function test_license_seat_can_be_updated()
    {
        $licenseSeat = LicenseSeat::factory()->create();

        $this->actingAsForApi(User::factory()->checkoutLicenses()->create())
            ->patchJson($this->route($licenseSeat), [
                'notes' => 'A new note is here',
            ])
            ->assertStatus(200)
            ->assertStatusMessageIs('success');

        $licenseSeat->refresh();

        $this->assertEquals('A new note is here', $licenseSeat->notes);
    }

    public function test_license_cannot_be_updated()
    {
        $licenseSeat = LicenseSeat::factory()->create();
        $licenseId = $licenseSeat->license_id;

        $this->actingAsForApi(User::factory()->checkoutLicenses()->create())
            ->patchJson($this->route($licenseSeat), [
                'notes' => '',
                'license_id' => License::factory()->create()->id,
            ])
            ->assertStatus(200)
            ->assertStatusMessageIs('success');

        $licenseSeat->refresh();
        $this->assertEquals($licenseId, $licenseSeat->license_id);
    }

    public function test_reassignableness_is_not_updated()
    {
        $licenseSeat = LicenseSeat::factory()->reassignable()->create(['unreassignable_seat' => false]);

        $this->actingAsForApi(User::factory()->checkoutLicenses()->create())
            ->patchJson($this->route($licenseSeat), [
                'notes' => '',
                'unreassignable_seat' => true,
            ])
            ->assertStatus(200)
            ->assertStatusMessageIs('success');

        $licenseSeat->refresh();
        $this->assertFalse($licenseSeat->unreassignable_seat);
    }

    public function test_created_by_and_timestamps_are_not_updated()
    {
        $licenseSeat = LicenseSeat::factory()->create();

        $createdBy = $licenseSeat->created_by;
        $createdAt = $licenseSeat->created_at;
        $deleteAt = $licenseSeat->deleted_at;

        $this->actingAsForApi(User::factory()->checkoutLicenses()->create())
            ->patchJson($this->route($licenseSeat), [
                'notes' => '',
                'created_by' => User::factory()->create()->id,
                'created_at' => now()->subDays(5)->toDateTimeString(),
                'deleted_at' => now()->toDateTimeString(),
            ])
            ->assertStatus(200)
            ->assertStatusMessageIs('success');

        $licenseSeat->refresh();

        $this->assertEquals($createdBy, $licenseSeat->created_by);
        $this->assertEquals($createdAt, $licenseSeat->created_at);
        $this->assertEquals($deleteAt, $licenseSeat->deleted_at);
    }

    public function test_cannot_reassign_unreassignable_license_seat()
    {
        $licenseSeat = LicenseSeat::factory()->assignedToUser()->create(['unreassignable_seat' => true]);

        $this->actingAsForApi(User::factory()->checkoutLicenses()->create())
            ->patchJson($this->route($licenseSeat), [
                'asset_id' => Asset::factory()->create()->id,
                'notes' => 'Attempting to reassign an unreassignable seat',
            ])
            ->assertStatus(200)
            ->assertStatusMessageIs('error');

        $licenseSeat->refresh();
        $this->assertNull($licenseSeat->asset_id);
    }

    public function test_license_seat_can_be_checked_out_to_user_when_updating()
    {
        $licenseSeat = LicenseSeat::factory()->create(['assigned_to' => null]);
        $targetUser = User::factory()->create();

        $this->actingAsForApi(User::factory()->checkoutLicenses()->create())
            ->patchJson($this->route($licenseSeat), [
                'assigned_to' => $targetUser->id,
                'notes' => 'Checking out the seat to a user',
            ])
            ->assertStatus(200)
            ->assertStatusMessageIs('success');

        $licenseSeat->refresh();

        $this->assertEquals($targetUser->id, $licenseSeat->assigned_to);
        $this->assertEquals('Checking out the seat to a user', $licenseSeat->notes);
        $this->assertHasTheseActionLogs($licenseSeat->license, ['add seats', 'create', 'checkout']); //FIXME - backwards
    }

    public function test_license_seat_can_be_checked_out_to_asset_when_updating()
    {
        $licenseSeat = LicenseSeat::factory()->create(['assigned_to' => null]);
        $targetAsset = Asset::factory()->create();

        $this->actingAsForApi(User::factory()->checkoutLicenses()->create())
            ->patchJson($this->route($licenseSeat), [
                'asset_id' => $targetAsset->id,
                'notes' => 'Checking out the seat to an asset',
            ])
            ->assertStatus(200)
            ->assertStatusMessageIs('success');

        $licenseSeat->refresh();
        $this->assertEquals($targetAsset->id, $licenseSeat->asset_id);
        $this->assertEquals('Checking out the seat to an asset', $licenseSeat->notes);
        $this->assertHasTheseActionLogs($licenseSeat->license, ['add seats', 'create', 'checkout']); //FIXME - backwards
    }

    public function test_license_seat_checked_out_to_asset_can_be_checked_in_when_updating()
    {
        $licenseSeat = LicenseSeat::factory()->unreassignable()->assignedToAsset()->create([
            // this will be updated to true upon checkin...
            'unreassignable_seat' => false,
        ]);

        $this->actingAsForApi(User::factory()->checkoutLicenses()->create())
            ->patchJson($this->route($licenseSeat), [
                'asset_id' => null,
                'notes' => 'Checking in the seat',
            ])
            ->assertStatus(200)
            ->assertStatusMessageIs('success');

        $licenseSeat->refresh();

        $this->assertNull($licenseSeat->asset_id);
        $this->assertTrue($licenseSeat->unreassignable_seat);
    }

    public function test_license_seat_checked_out_to_user_can_be_checked_in_when_updating()
    {
        $licenseSeat = LicenseSeat::factory()->unreassignable()->assignedToUser()->create([
            // this will be updated to true upon checkin...
            'unreassignable_seat' => false,
        ]);

        $this->actingAsForApi(User::factory()->checkoutLicenses()->create())
            ->patchJson($this->route($licenseSeat), [
                'assigned_to' => null,
                'notes' => 'Checking in the seat',
            ])
            ->assertStatus(200)
            ->assertStatusMessageIs('success');

        $licenseSeat->refresh();

        $this->assertNull($licenseSeat->assigned_to);
        $this->assertTrue($licenseSeat->unreassignable_seat);
    }

    public function test_license_seat_checked_out_to_purged_asset_can_be_checked_in_when_updating()
    {
        $this->markTestIncomplete();

        $licenseSeat = LicenseSeat::factory()->assignedToAsset()->create(['asset_id' => 100000]);

        $this->actingAsForApi(User::factory()->checkoutLicenses()->create())
            ->patchJson($this->route($licenseSeat), [
                'asset_id' => null,
                'notes' => 'Checking in the seat',
            ])
            ->assertStatus(200)
            ->assertStatusMessageIs('success');

        $licenseSeat->refresh();

        $this->assertNull($licenseSeat->asset_id);
    }

    public function test_license_seat_checked_out_to_purged_user_can_be_checked_in_when_updating()
    {
        $this->markTestIncomplete();

        $licenseSeat = LicenseSeat::factory()->unreassignable()->assignedToUser()->create(['assigned_to' => 100000]);

        $this->actingAsForApi(User::factory()->checkoutLicenses()->create())
            ->patchJson($this->route($licenseSeat), [
                'assigned_to' => null,
                'notes' => 'Checking in the seat',
            ])
            ->assertStatus(200)
            ->assertStatusMessageIs('success');

        $licenseSeat->refresh();

        $this->assertNull($licenseSeat->assigned_to);
    }

    public function test_license_seat_checked_out_to_soft_deleted_asset_can_be_checked_in_when_updating()
    {
        $licenseSeat = LicenseSeat::factory()->assignedToAsset()->create();
        $licenseSeat->asset->delete();

        $this->actingAsForApi(User::factory()->checkoutLicenses()->create())
            ->patchJson($this->route($licenseSeat), [
                'asset_id' => null,
                'notes' => 'Checking in the seat',
            ])
            ->assertStatus(200)
            ->assertStatusMessageIs('success');

        $licenseSeat->refresh();

        $this->assertNull($licenseSeat->asset_id);
    }

    public function test_license_seat_checked_out_to_soft_deleted_user_can_be_checked_in_when_updating()
    {
        $licenseSeat = LicenseSeat::factory()->unreassignable()->assignedToUser()->create();
        $licenseSeat->user->delete();

        $this->actingAsForApi(User::factory()->checkoutLicenses()->create())
            ->patchJson($this->route($licenseSeat), [
                'assigned_to' => null,
                'notes' => 'Checking in the seat',
            ])
            ->assertStatus(200)
            ->assertStatusMessageIs('success');

        $licenseSeat->refresh();

        $this->assertNull($licenseSeat->assigned_to);
    }

    private function route(LicenseSeat $licenseSeat)
    {
        return route('api.licenses.seats.update', [$licenseSeat->license->id, $licenseSeat->id]);
    }
}
