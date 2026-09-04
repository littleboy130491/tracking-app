<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('containers', function (Blueprint $table) {
            $table->foreignId('photo_door_id')->nullable()->after('driver_tracking_url')->constrained('curator')->nullOnDelete();
            $table->foreignId('photo_floor_id')->nullable()->after('photo_door_id')->constrained('curator')->nullOnDelete();
            $table->foreignId('photo_eir_id')->nullable()->after('photo_floor_id')->constrained('curator')->nullOnDelete();
            $table->foreignId('photo_seal_id')->nullable()->after('photo_eir_id')->constrained('curator')->nullOnDelete();
        });

        Schema::table('containers', function (Blueprint $table) {
            $table->dropColumn([
                'photo_door_path',
                'photo_floor_path',
                'photo_eir_path',
                'photo_seal_path',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('containers', function (Blueprint $table) {
            $table->string('photo_door_path')->nullable()->after('driver_tracking_url');
            $table->string('photo_floor_path')->nullable()->after('photo_door_path');
            $table->string('photo_eir_path')->nullable()->after('photo_floor_path');
            $table->string('photo_seal_path')->nullable()->after('photo_eir_path');
        });

        Schema::table('containers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('photo_door_id');
            $table->dropConstrainedForeignId('photo_floor_id');
            $table->dropConstrainedForeignId('photo_eir_id');
            $table->dropConstrainedForeignId('photo_seal_id');
        });
    }
};
