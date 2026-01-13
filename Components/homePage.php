<?php require 'Components/studysetHandler.php'; ?>

<?php
$studysets = getStudysets($_SESSION['userID']);
?>

<style>
    @media(max-width: 540px) {
        .top-section {
            flex-direction: column;
            height: auto !important;
            gap: 0 !important;
        }

        .flashcard {
            width: 100% !important;
            height: 70vw !important;
        }

        .buttons {
            width: 100% !important;
            justify-content: space-around !important;
            flex-direction: row !important;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .buttons>button {
            flex-basis: 47% !important;
        }
    }
</style>

<div class="mt-5 container d-flex flex-column">
    <?php
    if (count($studysets) > 0) {
        ?>

        <h1 class="display-1">Welcome back <?php echo $user->username ?>!</h1>

        <!--Top Section-->
        <h2>Continue where you left off.</h2>
        <div class="top-section d-flex gap-5 my-3" style="height: 400px">
            <div class="flashcard border rounded-5 p-5 shadow w-50 bg-body-tertiary">
            </div>
            <div class="buttons d-flex flex-column justify-content-between w-50 py-3">
                <button class="btn btn-primary btn-lg rounded rounded-pill shadow">Flashcards</button>
                <button class="btn btn-primary btn-lg rounded rounded-pill shadow">Quiz</button>
                <button class="btn btn-primary btn-lg rounded rounded-pill shadow">Write</button>
                <button class="btn btn-primary btn-lg rounded rounded-pill shadow">Combined</button>
            </div>
        </div>

        <!--Studysets-->
        <div class="border rounded-5 p-3 pt-4 mt-3 shadow bg-body-tertiary">
            <?php include 'Components/studysets.php'; ?>
        </div>
        <?php

    } else { ?>

        <!--No Studysets-->
        <div
            class="position-absolute top-50 start-50 translate-middle text-center border rounded-5 p-5 shadow w-50 bg-body-tertiary">
            <h1>Hi <?php echo $user->username ?>!</h1>
            <h2>It seems like you don't have any studysets.</h2>
            <h4 class="mt-4 mb-3">Do you wish to create one?</h4>
            <a href="newStudyset.php" class="btn btn-primary btn-lg rounded rounded-pill shadow">Create A Studyset</a>
        </div>

    <?php } ?>
</div>