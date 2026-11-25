<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\FeeClearance;
use App\Services\ApplicationWorkflow;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FeeClearanceController extends Controller
{
    public function __construct(private readonly ApplicationWorkflow $workflow)
    {
    }

    public function submitProof(Request $request, FeeClearance $feeClearance)
    {
        $application = auth()->user()->application()->firstOrFail();
        abort_unless($feeClearance->application_id === $application->id, 403);

        $data = $request->validate([
            'payment_method' => ['nullable', 'string', 'max:100'],
            'reference_no' => ['nullable', 'string', 'max:100'],
            'proof' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($request->hasFile('proof')) {
            $file = $request->file('proof');
            $data['proof_path'] = $file->store("fee-proofs/{$application->id}", 'local');
            $data['proof_original_name'] = $file->getClientOriginalName();
        }

        $this->workflow->submitFeeProof($application, $feeClearance, auth()->user(), $data);

        return back()->with('success', 'Fee proof submitted for staff verification.');
    }
}
