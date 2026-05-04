<?php
require_once __DIR__ . '/../Components/termsHandler.php';
require_once __DIR__ . '/../Components/studysetHandler.php';

$terms = getTerms($studyset);
$shareURL = sprintf(
    '%s://%s%s?studyset=%s',
    (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http',
    $_SERVER['HTTP_HOST'],
    $_SERVER['PHP_SELF'],
    urlencode($studysetURL)
);
?>

<header>
    <h1><i class="bi bi-clipboard"></i> <?php echo htmlspecialchars($studyset['name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
    </h1>
    <p><?php echo htmlspecialchars($studyset['description'] ?? '', ENT_QUOTES, 'UTF-8'); ?></p>
</header>

<!--Studying Modes-->
<div class="d-flex gap-4 my-4 flex-wrap">
    <?php if (isModeEnabled($studyset, 'flashcards')): ?>
        <a class="btn btn-primary" href="?studyset=<?php echo $studysetURL ?>&test=flashcards">Flashcards</a>
    <?php endif; ?>
    <?php if (isModeEnabled($studyset, 'quiz')): ?>
        <a class="btn btn-primary" href="?studyset=<?php echo $studysetURL ?>&test=quiz">Quiz</a>
    <?php endif; ?>
    <?php if (isModeEnabled($studyset, 'write')): ?>
        <a class="btn btn-primary" href="?studyset=<?php echo $studysetURL ?>&test=write">Write</a>
    <?php endif; ?>
</div>

<!--Terms and Definitions-->
<div class="table-responsive" style="width: auto;">
    <table class="table" style="table-layout: auto;">
        <thead>
            <tr>
                <th scope="col">#</th>
                <th scope="col">Term</th>
                <th scope="col">Definition</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($terms)): ?>
                <tr>
                    <td colspan="3">No terms yet.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($terms as $i => $t): ?>
                    <tr>
                        <th scope="row"><?php echo $i + 1; ?></th>
                        <td><?php echo htmlspecialchars($t['term'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($t['definition'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php if (isset($_GET['share'])) { ?>
    <div class="position-fixed top-50 start-50 translate-middle container h-50 bg-body-tertiary rounded rounded-4 p-5 shadow"
        style="min-width: 320px; max-width: 640px;">
        <h2>Share your studyset with others</h2>
        <div class="input-group my-3">
            <input id="share-url-input" type="text" readonly class="form-control"
                value="<?php echo htmlspecialchars($shareURL, ENT_QUOTES, 'UTF-8'); ?>">
            <button id="copy-share-url-btn" type="button" class="btn btn-outline-primary">Copy</button>
        </div>
        <div id="copy-status" class="text-success" style="display:none;">Link copied to clipboard.</div>
        <a href="?studyset=<?php echo urlencode($studysetURL); ?>" class="btn btn-secondary mt-3">Close</a>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var copyButton = document.getElementById('copy-share-url-btn');
            var shareInput = document.getElementById('share-url-input');
            var copyStatus = document.getElementById('copy-status');

            if (!copyButton || !shareInput) {
                return;
            }

            copyButton.addEventListener('click', function () {
                var url = shareInput.value;
                navigator.clipboard.writeText(url).then(function () {
                    copyStatus.style.display = 'block';
                    copyStatus.textContent = 'Link copied to clipboard.';
                    copyButton.textContent = 'Copied';
                    setTimeout(function () {
                        copyStatus.style.display = 'none';
                        copyButton.textContent = 'Copy';
                    }, 1500);
                }).catch(function () {
                    copyStatus.style.display = 'block';
                    copyStatus.textContent = 'Unable to copy link.';
                    copyStatus.classList.remove('text-success');
                    copyStatus.classList.add('text-danger');
                });
            });
        });
    </script>
    <?php
} ?>