<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('bill_of_ladings', function (Blueprint $table) {
            $table->string('origin')->nullable()->after('shipment_description');
            $table->string('destination')->nullable()->after('origin');
            $table->text('items_description')->nullable()->after('destination');
            $table->string('quantity')->nullable()->after('items_description');
            $table->decimal('gross_weight_kg', 10, 2)->nullable()->after('quantity');
            $table->decimal('volume_cbm', 10, 2)->nullable()->after('gross_weight_kg');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bill_of_ladings', function (Blueprint $table) {
            $table->dropColumn([
                'origin',
                'destination',
                'items_description',
                'quantity',
                'gross_weight_kg',
                'volume_cbm',
            ]);
        });
    }
};
