<!DOCTYPE html>
<html><body style="background:#f6f7fb;padding:16px">
<p style="font-family:Arial,sans-serif">Hello {{ $appointment->application->user->name }}, your appointment is confirmed. Please <strong>print this slip</strong> and bring it as evidence.</p>
@include('partials.slip', ['appointment' => $appointment])
</body></html>
