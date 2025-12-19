<?php
require 'Components/databaseConnection.php';
require 'Components/userHandler.php';
requireUser();
require 'Components/studysetHandler.php';

$studysetID = createStudyset(new Studyset($_SESSION['userID'], "New Studyset"));

$studysetURL = find("studysets", "studysetID", $studysetID);

header("Location: studyset.php?studyset='$studysetURL'");
exit();

?>