<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('student_profiles', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $t->string('first_name');
            $t->string('middle_name')->nullable();
            $t->string('last_name');
            $t->date('birthdate');
            $t->string('sex', 10);
            $t->string('contact_number', 30);
            $t->string('address');
            $t->string('student_status');              // Freshman | Transferee | Shifter | Irregular
            $t->string('course_applied', 20);
            $t->string('previous_school')->nullable();  // Transferee / Shifter / Irregular
            $t->string('guardian_name')->nullable();
            $t->string('guardian_contact', 30)->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_profiles');
    }
};
