<?php

namespace Tests\Feature;

use App\Models\BillOfLading;
use App\Models\BillOfLadingContainer;
use App\Models\BillOfLadingMilestoneState;
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

        // 6 client sample BLs + 18 volume demo BLs
        $this->assertSame(24, BillOfLading::query()->count());

        $customerA = User::query()->where('email', 'customer-a@example.com')->firstOrFail();
        $customerB = User::query()->where('email', 'customer-b@example.com')->firstOrFail();

        $this->assertTrue($customerA->companies()->where('name', 'PT Dolpin Putra Sejati')->exists());
        $this->assertTrue($customerB->companies()->where('name', 'Beta Trading')->exists());
        $this->assertSame(15, $customerA->accessibleBillOfLadings()->count()); // 6 client + 9 volume
        $this->assertSame(9, $customerB->accessibleBillOfLadings()->count());

        $this->assertTrue(
            BillOfLading::query()->where('bl_number', 'KMTCSIN3242091')->exists(),
        );
        $this->assertTrue(
            BillOfLading::query()->where('bl_number', 'EXPORT-DPS-2026-001')->exists(),
        );

        $sampleBl = BillOfLading::query()
            ->where('bl_number', 'MEDUYF895047')
            ->with(['containers', 'milestoneStates'])
            ->firstOrFail();

        $this->assertSame(BillOfLading::TYPE_IMPORT, $sampleBl->shipment_type);
        $this->assertSame('yellow', $sampleBl->customs_lane);
        $this->assertNotNull($sampleBl->port_of_loading);
        $this->assertNotNull($sampleBl->port_of_discharge);
        $this->assertNotNull($sampleBl->goods_description);
        $this->assertGreaterThan(0, $sampleBl->containers->count());
        $this->assertTrue(
            $sampleBl->milestoneStates->contains(
                fn (BillOfLadingMilestoneState $milestone): bool => $milestone->state === BillOfLadingMilestoneState::STATE_IN_PROGRESS
            )
        );
        $this->assertTrue(BillOfLadingContainer::query()->exists());

        $ooclContainers = BillOfLading::query()
            ->where('bl_number', 'OOLU2327606650')
            ->firstOrFail()
            ->containers()
            ->pluck('container_number')
            ->all();

        $this->assertSame([
            'CCLU7687950',
            'FFAU3320525',
            'FFAU3136821',
            'CSNU7931556',
            'FFAU5965864',
            'OOLU6751921',
        ], $ooclContainers);

        $this->assertFalse(BillOfLading::query()
            ->where('status', BillOfLading::STATUS_COMPLETED)
            ->whereHas('milestoneStates', fn ($query) => $query->whereIn('state', [
                BillOfLadingMilestoneState::STATE_PENDING,
                BillOfLadingMilestoneState::STATE_IN_PROGRESS,
            ]))
            ->exists());

        $this->assertFalse(
            BillOfLading::query()
                ->accessibleBy($customerA)
                ->whereIn('id', $customerB->accessibleBillOfLadings()->pluck('id'))
                ->exists(),
        );
        $this->assertSame(
            'PT Dolpin Putra Sejati',
            BillOfLading::query()->where('bl_number', 'KMTCSIN3242091')->first()?->company?->name,
        );
    }

    public function test_demo_data_seeder_can_seed_hundreds_of_records_when_requested(): void
    {
        DemoDataSeeder::$recordCount = DemoDataSeeder::DEMO_BL_COUNT;

        try {
            $this->seed(DemoDataSeeder::class);

            // 6 client samples + requested volume count
            $this->assertSame(6 + DemoDataSeeder::DEMO_BL_COUNT, BillOfLading::query()->count());
        } finally {
            DemoDataSeeder::$recordCount = null;
        }
    }

    public function test_demo_data_seeder_can_be_run_again_without_leaving_legacy_rows(): void
    {
        $this->seed(DemoDataSeeder::class);
        $this->seed(DemoDataSeeder::class);

        $this->assertSame(24, BillOfLading::query()->count());
        $this->assertSame(24, BillOfLading::withTrashed()->count());
        $this->assertSame(24, BillOfLading::query()->distinct()->count('bl_number'));
    }
}
