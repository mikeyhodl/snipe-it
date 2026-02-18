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

    public function test_assigned_to_cannot_be_array()
    {
        $licenseSeat = LicenseSeat::factory()->create(['assigned_to' => null]);

        $targets = User::factory()->count(2)->create();

        $this->actingAsForApi(User::factory()->superuser()->create())
            ->patchJson(
                route('api.licenses.seats.update', [$licenseSeat->license->id, $licenseSeat->id]),
                [
                    'assigned_to' => [
                        $targets[0]->id,
                        $targets[1]->id,
                    ],
                    'notes' => '',
                ]
            )
            ->assertStatus(200)
            ->assertStatusMessageIs('error')
            ->assertMessagesContains('assigned_to');
    }

    public function test_assigned_to_must_be_valid_user()
    {
        $licenseSeat = LicenseSeat::factory()->create(['assigned_to' => null]);

        $softDeletedUser = User::factory()->trashed()->create();

        $this->actingAsForApi(User::factory()->superuser()->create())
            ->patchJson(
                route('api.licenses.seats.update', [$licenseSeat->license->id, $licenseSeat->id]),
                [
                    'assigned_to' => $softDeletedUser->id,
                    'notes' => '',
                ]
            )
            ->assertStatus(200)
            ->assertStatusMessageIs('error')
            ->assertMessagesContains('assigned_to');
    }

    public function test_asset_id_must_be_a_valid_asset()
    {
        $licenseSeat = LicenseSeat::factory()->create(['assigned_to' => null]);

        $softDeletedAsset = Asset::factory()->trashed()->create();

        $this->actingAsForApi(User::factory()->superuser()->create())
            ->patchJson(
                route('api.licenses.seats.update', [$licenseSeat->license->id, $licenseSeat->id]),
                [
                    'asset_id' => $softDeletedAsset->id,
                    'notes' => '',
                ]
            )
            ->assertStatus(200)
            ->assertStatusMessageIs('error')
            ->assertMessagesContains('asset_id');
    }

    public function test_assigned_to_and_asset_id_cannot_be_provided_together()
    {
        $licenseSeat = LicenseSeat::factory()->create(['assigned_to' => null]);

        $this->actingAsForApi(User::factory()->superuser()->create())
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

        $this->actingAsForApi(User::factory()->superuser()->create())
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

        $this->actingAsForApi(User::factory()->superuser()->create())
            ->patchJson($this->route($licenseSeat), [
                'notes' => '',
                'license_id' => License::factory()->create()->id,
            ])
            ->assertStatus(200)
            ->assertStatusMessageIs('success');

        $licenseSeat->refresh();
        $this->assertEquals($licenseId, $licenseSeat->license_id);
    }

    public function test_created_by_and_timestamps_are_not_updated()
    {
        $licenseSeat = LicenseSeat::factory()->create();

        $createdBy = $licenseSeat->created_by;
        $createdAt = $licenseSeat->created_at;
        $deleteAt = $licenseSeat->deleted_at;

        $this->actingAsForApi(User::factory()->superuser()->create())
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

    public function test_reassignableness_cannot_be_updated()
    {
        $this->markTestIncomplete();
    }

    public function test_license_seat_can_be_checked_out_to_user_when_updating()
    {
        $this->markTestIncomplete();

        $license = License::factory()->create();
        $licenseSeat = LicenseSeat::factory()->for($license)->create([
            'assigned_to' => null,
        ]);

        $targetUser = User::factory()->create();

        $payload = [
            'assigned_to' => $targetUser->id,
            'notes' => 'Checking out the seat to a user',
        ];

        $response = $this->actingAsForApi(User::factory()->superuser()->create())
            ->patchJson(
                $this->route($licenseSeat),
                $payload
            );

        $response->assertStatus(200)
            ->assertJsonFragment([
                'status' => 'success',
            ]);

        $licenseSeat->refresh();

        $this->assertEquals($targetUser->id, $licenseSeat->assigned_to);
        $this->assertEquals('Checking out the seat to a user', $licenseSeat->notes);
        $this->assertHasTheseActionLogs($license, ['add seats', 'create', 'checkout']); //FIXME - backwards
    }

    public function test_license_seat_can_be_checked_out_to_asset_when_updating()
    {
        $this->markTestIncomplete();
    }

    public function test_license_seat_can_be_checked_in_when_updating()
    {
        $this->markTestIncomplete();

        $licenseSeat = LicenseSeat::factory()->reassignable()->assignedToUser()->create();

        $this->actingAsForApi(User::factory()->superuser()->create())
            ->patchJson(
                route('api.licenses.seats.update', [$licenseSeat->license->id, $licenseSeat->id]),
                [
                    'assigned_to' => null,
                    'notes' => '',
                ]
            )
            ->assertStatus(200)
            ->assertStatusMessageIs('error');

        $licenseSeat->refresh();

        $this->assertNull($licenseSeat->assigned_to);
    }

    public function test_cannot_change_license_for_license_seat()
    {
        $this->markTestIncomplete();
    }

    public function test_cannot_reassign_unreassignable_license_seat()
    {
        $this->markTestIncomplete();
    }

    private function route(LicenseSeat $licenseSeat)
    {
        return route('api.licenses.seats.update', [$licenseSeat->license->id, $licenseSeat->id]);
    }
}
