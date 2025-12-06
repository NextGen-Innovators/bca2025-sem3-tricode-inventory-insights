<?php
// includes/config.php
date_default_timezone_set('Asia/Kathmandu');

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "wastewise_nepal";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Function to sanitize input
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}
?>