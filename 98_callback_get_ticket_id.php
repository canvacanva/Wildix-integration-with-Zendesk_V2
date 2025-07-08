<?php
// Get credentials
require '/var/credentials/zendesk.php';

$queryParams = [];
parse_str($_SERVER["QUERY_STRING"], $queryParams);

// Extract parameters into variables
$caller = isset($queryParams['caller']) ? $queryParams['caller'] : '';
$userId = '';

// Search Query
$query = 'phone:' . $caller;
$url = "https://{$subdomain}.zendesk.com/api/v2/users/search.json?query=" . urlencode($query);

// Initialize cURL session to search for user
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERPWD, "{$email}/token:{$apiToken}");
curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
curl_setopt($ch, CURLOPT_VERBOSE, false);

$response = curl_exec($ch);
curl_close($ch);

if (curl_errno($ch)) {
    echo 'cURL error: ' . curl_error($ch);
    exit;
}

$data = json_decode($response, true);

// API
if ($data['count'] == 0) {
    echo "No user found with phone number {$caller}.\n";
    exit;
}

$userId = $data['users'][0]['id'];

// Fetch tickets requested by the user
$ticketsUrl = "https://{$subdomain}.zendesk.com/api/v2/users/{$userId}/tickets/requested.json";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $ticketsUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERPWD, "{$email}/token:{$apiToken}");
curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_VERBOSE, false);

$ticketsResponse = curl_exec($ch);
curl_close($ch);

$ticketsData = json_decode($ticketsResponse, true);

// Sort and display latest ticket
if (!empty($ticketsData['tickets'])) {
    usort($ticketsData['tickets'], function ($a, $b) {
        return strtotime($b['created_at']) - strtotime($a['created_at']);
    });

    $latestTicket = $ticketsData['tickets'][0];
    echo $latestTicket['id'];
    echo $latestTicket[''];

} else {
    echo "No tickets found for user ID {$userId}.\n";
}
?>
