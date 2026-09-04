<?php

namespace Database\Seeders;

use App\Models\BillOfLading;
use App\Models\BillOfLadingUpdate;
use App\Models\Company;
use App\Models\Container;
use App\Models\User;
use App\Services\BillOfLadingWorkflowService;
use Awcodes\Curator\Models\Media;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
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
        $photos = $this->seedDocumentationPhotos();
        $this->seedClientBillOfLadings($admin, $customers['dolpin'], $photos);
        $this->seedVolumeBillOfLadings($admin, $customers, $photos);
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
        Storage::disk('public')->deleteDirectory('container-photos');
        Storage::disk('public')->deleteDirectory('import-documents');

        Schema::disableForeignKeyConstraints();

        DB::table('logs')->delete();
        DB::table('bill_of_lading_audits')->delete();
        DB::table('bill_of_lading_milestone_states')->delete();
        DB::table('containers')->delete();

        if (Schema::hasTable('curator')) {
            DB::table('curator')->delete();
        }
        DB::table('bill_of_lading_updates')->delete();
        DB::table('bill_of_ladings')->delete();
        DB::table('company_user')->delete();
        DB::table('companies')->delete();
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
        $dolpinCompany = Company::query()->create([
            'name' => 'PT Dolpin Putra Sejati',
            'address' => 'Komp. Jakarta Distribution Centre, Jl. Kapuk Kamal Raya No. 40 Blok B Kav. No. 03, Jakarta Utara 14470',
        ]);
        $dolpin = User::factory()->create([
            'name' => 'Ops PIC Dolpin',
            'email' => 'customer-a@example.com',
            'pic_name' => 'Ops PIC Dolpin',
            'pic_phone' => '+62 21 22057980',
            'last_login_at' => now()->subDays(1),
        ]);
        $dolpin->assignRole(User::ROLE_CUSTOMER);
        $dolpin->companies()->attach($dolpinCompany);

        $betaCompany = Company::query()->create([
            'name' => 'Beta Trading',
            'address' => 'Jl. Asia Afrika No. 25, Bandung 40111',
        ]);
        $beta = User::factory()->create([
            'name' => 'Budi Santoso',
            'email' => 'customer-b@example.com',
            'pic_name' => 'Budi Santoso',
            'pic_phone' => '+62 813 9876 5432',
            'last_login_at' => now()->subDays(5),
        ]);
        $beta->assignRole(User::ROLE_CUSTOMER);
        $beta->companies()->attach($betaCompany);

        return [
            'dolpin' => $dolpin,
            'beta' => $beta,
        ];
    }

    /**
     * @return array{door: Media, floor: Media, eir: Media, seal: Media}
     */
    private function seedDocumentationPhotos(): array
    {
        return [
            'door' => $this->createDemoPhoto('door', 'PINTU', [30, 80, 160]),
            'floor' => $this->createDemoPhoto('floor', 'LANTAI', [80, 120, 50]),
            'eir' => $this->createDemoPhoto('eir', 'EIR', [160, 90, 40]),
            'seal' => $this->createDemoPhoto('seal', 'SEAL', [120, 40, 80]),
        ];
    }

    /**
     * @param  array{0: int, 1: int, 2: int}  $rgb
     */
    private function createDemoPhoto(string $slug, string $label, array $rgb): Media
    {
        Storage::disk('public')->makeDirectory('container-photos');
        $relative = "container-photos/demo-{$slug}.jpg";
        $absolute = Storage::disk('public')->path($relative);

        if (function_exists('imagecreatetruecolor')) {
            $image = imagecreatetruecolor(640, 400);
            $background = imagecolorallocate($image, $rgb[0], $rgb[1], $rgb[2]);
            $foreground = imagecolorallocate($image, 255, 255, 255);
            imagefilledrectangle($image, 0, 0, 640, 400, $background);
            imagestring($image, 5, 250, 190, $label, $foreground);
            imagejpeg($image, $absolute, 85);
            imagedestroy($image);
        } else {
            Storage::disk('public')->put($relative, $this->fallbackJpeg());
        }

        return Media::query()->create([
            'disk' => 'public',
            'directory' => 'container-photos',
            'visibility' => 'public',
            'name' => 'demo-'.$slug,
            'path' => $relative,
            'ext' => 'jpg',
            'type' => 'image/jpeg',
            'size' => filesize($absolute) ?: 0,
            'width' => 640,
            'height' => 400,
            'alt' => $label,
            'title' => $label,
        ]);
    }

    private function fallbackJpeg(): string
    {
        return (string) base64_decode(''
            .'/9j/4AAQSkZJRgABAQAAAQABAAD/2wCEAAkGBwgHBgkIBwgKCgkLDRYPDQwMDRsUFRAWIB0iIiAdHx8kKDQsJCYxJx8fLT0tMTU3Ojo6Iys/RD84QzQ5OjcBCgoKDQwNGg8PGjclHyU3Nzc3Nzc3Nzc3Nzc3Nzc3Nzc3Nzc3Nzc3Nzc3Nzc3Nzc3Nzc3Nzc3Nzc3Nzc3Nzc3N//AABEIAAEAAQMBIgACEQEDEQH/xAAXAAADAQAAAAAAAAAAAAAAAAABAgcA/8QAFhABAQEAAAAAAAAAAAAAAAAAAAER/8QAFQEBAQAAAAAAAAAAAAAAAAAAAAX/xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oADAMBAAIQAxAAAAGdP//EABQQAQAAAAAAAAAAAAAAAAAAAAD/2gAIAQEAAT8Af//Z', true);
    }

    private function storeDemoPdf(string $filename, string $title): string
    {
        Storage::disk('public')->makeDirectory('import-documents');
        $relative = 'import-documents/'.$filename;
        Storage::disk('public')->put($relative, "%PDF-1.4\n% {$title}\n1 0 obj<<>>endobj\ntrailer<<>>\n%%EOF\n");

        return $relative;
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function importTracking(array $overrides = []): array
    {
        return [
            'importer_name' => 'PT DOLPIN PUTRA SEJATI',
            'document_checked' => true,
            'draft_pib_checked' => true,
            'customer_confirmed' => true,
            'pib_sent_to_customs' => true,
            'billing_issued' => true,
            'thc_paid' => true,
            'waiting_do_release' => false,
            'do_released' => true,
            'billing_paid' => true,
            'waiting_bahandle' => false,
            'bahandle_paid' => false,
            'container_inspected' => false,
            'waiting_spjm_to_sppb' => false,
            'empty_container_returned' => false,
            ...$overrides,
        ];
    }

    /**
     * @param  array<string, mixed>  $container
     * @return array<string, mixed>
     */
    private function withImportDelivery(array $container, string $gateOut, string $emptyDate, string $progress = Container::FACTORY_LOADING_FINAL): array
    {
        return [
            ...$container,
            'gate_out_cy_at' => $gateOut,
            'driver_name' => 'Surya Pratama',
            'license_number' => 'B 9012 DPS',
            'driver_tracking_url' => 'https://maps.google.com/?q=Cikarang+Jababeka',
            'factory_loading_progress' => $progress,
            'empty_return_depot' => $progress === Container::FACTORY_LOADING_FINAL ? 'KOJA CONTAINER DEPOT' : null,
            'empty_return_at' => $progress === Container::FACTORY_LOADING_FINAL ? $emptyDate : null,
        ];
    }

    /**
     * @param  array{door: Media, floor: Media, eir: Media, seal: Media}  $photos
     */
    private function seedClientBillOfLadings(User $admin, User $dolpin, array $photos): void
    {
        $workflow = app(BillOfLadingWorkflowService::class);
        $companyId = $dolpin->companies()->first()->id;
        $consigneeAddress = 'Komp. Jakarta Distribution Centre, Jl. Kapuk Kamal Raya No. 40 Blok B Kav. No. 03, Kel. Kamal Muara, Kec. Penjaringan, Jakarta Utara 14470';

        // 1) KMTC — Green lane / SPPB, delivery completed
        $kmtc = $this->createBl([
            'company_id' => $companyId,
            'bl_number' => 'KMTCSIN3242091',
            'aju_number' => '00005002123420250702',
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
            'customer_note' => 'SPPB issued; all 4 containers delivered and empty returned.',
            ...$this->importTracking([
                'departure_date' => '2025-07-02',
                'eta_at' => '2025-07-08 14:00:00',
                'do_release_date' => '2025-07-10',
                'customs_response' => BillOfLading::CUSTOMS_RESPONSE_SPPB,
                'shipping_schedule' => 'KMTC SIN-JKT weekly / BELAWAN 2506S',
                'terminal_name' => 'JICT 2',
                'loading_date' => '2025-07-02',
                'loading_destination' => 'Tanjung Priok',
                'on_the_way_factory_at' => '2025-07-12 09:00:00',
                'arrived_at_factory_at' => '2025-07-12 14:30:00',
                'empty_container_returned' => true,
            ]),
        ], [
            $this->withImportDelivery(['container_number' => 'BEAU2653110', 'seal_number' => 'KMTC1519769', 'container_type' => "20'GP", 'package_count' => '640 BAGS', 'gross_weight_kg' => 16077, 'measurement_cbm' => 29.44, 'sort_order' => 1], '2025-07-12 08:00:00', '2025-07-15'),
            $this->withImportDelivery(['container_number' => 'SEGU1887687', 'seal_number' => 'KMTC1519765', 'container_type' => "20'GP", 'package_count' => '640 BAGS', 'gross_weight_kg' => 16077, 'measurement_cbm' => 29.44, 'sort_order' => 2], '2025-07-12 08:40:00', '2025-07-15'),
            $this->withImportDelivery(['container_number' => 'TEMU0376333', 'seal_number' => 'KMTC1519766', 'container_type' => "20'GP", 'package_count' => '640 BAGS', 'gross_weight_kg' => 16077, 'measurement_cbm' => 29.44, 'sort_order' => 3], '2025-07-12 09:15:00', '2025-07-16'),
            $this->withImportDelivery(['container_number' => 'TRHU3351311', 'seal_number' => 'KMTC1519768', 'container_type' => "20'GP", 'package_count' => '640 BAGS', 'gross_weight_kg' => 16077, 'measurement_cbm' => 29.44, 'sort_order' => 4], '2025-07-12 09:50:00', '2025-07-16'),
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

        // 2) MSC — Yellow lane / SPJK, waiting DO
        $msc = $this->createBl([
            'company_id' => $companyId,
            'bl_number' => 'MEDUYF895047',
            'aju_number' => '00005002123420250618',
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
            'customer_note' => 'SPJK issued; waiting DO release after document submit.',
            ...$this->importTracking([
                'waiting_do_release' => true,
                'do_released' => false,
                'do_release_date' => null,
                'departure_date' => '2026-05-20',
                'eta_at' => '2026-06-04 06:00:00',
                'customs_response' => BillOfLading::CUSTOMS_RESPONSE_SPJK,
                'shipping_schedule' => 'MSC HW620A Tianjin–Jakarta',
                'terminal_name' => 'NPCT1',
                'loading_date' => '2026-05-20',
                'loading_destination' => 'Jakarta',
            ]),
        ], [
            ['container_number' => 'FFAU2377526', 'seal_number' => 'FX46586486', 'container_type' => "40'HC", 'package_count' => '1080 BAGS', 'gross_weight_kg' => 27108, 'measurement_cbm' => 50, 'sort_order' => 1],
            ['container_number' => 'MSDU8105604', 'seal_number' => 'FX46586467', 'container_type' => "40'HC", 'package_count' => '1080 BAGS', 'gross_weight_kg' => 27108, 'measurement_cbm' => 50, 'sort_order' => 2],
            ['container_number' => 'TIBU4337360', 'seal_number' => 'FX46586468', 'container_type' => "40'HC", 'package_count' => '1080 BAGS', 'gross_weight_kg' => 27108, 'measurement_cbm' => 50, 'sort_order' => 3],
            ['container_number' => 'FFAU1339603', 'seal_number' => 'FX46586469', 'container_type' => "40'HC", 'package_count' => '1080 BAGS', 'gross_weight_kg' => 27108, 'measurement_cbm' => 50, 'sort_order' => 4],
        ], $admin);

        $this->advanceImportThroughLane($workflow, $msc, 'yellow', 'lane_notice', $admin->id);

        // 3) Samudera — Green lane / SPPB, gate out started
        $samudera = $this->createBl([
            'company_id' => $companyId,
            'bl_number' => 'SSLSGJKTCAE9741',
            'aju_number' => '00005002123420250601',
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
            'customer_note' => 'SPPB issued; container gated out, still on the way to factory.',
            ...$this->importTracking([
                'departure_date' => '2025-02-13',
                'eta_at' => '2025-02-16 10:00:00',
                'do_release_date' => '2025-02-18',
                'customs_response' => BillOfLading::CUSTOMS_RESPONSE_SPPB,
                'shipping_schedule' => 'SSL AN HAI 039S Singapore–Priok',
                'terminal_name' => 'KOJA TPK',
                'loading_date' => '2025-02-13',
                'loading_destination' => 'Tanjung Priok CY',
                'on_the_way_factory_at' => '2025-02-20 07:30:00',
            ]),
        ], [
            $this->withImportDelivery(
                ['container_number' => 'CAIU6179528', 'seal_number' => '2432497', 'container_type' => "20'GP", 'package_count' => '640 BAGS', 'gross_weight_kg' => 16077, 'measurement_cbm' => 29.44, 'sort_order' => 1],
                '2025-02-20 06:45:00',
                '2025-02-22',
                Container::FACTORY_LOADING_ON_PROCESS,
            ),
        ], $admin);

        $this->advanceImportThroughLane($workflow, $samudera, 'green', 'sppb', $admin->id);
        $workflow->completeCurrentMilestone($samudera->fresh(), [
            'note' => 'SPPB completed.',
            'status' => BillOfLading::STATUS_IN_PROGRESS,
        ], $admin->id);

        // 4) COSCO — Red lane / SPJM extras
        $cosco = $this->createBl([
            'company_id' => $companyId,
            'bl_number' => 'COSU6394859890',
            'aju_number' => '00005002123420250520',
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
            'customer_note' => 'SPJM: inspection done, waiting change of status to SPPB.',
            ...$this->importTracking([
                'departure_date' => '2024-09-07',
                'eta_at' => '2024-10-02 16:00:00',
                'do_release_date' => '2024-10-04',
                'customs_response' => BillOfLading::CUSTOMS_RESPONSE_SPJM,
                'import_documents' => [$this->storeDemoPdf('cosco-spjm-pack.pdf', 'COSCO SPJM document pack')],
                'waiting_bahandle' => true,
                'bahandle_paid' => true,
                'container_inspected' => true,
                'waiting_spjm_to_sppb' => true,
                'shipping_schedule' => 'COSCO 24029E Shuaiba–Jakarta',
                'terminal_name' => 'JICT 1',
                'loading_date' => '2024-09-07',
                'loading_destination' => 'Jakarta',
            ]),
        ], [
            ['container_number' => 'CSNU6669884', 'seal_number' => 'EQ0632762', 'container_type' => '40HQ', 'package_count' => '990 BAGS', 'gross_weight_kg' => 25279, 'measurement_cbm' => 55, 'sort_order' => 1],
            ['container_number' => 'FSCU8483882', 'seal_number' => 'EQ0632766', 'container_type' => '40HQ', 'package_count' => '990 BAGS', 'gross_weight_kg' => 25279, 'measurement_cbm' => 55, 'sort_order' => 2],
            ['container_number' => 'OOCU8417727', 'seal_number' => 'EQ0632764', 'container_type' => '40HQ', 'package_count' => '990 BAGS', 'gross_weight_kg' => 25279, 'measurement_cbm' => 55, 'sort_order' => 3],
            ['container_number' => 'TCNU1386470', 'seal_number' => 'EQ0632767', 'container_type' => '40HQ', 'package_count' => '990 BAGS', 'gross_weight_kg' => 25279, 'measurement_cbm' => 55, 'sort_order' => 4],
        ], $admin);

        $this->advanceImportThroughLane($workflow, $cosco, 'red', 'sppb', $admin->id);

        // 5) OOCL — pre-lane, billing stage
        $oocl = $this->createBl([
            'company_id' => $companyId,
            'bl_number' => 'OOLU2327606650',
            'aju_number' => '00005002123420250412',
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
            'customer_note' => 'Draft PIB checked; billing not yet issued.',
            ...$this->importTracking([
                'customer_confirmed' => false,
                'pib_sent_to_customs' => false,
                'billing_issued' => false,
                'thc_paid' => false,
                'do_released' => false,
                'billing_paid' => false,
                'departure_date' => '2026-05-21',
                'eta_at' => '2026-06-02 11:00:00',
                'customs_response' => null,
                'shipping_schedule' => 'OOCL LILAC 004E Yangpu–Jakarta',
                'terminal_name' => 'JICT 2',
                'loading_date' => '2026-05-21',
                'loading_destination' => 'Jakarta',
            ]),
        ], [
            ['container_number' => 'CCLU7687950', 'seal_number' => 'OOLKZK3993', 'container_type' => '40HQ', 'package_count' => '1040 BAGS', 'gross_weight_kg' => 26338, 'measurement_cbm' => 50, 'sort_order' => 1],
            ['container_number' => 'FFAU3320525', 'seal_number' => 'OOLKZK3992', 'container_type' => '40HQ', 'package_count' => '1040 BAGS', 'gross_weight_kg' => 26338, 'measurement_cbm' => 50, 'sort_order' => 2],
            ['container_number' => 'FFAU3136821', 'seal_number' => 'OOLKZK3996', 'container_type' => '40HQ', 'package_count' => '1040 BAGS', 'gross_weight_kg' => 26338, 'measurement_cbm' => 50, 'sort_order' => 3],
            ['container_number' => 'CSNU7931556', 'seal_number' => 'OOLKZK3994', 'container_type' => '40HQ', 'package_count' => '1040 BAGS', 'gross_weight_kg' => 26338, 'measurement_cbm' => 50, 'sort_order' => 4],
            ['container_number' => 'FFAU5965864', 'seal_number' => 'OOLKZK3995', 'container_type' => '40HQ', 'package_count' => '1040 BAGS', 'gross_weight_kg' => 26338, 'measurement_cbm' => 50, 'sort_order' => 5],
            ['container_number' => 'OOLU6751921', 'seal_number' => 'OOLKZK3997', 'container_type' => '40HQ', 'package_count' => '1040 BAGS', 'gross_weight_kg' => 26338, 'measurement_cbm' => 50, 'sort_order' => 6],
        ], $admin);

        $workflow->advanceToMilestone($oocl->fresh(), 'send_billing', $admin->id);

        // 6) Export sample with Curator documentation photos
        $export = $this->createBl([
            'company_id' => $companyId,
            'bl_number' => 'EXPORT-DPS-2026-001',
            'aju_number' => '00005002987620260601',
            'shipment_type' => BillOfLading::TYPE_EXPORT,
            'shipping_method' => BillOfLading::SHIPPING_METHOD_FCL,
            'carrier_name' => 'Demo Export Carrier',
            'shipment_description' => 'Synthetic export demo for PEB / NPE workflow',
            'shipper_name' => 'PT DOLPIN PUTRA SEJATI',
            'exporter_name' => 'PT DOLPIN PUTRA SEJATI',
            'booking_order_checked' => true,
            'do_number' => 'DO-EXP-2026-001',
            'depot_closing_at' => '2026-06-03 16:00:00',
            'cy_closing_at' => '2026-06-04 18:00:00',
            'container_size' => "1x20'GP",
            'pickup_depot' => 'KOJA CONTAINER DEPOT',
            'stuffing_date' => '2026-06-05',
            'stuffing_destination' => 'Pabrik Cikarang',
            'on_the_way_factory_at' => '2026-06-05 08:30:00',
            'peb_npe_checked' => true,
            'gate_in_cy_processed' => false,
            'final_checking_notes' => 'Menunggu gate in CY.',
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
            'customer_note' => 'Empty pickup documented; stuffing still ON-PROCESS.',
        ], [
            [
                'container_number' => 'EXPU1234567',
                'seal_number' => 'EXPSEAL001',
                'container_type' => "20'GP",
                'package_count' => '200 BAGS',
                'gross_weight_kg' => 5200,
                'measurement_cbm' => 18.5,
                'driver_name' => 'Andi Saputra',
                'license_number' => 'B 1234 EXP',
                'driver_tracking_url' => 'https://maps.google.com/?q=Pabrik+Cikarang',
                'photo_door_id' => $photos['door']->id,
                'photo_floor_id' => $photos['floor']->id,
                'photo_eir_id' => $photos['eir']->id,
                'photo_seal_id' => $photos['seal']->id,
                'stuffing_progress' => Container::STUFFING_ON_PROCESS,
                'gate_in_pol' => 'TANJUNG PRIOK',
                'vgm_kg' => 5450,
                'final_checked' => false,
                'sort_order' => 1,
            ],
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
     * @param  array{door: Media, floor: Media, eir: Media, seal: Media}  $photos
     */
    private function seedVolumeBillOfLadings(User $admin, array $customers, array $photos): void
    {
        $total = $this->volumeRecordCount();
        $perCustomer = intdiv($total, 2);
        $origins = ['Jakarta Port', 'Surabaya Port', 'Singapore', 'Tianjin', 'Yangpu'];
        $destinations = ['Singapore', 'Jakarta', 'Tanjung Priok', 'Manila', 'Bangkok'];

        $index = 1;
        foreach (['dolpin' => $customers['dolpin'], 'beta' => $customers['beta']] as $key => $customer) {
            $company = $customer->companies()->first();

            for ($i = 1; $i <= $perCustomer; $i++, $index++) {
                $origin = $origins[($i - 1) % count($origins)];
                $destination = $destinations[$i % count($destinations)];
                $type = $i % 5 === 0 ? BillOfLading::TYPE_EXPORT : BillOfLading::TYPE_IMPORT;
                $isExport = $type === BillOfLading::TYPE_EXPORT;

                $tracking = $isExport
                    ? [
                        'exporter_name' => $company->name,
                        'booking_order_checked' => $i % 2 === 0,
                        'do_number' => sprintf('DO-VOL-%04d', $i),
                        'pickup_depot' => 'KOJA CONTAINER DEPOT',
                        'container_size' => $i % 2 === 0 ? "1x40'HC" : "1x20'GP",
                    ]
                    : [
                        'importer_name' => $company->name,
                        'document_checked' => true,
                        'draft_pib_checked' => $i % 2 === 0,
                        'customs_response' => match ($i % 4) {
                            1 => BillOfLading::CUSTOMS_RESPONSE_SPPB,
                            2 => BillOfLading::CUSTOMS_RESPONSE_SPJK,
                            3 => BillOfLading::CUSTOMS_RESPONSE_SPJM,
                            default => null,
                        },
                        'shipping_schedule' => sprintf('Volume weekly schedule %d', $i),
                        'waiting_bahandle' => $i % 4 === 3,
                        'container_inspected' => $i % 4 === 3,
                    ];

                $billOfLading = BillOfLading::query()->create([
                    'company_id' => $company->id,
                    'bl_number' => sprintf('BL-%s-%04d', strtoupper($key === 'dolpin' ? 'DPS' : 'BETA'), $i),
                    'aju_number' => sprintf('00005002%08d', $i),
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
                    ...$tracking,
                ]);

                $container = [
                    'container_number' => sprintf('%sU%07d', strtoupper(substr($key, 0, 3)), $i),
                    'seal_number' => sprintf('SEAL%05d', $i),
                    'container_type' => $i % 2 === 0 ? "40'HC" : "20'GP",
                    'package_count' => '100 BAGS',
                    'gross_weight_kg' => 10000,
                    'measurement_cbm' => 25,
                    'sort_order' => 1,
                ];

                if ($isExport) {
                    $container['stuffing_progress'] = $i % 2 === 0
                        ? Container::STUFFING_FINISHED
                        : Container::STUFFING_ON_PROCESS;
                    $container['driver_name'] = 'Volume Driver '.$i;
                    $container['license_number'] = sprintf('B %04d VOL', $i);

                    if ($i % 5 === 0) {
                        $container['photo_door_id'] = $photos['door']->id;
                        $container['photo_floor_id'] = $photos['floor']->id;
                        $container['photo_eir_id'] = $photos['eir']->id;
                        $container['photo_seal_id'] = $photos['seal']->id;
                    }
                } elseif ($i % 3 === 0) {
                    $container['gate_out_cy_at'] = now()->subDays($i)->setTime(8, 0)->toDateTimeString();
                    $container['factory_loading_progress'] = Container::FACTORY_LOADING_ON_PROCESS;
                    $container['driver_name'] = 'Volume Driver '.$i;
                }

                $billOfLading->containers()->create($container);

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
