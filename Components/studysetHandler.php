<?php 
function getStudysets(string $userID){
    $foundStudysets = find("Studysets", "userID", $userID);
    $test = $foundStudysets->fetch_all(MYSQLI_ASSOC);
    return $test;
}
?>