    <?php
    session_start();

    // Clear current conversation
    $_SESSION['conversations'] = [];

    // Redirect back to main page
    header('Location: index.php');
    exit;
    ?>