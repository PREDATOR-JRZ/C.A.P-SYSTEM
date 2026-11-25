<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Application;

class DashboardController extends Controller
{
    public function index()
    {
        return view('admin.dashboard', [
            'counts'         => Application::selectRaw('stage, count(*) as total')->groupBy('stage')->pluck('total', 'stage'),
            'readyToRelease' => Application::where('stage', 'exam_scheduled')->whereNotNull('exam_result')->count(),
            'awaitingReview' => Application::whereIn('stage', ['requirements_submitted', 'requirements_in_review'])->count(),
            'awaitingClearance' => Application::where('stage', 'accepted_for_clearance')->count(),
            'awaitingPrism'  => Application::whereIn('stage', ['eligible_for_official_enrollment', 'prism_pending'])->count(),
            'todayExam'      => Appointment::where('type', 'exam')->whereDate('appointment_date', today())->count(),
            'todayInterview' => Appointment::where('type', 'interview')->whereDate('appointment_date', today())->count(),
            'slots'          => config('cics.daily_slots'),
            'maxBatches'     => config('cics.max_fee_batches'),
        ]);
    }
}
