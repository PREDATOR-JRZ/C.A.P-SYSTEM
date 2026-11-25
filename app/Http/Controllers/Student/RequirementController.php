<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Services\ApplicationWorkflow;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class RequirementController extends Controller
{
    public function __construct(private readonly ApplicationWorkflow $workflow)
    {
    }

    public function create()
    {
        $application = auth()->user()->application()->with('documents')->firstOrFail();
        $status = auth()->user()->profile->student_status;

        return view('student.requirements', [
            'application'  => $application,
            'requirements' => config("cics.requirements.{$status}", []),
            'uploaded'     => $application->documents->sortByDesc('id')->unique('label')->keyBy('label'),
            'canUpload'    => in_array($application->stage, ['eligible', 'requirements_returned'], true),
            'canSubmit'    => in_array($application->stage, ['eligible', 'requirements_returned'], true),
        ]);
    }

    public function store(Request $request)
    {
        $application = auth()->user()->application()->load('documents');
        abort_unless(in_array($application->stage, ['eligible', 'requirements_returned'], true), 403, 'Requirements cannot be uploaded at this stage.');

        $request->validate([
            'files.*' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ], [
            'files.*.max'   => 'Each file must be 5 MB or smaller.',
            'files.*.mimes' => 'Files must be PDF, JPG or PNG.',
        ]);

        $allowed = config('cics.requirements.' . auth()->user()->profile->student_status, []);

        foreach ((array) $request->file('files') as $label => $file) {
            if (! $file || ! in_array($label, $allowed, true)) {
                continue;
            }

            $latest = $application->documents()->where('label', $label)->latest('id')->first();
            if ($latest && ! $latest->canBeReplacedByStudent()) {
                continue;
            }

            $path = $file->store("requirements/{$application->id}", 'local');
            $application->documents()->create([
                'label' => $label,
                'requirement_key' => str($label)->slug('_')->toString(),
                'version' => $latest ? $latest->version + 1 : 1,
                'status' => Document::STATUS_UPLOADED,
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getClientMimeType(),
                'size_bytes' => $file->getSize(),
                'checksum' => hash_file('sha256', $file->getRealPath()),
                'replaces_document_id' => $latest?->id,
            ]);
        }

        return back()->with('success', 'Files uploaded. Review your uploads and click Submit for Review when ready.');
    }

    public function submit(Request $request)
    {
        $application = auth()->user()->application()->firstOrFail();
        $this->workflow->submitRequirements($application, auth()->user());

        return redirect()->route('student.dashboard')
            ->with('success', 'Requirements submitted for manual college review.');
    }

    public function show(Document $document)
    {
        $owner = $document->application->user_id === auth()->id();
        abort_unless($owner || auth()->user()->isAdmin(), 403);

        return Storage::disk('local')->response($document->path, $document->original_name);
    }
}
