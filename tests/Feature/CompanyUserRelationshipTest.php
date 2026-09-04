<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyUserRelationshipTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_can_belong_to_many_companies(): void
    {
        $user = User::factory()->customer()->create();
        $alpha = Company::factory()->create(['name' => 'Alpha Logistics']);
        $beta = Company::factory()->create(['name' => 'Beta Trading']);

        $user->companies()->sync([$alpha->id, $beta->id]);

        $this->assertEqualsCanonicalizing(
            ['Alpha Logistics', 'Beta Trading'],
            $user->companies()->pluck('name')->all(),
        );
        $this->assertTrue($alpha->users->contains($user));
        $this->assertTrue($beta->users->contains($user));
    }

    public function test_a_company_can_have_many_users(): void
    {
        $company = Company::factory()->create(['name' => 'Shared Consignee']);
        $first = User::factory()->customer()->create(['email' => 'pic-one@example.com']);
        $second = User::factory()->customer()->create(['email' => 'pic-two@example.com']);

        $company->users()->sync([$first->id, $second->id]);

        $this->assertEqualsCanonicalizing(
            ['pic-one@example.com', 'pic-two@example.com'],
            $company->users()->pluck('email')->all(),
        );
    }

    public function test_customer_factory_attaches_a_company_from_the_profile_name(): void
    {
        $customer = User::factory()
            ->customer()
            ->withCompany([
                'name' => 'Acme Logistics',
                'address' => '123 Harbour Road',
            ])
            ->create();

        $this->assertTrue($customer->companies()->where('name', 'Acme Logistics')->exists());
        $this->assertSame('123 Harbour Road', $customer->companies()->first()?->address);
    }
}
