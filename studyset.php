<?php
require 'Components/databaseConnection.php';
require 'Components/userHandler.php';
requireUser();
require 'Components/studysetHandler.php';
$studysetURL;
$studyset;
function getStudyset()
{
    global $studysetURL, $studyset;
    $studysetURL = isset($_GET['studyset']) ? $_GET['studyset'] : null;
    if ($studysetURL === null) {
        header("Location: index.php");
        exit();
    }
    try {
        $studyset = find("studysets", "studysetURL", $studysetURL)->fetch_assoc();
    } catch (Exception $e) {
        header("Location: index.php");
        exit();
    }
}

// function rememberLatestStudyset(){
//     setcookie("latest_studyset", $studyset['studysetID'], time() + (86400 * 30), "/");
// }


function getTest()
{
    global $studyset, $studysetURL;
    if (!isset($_GET['test'])) {
        include __DIR__ . '/Studyset/summary.php';
        return;
    }
    $test = $_GET['test'];

    switch ($test) {
        case 'flashcards':
            include __DIR__ . '/Studyset/flashcards.php';
            break;
        case 'quiz':
            include __DIR__ . '/Studyset/quiz.php';
            break;
        case 'write':
            include __DIR__ . '/Studyset/write.php';
            break;
        case 'combined':
            include __DIR__ . '/Studyset/combined.php';
            break;
        default:
            include __DIR__ . '/Studyset/summary.php';
            break;
    }
}

?>

<!doctype html>

<head>
    <?php include 'Components/theme.php'; ?>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Bootstrap demo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
</head>

<body>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI"
        crossorigin="anonymous"></script>

    <?php include "Components/navbar.php" ?>

    <main class="container my-4" style="padding-inline: 1rem;">
        <?php
        getStudyset();
        if (isset($_GET['edit'])) {
            include 'Studyset/edit.php';
        } else {
            getTest();
        }
        ?>
    </main>

</body>

</html>