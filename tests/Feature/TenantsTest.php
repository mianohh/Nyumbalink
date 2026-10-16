<?php

namespace Tests\Feature;

use App\Livewire\Payments\Create as PaymentCreate;
use App\Livewire\Tenants\Create as TenantCreate;
use App\Livewire\Tenants\Edit as TenantEdit;
use App\Models\House;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TenantsTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_can_create_a_tenant(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(TenantCreate::class)
            ->set('name', 'Jane Wanjiku')
            ->set('contact', '+254712345678')
            ->set('id_number', '12345678')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('tenants', ['id_number' => '12345678']);
    }

    public function test_creating_a_tenant_with_a_house_assigns_and_occupies_it(): void
    {
        $house = House::factory()->create();
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(TenantCreate::class)
            ->set('name', 'John Doe')
            ->set('contact', '+254700000000')
            ->set('id_number', '87654321')
            ->set('house_id', $house->id)
            ->call('save')
            ->assertHasNoErrors();

        $tenant = Tenant::where('id_number', '87654321')->firstOrFail();

        $this->assertSame($house->id, $tenant->house_id);
        $this->assertSame('occupied', $house->fresh()->status->value);
    }

    public function test_tenant_with_payments_cannot_be_deleted(): void
    {
        $tenant = Tenant::factory()->create();
        Payment::factory()->create(['tenant_id' => $tenant->id]);
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test(TenantEdit::class, ['tenant' => $tenant])
            ->call('delete');

        $this->assertDatabaseHas('tenants', ['id' => $tenant->id]);
    }

    public function test_payment_creation_generates_a_unique_receipt(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(PaymentCreate::class)
            ->set('tenant_id', $tenant->id)
            ->set('amount_paid', '5000')
            ->set('payment_date', now()->format('Y-m-d'))
            ->call('save')
            ->assertHasNoErrors();

        $payment = Payment::firstOrFail();

        $this->assertMatchesRegularExpression('/^RCP-\d{8}-[A-Z0-9]{3}$/', $payment->receipt_number);
        $this->assertSame(5000.0, (float) $payment->amount_paid);
    }

    public function test_receipt_endpoint_returns_a_pdf(): void
    {
        $payment = Payment::factory()->create();
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('payments.receipt', $payment));

        $response->assertOk();
        $this->assertStringContainsString('pdf', $response->headers->get('Content-Type'));
    }
}
