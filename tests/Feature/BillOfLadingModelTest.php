<?php

namespace Tests\Feature;

use App\Models\BillOfLading;
use App\Models\Container;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class BillOfLadingModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_bill_of_lading_can_be_created_for_a_company(): void
    {
        $customer = User::factory()->customer()->create();
        $company = $customer->companies()->firstOrFail();

        $billOfLading = BillOfLading::factory()->forUser($customer)->create([
            'bl_number' => 'BL-TEST-1001',
        ]);

        $this->assertTrue($billOfLading->company->is($company));
        $this->assertSame(1, $customer->accessibleBillOfLadings()->count());
    }

    public function test_duplicate_bl_numbers_are_rejected_by_database_and_validation(): void
    {
        BillOfLading::factory()->create([
            'bl_number' => 'BL-DUPLICATE',
        ]);

        $validator = Validator::make(
            ['bl_number' => 'BL-DUPLICATE'],
            ['bl_number' => ['required', 'unique:bill_of_ladings,bl_number']],
        );

        $this->assertTrue($validator->fails());

        $this->expectException(QueryException::class);

        BillOfLading::factory()->create([
            'bl_number' => 'BL-DUPLICATE',
        ]);
    }

    public function test_bill_of_lading_relationships_are_available(): void
    {
        $billOfLading = BillOfLading::factory()->create();

        $billOfLading->updates()->create([
            'user_id' => User::factory()->admin()->create()->id,
            'status' => 'Pending',
            'phase' => 'Input',
            'note' => 'Created.',
        ]);

        $this->assertNotNull($billOfLading->company);
        $this->assertCount(1, $billOfLading->updates);
    }

    public function test_a_bill_of_lading_can_have_many_containers(): void
    {
        $billOfLading = BillOfLading::factory()->create();

        $first = $billOfLading->containers()->create([
            'container_number' => 'TESTU1111111',
            'seal_number' => 'SEAL1',
            'container_type' => "20'GP",
            'sort_order' => 1,
        ]);
        $second = Container::factory()->create([
            'bill_of_lading_id' => $billOfLading->id,
            'container_number' => 'TESTU2222222',
            'sort_order' => 2,
        ]);

        $this->assertSame(2, $billOfLading->containers()->count());
        $this->assertTrue($first->billOfLading->is($billOfLading));
        $this->assertTrue($second->billOfLading->is($billOfLading));
        $this->assertSame(
            ['TESTU1111111', 'TESTU2222222'],
            $billOfLading->containers()->pluck('container_number')->all(),
        );
    }
}
