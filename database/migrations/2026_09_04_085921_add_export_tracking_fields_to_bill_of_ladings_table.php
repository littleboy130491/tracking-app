<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bill_of_ladings', function (Blueprint $table) {
            $table->string('exporter_name')->nullable()->after('shipper_name');
            $table->boolean('booking_order_checked')->default(false)->after('exporter_name');
            $table->string('do_number')->nullable()->after('booking_order_checked');
            $table->dateTime('depot_closing_at')->nullable()->after('do_number');
            $table->dateTime('cy_closing_at')->nullable()->after('depot_closing_at');
            $table->string('container_size')->nullable()->after('cy_closing_at');
            $table->string('pickup_depot')->nullable()->after('container_size');
            $table->date('stuffing_date')->nullable()->after('pickup_depot');
            $table->string('stuffing_destination')->nullable()->after('stuffing_date');
            $table->dateTime('on_the_way_factory_at')->nullable()->after('stuffing_destination');
            $table->boolean('peb_npe_checked')->default(false)->after('on_the_way_factory_at');
            $table->boolean('gate_in_cy_processed')->default(false)->after('peb_npe_checked');
            $table->text('final_checking_notes')->nullable()->after('gate_in_cy_processed');
        });
    }

    public function down(): void
    {
        Schema::table('bill_of_ladings', function (Blueprint $table) {
            $table->dropColumn([
                'exporter_name',
                'booking_order_checked',
                'do_number',
                'depot_closing_at',
                'cy_closing_at',
                'container_size',
                'pickup_depot',
                'stuffing_date',
                'stuffing_destination',
                'on_the_way_factory_at',
                'peb_npe_checked',
                'gate_in_cy_processed',
                'final_checking_notes',
            ]);
        });
    }
};
