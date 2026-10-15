<?php
// db.php — shared MySQLi connection (kept for backward compatibility).
// Existing files use `$conn`. New code also uses `$conn`.
// Credentials: env vars / config.mail.php override these defaults.
require_once __DIR__ . '/config.php';

$host     = env_or('DB_HOST', 'localhost');
$username = env_or('DB_USER', 'root');
$password = env_or('DB_PASS', 'root');
$database = env_or('DB_NAME', 'portfolio_db');

// Create connection using MySQLi
$conn = new mysqli($host, $username, $password, $database);

// Check connection (fail softly if DB missing so install.php can help)
if ($conn->connect_error) {
    // If the database itself is missing, try creating the server link
    // without a DB so install.php can bootstrap. Otherwise die clearly.
    http_response_code(500);
    die("Database Connection Failed: " . htmlspecialchars($conn->connect_error)
      . "<br>Tip: run <a href='install.php'>install.php</a> to create the database and tables.");
}

// Set charset to UTF-8 (full unicode)
$conn->set_charset("utf8mb4");
