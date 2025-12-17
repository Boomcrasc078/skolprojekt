<!doctype html>

<head>
    <?php include 'Components/theme.php'; ?>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Bootstrap demo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
</head>

<body>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI"
        crossorigin="anonymous"></script>

    <?php include "Components/navbar.php" ?>

    <main>
        <div class="container mt-3">
            <div class="px-4 py-5 my-5 text-center"> <img class="d-block mx-auto mb-4"
                    src="/docs/5.3/assets/brand/bootstrap-logo.svg" alt="" width="72" height="57">
                <h1 class="display-5 fw-bold text-body-emphasis">Quis, Study rehearsal made easy</h1>
                <div class="col-lg-6 mx-auto">

                    <p class="lead mb-4">Make it easier to study and rehearse for tests with flashcards, quizzes and
                        more. All using Quis!
                    </p>

                    <div class="d-grid gap-2 d-sm-flex justify-content-sm-center"> <button type="button"
                            class="btn btn-primary btn-lg px-4 gap-3">Sign up</button> <button type="button"
                            class="btn btn-outline-secondary btn-lg px-4">Log in</button>
                    </div>
                </div>
            </div>
        </div>


    </main>

</body>

</html>