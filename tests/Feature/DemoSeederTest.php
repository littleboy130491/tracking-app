<?php

namespace Tests\Feature;

use App\Models\BillOfLading;
use App\Models\BillOfLadingUpdate;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_seed_data_contains_admin_customers_and_separate_bl_records(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(1, User::role(User::ROLE_ADMIN)->count());
        $this->assertSame(2, User::role(User::ROLE_CUSTOMER)->count());
        $this->assertSame(24, BillOfLading::query()->count());
        $this->assertSame(48, BillOfLadingUpdate::query()->count());

        $customers = User::role(User::ROLE_CUSTOMER)->with('billOfLadings')->get();

        $this->assertTrue($customers->every(fn (User $customer): bool => $customer->billOfLadings->isNotEmpty()));

        $customerA = User::query()->where('email', 'customer-a@example.com')->firstOrFail();
        $customerB = User::query()->where('email', 'customer-b@example.com')->firstOrFail();

        $this->assertSame(12, $customerA->billOfLadings()->count());
        $this->assertSame(12, $customerB->billOfLadings()->count());
        $this->assertNotNull($customerA->company_name);
        $this->assertNotNull($customerA->pic_phone);
        $this->assertNotNull($customerA->last_login_at);

        $sampleBl = BillOfLading::query()->whereBelongsTo($customerA, 'customer')->firstOrFail();

        $this->assertNotNull($sampleBl->origin);
        $this->assertNotNull($sampleBl->destination);
        $this->assertNotNull($sampleBl->items_description);
        $this->assertNotNull($sampleBl->quantity);
        $this->assertNotNull($sampleBl->gross_weight_kg);
        $this->assertNotNull($sampleBl->volume_cbm);

        $this->assertFalse(
            BillOfLading::query()
                ->whereBelongsTo($customerA, 'customer')
                ->whereIn('id', $customerB->billOfLadings()->pluck('id'))
                ->exists(),
        );
    }

    public function test_demo_data_seeder_can_seed_hundreds_of_records_when_requested(): void
    {
        DemoDataSeeder::$recordCount = DemoDataSeeder::DEMO_BL_COUNT;

        try {
            $this->seed(DemoDataSeeder::class);

            $this->assertSame(DemoDataSeeder::DEMO_BL_COUNT, BillOfLading::query()->count());
            $this->assertSame(DemoDataSeeder::DEMO_BL_COUNT * 2, BillOfLadingUpdate::query()->count());
        } finally {
            DemoDataSeeder::$recordCount = null;
        }
    }
}
