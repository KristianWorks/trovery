<?php
require_once '../includes/db.php';
header('Content-Type: application/json');

$email = trim($_POST['email'] ?? '');

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Please enter a valid email address.']);
    exit;
}

$esc = $conn->real_escape_string($email);
$conn->query("INSERT IGNORE INTO newsletter_subscribers (email) VALUES ('$esc')");

echo json_encode(['success' => true, 'message' => "You're subscribed!"]);
