<?php
require_once __DIR__ . '/../Components/termsHandler.php';
$data = get_terms_and_file($studyset);
$terms = $data['terms'] ?? [];
?>

<header>
    <h1><?php echo htmlspecialchars($studyset['name'] ?? '', ENT_QUOTES, 'UTF-8'); ?></h1>
    <p><?php echo htmlspecialchars($studyset['description'] ?? '', ENT_QUOTES, 'UTF-8'); ?></p>
</header>

<!--Studying Modes-->
<div class="d-flex gap-4 my-4 flex-wrap">
    <a class="btn btn-primary" href="?studyset=<?php echo $studysetURL ?>&test=flashcards">Flashcards</a>
    <a class="btn btn-primary" href="?studyset=<?php echo $studysetURL ?>&test=quiz">Quiz</a>
    <a class="btn btn-primary" href="?studyset=<?php echo $studysetURL ?>&test=write">Write</a>
    <a class="btn btn-primary" href="?studyset=<?php echo $studysetURL ?>&test=combined">Combined</a>
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