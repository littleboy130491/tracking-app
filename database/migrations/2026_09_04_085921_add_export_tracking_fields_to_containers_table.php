<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('containers', function (Blueprint $table) {
            $table->string('driver_name')->nullable()->after('seal_number');
            $table->string('license_number')->nullable()->after('driver_name');
            $table->string('driver_tracking_url')->nullable()->after('license_number');
            $table->string('photo_door_path')->nullable()->after('driver_tracking_url');
            $table->string('photo_floor_path')->nullable()->after('photo_door_path');
            $table->string('photo_eir_path')->nullable()->after('photo_floor_path');
            $table->string('photo_seal_path')->nullable()->after('photo_eir_path');
            $table->string('stuffing_progress')->nullable()->after('photo_seal_path');
            $table->timestamp('gate_in_cy_at')->nullable()->after('stuffing_progress');
            $table->string('gate_in_pol')->nullable()->after('gate_in_cy_at');
            $table->decimal('vgm_kg', 12, 3)->nullable()->after('gate_in_pol');
            $table->boolean('final_checked')->default(false)->after('vgm_kg');
            $table->date('final_checked_at')->nullable()->after('final_checked');
        });
    }

    public function down(): void
    {
        Schema::table('containers', function (Blueprint $table) {
            $table->dropColumn([
                'driver_name',
                'license_number',
                'driver_tracking_url',
                'photo_door_path',
                'photo_floor_path',
                'photo_eir_path',
                'photo_seal_path',
                'stuffing_progress',
                'gate_in_cy_at',
                'gate_in_pol',
                'vgm_kg',
                'final_checked',
                'final_checked_at',
            ]);
        });
    }
};
