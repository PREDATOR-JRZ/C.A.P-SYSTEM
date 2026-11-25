<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->string('requirement_key', 100)->nullable()->after('label')->index();
            $table->unsignedInteger('version')->default(1)->after('requirement_key');
            $table->string('status', 40)->default('uploaded')->after('version')->index();
            $table->text('review_notes')->nullable()->after('status');
            $table->text('student_visible_note')->nullable()->after('review_notes');
            $table->foreignId('reviewed_by')->nullable()->after('student_visible_note')->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
            $table->timestamp('submitted_at')->nullable()->after('reviewed_at');
            $table->string('mime_type', 100)->nullable()->after('original_name');
            $table->unsignedBigInteger('size_bytes')->nullable()->after('mime_type');
            $table->string('checksum', 128)->nullable()->after('size_bytes');
            $table->foreignId('replaces_document_id')->nullable()->after('checksum')->constrained('documents')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropForeign(['reviewed_by']);
            $table->dropForeign(['replaces_document_id']);
            $table->dropColumn([
                'requirement_key', 'version', 'status', 'review_notes', 'student_visible_note',
                'reviewed_by', 'reviewed_at', 'submitted_at', 'mime_type', 'size_bytes', 'checksum', 'replaces_document_id',
            ]);
        });
    }
};
