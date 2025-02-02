<?php include 'php/header.php'; ?>
<link rel="stylesheet" href="/css/global.css">

<nav>
    <div class="logo">Môj Projekt</div>
    <ul class="menu">
        <li><a href="index.php">Domov</a></li>
        <li><a href="php/view.php">Pridať zákazníka</a></li>
    </ul>
</nav>

<main class="content">
    <h1>Vitajte na hlavnej stránke</h1>
    <?php
    // Zobrazenie správy o výsledku (ak existuje)
    if (isset($_GET['message'])) {
        $message = urldecode($_GET['message']);
        $class = (strpos($message, 'Chyba') === false) ? 'success' : 'error';
        echo "<div class='message $class'>$message</div>";
    }
    ?>
</main>

<?php include 'php/footer.php'; ?>
