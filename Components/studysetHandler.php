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

function createStudyset(Studyset $studyset)
{
    try {
        global $databaseConnection;
        $url = createNewURL();
        $stmt = query("INSERT INTO studysets (userID, name, studysetURL) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $studyset->userID, $studyset->name, $url);
        $stmt->execute();

        $studysetID = $databaseConnection->insert_id;

        $stmt->close();

        return $studysetID;
    } catch (Exception $exception) {
        return "Error: " . $exception->getMessage();
    }
}

// function deleteStudyset(int $studysetID){
//     try{
//         $stmt = query("")
//     }catch(Exception $exception){
//         return "Error: " . $exception->getMessage();
//     }
// }
?>