<?php
session_start(); // Použitie session na zapamätanie obdobia

// Pripojenie k databáze
require_once '../config/config.php'; // Načítanie prihlasovacích údajov
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Kontrola pripojenia
if ($conn->connect_error) {
    die("Pripojenie zlyhalo: " . $conn->connect_error);
}

// Spracovanie údajov z formulára
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['setPeriod'])) {
        // Uloženie obdobia do session
        $_SESSION['startDate'] = $_POST['startDate'];
        $_SESSION['endDate'] = $_POST['endDate'];
    } elseif (isset($_POST['save'])) {
        // Uloženie objednávok
        $id_adress = $_POST['id_adress'];
        $dates = $_POST['dates'];
        $obed_a = $_POST['obed_a'];
        $obed_b = $_POST['obed_b'];
        $obed_d = $_POST['obed_d'];

        $errors = []; // Pole pre chybové správy

        foreach ($dates as $index => $date) {
            // Kontrola, či už existuje záznam s rovnakým dátumom a id_adress
            $sqlCheck = "SELECT id FROM orders WHERE id_adress = ? AND datum = ?";
            $stmtCheck = $conn->prepare($sqlCheck);
            $stmtCheck->bind_param("is", $id_adress, $date);
            $stmtCheck->execute();
            $stmtCheck->store_result();

            if ($stmtCheck->num_rows > 0) {
                // Záznam už existuje
                $errors[] = "Pre dátum $date už existuje záznam pre vybraného klienta.";
            } else {
                // Záznam neexistuje, môžeme ho uložiť
                $sqlInsert = "INSERT INTO orders (id_adress, datum, obed_a, obed_b, obed_d) VALUES (?, ?, ?, ?, ?)";
                $stmtInsert = $conn->prepare($sqlInsert);
                $stmtInsert->bind_param("isiii", $id_adress, $date, $obed_a[$index], $obed_b[$index], $obed_d[$index]);

                if (!$stmtInsert->execute()) {
                    $errors[] = "Chyba pri ukladaní záznamu pre dátum $date.";
                }

                $stmtInsert->close();
            }

            $stmtCheck->close();
        }

        if (empty($errors)) {
            $message = "Objednávky boli úspešne uložené!";
        } else {
            $message = "Chyby pri ukladaní:<br>" . implode("<br>", $errors);
        }
    }
}

// Získanie údajov pre výber adries
$search = isset($_GET['search']) ? $_GET['search'] : '';
$filterTyp = isset($_GET['filterTyp']) ? $_GET['filterTyp'] : '';
$filterAkt = isset($_GET['filterAkt']) ? $_GET['filterAkt'] : '';

// Získanie obdobia z session
$startDate = isset($_SESSION['startDate']) ? $_SESSION['startDate'] : null;
$endDate = isset($_SESSION['endDate']) ? $_SESSION['endDate'] : null;

// Základný SQL dotaz pre zoznam adries
$sql = "SELECT id, meno, priezvisko, typ FROM adress WHERE priezvisko LIKE ?";
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

// Pridanie filtra pre klientov, ktorí ešte nemajú objednávky v zadanom období
if ($startDate && $endDate) {
    $sql .= " AND id NOT IN (
        SELECT DISTINCT id_adress FROM orders WHERE datum BETWEEN ? AND ?
    )";
    $params[] = $startDate;
    $params[] = $endDate;
}

$stmt = $conn->prepare($sql);
$types = str_repeat('s', count($params));
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
?>

<?php include 'header.php'; ?>

    <h1>Objednávky</h1>

    <!-- Nastavenie obdobia -->
    <div class="form-container">
        <h2>Nastavenie obdobia</h2>
        <form method="POST" action="orders.php">
            <label for="startDate">Začiatok obdobia:</label>
            <input type="date" id="startDate" name="startDate" value="<?php echo isset($_SESSION['startDate']) ? $_SESSION['startDate'] : ''; ?>" required>

            <label for="endDate">Koniec obdobia:</label>
            <input type="date" id="endDate" name="endDate" value="<?php echo isset($_SESSION['endDate']) ? $_SESSION['endDate'] : ''; ?>" required>

            <button type="submit" name="setPeriod">Nastaviť obdobie</button>
        </form>
    </div>

    <!-- Ľavá časť: Výber adries -->
    <div class="orders-container">
        <div class="orders-left">
            <h2>Výber adries</h2>
            <form method="GET" action="orders.php" class="filter-form">
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

                <button type="submit">Aplikovať</button>
            </form>

            <h3>Zoznam adries</h3>
            <select id="addressList" size="10">
                <?php while ($row = $result->fetch_assoc()) : ?>
                    <option value="<?php echo $row['id']; ?>"><?php echo htmlspecialchars($row['meno'] . ' ' . $row['priezvisko']); ?></option>
                <?php endwhile; ?>
            </select>

            <button type="button" onclick="loadOrders()">Vybrať</button>
        </div>

        <!-- Pravá časť: Formulár pre objednávky -->
        <div class="orders-right">
            <h2>Objednávky</h2>
            <?php if (isset($message)) : ?>
                <div class="message"><?php echo $message; ?></div>
            <?php endif; ?>
            <form method="POST" action="orders.php" id="orderForm">
                <input type="hidden" name="id_adress" id="id_adress">
                <table class="orders-table" id="orderTable">
                    <thead>
                        <tr>
                            <th>Dátum</th>
                            <th>Deň</th>
                            <th>A</th>
                            <th>B</th>
                            <th>D</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Riadky sa vygenerujú dynamicky pomocou JavaScriptu -->
                    </tbody>
                </table>
                <button type="submit" name="save">Uložiť objednávky</button>
                <button type="button" onclick="window.location.href='../index.php'">Koniec</button>
            </form>
        </div>
    </div>

    <script src="../js/functions.js"></script>

<?php include 'footer.php'; ?>