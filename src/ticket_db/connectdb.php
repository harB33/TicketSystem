<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "staged_db";

if ($conn = mysqli_connect($servername, $username, $password, $dbname)) {
} else {
    die("Connection failed: " . mysqli_connect_error());
}
?>
