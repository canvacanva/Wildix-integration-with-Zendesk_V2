<?php

sleep(1); // Simulate processing delay

require '/var/credentials/zendesk.php';

// Parse CLI arguments
$args = [];
foreach ($argv as $arg) {
    if (preg_match('/--([^=]+)=(.*)/', $arg, $matches)) {
        $args[$matches[1]] = $matches[2];
    }
}

$uid_channel = $args['uid_channel'] ?? null;
$caller = $args['caller'] ?? null;
$called = $args['called'] ?? null;
$end_user_id = $args['end_user_id'] ?? null;
$ticket_id = $args['ticket_id'] ?? null;
$call_id = $args['call_id'] ?? null;

//set date and time in ISO 8601
date_default_timezone_set('Europe/Rome');
$europeanDateTime = date('c'); // e.g. 2022-04-16T11:15:37+02:00

if (!isset($wildixdns, $authToken)) {
    die("Error: Missing credentials.\n");
}

$encodedChannel = urlencode($uid_channel);
$url = "https://{$wildixdns}.wildixin.com/api/v1/Calls/{$encodedChannel}";

// Initialize call status
$call_status = "ring";
$answered = false;

echo "$europeanDateTime - $uid_channel - Wait call to been answered. \n";

//-----------------------------------------
// Wait for answer
//-----------------------------------------

do {
    $europeanDateTime = date('c');
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Accept: application/json',
            'Content-Type: application/json',
            "Authorization: Bearer $authToken"
        ]
    ]);

    echo "$europeanDateTime - $uid_channel - CURL api - wait for answer \n";
    $response = curl_exec($ch);

    if ($response === false) {
        echo "cURL Error: " . curl_error($ch) . "\n";
        curl_close($ch);
        break;
    }

    curl_close($ch);

    $decodedResponse = json_decode($response, true);
    if (!$decodedResponse) {
        echo "Error decoding JSON.\n";
        break;
    }

    if (($decodedResponse['type'] ?? '') === 'error') {
        echo "CALL WAS RINGING but API Error: " . ($decodedResponse['reason'] ?? 'Unknown') . "\n";
        break;
    }

    $ConnectedLineNum = $decodedResponse['result']['ConnectedLineNum'] ?? null;

    if (is_numeric($ConnectedLineNum)) {
        $answered = true;
        echo "$europeanDateTime - $uid_channel - Call has been answered.\n";
        break;
    } else {
        echo "$europeanDateTime - $uid_channel - Ticket not yet created, call not answered.\n";
        sleep(1);
    }

} while (true);


// Determine final call status
//$call_status = $answered ? "answer" : "ring";
$call_status = empty($call_id)
    ? ($answered ? "answer" : "ring")
    : "callback";

$europeanDateTime = date('c');
echo "$europeanDateTime - $uid_channel - Call end ring status. \n";

//-----------------------------------------
// If not answered, create ticket, update and exit
//-----------------------------------------

if (!$answered) {
        // Create ticket as missed
        $creationScript = "php 99_close_missed.php --caller=$caller --called=$called --end_user_id=$end_user_id";
        echo "$europeanDateTime - $uid_channel - CURL api - close ticketas missed, call status: $call_status \n";
        exec($creationScript, $outputLines, $exitCode);

        if ($exitCode === 0 && !empty($outputLines)) {
            $parts = explode('|', trim($outputLines[0]));
            $ticket_id = $parts[0] ?? null;
            $call_id = $parts[1] ?? null;

            echo "$europeanDateTime - $uid_channel - From $caller to $called -  Created ticket as MISSED CALL, ID $ticket_id, call ID $call_id , call status: $call_status \n";
        } else {
            echo "$europeanDateTime - $uid_channel - Error creating ticket. Exit code: $exitCode , call status: $call_status \n";
            exit;
        }
        $outputLines = [];
        exit;
}

$europeanDateTime = date('c');
echo "$europeanDateTime - $uid_channel - Call has been answered and line is UP , call status: $call_status\n";
// Initialize previous value
$previousConnectedLineNum = $ConnectedLineNum;

