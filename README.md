# CICS Admissions Portal

Laravel application for the CICS admissions workflow (student registration, requirements, exam/interview scheduling, results, and batching).

## Requirements

- PHP 8.3+
- Composer
- Node.js & npm (for frontend assets)
- A database supported by Laravel (MySQL, MariaDB, PostgreSQL, SQLite, etc.)

## Setup

1. Install PHP dependencies:
   ```bash
   composer install
   ```
2. Create your environment file and app key (if needed):
   ```bash
   copy .env.example .env   # Windows
   php artisan key:generate
   ```
3. Configure `.env` with your database and mail settings (use Mailpit/Mailtrap for local testing).
4. Create the notifications table, run migrations, and seed the default admin account:
   ```bash
   php artisan make:notifications-table
   php artisan migrate
   php artisan db:seed --class=AdminSeeder
   ```
   Default admin: `admin@cics.test` / `ChangeMe123!` (change it immediately).
5. Install frontend dependencies and build assets:
   ```bash
   npm install
   npm run build
   ```
6. Start the application:
   ```bash
   php artisan serve
   ```

### Scheduler (Step 6: dispose failed accounts)

Add a cron/scheduled task to run Laravel's scheduler every minute:

```bash
php /path/to/artisan schedule:run >> /dev/null 2>&1
```

The app schedules `applications:dispose-failed` daily (see [routes/console.php](routes/console.php)). You can also run it manually:

```bash
php artisan applications:dispose-failed
```

## Project notes

- Role-based access is registered in [bootstrap/app.php](bootstrap/app.php) via the `role` middleware alias.
- Admission rules and configurable values live in [config/cics.php](config/cics.php) (courses, requirements per status, reminders, slot count, batch count, retention days).
- Uploaded requirement files are stored privately under `storage/app/private/requirements/...` and served only to the owner or an admin.

## Workflow map

| Step | Description | Where |
| --- | --- | --- |
| 1 | Register student account (personal info, status, contacts) | [app/Http/Controllers/Auth/RegisterController.php](app/Http/Controllers/Auth/RegisterController.php), [resources/views/auth/register.blade.php](resources/views/auth/register.blade.php) |
| 2 | Upload admission test result and requirements | [app/Http/Controllers/Student/RequirementController.php](app/Http/Controllers/Student/RequirementController.php), [resources/views/student/requirements.blade.php](resources/views/student/requirements.blade.php) |
| 3 | Schedule CICS exam (slots/day configurable) | [app/Http/Controllers/Student/AppointmentController.php](app/Http/Controllers/Student/AppointmentController.php), [app/Services/SlotService.php](app/Services/SlotService.php) |
| 4 | Confirmation slip with reference number, requirements, reminders | [app/Mail/AppointmentConfirmation.php](app/Mail/AppointmentConfirmation.php), [resources/views/partials/slip.blade.php](resources/views/partials/slip.blade.php) |
| 5 | Redirect to student dashboard | [app/Http/Controllers/Student/AppointmentController.php](app/Http/Controllers/Student/AppointmentController.php) |
| 6 | Record/release exam results; dispose failed accounts after retention | [app/Http/Controllers/Admin/ApplicationController.php](app/Http/Controllers/Admin/ApplicationController.php), [app/Console/Commands/DisposeFailedAccounts.php](app/Console/Commands/DisposeFailedAccounts.php) |
| 7 | Schedule interview (passed applicants) | [app/Http/Controllers/Student/AppointmentController.php](app/Http/Controllers/Student/AppointmentController.php) |
| 8 | Notify applicant of approval/status | [app/Http/Controllers/Admin/ApplicationController.php](app/Http/Controllers/Admin/ApplicationController.php), [app/Notifications/StatusUpdated.php](app/Notifications/StatusUpdated.php) |
| 9 | Group approved applicants into fee batches | [app/Http/Controllers/Admin/ApplicationController.php](app/Http/Controllers/Admin/ApplicationController.php) |

## Stage flow (`applications.stage`)

`registered -> requirements_submitted -> exam_scheduled -> exam_passed | exam_failed`  
`exam_passed -> interview_scheduled -> approved | not_approved -> batched`

Each controller checks the current stage before allowing an action so steps cannot be skipped.

## Customization

- [config/cics.php](config/cics.php): courses, requirement lists per status, reminders, slot count, batch count, retention days.
- [resources/views/student/about.blade.php](resources/views/student/about.blade.php): replace placeholders with official CICS history and course descriptions.
- [resources/views/partials/slip.blade.php](resources/views/partials/slip.blade.php): swap the CSS-drawn stamp for an official image if needed.
- Mail is sent synchronously by default; switch to queued mail if you run a queue worker.
