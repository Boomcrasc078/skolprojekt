<?php
require 'Components/databaseConnection.php';
require 'Components/userHandler.php';

requireUser();

$user = getUser();
$error = "";
$success = "";

// Handle username change
if ($_SERVER['REQUEST_METHOD'] == "POST" && isset($_POST['action'])) {
    if ($_POST['action'] == 'changeUsername' && isset($_POST['newUsername'])) {
        $result = changeUsername($_POST['newUsername']);
        if ($result === "success") {
            $success = "Username updated successfully!";
            $user = getUser();
        } else {
            $error = $result;
        }
    }

    // Handle password change
    if ($_POST['action'] == 'changePassword' && isset($_POST['currentPassword']) && isset($_POST['newPassword']) && isset($_POST['confirmPassword'])) {
        $result = changePassword($_POST['currentPassword'], $_POST['newPassword'], $_POST['confirmPassword']);
        if ($result === "success") {
            $success = "Password updated successfully!";
            $user = getUser();
        } else {
            $error = $result;
        }
    }

    // Handle account deletion
    if ($_POST['action'] == 'deleteAccount' && isset($_POST['confirmDelete'])) {
        if ($_POST['confirmDelete'] !== 'YES') {
            $error = "You must type 'YES' to confirm account deletion.";
        } else {
            deleteAccount();
        }
    }

    // Handle sign out
    if ($_POST['action'] == 'signOut') {
        signOut();
    }
}
?>

<!doctype html>

<head>
    <?php include 'Components/theme.php'; ?>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>QUIS | Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <style>
        .profile-container {
            max-width: 600px;
            margin: 40px auto;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            background-color: var(--bs-body-bg);
        }

        .profile-header {
            text-align: center;
            margin-bottom: 40px;
            border-bottom: 2px solid var(--bs-border-color);
            padding-bottom: 20px;
        }

        .profile-header h1 {
            font-weight: 600;
            margin-bottom: 10px;
        }

        .profile-header p {
            color: var(--bs-secondary-color);
            margin: 0;
        }

        .section-title {
            font-size: 1.3rem;
            font-weight: 600;
            margin-top: 30px;
            margin-bottom: 20px;
            color: var(--bs-body-color);
        }

        .form-section {
            margin-bottom: 30px;
            padding: 20px;
            background-color: var(--bs-body-secondary);
            border-radius: 10px;
        }

        .alert {
            margin-bottom: 20px;
        }

        .danger-zone {
            background-color: rgba(var(--bs-danger-rgb), 0.1);
            border-left: 4px solid var(--bs-danger);
            padding: 20px;
            border-radius: 8px;
            margin-top: 40px;
        }

        .danger-zone h3 {
            color: var(--bs-danger);
            margin-bottom: 15px;
        }

        .btn-group-custom {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }

        .btn-group-custom .btn {
            flex: 1;
        }
    </style>
</head>

<body>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI"
        crossorigin="anonymous"></script>

    <?php include "Components/navbar.php" ?>

    <main>
        <div class="profile-container">
            <div class="profile-header">
                <h1>My Profile</h1>
            </div>

            <!-- Alert Messages -->
            <?php if (!empty($success)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo $success; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <!-- Change Username Section -->
            <div class="form-section">
                <h2 class="section-title">Change Username</h2>
                <p class="text-secondary">Current username:
                    <strong><?php echo htmlspecialchars($user->username); ?></strong>
                </p>
                <form method="post">
                    <input type="hidden" name="action" value="changeUsername">
                    <div class="mb-3">
                        <label for="newUsername" class="form-label">New Username</label>
                        <input type="text" class="form-control" id="newUsername" name="newUsername"
                            placeholder="Enter new username" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Update Username</button>
                </form>
            </div>

            <!-- Change Password Section -->
            <div class="form-section">
                <h2 class="section-title">Change Password</h2>
                <form method="post">
                    <input type="hidden" name="action" value="changePassword">
                    <div class="mb-3">
                        <label for="currentPassword" class="form-label">Current Password</label>
                        <input type="password" class="form-control" id="currentPassword" name="currentPassword"
                            placeholder="Enter current password" required>
                    </div>
                    <div class="mb-3">
                        <label for="newPassword" class="form-label">New Password</label>
                        <input type="password" class="form-control" id="newPassword" name="newPassword"
                            placeholder="Enter new password" required>
                    </div>
                    <div class="mb-3">
                        <label for="confirmPassword" class="form-label">Confirm New Password</label>
                        <input type="password" class="form-control" id="confirmPassword" name="confirmPassword"
                            placeholder="Confirm new password" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Update Password</button>
                </form>
            </div>

            <!-- Sign Out Section -->
            <form method="post" class="form-section">
                <h2 class="section-title">Sign Out</h2>
                <p class="text-secondary mb-3">Securely end your current session.</p>
                <input type="hidden" name="action" value="signOut">
                <button type="submit" class="btn btn-warning">Sign Out</button>
            </form>

            <!-- Delete Account Section -->
            <div class="danger-zone">
                <h3>Danger Zone</h3>
                <p class="text-muted mb-3">Permanently delete your account and all associated data. This action cannot
                    be undone.</p>
                <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteAccountModal">Delete
                    Account</button>
            </div>
        </div>
    </main>

    <!-- Delete Account Confirmation Modal -->
    <div class="modal fade" id="deleteAccountModal" tabindex="-1" aria-labelledby="deleteAccountModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="deleteAccountModalLabel">Delete Account</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-danger"><strong>Warning:</strong> This action will permanently delete your account
                        and all your study sets. This cannot be undone.</p>
                    <p>To confirm account deletion, type <strong>YES</strong> in the field below:</p>
                    <form id="deleteForm" method="post">
                        <input type="hidden" name="action" value="deleteAccount">
                        <div class="mb-3">
                            <input type="text" class="form-control" id="confirmDelete" name="confirmDelete"
                                placeholder="Type YES to confirm" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger"
                        onclick="document.getElementById('deleteForm').submit();">Delete Account</button>
                </div>
            </div>
        </div>
    </div>

</body>

</html>