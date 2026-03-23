<?php
require_once __DIR__ . '/../Components/termsHandler.php';
require_once __DIR__ . '/../Components/databaseConnection.php';
require_once __DIR__ . '/../Components/userHandler.php';

/**
 * redirect back to the studyset page when we can't show flashcards
 */
function exitFlashcards()
{
    global $studyset;
    header('Location: studyset.php?studyset=' . $studyset['studysetURL']);
    exit();
}

/**
 * load and validate terms for the current studyset or bail out
 *
 * @param array $studyset
 * @return array
the decoded terms array
 */
function loadTerms(array $studyset): array
{
    $terms = getTerms($studyset);
    if (!is_array($terms) || count($terms) === 0) {
        exitFlashcards();
    }
    return $terms;
}

/**
 * reset all card progress for a studyset so user can study from scratch
 *
 * @param string|int $studysetId
 */
function resetStudysetProgress($studysetId)
{
    $userId = $_SESSION['userID'] ?? null;
    if (!$userId) {
        return;
    }

    $stmt = prepareQuery('SELECT userData FROM users WHERE userID = ?');
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    $userData = json_decode($row['userData'] ?? '{}', true);
    if (!is_array($userData)) {
        $userData = [];
    }

    // clear this studyset's flashcard data
    if (isset($userData['flashcards'])) {
        unset($userData['flashcards'][(string) $studysetId]);
    }

    $json = json_encode($userData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    $stmt = prepareQuery('UPDATE users SET userData = ? WHERE userID = ?');
    $stmt->bind_param('si', $json, $userId);
    $stmt->execute();
    $stmt->close();
}

function loadUserCardHistory($studysetId, array $terms): array
{
    $known = [];

    $userId = $_SESSION['userID'] ?? null;
    if (!$userId) {
        // no user, everything is unknown
        return [$known, array_keys($terms)];
    }

    // column is called `userData` in the users table
    $stmt = prepareQuery('SELECT userData FROM users WHERE userID = ?');
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    $userData = json_decode($row['userData'] ?? '{}', true);
    if (!is_array($userData)) {
        return [$known, array_keys($terms)];
    }

    $cards = $userData['flashcards'][(string) $studysetId] ?? [];
    if (!is_array($cards)) {
        return [$known, array_keys($terms)];
    }

    foreach ($cards as $idx => $info) {
        $idx = (int) $idx; // convert string key to int for array access
        if (!isset($terms[$idx])) {
            continue; // stale entry, ignore
        }
        if (!empty($info['known'])) {
            $known[] = $idx;
        }
        // if card exists and not known we simply leave it out of $known; it'll be
        // included in unknown list later
    }

    // every index that is not in $known is considered unknown
    $all = array_keys($terms);
    $unknown = array_values(array_diff($all, $known));

    return [$known, $unknown];
}

// --- main execution -------------------------------------------------

$studysetName = $studyset['name'] ?? '';
$studysetId = $studyset['studysetID'] ?? '';
$terms = loadTerms($studyset);
list($knownCards, $unknownCards) = loadUserCardHistory($studysetId, $terms);
// only show cards the user hasn't marked known
if (empty($unknownCards)) {
    // all cards mastered! reset progress and reload to study again
    resetStudysetProgress($studysetId);
    header('Location: studyset.php?studyset=' . $studyset['studysetURL']);
    exit();
}

// build array of terms for the flashcards, keeping original index for saving
$flashTerms = [];
foreach ($unknownCards as $idx) {
    if (isset($terms[$idx])) {
        $flashTerms[] = [
            'term' => $terms[$idx]['term'] ?? '',
            'definition' => $terms[$idx]['definition'] ?? '',
            'origIndex' => $idx,
        ];
    }
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
    const terms = <?php echo json_encode($flashTerms, JSON_UNESCAPED_UNICODE); ?>;
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
        const cardIndex = currentIndex;             // the card that was just shown
        currentIndex++;
        updateProgressBar((currentIndex / terms.length) * 100);

        // send to server for persistence (use original term index)
        fetch('Components/saveCardResponse.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'same-origin',
            body: JSON.stringify({
                studysetId: <?php echo json_encode($studysetId); ?>,
                cardIndex: terms[cardIndex].origIndex,
                isKnown: knowCard
            })
        });

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

        // reload page to show remaining unknown cards
        setTimeout(() => {
            location.reload();
        }, 1500);
    }

    function updateProgressBar(progress) {
        const progressBar = document.getElementById('progressbar');
        progressBar.style.width = `${progress}%`;
    }
</script>