<script>
    function progressbar() {
        const element = document.getElementById("progressbar");
        let width = parseFloat(element.style.width);
        width += 10;
        element.style.width = width + "%";
    }

    function flipCard() {
        const element = document.getElementById("flashcard");
        let animation = null;
        clearInterval(animation);
        animation = setInterval(frame, 5);
        let width = 0.9;
        let flipped = false;

        function frame() {
            if (width >= 1) {
                clearInterval(animation);
            } else {
                if (!flipped) {
                    width -= 0.05;
                    element.style.transform = "scaleX(" + width + ")"
                    if (width <= 0) {
                        flipped = true
                        element.children[0].innerHTML = "Test";
                    }
                } else {
                    width += 0.05;
                    element.style.transform = "scaleX(" + width + ")"
                }
            }
        }
    }
</script>

<div>
    <h1 class="">Flashcards</h1>
    <h2>5/14</h2>
    <div class="progress" role="progressbar" aria-label="Basic example" aria-valuenow="35.7142857142857"
        aria-valuemin="0" aria-valuemax="100">
        <div id="progressbar" class="progress-bar" style="width: 0%"></div>
    </div>
</div>
<br>
<div id="flashcard" class="container-fluid flashcard btn bg-body-tertiary shadow rounded-5" onclick="flipCard()">
    <h1>Term</h1>
</div>
<div class="flashcard-buttons d-flex justify-content-between my-4 gap-3 width-100">
    <button class="btn btn-danger shadow rounded-5" onclick="progressbar()">
        <h2>Don't Know</h2>
    </button>
    <button class="btn btn-success shadow rounded-5">
        <h2>Know</h2>
    </button>

</div>

<style>
    .flashcard {
        display: flex;
        justify-content: center;
        align-items: center;
        height: calc(70svh - 60px - 80px);
    }

    .flashcard-buttons>button {
        flex: 1;
        height: calc(30svh - 60px - 80px);
    }
</style>