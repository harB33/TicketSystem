<?php
// Simple .env loader
function loadEnv($path) {
    if (!file_exists($path)) return;
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        $parts = explode('=', $line, 2);
        if (count($parts) === 2) {
            putenv(trim($parts[0]) . '=' . trim($parts[1]));
        }
    }
}
loadEnv(__DIR__ . '/../../.env');

class DatabaseConfig {
    public static function getServername() { return getenv('DB_SERVERNAME') ?: "staged-db-aiven-staged-udm.k.aivencloud.com"; }
    public static function getUsername() { return getenv('DB_USERNAME') ?: "avnadmin"; }
    public static function getPassword() { return getenv('DB_PASSWORD') ?: ""; }
    public static function getDbname() { return getenv('DB_NAME') ?: "defaultdb"; }
    public static function getPortPrimary() { return getenv('DB_PORT_PRIMARY') ?: 12659; }
    public static function getPortFallback() { return getenv('DB_PORT_FALLBACK') ?: 3307; }
}


function connectToDatabase(): ?mysqli {
    $servername = DatabaseConfig::getServername();
    $username = DatabaseConfig::getUsername();
    $password = DatabaseConfig::getPassword();
    $dbname = DatabaseConfig::getDbname();

    // Try Primary Port
    $conn = mysqli_init();
    mysqli_options($conn, MYSQLI_OPT_CONNECT_TIMEOUT, 5);
    $connected = @mysqli_real_connect($conn, $servername, $username, $password, $dbname, DatabaseConfig::getPortPrimary());
    
    if ($connected && mysqli_ping($conn)) {
        return $conn;
    }

    error_log("Primary connection failed. Trying fallback port.");
    
    // Try Fallback Port
    $conn = mysqli_init();
    mysqli_options($conn, MYSQLI_OPT_CONNECT_TIMEOUT, 5);
    $connected = @mysqli_real_connect($conn, $servername, $username, $password, $dbname, DatabaseConfig::getPortFallback());

    if ($connected && mysqli_ping($conn)) {
        return $conn;
    }

    error_log("FATAL: Could not connect to the database.");
    return null;
}

$conn = connectToDatabase();

if ($conn === null) {
    // We don't use die() here to ensure the rest of the page (including scripts) can still load
    echo "<div style='position: fixed; top: 0; left: 0; width: 100%; z-index: 9999; background: #ef4444; color: white; padding: 1rem; text-align: center; font-weight: bold;'>
            Database Connection Failed. Please check your .env credentials and server status.
          </div>";
}

?>

