<?php
require_once __DIR__ . '/databaseConnection.php';

// this endpoint is called via fetch() from the flashcards page. it stores
// whether the user knew the current card in the `userData` column of the users
// table (which is kept as a JSON blob). the structure looks like:
// {
//     "flashcards": {
//         "<studysetId>": {
//             "<cardIndex>": {"known":true, "ts":1234567890},
//             ...
//         },
//         ...
//     }
// }

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('method not allowed');
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$studysetId = $input['studysetId'] ?? null;
$cardIndex = isset($input['cardIndex']) ? $input['cardIndex'] : null;
$isKnown = isset($input['isKnown']) ? (bool) $input['isKnown'] : null;

if ($studysetId === null || $cardIndex === null || $isKnown === null) {
    http_response_code(400);
    exit('invalid payload');
}

$userId = $_SESSION['userID'] ?? null;
if (!$userId) {
    http_response_code(403);
    exit('not logged in');
}

// read existing data blob from user row
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

if (!isset($userData['flashcards']) || !is_array($userData['flashcards'])) {
    $userData['flashcards'] = [];
}
if (!isset($userData['flashcards'][(string) $studysetId]) || !is_array($userData['flashcards'][(string) $studysetId])) {
    $userData['flashcards'][(string) $studysetId] = [];
}

$userData['flashcards'][(string) $studysetId][(string) $cardIndex] = [
    'known' => $isKnown,
    'ts' => time()
];

$json = json_encode($userData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
if ($json === false) {
    http_response_code(500);
    exit('json_encode failed: ' . json_last_error_msg());
}

$stmt = prepareQuery('UPDATE users SET userData = ? WHERE userID = ?');
$stmt->bind_param('si', $json, $userId);
$stmt->execute();

if ($stmt->affected_rows === 0) {
    http_response_code(500);
    exit('update failed');
}

$stmt->close();

// no output needed; we can send a quick success code
http_response_code(204);
