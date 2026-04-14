<?php
require_once __DIR__ . '/../databaseConnection.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('method not allowed');
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$studysetId = $input['studysetId'] ?? null;

if ($studysetId === null) {
    http_response_code(400);
    exit('invalid payload');
}

$userId = $_SESSION['userID'] ?? null;
if (!$userId) {
    http_response_code(403);
    exit('not logged in');
}

$stmt = prepareQuery('SELECT userData FROM users WHERE userID = ?');
$stmt->bind_param('i', $userId);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$stmt->close();

if (!$row) {
    http_response_code(404);
    exit('user not found');
}

$userData = json_decode($row['userData'] ?? '{}', true);
if (!is_array($userData)) {
    $userData = [];
}

if (isset($userData['flashcards'])) {
    unset($userData['flashcards'][(string) $studysetId]);
}

$json = json_encode($userData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
if ($json === false) {
    http_response_code(500);
    exit('json_encode failed: ' . json_last_error_msg());
}

$stmt = prepareQuery('UPDATE users SET userData = ? WHERE userID = ?');
$stmt->bind_param('si', $json, $userId);
$stmt->execute();
$stmt->close();

// ← removed the affected_rows check: 0 rows changed just means there was
//   no progress to clear, which is still a valid successful restart
http_response_code(204);