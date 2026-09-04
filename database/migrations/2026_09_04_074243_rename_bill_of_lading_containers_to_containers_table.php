<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::rename('bill_of_lading_containers', 'containers');
    }

    public function down(): void
    {
        Schema::rename('containers', 'bill_of_lading_containers');
    }
};
