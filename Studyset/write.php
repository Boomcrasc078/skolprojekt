<?php
require_once __DIR__ . '/../Components/termsHandler.php';
require_once __DIR__ . '/../Components/databaseConnection.php';
require_once __DIR__ . '/../Components/userHandler.php';
require_once __DIR__ . '/../Components/studysetHandler.php';

function exitWrite()
{
    global $studyset;
    header('Location: studyset.php?studyset=' . urlencode($studyset['studysetURL']));
    exit();
}

function loadTerms(array $studyset): array
{
    $terms = getTerms($studyset);
    if (!is_array($terms) || count($terms) === 0) {
        exitWrite();
    }
    return $terms;
}

function loadUserCardHistory($studysetId, array $terms): array
{
    $known = [];
    $userId = $_SESSION['userID'] ?? null;
    if (!$userId) {
        return [$known, array_keys($terms)];
    }

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
        $idx = (int) $idx;
        if (!isset($terms[$idx])) {
            continue;
        }
        if (!empty($info['known'])) {
            $known[] = $idx;
        }
    }

    $all = array_keys($terms);
    $unknown = array_values(array_diff($all, $known));
    return [$known, $unknown];
}

$studysetId = $studyset['studysetID'] ?? null;
$studysetURL = $studyset['studysetURL'] ?? '';
if (!isModeEnabled($studyset, 'write')) {
    exitWrite();
}
$terms = loadTerms($studyset);
list($knownTerms, $unknownTerms) = loadUserCardHistory($studysetId, $terms);
$unknownOnly = isset($_GET['unknownOnly']) && $_GET['unknownOnly'] === '1';
$questionIndices = $unknownOnly ? $unknownTerms : array_keys($terms);

$writeTerms = [];
foreach ($questionIndices as $idx) {
    if (isset($terms[$idx])) {
        $writeTerms[] = [
            'term' => $terms[$idx]['term'] ?? '',
            'definition' => $terms[$idx]['definition'] ?? '',
            'origIndex' => $idx,
        ];
    }
}
?>

<div>
    <h1>Write Quiz</h1>
    <p class="lead">
        <?php echo $unknownOnly ? 'Practice only unknown terms.' : 'Type the definition for each term and save it as known or unknown.'; ?>
    </p>
    <p>Known terms: <?php echo count($knownTerms); ?> · Unknown terms: <?php echo count($unknownTerms); ?></p>
</div>

<div id="write-container" style="display: <?php echo count($writeTerms) > 0 ? 'block' : 'none'; ?>;">
    <div class="card p-4 shadow-sm mb-4">
        <h2 id="writePrompt"></h2>
        <div class="mb-3">
            <label for="answerInput" class="form-label">Your definition</label>
            <textarea id="answerInput" class="form-control" rows="4" placeholder="Type your answer here"></textarea>
        </div>
        <button id="submitAnswer" class="btn btn-primary">Submit answer</button>
        <div id="feedbackMessage" class="mt-3" style="display:none;"></div>
    </div>
    <div class="progress my-3" role="progressbar" aria-label="Write quiz progress" aria-valuemin="0"
        aria-valuemax="100">
        <div id="writeProgressbar" class="progress-bar" style="width: 0%"></div>
    </div>
    <p id="writeProgressLabel" class="text-end text-muted"></p>
</div>

<div id="emptyMessage" class="alert alert-info"
    style="display: <?php echo count($writeTerms) === 0 ? 'block' : 'none'; ?>;">
    <?php if ($unknownOnly): ?>
        <strong>No unknown terms left.</strong> Restart the write quiz or go back to the study set summary.
    <?php else: ?>
        <strong>No terms available for write quiz.</strong>
    <?php endif; ?>
    <div class="mt-3 d-flex gap-2 flex-wrap">
        <button class="btn btn-outline-primary rounded-pill" onclick="restartWrite()">Restart whole write quiz</button>
        <a href="?studyset=<?php echo urlencode($studysetURL); ?>" class="btn btn-outline-danger rounded-pill">Exit
            quiz</a>
    </div>
</div>