// Get agent info (user that answer)
$search_wildix_mail_php = "php 98_search_wildix_mail.php --wildix_extension=$ConnectedLineNum";
echo "$europeanDateTime - $uid_channel - CURL api - search wildix mail using extension (first run) , call status: $call_status \n";
exec($search_wildix_mail_php, $outputLines, $exitCode);
$wildix_mail = trim(implode("\n", $outputLines));
$outputLines = [];

//Convert mailbox to Zendesk ID
$search_zendesk_mail_php = "php 98_search_zendesk_agent_id.php --wildix_mail=$wildix_mail";
echo "$europeanDateTime - $uid_channel - CURL api - search zendesk id using mailbox , call status: $call_status \n";
exec($search_zendesk_mail_php, $outputLines, $exitCode);
$agent_id = trim(implode("\n", $outputLines));
$outputLines = [];

//-----------------------------------------
// RUNNINGGGGGGGGG
//-----------------------------------------
if (empty($ticket_id)) {
    // Do something if ticket is empty
    echo "No ticket ID provided, create new one , call status: $call_status \n";
        
        // Create ticket as asnwered
        $creationScript = "php 9_create_ticket.php --caller=$caller --called=$called --end_user_id=$end_user_id --agent_id=$agent_id";
        echo "$europeanDateTime - $uid_channel - CURL api - create ticket as answer , call status: $call_status \n";
        exec($creationScript, $outputLines, $exitCode);

        if ($exitCode === 0 && !empty($outputLines)) {
            $parts = explode('|', trim($outputLines[0]));
            $ticket_id = $parts[0] ?? null;
            $call_id = $parts[1] ?? null;

            echo "$europeanDateTime - $uid_channel - Created ticket ID $ticket_id, call ID $call_id , call status: $call_status \n";
        } else {
            echo "$europeanDateTime - $uid_channel - Error creating ticket. Exit code: $exitCode , call status: $call_status \n";
            exit;
        }
        $outputLines = [];
} else {
    // Do something else if ticket is populated
    echo "Ticket ID already exist (callback call), ticket ID: " . $ticket_id ." call ID: $call_id  , call status: $call_status \n";
}


//-----------------------------------------
// Assig ticket to user that answers
//-----------------------------------------
        $assignScript = "php 99_update_ticket.php --ticket_id=$ticket_id --agent_id=$agent_id >> /tmp/loop_get_call_status.log";
        echo "$europeanDateTime - $uid_channel - CURL api - update tiket , call status: $call_status  \n";
        exec($assignScript);
        echo "$europeanDateTime $uid_channel - Answer - Assigned Ticket $ticket_id to $agent_id , call status: $call_status  \n";

//-----------------------------------------
// Monit call during UP
//-----------------------------------------
echo "$europeanDateTime - $uid_channel - Call start monitoring staus , call status: $call_status  \n";
do {
    $europeanDateTime = date('c');
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Accept: application/json',
            'Content-Type: application/json',
            "Authorization: Bearer $authToken"
        ]
    ]);

    $response = curl_exec($ch);
    echo "$europeanDateTime - $uid_channel - CURL api - monitor api call , call status: $call_status  \n";
    curl_close($ch);

    if ($response === false) {
        echo "cURL Error: " . curl_error($ch) . "\n";
        break;
    }

    $decodedResponse = json_decode($response, true);
    if (!$decodedResponse) {
        echo "Error decoding JSON.\n";
        break;
    }

    if (($decodedResponse['type'] ?? '') === 'error') {
        echo "CALL WAS UP but API Error: " . ($decodedResponse['reason'] ?? 'Unknown') . "\n";
        break;
    }

    $ConnectedLineNum = $decodedResponse['result']['ConnectedLineNum'] ?? null;
    echo "$europeanDateTime - $uid_channel - ConnectedLineNum active: " . ($ConnectedLineNum ?? 'Unknown') . "\n";


