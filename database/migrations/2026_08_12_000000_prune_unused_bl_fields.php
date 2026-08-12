<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('bill_of_lading_documents');

        Schema::table('bill_of_lading_containers', function (Blueprint $table) {
            $table->dropColumn('goods_description');
        });

        Schema::table('bill_of_lading_milestone_states', function (Blueprint $table) {
            $table->dropColumn('allows_document');
        });
    }

    public function down(): void
    {
        Schema::table('bill_of_lading_containers', function (Blueprint $table) {
            $table->text('goods_description')->nullable();
        });

        Schema::table('bill_of_lading_milestone_states', function (Blueprint $table) {
            $table->boolean('allows_document')->default(false);
        });

        Schema::create('bill_of_lading_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bill_of_lading_id')->constrained()->cascadeOnDelete();
            $table->foreignId('milestone_state_id')->nullable()->constrained('bill_of_lading_milestone_states')->nullOnDelete();
            $table->string('document_type')->default('other');
            $table->string('title');
            $table->string('file_path');
            $table->string('visibility')->default('admin_only');
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('uploaded_at')->nullable();
            $table->timestamps();
        });
    }
};
