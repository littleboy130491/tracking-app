<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bill_of_ladings', function (Blueprint $table) {
            $table->string('aju_number')->nullable()->after('bl_number')->index();
        });
    }

    public function down(): void
    {
        Schema::table('bill_of_ladings', function (Blueprint $table) {
            $table->dropIndex(['aju_number']);
            $table->dropColumn('aju_number');
        });
    }
};
