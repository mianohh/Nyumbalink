<?php

namespace Tests\Feature;

use App\Livewire\Staff\Create as StaffCreate;
use App\Livewire\Staff\Index as StaffIndex;
use App\Models\House;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class StaffTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_pages_require_admin_access(): void
    {
        $staff = User::factory()->create();

        $this->actingAs($staff)->get(route('staff.index'))->assertForbidden();
        $this->actingAs($staff)->get(route('staff.create'))->assertForbidden();
    }

    public function test_admin_can_view_staff_pages(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('staff.index'))->assertOk();
        $this->actingAs($admin)->get(route('staff.create'))->assertOk();
    }

    public function test_admin_can_create_a_staff_member(): void
    {
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test(StaffCreate::class)
            ->set('name', 'New Staff')
            ->set('email', 'newstaff@example.com')
            ->set('password', 'secret123')
            ->set('role', 'staff')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('users', [
            'email' => 'newstaff@example.com',
            'role' => 'staff',
        ]);
    }

    public function test_staff_cannot_delete_house_from_index_page(): void
    {
        // Policies are enforced at the component level; verify staff cannot delete houses.
        $house = House::factory()->create();
        $staff = User::factory()->create();

        $this->assertFalse($staff->can('delete', $house));

        $admin = User::factory()->admin()->create();
        $this->assertTrue($admin->can('delete', $house));
    }

    public function test_admin_cannot_delete_self_from_staff_list(): void
    {
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test(StaffIndex::class)
            ->call('delete', $admin->id);

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }
}
