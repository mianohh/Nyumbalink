<?php

namespace Tests\Feature;

use App\Enums\HouseStatus;
use App\Livewire\Houses\Create as HouseCreate;
use App\Livewire\Houses\Edit as HouseEdit;
use App\Livewire\Houses\Show as HouseShow;
use App\Models\House;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class HousesTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_can_create_a_house(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(HouseCreate::class)
            ->set('house_number', 'A-01')
            ->set('location', 'Kilimani')
            ->set('rent_amount', '15000')
            ->set('status', 'available')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('houses', [
            'house_number' => 'A-01',
            'location' => 'Kilimani',
            'status' => HouseStatus::Available,
        ]);
    }

    public function test_house_number_must_be_unique(): void
    {
        House::factory()->create(['house_number' => 'A-01']);
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(HouseCreate::class)
            ->set('house_number', 'A-01')
            ->set('location', 'Westlands')
            ->set('rent_amount', '10000')
            ->set('status', 'available')
            ->call('save')
            ->assertHasErrors(['house_number' => 'unique']);
    }

    public function test_staff_cannot_delete_a_house(): void
    {
        $house = House::factory()->create();
        $staff = User::factory()->create();

        Livewire::actingAs($staff)
            ->test(HouseEdit::class, ['house' => $house])
            ->call('delete')
            ->assertForbidden();

        $this->assertDatabaseHas('houses', ['id' => $house->id]);
    }

    public function test_admin_can_delete_a_house(): void
    {
        $house = House::factory()->create();
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test(HouseEdit::class, ['house' => $house])
            ->call('delete')
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('houses', ['id' => $house->id]);
    }

    public function test_assigning_a_tenant_marks_house_occupied_and_unassign_releases_it(): void
    {
        $house = House::factory()->create();
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(HouseShow::class, ['house' => $house])
            ->set('tenantToAssign', $tenant->id)
            ->call('assign')
            ->assertHasNoErrors();

        $this->assertSame($house->id, $tenant->fresh()->house_id);
        $this->assertSame(HouseStatus::Occupied, $house->fresh()->status);

        Livewire::actingAs($user)
            ->test(HouseShow::class, ['house' => $house])
            ->call('unassign')
            ->assertHasNoErrors();

        $this->assertNull($tenant->fresh()->house_id);
        $this->assertSame(HouseStatus::Available, $house->fresh()->status);
    }
}
