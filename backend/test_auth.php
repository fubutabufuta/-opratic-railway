<?php
// Test auth endpoint
$url = 'http://192.168.1.159:8001/api/v1/auth/login';
$data = array(
    'phone' => '5551234567',
    'password' => '123456'
);

$options = array(
    'http' => array(
        'header'  => "Content-type: application/json\r\n",
        'method'  => 'POST',
        'content' => json_encode($data)
    )
);

$context  = stream_context_create($options);
$result = file_get_contents($url, false, $context);

if ($result === FALSE) {
    echo "Hata: API'ye bağlanılamadı\n";
    print_r($http_response_header);
} else {
    echo "Başarılı yanıt:\n";
    echo $result . "\n";
}
?> 