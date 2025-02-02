<?php
session_start(); // Použitie session na zapamätanie stavu

// Pripojenie k databáze
require_once '../config/config.php'; // Načítanie prihlasovacích údajov
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Kontrola pripojenia
if ($conn->connect_error) {
    die("Pripojenie zlyhalo: " . $conn->connect_error);
}

// Funkcia na získanie posledného záznamu z tabuľky
function getLastRecord($conn, $table) {
    $sql = "SELECT * FROM $table ORDER BY date_a DESC LIMIT 1";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    return null;
}

// Funkcia na pridanie nového záznamu do tabuľky
function addNewRecord($conn, $table, $date_a, $price) {
    global $message;

    // Získanie posledného záznamu
    $lastRecord = getLastRecord($conn, $table);

    if ($lastRecord) {
        $lastDateA = new DateTime($lastRecord['date_a']);
        $newDateA = new DateTime($date_a);

        // Kontrola, či nový dátum je väčší ako posledný dátum
        if ($newDateA <= $lastDateA) {
            $message = "Chyba: Nový dátum musí byť väčší ako posledný dátum (" . $lastRecord['date_a'] . ").";
            return false;
        }

        // Aktualizácia date_b v poslednom zázname
        $newDateB = (new DateTime($date_a))->modify('-1 day')->format('Y-m-d');
        $sqlUpdate = "UPDATE $table SET date_b = ? WHERE id = ?";
        $stmtUpdate = $conn->prepare($sqlUpdate);
        $stmtUpdate->bind_param("si", $newDateB, $lastRecord['id']);
        $stmtUpdate->execute();
        $stmtUpdate->close();
    }

    // Pridanie nového záznamu
    $sqlInsert = "INSERT INTO $table (date_a, date_b, $table) VALUES (?, NULL, ?)";
    $stmtInsert = $conn->prepare($sqlInsert);
    $stmtInsert->bind_param("sd", $date_a, $price);

    if ($stmtInsert->execute()) {
        $message = "Záznam bol úspešne pridaný.";
        return true;
    } else {
        $message = "Chyba: Nepodarilo sa pridať záznam.";
        return false;
    }

    $stmtInsert->close();
}

// Funkcia na úpravu posledného záznamu
function updateLastRecord($conn, $table, $price) {
    global $message;

    // Získanie posledného záznamu
    $lastRecord = getLastRecord($conn, $table);

    if ($lastRecord) {
        // Aktualizácia ceny v poslednom zázname
        $sqlUpdate = "UPDATE $table SET $table = ? WHERE id = ?";
        $stmtUpdate = $conn->prepare($sqlUpdate);
        $stmtUpdate->bind_param("di", $price, $lastRecord['id']);

        if ($stmtUpdate->execute()) {
            $message = "Cena bola úspešne aktualizovaná.";
            return true;
        } else {
            $message = "Chyba: Nepodarilo sa aktualizovať cenu.";
            return false;
        }

        $stmtUpdate->close();
    } else {
        $message = "Chyba: Žiadny záznam na úpravu.";
        return false;
    }
}

// Spracovanie údajov z formulára
$message = ""; // Premenná pre oznámenia
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['submit_l'])) {
        $newDate = $_POST['new_date_l'];
        $newPrice = $_POST['new_price_l'];
        addNewRecord($conn, 'price_l', $newDate, $newPrice);
    }
    if (isset($_POST['submit_b'])) {
        $newDate = $_POST['new_date_b'];
        $newPrice = $_POST['new_price_b'];
        addNewRecord($conn, 'price_b', $newDate, $newPrice);
    }
    if (isset($_POST['submit_s'])) {
        $newDate = $_POST['new_date_s'];
        $newPrice = $_POST['new_price_s'];
        addNewRecord($conn, 'price_s', $newDate, $newPrice);
    }
    if (isset($_POST['update_l'])) {
        $newPrice = $_POST['update_price_l'];
        updateLastRecord($conn, 'price_l', $newPrice);
    }
    if (isset($_POST['update_b'])) {
        $newPrice = $_POST['update_price_b'];
        updateLastRecord($conn, 'price_b', $newPrice);
    }
    if (isset($_POST['update_s'])) {
        $newPrice = $_POST['update_price_s'];
        updateLastRecord($conn, 'price_s', $newPrice);
    }
}

