<?php

namespace Database\Seeders;

use App\Models\BillOfLading;
use App\Models\BillOfLadingUpdate;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class DemoDataSeeder extends Seeder
{
    public const DEMO_BL_COUNT = 300;

    public static ?int $recordCount = null;

    /**
     * @var array<int, string>
     */
    private const ORIGINS = [
        'Jakarta Port, Indonesia',
        'Surabaya Port, Indonesia',
        'Semarang Port, Indonesia',
        'Belawan Port, Indonesia',
        'Makassar Port, Indonesia',
    ];

    /**
     * @var array<int, string>
     */
    private const DESTINATIONS = [
        'Singapore Port, Singapore',
        'Manila Port, Philippines',
        'Bangkok Port, Thailand',
        'Ho Chi Minh Port, Vietnam',
        'Hong Kong Port, Hong Kong',
        'Rotterdam Port, Netherlands',
    ];

    /**
     * @var array<int, string>
     */
    private const ITEM_TYPES = [
        'Consumer electronics',
        'Industrial spare parts',
        'Textile rolls',
        'Automotive components',
        'Agricultural products',
        'Medical supplies',
        'Steel coils',
        'Furniture shipment',
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->wipeApplicationData();
        $this->seedRoles();

        $admin = $this->seedAdmin();
        $customers = $this->seedCustomers();
        $this->seedBillOfLadings($admin, $customers);
        $this->seedShieldPermissions();
    }

    private function blRecordCount(): int
    {
        if (self::$recordCount !== null) {
            return self::$recordCount;
        }

        return app()->environment('testing') ? 24 : self::DEMO_BL_COUNT;
    }

    private function wipeApplicationData(): void
    {
        Schema::disableForeignKeyConstraints();

        BillOfLadingUpdate::query()->delete();
        BillOfLading::query()->delete();
        DB::table('model_has_roles')->delete();
        DB::table('model_has_permissions')->delete();
        User::query()->delete();

        Schema::enableForeignKeyConstraints();
    }

    private function seedRoles(): void
    {
        foreach ([User::ROLE_ADMIN, User::ROLE_PANEL_USER, User::ROLE_CUSTOMER] as $role) {
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
     * @return array<string, User>
     */
    private function seedCustomers(): array
    {
        $customerA = User::factory()->create([
            'name' => 'Acme Logistics',
            'email' => 'customer-a@example.com',
            'company_name' => 'Acme Logistics',
            'company_address' => 'Jl. Sudirman No. 10, Jakarta 10220',
            'pic_name' => 'Rina Wijaya',
            'pic_phone' => '+62 812 3456 7890',
            'last_login_at' => now()->subDays(2),
        ]);
        $customerA->assignRole(User::ROLE_CUSTOMER);

        $customerB = User::factory()->create([
            'name' => 'Beta Trading',
            'email' => 'customer-b@example.com',
            'company_name' => 'Beta Trading',
            'company_address' => 'Jl. Asia Afrika No. 25, Bandung 40111',
            'pic_name' => 'Budi Santoso',
            'pic_phone' => '+62 813 9876 5432',
            'last_login_at' => now()->subDays(5),
        ]);
        $customerB->assignRole(User::ROLE_CUSTOMER);

        return [
            'acme' => $customerA,
            'beta' => $customerB,
        ];
    }

    /**
     * @param  array<string, User>  $customers
     */
    private function seedBillOfLadings(User $admin, array $customers): void
    {
        $total = $this->blRecordCount();
        $perCustomer = intdiv($total, count($customers));
        $now = now();

        foreach ($customers as $key => $customer) {
            $prefix = $key === 'acme' ? 'ACME' : 'BETA';

            for ($index = 1; $index <= $perCustomer; $index++) {
                $origin = self::ORIGINS[($index - 1) % count(self::ORIGINS)];
                $destination = self::DESTINATIONS[$index % count(self::DESTINATIONS)];
                $itemType = self::ITEM_TYPES[$index % count(self::ITEM_TYPES)];
                $quantity = (($index * 7) % 450) + 20;
                $inputDate = Carbon::create(
                    year: 2024 + ($index % 3),
                    month: ($index % 12) + 1,
                    day: min((($index * 3) % 28) + 1, 28),
                );
                $status = BillOfLading::STATUSES[$index % count(BillOfLading::STATUSES)];
                $phase = BillOfLading::PHASES[$index % count(BillOfLading::PHASES)];
                $note = match ($phase) {
                    'Customs' => 'Awaiting customs document verification.',
                    'Transit' => 'Container departed origin port.',
                    'Delivery' => 'Cargo scheduled for final delivery.',
                    'Closed' => 'Shipment closed after final delivery confirmation.',
                    default => 'BL record created for tracking.',
                };

                $billOfLading = BillOfLading::query()->create([
                    'customer_id' => $customer->id,
                    'bl_number' => sprintf('BL-%s-%04d', $prefix, $index),
                    'shipment_description' => sprintf(
                        '%s shipment from %s to %s',
                        $itemType,
                        Str::before($origin, ','),
                        Str::before($destination, ','),
                    ),
                    'origin' => $origin,
                    'destination' => $destination,
                    'items_description' => sprintf('%s, %d cartons, mixed commercial cargo', $itemType, $quantity),
                    'quantity' => $quantity.' cartons',
                    'gross_weight_kg' => round(800 + ($index * 137.5), 2),
                    'volume_cbm' => round(4 + ($index * 0.37), 2),
                    'input_date' => $inputDate->toDateString(),
                    'status' => $status,
                    'phase' => $phase,
                    'gps_tracking_url' => $index % 3 === 0
                        ? 'https://maps.google.com/?q='.urlencode(Str::before($origin, ','))
                        : null,
                    'note' => $note,
                    'created_at' => $inputDate,
                    'updated_at' => $now->copy()->subDays($index % 45)->subHours($index % 12),
                ]);

                BillOfLadingUpdate::query()->create([
                    'bill_of_lading_id' => $billOfLading->id,
                    'user_id' => $admin->id,
                    'status' => 'Pending',
                    'phase' => 'Input',
                    'note' => 'BL record created for tracking.',
                    'created_at' => $inputDate,
                    'updated_at' => $inputDate,
                ]);

                BillOfLadingUpdate::query()->create([
                    'bill_of_lading_id' => $billOfLading->id,
                    'user_id' => $admin->id,
                    'status' => $status,
                    'phase' => $phase,
                    'note' => $note,
                    'created_at' => $billOfLading->updated_at,
                    'updated_at' => $billOfLading->updated_at,
                ]);
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

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
