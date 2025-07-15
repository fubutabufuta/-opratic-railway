<?php
// API debug
$url = "http://localhost:8001/api/v1/sliders/";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_VERBOSE, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "URL: $url\n";
echo "HTTP Code: $httpCode\n";
echo "Response Length: " . strlen($response) . "\n";
echo "Response:\n";
echo $response . "\n";
?> 