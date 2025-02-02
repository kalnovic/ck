<?php include __DIR__ . '/../config/config.php'; ?>
<?php include 'php/header.php'; ?>
<link rel="stylesheet" href="<?php echo BASE_URL; ?><?php echo BASE_URL; ?>/css/global.css">
    <h1>Vitajte na hlavnej stránke</h1>
    <?php
    // Zobrazenie správy o výsledku (ak existuje)
    if (isset($_GET['message'])) {
        $message = urldecode($_GET['message']);
        $class = (strpos($message, 'Chyba') === false) ? 'success' : 'error';
        echo "<div class='message $class'>$message</div>";
    }
    ?>

<?php include 'php/footer.php'; ?>