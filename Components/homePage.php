<?php
require 'Components/studysetHandler.php';
require 'Components/termsHandler.php';

$studysets = getStudysets($_SESSION['userID']);
$lastStudysetURL = null;
$lastStudysetPreview = null;

if (isset($_SESSION['userID'])) {
    $stmt = prepareQuery('SELECT lastStudysetID FROM users WHERE userID = ?');
    $stmt->bind_param('i', $_SESSION['userID']);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    if (!empty($row['lastStudysetID'])) {
        $lastStudyset = find('studysets', 'studysetID', $row['lastStudysetID'])->fetch_assoc();
        if ($lastStudyset) {
            $lastStudysetURL = $lastStudyset['studysetURL'];
            $terms = getTerms($lastStudyset);
            if (!empty($terms) && isset($terms[0]['term'])) {
                $lastStudysetPreview = $terms[0]['term'];
            }
        }
    }

    if ($lastStudysetURL === null && !empty($studysets)) {
        $lastStudysetURL = $studysets[0]['studysetURL'];
        $terms = getTerms($studysets[0]);
        if (!empty($terms) && isset($terms[0]['term'])) {
            $lastStudysetPreview = $terms[0]['term'];
        }
    }
}
?>

<style>
    .no-studysets {
        width: 95% !important;
    }

    @media(max-width: 300px) {
        .no-studysets {
            word-wrap: break-word !important;
        }
    }

    @media(min-width: 403px) {
        .no-studysets {
            width: 90% !important
        }
    }

    @media(min-width: 459px) {
        .no-studysets {
            width: 80% !important
        }
    }

    @media(min-width: 525px) {
        .no-studysets {
            width: 70% !important
        }
    }

    @media(min-width: 800px) {
        .no-studysets {
            width: 50% !important
        }
    }


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
            gap: 0.5rem;
        }

        .buttons>a {
            flex-basis: 30%;
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
        <div class="top-section d-flex gap-5 my-3" style="height: 300px">
            <div
                class="flashcard border rounded-5 p-5 shadow w-50 bg-body-tertiary d-flex align-items-center justify-content-center text-center">
                <?php if ($lastStudysetPreview): ?>
                    <div>
                        <h2 class="fs-1 mb-3"><?php echo htmlspecialchars($lastStudysetPreview, ENT_QUOTES, 'UTF-8'); ?></h2>
                    </div>
                <?php else: ?>
                    <div>

                    </div>
                <?php endif; ?>
            </div>
            <div class="buttons d-flex flex-column justify-content-between w-50 py-3">
                <a class="btn btn-primary btn-lg rounded rounded-pill shadow"
                    href="<?php echo $lastStudysetURL ? 'studyset.php?studyset=' . urlencode($lastStudysetURL) . '&test=flashcards' : 'Studyset/new.php'; ?>">Flashcards</a>
                <a class="btn btn-primary btn-lg rounded rounded-pill shadow"
                    href="<?php echo $lastStudysetURL ? 'studyset.php?studyset=' . urlencode($lastStudysetURL) . '&test=quiz' : 'Studyset/new.php'; ?>">Quiz</a>
                <a class="btn btn-primary btn-lg rounded rounded-pill shadow"
                    href="<?php echo $lastStudysetURL ? 'studyset.php?studyset=' . urlencode($lastStudysetURL) . '&test=write' : 'Studyset/new.php'; ?>">Write</a>
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
            class="no-studysets container position-absolute top-50 start-50 translate-middle text-center border rounded-5 p-5 shadow bg-body-tertiary">
            <h1>Hi <?php echo $user->username ?>!</h1>
            <h2>It seems like you don't have any studysets.</h2>
            <h4 class="mt-4 mb-3">Do you wish to create one?</h4>
            <a href="Studyset/new.php" class="btn btn-primary btn-lg rounded rounded-pill shadow">Create A Studyset</a>
        </div>

    <?php } ?>
</div>