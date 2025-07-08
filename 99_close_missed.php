<?php
// Get credentials
require '/var/credentials/zendesk.php';
sleep(2);

// Parse CLI arguments into key-value pairs
$args = [];
foreach ($argv as $arg) {
    if (preg_match('/--([^=]+)=(.*)/', $arg, $matches)) {
        $args[$matches[1]] = $matches[2];
    }
}

// Assign variables with defaults if not provided
$caller = $args['caller'] ?? null;
$called = $args['called'] ?? null;
$agent_id = $args['agent_id'] ?? null;
$end_user_id = $args['end_user_id'] ?? null;

// Ensure both numbers start with '+'
if ($caller !== '' && $caller[0] !== '+') {
    $caller = '+' . $caller;
}
if ($called !== '' && $called[0] !== '+') {
    $called = '+' . $called;
}

//set date and time in ISO 8601
date_default_timezone_set('Europe/Rome');
$europeanDateTime = date('c'); // e.g. 2022-04-16T11:15:37+02:00


//---------------------------
// API URL close call, set call as missed
//---------------------------
$url = "https://{$subdomain}.zendesk.com/api/v2/calls";

// JSON data to send
$data = json_encode([

        "call" => [
        "app_id" => $app_id,
        "agent_id"=> $agent_id,
        "end_user_id" => $end_user_id,
        "from_line" => $caller,
        "to_line" => $called,
        "call_started_at" => $europeanDateTime,
        //"call_ended_at" => $europeanDateTime,
        "direction" => "inbound",
        "completion_status" => "missed",
        
    ],
    "comment" => [
        "title" => "Chiamata PERSA",
        "call_fields" => [
            "from_line", "to_line", "call_started_at", "call_ended_at", "direction", "completion_status",
        ],
        "author_id" => $end_user_id,
        "subject" => "Ticket generato da chiamata in ingresso - PERSA",
    ]
]);

// Initialize cURL
$ch = curl_init();

// Set cURL options
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json"
]);
curl_setopt($ch, CURLOPT_USERPWD, "$email/token:$apiToken");
curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");

// Execute cURL and capture response
$response = curl_exec($ch);
//echo $response;

preg_match('/\{.*\}/', $response, $matches);

if (!empty($matches)) {
    $json = $matches[0];
    $data = json_decode($json, true);

    if (isset($data['call']['ticket_id'])) {
        echo $data['call']['ticket_id'];
        echo "|";
        echo $data['call']['id'];
        $ticket_id = $data['call']['ticket_id'];
    } else {
        echo "ticket_id not found in the JSON.";
    }
} else {
    echo "No JSON found in the input string.";
}


// Close cURL
curl_close($ch);

//---------------------------
// API URL add tag to ticket
//---------------------------
$url = "https://{$subdomain}.zendesk.com/api/v2/tickets/{$ticket_id}/tags.json";

// JSON data to send
$data = json_encode([

        "tags" => [
            "inbound_missed_call_wildix",
        ],

]);

// Initialize cURL
$ch = curl_init();

// Set cURL options
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json"
]);
curl_setopt($ch, CURLOPT_USERPWD, "$email/token:$apiToken");
curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");

// Execute cURL and capture response
$response = curl_exec($ch);
//echo $response;

// Close cURL
curl_close($ch);

?>