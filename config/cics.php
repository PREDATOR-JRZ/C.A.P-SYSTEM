<?php

return [
    // "Appointment Schedule has only allotted student slots each day (50 students)"
    'daily_slots'     => 50,
    'booking_window'  => 30,   // how many days ahead a student can book
    'dispose_days'    => 30,   // failed accounts are disposed within 30 days of result release
    'max_fee_batches' => 3,    // Step 9: group into 2-3 batches

    'student_statuses' => ['Freshman', 'Transferee', 'Shifter', 'Irregular'],

    // EDIT to match the real CICS offerings
    'courses' => [
        'BSIT' => 'Bachelor of Science in Information Technology',
        'BSCS' => 'Bachelor of Science in Computer Science',
        'BSIS' => 'Bachelor of Science in Information Systems',
    ],

    // Requirement labels per student status. "Admission Test Result" is the mandatory
    // upload in Step 2; the others are optional uploads and drive the Check List page.
    'requirements' => [
        'Freshman' => [
            'Admission Test Result', 'Form 138 / Report Card', 'Certificate of Good Moral Character',
            'PSA Birth Certificate', '2x2 ID Pictures',
        ],
        'Transferee' => [
            'Admission Test Result', 'Transcript of Records', 'Honorable Dismissal',
            'Certificate of Good Moral Character', 'PSA Birth Certificate', '2x2 ID Pictures',
        ],
        'Shifter' => [
            'Admission Test Result', 'Approved Shifting Form', 'Copy of Grades',
            'Certificate of Good Moral Character', '2x2 ID Pictures',
        ],
        'Irregular' => [
            'Admission Test Result', 'Copy of Grades', 'Study Load / Advising Form',
            'Certificate of Good Moral Character', '2x2 ID Pictures',
        ],
    ],

    'reminders' => [
        'Print this slip and bring it on your appointment date as evidence.',
        'Arrive at least 30 minutes before your scheduled time.',
        'Bring a valid ID and the original copies of your requirements.',
        'This slip is invalid without the reference number and the CICS stamp.',
    ],
];
