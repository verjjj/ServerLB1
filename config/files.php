<?php

return [
    'max_size' => env('MAX_FILE_SIZE', 5 * 1024 * 1024), // 5MB
    'aspect_ratio' => env('ALLOWED_ASPECT_RATIO', '1:1'),
    'avatar_size' => 128,
    'storage' => [
        'disk' => 'private',
        'path' => 'photos'
    ]
]; 