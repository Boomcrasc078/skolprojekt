<?php require 'Components/studysetHandler.php'; ?>

<div class="mt-5 container d-flex flex-column">
    <h1>Welcome back <?php echo $user->username ?></h1>
    <div class="border rounded-5 p-5 mt-3 shadow">
        <?php include 'Components/studysets.php'; ?>
    </div>
</div>