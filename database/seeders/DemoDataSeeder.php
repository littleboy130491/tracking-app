<?php

namespace Database\Seeders;

use App\Models\BillOfLading;
use App\Models\BillOfLadingUpdate;
use App\Models\User;
use App\Services\BillOfLadingWorkflowService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class DemoDataSeeder extends Seeder
{
    /**
     * Extra synthetic volume records (split across customers) for list/filter demos.
     * Real client BL samples are always seeded in addition to this count.
     */
    public const DEMO_BL_COUNT = 300;

    public static ?int $recordCount = null;

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->wipeApplicationData();
        $this->seedRoles();

        $admin = $this->seedAdmin();
        $customers = $this->seedCustomers();
        $this->seedClientBillOfLadings($admin, $customers['dolpin']);
        $this->seedVolumeBillOfLadings($admin, $customers);
        $this->seedShieldPermissions();
    }

    private function volumeRecordCount(): int
    {
        if (self::$recordCount !== null) {
            return self::$recordCount;
        }

        return app()->environment('testing') ? 18 : self::DEMO_BL_COUNT;
    }

    private function wipeApplicationData(): void
    {
        Schema::disableForeignKeyConstraints();

        DB::table('bill_of_lading_audits')->delete();
        DB::table('bill_of_lading_milestone_states')->delete();
        DB::table('bill_of_lading_containers')->delete();
        DB::table('bill_of_lading_updates')->delete();
        DB::table('bill_of_ladings')->delete();
        DB::table('model_has_roles')->delete();
        DB::table('model_has_permissions')->delete();
        User::query()->delete();

        Schema::enableForeignKeyConstraints();
    }

    private function seedRoles(): void
    {
        foreach ([User::ROLE_ADMIN, User::ROLE_PANEL_USER, User::ROLE_CUSTOMER, ...User::WORKFLOW_ROLES] as $role) {
            Role::query()->firstOrCreate([
                'name' => $role,
                'guard_name' => 'web',
            ]);
        }
    }

    private function seedAdmin(): User
    {
        $admin = User::factory()->create([
            'name' => 'Demo Admin',
            'email' => 'admin@example.com',
        ]);
        $admin->assignRole(User::ROLE_ADMIN);

        return $admin;
    }

    /**
     * @return array{dolpin: User, beta: User}
     */
    private function seedCustomers(): array
    {
        $dolpin = User::factory()->create([
            'name' => 'PT Dolpin Putra Sejati',
            'email' => 'customer-a@example.com',
            'company_name' => 'PT Dolpin Putra Sejati',
            'company_address' => 'Komp. Jakarta Distribution Centre, Jl. Kapuk Kamal Raya No. 40 Blok B Kav. No. 03, Jakarta Utara 14470',
            'pic_name' => 'Ops PIC Dolpin',
            'pic_phone' => '+62 21 22057980',
            'last_login_at' => now()->subDays(1),
        ]);
        $dolpin->assignRole(User::ROLE_CUSTOMER);

        $beta = User::factory()->create([
            'name' => 'Beta Trading',
            'email' => 'customer-b@example.com',
            'company_name' => 'Beta Trading',
            'company_address' => 'Jl. Asia Afrika No. 25, Bandung 40111',
            'pic_name' => 'Budi Santoso',
            'pic_phone' => '+62 813 9876 5432',
            'last_login_at' => now()->subDays(5),
        ]);
        $beta->assignRole(User::ROLE_CUSTOMER);

        return [
            'dolpin' => $dolpin,
            'beta' => $beta,
        ];
    }

    private function seedClientBillOfLadings(User $admin, User $dolpin): void
    {
        $workflow = app(BillOfLadingWorkflowService::class);
        $consigneeAddress = 'Komp. Jakarta Distribution Centre, Jl. Kapuk Kamal Raya No. 40 Blok B Kav. No. 03, Kel. Kamal Muara, Kec. Penjaringan, Jakarta Utara 14470';

        // 1) KMTC — Green lane, delivery completed
        $kmtc = $this->createBl([
            'customer_id' => $dolpin->id,
            'bl_number' => 'KMTCSIN3242091',
            'shipment_type' => BillOfLading::TYPE_IMPORT,
            'shipping_method' => BillOfLading::SHIPPING_METHOD_FCL,
            'carrier_name' => 'KMTC Line',
            'shipment_description' => 'PP Copolymer import Singapore → Tanjung Priok (4x20\'GP)',
            'shipper_name' => 'SUMITOMO CORPORATION ASIA & OCEANIA PTE LTD',
            'consignee_name' => 'PT DOLPIN PUTRA SEJATI',
            'consignee_address' => $consigneeAddress,
            'notify_party_name' => 'SAME AS CONSIGNEE',
            'destination_agent_name' => 'PT SAMUDERA INDONESIA GROUP',
            'port_of_loading' => 'SINGAPORE',
            'port_of_discharge' => 'TANJUNG PRIOK JAKARTA INDONESIA',
            'place_of_delivery' => 'TANJUNG PRIOK JAKARTA INDONESIA',
            'vessel_name' => 'BELAWAN',
            'voyage_number' => '2506S',
            'goods_description' => '64 MT PP COPOLYMER PC9415G',
            'hs_code' => '3902.30.90',
            'package_count' => '2560 BAGS',
            'gross_weight_kg' => 64308,
            'measurement_cbm' => 117.76,
            'free_time_notes' => '14 DAYS FREE TIME AT DESTINATION',
            'input_date' => '2025-07-02',
            'shipped_on_board_date' => '2025-07-02',
            'status' => BillOfLading::STATUS_PENDING,
            'phase' => 'Input',
            'gps_tracking_url' => 'https://maps.google.com/?q=Tanjung+Priok',
            'customer_note' => 'Import BL seeded from KMTC sample.',
        ], [
            ['container_number' => 'BEAU2653110', 'seal_number' => 'KMTC1519769', 'container_type' => "20'GP", 'package_count' => '640 BAGS', 'gross_weight_kg' => 16077, 'measurement_cbm' => 29.44, 'sort_order' => 1],
            ['container_number' => 'SEGU1887687', 'seal_number' => 'KMTC1519765', 'container_type' => "20'GP", 'package_count' => '640 BAGS', 'gross_weight_kg' => 16077, 'measurement_cbm' => 29.44, 'sort_order' => 2],
            ['container_number' => 'TEMU0376333', 'seal_number' => 'KMTC1519766', 'container_type' => "20'GP", 'package_count' => '640 BAGS', 'gross_weight_kg' => 16077, 'measurement_cbm' => 29.44, 'sort_order' => 3],
            ['container_number' => 'TRHU3351311', 'seal_number' => 'KMTC1519768', 'container_type' => "20'GP", 'package_count' => '640 BAGS', 'gross_weight_kg' => 16077, 'measurement_cbm' => 29.44, 'sort_order' => 4],
        ], $admin);

        $this->advanceImportThroughLane($workflow, $kmtc, 'green', 'deliver_container', $admin->id);
        $workflow->completeCurrentMilestone($kmtc->fresh(), [
            'note' => 'Import processing completed; ready for delivery tracking.',
        ], $admin->id);
        $workflow->activateDeliveryTrack($kmtc->fresh(), $admin->id, 'Delivery track started after SPPB.');
        $workflow->advanceToMilestone($kmtc->fresh(), 'down_container_depot', $admin->id);
        $workflow->completeCurrentMilestone($kmtc->fresh(), [
            'note' => 'Delivery completed to factory.',
            'status' => BillOfLading::STATUS_COMPLETED,
        ], $admin->id);

        // 2) MSC — Yellow lane, in progress at SPJK
        $msc = $this->createBl([
            'customer_id' => $dolpin->id,
            'bl_number' => 'MEDUYF895047',
            'shipment_type' => BillOfLading::TYPE_IMPORT,
            'shipping_method' => BillOfLading::SHIPPING_METHOD_FCL,
            'carrier_name' => 'Mediterranean Shipping Company (MSC)',
            'shipment_description' => 'LLDPE import Tianjin → Jakarta (4x40\'HC)',
            'shipper_name' => 'ZHEJIANG FUTURE PETROCHEMICAL CO., LTD.',
            'consignee_name' => 'PT. DOLPIN PUTRA SEJATI',
            'consignee_address' => $consigneeAddress,
            'notify_party_name' => 'SAME AS CONSIGNEE',
            'destination_agent_name' => 'PT MSC Mediterranean Shipping Indonesia',
            'port_of_loading' => 'TIANJIN, CHINA',
            'port_of_discharge' => 'Jakarta, Java, Indonesia',
            'vessel_name' => 'MSC SHAULA',
            'voyage_number' => 'HW620A',
            'goods_description' => 'LINEAR LOW DENSITY POLYETHYLENE LLDPE SINOPEC EGF-35B',
            'hs_code' => '3901',
            'package_count' => '4320 BAGS',
            'gross_weight_kg' => 108432,
            'measurement_cbm' => 200,
            'input_date' => '2026-05-26',
            'shipped_on_board_date' => '2026-05-20',
            'status' => BillOfLading::STATUS_PENDING,
            'phase' => 'Input',
            'customer_note' => 'Awaiting document submit after SPJK.',
        ], [
            ['container_number' => 'FFAU2377526', 'seal_number' => 'FX46586486', 'container_type' => "40'HC", 'package_count' => '1080 BAGS', 'gross_weight_kg' => 27108, 'measurement_cbm' => 50, 'sort_order' => 1],
            ['container_number' => 'MSDU8105604', 'seal_number' => 'FX46586467', 'container_type' => "40'HC", 'package_count' => '1080 BAGS', 'gross_weight_kg' => 27108, 'measurement_cbm' => 50, 'sort_order' => 2],
            ['container_number' => 'TIBU4337360', 'seal_number' => 'FX46586468', 'container_type' => "40'HC", 'package_count' => '1080 BAGS', 'gross_weight_kg' => 27108, 'measurement_cbm' => 50, 'sort_order' => 3],
            ['container_number' => 'FFAU1339603', 'seal_number' => 'FX46586469', 'container_type' => "40'HC", 'package_count' => '1080 BAGS', 'gross_weight_kg' => 27108, 'measurement_cbm' => 50, 'sort_order' => 4],
        ], $admin);

        $this->advanceImportThroughLane($workflow, $msc, 'yellow', 'lane_notice', $admin->id);

        // 3) Samudera — Green lane, SPPB done / delivery pending
        $samudera = $this->createBl([
            'customer_id' => $dolpin->id,
            'bl_number' => 'SSLSGJKTCAE9741',
            'shipment_type' => BillOfLading::TYPE_IMPORT,
            'shipping_method' => BillOfLading::SHIPPING_METHOD_FCL,
            'carrier_name' => 'Samudera Shipping Line Ltd',
            'shipment_description' => 'LDPE Cosmothene import Singapore → Tanjung Priok (1x20\'GP)',
            'shipper_name' => 'SUMITOMO CORPORATION ASIA & OCEANIA PTE LTD',
            'consignee_name' => 'PT DOLPIN PUTRA SEJATI',
            'consignee_address' => $consigneeAddress,
            'notify_party_name' => 'SAME AS CONSIGNEE',
            'destination_agent_name' => 'PT. SAMUDERA AGENCIES INDONESIA',
            'port_of_loading' => 'SINGAPORE',
            'port_of_discharge' => 'TANJUNG PRIOK',
            'place_of_delivery' => 'TANJUNG PRIOK CY',
            'vessel_name' => 'AN HAI',
            'voyage_number' => '039S',
            'goods_description' => '16 MT LDPE COSMOTHENE L705',
            'hs_code' => '3901.10.99',
            'package_count' => '640 BAGS',
            'gross_weight_kg' => 16077,
            'measurement_cbm' => 29.44,
            'free_time_notes' => '14 DAYS FREE DETENTION / DEMURRAGE AT DESTINATION',
            'input_date' => '2025-02-13',
            'shipped_on_board_date' => '2025-02-13',
            'status' => BillOfLading::STATUS_PENDING,
            'phase' => 'Input',
            'customer_note' => 'SPPB issued; container delivery pending.',
        ], [
            ['container_number' => 'CAIU6179528', 'seal_number' => '2432497', 'container_type' => "20'GP", 'package_count' => '640 BAGS', 'gross_weight_kg' => 16077, 'measurement_cbm' => 29.44, 'sort_order' => 1],
        ], $admin);

        $this->advanceImportThroughLane($workflow, $samudera, 'green', 'sppb', $admin->id);
        $workflow->completeCurrentMilestone($samudera->fresh(), [
            'note' => 'SPPB completed.',
            'status' => BillOfLading::STATUS_IN_PROGRESS,
        ], $admin->id);

        // 4) COSCO — Red lane, physical inspection done, SPPB in progress
        $cosco = $this->createBl([
            'customer_id' => $dolpin->id,
            'bl_number' => 'COSU6394859890',
            'shipment_type' => BillOfLading::TYPE_IMPORT,
            'shipping_method' => BillOfLading::SHIPPING_METHOD_FCL,
            'carrier_name' => 'COSCO Shipping Lines',
            'shipment_description' => 'HDPE EGDA-6888 import Shuaiba → Jakarta (4x40HQ)',
            'shipper_name' => 'EQUATE PETROCHEMICAL CO. K.S.C.C.',
            'consignee_name' => 'PT. DOLPIN PUTRA SEJATI',
            'consignee_address' => $consigneeAddress,
            'notify_party_name' => 'SAME AS CONSIGNEE',
            'destination_agent_name' => 'PT. COSCO SHIPPING LINES INDONESIA',
            'port_of_loading' => 'SHUAIBA, KUWAIT',
            'port_of_discharge' => 'JAKARTA, INDONESIA',
            'place_of_delivery' => 'JAKARTA, INDONESIA',
            'vessel_name' => 'ZHONG GU DA LIAN',
            'voyage_number' => '24029E',
            'goods_description' => '04 CNTRS STC 99 MT HIGH DENSITY POLYETHYLENE EGDA-6888',
            'hs_code' => '3901.20',
            'package_count' => '3960 BAGS',
            'gross_weight_kg' => 101116,
            'measurement_cbm' => 220,
            'free_time_notes' => '14 DAYS FREE LINE DETENTION/DEMURRAGE AT PORT OF DISCHARGE',
            'input_date' => '2024-09-07',
            'shipped_on_board_date' => '2024-09-07',
            'status' => BillOfLading::STATUS_PENDING,
            'phase' => 'Input',
            'customer_note' => 'Physical inspection completed; SPPB in progress.',
        ], [
            ['container_number' => 'CSNU6669884', 'seal_number' => 'EQ0632762', 'container_type' => '40HQ', 'package_count' => '990 BAGS', 'gross_weight_kg' => 25279, 'measurement_cbm' => 55, 'sort_order' => 1],
            ['container_number' => 'FSCU8483882', 'seal_number' => 'EQ0632766', 'container_type' => '40HQ', 'package_count' => '990 BAGS', 'gross_weight_kg' => 25279, 'measurement_cbm' => 55, 'sort_order' => 2],
            ['container_number' => 'OOCU8417727', 'seal_number' => 'EQ0632764', 'container_type' => '40HQ', 'package_count' => '990 BAGS', 'gross_weight_kg' => 25279, 'measurement_cbm' => 55, 'sort_order' => 3],
            ['container_number' => 'TCNU1386470', 'seal_number' => 'EQ0632767', 'container_type' => '40HQ', 'package_count' => '990 BAGS', 'gross_weight_kg' => 25279, 'measurement_cbm' => 55, 'sort_order' => 4],
        ], $admin);

        $this->advanceImportThroughLane($workflow, $cosco, 'red', 'sppb', $admin->id);

        // 5) OOCL — pre-lane, billing stage
        $oocl = $this->createBl([
            'customer_id' => $dolpin->id,
            'bl_number' => 'OOLU2327606650',
            'shipment_type' => BillOfLading::TYPE_IMPORT,
            'shipping_method' => BillOfLading::SHIPPING_METHOD_FCL,
            'carrier_name' => 'Orient Overseas Container Line (OOCL)',
            'shipment_description' => 'LLDPE SINOPEC F181CC import Yangpu → Jakarta (6 containers)',
            'shipper_name' => 'SINOPEC CHEMICAL COMMERCIAL INTERNATIONAL CO., LTD.',
            'consignee_name' => 'PT. DOLPIN PUTRA SEJATI',
            'consignee_address' => $consigneeAddress,
            'notify_party_name' => 'SAME AS CONSIGNEE',
            'destination_agent_name' => 'PT. OOCL INDONESIA',
            'port_of_loading' => 'YANGPU, CHINA',
            'port_of_discharge' => 'JAKARTA, INDONESIA',
            'place_of_delivery' => 'JAKARTA, INDONESIA',
            'vessel_name' => 'OOCL LILAC',
            'voyage_number' => '004E',
            'goods_description' => 'LINEAR LOW DENSITY POLYETHYLENE SINOPEC F181CC',
            'hs_code' => '3901',
            'package_count' => '6240 BAGS',
            'gross_weight_kg' => 158028,
            'measurement_cbm' => 300,
            'input_date' => '2026-05-21',
            'shipped_on_board_date' => '2026-05-21',
            'status' => BillOfLading::STATUS_PENDING,
            'phase' => 'Input',
            'customer_note' => 'Billing stage in progress.',
        ], [
            ['container_number' => 'CCLU7687950', 'seal_number' => 'OOLKZK3993', 'container_type' => '40HQ', 'package_count' => '1040 BAGS', 'gross_weight_kg' => 26338, 'measurement_cbm' => 50, 'sort_order' => 1],
            ['container_number' => 'FFAU3320525', 'seal_number' => 'OOLKZK3992', 'container_type' => '40HQ', 'package_count' => '1040 BAGS', 'gross_weight_kg' => 26338, 'measurement_cbm' => 50, 'sort_order' => 2],
            ['container_number' => 'FFAU3136821', 'seal_number' => 'OOLKZK3996', 'container_type' => '40HQ', 'package_count' => '1040 BAGS', 'gross_weight_kg' => 26338, 'measurement_cbm' => 50, 'sort_order' => 3],
            ['container_number' => 'CSNU7931556', 'seal_number' => 'OOLKZK3994', 'container_type' => '40HQ', 'package_count' => '1040 BAGS', 'gross_weight_kg' => 26338, 'measurement_cbm' => 50, 'sort_order' => 4],
            ['container_number' => 'FFAU5965864', 'seal_number' => 'OOLKZK3995', 'container_type' => '40HQ', 'package_count' => '1040 BAGS', 'gross_weight_kg' => 26338, 'measurement_cbm' => 50, 'sort_order' => 5],
            ['container_number' => 'OOLU6751921', 'seal_number' => 'OOLKZK3997', 'container_type' => '40HQ', 'package_count' => '1040 BAGS', 'gross_weight_kg' => 26338, 'measurement_cbm' => 50, 'sort_order' => 6],
        ], $admin);

        $workflow->advanceToMilestone($oocl->fresh(), 'send_billing', $admin->id);

        // 6) Synthetic export sample
        $export = $this->createBl([
            'customer_id' => $dolpin->id,
            'bl_number' => 'EXPORT-DPS-2026-001',
            'shipment_type' => BillOfLading::TYPE_EXPORT,
            'shipping_method' => BillOfLading::SHIPPING_METHOD_FCL,
            'carrier_name' => 'Demo Export Carrier',
            'shipment_description' => 'Synthetic export demo for PEB / NPE workflow',
            'shipper_name' => 'PT DOLPIN PUTRA SEJATI',
            'consignee_name' => 'DEMO OVERSEAS BUYER LTD',
            'consignee_address' => 'Singapore',
            'port_of_loading' => 'TANJUNG PRIOK',
            'port_of_discharge' => 'SINGAPORE',
            'vessel_name' => 'DEMO VESSEL',
            'voyage_number' => '001E',
            'goods_description' => 'Export polymer samples for demo',
            'hs_code' => '3901.10',
            'package_count' => '200 BAGS',
            'gross_weight_kg' => 5200,
            'measurement_cbm' => 18.5,
            'input_date' => '2026-06-01',
            'status' => BillOfLading::STATUS_PENDING,
            'phase' => 'Input',
            'customer_note' => 'Export card creation in progress.',
        ], [
            ['container_number' => 'EXPU1234567', 'seal_number' => 'EXPSEAL001', 'container_type' => "20'GP", 'package_count' => '200 BAGS', 'gross_weight_kg' => 5200, 'measurement_cbm' => 18.5, 'sort_order' => 1],
        ], $admin);

        $workflow->advanceToMilestone($export->fresh(), 'export_card', $admin->id);
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  list<array<string, mixed>>  $containers
     */
    private function createBl(array $attributes, array $containers, User $admin): BillOfLading
    {
        $billOfLading = BillOfLading::query()->create($attributes);

        foreach ($containers as $container) {
            $billOfLading->containers()->create($container);
        }

        BillOfLadingUpdate::query()->create([
            'bill_of_lading_id' => $billOfLading->id,
            'user_id' => $admin->id,
            'status' => $billOfLading->status,
            'phase' => $billOfLading->phase,
            'milestone_key' => $billOfLading->current_milestone_key,
            'visibility' => BillOfLadingUpdate::VISIBILITY_CUSTOMER,
            'note' => 'BL record created from client sample data.',
        ]);

        return $billOfLading->fresh(['containers', 'milestoneStates']);
    }

    private function advanceImportThroughLane(
        BillOfLadingWorkflowService $workflow,
        BillOfLading $billOfLading,
        string $lane,
        string $targetLaneMilestone,
        int $adminId,
    ): void {
        $workflow->advanceToMilestone($billOfLading->fresh(), 'pib_response', $adminId);
        $workflow->completeCurrentMilestone($billOfLading->fresh(), [
            'note' => 'PIB response received.',
            'status' => BillOfLading::STATUS_IN_PROGRESS,
        ], $adminId);
        $workflow->assignCustomsLane($billOfLading->fresh(), $lane, $adminId, 'Lane assigned from customs response.');
        $workflow->advanceToMilestone($billOfLading->fresh(), $targetLaneMilestone, $adminId);
    }

    /**
     * @param  array{dolpin: User, beta: User}  $customers
     */
    private function seedVolumeBillOfLadings(User $admin, array $customers): void
    {
        $total = $this->volumeRecordCount();
        $perCustomer = intdiv($total, 2);
        $origins = ['Jakarta Port', 'Surabaya Port', 'Singapore', 'Tianjin', 'Yangpu'];
        $destinations = ['Singapore', 'Jakarta', 'Tanjung Priok', 'Manila', 'Bangkok'];

        $index = 1;
        foreach (['dolpin' => $customers['dolpin'], 'beta' => $customers['beta']] as $key => $customer) {
            for ($i = 1; $i <= $perCustomer; $i++, $index++) {
                $origin = $origins[($i - 1) % count($origins)];
                $destination = $destinations[$i % count($destinations)];
                $type = $i % 5 === 0 ? BillOfLading::TYPE_EXPORT : BillOfLading::TYPE_IMPORT;

                $billOfLading = BillOfLading::query()->create([
                    'customer_id' => $customer->id,
                    'bl_number' => sprintf('BL-%s-%04d', strtoupper($key === 'dolpin' ? 'DPS' : 'BETA'), $i),
                    'shipment_type' => $type,
                    'shipping_method' => $i % 3 === 0
                        ? BillOfLading::SHIPPING_METHOD_LCL
                        : BillOfLading::SHIPPING_METHOD_FCL,
                    'carrier_name' => 'Volume Demo Carrier',
                    'shipment_description' => sprintf('Volume demo shipment %d from %s to %s', $i, $origin, $destination),
                    'port_of_loading' => $origin,
                    'port_of_discharge' => $destination,
                    'goods_description' => 'Mixed commercial cargo for list/filter demos',
                    'package_count' => (($i * 7) % 450) + 20 .' cartons',
                    'gross_weight_kg' => round(800 + ($i * 137.5), 2),
                    'measurement_cbm' => round(4 + ($i * 0.37), 2),
                    'input_date' => now()->subDays($i)->toDateString(),
                    'status' => BillOfLading::STATUS_IN_PROGRESS,
                    'phase' => 'Input',
                    'gps_tracking_url' => $i % 4 === 0 ? 'https://maps.google.com/?q='.urlencode($destination) : null,
                    'customer_note' => 'Volume demo record.',
                ]);

                $billOfLading->containers()->create([
                    'container_number' => sprintf('%sU%07d', strtoupper(substr($key, 0, 3)), $i),
                    'seal_number' => sprintf('SEAL%05d', $i),
                    'container_type' => $i % 2 === 0 ? "40'HC" : "20'GP",
                    'package_count' => '100 BAGS',
                    'gross_weight_kg' => 10000,
                    'measurement_cbm' => 25,
                    'sort_order' => 1,
                ]);

                BillOfLadingUpdate::query()->create([
                    'bill_of_lading_id' => $billOfLading->id,
                    'user_id' => $admin->id,
                    'status' => $billOfLading->status,
                    'phase' => $billOfLading->phase,
                    'milestone_key' => $billOfLading->current_milestone_key,
                    'visibility' => BillOfLadingUpdate::VISIBILITY_CUSTOMER,
                    'note' => 'Volume demo BL created.',
                ]);

                if ($i % 11 === 0) {
                    $workflow = app(BillOfLadingWorkflowService::class);
                    $workflow->postProgressUpdate($billOfLading->fresh(), [
                        'status' => BillOfLading::STATUS_CANCELLED,
                        'note' => 'Shipment cancelled in the volume demo.',
                    ], $admin->id);
                } elseif ($i % 7 === 0) {
                    $workflow = app(BillOfLadingWorkflowService::class);
                    $workflow->postProgressUpdate($billOfLading->fresh(), [
                        'status' => BillOfLading::STATUS_ON_HOLD,
                        'note' => 'Shipment temporarily on hold in the volume demo.',
                    ], $admin->id);
                }
            }
        }
    }

    private function seedShieldPermissions(): void
    {
        Artisan::call('shield:generate', [
            '--all' => true,
            '--panel' => 'admin',
            '--option' => 'permissions',
        ]);

        Role::findByName(User::ROLE_ADMIN)->syncPermissions(Permission::query()->pluck('name'));

        $staffViewPermissions = Permission::query()
            ->whereIn('name', ['ViewAny:BillOfLading', 'View:BillOfLading'])
            ->get();

        foreach (User::WORKFLOW_ROLES as $workflowRole) {
            Role::findByName($workflowRole)->syncPermissions($staffViewPermissions);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
