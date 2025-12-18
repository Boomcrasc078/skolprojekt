<?php 
function getStudysets(string $userID){
    $foundStudysets = find("Studysets", "userID", $userID);
    $studysets = $foundStudysets->fetch_all(MYSQLI_ASSOC);
    return $studysets;
}
?>