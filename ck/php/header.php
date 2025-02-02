<?php include __DIR__ . '/../config/config.php'; ?>
<!-- header.php -->
<!DOCTYPE html>
<html lang="sk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Môj Projekt</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?><?php echo BASE_URL; ?>../css/global.css">
</head>
<body>
    <!-- Header s menu -->
    <header>
        <nav>
            <ul>
                <li><a href="<?php echo BASE_URL; ?><?php echo BASE_URL; ?>../index.php">Domov</a></li>
                <li>
                    <a href="<?php echo BASE_URL; ?><?php echo BASE_URL; ?>#">Stravovanie</a>
                    <ul>
                        <li><a href="<?php echo BASE_URL; ?><?php echo BASE_URL; ?>../php/orders.php">Nová objednávka</a></li>
                        <li><a href="<?php echo BASE_URL; ?><?php echo BASE_URL; ?>../php/sum_orders.php">Spracovanie objednávok</a></li>
                        <li><a href="<?php echo BASE_URL; ?><?php echo BASE_URL; ?>../php/delivery.php">Rozvoz</a></li>
                        <li><a href="<?php echo BASE_URL; ?><?php echo BASE_URL; ?>../php/price.php">Ceny stravovania a pobytu</a></li>
                    </ul>
                </li>
                <li><a href="<?php echo BASE_URL; ?><?php echo BASE_URL; ?>#">Financie</a></li>
                <li>
                    <a href="<?php echo BASE_URL; ?><?php echo BASE_URL; ?>#">Zákazníci</a>
                    <ul>
                        <!--li><a href="<?php echo BASE_URL; ?><?php echo BASE_URL; ?>../php/view.php">Nový zákazník</a></li-->
                        <li><a href="<?php echo BASE_URL; ?><?php echo BASE_URL; ?>../php/list.php">Zoznam zákazníkov</a></li>
                    </ul>
                </li>
                <li><a href="<?php echo BASE_URL; ?><?php echo BASE_URL; ?>#">Výstupy</a></li>
            </ul>
        </nav>
    </header>

    <!-- Hlavný obsah -->
    <main>