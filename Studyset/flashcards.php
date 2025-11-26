<div>
    <h1 class="">Flashcards</h1>
    <h2>5/14</h2>
    <div class="progress" role="progressbar" aria-label="Basic example" aria-valuenow="35.7142857142857" aria-valuemin="0"
        aria-valuemax="100">
        <div class="progress-bar" style="width: 35.7142857142857%"></div>
    </div>
</div>
<br>
<div class="container-fluid flashcard btn btn-primary">
    <h1>Term</h1>
</div>
<div class="flashcard-buttons d-flex justify-content-between my-4 gap-3 width-100">
    <button class="btn btn-danger">
        <h2>Don't Know</h2>
    </button>
    <button class="btn btn-success">
        <h2>Know</h2>
    </button>

</div>

<style>
    .flashcard {
        display: flex;
        justify-content: center;
        align-items: center;
        color: white;
        border-radius: 2rem;
        height: calc(70svh - 60px - 80px);
    }

    .flashcard-buttons>button {
        flex: 1;
        height: calc(30svh - 60px - 80px);
        border-radius: 2rem;
    }
</style>