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
            $table->string('estimated_no_days', 50)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('biomedical_service_docs', function (Blueprint $table) {
            $table->integer('estimated_no_days')->nullable()->change();
        });
    }
};
