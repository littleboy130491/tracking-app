<?php

namespace Tests\Feature;

use App\Filament\Resources\BillOfLadings\BillOfLadingResource;
use App\Filament\Resources\Companies\CompanyResource;
use App\Filament\Resources\Containers\ContainerResource;
use App\Filament\Resources\Users\UserResource;
use App\Models\BillOfLading;
use App\Models\Company;
use App\Models\Container;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminResourcePagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_company_bl_and_container_pages_with_company_name(): void
    {
        $admin = User::factory()->admin()->create();
        $company = Company::factory()->create(['name' => 'Harbour Consignee']);
        $customer = User::factory()->customer()->withCompany($company)->create([
            'email' => 'harbour-pic@example.com',
            'pic_name' => 'Harbour PIC',
        ]);
        $billOfLading = BillOfLading::factory()->create([
            'company_id' => $company->id,
            'bl_number' => 'BL-ADMIN-UI',
        ]);
        Container::factory()->create([
            'bill_of_lading_id' => $billOfLading->id,
            'container_number' => 'TESTU9999999',
        ]);

        $this->actingAs($admin)
            ->get(CompanyResource::getUrl('index'))
            ->assertOk()
            ->assertSee('Harbour Consignee');

        $this->actingAs($admin)
            ->get(BillOfLadingResource::getUrl('index'))
            ->assertOk()
            ->assertSee('Harbour Consignee')
            ->assertSee('BL-ADMIN-UI');

        $this->actingAs($admin)
            ->get(BillOfLadingResource::getUrl('view', ['record' => $billOfLading]))
            ->assertOk()
            ->assertSee('Harbour Consignee')
            ->assertSee('TESTU9999999');

        $this->actingAs($admin)
            ->get(ContainerResource::getUrl('index'))
            ->assertOk()
            ->assertSee('TESTU9999999')
            ->assertSee('BL-ADMIN-UI')
            ->assertSee('Harbour Consignee');

        $this->actingAs($admin)
            ->get(UserResource::getUrl('index'))
            ->assertOk()
            ->assertSee('harbour-pic@example.com')
            ->assertSee('Harbour Consignee');
    }
}
