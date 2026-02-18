<?php
function getTerms($studyset)
{
    try{
        $terms = [];

        $decoded = json_decode($studyset['terms'], true);

        $terms = $decoded;
        return $terms;
    }catch(Exception $e){
        return $e->getMessage();
    }
}

function save_terms($studyset, $termsArray, $name = null, $description = null)
{
    $json = json_encode($termsArray, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    if ($json === false) {
        return ['path' => null, 'error' => 'json_encode failed: ' . json_last_error_msg()];
    }

    try {
        $id = $studyset['studysetID'];

        if ($name === null)
            $name = $studyset['name'] ?? '';
        if ($description === null)
            $description = $studyset['description'] ?? '';

        $stmt = prepareQuery("UPDATE studysets SET terms = ?, name = ?, description = ? WHERE studysetID = ?");
        $stmt->bind_param('sssi', $json, $name, $description, $id);
        $stmt->execute();
        $stmt->close();

    } catch (Exception $e) {
        return $e->getMessage();
    }
}

?>