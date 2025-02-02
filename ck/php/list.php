<?php
include __DIR__ . '/../config/config.php'; // Správne načítanie konfigurácie

$result = mysqli_query($conn, "SELECT * FROM customers");
?>
<!DOCTYPE html>
<html lang="sk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zoznam zákazníkov</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/global.css">
</head>
<body>
    <header>
        <nav>
            <div class="logo">Môj Projekt</div>
            <ul class="menu">
                <li><a href="<?php echo BASE_URL; ?>index.php">Domov</a></li>
                <li><a href="<?php echo BASE_URL; ?>php/view.php">Pridať zákazníka</a></li>
            </ul>
        </nav>
    </header>
    <main>
        <section class="content">
            <h2>Zoznam zákazníkov</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Meno</th>
                        <th>Email</th>
                        <th>Akcie</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = mysqli_fetch_assoc($result)) { ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['id']); ?></td>
                        <td><?php echo htmlspecialchars($row['name']); ?></td>
                        <td><?php echo htmlspecialchars($row['email']); ?></td>
                        <td>
                            <a href="<?php echo BASE_URL; ?>php/edit.php?id=<?php echo $row['id']; ?>" class="button edit">Upraviť</a>
                            <a href="<?php echo BASE_URL; ?>php/delete.php?id=<?php echo $row['id']; ?>" class="button delete">Vymazať</a>
                        </td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>
        </section>
    </main>
    <footer>
        <p>&copy; 2025 Môj Projekt. Všetky práva vyhradené.</p>
    </footer>
</body>
</html>
