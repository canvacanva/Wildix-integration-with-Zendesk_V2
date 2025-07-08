<?php
// Load credentials
require '/var/credentials/zendesk.php';

// Get ticket ID from query string
$ticketId = isset($_GET['ticket_id']) ? intval($_GET['ticket_id']) : 0;

if (!$ticketId) {
    echo "Missing or invalid ticket ID.\n";
    exit;
}

// Zendesk API endpoint to get ticket comments
$commentsUrl = "https://{$subdomain}.zendesk.com/api/v2/tickets/{$ticketId}/comments.json";

// Initialize cURL
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $commentsUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERPWD, "{$email}/token:{$apiToken}");
curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_VERBOSE, false);

$response = curl_exec($ch);
curl_close($ch);

if (!$response) {
    echo "Failed to fetch comments.\n";
    exit;
}

$data = json_decode($response, true);
$latestCallId = null;
$latestTimestamp = 0;

// Loop through comments to find the most recent TpeVoiceComment with a call_id
foreach ($data['comments'] as $comment) {
    if (
        isset($comment['type']) &&
        $comment['type'] === 'TpeVoiceComment' &&
        isset($comment['data']['call_id']) &&
        isset($comment['created_at'])
    ) {
        $timestamp = strtotime($comment['created_at']);
        if ($timestamp > $latestTimestamp) {
            $latestTimestamp = $timestamp;
            $latestCallId = $comment['data']['call_id'];
        }
    }
}

if ($latestCallId) {
    echo $latestCallId;
} else {
    echo "No call_id found in comments for ticket {$ticketId}.\n";
}
?>
