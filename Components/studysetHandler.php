<?php

class Studyset
{
    public int $userID;
    public string $name;

    function __construct(int $userID, string $name)
    {
        $this->userID = $userID;
        $this->name = $name;
    }
}

function getStudysets(int $userID)
{
    $foundStudysets = find("Studysets", "userID", $userID);
    $studysets = $foundStudysets->fetch_all(MYSQLI_ASSOC);
    return $studysets;
}

function generateRandomString($length = 5)
{
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';

    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[random_int(0, $charactersLength - 1)];
    }

    return $randomString;
}

function createNewURL()
{
    try {
        global $databaseConnection;

        while (true) {
            $url = generateRandomString();

            $foundStudysets = find("studysets", "studysetURL", $url);
            $studysets = $foundStudysets->fetch_all();

            if (count($studysets) == 0) {
                return $url;
            }
        }

    } catch (Exception $exception) {
        return "Error: " . $exception->getMessage();
    }
}

function hasStudysetField(string $field): bool
{
    try {
        static $fieldCache = [];
        if (array_key_exists($field, $fieldCache)) {
            return $fieldCache[$field];
        }

        $stmt = prepareQuery('SHOW COLUMNS FROM studysets LIKE ?');
        $stmt->bind_param('s', $field);
        $stmt->execute();
        $result = $stmt->get_result();
        $exists = $result->num_rows > 0;
        $stmt->close();

        return $fieldCache[$field] = $exists;
    } catch (Exception $exception) {
        return false;
    }
}

function getStudysetModeField(string $mode): ?string
{
    switch ($mode) {
        case 'flashcards':
            return 'enableFlashcards';
        case 'quiz':
            return 'enableQuiz';
        case 'write':
            return 'enableWrite';
        default:
            return null;
    }
}

function isModeEnabled(array $studyset, string $mode): bool
{
    $field = getStudysetModeField($mode);
    if ($field === null) {
        return false;
    }

    if (!array_key_exists($field, $studyset)) {
        return true;
    }

    return !empty($studyset[$field]);
}

function getEnabledStudysetModes(array $studyset): array
{
    $modes = [];

    foreach (['flashcards', 'quiz', 'write'] as $mode) {
        if (isModeEnabled($studyset, $mode)) {
            $modes[] = $mode;
        }
    }

    return $modes;
}

function createStudyset(Studyset $studyset)
{
    try {
        global $databaseConnection;
        $url = createNewURL();
        $fieldNames = ['userID', 'name', 'studysetURL'];
        $placeholders = ['?', '?', '?'];
        $types = 'iss';
        $values = [$studyset->userID, $studyset->name, $url];

        $modeFields = ['enableFlashcards', 'enableQuiz', 'enableWrite'];
        foreach ($modeFields as $field) {
            if (hasStudysetField($field)) {
                $fieldNames[] = $field;
                $placeholders[] = '?';
                $types .= 'i';
                $values[] = 1;
            }
        }

        $query = 'INSERT INTO studysets (' . implode(', ', $fieldNames) . ') VALUES (' . implode(', ', $placeholders) . ')';
        $stmt = prepareQuery($query);
        $stmt->bind_param($types, ...$values);
        $stmt->execute();

        $studysetID = $databaseConnection->insert_id;
        $stmt->close();

        return $studysetID;
    } catch (Exception $exception) {
        return 'Error: ' . $exception->getMessage();
    }
}

function saveStudysetModes(int $studysetID, array $modes)
{
    $modeFields = ['enableFlashcards', 'enableQuiz', 'enableWrite'];
    $available = [];
    foreach ($modeFields as $field) {
        if (hasStudysetField($field)) {
            $available[] = $field;
        }
    }

    if (empty($available)) {
        return true;
    }

    $setParts = [];
    foreach ($available as $field) {
        $setParts[] = "$field = ?";
    }

    $query = 'UPDATE studysets SET ' . implode(', ', $setParts) . ' WHERE studysetID = ?';
    $stmt = prepareQuery($query);

    $types = str_repeat('i', count($available)) . 'i';
    $values = [];
    foreach ($available as $field) {
        $values[] = isset($modes[$field]) ? (int) $modes[$field] : 0;
    }
    $values[] = $studysetID;

    $stmt->bind_param($types, ...$values);
    $stmt->execute();
    $stmt->close();

    return true;
}

function deleteStudyset(int $studysetID)
{
    try {
        $stmt = prepareQuery("DELETE FROM studysets WHERE studysetID = ?");
        $stmt->bind_param("i", $studysetID);
        $result = $stmt->execute();
        $stmt->close();

        return $result;
    } catch (Exception $exception) {
        return "Error: " . $exception->getMessage();
    }
}
?>