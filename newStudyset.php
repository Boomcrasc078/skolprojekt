<?php
require 'Components/databaseConnection.php';
require 'Components/userHandler.php';
requireUser();
require 'Components/studysetHandler.php';

$studysetID = createStudyset(new Studyset($_SESSION['userID'], "New Studyset"));

$studysetURL = find("studysets", "studysetID", $studysetID)->fetch_assoc()['studysetURL'];

<<<<<<< HEAD
header('Location: studyset.php?studyset='.$studysetURL);
=======


header("Location: studyset.php?studyset=" . $studysetURL);
>>>>>>> d0a1c80a0759694f5c6df653e3da27ff99583559
exit();


?>