<div id="completionMessage" class="container text-center my-5" style="display: none;">
    <h1 class="mb-4">Write Quiz Complete</h1>
    <p id="completionSummary" class="mb-4"></p>
    <div class="d-flex flex-column gap-3 align-items-center">
        <button id="continueUnknownBtn" class="btn btn-primary rounded-pill" onclick="continueUnknown()">
            Continue with unknown terms only
        </button>
        <button class="btn btn-outline-primary rounded-pill" onclick="restartWrite()">Restart whole write quiz</button>
        <a href="?studyset=<?php echo urlencode($studysetURL); ?>" class="btn btn-outline-danger rounded-pill">Exit
            quiz</a>
    </div>
</div>

<script>
    const writeTerms = <?php echo json_encode($writeTerms, JSON_UNESCAPED_UNICODE); ?>;
    const studysetId = <?php echo json_encode($studysetId); ?>;
    const studysetURL = <?php echo json_encode($studysetURL); ?>;
    const unknownOnly = <?php echo json_encode($unknownOnly); ?>;

    const writeContainer = document.getElementById('write-container');
    const completionMessageElement = document.getElementById('completionMessage');
    const continueUnknownBtn = document.getElementById('continueUnknownBtn');
    const completionSummary = document.getElementById('completionSummary');
    const feedbackMessage = document.getElementById('feedbackMessage');
    const answerInput = document.getElementById('answerInput');
    const submitButton = document.getElementById('submitAnswer');
    const progressLabel = document.getElementById('writeProgressLabel');

    let currentIndex = 0;
    let correctCount = 0;
    let wrongCount = 0;
    let totalQuestions = writeTerms.length;

    if (totalQuestions === 0) {
        writeContainer.style.display = 'none';
        completionMessageElement.style.display = 'none';
    } else {
        renderPrompt();
        updateProgress();
    }

    submitButton.addEventListener('click', handleSubmit);

    function renderPrompt() {
        const item = writeTerms[currentIndex];
        document.getElementById('writePrompt').textContent = `Write the definition for “${item.term}”.`;
        feedbackMessage.style.display = 'none';
        answerInput.value = '';
        answerInput.focus();
    }

    function handleSubmit() {
        const response = normalizeText(answerInput.value);
        if (!response) {
            feedback('Please enter your definition before submitting.', 'warning');
            return;
        }

        const item = writeTerms[currentIndex];
        const correct = normalizeText(item.definition);
        const isCorrect = response === correct;

        if (isCorrect) {
            correctCount += 1;
            feedback('Correct! This term has been saved as known.', 'success');
        } else {
            wrongCount += 1;
            feedback(`Incorrect. The right answer was: ${item.definition}`, 'danger');
        }

        saveTestResponse(item.origIndex, isCorrect);
        currentIndex += 1;
        updateProgress();

        if (currentIndex >= totalQuestions) {
            showCompletion();
            return;
        }

        setTimeout(renderPrompt, 1200);
    }

    function feedback(message, type) {
        feedbackMessage.textContent = message;
        feedbackMessage.className = `alert alert-${type}`;
        feedbackMessage.style.display = 'block';
    }

    function showCompletion() {
        writeContainer.style.display = 'none';
        completionMessageElement.style.display = 'block';
        completionSummary.textContent = `You answered ${correctCount} correctly and ${wrongCount} incorrectly.`;
        continueUnknownBtn.style.display = wrongCount > 0 && !unknownOnly ? 'block' : 'none';
    }

    function updateProgress() {
        const progress = totalQuestions > 0 ? (currentIndex / totalQuestions) * 100 : 100;
        document.getElementById('writeProgressbar').style.width = `${progress}%`;
        progressLabel.textContent = `Question ${Math.min(currentIndex + 1, totalQuestions)}/${totalQuestions}`;
    }

    function saveTestResponse(cardIndex, isKnown) {
        fetch('Components/saveTestResponse.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'same-origin',
            body: JSON.stringify({ studysetId, cardIndex, isKnown })
        }).catch(err => console.error('Could not save response', err));
    }

    async function restartWrite() {
        const res = await fetch('Components/restartTestResponse.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'same-origin',
            body: JSON.stringify({ studysetId })
        });
        if (!res.ok) {
            console.error('Restart failed:', res.status);
            return;
        }
        location.href = `?studyset=${encodeURIComponent(studysetURL)}&test=write`;
    }

    function continueUnknown() {
        location.href = `?studyset=${encodeURIComponent(studysetURL)}&test=write&unknownOnly=1`;
    }

    function normalizeText(text) {
        return text
            .trim()
            .replace(/\s+/g, ' ')
            .replace(/[\.,;!?]/g, '')
            .toLowerCase();
    }
</script>