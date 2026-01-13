<?php
require_once __DIR__ . '/../Components/databaseConnection.php';
require_once __DIR__ . '/../Components/userHandler.php';
requireUser();
require_once __DIR__ . '/../Components/studysetHandler.php';

$studysetID = createStudyset(new Studyset($_SESSION['userID'], ""));

$studysetURL = find("studysets", "studysetID", $studysetID)->fetch_assoc()['studysetURL'];

header('Location: ../studyset.php?studyset=' . $studysetURL . "&edit=true");
exit();


?>