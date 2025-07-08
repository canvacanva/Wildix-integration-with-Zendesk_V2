<?php
// Get credentials
require '/var/credentials/zendesk.php';

// Parse CLI arguments into key-value pairs
$args = [];
foreach ($argv as $arg) {
    if (preg_match('/--([^=]+)=(.*)/', $arg, $matches)) {
        $args[$matches[1]] = $matches[2];
    }
}

// Assign variables with defaults if not provided
$wildix_extension = $args['wildix_extension'] ?? null;

// Set date and time in ISO 8601
date_default_timezone_set('Europe/Rome');
$europeanDateTime = date('c'); // e.g. 2022-04-16T11:15:37+02:00

//-----------------------------------------
// Search mailbox user based on extension
//-----------------------------------------
$url = "https://{$wildixdns}.wildixin.com/api/v1/Colleagues/";

// Initialize cURL session
$ch = curl_init();

// Set cURL options using Bearer Token authentication
curl_setopt_array($ch, [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Accept: application/json',
        'Content-Type: application/json',
        "Authorization: Bearer $authToken"
    ]
]);

// Execute cURL request
$response = curl_exec($ch);

if ($response === false) {
    echo "cURL Error: " . curl_error($ch);
} else {
    $decodedResponse = json_decode($response, true);
    
    if ($decodedResponse === null) {
        echo "Error decoding JSON.";
    } else {
        if (isset($decodedResponse['result']['records']) && is_array($decodedResponse['result']['records'])) {
            $records = $decodedResponse['result']['records'];
            $email = null;

            foreach ($records as $record) {
                if (isset($record['extension']) && $record['extension'] === $wildix_extension) {
                    $email = $record['email'];
                    break;
                }
            }

            echo $email;
        } else {
            echo "No records found in the response.";
        }
    }
}

// Close cURL session
curl_close($ch);
?>
