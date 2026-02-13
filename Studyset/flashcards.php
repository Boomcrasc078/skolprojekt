<?php
require_once __DIR__ . '/../Components/termsHandler.php';

$terms = [];
$studysetName = '';

// Use the $studyset already loaded by studyset.php
if (isset($studyset) && is_array($studyset) && !empty($studyset['studysetID'])) {
    $studysetName = $studyset['name'] ?? '';
    $res = get_terms_and_file($studyset);
    $terms = $res['terms'] ?? [];
}
?>

<script>
    // terms loaded from PHP
    const TERMS = <?php echo json_encode(array_values($terms), JSON_UNESCAPED_UNICODE); ?> || [];
    let currentIndex = 0;
    let showingFront = true;
    let knownCount = 0;
    let unknownCount = 0;
    const knownCards = new Set();
    const unknownCards = new Set();
    let activeIndices = Array.from({ length: TERMS.length }, (_, i) => i); // [0, 1, 2, ...]

    function renderProgress() {
        const total = activeIndices.length || 1;
        const value = Math.min(currentIndex, activeIndices.length);
        const percent = Math.round((value / total) * 100);
        document.getElementById('progressbar').style.width = percent + '%';
        document.getElementById('progressText').innerText = `${Math.min(value, activeIndices.length)}/${activeIndices.length}`;
    }

    function renderCard() {
        const card = document.getElementById('flashcard');

        const termIndex = activeIndices[currentIndex];
        const item = TERMS[termIndex];
        const front = item.term;
        const back = item.definition;

        showingFront = true;
        card.dataset.front = front;
        card.dataset.back = back;
        card.innerHTML = `<h1>${escapeHtml(front)}</h1>`;
        card.style.transform = 'scaleX(1)';
        renderProgress();
    }

    function flipCard() {
        const card = document.getElementById('flashcard');
        const front = card.dataset.front || '';
        const back = card.dataset.back || '';
        // simple flip animation
        card.style.transition = 'transform 0.2s';
        card.style.transform = 'scaleX(0)';
        setTimeout(() => {
            showingFront = !showingFront;
            card.innerHTML = `<h1>${escapeHtml(showingFront ? front : back)}</h1>`;
            card.style.transform = 'scaleX(1)';
        }, 200);
    }

    function nextCard(isKnown) {
        const termIndex = activeIndices[currentIndex];
        if (isKnown) {
            knownCount++;
            knownCards.add(termIndex);
            unknownCards.delete(termIndex);
        } else {
            unknownCount++;
            unknownCards.add(termIndex);
            knownCards.delete(termIndex);
        }
        currentIndex++;
        if (currentIndex >= activeIndices.length) {
            showSummary();
            return;
        }
        renderCard();
    }

    function showSummary() {
        const container = document.getElementById('flashcard-container');
        const unknownIndices = Array.from(unknownCards);

        let summaryHtml = `
            <div class="text-center">
                <h2>Färdig!</h2>
                <p>${knownCount} kända, ${unknownCount} okända av ${activeIndices.length}</p>
                <div class="d-flex flex-column gap-3 mt-4" style="max-width: 300px; margin: 0 auto;">`;

        if (unknownIndices.length > 0) {
            summaryHtml += `<button onclick="repeatUnknown()" class="btn btn-warning">Studera okända (${unknownIndices.length})</button>`;
        }

        summaryHtml += `
                    <button onclick="repeatAll()" class="btn btn-info">Studera alla kort igen</button>
                    <a href="?studyset=<?php echo isset($studyset['studysetURL']) ? $studyset['studysetURL'] : ''; ?>" class="btn btn-primary">Tillbaka till summary</a>
                </div>
            </div>`;

        container.innerHTML = summaryHtml;
        document.getElementById('progressbar').style.width = '100%';
        document.getElementById('progressText').innerText = `${activeIndices.length}/${activeIndices.length}`;
        document.getElementById('knowBtn').disabled = true;
        document.getElementById('dontKnowBtn').disabled = true;
    }

    function repeatUnknown() {
        activeIndices = Array.from(unknownCards);
        restartQuiz();
    }

    function repeatAll() {
        activeIndices = Array.from({ length: TERMS.length }, (_, i) => i);
        restartQuiz();
    }

    function restartQuiz() {
        currentIndex = 0;
        knownCount = 0;
        unknownCount = 0;
        knownCards.clear();
        unknownCards.clear();

        const container = document.getElementById('flashcard-container');
        container.innerHTML = `
            <div>
                <h1>Flashcards</h1>
                <h2 id="progressText">0/${activeIndices.length}</h2>
                <div class="progress" role="progressbar" aria-label="Basic example" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">
                    <div id="progressbar" class="progress-bar" style="width: 0%"></div>
                </div>
            </div>
            <br>
            <div id="flashcard" class="container-fluid flashcard btn bg-body-tertiary shadow rounded-5">
                <h1>Term</h1>
            </div>
            <div class="flashcard-buttons d-flex justify-content-between my-4 gap-3 width-100">
                <button id="dontKnowBtn" class="btn btn-danger shadow rounded-5">
                    <h2>Don't Know</h2>
                </button>
                <button id="knowBtn" class="btn btn-success shadow rounded-5">
                    <h2>Know</h2>
                </button>
            </div>
        `;

        document.getElementById('flashcard').addEventListener('click', flipCard);
        document.getElementById('knowBtn').addEventListener('click', () => nextCard(true));
        document.getElementById('dontKnowBtn').addEventListener('click', () => nextCard(false));
        renderCard();
    }

    function escapeHtml(unsafe) {
        if (unsafe === null || unsafe === undefined) return '';
        return String(unsafe)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/\"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    document.addEventListener('DOMContentLoaded', () => {
        document.getElementById('flashcard').addEventListener('click', flipCard);
        document.getElementById('knowBtn').addEventListener('click', () => nextCard(true));
        document.getElementById('dontKnowBtn').addEventListener('click', () => nextCard(false));
        renderCard();
    });
</script>

<div id="flashcard-container">
    <div>
        <h1>Flashcards</h1>
        <h2 id="progressText">0/<?php echo count($terms); ?></h2>
        <div class="progress" role="progressbar" aria-label="Basic example" aria-valuenow="0" aria-valuemin="0"
            aria-valuemax="100">
            <div id="progressbar" class="progress-bar" style="width: 0%"></div>
        </div>
    </div>
    <br>
    <div id="flashcard" class="container-fluid flashcard btn bg-body-tertiary shadow rounded-5">
        <h1>Term</h1>
    </div>
    <div class="flashcard-buttons d-flex justify-content-between my-4 gap-3 width-100">
        <button id="dontKnowBtn" class="btn btn-danger shadow rounded-5">
            <h2>Don't Know</h2>
        </button>
        <button id="knowBtn" class="btn btn-success shadow rounded-5">
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