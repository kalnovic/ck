<?php
require_once '../config/config.php'; // Načítanie prihlasovacích údajov

// Pripojenie k databáze
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Kontrola pripojenia
if ($conn->connect_error) {
    die("Pripojenie zlyhalo: " . $conn->connect_error);
}

// Načítanie správy z URL parametra
$message = isset($_GET['message']) ? urldecode($_GET['message']) : '';
$messageType = isset($_GET['messageType']) ? $_GET['messageType'] : ''; // Typ správy (success/error)

// Spracovanie vyhľadávania, zoradenia a filtra
$search = isset($_GET['search']) ? $_GET['search'] : '';
$order = isset($_GET['order']) ? $_GET['order'] : 'ASC';
$filterTyp = isset($_GET['filterTyp']) ? $_GET['filterTyp'] : '';
$filterAkt = isset($_GET['filterAkt']) ? $_GET['filterAkt'] : '';

// Základný SQL dotaz
$sql = "SELECT * FROM adress WHERE priezvisko LIKE ?";
$params = ["%$search%"];

// Pridanie filtra pre typ
if ($filterTyp && $filterTyp !== 'všetko') {
    $sql .= " AND typ = ?";
    $params[] = $filterTyp;
}

// Pridanie filtra pre akt
if ($filterAkt === '1') {
    $sql .= " AND akt = 1";
}

// Pridanie zoradenia
$sql .= " ORDER BY priezvisko $order";

// Príprava a vykonanie dotazu
$stmt = $conn->prepare($sql);
$types = str_repeat('s', count($params)); // Typy parametrov pre bind_param
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
?>

<?php include 'header.php'; ?>

    <h1>Zoznam adries</h1>

    <!-- Zobrazenie správy -->
    <?php if (!empty($message)) : ?>
        <div class="message <?php echo $messageType; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <!-- Vyhľadávanie, filter a zoradenie -->
    <form method="GET" action="list.php" class="filter-form">
        <label for="search">Vyhľadať podľa priezviska:</label>
        <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>">

        <label for="filterTyp">Filter podľa typu:</label>
        <select id="filterTyp" name="filterTyp" onchange="this.form.submit()">
            <option value="všetko" <?php echo ($filterTyp === 'všetko') ? 'selected' : ''; ?>>Všetko</option>
            <option value="D" <?php echo ($filterTyp === 'D') ? 'selected' : ''; ?>>Dôchodca</option>
            <option value="K" <?php echo ($filterTyp === 'K') ? 'selected' : ''; ?>>Klient</option>
            <option value="Z" <?php echo ($filterTyp === 'Z') ? 'selected' : ''; ?>>Zamestnanec</option>
        </select>

        <label for="filterAkt">Len aktívne záznamy:</label>
        <input type="checkbox" id="filterAkt" name="filterAkt" value="1" <?php echo ($filterAkt === '1') ? 'checked' : ''; ?> onchange="this.form.submit()">

        <label for="order">Zoradiť podľa priezviska:</label>
        <select id="order" name="order" onchange="this.form.submit()">
            <option value="ASC" <?php echo ($order == 'ASC') ? 'selected' : ''; ?>>Vzostupne</option>
            <option value="DESC" <?php echo ($order == 'DESC') ? 'selected' : ''; ?>>Zostupne</option>
        </select>

        <button type="submit">Aplikovať</button>
    </form>

    <!-- Tabuľka s údajmi -->
    <table>
        <thead>
            <tr>
                <th>Meno</th>
                <th>Priezvisko</th>
                <th>Ulica</th>
                <th>Mesto</th>
                <th>PSČ</th>
                <th>Typ</th>
                <th>Poznámka</th>
                <th>Telefón</th>
                <th>Email</th>
                <th>Akt</th>
                <th>Akcie</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $result->fetch_assoc()) : ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['meno']); ?></td>
                    <td><?php echo htmlspecialchars($row['priezvisko']); ?></td>
                    <td><?php echo htmlspecialchars($row['ulica']); ?></td>
                    <td><?php echo htmlspecialchars($row['mesto']); ?></td>
                    <td><?php echo htmlspecialchars($row['psc']); ?></td>
                    <td><?php echo htmlspecialchars($row['typ']); ?></td>
                    <td><?php echo htmlspecialchars($row['poznamka'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($row['tel'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($row['email'] ?? ''); ?></td>
                    <td><?php echo $row['akt'] ? 'Áno' : 'Nie'; ?></td>
                    <td>
                        <a href="edit.php?id=<?php echo $row['id']; ?>">Upraviť</a> | 
                        <a href="delete.php?id=<?php echo $row['id']; ?>" onclick="return confirm('Naozaj chcete zmazať tento záznam?');">Zmazať</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

<!-- Tlačidlá -->
<div class="button-container">
    <button onclick="window.location.href='clients.php'">Nový klient</button>
    <button onclick="window.location.href='../index.php'">Koniec</button>
</div>

<?php include 'footer.php'; ?>