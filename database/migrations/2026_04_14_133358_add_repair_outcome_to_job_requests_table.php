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
        Schema::table('job_requests', function (Blueprint $table) {
            $table->string('repair_outcome')->nullable()->after('repair_category'); // 'Repaired' or 'Unserviceable'
            $table->foreignId('equipment_id')->nullable()->constrained('equipment')->nullOnDelete()->after('repair_outcome');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('job_requests', function (Blueprint $table) {
            $table->dropForeign(['equipment_id']);
            $table->dropColumn(['repair_outcome', 'equipment_id']);
        });
    }
};
