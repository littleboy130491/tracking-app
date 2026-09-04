<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('containers', function (Blueprint $table) {
            $table->dateTime('gate_out_cy_at')->nullable()->after('gate_in_cy_at');
            $table->string('factory_loading_progress')->nullable()->after('stuffing_progress');
            $table->string('empty_return_depot')->nullable()->after('factory_loading_progress');
            $table->date('empty_return_at')->nullable()->after('empty_return_depot');
        });
    }

    public function down(): void
    {
        Schema::table('containers', function (Blueprint $table) {
            $table->dropColumn([
                'gate_out_cy_at',
                'factory_loading_progress',
                'empty_return_depot',
                'empty_return_at',
            ]);
        });
    }
};
