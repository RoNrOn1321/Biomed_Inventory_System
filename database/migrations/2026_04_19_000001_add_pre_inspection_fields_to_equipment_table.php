<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('equipment', function (Blueprint $table) {
            $table->string('pre_inspection_control_no')->nullable()->after('status');
            $table->date('pre_inspectioned_at')->nullable()->after('pre_inspection_control_no');
        });
    }

    public function down(): void
    {
        Schema::table('equipment', function (Blueprint $table) {
            $table->dropColumn(['pre_inspection_control_no', 'pre_inspectioned_at']);
        });
    }
};
