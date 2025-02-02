<?php include 'config/config.php'; ?>
<?php
session_start(); // Použitie session na zapamätanie stavu

// Pripojenie k databáze
require_once '../config/config.php'; // Načítanie prihlasovacích údajov
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Kontrola pripojenia
if ($conn->connect_error) {
    die("Pripojenie zlyhalo: " . $conn->connect_error);
}

// Spracovanie údajov z formulára
$message = ""; // Premenná pre oznámenia
$selectedDate = isset($_SESSION['selectedDate']) ? $_SESSION['selectedDate'] : date('Y-m-d'); // Predvolený dátum

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['show_delivery'])) {
        $selectedDate = $_POST['delivery_date'];
        $_SESSION['selectedDate'] = $selectedDate; // Uloženie dátumu do session
    } elseif (isset($_POST['deliver'])) {
        // Spracovanie doručenia pre jednotlivé typy klientov
        $idAdress = $_POST['id_adress'];
        $delivered = $_POST['delivered'] === 'true' ? 1 : 0;

        // Aktualizácia stĺpca delivered v tabuľke orders
        $sqlUpdate = "UPDATE orders SET delivered = ? WHERE id_adress = ? AND datum = ?";
        $stmtUpdate = $conn->prepare($sqlUpdate);
        $stmtUpdate->bind_param("iis", $delivered, $idAdress, $selectedDate);
        $stmtUpdate->execute();
        $stmtUpdate->close();

        $message = $delivered ? "Záznam bol úspešne doručený." : "Doručenie bolo zrušené.";
    } elseif (isset($_POST['deliver_all_z'])) {
        // Hromadné spracovanie pre typ "Z" (zamestnanci)
        $delivered = $_POST['delivered'] === 'true' ? 1 : 0;

        // Aktualizácia stĺpca delivered v tabuľke orders pre typ "Z"
        $sqlUpdate = "UPDATE orders 
                      JOIN adress ON orders.id_adress = adress.id 
                      SET orders.delivered = ? 
                      WHERE orders.datum = ? AND adress.typ = 'Z'";
        $stmtUpdate = $conn->prepare($sqlUpdate);
        $stmtUpdate->bind_param("is", $delivered, $selectedDate);
        $stmtUpdate->execute();
        $stmtUpdate->close();

        $message = $delivered ? "Všetky záznamy pre zamestnancov boli úspešne doručené." : "Doručenie bolo zrušené pre všetky záznamy zamestnancov.";
    } elseif (isset($_POST['deliver_all_k'])) {
        // Hromadné spracovanie pre typ "K" (klienti DSS)
        $delivered = $_POST['delivered'] === 'true' ? 1 : 0;

        // Aktualizácia stĺpca delivered v tabuľke orders pre typ "K"
        $sqlUpdate = "UPDATE orders 
                      JOIN adress ON orders.id_adress = adress.id 
                      SET orders.delivered = ? 
                      WHERE orders.datum = ? AND adress.typ = 'K'";
        $stmtUpdate = $conn->prepare($sqlUpdate);
        $stmtUpdate->bind_param("is", $delivered, $selectedDate);
        $stmtUpdate->execute();
        $stmtUpdate->close();

        $message = $delivered ? "Všetky záznamy pre klientov DSS boli úspešne doručené." : "Doručenie bolo zrušené pre všetky záznamy klientov DSS.";
    } elseif (isset($_POST['finish_delivery'])) {
        // Presun doručených záznamov do tabuľky delivery a ich vymazanie z orders
        $sqlMove = "INSERT INTO delivery (id_adress, datum, A, B, D, price_l, price_b)
                    SELECT orders.id_adress, orders.datum, orders.obed_a, orders.obed_b, orders.obed_d, 
                           COALESCE(price_l.price_l, 0), COALESCE(price_b.price_b, 0)
                    FROM orders
                    LEFT JOIN price_l ON orders.datum BETWEEN price_l.date_a AND COALESCE(price_l.date_b, orders.datum)
                    LEFT JOIN price_b ON orders.datum BETWEEN price_b.date_a AND COALESCE(price_b.date_b, orders.datum)
                    WHERE orders.datum = ? AND orders.delivered = 1";
        $stmtMove = $conn->prepare($sqlMove);
        $stmtMove->bind_param("s", $selectedDate);
        $stmtMove->execute();
        $stmtMove->close();

        $sqlDelete = "DELETE FROM orders WHERE datum = ? AND delivered = 1";
        $stmtDelete = $conn->prepare($sqlDelete);
        $stmtDelete->bind_param("s", $selectedDate);
        $stmtDelete->execute();
        $stmtDelete->close();

        $message = "Rozvoz bol úspešne ukončený a záznamy boli presunuté.";
    }
}

// Funkcia na získanie objednávok pre zvolený dátum
function getOrdersForDate($conn, $selectedDate, $type) {
    $sql = "SELECT orders.*, adress.meno, adress.priezvisko, adress.ulica, adress.mesto, adress.tel, adress.poznamka 
            FROM orders 
            JOIN adress ON orders.id_adress = adress.id 
            WHERE orders.datum = ? AND adress.typ = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $selectedDate, $type);
    $stmt->execute();
    $result = $stmt->get_result();
    $orders = [];
    while ($row = $result->fetch_assoc()) {
        $orders[] = $row;
    }
    return $orders;
}

// Funkcia na kontrolu, či sú všetky záznamy doručené
function areAllDelivered($conn, $selectedDate) {
    $sql = "SELECT COUNT(*) AS total FROM orders WHERE datum = ? AND delivered = 0";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $selectedDate);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['total'] == 0;
}

