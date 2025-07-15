<?php
// Basit test sunucusu

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$uri = $_SERVER['REQUEST_URI'];
echo json_encode([
    'status' => 'success',
    'message' => 'Simple server is working',
    'uri' => $uri,
    'method' => $_SERVER['REQUEST_METHOD'],
    'timestamp' => date('Y-m-d H:i:s')
]);
?> 