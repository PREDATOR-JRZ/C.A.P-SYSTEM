<?php

namespace App\Console\Commands;

use App\Models\Application;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/** Step 6: accounts that FAILED are disposed within 30 days of result release. */
class DisposeFailedAccounts extends Command
{
    protected $signature = 'applications:dispose-failed';
    protected $description = 'Delete student accounts that failed the exam and passed their 30-day retention period';

    public function handle(): int
    {
        $count = 0;

        Application::with(['user', 'documents'])
            ->where('stage', 'exam_failed')
            ->whereNotNull('dispose_at')
            ->where('dispose_at', '<=', now())
            ->each(function (Application $application) use (&$count) {
                foreach ($application->documents as $doc) {
                    Storage::disk('local')->delete($doc->path);
                }
                $application->user->notifications()->delete();
                // cascades to profile, application, documents, appointments
                $application->user->delete();
                $count++;
            });

        $this->info("Disposed {$count} account(s).");

        return self::SUCCESS;
    }
}
