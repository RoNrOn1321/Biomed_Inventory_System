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
        Schema::table('equipment', function (Blueprint $table) {
            $table->enum('admin_approval', ['Pending', 'Approved', 'Rejected'])->nullable();
            $table->string('pending_action')->nullable()->comment('Restore or Condemn');
            $table->text('admin_approval_notes')->nullable();
            $table->timestamp('admin_reviewed_at')->nullable();
            $table->foreignId('admin_reviewed_by')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('equipment', function (Blueprint $table) {
            $table->dropForeign(['admin_reviewed_by']);
            $table->dropColumn(['admin_approval', 'pending_action', 'admin_approval_notes', 'admin_reviewed_at', 'admin_reviewed_by']);
        });
    }
};
