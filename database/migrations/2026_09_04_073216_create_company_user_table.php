<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['company_id', 'user_id']);
        });

        $now = now();
        $companiesByName = [];

        foreach (DB::table('users')->orderBy('id')->get() as $user) {
            $companyName = filled($user->company_name ?? null)
                ? $user->company_name
                : null;

            if (blank($companyName)) {
                continue;
            }

            if (! isset($companiesByName[$companyName])) {
                $existingId = DB::table('companies')->where('name', $companyName)->value('id');

                $companiesByName[$companyName] = $existingId ?: DB::table('companies')->insertGetId([
                    'name' => $companyName,
                    'address' => $user->company_address,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            DB::table('company_user')->insert([
                'company_id' => $companiesByName[$companyName],
                'user_id' => $user->id,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('company_user');
    }
};
