<?php
//sleep(1); // Simulate processing delay

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
$wildix_mail = $args['wildix_mail'] ?? null;

//set date and time in ISO 8601
date_default_timezone_set('Europe/Rome');
$europeanDateTime = date('c'); // e.g. 2022-04-16T11:15:37+02:00

// Search Query
$query = 'type:user role:agent email:'.$wildix_mail;
$query = 'type:user email:'.$wildix_mail;
//echo $query;

//-----------------------------------------
// Search zendesk_id user based by mailbox
//-----------------------------------------
$url = "https://{$subdomain}.zendesk.com/api/v2/users/search.json?query=" . urlencode($query);

// Initialize cURL session
$ch = curl_init();

// Set cURL options
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERPWD, "{$email}/token:{$apiToken}");
curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
curl_setopt($ch, CURLOPT_VERBOSE, false); // For debugging

// Execute the request
$response = curl_exec($ch);

// Check for errors
if (curl_errno($ch)) {
    echo 'cURL error: ' . curl_error($ch);
} else {
    // Decode and print the response
    $data = json_decode($response, true);
    //print_r($data);
}

// Check if users were found
if ($data['count'] == 0) {
    echo "No users found.\n";
} else {
    //echo "Total users found: " . $data['count'] . "\n\n";
    foreach ($data['users'] as $user) {
        //echo "ID: " . $user['id'] . "\n";
        echo $user['id'];
        break;
    }
}

?>
