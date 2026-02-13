<?php
// termsHandler.php
// Functions to load and save terms for a studyset (JSON file + DB)

function resolve_path_terms($p)
{
    if (!$p)
        return null;
    if (preg_match('#^(?:[A-Za-z]:|\\\\|/)#', $p))
        return $p;
    return __DIR__ . DIRECTORY_SEPARATOR . ltrim($p, "\\/\"");
}

function get_terms_and_file($studyset)
{
    $terms = [];
    $termsFile = null;

    if (!empty($studyset['terms'])) {
        $raw = $studyset['terms'];
        $trim = trim($raw);
        if (($trim !== '') && (isset($trim[0]) && ($trim[0] === '{' || $trim[0] === '['))) {
            $decoded = json_decode($raw, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded))
                $terms = $decoded;
        } else {
            $path = resolve_path_terms($raw);
            if ($path && file_exists($path)) {
                $termsFile = $path;
                $content = file_get_contents($termsFile);
                $decoded = json_decode($content, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded))
                    $terms = $decoded;
            } else {
                // Do NOT create files or directories on disk. If the referenced file
                // does not exist, return empty terms and no file path.
                $termsFile = null;
                $terms = [];
            }
        }
    } else {
        $name = !empty($studyset['name']) ? $studyset['name'] : 'studyset';
        $slug = preg_replace('/[^a-z0-9]+/i', '_', strtolower($name));
        $path = __DIR__ . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . "Studyset" . DIRECTORY_SEPARATOR . "terms_{$slug}.json";
        // Do NOT create a default file on disk. Return empty terms and no file path.
        $termsFile = null;
        $terms = [];
    }

    return ['terms' => $terms, 'file' => $termsFile];
}

function save_terms($studyset, $termsArray, $name = null, $description = null)
{
    $json = json_encode($termsArray, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    if ($json === false) {
        return ['path' => null, 'error' => 'json_encode failed: ' . json_last_error_msg()];
    }

    try {
        require_once __DIR__ . '/databaseConnection.php';
        global $databaseConnection;
        $id = (int) ($studyset['studysetID'] ?? $studyset['id'] ?? 0);
        if ($id <= 0) {
            return ['path' => null, 'error' => 'Missing studyset ID'];
        }

        if ($name === null)
            $name = $studyset['name'] ?? '';
        if ($description === null)
            $description = $studyset['description'] ?? '';

        $stmt = $databaseConnection->prepare("UPDATE studysets SET terms = ?, name = ?, description = ? WHERE studysetID = ?");
        if ($stmt === false) {
            return ['path' => null, 'error' => 'DB prepare failed: ' . $databaseConnection->error];
        }
        if (!$stmt->bind_param('sssi', $json, $name, $description, $id)) {
            $err = $stmt->error;
            $stmt->close();
            return ['path' => null, 'error' => 'DB bind failed: ' . $err];
        }
        if (!$stmt->execute()) {
            $err = $stmt->error;
            $stmt->close();
            return ['path' => null, 'error' => 'DB execute failed: ' . $err];
        }
        $stmt->close();
    } catch (Exception $e) {
        return ['path' => null, 'error' => $e->getMessage()];
    }

    return ['path' => null, 'error' => null];
}

?>