<?php

return [
    'name' => 'CimsSignature',

    // SigCaptX WebSocket URL (default localhost)
    'sigcaptx_url' => env('SIGCAPTX_URL', 'ws://localhost:8000'),

    // Signature image storage path
    'storage_path' => 'signatures',
];
