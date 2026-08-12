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
            ->assertSee('BL atau kontainer')
            ->assertSee('Lihat pelacakan');

        $this->actingAs($customer)
            ->get(route('customer.dashboard', ['q' => 'MISSING']))
            ->assertOk()
            ->assertSee('Tidak ada pengiriman ditemukan');
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
            ->assertSee('customer@example.com');
    }

    public function test_customer_can_filter_dashboard_by_status_type_month_and_year(): void
    {
        $customer = User::factory()->customer()->create();

        $match = BillOfLading::factory()->create([
            'customer_id' => $customer->id,
            'bl_number' => 'BL-MATCH',
            'status' => 'In Progress',
            'shipment_type' => BillOfLading::TYPE_IMPORT,
            'input_date' => '2026-06-15',
        ]);
        $match->forceFill(['current_milestone_key' => 'process_do'])->saveQuietly();

        $other = BillOfLading::factory()->create([
            'customer_id' => $customer->id,
            'bl_number' => 'BL-OTHER',
            'shipment_type' => BillOfLading::TYPE_EXPORT,
            'input_date' => '2026-01-10',
        ]);
        $other->forceFill(['current_milestone_key' => 'receive_docs'])->saveQuietly();

        $this->actingAs($customer)
            ->get(route('customer.dashboard', [
                'status' => 'In Progress',
                'shipment_type' => BillOfLading::TYPE_IMPORT,
                'month' => '6',
                'year' => '2026',
            ]))
            ->assertOk()
            ->assertSee('BL-MATCH')
            ->assertDontSee('BL-OTHER')
            ->assertSee('Juni')
            ->assertSee('2026');
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
            ->assertSee('Menampilkan 1–10 dari 12 data')
            ->assertSee('Halaman 1 dari 2');

        $this->actingAs($customer)
            ->get(route('customer.dashboard', ['per_page' => 25]))
            ->assertOk()
            ->assertSee('Menampilkan 1–12 dari 12 data')
            ->assertDontSee('Halaman 1 dari 2');
    }

    public function test_customer_with_no_bl_records_sees_empty_state(): void
    {
        $customer = User::factory()->customer()->create();

        $this->actingAs($customer)
            ->get(route('customer.dashboard'))
            ->assertOk()
            ->assertSee('Tidak ada pengiriman ditemukan');
    }

    public function test_customer_bl_detail_shows_tracking_fields_and_update_history(): void
    {
        $customer = User::factory()->customer()->create();
        $billOfLading = BillOfLading::factory()->create([
            'customer_id' => $customer->id,
            'bl_number' => 'BL-DETAIL-001',
            'shipment_description' => 'Machinery shipment to Singapore',
            'port_of_loading' => 'Jakarta Port, Indonesia',
            'port_of_discharge' => 'Singapore Port, Singapore',
            'place_of_delivery' => 'Jurong Logistics Hub',
            'shipper_name' => 'PT Example Shipper',
            'consignee_name' => 'Example Singapore Pte Ltd',
            'consignee_address' => "10 Jurong Pier Road\nSingapore 619162",
            'notify_party_name' => 'Example Notify Party',
            'destination_agent_name' => 'Example Destination Agent',
            'goods_description' => 'CNC machinery parts, 12 crates',
            'package_count' => '12 crates',
            'gross_weight_kg' => 3200.50,
            'measurement_cbm' => 14.20,
            'status' => 'In Progress',
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
            ->assertSee('Pihak terkait')
            ->assertSee('PT Example Shipper')
            ->assertSee('Example Singapore Pte Ltd')
            ->assertSee('Example Notify Party')
            ->assertSee('Tujuan pengiriman')
            ->assertSee('Jurong Logistics Hub')
            ->assertSee('10 Jurong Pier Road')
            ->assertSee('Example Destination Agent')
            ->assertSee('CNC machinery parts, 12 crates')
            ->assertSee('3,200.50 kg')
            ->assertSee('14.20 CBM')
            ->assertSee('Sedang diproses')
            ->assertSee('Currently in transit.')
            ->assertSee('Pembaruan')
            ->assertSee('Initial history entry.')
            ->assertSee('Proses impor')
            ->assertSee('Kontainer')
            ->assertSee('Langkah saat ini');
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
