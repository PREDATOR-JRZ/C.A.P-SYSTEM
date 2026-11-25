<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('fee_clearances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained()->cascadeOnDelete();
            $table->string('fee_type', 50);
            $table->string('label', 100);
            $table->decimal('amount', 10, 2)->nullable();
            $table->string('currency', 10)->default('PHP');
            $table->string('status', 40)->default('unpaid')->index();
            $table->string('payment_method', 100)->nullable();
            $table->string('reference_no', 100)->nullable();
            $table->foreignId('proof_document_id')->nullable()->constrained('documents')->nullOnDelete();
            $table->string('proof_path')->nullable();
            $table->string('proof_original_name')->nullable();
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('waived_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('waived_at')->nullable();
            $table->string('waiver_reason', 255)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['application_id', 'fee_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fee_clearances');
    }
};
