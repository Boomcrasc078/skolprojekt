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
                if ($path) {
                    $dir = dirname($path);
                    if (!is_dir($dir))
                        mkdir($dir, 0755, true);
                    file_put_contents($path, json_encode([], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                    $termsFile = $path;
                    $terms = [];
                }
            }
        }
    } else {
        $name = !empty($studyset['name']) ? $studyset['name'] : 'studyset';
        $slug = preg_replace('/[^a-z0-9]+/i', '_', strtolower($name));
        $path = __DIR__ . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . "Studyset" . DIRECTORY_SEPARATOR . "terms_{$slug}.json";
        if (!file_exists($path))
            file_put_contents($path, json_encode([], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $termsFile = $path;
        $terms = [];
    }

    return ['terms' => $terms, 'file' => $termsFile];
}

function save_terms($studyset, $termsArray, $name = null, $description = null)
{
    $json = json_encode($termsArray, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

    // determine file path to save
    $path = null;
    if (!empty($studyset['terms'])) {
        $raw = $studyset['terms'];
        $trim = trim($raw);
        if (!($trim !== '' && (isset($trim[0]) && ($trim[0] === '{' || $trim[0] === '[')))) {
            $p = resolve_path_terms($raw);
            if ($p)
                $path = $p;
        }
    }

    if ($path === null) {
        $sname = !empty($studyset['name']) ? $studyset['name'] : ($name ?? 'studyset');
        $slug = preg_replace('/[^a-z0-9]+/i', '_', strtolower($sname));
        $path = __DIR__ . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . "Studyset" . DIRECTORY_SEPARATOR . "terms_{$slug}.json";
    }

    // ensure directory
    $dir = dirname($path);
    if (!is_dir($dir))
        mkdir($dir, 0755, true);

    $written = @file_put_contents($path, $json);
    if ($written === false) {
        return ['path' => $path, 'error' => 'Failed to write terms file'];
    }

    // update DB
    try {
        require_once __DIR__ . '/databaseConnection.php';
        global $databaseConnection;
        $id = (int) ($studyset['studysetID'] ?? $studyset['id'] ?? 0);
        if ($id > 0) {
            // prepare query
            if ($name === null)
                $name = $studyset['name'] ?? '';
            if ($description === null)
                $description = $studyset['description'] ?? '';
            $stmt = query("UPDATE studysets SET terms = ?, name = ?, description = ? WHERE studysetID = ?");
            if ($stmt === false) {
                return ['path' => $path, 'error' => 'DB prepare failed: ' . $databaseConnection->error];
            }
            if (!$stmt->bind_param('sssi', $json, $name, $description, $id)) {
                $err = $stmt->error;
                $stmt->close();
                return ['path' => $path, 'error' => 'DB bind failed: ' . $err];
            }
            if (!$stmt->execute()) {
                $err = $stmt->error;
                $stmt->close();
                return ['path' => $path, 'error' => 'DB execute failed: ' . $err];
            }
            $stmt->close();
        }
    } catch (Exception $e) {
        return ['path' => $path, 'error' => $e->getMessage()];
    }

    return ['path' => $path, 'error' => null];
}

?>