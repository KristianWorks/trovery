<?php
require_once __DIR__ . '/config.php';

$host = 'localhost';
$user = 'root';
$pass = '';
$name = 'trovery_db';

try {
    $conn = new mysqli($host, $user, $pass, $name);
} catch (mysqli_sql_exception $e) {
    http_response_code(500);
    die('<div style="font-family:sans-serif;padding:2rem;color:#721c24;background:#f8d7da;border:1px solid #f5c6cb;border-radius:8px;max-width:500px;margin:2rem auto;">
        <strong>Database connection failed.</strong><br>
        Make sure XAMPP is running and the database <code>trovery_db</code> exists.<br>
        Run <a href="/trovery/setup.php">setup.php</a> to create the database.
    </div>');
}

$conn->set_charset('utf8mb4');
