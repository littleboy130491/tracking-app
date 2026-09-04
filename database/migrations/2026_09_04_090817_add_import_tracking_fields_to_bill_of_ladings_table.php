<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bill_of_ladings', function (Blueprint $table) {
            $table->string('importer_name')->nullable()->after('exporter_name');
            $table->boolean('document_checked')->default(false)->after('importer_name');
            $table->boolean('draft_pib_checked')->default(false)->after('document_checked');
            $table->boolean('customer_confirmed')->default(false)->after('draft_pib_checked');
            $table->boolean('pib_sent_to_customs')->default(false)->after('customer_confirmed');
            $table->boolean('billing_issued')->default(false)->after('pib_sent_to_customs');
            $table->boolean('thc_paid')->default(false)->after('billing_issued');
            $table->boolean('waiting_do_release')->default(false)->after('thc_paid');
            $table->boolean('do_released')->default(false)->after('waiting_do_release');
            $table->date('do_release_date')->nullable()->after('do_released');
            $table->boolean('billing_paid')->default(false)->after('do_release_date');
            $table->date('departure_date')->nullable()->after('billing_paid');
            $table->dateTime('eta_at')->nullable()->after('departure_date');
            $table->string('customs_response')->nullable()->after('eta_at');
            $table->json('import_documents')->nullable()->after('customs_response');
            $table->boolean('waiting_bahandle')->default(false)->after('import_documents');
            $table->boolean('bahandle_paid')->default(false)->after('waiting_bahandle');
            $table->boolean('container_inspected')->default(false)->after('bahandle_paid');
            $table->boolean('waiting_spjm_to_sppb')->default(false)->after('container_inspected');
            $table->text('shipping_schedule')->nullable()->after('waiting_spjm_to_sppb');
            $table->string('terminal_name')->nullable()->after('shipping_schedule');
            $table->date('loading_date')->nullable()->after('terminal_name');
            $table->string('loading_destination')->nullable()->after('loading_date');
            $table->dateTime('arrived_at_factory_at')->nullable()->after('loading_destination');
            $table->boolean('empty_container_returned')->default(false)->after('arrived_at_factory_at');
        });
    }

    public function down(): void
    {
        Schema::table('bill_of_ladings', function (Blueprint $table) {
            $table->dropColumn([
                'importer_name',
                'document_checked',
                'draft_pib_checked',
                'customer_confirmed',
                'pib_sent_to_customs',
                'billing_issued',
                'thc_paid',
                'waiting_do_release',
                'do_released',
                'do_release_date',
                'billing_paid',
                'departure_date',
                'eta_at',
                'customs_response',
                'import_documents',
                'waiting_bahandle',
                'bahandle_paid',
                'container_inspected',
                'waiting_spjm_to_sppb',
                'shipping_schedule',
                'terminal_name',
                'loading_date',
                'loading_destination',
                'arrived_at_factory_at',
                'empty_container_returned',
            ]);
        });
    }
};
