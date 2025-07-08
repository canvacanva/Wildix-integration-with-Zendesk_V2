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
$call_id = $args['call_id'] ?? null;
$ticket_id = $args['ticket_id'] ?? null;
$agent_id = $args['agent_id'] ?? null;
$end_user_id = $args['end_user_id'] ?? null;


//set date and time in ISO 8601
date_default_timezone_set('Europe/Rome');
$europeanDateTime = date('c'); // e.g. 2022-04-16T11:15:37+02:00

// Output the date and time
// echo $europeanDateTime;


//-----------------------------------
// API URL Call transfer POST
//-----------------------------------

$url = "https://{$subdomain}.zendesk.com/api/v2/calls/{$call_id}/comments";

// JSON payload
// JSON data to send
$data = json_encode([
"call_fields" => [
            "from_line", "to_line", "call_started_at", "call_ended_at", "direction", "completion_status",
        ],
        "title" => "Chiamata Trasferita ad Agente",
        "display_to_agent" => $agent_id
]);

// Initialize cURL
$ch = curl_init();

// Set cURL options
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json"
]);
curl_setopt($ch, CURLOPT_USERPWD, "{$email}/token:{$apiToken}");
curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");

// Execute cURL and capture response
$response = curl_exec($ch);
echo $response;

// Check for errors
if (curl_errno($ch)) {
    echo "cURL Error: " . curl_error($ch);
} else {
    // Decode and display response
    $responseDecoded = json_decode($response, true);
    //print_r($responseDecoded);
}

// Close cURL
curl_close($ch);

?>
