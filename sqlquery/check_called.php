<?php
// Import credentials securely
require '/var/credentials/db_config.php';

// Define Input variables
// Parse the query string into an associative array
$queryParams = [];
parse_str($_SERVER["QUERY_STRING"], $queryParams);

// Extract parameters into variables
$caller = isset($queryParams['caller']) ? $queryParams['caller'] : '';
$timeout = isset($queryParams['timeout']) ? $queryParams['timeout'] : '';
  
// Establish connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    echo "Warning: Database connection failed: " . $conn->connect_error;
    print_r("no_calls_found");
    exit(); // Gracefully exit without terminating the script abruptly
}

// Sanitize the input
$caller = $conn->real_escape_string($caller);

// Dynamically construct the query, searching for the tag
//$query = "SELECT * FROM cdr.cdr where disposition = 'NO ANSWER' and c_to like '%$caller'  and start between now() - interval $timeout minute and now() order by rowid desc limit 1";
$query = "SELECT * FROM cdr.cdr where c_to like '%$caller'  and start between now() - interval $timeout minute and now() order by rowid desc limit 1";

// Execute the query
$result = $conn->query($query);
if ($result->num_rows > 0) {
    // Fetch the first row of the result
    $row = $result->fetch_assoc();

    // Save each column into a variable
    $src = $row['src']; // internal user that call

// Close the connection
$conn->close();
print_r($src);

} else {
    print_r("no_calls_found");
}
// Close the connection
$conn->close();

?>
