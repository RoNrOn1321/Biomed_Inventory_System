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
        Schema::table('biomedical_service_docs', function (Blueprint $table) {
            $table->string('repair_category')->nullable()->after('remarks');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('biomedical_service_docs', function (Blueprint $table) {
            $table->dropColumn('repair_category');
        });
    }
};
