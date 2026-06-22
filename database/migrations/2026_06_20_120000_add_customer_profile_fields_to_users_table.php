<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('company_name')->nullable()->after('name');
            $table->text('company_address')->nullable()->after('company_name');
            $table->string('pic_name')->nullable()->after('company_address');
            $table->string('pic_phone')->nullable()->after('pic_name');
            $table->timestamp('last_login_at')->nullable()->after('remember_token');
        });

        DB::table('users')
            ->whereNotNull('name')
            ->whereNull('company_name')
            ->update([
                'company_name' => DB::raw('name'),
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'company_name',
                'company_address',
                'pic_name',
                'pic_phone',
                'last_login_at',
            ]);
        });
    }
};
