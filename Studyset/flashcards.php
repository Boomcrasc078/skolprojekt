<div class="container-fluid flashcard">
    Hello
</div>
<div class="flashcard-buttons d-flex justify-content-between my-4 gap-3 width-100">
    <button class="btn btn-secondary">Previous</button>
    <button class="btn btn-secondary">Flip</button>
    <button class="btn btn-secondary">Next</button>

</div>

<style>
    .flashcard {
        display: flex;
        justify-content: center;
        align-items: center;
        color: white;
        border-radius: 2rem;
        height: calc(70svh - 60px);
        background-color: var(--bs-secondary);
        font-size: 5svw;
    }

    .flashcard-buttons>button {
        flex: 1;
        height: calc(30svh - 60px);
        font-size: 3svw;
    }
</style>