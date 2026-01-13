<?php
require 'Components/databaseConnection.php';
require 'Components/userHandler.php';
requireUser();
require 'Components/studysetHandler.php';

$studysetURL = $_GET['studyset'];
$studyset = find('STUDYSETS', 'studysetURL', $studysetURL);


?>