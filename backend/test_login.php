<?php
// Login API test dosyası
$url = 'http://127.0.0.1:8000/api/v1/auth/login.php';

$data = array(
    'phone' => '+905551234567',
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
    echo "API çağrısı başarısız\n";
} else {
    echo "API Yanıtı:\n";
    echo $result . "\n";
}
