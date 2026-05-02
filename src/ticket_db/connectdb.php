<?php
$servername = "127.0.0.1";
$username = "root";
$password = "";
$dbname = "staged_db";
$port = 3306;
$alternative_port = 3307;

try {
    $conn = mysqli_connect($servername, $username, $password, $dbname, $port);
} catch (Exception $e) {
    $conn = mysqli_connect($servername, $username, $password, $dbname, $alternative_port);
}

?>