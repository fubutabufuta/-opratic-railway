<?php
// API endpointlerini test et

$base_url = 'http://localhost:8000';

$endpoints = [
    '/api/v1/reminders' => 'Reminders',
    '/api/v1/reminders?action=upcoming' => 'Upcoming Renewals',
    '/api/v1/campaigns' => 'Campaigns',
    '/api/v1/sliders' => 'Sliders',
    '/api/v1/vehicles' => 'Vehicles'
];

foreach ($endpoints as $endpoint => $name) {
    echo "\n=== Testing $name ($endpoint) ===\n";
    
    $url = $base_url . $endpoint;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    echo "HTTP Code: $http_code\n";
    if ($error) {
        echo "Error: $error\n";
    } else {
        echo "Response: " . substr($response, 0, 200) . "...\n";
        
        $json = json_decode($response, true);
        if ($json && isset($json['data']) && is_array($json['data'])) {
            echo "Data count: " . count($json['data']) . "\n";
        } elseif ($json && isset($json['message'])) {
            echo "Message: " . $json['message'] . "\n";
        }
    }
}
?> 