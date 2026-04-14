<?php
require_once __DIR__ . '/../Components/termsHandler.php';
require_once __DIR__ . '/../Components/databaseConnection.php';
require_once __DIR__ . '/../Components/userHandler.php';
require_once __DIR__ . '/../Components/studysetHandler.php';

function exitQuiz()
{
    global $studyset;
    header('Location: studyset.php?studyset=' . urlencode($studyset['studysetURL']));
    exit();
}

function loadTerms(array $studyset): array
{
    $terms = getTerms($studyset);
    if (!is_array($terms) || count($terms) === 0) {
        exitQuiz();
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
if (!isModeEnabled($studyset, 'quiz')) {
    exitQuiz();
}
$terms = loadTerms($studyset);
list($knownTerms, $unknownTerms) = loadUserCardHistory($studysetId, $terms);
$unknownOnly = isset($_GET['unknownOnly']) && $_GET['unknownOnly'] === '1';
$questionIndices = $unknownOnly ? $unknownTerms : array_keys($terms);

$quizTerms = [];
foreach ($questionIndices as $idx) {
    if (isset($terms[$idx])) {
        $quizTerms[] = [
            'term' => $terms[$idx]['term'] ?? '',
            'definition' => $terms[$idx]['definition'] ?? '',
            'origIndex' => $idx,
        ];
    }
}
?>

<div>
    <h1>Quiz</h1>
    <p class="lead">
        <?php echo $unknownOnly ? 'Review only unknown terms from this study set.' : 'Answer multiple-choice questions to build a known/unknown terms memory list.'; ?>
    </p>
    <p>Known terms: <?php echo count($knownTerms); ?> · Unknown terms: <?php echo count($unknownTerms); ?></p>
</div>

<div id="quiz-container" style="display: <?php echo count($quizTerms) > 0 ? 'block' : 'none'; ?>;">
    <div class="card p-4 shadow-sm mb-4">
        <h2 id="questionText"></h2>
        <div class="list-group mt-4" id="answerButtons"></div>
    </div>
    <div class="progress my-3" role="progressbar" aria-label="Quiz progress" aria-valuemin="0" aria-valuemax="100">
        <div id="quizProgressbar" class="progress-bar" style="width: 0%"></div>
    </div>
    <p id="progressLabel" class="text-end text-muted"></p>
</div>

<div id="emptyMessage" class="alert alert-info"
    style="display: <?php echo count($quizTerms) === 0 ? 'block' : 'none'; ?>;">
    <?php if ($unknownOnly): ?>
        <strong>No unknown terms left.</strong> Restart the quiz or go back to the study set summary.
    <?php else: ?>
        <strong>No terms available for quiz.</strong>
    <?php endif; ?>
    <div class="mt-3 d-flex gap-2 flex-wrap">
        <button class="btn btn-outline-primary rounded-pill" onclick="restartQuiz()">Restart whole quiz</button>
        <a href="?studyset=<?php echo urlencode($studysetURL); ?>" class="btn btn-outline-danger rounded-pill">Exit
            quiz</a>
    </div>
</div>

<div id="completionMessage" class="container text-center my-5" style="display: none;">
    <h1 class="mb-4">Quiz Complete</h1>
    <p id="completionSummary" class="mb-4"></p>
    <div class="d-flex flex-column gap-3 align-items-center">
        <button id="continueUnknownBtn" class="btn btn-primary rounded-pill" onclick="continueUnknown()">
            Continue with unknown terms only
        </button>
        <button class="btn btn-outline-primary rounded-pill" onclick="restartQuiz()">Restart whole quiz</button>
        <a href="?studyset=<?php echo urlencode($studysetURL); ?>" class="btn btn-outline-danger rounded-pill">Exit
            quiz</a>
    </div>
</div>

<script>
    const allQuizTerms = <?php echo json_encode($quizTerms, JSON_UNESCAPED_UNICODE); ?>;
    const allTermDefinitions = <?php echo json_encode(array_map(function ($item) {
        return $item['definition']; }, $quizTerms), JSON_UNESCAPED_UNICODE); ?>;
    const studysetId = <?php echo json_encode($studysetId); ?>;
    const studysetURL = <?php echo json_encode($studysetURL); ?>;
    const unknownOnly = <?php echo json_encode($unknownOnly); ?>;

    const quizContainer = document.getElementById('quiz-container');
    const completionMessageElement = document.getElementById('completionMessage');
    const continueUnknownBtn = document.getElementById('continueUnknownBtn');
    const completionSummary = document.getElementById('completionSummary');
    const progressLabel = document.getElementById('progressLabel');

    let currentQuestionIndex = 0;
    let correctCount = 0;
    let wrongCount = 0;
    let totalQuestions = allQuizTerms.length;
    let questionOrder = shuffleArray([...allQuizTerms]);

    if (totalQuestions === 0) {
        quizContainer.style.display = 'none';
        completionMessageElement.style.display = 'block';
        completionSummary.textContent = 'Nothing to quiz here.';
        continueUnknownBtn.style.display = 'none';
    } else {
        renderQuestion();
        updateProgress();
    }

    function renderQuestion() {
        const question = questionOrder[currentQuestionIndex];
        document.getElementById('questionText').textContent = `What is the definition of “${question.term}”?`;
        const answerButtons = document.getElementById('answerButtons');
        answerButtons.innerHTML = '';

        const options = buildOptions(question);
        options.forEach(option => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'list-group-item list-group-item-action';
            btn.textContent = option.text;
            btn.addEventListener('click', () => handleAnswer(option.isCorrect));
            answerButtons.appendChild(btn);
        });
    }

    function buildOptions(currentQuestion) {
        const correctDefinition = currentQuestion.definition;
        const pageOptions = [{ text: correctDefinition, isCorrect: true }];
        const wrongDefinitions = allQuizTerms
            .map(item => item.definition)
            .filter(def => def !== correctDefinition);

        while (pageOptions.length < 4 && wrongDefinitions.length > 0) {
            const index = Math.floor(Math.random() * wrongDefinitions.length);
            pageOptions.push({ text: wrongDefinitions.splice(index, 1)[0], isCorrect: false });
        }

        return shuffleArray(pageOptions);
    }

    function handleAnswer(isCorrect) {
        const question = questionOrder[currentQuestionIndex];
        if (isCorrect) {
            correctCount += 1;
        } else {
            wrongCount += 1;
        }

        saveTestResponse(question.origIndex, isCorrect);
        currentQuestionIndex += 1;
        updateProgress();

        if (currentQuestionIndex >= totalQuestions) {
            showCompletion();
            return;
        }
        renderQuestion();
    }

    function showCompletion() {
        quizContainer.style.display = 'none';
        completionMessageElement.style.display = 'block';
        completionSummary.textContent = `You answered ${correctCount} correctly and ${wrongCount} incorrectly.`;
        continueUnknownBtn.style.display = wrongCount > 0 && !unknownOnly ? 'block' : 'none';
    }

    function updateProgress() {
        const progress = totalQuestions > 0 ? (currentQuestionIndex / totalQuestions) * 100 : 100;
        document.getElementById('quizProgressbar').style.width = `${progress}%`;
        progressLabel.textContent = `Question ${Math.min(currentQuestionIndex + 1, totalQuestions)}/${totalQuestions}`;
    }

    function saveTestResponse(cardIndex, isKnown) {
        fetch('Components/saveTestResponse.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'same-origin',
            body: JSON.stringify({ studysetId, cardIndex, isKnown })
        }).catch(err => console.error('Could not save response', err));
    }

    async function restartQuiz() {
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
        location.href = `?studyset=${encodeURIComponent(studysetURL)}&test=quiz`;
    }

    function continueUnknown() {
        location.href = `?studyset=${encodeURIComponent(studysetURL)}&test=quiz&unknownOnly=1`;
    }

    function shuffleArray(array) {
        for (let i = array.length - 1; i > 0; i--) {
            const j = Math.floor(Math.random() * (i + 1));
            [array[i], array[j]] = [array[j], array[i]];
        }
        return array;
    }
</script>