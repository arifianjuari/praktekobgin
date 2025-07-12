<?php
// Production environment configuration

return [
    // Base URL untuk production (sesuaikan dengan domain Anda)
    'base_url' => 'https://yourdomain.com',

    // Database configuration
    'db' => [
        'host' => 'localhost',
        'name' => 'u1234567_dbname', // Ganti sesuai database Hostinger
        'user' => 'u1234567_user',   // Ganti sesuai user Hostinger
        'pass' => 'your_password',    // Ganti dengan password yang aman
        'charset' => 'utf8mb4'
    ],

    // Upload configuration
    'upload_path' => $_SERVER['DOCUMENT_ROOT'] . '/uploads',
    'upload_url' => '/uploads',

    // Image configuration
    'image' => [
        'max_size' => 2 * 1024 * 1024, // 2MB
        'allowed_types' => ['image/jpeg', 'image/png', 'image/gif'],
        'max_dimension' => 800,
        'quality' => 85,
        'output_format' => 'jpg'
    ],

    // Security configuration
    'security' => [
        'csrf_protection' => true,
        'session_timeout' => 172800, // 48 hours
        'password_algo' => PASSWORD_ARGON2ID,
        'password_options' => [
            'memory_cost' => 65536,
            'time_cost' => 4,
            'threads' => 3
        ]
    ],

    // Error reporting (minimal untuk production)
    'error_reporting' => E_ALL & ~E_DEPRECATED & ~E_STRICT,
    'display_errors' => false,
    'log_errors' => true,
    'error_log' => $_SERVER['DOCUMENT_ROOT'] . '/../logs/error.log'
];
