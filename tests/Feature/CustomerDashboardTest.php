<?php

namespace Tests\Feature;

use App\Models\BillOfLading;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_sees_only_their_own_bl_records(): void
    {
        $customerA = User::factory()->customer()->create();
        $customerB = User::factory()->customer()->create();

        BillOfLading::factory()->create([
            'customer_id' => $customerA->id,
            'bl_number' => 'BL-CUSTOMER-A',
        ]);
        BillOfLading::factory()->create([
            'customer_id' => $customerB->id,
            'bl_number' => 'BL-CUSTOMER-B',
        ]);

        $this->actingAs($customerA)
            ->get(route('customer.dashboard'))
            ->assertOk()
            ->assertSee('BL-CUSTOMER-A')
            ->assertDontSee('BL-CUSTOMER-B');
    }

    public function test_customer_cannot_open_another_customers_bl_detail(): void
    {
        $customerA = User::factory()->customer()->create();
        $customerB = User::factory()->customer()->create();
        $otherBillOfLading = BillOfLading::factory()->create([
            'customer_id' => $customerB->id,
        ]);

        $this->actingAs($customerA)
            ->get(route('customer.bill-of-ladings.show', $otherBillOfLading))
            ->assertNotFound();
    }

    public function test_guest_cannot_access_customer_dashboard(): void
    {
        $this->get(route('customer.dashboard'))
            ->assertRedirectToRoute('customer.login');
    }

    public function test_gps_url_renders_as_clickable_external_link_when_present(): void
    {
        $customer = User::factory()->customer()->create();
        $billOfLading = BillOfLading::factory()->create([
            'customer_id' => $customer->id,
            'gps_tracking_url' => 'https://maps.google.com/?q=Jakarta+Port',
        ]);

        $this->actingAs($customer)
            ->get(route('customer.bill-of-ladings.show', $billOfLading))
            ->assertOk()
            ->assertSee('href="https://maps.google.com/?q=Jakarta+Port"', false)
            ->assertSee('target="_blank"', false);
    }

    public function test_customer_can_search_by_bl_number_and_empty_state_appears(): void
    {
        $customer = User::factory()->customer()->create();
        BillOfLading::factory()->create([
            'customer_id' => $customer->id,
            'bl_number' => 'BL-FOUND',
        ]);

        $this->actingAs($customer)
            ->get(route('customer.dashboard', ['q' => 'FOUND']))
            ->assertOk()
            ->assertSee('BL-FOUND')
            ->assertSee('Search BL / Container')
            ->assertSee('Enter part or all of a BL number or container number');

        $this->actingAs($customer)
            ->get(route('customer.dashboard', ['q' => 'MISSING']))
            ->assertOk()
            ->assertSee('No BL records found.');
    }

    public function test_customer_dashboard_shows_company_name_and_login_email(): void
    {
        $customer = User::factory()->customer()->create([
            'company_name' => 'Acme Logistics',
            'email' => 'customer@example.com',
        ]);

        $this->actingAs($customer)
            ->get(route('customer.dashboard'))
            ->assertOk()
            ->assertSee('Acme Logistics')
            ->assertSee('Signed in with customer@example.com');
    }

    public function test_customer_can_filter_dashboard_by_status_milestone_and_date(): void
    {
        $customer = User::factory()->customer()->create();

        $match = BillOfLading::factory()->create([
            'customer_id' => $customer->id,
            'bl_number' => 'BL-MATCH',
            'status' => 'In Progress',
            'input_date' => '2026-06-15',
        ]);
        $match->forceFill(['current_milestone_key' => 'process_do'])->saveQuietly();

        $other = BillOfLading::factory()->create([
            'customer_id' => $customer->id,
            'bl_number' => 'BL-OTHER',
            'status' => 'Pending',
            'input_date' => '2025-01-10',
        ]);
        $other->forceFill(['current_milestone_key' => 'receive_docs'])->saveQuietly();

        $this->actingAs($customer)
            ->get(route('customer.dashboard', [
                'status' => 'In Progress',
                'milestone' => 'process_do',
                'month' => '6',
                'year' => '2026',
            ]))
            ->assertOk()
            ->assertSee('BL-MATCH')
            ->assertDontSee('BL-OTHER');
    }

    public function test_customer_dashboard_paginates_results_and_supports_per_page_selection(): void
    {
        $customer = User::factory()->customer()->create();

        BillOfLading::factory()
            ->count(12)
            ->create(['customer_id' => $customer->id]);

        $this->actingAs($customer)
            ->get(route('customer.dashboard'))
            ->assertOk()
            ->assertSee('Showing 1–10 of 12 records')
            ->assertSee('Page 1 of 2');

        $this->actingAs($customer)
            ->get(route('customer.dashboard', ['per_page' => 25]))
            ->assertOk()
            ->assertSee('Showing 1–12 of 12 records')
            ->assertDontSee('Page 1 of 2');
    }

    public function test_customer_with_no_bl_records_sees_empty_state(): void
    {
        $customer = User::factory()->customer()->create();

        $this->actingAs($customer)
            ->get(route('customer.dashboard'))
            ->assertOk()
            ->assertSee('No BL records found.');
    }

    public function test_customer_bl_detail_shows_tracking_fields_and_update_history(): void
    {
        $customer = User::factory()->customer()->create();
        $billOfLading = BillOfLading::factory()->create([
            'customer_id' => $customer->id,
            'bl_number' => 'BL-DETAIL-001',
            'shipment_description' => 'Machinery shipment to Singapore',
            'origin' => 'Jakarta Port, Indonesia',
            'destination' => 'Singapore Port, Singapore',
            'port_of_loading' => 'Jakarta Port, Indonesia',
            'port_of_discharge' => 'Singapore Port, Singapore',
            'items_description' => 'CNC machinery parts, 12 crates',
            'goods_description' => 'CNC machinery parts, 12 crates',
            'quantity' => '12 crates',
            'package_count' => '12 crates',
            'gross_weight_kg' => 3200.50,
            'volume_cbm' => 14.20,
            'measurement_cbm' => 14.20,
            'status' => 'In Progress',
            'note' => 'Currently in transit.',
            'customer_note' => 'Currently in transit.',
        ]);
        $billOfLading->forceFill(['phase' => 'Transit'])->saveQuietly();

        $billOfLading->updates()->create([
            'user_id' => User::factory()->admin()->create()->id,
            'status' => 'Pending',
            'phase' => 'Input',
            'note' => 'Initial history entry.',
        ]);

        $this->actingAs($customer)
            ->get(route('customer.bill-of-ladings.show', $billOfLading))
            ->assertOk()
            ->assertSee('BL-DETAIL-001')
            ->assertSee('Machinery shipment to Singapore')
            ->assertSee('Singapore Port, Singapore')
            ->assertSee('CNC machinery parts, 12 crates')
            ->assertSee('3,200.50 kg')
            ->assertSee('14.20 CBM')
            ->assertSee('In Progress')
            ->assertSee('Currently in transit.')
            ->assertSee('Update history')
            ->assertSee('Initial history entry.')
            ->assertSee('Progress')
            ->assertSee('Containers')
            ->assertSee('Current status');
    }

    public function test_customer_pages_include_mobile_viewport_layout(): void
    {
        $customer = User::factory()->customer()->create();

        $this->actingAs($customer)
            ->get(route('customer.dashboard'))
            ->assertOk()
            ->assertSee('width=device-width', false)
            ->assertSee('/css/customer.css', false)
            ->assertSee('/js/customer.js', false);
    }
}
