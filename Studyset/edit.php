<?php
require_once __DIR__ . '/../Components/termsHandler.php';

$terms = getTerms($studyset);
$saveError = null;

function createTermsArray($termsIn, $defsIn)
{
    $count = max(count($termsIn), count($defsIn));

    for ($i = 0; $i < $count; $i++) {
        
        $term = isset($termsIn[$i]) ? trim($termsIn[$i]) : '';
        $def = isset($defsIn[$i]) ? trim($defsIn[$i]) : '';

        if ($term == '') {
            continue;
        }

        $newTerms[] = ['term' => $term, 'definition' => $def];
    }
    return $newTerms;
}

// Handle saving terms via POST (inline table submit)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // collect terms from term[] and definition[] arrays (ensures indices match)
    $termsIn = $_POST['term'] ?? [];
    $defsIn = $_POST['definition'] ?? [];
    $newTerms = createTermsArray($termsIn, $defsIn);

    $name = trim($_POST['studyset_name'] ?? '');
    $description = trim($_POST['studyset_description'] ?? '');

    // save using handler (will update file + DB)
    $result = save_terms($studyset, $newTerms, $name, $description);
    header('Location: studyset.php?studyset=' . urlencode($studyset['studysetURL']));
    exit;

}
?>

<form method="post">
    <?php if (!empty($saveError)): ?>
        <div class="alert alert-danger" role="alert"><?php echo htmlspecialchars($saveError, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>
    <header class="mb-5">
        <h1 class="display-1 mb-5">Edit Studyset</h1>
        <div class="form-floating mb-3">
            <input name="studyset_name" type="text" class="form-control form-control-lg" placeholder="Studyset Name"
                id="floatingInput" value="<?php echo htmlspecialchars($studyset['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            <label for="floatingInput">Studyset Name</label>
        </div>
        <div class="form-floating">
            <textarea name="studyset_description" style="height: 200px;" class="form-control form-control-lg"
                placeholder="Description"
                id="floatingTextarea"><?php echo htmlspecialchars($studyset['description'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
            <label for="floatingTextarea">Description</label>
        </div>
    </header>
    <div>
        <h2>Terms</h2>
        <table class="table" id="termsTable">
            <thead>
                <tr>
                    <th scope="col">#</th>
                    <th scope="col">Term</th>
                    <th scope="col">Definition</th>
                    <th scope="col">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($terms)): ?>
                    <tr class="no-terms-row">
                        <td colspan="4">No terms yet.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($terms as $i => $t): ?>
                        <tr>
                            <th scope="row"><?php echo $i + 1 ?></th>
                            <td>
                                <input name="term[]" class="form-control"
                                    value="<?php echo htmlspecialchars($t['term'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                            </td>
                            <td>
                                <input name="definition[]" class="form-control"
                                    value="<?php echo htmlspecialchars($t['definition'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                            </td>
                            <td>
                                <button type="button" class="btn btn-sm btn-danger remove-row">Remove</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        <div class="d-flex gap-2 mt-3">
            <button id="addRow" type="button" class="btn btn-secondary">Add row</button>
            <button type="submit" class="btn btn-primary">Save changes</button>
        </div>
    </div>
</form>

<script>
    (function () {
        const table = document.getElementById('termsTable');
        const tbody = table.querySelector('tbody');

        function updateIndexes() {
            const rows = tbody.querySelectorAll('tr');
            rows.forEach((r, i) => {
                const th = r.querySelector('th');
                if (th) th.textContent = i + 1;
            });
        }

        document.getElementById('addRow').addEventListener('click', function () {
            // remove "no terms" placeholder if present
            const placeholder = tbody.querySelector('.no-terms-row');
            if (placeholder) placeholder.remove();

            const tr = document.createElement('tr');
            tr.innerHTML = `
                <th scope="row"></th>
                <td><input name="term[]" class="form-control" value=""></td>
                <td><input name="definition[]" class="form-control" value=""></td>
                <td><button type="button" class="btn btn-sm btn-danger remove-row">Remove</button></td>
            `;
            tbody.appendChild(tr);
            updateIndexes();
        });

        tbody.addEventListener('click', function (e) {
            if (e.target && e.target.classList.contains('remove-row')) {
                const tr = e.target.closest('tr');
                if (tr) tr.remove();
                if (!tbody.querySelector('tr')) {
                    const r = document.createElement('tr');
                    r.className = 'no-terms-row';
                    r.innerHTML = '<td colspan="4">No terms yet.</td>';
                    tbody.appendChild(r);
                }
                updateIndexes();
            }
        });

        // init: wire up existing remove buttons
        document.querySelectorAll('.remove-row').forEach(btn => {
            btn.addEventListener('click', function () {
                const tr = btn.closest('tr'); if (tr) tr.remove(); updateIndexes();
            });
        });

    })();
</script>