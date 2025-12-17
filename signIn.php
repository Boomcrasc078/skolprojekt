<?php
include 'Components/userHandler.php';

if ($_SERVER['REQUEST_METHOD'] == "POST") {
    $enteredUsername = $_POST['username'];
    $enteredPassword = $_POST['password'];

    $error = signIn($enteredUsername, $enteredPassword);
}
?>

<!doctype html>

<head>
    <?php include 'Components/theme.php'; ?>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>QUIS | SIGN IN</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
</head>

<body>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI"
        crossorigin="anonymous"></script>

    <?php include "Components/navbar.php" ?>

    <main>
        <form class="container p-5 position-fixed top-50 start-50 translate-middle z-n1" method="post"
            action="<?php htmlspecialchars($_SERVER['PHP_SELF']); ?>">
            <h1 class="mb-3">Sign In</h1>
            <div class="mb-3">
                <label for="exampleInputEmail1" class="form-label">Username</label>
                <input type="text" class="form-control" id="inputUsername" name="username" required>
            </div>
            <div class="mb-5">
                <label for="exampleInputPassword1" class="form-label">Password</label>
                <input type="password" class="form-control" id="inputPassword" name="password" required>
            </div>
            <div id="error" class="form-text">
                <?php
                if (isset($error)) {
                    echo $error;
                }
                ?>
            </div>
            <input type="submit" class="btn btn-primary" value="Sign In"></input>
            <a class="btn btn-outline-secondary" href="signUp.php">Don't have an account?</a>
        </form>
    </main>

</body>

</html>