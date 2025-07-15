<?php
echo "🔍 LOGIN API TEST\n";

// Test data
$testData = [
    'phone' => '+905551234567',
    'password' => '123'
];

// Create POST data
$postData = json_encode($testData);

// Create context for POST request
$context = stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => "Content-Type: application/json\r\n",
        'content' => $postData
    ]
]);

echo "📤 Request Data:\n";
echo $postData . "\n\n";

// Make request
try {
    $response = file_get_contents('http://127.0.0.1:8000/api/v1/auth/login.php', false, $context);

    echo "📥 Response:\n";
    echo $response . "\n\n";

    // Try to decode JSON
    $responseData = json_decode($response, true);

    if ($responseData) {
        echo "✅ Valid JSON Response:\n";
        foreach ($responseData as $key => $value) {
            $type = gettype($value);
            $displayValue = is_array($value) ? '[Array]' : (string)$value;
            echo "  $key ($type): $displayValue\n";
        }

        // Check for null values
        echo "\n🔍 Null Value Check:\n";
        $hasNulls = false;
        foreach ($responseData as $key => $value) {
            if ($value === null) {
                echo "  ❌ NULL found in: $key\n";
                $hasNulls = true;
            }
        }

        if (!$hasNulls) {
            echo "  ✅ No null values found\n";
        }
    } else {
        echo "❌ Invalid JSON Response\n";
        echo "JSON Error: " . json_last_error_msg() . "\n";
    }
} catch (Exception $e) {
    echo "❌ Request failed: " . $e->getMessage() . "\n";
}
