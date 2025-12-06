<?php
session_start();
date_default_timezone_set('Asia/Kathmandu');

$conn = new mysqli('localhost', 'root', '', 'wastewise_nepal');

if($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>