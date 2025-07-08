<?php

// Get credentials
require '/var/credentials/zendesk.php';

// Parse the query string into an associative array
$queryParams = [];
parse_str($_SERVER["QUERY_STRING"], $queryParams);

// Extract parameters into variables
$uid_channel = isset($queryParams['uid_channel']) ? str_replace(' ', '', $queryParams['uid_channel']) : '';
$caller = isset($queryParams['caller']) ? str_replace(' ', '', $queryParams['caller']) : '';
$called = isset($queryParams['called']) ? str_replace(' ', '', $queryParams['called']) : '';
$end_user_id = isset($queryParams['end_user_id']) ? str_replace(' ', '', $queryParams['end_user_id']) : '';
$ticket_id = isset($queryParams['ticket_id']) ? str_replace(' ', '', $queryParams['ticket_id']) : '';
$call_id = isset($queryParams['call_id']) ? str_replace(' ', '', $queryParams['call_id']) : '';

// Check if the parameters are set
if (empty($uid_channel) || empty($uid_channel)) {
    die("Error: Missing ticketId or uniqueId.");
}

//-----------------------------------------
// Star loop (ps aux | grep php to show)
// or tail -f /tmp/loop_get_call_status.log
//-----------------------------------------
$backgroundScript = "php 2_loop_inbound.php --ticket_id=$ticket_id --uid_channel=$uid_channel --caller=$caller --called=$called --end_user_id=$end_user_id --call_id=$call_id >> /tmp/loop_get_call_status.log 2>&1 &";
exec($backgroundScript);

?>
