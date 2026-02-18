<?php
$user = getUser();

function getUser()
{
    if (!isset($_SESSION['userID'])) {
        return;
    }
    $userID = $_SESSION['userID'];
    $foundUsers = find("users", "userID", $userID);
    $foundUser = $foundUsers->fetch_assoc();

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

function createUser(User $user)
{
    try {
        global $databaseConnection;
        $hashedPassword = password_hash($user->password, PASSWORD_DEFAULT);

        $stmt = prepareQuery("INSERT INTO users (username, password) VALUES (?, ?)");
        $stmt->bind_param("ss", $user->username, $hashedPassword);
        $stmt->execute();

        $userID = $databaseConnection->insert_id;

        $stmt->close();

        return $userID;
    } catch (Exception $exception) {
        return "Error: " . $exception->getMessage();
    }
}

function signUp($username, $password)
{
    try {
        $user = new User($username, $password);
        $foundUsers = find("users", "username", $user->username);
        $foundUser = $foundUsers->fetch_assoc();

        if ($foundUser != null & $foundUser != false) {
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

        $foundUsers = find("users", "username", $username);
        $foundUser = $foundUsers->fetch_assoc();

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

function changeUsername($newUsername)
{
    $newUsername = validate($newUsername);

    if (empty($newUsername)) {
        return "Username cannot be empty.";
    }

    // Check if username already exists
    $foundUsers = find("users", "username", $newUsername);
    $foundUser = $foundUsers->fetch_assoc();

    if ($foundUser != null && $foundUser != false) {
        return "Username is already taken.";
    }

    try {
        $userID = $_SESSION['userID'];
        $stmt = prepareQuery("UPDATE users SET username = ? WHERE userID = ?");
        $stmt->bind_param("si", $newUsername, $userID);
        $stmt->execute();
        $stmt->close();

        return "success";
    } catch (Exception $exception) {
        return "Error updating username: " . $exception->getMessage();
    }
}

function changePassword($currentPassword, $newPassword, $confirmPassword)
{
    $user = getUser();

    if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
        return "All password fields are required.";
    }

    if (!password_verify($currentPassword, $user->password)) {
        return "Current password is incorrect.";
    }

    if ($newPassword !== $confirmPassword) {
        return "New passwords do not match.";
    }

    if (strlen($newPassword) < 6) {
        return "New password must be at least 6 characters long.";
    }

    try {
        $userID = $_SESSION['userID'];
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = prepareQuery("UPDATE users SET password = ? WHERE userID = ?");
        $stmt->bind_param("si", $hashedPassword, $userID);
        $stmt->execute();
        $stmt->close();

        return "success";
    } catch (Exception $exception) {
        return "Error updating password: " . $exception->getMessage();
    }
}

function deleteAccount()
{
    try {
        $userID = $_SESSION['userID'];

        // Delete all study sets associated with the user
        $stmt = prepareQuery("DELETE FROM studysets WHERE userID = ?");
        $stmt->bind_param("i", $userID);
        $stmt->execute();
        $stmt->close();

        // Delete the user
        $stmt = prepareQuery("DELETE FROM users WHERE userID = ?");
        $stmt->bind_param("i", $userID);
        $stmt->execute();
        $stmt->close();

        // Clear session and redirect to index
        $_SESSION['userID'] = null;
        header("Location: index.php");
        exit();
    } catch (Exception $exception) {
        return "Error deleting account: " . $exception->getMessage();
    }
}

function signOut()
{
    $_SESSION['userID'] = null;
    header("Location: index.php");
    exit();
}
?>