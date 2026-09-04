<?php

namespace Tests\Feature;

use App\Models\BillOfLading;
use App\Models\Company;
use App\Models\Container;
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

        BillOfLading::factory()->forUser($customerA)->create([
            'bl_number' => 'BL-CUSTOMER-A',
        ]);
        BillOfLading::factory()->forUser($customerB)->create([
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
        $otherBillOfLading = BillOfLading::factory()->forUser($customerB)->create();

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
        $billOfLading = BillOfLading::factory()->forUser($customer)->create([
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
        BillOfLading::factory()->forUser($customer)->create([
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

    public function test_customer_dashboard_shows_login_email_and_company_filter(): void
    {
        $customer = User::factory()
            ->customer()
            ->withCompany(['name' => 'Acme Logistics'])
            ->create([
                'email' => 'customer@example.com',
            ]);

        $this->actingAs($customer)
            ->get(route('customer.dashboard'))
            ->assertOk()
            ->assertSee('Pengiriman')
            ->assertSee('customer@example.com')
            ->assertSee('Perusahaan')
            ->assertSee('Acme Logistics')
            ->assertSee('name="company_id"', false)
            ->assertDontSee('account-companies', false);
    }

    public function test_customer_can_filter_dashboard_by_status_type_month_and_year(): void
    {
        $customer = User::factory()->customer()->create();

        $match = BillOfLading::factory()->forUser($customer)->create([
            'bl_number' => 'BL-MATCH',
            'status' => 'In Progress',
            'shipment_type' => BillOfLading::TYPE_IMPORT,
            'input_date' => '2026-06-15',
        ]);
        $match->forceFill(['current_milestone_key' => 'process_do'])->saveQuietly();

        $other = BillOfLading::factory()->forUser($customer)->create([
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
            ->forUser($customer)
            ->count(12)
            ->create();

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
        $billOfLading = BillOfLading::factory()->forUser($customer)->create([
            'bl_number' => 'BL-DETAIL-001',
            'aju_number' => '00005002123420250702',
            'shipping_method' => BillOfLading::SHIPPING_METHOD_LCL,
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
            ->assertSee('Ringkasan')
            ->assertSee('Nomor BL')
            ->assertSee('Nomor Aju')
            ->assertSee('00005002123420250702')
            ->assertSee('Jenis Kontainer')
            ->assertSee('LCL')
            ->assertSee('data-shipment-type="'.$billOfLading->shipment_type.'"', false)
            ->assertSee('Tracking URL')
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
            ->assertSee('Log')
            ->assertSee('Created bill of lading');
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

    public function test_customer_can_access_bill_of_ladings_from_every_assigned_company(): void
    {
        $alpha = Company::factory()->create(['name' => 'Alpha Logistics']);
        $beta = Company::factory()->create(['name' => 'Beta Trading']);
        $other = Company::factory()->create(['name' => 'Hidden Freight']);

        $customer = User::factory()->customer()->create();
        $customer->companies()->sync([$alpha->id, $beta->id]);

        BillOfLading::factory()->create([
            'company_id' => $alpha->id,
            'bl_number' => 'BL-ALPHA',
        ]);
        BillOfLading::factory()->create([
            'company_id' => $beta->id,
            'bl_number' => 'BL-BETA',
        ]);
        $hidden = BillOfLading::factory()->create([
            'company_id' => $other->id,
            'bl_number' => 'BL-HIDDEN',
        ]);

        $this->actingAs($customer)
            ->get(route('customer.dashboard'))
            ->assertOk()
            ->assertSee('BL-ALPHA')
            ->assertSee('Alpha Logistics')
            ->assertSee('BL-BETA')
            ->assertSee('Beta Trading')
            ->assertDontSee('BL-HIDDEN');

        $this->actingAs($customer)
            ->get(route('customer.bill-of-ladings.show', $hidden))
            ->assertNotFound();

        $this->actingAs($customer)
            ->get(route('customer.dashboard', ['company_id' => $alpha->id]))
            ->assertOk()
            ->assertSee('BL-ALPHA')
            ->assertDontSee('BL-BETA')
            ->assertSee('name="company_id"', false);
    }

    public function test_customer_dashboard_and_bl_detail_show_company_name(): void
    {
        $company = Company::factory()->create(['name' => 'Harbour Consignee']);
        $customer = User::factory()->customer()->create();
        $customer->companies()->sync([$company->id]);

        $billOfLading = BillOfLading::factory()->create([
            'company_id' => $company->id,
            'bl_number' => 'BL-COMPANY-NAME',
        ]);

        $this->actingAs($customer)
            ->get(route('customer.dashboard'))
            ->assertOk()
            ->assertSee('Harbour Consignee')
            ->assertSee('BL-COMPANY-NAME');

        $this->actingAs($customer)
            ->get(route('customer.bill-of-ladings.show', $billOfLading))
            ->assertOk()
            ->assertSee('Harbour Consignee')
            ->assertSee('Perusahaan');
    }

    public function test_customer_can_open_container_detail_from_bl_in_a_new_tab(): void
    {
        $customer = User::factory()->customer()->create();
        $billOfLading = BillOfLading::factory()->forUser($customer)->create([
            'bl_number' => 'BL-CONTAINER-LINK',
        ]);
        $container = Container::factory()->create([
            'bill_of_lading_id' => $billOfLading->id,
            'container_number' => 'TESTU8888888',
            'seal_number' => 'SEAL88',
        ]);

        $containerUrl = route('customer.containers.show', [$billOfLading, $container]);

        $this->actingAs($customer)
            ->get(route('customer.dashboard'))
            ->assertOk()
            ->assertSee('TESTU8888888')
            ->assertSee('target="_blank"', false)
            ->assertSee($containerUrl, false);

        $this->actingAs($customer)
            ->get(route('customer.bill-of-ladings.show', $billOfLading))
            ->assertOk()
            ->assertSee('TESTU8888888')
            ->assertSee('target="_blank"', false)
            ->assertSee($containerUrl, false)
            ->assertSee('Log');

        $this->actingAs($customer)
            ->get($containerUrl)
            ->assertOk()
            ->assertSee('TESTU8888888')
            ->assertSee('SEAL88')
            ->assertSee('BL-CONTAINER-LINK')
            ->assertSee('Log')
            ->assertSee('Created container');
    }

    public function test_export_bl_detail_shows_three_process_tracking_progress(): void
    {
        $customer = User::factory()->customer()->create();
        $billOfLading = BillOfLading::factory()->forUser($customer)->create([
            'bl_number' => 'BL-EXPORT-TRACK',
            'shipment_type' => BillOfLading::TYPE_EXPORT,
            'exporter_name' => 'PT Example Exporter',
            'booking_order_checked' => true,
            'do_number' => 'DO-99',
            'carrier_name' => 'Export Line',
            'container_size' => "1x40'HC",
            'port_of_discharge' => 'Singapore',
            'pickup_depot' => 'Koja Depot',
            'peb_npe_checked' => true,
        ]);
        Container::factory()->create([
            'bill_of_lading_id' => $billOfLading->id,
            'container_number' => 'EXPU9999999',
            'seal_number' => 'SEALX1',
            'driver_name' => 'Budi Driver',
            'license_number' => 'B 9999 XX',
            'stuffing_progress' => Container::STUFFING_ON_PROCESS,
            'vgm_kg' => 21000,
        ]);

        $this->actingAs($customer)
            ->get(route('customer.bill-of-ladings.show', $billOfLading))
            ->assertOk()
            ->assertSee('Tracking Progress EXPORT')
            ->assertSee('OUTPUT — Process 1')
            ->assertSee('Document Received')
            ->assertSee('PT Example Exporter')
            ->assertSee('DO-99')
            ->assertSee('Export Line')
            ->assertSee('INPUT — Process 2')
            ->assertSee('Koja Depot')
            ->assertSee('EXPU9999999')
            ->assertSee('Budi Driver')
            ->assertSee('B 9999 XX')
            ->assertSee('FINAL — Process 3')
            ->assertSee('ON-PROCESS')
            ->assertSee('21,000.000 kg')
            ->assertDontSee('Proses impor');
    }

    public function test_import_bl_detail_does_not_show_export_process_sections(): void
    {
        $customer = User::factory()->customer()->create();
        $billOfLading = BillOfLading::factory()->forUser($customer)->create([
            'shipment_type' => BillOfLading::TYPE_IMPORT,
        ]);

        $this->actingAs($customer)
            ->get(route('customer.bill-of-ladings.show', $billOfLading))
            ->assertOk()
            ->assertSee('Proses impor')
            ->assertDontSee('Tracking Progress EXPORT');
    }

    public function test_customer_cannot_open_another_companys_container(): void
    {
        $customer = User::factory()->customer()->create();
        $other = User::factory()->customer()->create();
        $otherBl = BillOfLading::factory()->forUser($other)->create();
        $container = Container::factory()->create([
            'bill_of_lading_id' => $otherBl->id,
            'container_number' => 'HIDNU0000001',
        ]);

        $this->actingAs($customer)
            ->get(route('customer.containers.show', [$otherBl, $container]))
            ->assertNotFound();
    }
}
