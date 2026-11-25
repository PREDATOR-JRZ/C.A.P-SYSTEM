<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('student_profiles', function (Blueprint $table) {
            $table->string('academic_year', 20)->nullable()->after('course_applied');
            $table->string('term', 20)->nullable()->after('academic_year');
            $table->string('cat_reference_no', 100)->nullable()->after('term');
            $table->timestamp('cat_passed_at')->nullable()->after('cat_reference_no');
        });

        DB::table('applications')->where('stage', 'registered')->update(['stage' => 'eligible', 'stage_changed_at' => now()]);
        DB::table('applications')->where('stage', 'approved')->update(['stage' => 'accepted_for_clearance', 'stage_changed_at' => now()]);
        DB::table('applications')->where('stage', 'batched')->update([
            'stage' => 'eligible_for_official_enrollment',
            'eligible_for_official_enrollment_at' => now(),
            'prism_status' => 'pending',
            'stage_changed_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::table('student_profiles', function (Blueprint $table) {
            $table->dropColumn(['academic_year', 'term', 'cat_reference_no', 'cat_passed_at']);
        });

        DB::table('applications')->where('stage', 'eligible')->update(['stage' => 'registered']);
        DB::table('applications')->where('stage', 'accepted_for_clearance')->update(['stage' => 'approved']);
        DB::table('applications')->where('stage', 'eligible_for_official_enrollment')->whereNull('officially_enrolled_at')->update(['stage' => 'batched']);
    }
};
