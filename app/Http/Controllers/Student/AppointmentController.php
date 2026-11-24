<?php

namespace App\Http\Controllers\Student;

use App\Exceptions\SlotFullException;
use App\Http\Controllers\Controller;
use App\Mail\AppointmentConfirmation;
use App\Models\Appointment;
use App\Models\Application;
use App\Services\SlotService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

/**
 * STEP 3 — schedule the CICS Exam.
 * STEP 4 — system emails the stamp / reference no. / requirements / reminders.
 * STEP 7 — schedule the Interview & passing of requirements (only if exam passed).
 */
class AppointmentController extends Controller
{
    /** The stage a student must be in to book each appointment type. */
    private const REQUIRED_STAGE = [
        Appointment::EXAM      => 'requirements_submitted',
        Appointment::INTERVIEW => 'exam_passed',
    ];

    private const NEXT_STAGE = [
        Appointment::EXAM      => 'exam_scheduled',
        Appointment::INTERVIEW => 'interview_scheduled',
    ];

    public function create(string $type, SlotService $slots)
    {
        $application = $this->resolve($type);
        if ($application instanceof \Illuminate\Http\RedirectResponse) {
            return $application;
        }

        return view('student.appointments.create', [
            'type'        => $type,
            'title'       => $type === Appointment::EXAM ? 'Schedule your CICS Exam' : 'Schedule your Interview & Submission of Requirements',
            'days'        => $slots->availableDays($type),
            'application' => $application,
        ]);
    }

    public function store(Request $request, string $type, SlotService $slots)
    {
        $application = $this->resolve($type);
        if ($application instanceof \Illuminate\Http\RedirectResponse) {
            return $application;
        }

        $request->validate(['appointment_date' => ['required', 'date_format:Y-m-d', 'after:today', function ($attr, $value, $fail) {
            if (now()->parse($value)->isWeekend()) {
                $fail('Appointments are only available on weekdays.');
            }
            if (now()->parse($value)->gt(now()->addDays(config('cics.booking_window')))) {
                $fail('That date is too far ahead.');
            }
        }]]);

        try {
            $appointment = $slots->book($application, $type, $request->appointment_date);
        } catch (SlotFullException $e) {
            return back()->withErrors(['appointment_date' => $e->getMessage()]);
        }

        $application->update(['stage' => self::NEXT_STAGE[$type]]);

        $message = 'Appointment booked! Reference no: ' . $appointment->reference_no . '.';
        try {
            Mail::to(auth()->user())->send(new AppointmentConfirmation($appointment));
            $message .= ' A softcopy of your slip was sent to ' . auth()->user()->email . '.';
        } catch (\Throwable $e) {
            report($e);
            $message .= ' We could not send the email right now — you can print the slip from your dashboard.';
        }

        // STEP 5 — redirect to dashboard
        return redirect()->route('student.dashboard')->with('success', $message);
    }

    /** Printable slip (same content as the email). */
    public function slip(Appointment $appointment)
    {
        abort_unless($appointment->application->user_id === auth()->id(), 403);

        return view('student.appointments.slip', compact('appointment'));
    }

    private function resolve(string $type): Application|\Illuminate\Http\RedirectResponse
    {
        abort_unless(in_array($type, [Appointment::EXAM, Appointment::INTERVIEW], true), 404);

        $application = auth()->user()->application;

        if ($application->appointmentFor($type)) {
            return redirect()->route('student.dashboard')->with('error', 'You already have this appointment.');
        }
        if ($application->stage !== self::REQUIRED_STAGE[$type]) {
            return redirect()->route('student.dashboard')->with('error', 'You cannot book this appointment yet.');
        }

        return $application;
    }
}
