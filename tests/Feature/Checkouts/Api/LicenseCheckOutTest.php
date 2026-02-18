<?php
namespace Tests\Feature\Checkouts\Api;

use App\Models\License;
use App\Models\LicenseSeat;
use App\Models\User;
use Tests\TestCase;

class LicenseCheckOutTest extends TestCase {
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

    public function test_assigned_to_cannot_be_array()
    {
        $licenseSeat = LicenseSeat::factory()->create([
            'assigned_to' => null,
        ]);

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
            ->assertStatusMessageIs('error');
    }
}
