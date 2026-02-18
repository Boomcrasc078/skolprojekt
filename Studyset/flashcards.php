<?php
require_once __DIR__ . '/../Components/termsHandler.php';

$studysetName = $studyset['name'] ?? '';
$terms = getTerms($studyset);


?>

<script>
    const terms = <?php echo json_encode(array_values($terms), JSON_UNESCAPED_UNICODE); ?>;

    function flipCard() {
        const card = document.getElementById('flashcard');
        card.style.transition = 'transform 0.2s';
        card.style.transform = 'scaleX(0)';
        setTimeout(() => {
            card.style.transform = 'scaleX(1)';
        }, 200);
    }

    function nextCard(knowCard) {

    }
</script>

<div id="flashcard-container">
    <div>
        <h1>Flashcards</h1>
        <h2 id="progressText"></h2>
        <div class="progress" role="progressbar" aria-label="Basic example" aria-valuenow="0" aria-valuemin="0"
            aria-valuemax="100">
            <div id="progressbar" class="progress-bar" style="width: 0%"></div>
        </div>
    </div>
    <br>
    <div id="flashcard" class="container-fluid flashcard btn bg-body-tertiary shadow rounded-5" onclick="flipCard()">
        <h1>Term</h1>
    </div>
    <div class="flashcard-buttons d-flex justify-content-between my-4 gap-3 width-100">
        <button id="dontKnowBtn" class="btn btn-danger shadow rounded-5" onclick="nextCard(false)">
            <h2>Don't Know</h2>
        </button>
        <button id="knowBtn" class="btn btn-success shadow rounded-5" onclick="nextCard(true)">
            <h2>Know</h2>
        </button>
    </div>
</div>

<style>
    .flashcard {
        display: flex;
        justify-content: center;
        align-items: center;
        height: calc(70svh - 60px - 80px);
        perspective: 600px;
        transform-origin: center;
    }

    .flashcard-buttons>button {
        flex: 1;
        height: calc(30svh - 60px - 80px);
    }
</style>