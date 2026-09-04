<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bill_of_ladings', function (Blueprint $table) {
            $table->foreignId('company_id')
                ->nullable()
                ->after('customer_id')
                ->constrained()
                ->restrictOnDelete();
        });

        $now = now();

        foreach (DB::table('bill_of_ladings')->orderBy('id')->get() as $billOfLading) {
            $companyId = DB::table('company_user')
                ->where('user_id', $billOfLading->customer_id)
                ->value('company_id');

            if (! $companyId && filled($billOfLading->customer_id)) {
                $customer = DB::table('users')->where('id', $billOfLading->customer_id)->first();
                $companyName = filled($customer?->company_name)
                    ? $customer->company_name
                    : ($customer?->name ?: 'Unassigned company');

                $companyId = DB::table('companies')->where('name', $companyName)->value('id');

                if (! $companyId) {
                    $companyId = DB::table('companies')->insertGetId([
                        'name' => $companyName,
                        'address' => $customer?->company_address,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }

                if ($customer) {
                    $alreadyLinked = DB::table('company_user')
                        ->where('company_id', $companyId)
                        ->where('user_id', $customer->id)
                        ->exists();

                    if (! $alreadyLinked) {
                        DB::table('company_user')->insert([
                            'company_id' => $companyId,
                            'user_id' => $customer->id,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]);
                    }
                }
            }

            if ($companyId) {
                DB::table('bill_of_ladings')
                    ->where('id', $billOfLading->id)
                    ->update(['company_id' => $companyId]);
            }
        }

        Schema::table('bill_of_ladings', function (Blueprint $table) {
            $table->dropIndex('bl_customer_updated_idx');
            $table->dropConstrainedForeignId('customer_id');
            $table->index(['company_id', 'updated_at'], 'bl_company_updated_idx');
        });
    }

    public function down(): void
    {
        Schema::table('bill_of_ladings', function (Blueprint $table) {
            $table->foreignId('customer_id')
                ->nullable()
                ->after('id')
                ->constrained('users')
                ->cascadeOnDelete();
        });

        foreach (DB::table('bill_of_ladings')->orderBy('id')->get() as $billOfLading) {
            $userId = DB::table('company_user')
                ->where('company_id', $billOfLading->company_id)
                ->value('user_id');

            if ($userId) {
                DB::table('bill_of_ladings')
                    ->where('id', $billOfLading->id)
                    ->update(['customer_id' => $userId]);
            }
        }

        Schema::table('bill_of_ladings', function (Blueprint $table) {
            $table->dropIndex('bl_company_updated_idx');
            $table->dropConstrainedForeignId('company_id');
            $table->index(['customer_id', 'updated_at'], 'bl_customer_updated_idx');
        });
    }
};
