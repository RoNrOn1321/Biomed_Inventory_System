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
        Schema::create('desc_equ_accessories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('brand')->nullable();
            $table->string('model')->nullable();
            $table->string('serial_number')->nullable();
            $table->string('end_user')->nullable();
            $table->timestamps();
        });

        Schema::create('request_details', function (Blueprint $table) {
            $table->id();
            $table->json('request_type')->nullable();
            $table->timestamps();
        });

        Schema::create('repairs', function (Blueprint $table) {
            $table->id();
            $table->string('repair_type')->nullable(); // MINOR REPAIR, MAJOR REPAIR
            $table->timestamps();
        });

        Schema::create('check_verify_bies', function (Blueprint $table) {
            $table->id();
            $table->date('date')->nullable();
            $table->string('biomed_head_unit')->nullable();
            $table->timestamps();
        });

        Schema::create('biomedical_service_docs', function (Blueprint $table) {
            $table->id();
            $table->string('receive_by')->nullable();
            $table->string('performed_by')->nullable();
            $table->date('date_receive')->nullable();
            $table->date('date_performed')->nullable();
            $table->integer('estimated_no_days')->nullable();
            $table->date('technician_date_received')->nullable();
            $table->date('date_started')->nullable();
            $table->date('date_finished')->nullable();
            $table->date('date_returned')->nullable();
            $table->string('receive_by_end_user')->nullable();
            $table->foreignId('check_verify_by_id')->nullable()->constrained('check_verify_bies')->nullOnDelete();
            $table->text('remarks')->nullable();
            $table->timestamps();
        });

        Schema::table('job_requests', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('date')->nullable();
            $table->string('control_no')->nullable();
            $table->string('location')->nullable();
            $table->foreignId('desc_equ_accessor_id')->nullable()->constrained('desc_equ_accessories')->nullOnDelete();
            $table->foreignId('request_detail_id')->nullable()->constrained('request_details')->nullOnDelete();
            $table->foreignId('repair_id')->nullable()->constrained('repairs')->nullOnDelete();
            $table->text('request_complaints')->nullable();
            $table->text('job_report')->nullable();
            $table->foreignId('bio_service_docs_id')->nullable()->constrained('biomedical_service_docs')->nullOnDelete();

            $table->string('requester_name')->nullable()->change();
            $table->string('equipment_name')->nullable()->change();
            $table->text('issue_summary')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('job_requests', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['desc_equ_accessor_id']);
            $table->dropForeign(['request_detail_id']);
            $table->dropForeign(['repair_id']);
            $table->dropForeign(['bio_service_docs_id']);

            $table->dropColumn([
                'user_id',
                'date',
                'control_no',
                'location',
                'desc_equ_accessor_id',
                'request_detail_id',
                'repair_id',
                'bio_service_docs_id',
                'request_complaints',
                'job_report',
            ]);
        });

        Schema::dropIfExists('biomedical_service_docs');
        Schema::dropIfExists('check_verify_bies');
        Schema::dropIfExists('repairs');
        Schema::dropIfExists('request_details');
        Schema::dropIfExists('desc_equ_accessories');
    }
};
