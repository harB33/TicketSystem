<?php
class DatabaseConfig {
    public static function getServername() { return getenv('DB_SERVERNAME') ?: "staged-db-aiven-staged-udm.k.aivencloud.com"; }
    public static function getUsername() { return getenv('DB_USERNAME') ?: "avnadmin"; }
    public static function getPassword() { return getenv('DB_PASSWORD') ?: ""; } // REMOVED SECRET
    public static function getDbname() { return getenv('DB_NAME') ?: "defaultdb"; }
    public static function getPortPrimary() { return getenv('DB_PORT_PRIMARY') ?: 12659; }
    public static function getPortFallback() { return getenv('DB_PORT_FALLBACK') ?: 3307; }
}


function connectToDatabase(): ?mysqli {
    $servername = DatabaseConfig::getServername();
    $username = DatabaseConfig::getUsername();
    $password = DatabaseConfig::getPassword();
    $dbname = DatabaseConfig::getDbname();

    $conn = @mysqli_connect($servername, $username, $password, $dbname, DatabaseConfig::getPortPrimary());
    if ($conn && mysqli_ping($conn)) {
        return $conn;
    }

    error_log("Primary connection failed. Trying fallback port.");
    $conn = @mysqli_connect($servername, $username, $password, $dbname, DatabaseConfig::getPortFallback());

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

?>
