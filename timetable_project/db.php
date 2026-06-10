<?php
/**
 * Main application database configuration.
 * Creates the mysqli connection and shared base URL used across the project.
 */
$servername = "localhost";
$username = "root"; // your DB username
$password = "";     // your DB password
$dbname = "timetable_db";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
