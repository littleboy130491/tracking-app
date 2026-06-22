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
        Schema::create('bill_of_ladings', function (Blueprint $table) {
            $table->id();
            $table->string('bl_number')->unique();
            $table->foreignId('customer_id')->constrained('users')->cascadeOnDelete();
            $table->text('shipment_description');
            $table->date('input_date');
            $table->string('status')->default('Pending');
            $table->string('phase')->default('Input');
            $table->text('gps_tracking_url')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bill_of_ladings');
    }
};