// Získanie posledných záznamov z tabuliek
$lastRecordL = getLastRecord($conn, 'price_l');
$lastRecordB = getLastRecord($conn, 'price_b');
$lastRecordS = getLastRecord($conn, 'price_s');

// Získanie histórie cien pre zobrazenie
function getHistory($conn, $table, $limit = 3) {
    $sql = "SELECT $table AS price, CONCAT(date_a, ' - ', IFNULL(date_b, 'súčasnosť')) AS period FROM $table ORDER BY date_a DESC LIMIT ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $history = [];
        while ($row = $result->fetch_assoc()) {
            $history[] = $row;
        }
        return $history;
    } else {
        return []; // Ak nie sú žiadne záznamy, vrátime prázdne pole
    }
}

$historyL = getHistory($conn, 'price_l');
$historyB = getHistory($conn, 'price_b');
$historyS = getHistory($conn, 'price_s');

$conn->close();
?>

<?php include 'header.php'; ?>

<h1>Správa cien</h1>

<!-- Oznámenie o úspechu/chybe -->
<?php if (!empty($message)): ?>
    <div class="message <?php echo strpos($message, 'Chyba') !== false ? 'error' : 'success'; ?>">
        <?php echo $message; ?>
    </div>
<?php endif; ?>

<!-- Kontajner pre formuláre -->
<div class="form-container-horizontal">
    <!-- Časť pre cenu obeda -->
    <div class="form-container">
        <h2>Cena obeda</h2>
        <p><strong>Aktuálna cena:</strong> <?php echo $lastRecordL ? $lastRecordL['price_l'] : 'Nie je dostupná'; ?></p>
        <p><strong>Platí od:</strong> <?php echo $lastRecordL ? $lastRecordL['date_a'] : 'Nie je dostupný'; ?></p>

        <form method="POST" action="price.php">
            <label for="new_price_l">Nová cena:</label>
            <input type="number" step="0.01" id="new_price_l" name="new_price_l" value="<?php echo $lastRecordL ? $lastRecordL['price_l'] : '0.00'; ?>" required>

            <label for="new_date_l">Dátum platnosti:</label>
            <input type="date" id="new_date_l" name="new_date_l" value="<?php echo date('Y-m-d'); ?>" required>

            <button type="submit" name="submit_l">Potvrdiť</button>
        </form>

        <form method="POST" action="price.php" style="margin-top: 10px;">
            <label for="update_price_l">Opraviť aktuálnu cenu:</label>
            <input type="number" step="0.01" id="update_price_l" name="update_price_l" value="<?php echo $lastRecordL ? $lastRecordL['price_l'] : '0.00'; ?>" required>
            <button type="submit" name="update_l">Opraviť</button>
        </form>
        <p style="font-size: 12px; color: #666; margin-top: 5px;">
            Poznámka: Oprava aktuálnej ceny zmení cenu pre celé obdobie od dátumu uvedeného v <strong>date_a</strong>. Táto funkcia slúži len na opravu zle zadanej aktuálnej ceny.
        </p>

        <button onclick="toggleHistory('historyL')">Zobraziť históriu cien</button>
        <div id="historyL" class="history-container" style="display: none;">
            <h3>História cien obeda</h3>
            <table>
                <tr><th>Cena</th><th>Platnosť</th></tr>
                <?php foreach ($historyL as $item): ?>
                    <tr><td><?php echo $item['price']; ?></td><td><?php echo $item['period']; ?></td></tr>
                <?php endforeach; ?>
            </table>
        </div>
    </div>

    <!-- Časť pre cenu desiaty -->
    <div class="form-container">
        <h2>Cena za desiatu</h2>
        <p><strong>Aktuálna cena:</strong> <?php echo $lastRecordB ? $lastRecordB['price_b'] : 'Nie je dostupná'; ?></p>
        <p><strong>Platí od:</strong> <?php echo $lastRecordB ? $lastRecordB['date_a'] : 'Nie je dostupný'; ?></p>

        <form method="POST" action="price.php">
            <label for="new_price_b">Nová cena:</label>
            <input type="number" step="0.01" id="new_price_b" name="new_price_b" value="<?php echo $lastRecordB ? $lastRecordB['price_b'] : '0.00'; ?>" required>

            <label for="new_date_b">Dátum platnosti:</label>
            <input type="date" id="new_date_b" name="new_date_b" value="<?php echo date('Y-m-d'); ?>" required>

            <button type="submit" name="submit_b">Potvrdiť</button>
        </form>

        <form method="POST" action="price.php" style="margin-top: 10px;">
            <label for="update_price_b">Opraviť aktuálnu cenu:</label>
            <input type="number" step="0.01" id="update_price_b" name="update_price_b" value="<?php echo $lastRecordB ? $lastRecordB['price_b'] : '0.00'; ?>" required>
            <button type="submit" name="update_b">Opraviť</button>
        </form>
        <p style="font-size: 12px; color: #666; margin-top: 5px;">
            Poznámka: Oprava aktuálnej ceny zmení cenu pre celé obdobie od dátumu uvedeného v <strong>date_a</strong>. Táto funkcia slúži len na opravu zle zadanej aktuálnej ceny.
        </p>

        <button onclick="toggleHistory('historyB')">Zobraziť históriu cien</button>
        <div id="historyB" class="history-container" style="display: none;">
            <h3>História cien desiaty</h3>
            <table>
                <tr><th>Cena</th><th>Platnosť</th></tr>
                <?php foreach ($historyB as $item): ?>
                    <tr><td><?php echo $item['price']; ?></td><td><?php echo $item['period']; ?></td></tr>
                <?php endforeach; ?>
            </table>
        </div>
    </div>

    <!-- Časť pre cenu pobytu -->
    <div class="form-container">
        <h2>Cena za pobyt</h2>
        <p><strong>Aktuálna cena:</strong> <?php echo $lastRecordS ? $lastRecordS['price_s'] : 'Nie je dostupná'; ?></p>
        <p><strong>Platí od:</strong> <?php echo $lastRecordS ? $lastRecordS['date_a'] : 'Nie je dostupný'; ?></p>

        <form method="POST" action="price.php">
            <label for="new_price_s">Nová cena:</label>
            <input type="number" step="0.01" id="new_price_s" name="new_price_s" value="<?php echo $lastRecordS ? $lastRecordS['price_s'] : '0.00'; ?>" required>

            <label for="new_date_s">Dátum platnosti:</label>
            <input type="date" id="new_date_s" name="new_date_s" value="<?php echo date('Y-m-d'); ?>" required>

            <button type="submit" name="submit_s">Potvrdiť</button>
        </form>

        <form method="POST" action="price.php" style="margin-top: 10px;">
            <label for="update_price_s">Opraviť aktuálnu cenu:</label>
            <input type="number" step="0.01" id="update_price_s" name="update_price_s" value="<?php echo $lastRecordS ? $lastRecordS['price_s'] : '0.00'; ?>" required>
            <button type="submit" name="update_s">Opraviť</button>
        </form>
        <p style="font-size: 12px; color: #666; margin-top: 5px;">
            Poznámka: Oprava aktuálnej ceny zmení cenu pre celé obdobie od dátumu uvedeného v <strong>date_a</strong>. Táto funkcia slúži len na opravu zle zadanej aktuálnej ceny.
        </p>

        <button onclick="toggleHistory('historyS')">Zobraziť históriu cien</button>
        <div id="historyS" class="history-container" style="display: none;">
            <h3>História cien pobytu</h3>
            <table>
                <tr><th>Cena</th><th>Platnosť</th></tr>
                <?php foreach ($historyS as $item): ?>
                    <tr><td><?php echo $item['price']; ?></td><td><?php echo $item['period']; ?></td></tr>
                <?php endforeach; ?>
            </table>
        </div>
    </div>
</div>

<!-- Tlačidlo Koniec -->
<div class="button-container">
    <button onclick="window.location.href='../index.php'">Koniec</button>
</div>

<!-- JavaScript pre zobrazenie/skrytie histórie -->
<script>
    function toggleHistory(historyId) {
        const historyElement = document.getElementById(historyId);
        if (historyElement.style.display === "none") {
            historyElement.style.display = "block";
        } else {
            historyElement.style.display = "none";
        }
    }
</script>

<?php include 'footer.php'; ?>