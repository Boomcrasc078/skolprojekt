<?php
require_once __DIR__ . '/../Components/termsHandler.php';
require_once __DIR__ . '/../Components/databaseConnection.php';
require_once __DIR__ . '/../Components/userHandler.php';
require_once __DIR__ . '/../Components/studysetHandler.php';

function exitCombined()
{
    global $studyset;
    header('Location: studyset.php?studyset=' . urlencode($studyset['studysetURL']));
    exit();
}

function loadTerms(array $studyset): array
{
    $terms = getTerms($studyset);
    if (!is_array($terms) || count($terms) === 0) {
        exitCombined();
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

function buildTermList(array $terms): array
{
    $result = [];
    foreach ($terms as $idx => $termData) {
        $result[] = [
            'origIndex' => $idx,
            'term' => $termData['term'] ?? '',
            'definition' => $termData['definition'] ?? '',
        ];
    }
    return $result;
}

function getStepLabel(string $step): string
{
    switch ($step) {
        case 'flashcards':
            return 'Flashcards';
        case 'quiz':
            return 'Quiz';
        case 'write':
            return 'Write';
        default:
            return ucfirst($step);
    }
}

$studysetId = $studyset['studysetID'] ?? null;
$studysetURL = $studyset['studysetURL'] ?? '';
if (!isModeEnabled($studyset, 'combined')) {
    exitCombined();
}
$terms = loadTerms($studyset);
list($knownTerms, $unknownTerms) = loadUserCardHistory($studysetId, $terms);
$enabledSteps = getEnabledStudysetModes($studyset);
if (empty($enabledSteps)) {
    exitCombined();
}
$termList = buildTermList($terms);
$enableFlashcards = isModeEnabled($studyset, 'flashcards');
$enableQuiz = isModeEnabled($studyset, 'quiz');
$enableWrite = isModeEnabled($studyset, 'write');
?>

<div>
    <h1>Combined Study Mode</h1>
    <p class="lead">Go through the enabled study modes in order: flashcards, quiz, then write.</p>
    <p>Known terms: <?php echo count($knownTerms); ?> · Unknown terms: <?php echo count($unknownTerms); ?></p>
    <p>Enabled steps: <?php echo implode(', ', array_map('ucfirst', $enabledSteps)); ?></p>
</div>

<div id="combinedContainer" class="mb-4">
    <div class="card p-4 shadow-sm mb-4">
        <h2 id="combinedStepTitle"></h2>
        <p id="combinedStepDescription" class="text-muted"></p>
        <div id="combinedStepContent"></div>
    </div>
    <div class="progress my-3" role="progressbar" aria-label="Combined progress" aria-valuemin="0" aria-valuemax="100">
        <div id="combinedProgressbar" class="progress-bar" style="width: 0%"></div>
    </div>
    <p id="combinedProgressLabel" class="text-end text-muted"></p>
</div>

<div id="combinedCompletion" class="container text-center my-5" style="display:none;">
    <h1 class="mb-4">Combined mode complete</h1>
    <p id="combinedSummary" class="mb-4"></p>
    <div class="d-flex flex-column gap-3 align-items-center">
        <button id="combinedRepeatBtn" class="btn btn-primary rounded-pill" onclick="restartCombined()">Continue only
            unknown terms</button>
        <button class="btn btn-outline-primary rounded-pill" onclick="restartCombined(true)">Restart whole combined
            mode</button>
        <a href="?studyset=<?php echo urlencode($studysetURL); ?>" class="btn btn-outline-danger rounded-pill">Exit
            combined mode</a>
    </div>
</div>

<script>
    const allTerms = <?php echo json_encode($termList, JSON_UNESCAPED_UNICODE); ?>;
    const initialKnown = <?php echo json_encode(array_values($knownTerms), JSON_UNESCAPED_UNICODE); ?>;
    const modeSteps = <?php echo json_encode($enabledSteps, JSON_UNESCAPED_UNICODE); ?>;
    const studysetId = <?php echo json_encode($studysetId); ?>;
    const studysetURL = <?php echo json_encode($studysetURL); ?>;

    let currentStepIndex = 0;
    let knownIndices = new Set(initialKnown);
    let correctCount = 0;
    let wrongCount = 0;
    let currentModeItems = [];
    let currentItemIndex = 0;

    const stepTitle = document.getElementById('combinedStepTitle');
    const stepDescription = document.getElementById('combinedStepDescription');
    const stepContent = document.getElementById('combinedStepContent');
    const progressBar = document.getElementById('combinedProgressbar');
    const progressLabel = document.getElementById('combinedProgressLabel');
    const completionSection = document.getElementById('combinedCompletion');
    const completionSummary = document.getElementById('combinedSummary');
    const repeatButton = document.getElementById('combinedRepeatBtn');

    startCombined();

    function startCombined() {
        if (currentStepIndex >= modeSteps.length) {
            return showCompletion();
        }

        const mode = modeSteps[currentStepIndex];
        currentModeItems = getRemainingTerms();
        currentItemIndex = 0;

        if (currentModeItems.length === 0) {
            currentStepIndex += 1;
            return startCombined();
        }

        setStepHeader(mode);
        renderCurrentMode(mode);
        updateProgress();
    }

    function setStepHeader(mode) {
        stepTitle.textContent = mode.charAt(0).toUpperCase() + mode.slice(1);
        stepDescription.textContent = {
            flashcards: 'Mark terms you know or do not know by flipping the cards.',
            quiz: 'Choose the correct definition from four answers.',
            write: 'Type the definition of the term in your own words.',
        }[mode] || '';
    }

    function getRemainingTerms() {
        return allTerms.filter(item => !knownIndices.has(item.origIndex));
    }

    function renderCurrentMode(mode) {
        stepContent.innerHTML = '';
        if (mode === 'flashcards') {
            renderFlashcardStep();
        } else if (mode === 'quiz') {
            renderQuizStep();
        } else if (mode === 'write') {
            renderWriteStep();
        }
    }

    function renderFlashcardStep() {
        const item = currentModeItems[currentItemIndex];
        const card = document.createElement('div');
        card.className = 'container-fluid flashcard btn bg-body-tertiary shadow rounded-5 mb-3';
        card.style.height = 'calc(70svh - 110px - 80px)';
        card.style.display = 'flex';
        card.style.alignItems = 'center';
        card.style.justifyContent = 'center';
        card.style.cursor = 'pointer';
        card.textContent = item.term;
        let showingDefinition = false;
        card.addEventListener('click', () => {
            showingDefinition = !showingDefinition;
            card.textContent = showingDefinition ? item.definition : item.term;
        });

        const actions = document.createElement('div');
        actions.className = 'd-flex justify-content-between gap-3';
        actions.innerHTML = `
            <button class="btn btn-danger flex-fill" id="flashUnknownBtn">Don't know</button>
            <button class="btn btn-success flex-fill" id="flashKnowBtn">Know</button>
        `;

        stepContent.appendChild(card);
        stepContent.appendChild(actions);

        document.getElementById('flashUnknownBtn').addEventListener('click', () => nextFlashcard(false));
        document.getElementById('flashKnowBtn').addEventListener('click', () => nextFlashcard(true));
    }

    function nextFlashcard(isKnown) {
        const item = currentModeItems[currentItemIndex];
        if (isKnown) {
            knownIndices.add(item.origIndex);
            correctCount += 1;
        } else {
            wrongCount += 1;
        }
        saveTestResponse(item.origIndex, isKnown);
        currentItemIndex += 1;
        if (currentItemIndex >= currentModeItems.length) {
            currentStepIndex += 1;
            startCombined();
            return;
        }
        renderCurrentMode(modeSteps[currentStepIndex]);
        updateProgress();
    }

    function renderQuizStep() {
        const item = currentModeItems[currentItemIndex];
        const question = document.createElement('h3');
        question.textContent = `What is the definition of “${item.term}”?`;
        const answers = document.createElement('div');
        answers.className = 'list-group mt-4';

        const options = buildQuizOptions(item);
        options.forEach(option => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'list-group-item list-group-item-action';
            button.textContent = option.text;
            button.addEventListener('click', () => submitQuizAnswer(option.isCorrect));
            answers.appendChild(button);
        });

        stepContent.appendChild(question);
        stepContent.appendChild(answers);
    }

    function buildQuizOptions(currentItem) {
        const available = getRemainingTerms();
        const wrongOptions = available
            .map(item => item.definition)
            .filter(def => def !== currentItem.definition);
        const choices = [{ text: currentItem.definition, isCorrect: true }];

        while (choices.length < 4 && wrongOptions.length) {
            const index = Math.floor(Math.random() * wrongOptions.length);
            choices.push({ text: wrongOptions.splice(index, 1)[0], isCorrect: false });
        }
        return shuffleArray(choices);
    }

    function submitQuizAnswer(isCorrect) {
        const item = currentModeItems[currentItemIndex];
        if (isCorrect) {
            knownIndices.add(item.origIndex);
            correctCount += 1;
        } else {
            wrongCount += 1;
        }
        saveTestResponse(item.origIndex, isCorrect);
        currentItemIndex += 1;
        if (currentItemIndex >= currentModeItems.length) {
            currentStepIndex += 1;
            startCombined();
            return;
        }
        renderCurrentMode(modeSteps[currentStepIndex]);
        updateProgress();
    }

    function renderWriteStep() {
        const item = currentModeItems[currentItemIndex];
        const prompt = document.createElement('h3');
        prompt.textContent = `Write the definition for “${item.term}”.`;

        const textarea = document.createElement('textarea');
        textarea.id = 'writeAnswer';
        textarea.className = 'form-control mb-3';
        textarea.rows = 4;
        textarea.placeholder = 'Type your definition here...';

        const submit = document.createElement('button');
        submit.type = 'button';
        submit.className = 'btn btn-primary';
        submit.textContent = 'Submit answer';

        const message = document.createElement('div');
        message.id = 'writeFeedback';
        message.className = 'mt-3';
        message.style.display = 'none';

        submit.addEventListener('click', () => {
            const answer = normalizeText(textarea.value);
            if (!answer) {
                message.textContent = 'Please enter an answer.';
                message.className = 'alert alert-warning';
                message.style.display = 'block';
                return;
            }
            const correctAnswer = normalizeText(item.definition);
            const isCorrect = answer === correctAnswer;
            if (isCorrect) {
                message.textContent = 'Correct!';
                message.className = 'alert alert-success';
                knownIndices.add(item.origIndex);
                correctCount += 1;
            } else {
                message.textContent = `Incorrect. Correct answer: ${item.definition}`;
                message.className = 'alert alert-danger';
                wrongCount += 1;
            }
            message.style.display = 'block';
            saveTestResponse(item.origIndex, isCorrect);
            currentItemIndex += 1;
            if (currentItemIndex >= currentModeItems.length) {
                setTimeout(() => {
                    currentStepIndex += 1;
                    startCombined();
                }, 1100);
                return;
            }
            setTimeout(() => {
                renderCurrentMode(modeSteps[currentStepIndex]);
                updateProgress();
            }, 1100);
        });

        stepContent.appendChild(prompt);
        stepContent.appendChild(textarea);
        stepContent.appendChild(submit);
        stepContent.appendChild(message);
    }

    function updateProgress() {
        const totalSteps = modeSteps.length;
        const stepProgress = currentStepIndex + 1;
        const percent = totalSteps > 0 ? (stepProgress / totalSteps) * 100 : 100;
        progressBar.style.width = `${percent}%`;
        progressLabel.textContent = `Step ${Math.min(stepProgress, totalSteps)}/${totalSteps}`;
    }

    function showCompletion() {
        document.getElementById('combinedContainer').style.display = 'none';
        completionSection.style.display = 'block';
        const remaining = getRemainingTerms().length;
        completionSummary.textContent = `You answered ${correctCount} correctly and ${wrongCount} incorrectly. ${remaining} unknown term${remaining === 1 ? '' : 's'} remain.`;
        repeatButton.style.display = remaining > 0 ? 'inline-flex' : 'none';
    }

    function saveTestResponse(cardIndex, isKnown) {
        fetch('Components/saveTestResponse.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'same-origin',
            body: JSON.stringify({ studysetId, cardIndex, isKnown })
        }).catch(err => console.error('Could not save response', err));
    }

    function restartCombined(fullRestart = false) {
        if (fullRestart) {
            knownIndices = new Set(initialKnown);
        }
        correctCount = 0;
        wrongCount = 0;
        currentStepIndex = 0;
        document.getElementById('combinedContainer').style.display = 'block';
        completionSection.style.display = 'none';
        startCombined();
    }

    function normalizeText(text) {
        return text.trim().replace(/\s+/g, ' ').replace(/[\.,;!?]/g, '').toLowerCase();
    }

    function shuffleArray(array) {
        for (let i = array.length - 1; i > 0; i--) {
            const j = Math.floor(Math.random() * (i + 1));
            [array[i], array[j]] = [array[j], array[i]];
        }
        return array;
    }
</script>