<?php
class DatabaseConfig {
    public const SERVERNAME = "127.0.0.1";
    public const USERNAME = "root";
    public const PASSWORD = "";
    public const DBNAME = "staged_db";
    public const PORT_PRIMARY = 3306;
    public const PORT_FALLBACK = 3307;
}


function connectToDatabase(): ?mysqli {
    $servername = DatabaseConfig::SERVERNAME;
    $username = DatabaseConfig::USERNAME;
    $password = DatabaseConfig::PASSWORD;
    $dbname = DatabaseConfig::DBNAME;

    $conn = @mysqli_connect($servername, $username, $password, $dbname, DatabaseConfig::PORT_PRIMARY);
    if ($conn && mysqli_ping($conn)) {
        return $conn;
    }

    error_log("Primary connection failed. Trying fallback port.");
    $conn = @mysqli_connect($servername, $username, $password, $dbname, DatabaseConfig::PORT_FALLBACK);

    if ($conn && mysqli_ping($conn)) {
        return $conn;
    }

    error_log("FATAL: Could not connect to the database.");
    return null;
}

$conn = connectToDatabase();

if ($conn === null) {
    die("<h1 style='color: red;'>Error</h1><p>Connection failed. Please check server status and configuration.</p>");
}

// Removed premature close to allow use of connection in pages
// mysqli_close($conn); 

?>
