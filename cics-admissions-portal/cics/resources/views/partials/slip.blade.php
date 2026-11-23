{{-- Inline styles only, so the same markup works in the email and the printable page. --}}
@php
    $app = $appointment->application;
    $profile = $app->user->profile;
    $requirements = config('cics.requirements.' . $profile->student_status);
@endphp
<div style="font-family:Arial,Helvetica,sans-serif;max-width:640px;margin:0 auto;border:2px solid #0b2a5b;padding:24px;color:#101828">
    <table width="100%" style="border-collapse:collapse"><tr>
        <td><h2 style="margin:0;color:#0b2a5b">CICS — {{ $appointment->label }} Slip</h2>
            <div style="color:#667085;font-size:13px">College of Information and Computing Sciences</div></td>
        <td align="right">
            {{-- STAMP --}}
            <div style="display:inline-block;border:3px double #0b2a5b;color:#0b2a5b;padding:8px 14px;transform:rotate(-6deg);font-weight:bold;text-align:center;font-size:13px">
                CICS OFFICIAL<br>RECEIVED<br>{{ $appointment->created_at->format('M d, Y') }}
            </div>
        </td></tr></table>

    <div style="margin:18px 0;padding:12px;background:#eceef1;text-align:center">
        <div style="font-size:12px;color:#667085">REFERENCE NUMBER</div>
        <div style="font-size:26px;letter-spacing:2px;font-weight:bold">{{ $appointment->reference_no }}</div>
    </div>

    <table width="100%" cellpadding="4" style="font-size:14px">
        <tr><td width="35%"><strong>Student</strong></td><td>{{ $profile->full_name }}</td></tr>
        <tr><td><strong>Status / Course</strong></td><td>{{ $profile->student_status }} · {{ $profile->course_applied }}</td></tr>
        <tr><td><strong>Appointment date</strong></td><td>{{ $appointment->appointment_date->format('l, F j, Y') }}</td></tr>
        <tr><td><strong>Slot number</strong></td><td>#{{ $appointment->slot_number }} of {{ config('cics.daily_slots') }}</td></tr>
    </table>

    <h3 style="color:#0b2a5b;margin-bottom:4px">Requirements to bring</h3>
    <ul style="margin-top:0">@foreach ($requirements as $r)<li>{{ $r }}</li>@endforeach</ul>

    <h3 style="color:#0b2a5b;margin-bottom:4px">Reminders</h3>
    <ul style="margin-top:0">@foreach (config('cics.reminders') as $r)<li>{{ $r }}</li>@endforeach</ul>
</div>
