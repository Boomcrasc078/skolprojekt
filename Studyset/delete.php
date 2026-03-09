<?php
require_once __DIR__ . '/../Components/databaseConnection.php';
require_once __DIR__ . '/../Components/userHandler.php';
requireUser();
require_once __DIR__ . '/../Components/studysetHandler.php';

$studysetURL = $_GET['studyset'];
$studyset = find('studysets', 'studysetURL', $studysetURL)->fetch_assoc();
deleteStudyset($studyset['studysetID']);
header("Location: ../index.php");
exit();

?>