<?php
session_start();
$user = getUser();



function getUser()
{
    if (!isset($_SESSION['userID'])) {
        return;
    }
    $userID = $_SESSION['userID'];
    $foundUser = find("users", "userID", $userID);

    if ($foundUser == null) {
        $_SESSION['userID'] = null;
        return;
    }

    $user = new User($foundUser['username'], $foundUser['password']);
    return $user;
}

function requireUser()
{
    if (!isset($_SESSION['userID'])) {
        header("Location: signIn.php");
        exit();
    }
}

class User
{
    public $username;
    public $password;

    function __construct($username, $password)
    {
        $this->username = $username;
        $this->password = $password;
    }
}

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

function query(mysqli $connection, string $query)
{

    $stmt = $connection->prepare($query);

    if ($stmt === false) {
        throw new Exception('Prepare failed: ' . $connection->error);
    }

    return $stmt;
}



function createUser(User $user)
{
    try {
        $connection = connectToDatabase();
        $hashedPassword = password_hash($user->password, PASSWORD_DEFAULT);

        $stmt = query($connection, "INSERT INTO users (username, password) VALUES (?, ?)");
        $stmt->bind_param("ss", $user->username, $hashedPassword);
        $stmt->execute();

        $userID = $connection->insert_id;

        $stmt->close();
        $connection->close();

        return $userID;
    } catch (Exception $exception) {
        return "Error: " . $exception->getMessage();
    }
}

function find(string $table, string $column, string $data)
{
    try {
        $connection = connectToDatabase();

        $stmt = query($connection, "SELECT * FROM $table WHERE $column=?");
        $stmt->bind_param("s", $data);
        $stmt->execute();

        $result = $stmt->get_result();
        $data = $result ? $result->fetch_assoc() : null;

        $stmt->close();
        $connection->close();

        return $data;

    } catch (Exception $exception) {
        return "Error: " . $exception->getMessage();
    }

}

function signUp($username, $password)
{
    try {
        $user = new User($username, $password);

        if (find("users", "username", $user->username)) {
            return "$username have already been used.";
        }

        $userID = createUser($user);

        $_SESSION['userID'] = $userID;

        header("Location: index.php");
        exit();

    } catch (Exception $exception) {
        return "Error: " . $exception->getMessage();
    }
}

function signIn($username, $password)
{
    try {

        $foundUser = find("users", "username", $username);

        if ($foundUser == null) {
            return "This user does not exist.";
        }

        if (!password_verify($password, $foundUser['password'])) {
            return "Password is incorrect.";
        }

        $_SESSION['userID'] = $foundUser['userID'];

        header("Location: index.php");
        exit();

    } catch (Exception $exception) {
        return "Error: " . $exception->getMessage();
    }
}

function validate($input)
{
    $input = trim($input);
    $input = stripslashes($input);
    $input = htmlspecialchars($input);
    return $input;
}
?>