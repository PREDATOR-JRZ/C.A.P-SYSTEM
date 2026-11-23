<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('applications', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $t->string('stage')->default('registered')->index();

            // Exam (Steps 5-6). exam_result is entered by admin and stays hidden from the
            // student until exam_result_released_at is filled in.
            $t->string('exam_result')->nullable();            // passed | failed
            $t->timestamp('exam_result_released_at')->nullable();
            $t->timestamp('dispose_at')->nullable();          // failed => released_at + 30 days

            // Interview (Steps 7-8)
            $t->string('interview_result')->nullable();       // approved | not_approved
            $t->timestamp('approved_at')->nullable();

            // Fees (Step 9)
            $t->unsignedTinyInteger('fee_batch')->nullable();
            $t->date('fee_payment_date')->nullable();

            $t->timestamps();
        });

        Schema::create('documents', function (Blueprint $t) {
            $t->id();
            $t->foreignId('application_id')->constrained()->cascadeOnDelete();
            $t->string('label');            // e.g. "Admission Test Result"
            $t->string('path');
            $t->string('original_name');
            $t->timestamps();
        });

        Schema::create('appointments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('application_id')->constrained()->cascadeOnDelete();
            $t->string('type', 20);                         // exam | interview
            $t->date('appointment_date');
            $t->unsignedSmallInteger('slot_number');
            $t->string('reference_no')->nullable()->unique();
            $t->timestamps();

            $t->unique(['application_id', 'type']);                    // one per type
            $t->unique(['type', 'appointment_date', 'slot_number']);   // no double-booked slot
        });

        Schema::create('announcements', function (Blueprint $t) {
            $t->id();
            $t->string('title');
            $t->text('body');
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('announcements');
        Schema::dropIfExists('appointments');
        Schema::dropIfExists('documents');
        Schema::dropIfExists('applications');
    }
};
