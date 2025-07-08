<?php
// Get credentials
require '/var/credentials/zendesk.php';

// Parse the query string into an associative array
$queryParams = [];
parse_str($_SERVER["QUERY_STRING"], $queryParams);

// Extract parameters into variables
$called = isset($queryParams['caller']) ? $queryParams['caller'] : '';

// Search Query
$query = 'phone:'.$called;
//echo $query;

// Search Url
$url = "https://{$subdomain}.zendesk.com/api/v2/users/search.json?query=" . urlencode($query);


//-----------------------------------------
// API get end-user data. or create new one
//-----------------------------------------
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
    //echo "No users found. Creating new user...\n";

    // Prepare user creation
    $newUserRole = 'end-user';
    $createUrl = "https://{$subdomain}.zendesk.com/api/v2/users.json";
    $userData = [
        "user" => [
            "name" => $called,
            "phone" => $called,
            "role" => $newUserRole,
        ]
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $createUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERPWD, "{$email}/token:{$apiToken}");
    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($userData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_VERBOSE, false);
    $createResponse = curl_exec($ch);

    if (curl_errno($ch)) {
        //echo 'Create cURL error: ' . curl_error($ch);
    } else {
        $createdUser = json_decode($createResponse, true);
        //echo "User created:\n";
        //echo "ID: " . $createdUser['user']['id'] . "\n";
        //echo "Name: " . $createdUser['user']['name'] . "\n";
        //echo "Email: " . $createdUser['user']['email'] . "\n";
        //echo "Phone: " . $createdUser['user']['phone'] . "\n";
        echo $createdUser['user']['id'];
    }

    curl_close($ch);
} else {
    //echo "Total users found: " . $data['count'] . "\n\n";
    foreach ($data['users'] as $user) {
        //echo "ID: " . $user['id'] . "\n";
        //echo "Name: " . $user['name'] . "\n";
        //echo "Email: " . ($user['email'] ?: '[no email]') . "\n";
        //echo "Phone: " . $user['phone'] . "\n";
        echo $user['id'];
        break;
    }
}
?>
