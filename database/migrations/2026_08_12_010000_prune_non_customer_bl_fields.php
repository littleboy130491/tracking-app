<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('bill_of_ladings')
            ->select([
                'id',
                'origin',
                'destination',
                'items_description',
                'quantity',
                'volume_cbm',
                'note',
                'port_of_loading',
                'port_of_discharge',
                'goods_description',
                'package_count',
                'measurement_cbm',
                'customer_note',
            ])
            ->orderBy('id')
            ->chunkById(500, function ($records): void {
                foreach ($records as $record) {
                    DB::table('bill_of_ladings')
                        ->where('id', $record->id)
                        ->update([
                            'port_of_loading' => $record->port_of_loading ?: $record->origin,
                            'port_of_discharge' => $record->port_of_discharge ?: $record->destination,
                            'goods_description' => $record->goods_description ?: $record->items_description,
                            'package_count' => $record->package_count ?: $record->quantity,
                            'measurement_cbm' => $record->measurement_cbm ?: $record->volume_cbm,
                            'customer_note' => $record->customer_note ?: $record->note,
                        ]);
                }
            });

        Schema::table('bill_of_ladings', function (Blueprint $table) {
            $table->dropColumn([
                'booking_number',
                'bl_document_type',
                'bl_surrendered',
                'issue_date',
                'place_of_issue',
                'export_reference',
                'freight_terms',
                'shipper_address',
                'consignee_npwp',
                'notify_party_address',
                'destination_agent_contact',
                'place_of_receipt',
                'movement_type',
                'service_type',
                'container_count_label',
                'marks_and_numbers',
                'internal_note',
                'origin',
                'destination',
                'items_description',
                'quantity',
                'volume_cbm',
                'note',
            ]);
        });

        Schema::table('bill_of_lading_containers', function (Blueprint $table) {
            $table->dropColumn('tare_weight_kg');
        });

        Schema::table('bill_of_lading_milestone_states', function (Blueprint $table) {
            $table->dropConstrainedForeignId('updated_by');
            $table->dropColumn('note');
        });

        Schema::table('bill_of_lading_updates', function (Blueprint $table) {
            $table->dropColumn('customs_lane');
        });
    }

    public function down(): void
    {
        Schema::table('bill_of_ladings', function (Blueprint $table) {
            $table->string('booking_number')->nullable();
            $table->string('bl_document_type')->nullable();
            $table->boolean('bl_surrendered')->default(false);
            $table->date('issue_date')->nullable();
            $table->string('place_of_issue')->nullable();
            $table->string('export_reference')->nullable();
            $table->string('freight_terms')->nullable();
            $table->text('shipper_address')->nullable();
            $table->string('consignee_npwp')->nullable();
            $table->text('notify_party_address')->nullable();
            $table->text('destination_agent_contact')->nullable();
            $table->string('place_of_receipt')->nullable();
            $table->string('movement_type')->nullable();
            $table->string('service_type')->nullable();
            $table->string('container_count_label')->nullable();
            $table->text('marks_and_numbers')->nullable();
            $table->text('internal_note')->nullable();
            $table->string('origin')->nullable();
            $table->string('destination')->nullable();
            $table->text('items_description')->nullable();
            $table->string('quantity')->nullable();
            $table->decimal('volume_cbm', 10, 2)->nullable();
            $table->text('note')->nullable();
        });

        Schema::table('bill_of_lading_containers', function (Blueprint $table) {
            $table->decimal('tare_weight_kg', 12, 3)->nullable();
        });

        Schema::table('bill_of_lading_milestone_states', function (Blueprint $table) {
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('note')->nullable();
        });

        Schema::table('bill_of_lading_updates', function (Blueprint $table) {
            $table->string('customs_lane')->nullable();
        });
    }
};
