<?php

return [
    'storage_disk' => 'kkprl_private',

    'proposal_access' => [
        'idle_timeout_seconds' => 1_800,
        'resume_attempts' => 5,
        'resume_decay_seconds' => 900,
        'resume_ip_attempts' => 30,
        'resume_ip_decay_seconds' => 900,
    ],
    'pdf_renderer' => [
        'binary' => env('KKPRL_PDF_RENDERER_BINARY', 'pdftoppm'),
        'format' => env('KKPRL_PDF_RENDERER_FORMAT', 'png'),
        'prepend_arguments' => [],
        'resolution_dpi' => 144,
        'timeout_seconds' => 60,
    ],
];