$ordersD = getOrdersForDate($conn, $selectedDate, 'D');
$ordersZ = getOrdersForDate($conn, $selectedDate, 'Z');
$ordersK = getOrdersForDate($conn, $selectedDate, 'K');

$allDelivered = areAllDelivered($conn, $selectedDate);

$conn->close();
?>

<?php include 'header.php'; ?>

<h1>Rozvoz objednávok</h1>

<!-- Oznámenie o úspechu/chybe -->
<?php if (!empty($message)): ?>
    <div class="message <?php echo strpos($message, 'Chyba') !== false ? 'error' : 'success'; ?>">
        <?php echo $message; ?>
    </div>
<?php endif; ?>

<!-- Výber dátumu -->
<div class="form-container">
    <form method="POST" action="delivery.php">
        <label for="delivery_date">Dátum rozvozu:</label>
        <input type="date" id="delivery_date" name="delivery_date" value="<?php echo $selectedDate; ?>" required>
        <button type="submit" name="show_delivery">Zobraziť rozvoz</button>
    </form>
</div>

<!-- Tabuľka pre dôchodcov (typ D) -->
<div class="form-container">
    <h2>Rozvoz dôchodcovia</h2>
    <table class="delivery-table">
        <thead>
            <tr>
                <th>Meno</th>
                <th>Priezvisko</th>
                <th>Ulica</th>
                <th>Mesto</th>
                <th>Telefón</th>
                <th>Poznámka</th>
                <th>Obed A</th>
                <th>Obed B</th>
                <th>Akcie</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($ordersD as $index => $order): ?>
                <tr class="<?php echo $order['delivered'] ? 'delivered' : ''; ?>">
                    <td><?php echo $order['meno']; ?></td>
                    <td><?php echo $order['priezvisko']; ?></td>
                    <td><?php echo $order['ulica']; ?></td>
                    <td><?php echo $order['mesto']; ?></td>
                    <td><?php echo $order['tel']; ?></td>
                    <td><?php echo $order['poznamka']; ?></td>
                    <td><?php echo $order['obed_a']; ?></td>
                    <td><?php echo $order['obed_b']; ?></td>
                    <td>
                        <form method="POST" action="delivery.php" style="display:inline;">
                            <input type="hidden" name="id_adress" value="<?php echo $order['id_adress']; ?>">
                            <input type="hidden" name="delivered" value="<?php echo $order['delivered'] ? 'false' : 'true'; ?>">
                            <button type="submit" name="deliver">
                                <?php echo $order['delivered'] ? 'Zrušiť doručenie' : 'Doručiť'; ?>
                            </button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Tabuľka pre zamestnancov (typ Z) -->
<div class="form-container">
    <h2>Rozvoz zamestnanci</h2>
    <table class="delivery-table">
        <thead>
            <tr>
                <th>Meno</th>
                <th>Priezvisko</th>
                <th>Poznámka</th>
                <th>Obed A</th>
                <th>Obed B</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($ordersZ as $index => $order): ?>
                <tr class="<?php echo $order['delivered'] ? 'delivered' : ''; ?>">
                    <td><?php echo $order['meno']; ?></td>
                    <td><?php echo $order['priezvisko']; ?></td>
                    <td><?php echo $order['poznamka']; ?></td>
                    <td><?php echo $order['obed_a']; ?></td>
                    <td><?php echo $order['obed_b']; ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php if (!empty($ordersZ)): ?>
        <form method="POST" action="delivery.php">
            <input type="hidden" name="delivered" value="<?php echo $ordersZ[0]['delivered'] ? 'false' : 'true'; ?>">
            <button type="submit" name="deliver_all_z">
                <?php echo $ordersZ[0]['delivered'] ? 'Zrušiť doručenie' : 'Doručiť všetko'; ?>
            </button>
        </form>
    <?php endif; ?>
</div>

<!-- Tabuľka pre klientov DSS (typ K) -->
<div class="form-container">
    <h2>Rozvoz klienti DSS</h2>
    <table class="delivery-table">
        <thead>
            <tr>
                <th>Meno</th>
                <th>Priezvisko</th>
                <th>Poznámka</th>
                <th>Obed A</th>
                <th>Obed B</th>
                <th>Desiata</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($ordersK as $index => $order): ?>
                <tr class="<?php echo $order['delivered'] ? 'delivered' : ''; ?>">
                    <td><?php echo $order['meno']; ?></td>
                    <td><?php echo $order['priezvisko']; ?></td>
                    <td><?php echo $order['poznamka']; ?></td>
                    <td><?php echo $order['obed_a']; ?></td>
                    <td><?php echo $order['obed_b']; ?></td>
                    <td><?php echo $order['obed_d']; ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php if (!empty($ordersK)): ?>
        <form method="POST" action="delivery.php">
            <input type="hidden" name="delivered" value="<?php echo $ordersK[0]['delivered'] ? 'false' : 'true'; ?>">
            <button type="submit" name="deliver_all_k">
                <?php echo $ordersK[0]['delivered'] ? 'Zrušiť doručenie' : 'Doručiť všetko'; ?>
            </button>
        </form>
    <?php endif; ?>
</div>

<!-- Tlačidlá na spodku stránky -->
<div class="button-container">
    <button onclick="window.location.href='../index.php'">Koniec</button>
    <form method="POST" action="delivery.php" style="display:inline;">
        <button type="submit" name="finish_delivery" <?php echo $allDelivered ? '' : 'disabled'; ?>>Ukončiť rozvoz</button>
    </form>
</div>

<?php include 'footer.php'; ?>