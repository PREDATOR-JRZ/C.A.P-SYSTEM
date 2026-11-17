# CICS Admissions Portal — Laravel (Blade + PHP)

Implements the workflow from `LOGIC_PLAT-TECH_SYSTEM.docx`.

## Install (Laravel 11/12, PHP 8.2+)

```bash
composer create-project laravel/laravel cics-admissions
cd cics-admissions
# copy the contents of this folder over the fresh project (app/, config/, database/, resources/, routes/)
```

1. **Register the role middleware alias** in `bootstrap/app.php`:
   ```php
   ->withMiddleware(function (Middleware $middleware) {
       $middleware->alias(['role' => \App\Http\Middleware\RoleMiddleware::class]);
   })
   ```
2. Configure `.env`: database, and `MAIL_*` (use Mailtrap/Mailpit for testing).
3. Create the notifications table, migrate and seed the admin:
   ```bash
   php artisan make:notifications-table      # Laravel 11+ (older: notifications:table)
   php artisan migrate
   php artisan db:seed --class=AdminSeeder   # admin@cics.test / ChangeMe123!  (change it!)
   php artisan serve
   ```
4. Scheduler for the 30-day disposal (Step 6): add a cron entry
   `* * * * * php /path/to/artisan schedule:run >> /dev/null 2>&1`
   (or run `php artisan applications:dispose-failed` manually).

Uploaded files are stored privately (`storage/app/private/requirements/...`) and served only to the owner or an admin.

## Workflow → code map

| Doc step | Where |
|---|---|
| 1. Register student account (personal info, status, contacts) | `Auth/RegisterController`, `auth/register.blade.php` |
| 2. Upload Admission Test Result + requirements | `Student/RequirementController`, `student/requirements.blade.php` |
| 3. Schedule CICS Exam (50 slots/day) | `Student/AppointmentController`, `Services/SlotService` |
| 4. Email stamp, reference no., requirements, reminders | `Mail/AppointmentConfirmation`, `partials/slip.blade.php` (also printable on the dashboard) |
| 5. Redirect to dashboard | `AppointmentController@store` → `student.dashboard` |
| 6. Check pass/fail; failed disposed in 30 days | `Admin\ApplicationController@examResult / releaseResults`, `Console/Commands/DisposeFailedAccounts` |
| 7. Schedule Interview & requirements (passed only) | `AppointmentController` (type `interview`) |
| 8. Account notified of approval | `Admin\ApplicationController@interviewResult` + `Notifications/StatusUpdated` (dashboard + email) |
| 9. Group into 2–3 fee batches | `Admin\ApplicationController@generateBatches` (admin dashboard) |
| Student account pages | Dashboard, Personal Information, Status, Check List, Announcements, About CICS |

## Stage flow (`applications.stage`)

`registered → requirements_submitted → exam_scheduled → exam_passed | exam_failed`
`exam_passed → interview_scheduled → approved | not_approved → batched`

Each controller checks the stage before allowing an action, so students can't skip steps.

## Things to customize

- `config/cics.php`: courses, requirement lists per status, reminders, slot count, batch count, retention days.
- `resources/views/student/about.blade.php`: add the real CICS history and course descriptions (placeholders left in).
- The stamp in `partials/slip.blade.php` is CSS-drawn; swap in an image if the school has an official stamp.
- Mail is sent synchronously; switch to queued mail if you have a queue worker.

> Note: this code was written without a PHP runtime available to lint or run it, so do a quick smoke test (register → upload → book → release results → interview → batch) after installing.
