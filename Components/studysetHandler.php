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

function createStudyset(Studyset $studyset)
{
    try {
        global $databaseConnection;

        $stmt = query("INSERT INTO studysets (userID, name) VALUES (?, ?)");
        $stmt->bind_param("is", $studyset->userID, $studyset->name);
        $stmt->execute();

        $studysetID = $databaseConnection->insert_id;

        $stmt->close();

        return $studysetID;
    } catch (Exception $exception) {
        return "Error: " . $exception->getMessage();
    }
}
?>