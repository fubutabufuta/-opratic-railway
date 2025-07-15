<?php
// Production Configuration for Oto Asist
return [
    'database' => [
        'host' => $_ENV['DB_HOST'] ?? 'localhost',
        'port' => $_ENV['DB_PORT'] ?? '3306',
        'dbname' => $_ENV['DB_NAME'] ?? 'otoasist',
        'username' => $_ENV['DB_USERNAME'] ?? 'root',
        'password' => $_ENV['DB_PASSWORD'] ?? '',
        'charset' => 'utf8mb4'
    ],

    'app' => [
        'url' => $_ENV['APP_URL'] ?? 'https://yourdomain.com',
        'api_url' => $_ENV['API_URL'] ?? 'https://yourdomain.com/api',
        'debug' => $_ENV['DEBUG_MODE'] ?? false,
        'timezone' => 'Europe/Istanbul'
    ],

    'admin' => [
        'token' => $_ENV['ADMIN_TOKEN'] ?? '+905551234567'
    ],

    'cors' => [
        'allowed_origins' => $_ENV['CORS_ORIGIN'] ?? '*',
        'allowed_methods' => 'GET, POST, PUT, DELETE, OPTIONS',
        'allowed_headers' => 'Content-Type, Authorization, X-Requested-With'
    ]
];
