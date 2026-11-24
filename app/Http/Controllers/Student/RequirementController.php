<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/** STEP 2 — Upload Admission Test Result and other requirements. */
class RequirementController extends Controller
{
    private const ADMISSION = 'Admission Test Result';

    public function create()
    {
        $application = auth()->user()->application()->with('documents')->firstOrFail();
        $status = auth()->user()->profile->student_status;

        return view('student.requirements', [
            'application'  => $application,
            'requirements' => config("cics.requirements.{$status}"),
            'uploaded'     => $application->documents->keyBy('label'),
        ]);
    }

    public function store(Request $request)
    {
        $application = auth()->user()->application;
        $already = $application->documents()->where('label', self::ADMISSION)->exists();

        $rules = ['files.*' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120']];
        $rules['files.' . self::ADMISSION] = [$already ? 'nullable' : 'required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'];

        $request->validate($rules, [
            'files.*.max'   => 'Each file must be 5 MB or smaller.',
            'files.*.mimes' => 'Files must be PDF, JPG or PNG.',
        ]);

        $allowed = config('cics.requirements.' . auth()->user()->profile->student_status);

        foreach ((array) $request->file('files') as $label => $file) {
            // only accept labels that belong to this student's requirement list
            if (! $file || ! in_array($label, $allowed, true)) {
                continue;
            }

            // replace any earlier upload for the same requirement
            $application->documents()->where('label', $label)->get()->each(function (Document $old) {
                Storage::disk('local')->delete($old->path);
                $old->delete();
            });

            $application->documents()->create([
                'label'         => $label,
                'path'          => $file->store("requirements/{$application->id}", 'local'),
                'original_name' => $file->getClientOriginalName(),
            ]);
        }

        if ($application->stage === 'registered' && $application->documents()->where('label', self::ADMISSION)->exists()) {
            $application->update(['stage' => 'requirements_submitted']);

            // Step 3 comes next
            return redirect()->route('student.appointments.create', 'exam')
                ->with('success', 'Requirements uploaded. Now pick a date for your CICS Exam.');
        }

        return back()->with('success', 'Files uploaded.');
    }

    /** Owner (or admin) can view an uploaded file. */
    public function show(Document $document)
    {
        $owner = $document->application->user_id === auth()->id();
        abort_unless($owner || auth()->user()->isAdmin(), 403);

        return Storage::disk('local')->response($document->path, $document->original_name);
    }
}
