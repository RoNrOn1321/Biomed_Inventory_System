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
            $table->enum('admin_approval', ['Pending', 'Approved', 'Rejected'])->nullable()->after('assigned_to');
            $table->text('admin_approval_notes')->nullable()->after('admin_approval');
            $table->timestamp('admin_reviewed_at')->nullable()->after('admin_approval_notes');
            $table->foreignId('admin_reviewed_by')->nullable()->constrained('users')->nullOnDelete()->after('admin_reviewed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('job_requests', function (Blueprint $table) {
            $table->dropForeign(['admin_reviewed_by']);
            $table->dropColumn(['admin_approval', 'admin_approval_notes', 'admin_reviewed_at', 'admin_reviewed_by']);
        });
    }
};
