<?php
require_once __DIR__ . '/../Components/termsHandler.php';

$studysetName = $studyset['name'] ?? '';
$terms = getTerms($studyset);

if (!is_array($terms)) {
    exitFlashcards();
}

if (count($terms) == 0) {
    exitFlashcards();
}

function exitFlashcards()
{
    global $studyset;
    header('Location: studyset.php?studyset=' . $studyset['studysetURL']);
    exit();
}
?>



<div>
    <h1>Flashcards</h1>
    <h2 id="progressText"></h2>
    <div class="progress" role="progressbar" aria-label="Basic example" aria-valuenow="0" aria-valuemin="0"
        aria-valuemax="100">
        <div id="progressbar" class="progress-bar" style="width: 0%"></div>
    </div>
</div>
<div id="flashcard-container">
    <br>
    <div id="flashcard" class="container-fluid flashcard btn bg-body-tertiary shadow rounded-5" onclick="flipCard()">
        <h1 id="flashcard-text">Term</h1>
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
<div id="completionMessage" class="container text-center" style="display: none;">
    <h1>Flashcards Complete!</h1>
    <a href="studyset.php?studyset=<?php echo $studysetURL ?>" class="btn btn-primary">Back to Studyset</a>
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

<script>
    const terms = <?php echo json_encode(array_values($terms), JSON_UNESCAPED_UNICODE); ?>;
    let currentIndex = 0;
    let cardFlipped = false;
    updateCard();
    updateProgressBar(0);

    function flipCard() {
        const card = document.getElementById('flashcard');
        card.style.transition = 'transform 0.2s';
        card.style.transform = 'scaleX(0)';
        setTimeout(() => {
            card.style.transform = 'scaleX(1)';
            cardFlipped = !cardFlipped;
            updateCard();
        }, 200);
    }

    function updateCard() {
        const text = document.getElementById('flashcard-text');
        text.innerHTML = cardFlipped ? terms[currentIndex].term : terms[currentIndex].definition;
    }

    function nextCard(knowCard) {
        currentIndex++;
        updateProgressBar((currentIndex / terms.length) * 100);

        <?php
        
        ?>

        if (currentIndex >= terms.length) {
            flashcardsComplete();
            return;
        }
        updateCard();
    }

    function flashcardsComplete() {
        updateProgressBar(100);
        const container = document.getElementById('flashcard-container');
        container.style.display = "none";
    }

    function updateProgressBar(progress) {
        const progressBar = document.getElementById('progressbar');
        progressBar.style.width = `${progress}%`;
    }
</script>