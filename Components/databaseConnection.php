<?php
session_start();
$databaseConnection = connectToDatabase();

function connectToDatabase()
{
    $serverName = "localhost";
    $serverUsername = "root";
    $serverPassword = "";

    $databaseName = "QUIS";

    $connection = new mysqli($serverName, $serverUsername, $serverPassword, $databaseName);

    if ($connection->connect_errno) {
        throw new Exception('Database connection failed: ' . $connection->connect_error);
    }

    return $connection;
}

function query(string $query)
{
    global $databaseConnection;
    $stmt = $databaseConnection->prepare($query);

    if ($stmt === false) {
        throw new Exception('Prepare failed: ' . $databaseConnection->error);
    }

    return $stmt;
}

function find(string $table, string $column, string $data)
{
    try {
        $stmt = query("SELECT * FROM $table WHERE $column=?");
        $stmt->bind_param("s", $data);
        $stmt->execute();

        $result = $stmt->get_result();

        $stmt->close();

        return $result;

    } catch (Exception $exception) {
        return "Error: " . $exception->getMessage();
    }

}

?>