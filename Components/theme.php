<?php
    if (isset($_POST["theme"])) {
        $theme = $_POST["theme"];
    } elseif (isset($_COOKIE["theme"])) {
        $theme = $_COOKIE["theme"];
    } else {
        $theme = "light";
    }
    $theme = strtolower($theme);
    setcookie("theme", $theme, time() + 60 * 60 * 24 * 30);
?>

<html lang="en" <?php echo "data-bs-theme = " . $theme ?>>