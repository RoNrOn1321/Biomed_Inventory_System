<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE users MODIFY COLUMN account_type ENUM('End_User','Biomed_Technician','Admin','Moderator') NOT NULL DEFAULT 'End_User'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE users MODIFY COLUMN account_type ENUM('End_User','Biomed_Technician','Admin') NOT NULL DEFAULT 'End_User'");
    }
};
