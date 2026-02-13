<div class="container">
    <div class="d-flex justify-content-between">
        <h2>Your Studysets</h2>
        <a class="btn btn-primary btn-lg rounded rounded-pill shadow" href="Studyset/new.php">New</a>
    </div>
    <div class="mt-3">
        <?php foreach ($studysets as $studyset) { ?>
            <div class="card mb-3 shadow">
                <div class="card-header">
                    <?php echo $studyset['createdAt'] ?>
                </div>
                <div class="card-body">
                    <h5 class="card-title"><?php echo $studyset['name'] ?></h5>
                    <p class="card-text"><?php echo $studyset['description'] ?></p>
                </div>
                <div class="card-footer d-flex justify-content-between g-5">
                    <div>
                        <a href="studyset.php?studyset=<?php echo $studyset['studysetURL'] ?>"
                            class="btn btn-primary">Learn</a>
                        <a href="studyset.php?studyset=<?php echo $studyset['studysetURL'] ?>&edit=true"
                            class="btn btn-outline-primary">Edit</a>
                    </div>
                    <div>
                        <a href="Studyset/delete.php?studyset=<?php echo $studyset['studysetURL'] ?>"
                            class="btn btn-outline-danger">Delete</a>
                    </div>
                </div>
            </div>
        <?php } ?>
    </div>
</div>