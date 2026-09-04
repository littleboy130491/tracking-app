<?php

namespace Tests\Feature;

use App\Models\BillOfLading;
use App\Models\BillOfLadingMilestoneState;
use App\Models\Company;
use App\Models\Container;
use App\Models\User;
use Awcodes\Curator\Models\Media;
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
        $this->assertSame(4, User::role(User::ROLE_CUSTOMER)->count());

        // 8 client sample BLs + 18 volume demo BLs
        $this->assertSame(26, BillOfLading::query()->count());

        $customerA = User::query()->where('email', 'customer-a@example.com')->firstOrFail();
        $customerB = User::query()->where('email', 'customer-b@example.com')->firstOrFail();
        $customerC = User::query()->where('email', 'customer-c@example.com')->firstOrFail();
        $customerD = User::query()->where('email', 'customer-d@example.com')->firstOrFail();

        $this->assertEqualsCanonicalizing(
            ['PT Dolpin Putra Sejati', 'PT Nusantara Forwarding', 'PT Samudera Mitra'],
            $customerA->companies()->pluck('name')->all(),
        );
        $this->assertEqualsCanonicalizing(
            ['Beta Trading', 'PT Samudera Mitra'],
            $customerB->companies()->pluck('name')->all(),
        );
        $this->assertEqualsCanonicalizing(
            ['PT Dolpin Putra Sejati'],
            $customerC->companies()->pluck('name')->all(),
        );
        $this->assertEqualsCanonicalizing(
            ['Beta Trading'],
            $customerD->companies()->pluck('name')->all(),
        );

        $dolpin = Company::query()->where('name', 'PT Dolpin Putra Sejati')->firstOrFail();
        $beta = Company::query()->where('name', 'Beta Trading')->firstOrFail();
        $this->assertEqualsCanonicalizing(
            ['customer-a@example.com', 'customer-c@example.com'],
            $dolpin->users()->pluck('email')->all(),
        );
        $this->assertEqualsCanonicalizing(
            ['customer-b@example.com', 'customer-d@example.com'],
            $beta->users()->pluck('email')->all(),
        );

        $this->assertSame(20, $customerA->accessibleBillOfLadings()->count());
        $this->assertSame(10, $customerB->accessibleBillOfLadings()->count());
        $this->assertSame(12, $customerC->accessibleBillOfLadings()->count());
        $this->assertSame(6, $customerD->accessibleBillOfLadings()->count());

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
        $this->assertTrue(Container::query()->exists());

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

        $sharedBlNumbers = BillOfLading::query()
            ->accessibleBy($customerA)
            ->whereIn('id', $customerB->accessibleBillOfLadings()->pluck('id'))
            ->pluck('bl_number');

        $this->assertTrue($sharedBlNumbers->contains('MITRA-SIN-2026-001'));
        $this->assertTrue(
            $sharedBlNumbers->contains(fn (string $blNumber): bool => str_starts_with($blNumber, 'BL-MTR-')),
        );
        $this->assertFalse(
            BillOfLading::query()
                ->accessibleBy($customerA)
                ->where('bl_number', 'like', 'BL-BETA-%')
                ->exists(),
        );
        $this->assertFalse(
            BillOfLading::query()
                ->accessibleBy($customerB)
                ->where('bl_number', 'KMTCSIN3242091')
                ->exists(),
        );
        $this->assertSame(
            'PT Dolpin Putra Sejati',
            BillOfLading::query()->where('bl_number', 'KMTCSIN3242091')->first()?->company?->name,
        );

        $kmtc = BillOfLading::query()->where('bl_number', 'KMTCSIN3242091')->firstOrFail();
        $this->assertSame(BillOfLading::CUSTOMS_RESPONSE_SPPB, $kmtc->customs_response);
        $this->assertTrue($kmtc->document_checked);
        $this->assertTrue($kmtc->empty_container_returned);
        $this->assertNotNull($kmtc->containers()->whereNotNull('gate_out_cy_at')->first());

        $cosco = BillOfLading::query()->where('bl_number', 'COSU6394859890')->firstOrFail();
        $this->assertTrue($cosco->isSpjmResponse());
        $this->assertTrue($cosco->waiting_spjm_to_sppb);
        $this->assertNotEmpty($cosco->import_documents);

        $exportContainer = Container::query()
            ->where('container_number', 'EXPU1234567')
            ->with(['photoDoor', 'photoFloor', 'photoEir', 'photoSeal'])
            ->firstOrFail();
        $this->assertNotNull($exportContainer->photo_door_id);
        $this->assertNotNull($exportContainer->photoDoor?->url);
        $this->assertCount(4, array_filter($exportContainer->documentationPhotos()));
        $this->assertSame(4, Media::query()->count());
        $this->assertSame(3, Container::query()->where('bill_of_lading_id', $exportContainer->bill_of_lading_id)->count());
        $this->assertGreaterThan(
            1,
            BillOfLading::query()->where('bl_number', 'KMTCSIN3242091')->firstOrFail()->containers()->count(),
        );
    }

    public function test_demo_data_seeder_can_seed_hundreds_of_records_when_requested(): void
    {
        DemoDataSeeder::$recordCount = DemoDataSeeder::DEMO_BL_COUNT;

        try {
            $this->seed(DemoDataSeeder::class);

            // 8 client samples + requested volume count
            $this->assertSame(8 + DemoDataSeeder::DEMO_BL_COUNT, BillOfLading::query()->count());
        } finally {
            DemoDataSeeder::$recordCount = null;
        }
    }

    public function test_demo_data_seeder_can_be_run_again_without_leaving_legacy_rows(): void
    {
        $this->seed(DemoDataSeeder::class);
        $this->seed(DemoDataSeeder::class);

        $this->assertSame(26, BillOfLading::query()->count());
        $this->assertSame(26, BillOfLading::withTrashed()->count());
        $this->assertSame(26, BillOfLading::query()->distinct()->count('bl_number'));
    }
}
