<?php

return [
    'daily_slots'     => 50,
    'booking_window'  => 30,
    'dispose_days'    => 30,
    'max_fee_batches' => 3,

    'student_statuses' => ['Freshman', 'Transferee', 'Shifter', 'Irregular'],

    'courses' => [
        'BSIT' => 'Bachelor of Science in Information Technology',
        'BSCS' => 'Bachelor of Science in Computer Science',
        'BSIS' => 'Bachelor of Science in Information Systems',
    ],

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

    'fee_clearances' => [
        'department_fee' => ['label' => 'Department Fee', 'required' => true],
        'insurance_fee' => ['label' => 'Insurance Fee', 'required' => true],
    ],

    'reminders' => [
        'Print this slip and bring it on your appointment date as evidence.',
        'Arrive at least 30 minutes before your scheduled time.',
        'Bring a valid ID and the original copies of your requirements.',
        'This slip is invalid without the reference number and the CICS stamp.',
    ],
];
