<?php
// Auth dosyasını doğrudan test et
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['CONTENT_TYPE'] = 'application/json';

// Test verisi
$test_data = '{"phone":"5551234567","password":"123456"}';

// Input stream'i simüle et
$stream = fopen('php://temp', 'r+');
fwrite($stream, $test_data);
rewind($stream);

// file_get_contents'i override et
function file_get_contents($filename) {
    global $test_data;
    if ($filename === 'php://input') {
        return $test_data;
    }
    return '';
}

// Auth dosyasını include et
include_once 'api/v1/auth/login.php';
?> 