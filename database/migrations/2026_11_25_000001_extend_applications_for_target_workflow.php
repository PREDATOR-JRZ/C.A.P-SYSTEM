<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->string('academic_year', 20)->nullable()->after('user_id');
            $table->string('term', 20)->nullable()->after('academic_year');
            $table->string('intake_batch', 50)->nullable()->after('term');

            $table->string('cat_status', 30)->nullable()->after('stage')->index();
            $table->string('cat_reference_no', 100)->nullable()->after('cat_status');
            $table->timestamp('cat_verified_at')->nullable()->after('cat_reference_no');
            $table->foreignId('cat_verified_by')->nullable()->after('cat_verified_at')->constrained('users')->nullOnDelete();

            $table->timestamp('stage_changed_at')->nullable()->after('stage');

            $table->decimal('exam_score', 5, 2)->nullable()->after('exam_result');
            $table->foreignId('exam_recorded_by')->nullable()->after('exam_score')->constrained('users')->nullOnDelete();
            $table->foreignId('exam_released_by')->nullable()->after('exam_result_released_at')->constrained('users')->nullOnDelete();

            $table->text('interview_notes')->nullable()->after('interview_result');
            $table->timestamp('interviewed_at')->nullable()->after('interview_notes');
            $table->foreignId('interviewed_by')->nullable()->after('interviewed_at')->constrained('users')->nullOnDelete();

            $table->foreignId('accepted_by')->nullable()->after('approved_at')->constrained('users')->nullOnDelete();

            $table->timestamp('eligible_for_official_enrollment_at')->nullable()->after('accepted_by');
            $table->foreignId('eligible_by')->nullable()->after('eligible_for_official_enrollment_at')->constrained('users')->nullOnDelete();

            $table->string('prism_status', 30)->default('not_ready')->after('eligible_by')->index();
            $table->string('prism_reference_no', 100)->nullable()->after('prism_status');
            $table->timestamp('prism_encoded_at')->nullable()->after('prism_reference_no');
            $table->foreignId('prism_encoded_by')->nullable()->after('prism_encoded_at')->constrained('users')->nullOnDelete();
            $table->text('prism_notes')->nullable()->after('prism_encoded_by');
            $table->timestamp('officially_enrolled_at')->nullable()->after('prism_notes');

            $table->string('closed_reason', 255)->nullable()->after('officially_enrolled_at');
            $table->timestamp('closed_at')->nullable()->after('closed_reason');
            $table->foreignId('closed_by')->nullable()->after('closed_at')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->dropForeign(['cat_verified_by']);
            $table->dropForeign(['exam_recorded_by']);
            $table->dropForeign(['exam_released_by']);
            $table->dropForeign(['interviewed_by']);
            $table->dropForeign(['accepted_by']);
            $table->dropForeign(['eligible_by']);
            $table->dropForeign(['prism_encoded_by']);
            $table->dropForeign(['closed_by']);
            $table->dropColumn([
                'academic_year', 'term', 'intake_batch',
                'cat_status', 'cat_reference_no', 'cat_verified_at', 'cat_verified_by',
                'stage_changed_at',
                'exam_score', 'exam_recorded_by', 'exam_released_by',
                'interview_notes', 'interviewed_at', 'interviewed_by',
                'accepted_by',
                'eligible_for_official_enrollment_at', 'eligible_by',
                'prism_status', 'prism_reference_no', 'prism_encoded_at', 'prism_encoded_by', 'prism_notes', 'officially_enrolled_at',
                'closed_reason', 'closed_at', 'closed_by',
            ]);
        });
    }
};
