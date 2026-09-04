<?php

namespace Tests\Feature;

use App\Models\BillOfLading;
use App\Models\Container;
use App\Models\Log;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChangeLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_a_bill_of_lading_records_who_what_and_when(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $billOfLading = BillOfLading::factory()->create([
            'bl_number' => 'BL-LOG-001',
        ]);

        $log = $billOfLading->logs()->first();

        $this->assertNotNull($log);
        $this->assertSame(Log::EVENT_CREATED, $log->event);
        $this->assertTrue($log->user->is($admin));
        $this->assertSame($admin->name, $log->whoLabel());
        $this->assertStringContainsString('BL-LOG-001', $log->description);
        $this->assertNotNull($log->created_at);
    }

    public function test_updating_a_customer_records_the_changed_fields(): void
    {
        $admin = User::factory()->admin()->create();
        $customer = User::factory()->customer()->create([
            'pic_name' => 'Old PIC',
        ]);

        $this->actingAs($admin);

        $customer->update(['pic_name' => 'New PIC']);

        $log = $customer->logs()
            ->where('event', Log::EVENT_UPDATED)
            ->first();

        $this->assertNotNull($log);
        $this->assertTrue($log->user->is($admin));
        $this->assertStringContainsString('pic_name', $log->description);
        $this->assertSame('Old PIC', $log->changes['pic_name']['old']);
        $this->assertSame('New PIC', $log->changes['pic_name']['new']);
    }

    public function test_container_changes_are_logged_on_the_container(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $container = Container::factory()->create([
            'container_number' => 'TESTU0000001',
            'seal_number' => 'OLDSEAL',
        ]);

        $container->update(['seal_number' => 'NEWSEAL']);

        $this->assertTrue(
            $container->logs()->where('event', Log::EVENT_CREATED)->exists(),
        );

        $updated = $container->logs()->where('event', Log::EVENT_UPDATED)->first();

        $this->assertNotNull($updated);
        $this->assertTrue($updated->user->is($admin));
        $this->assertStringContainsString('TESTU0000001', $updated->description);
        $this->assertSame('OLDSEAL', $updated->changes['seal_number']['old']);
        $this->assertSame('NEWSEAL', $updated->changes['seal_number']['new']);
    }
}
