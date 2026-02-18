<?php
namespace Tests\Feature\Checkouts\Api;

use App\Models\License;
use App\Models\LicenseSeat;
use App\Models\User;
use Tests\TestCase;

class LicenseCheckOutTest extends TestCase {

    public function test_requires_permission()
    {
        $licenseSeat = LicenseSeat::factory()->create();

        $this->actingAsForApi(User::factory()->create())
            ->patchJson($this->route($licenseSeat), [])
            ->assertForbidden();
    }

    public function testLicenseCheckout()
    {
        $authUser = User::factory()->superuser()->create();
        $this->actingAsForApi($authUser);

        $license = License::factory()->create();
        $licenseSeat = LicenseSeat::factory()->for($license)->create([
            'assigned_to' => null,
        ]);

        $targetUser = User::factory()->create();

        $payload = [
            'assigned_to' => $targetUser->id,
            'notes' => 'Checking out the seat to a user',
        ];

        $response = $this->patchJson(
            route('api.licenses.seats.update', [$license->id, $licenseSeat->id]),
            $payload);

        $response->assertStatus(200)
            ->assertJsonFragment([
                'status' => 'success',
            ]);

        $licenseSeat->refresh();

        $this->assertEquals($targetUser->id, $licenseSeat->assigned_to);
        $this->assertEquals('Checking out the seat to a user', $licenseSeat->notes);
        $this->assertHasTheseActionLogs($license, ['add seats', 'create', 'checkout']); //FIXME - backwards
    }

    public function test_license_update_without_checkout()
    {
        $this->markTestIncomplete();
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

    public function test_null_assigned_to_checks_in_license_seat()
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

    public function test_cannot_reassign_unreassignable_license_seat()
    {
        $this->markTestIncomplete();
    }

    private function route(LicenseSeat $licenseSeat)
    {
        return route('api.licenses.seats.update', [$licenseSeat->license->id, $licenseSeat->id]);
    }
}
