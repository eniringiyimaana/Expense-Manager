<?php
$host = "localhost";
$user = "root"; // change if needed
$pass = "";
$dbname = "expense_budget_db";

$conn = new mysqli($host, $user, $pass, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
