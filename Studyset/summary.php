<header>
    <h1><?php echo $studyset['name']; ?></h1>
    <p><?php echo $studyset['description']; ?></p>
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
                <th scope="col">Term</th>
                <th scope="col">Definition</th>
                <th scope="col" style="width: 0;"></th>
            </tr>
        </thead>
        <tbody>
            <tr>

                <th scope="row">1</th>
                <td>Definition 1</td>
                <td><a class="btn btn-secondary" href="">Edit</a></td>
            </tr>
            <tr>
                <th scope="row">2</th>
                <td>Definition 2</td>
                <td><a class="btn btn-secondary" href="">Edit</a></td>
            </tr>
        </tbody>
    </table>
</div>