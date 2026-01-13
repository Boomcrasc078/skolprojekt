<?php
require 'Components/databaseConnection.php';
require 'Components/userHandler.php';
requireUser();
require 'Components/studysetHandler.php';

$studysetURL = $_GET['studyset'];
$studyset = find('studysets', 'studysetURL', $studysetURL)->fetch_assoc();
deleteStudyset($studyset['studysetID']);
header("Location: index.php");
exit();

?>