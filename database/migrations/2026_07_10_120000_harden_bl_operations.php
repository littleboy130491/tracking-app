<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('last_login_at')->index();
        });

        Schema::table('bill_of_ladings', function (Blueprint $table) {
            $table->date('retention_until')->nullable()->after('input_date')->index();
            $table->softDeletes();

            $table->index(['customer_id', 'updated_at'], 'bl_customer_updated_idx');
            $table->index(['status', 'updated_at'], 'bl_status_updated_idx');
            $table->index(['current_milestone_key', 'updated_at'], 'bl_milestone_updated_idx');
            $table->index(['shipment_type', 'input_date'], 'bl_type_input_idx');
            $table->index(['customs_lane', 'updated_at'], 'bl_lane_updated_idx');
        });

        DB::table('bill_of_ladings')
            ->select(['id', 'input_date', 'created_at'])
            ->orderBy('id')
            ->chunkById(500, function ($rows): void {
                foreach ($rows as $row) {
                    $inputDate = Carbon::parse($row->input_date)->startOfDay();
                    $createdDate = Carbon::parse($row->created_at)->startOfDay();
                    $retentionStart = $inputDate->greaterThan($createdDate) ? $inputDate : $createdDate;

                    DB::table('bill_of_ladings')
                        ->where('id', $row->id)
                        ->update(['retention_until' => $retentionStart->addYears(3)->toDateString()]);
                }
            });

        Schema::create('bill_of_lading_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bill_of_lading_id')->nullable()->constrained()->nullOnDelete();
            $table->string('bl_number');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('event');
            $table->json('changes');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['bl_number', 'created_at']);
            $table->index(['bill_of_lading_id', 'created_at'], 'bl_audits_record_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bill_of_lading_audits');

        Schema::table('bill_of_ladings', function (Blueprint $table) {
            $table->dropIndex('bl_customer_updated_idx');
            $table->dropIndex('bl_status_updated_idx');
            $table->dropIndex('bl_milestone_updated_idx');
            $table->dropIndex('bl_type_input_idx');
            $table->dropIndex('bl_lane_updated_idx');
            $table->dropIndex(['retention_until']);
            $table->dropSoftDeletes();
            $table->dropColumn('retention_until');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['is_active']);
            $table->dropColumn('is_active');
        });
    }
};