//-----------------------------------------
// ConnectedLineNum is numeric, means call is still UP and talinkg
//-----------------------------------------
if (is_numeric($ConnectedLineNum)) {
    // Check if the number has changed
    if ($ConnectedLineNum !== $previousConnectedLineNum) {
        echo "$europeanDateTime - $uid_channel - ConnectedLineNum changed: $previousConnectedLineNum → $ConnectedLineNum\n";
        $previousConnectedLineNum = $ConnectedLineNum;

        // Reconfirm agent
        $search_wildix_mail_php = "php 98_search_wildix_mail.php --wildix_extension=$ConnectedLineNum";
        echo "$europeanDateTime - $uid_channel - CURL api - search wildix mail using extension (update process) , call status: $call_status  \n";
        exec($search_wildix_mail_php, $outputLines, $exitCode);
        $wildix_mail = trim(implode("\n", $outputLines));
        echo "$europeanDateTime - $uid_channel - Agent mail: $wildix_mail , call status: $call_status \n";
        $outputLines = [];

        $search_zendesk_mail_php = "php 98_search_zendesk_agent_id.php --wildix_mail=$wildix_mail";
        echo "$europeanDateTime - $uid_channel - CURL api - search zendesk id using mailbox (update process) , call status: $call_status  \n";
        exec($search_zendesk_mail_php, $outputLines, $exitCode);
        $agent_id = trim(implode("\n", $outputLines));
        $outputLines = [];

        // Assign ticket
        $assignScript = "php 99_update_ticket.php --ticket_id=$ticket_id --agent_id=$agent_id >> /tmp/loop_get_call_status.log";
        echo "$europeanDateTime - $uid_channel - CURL api - ticket update (update process) , call status: $call_status  \n";
        exec($assignScript);
        echo "$europeanDateTime $uid_channel -  Transfer - Assigned Ticket $ticket_id to $agent_id , call status: $call_status \n";

        // Update ticket for new assigned 
        $creationScript = "php 99_update_transfer.php --caller=$caller --called=$called --call_id=$call_id --end_user_id=$end_user_id --agent_id=$agent_id";
        echo "$europeanDateTime - $uid_channel - CURL api - ticket transfered , call status: $call_status  \n";
        exec($creationScript, $outputLines, $exitCode);

        if ($exitCode === 0 && !empty($outputLines)) {
            $parts = explode('|', trim($outputLines[0]));

            echo "$europeanDateTime - $uid_channel - Update ticket ID $ticket_id, call ID $call_id , call status: $call_status \n";
        } else {
            echo "$europeanDateTime - $uid_channel - Error creating ticket. Exit code: $exitCode , call status: $call_status \n";
            exit;
        }
        $outputLines = [];
        
    }
}


    sleep(1);
} while (is_numeric($ConnectedLineNum));

//-----------------------------------------
// Status for call ended
//-----------------------------------------
switch ($call_status) {
    case "answer":
        echo "Call_ID: " . $call_id ."\n";
        echo "Ticket_ID: " . $ticket_id."\n";
        $answerScript = "php 99_close_answered.php --call_id=$call_id --ticket_id=$ticket_id >> /tmp/loop_get_call_status.log";
        echo "$europeanDateTime - $uid_channel - CURL api - ticked open as call answered and complete , call status: $call_status  \n";
        exec($answerScript, $outputLines, $exitCode);
        echo "$europeanDateTime - $uid_channel - STOP monitoring. Call ended. Call was answered , call status: $call_status \n";
        $outputLines = [];
        break;
    case "ring":
        echo "$europeanDateTime - $uid_channel - NOT USED STOP monitoring. Call was missed , call status: $call_status \n";
        break;
    case "transfer":
        echo "$europeanDateTime - $uid_channel - STOP monitoring. Call was transferred , call status: $call_status \n";
        break;
    case "callback":
        echo "Callback Call_ID: " . $call_id ."\n";
        echo "Callback Ticket_ID: " . $ticket_id."\n";
        $answerScript = "php 99_close_callback.php --call_id=$call_id --ticket_id=$ticket_id >> /tmp/loop_get_call_status.log";
        echo "$europeanDateTime - $uid_channel - CURL api - ticked open as call callbacked and complete , call status: $call_status  \n";
        exec($answerScript, $outputLines, $exitCode);
        echo "$europeanDateTime - $uid_channel - STOP monitoring. Call ended. Call was callbacked , call status: $call_status \n";
        $outputLines = [];
        break;
    default:
        echo "$europeanDateTime - $uid_channel - STOP monitoring. Unknown call status: $call_status\n";
        break;
}

?>
