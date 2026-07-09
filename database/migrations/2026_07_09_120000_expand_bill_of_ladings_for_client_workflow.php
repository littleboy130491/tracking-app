<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bill_of_ladings', function (Blueprint $table) {
            $table->string('shipment_type')->default('import')->after('customer_id');
            $table->string('booking_number')->nullable()->after('bl_number');
            $table->string('carrier_name')->nullable()->after('booking_number');
            $table->string('bl_document_type')->nullable()->after('carrier_name');
            $table->boolean('bl_surrendered')->default(false)->after('bl_document_type');
            $table->date('issue_date')->nullable()->after('input_date');
            $table->string('place_of_issue')->nullable()->after('issue_date');
            $table->date('shipped_on_board_date')->nullable()->after('place_of_issue');

            $table->string('shipper_name')->nullable()->after('shipment_description');
            $table->text('shipper_address')->nullable()->after('shipper_name');
            $table->string('consignee_name')->nullable()->after('shipper_address');
            $table->text('consignee_address')->nullable()->after('consignee_name');
            $table->string('consignee_npwp')->nullable()->after('consignee_address');
            $table->string('notify_party_name')->nullable()->after('consignee_npwp');
            $table->text('notify_party_address')->nullable()->after('notify_party_name');
            $table->string('destination_agent_name')->nullable()->after('notify_party_address');
            $table->text('destination_agent_contact')->nullable()->after('destination_agent_name');

            $table->string('place_of_receipt')->nullable()->after('destination');
            $table->string('port_of_loading')->nullable()->after('place_of_receipt');
            $table->string('port_of_discharge')->nullable()->after('port_of_loading');
            $table->string('place_of_delivery')->nullable()->after('port_of_discharge');
            $table->string('vessel_name')->nullable()->after('place_of_delivery');
            $table->string('voyage_number')->nullable()->after('vessel_name');
            $table->string('movement_type')->nullable()->after('voyage_number');
            $table->string('service_type')->nullable()->after('movement_type');

            $table->text('goods_description')->nullable()->after('items_description');
            $table->string('hs_code')->nullable()->after('goods_description');
            $table->string('package_count')->nullable()->after('quantity');
            $table->string('container_count_label')->nullable()->after('package_count');
            $table->decimal('measurement_cbm', 12, 4)->nullable()->after('volume_cbm');
            $table->text('marks_and_numbers')->nullable()->after('measurement_cbm');
            $table->text('free_time_notes')->nullable()->after('marks_and_numbers');
            $table->string('freight_terms')->nullable()->after('free_time_notes');
            $table->string('export_reference')->nullable()->after('freight_terms');

            $table->string('customs_lane')->nullable()->after('phase');
            $table->string('current_milestone_key')->nullable()->after('customs_lane');
            $table->text('customer_note')->nullable()->after('note');
            $table->text('internal_note')->nullable()->after('customer_note');
        });

        foreach (DB::table('bill_of_ladings')->orderBy('id')->get() as $row) {
            DB::table('bill_of_ladings')->where('id', $row->id)->update([
                'port_of_loading' => $row->origin,
                'port_of_discharge' => $row->destination,
                'goods_description' => $row->items_description,
                'package_count' => $row->quantity,
                'measurement_cbm' => $row->volume_cbm,
                'customer_note' => $row->note,
                'internal_note' => $row->note,
                'current_milestone_key' => match ($row->phase) {
                    'Customs' => 'draft_pib',
                    'Transit' => 'process_do',
                    'Delivery' => 'deliver_container',
                    'Closed' => 'deliver_container',
                    default => 'receive_docs',
                },
            ]);
        }

        Schema::create('bill_of_lading_containers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bill_of_lading_id')->constrained()->cascadeOnDelete();
            $table->string('container_number');
            $table->string('seal_number')->nullable();
            $table->string('container_type')->nullable();
            $table->string('package_count')->nullable();
            $table->decimal('gross_weight_kg', 12, 3)->nullable();
            $table->decimal('measurement_cbm', 12, 4)->nullable();
            $table->decimal('tare_weight_kg', 12, 3)->nullable();
            $table->text('goods_description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['bill_of_lading_id', 'container_number'], 'bl_containers_unique');
        });

        Schema::create('bill_of_lading_milestone_states', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bill_of_lading_id')->constrained()->cascadeOnDelete();
            $table->string('workflow_key');
            $table->string('milestone_key');
            $table->unsignedInteger('sequence');
            $table->string('label');
            $table->string('customer_label')->nullable();
            $table->string('state')->default('pending');
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('customer_visible')->default(true);
            $table->boolean('allows_document')->default(false);
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['bill_of_lading_id', 'sequence']);
            $table->unique(['bill_of_lading_id', 'workflow_key', 'milestone_key'], 'bl_milestones_unique');
        });

        Schema::create('bill_of_lading_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bill_of_lading_id')->constrained()->cascadeOnDelete();
            $table->foreignId('milestone_state_id')->nullable()->constrained('bill_of_lading_milestone_states')->nullOnDelete();
            $table->string('document_type')->default('other');
            $table->string('title');
            $table->string('file_path');
            $table->string('visibility')->default('admin_only');
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('uploaded_at')->nullable();
            $table->timestamps();
        });

        Schema::table('bill_of_lading_updates', function (Blueprint $table) {
            $table->string('milestone_key')->nullable()->after('phase');
            $table->string('customs_lane')->nullable()->after('milestone_key');
            $table->string('visibility')->default('customer')->after('customs_lane');
        });
    }

    public function down(): void
    {
        Schema::table('bill_of_lading_updates', function (Blueprint $table) {
            $table->dropColumn(['milestone_key', 'customs_lane', 'visibility']);
        });

        Schema::dropIfExists('bill_of_lading_documents');
        Schema::dropIfExists('bill_of_lading_milestone_states');
        Schema::dropIfExists('bill_of_lading_containers');

        Schema::table('bill_of_ladings', function (Blueprint $table) {
            $table->dropColumn([
                'shipment_type',
                'booking_number',
                'carrier_name',
                'bl_document_type',
                'bl_surrendered',
                'issue_date',
                'place_of_issue',
                'shipped_on_board_date',
                'shipper_name',
                'shipper_address',
                'consignee_name',
                'consignee_address',
                'consignee_npwp',
                'notify_party_name',
                'notify_party_address',
                'destination_agent_name',
                'destination_agent_contact',
                'place_of_receipt',
                'port_of_loading',
                'port_of_discharge',
                'place_of_delivery',
                'vessel_name',
                'voyage_number',
                'movement_type',
                'service_type',
                'goods_description',
                'hs_code',
                'package_count',
                'container_count_label',
                'measurement_cbm',
                'marks_and_numbers',
                'free_time_notes',
                'freight_terms',
                'export_reference',
                'customs_lane',
                'current_milestone_key',
                'customer_note',
                'internal_note',
            ]);
        });
    }
};
