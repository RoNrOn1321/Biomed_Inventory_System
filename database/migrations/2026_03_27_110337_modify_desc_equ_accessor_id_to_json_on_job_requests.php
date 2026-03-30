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
        // Remove the 1-to-1 restriction first
        Schema::table('job_requests', function (Blueprint $table) {
            $table->dropForeign(['desc_equ_accessor_id']);
            $table->dropColumn('desc_equ_accessor_id');
        });

        // Add 1-to-Many by putting the FK on the equipment table instead
        Schema::table('desc_equ_accessories', function (Blueprint $table) {
            $table->foreignId('job_request_id')->nullable()->constrained('job_requests')->cascadeOnDelete()->after('id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('desc_equ_accessories', function (Blueprint $table) {
            $table->dropForeign(['job_request_id']);
            $table->dropColumn('job_request_id');
        });

        Schema::table('job_requests', function (Blueprint $table) {
            $table->foreignId('desc_equ_accessor_id')->nullable()->constrained('desc_equ_accessories')->nullOnDelete();
        });
    }
};
