<?php include 'config/config.php'; ?>
<?php
session_start(); // Použitie session na zapamätanie obdobia

// Pripojenie k databáze
require_once '../config/config.php'; // Načítanie prihlasovacích údajov
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Kontrola pripojenia
if ($conn->connect_error) {
    die("Pripojenie zlyhalo: " . $conn->connect_error);
}

// Funkcia na kontrolu obdobia
function isWorkWeek($startDate, $endDate) {
    $startDay = (new DateTime($startDate))->format('N'); // 1 = pondelok, 5 = piatok
    $endDay = (new DateTime($endDate))->format('N');
    $daysDifference = (new DateTime($endDate))->diff(new DateTime($startDate))->days + 1;

    return ($startDay == 1 && $endDay == 5 && $daysDifference == 5);
}

// Spracovanie údajov z formulára
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['setPeriod'])) {
        $startDate = $_POST['startDate'];
        $endDate = $_POST['endDate'];

        // Kontrola obdobia
        if (!isWorkWeek($startDate, $endDate)) {
            $message = "Obdobie musí byť pracovný týždeň (pondelok až piatok).";
        } else {
            // Uloženie obdobia do session
            $_SESSION['startDate'] = $startDate;
            $_SESSION['endDate'] = $endDate;
        }
    }
}

// Vynulovanie obdobia po stlačení tlačidla "Koniec"
if (isset($_GET['action']) && $_GET['action'] === 'reset') {
    unset($_SESSION['startDate']);
    unset($_SESSION['endDate']);
    header("Location: sum_orders.php"); // Presmerovanie na tú istú stránku
    exit();
}

// Získanie obdobia z session
$startDate = isset($_SESSION['startDate']) ? $_SESSION['startDate'] : null;
$endDate = isset($_SESSION['endDate']) ? $_SESSION['endDate'] : null;

// Získanie údajov pre tabuľku
$ordersData = [];
$summaryData = []; // Súhrn objednávok podľa dní a typu klienta
$totalA = ['D' => 0, 'K' => 0, 'Z' => 0]; // Celkový súčet Obed A podľa typu
$totalB = ['D' => 0, 'K' => 0, 'Z' => 0]; // Celkový súčet Obed B podľa typu
$totalD = ['D' => 0, 'K' => 0, 'Z' => 0]; // Celkový súčet Desiata podľa typu

if ($startDate && $endDate) {
    // Získanie údajov z tabuľky orders pre vybrané obdobie
    $sql = "SELECT o.id_adress, o.datum, o.obed_a, o.obed_b, o.obed_d, a.meno, a.priezvisko, a.typ 
            FROM orders o 
            JOIN adress a ON o.id_adress = a.id 
            WHERE o.datum BETWEEN ? AND ? 
            ORDER BY o.datum, a.priezvisko, a.meno";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $startDate, $endDate);
    $stmt->execute();
    $result = $stmt->get_result();

    // Spracovanie údajov
    while ($row = $result->fetch_assoc()) {
        $clientKey = $row['meno'] . ' ' . $row['priezvisko'];
        $date = $row['datum'];
        $typ = $row['typ']; // Typ klienta (D, K, Z)

        // Pridanie údajov do tabuľky
        if (!isset($ordersData[$clientKey])) {
            $ordersData[$clientKey] = [];
        }
        $ordersData[$clientKey][$date] = [
            'A' => $row['obed_a'],
            'B' => $row['obed_b'],
            'D' => $row['obed_d'], // Stĺpec D zostáva nezmenený pre všetkých klientov
            'typ' => $typ, // Uložíme typ klienta pre ďalšie použitie
        ];

        // Pridanie údajov do súhrnu podľa dní a typu klienta
        if (!isset($summaryData[$date])) {
            $summaryData[$date] = [
                'D' => ['A' => 0, 'B' => 0, 'D' => 0], // Rozvoz (Dôchodcovia)
                'K' => ['A' => 0, 'B' => 0, 'D' => 0], // Klienti DSS
                'Z' => ['A' => 0, 'B' => 0, 'D' => 0], // Zamestnanci
                'Spolu' => ['A' => 0, 'B' => 0, 'D' => 0], // Spolu
            ];
        }
        $summaryData[$date][$typ]['A'] += $row['obed_a'];
        $summaryData[$date][$typ]['B'] += $row['obed_b'];
        $summaryData[$date][$typ]['D'] += ($typ === 'D' || $typ === 'Z') ? 0 : $row['obed_d']; // Pre D a Z je D vždy 0

        // Súčty pre stĺpec "Spolu"
        $summaryData[$date]['Spolu']['A'] += $row['obed_a'];
        $summaryData[$date]['Spolu']['B'] += $row['obed_b'];
        $summaryData[$date]['Spolu']['D'] += ($typ === 'D' || $typ === 'Z') ? 0 : $row['obed_d']; // Pre D a Z je D vždy 0

        // Celkové súčty podľa typu klienta
        $totalA[$row['typ']] += $row['obed_a'];
        $totalB[$row['typ']] += $row['obed_b'];
        $totalD[$row['typ']] += ($typ === 'D' || $typ === 'Z') ? 0 : $row['obed_d']; // Pre D a Z je D vždy 0
    }
    $stmt->close();
}

// Generovanie textového súboru
if (isset($_POST['generateOrder'])) {
    $filename = "objednavky_" . date("Y-m-d_H-i-s") . ".txt";
    $fileContent = "========================================\n";
    $fileContent .= "Objednávka obedov\n";
    $fileContent .= "========================================\n";
    $fileContent .= "Obdobie: " . (new DateTime($startDate))->format('d.m.Y') . " - " . (new DateTime($endDate))->format('d.m.Y') . "\n";
    $fileContent .= "========================================\n\n";

    // Prechádzanie cez každý deň
    $currentDate = new DateTime($startDate);
    $endDateObj = new DateTime($endDate);
    while ($currentDate <= $endDateObj) {
        $dateKey = $currentDate->format('Y-m-d');
        $dateFormatted = $currentDate->format('d.m.Y');

        // Získanie údajov pre daný deň
        $rozvozA = isset($summaryData[$dateKey]['D']['A']) ? $summaryData[$dateKey]['D']['A'] : 0;
        $rozvozB = isset($summaryData[$dateKey]['D']['B']) ? $summaryData[$dateKey]['D']['B'] : 0;
        $klientiA = isset($summaryData[$dateKey]['K']['A']) ? $summaryData[$dateKey]['K']['A'] : 0;
        $klientiB = isset($summaryData[$dateKey]['K']['B']) ? $summaryData[$dateKey]['K']['B'] : 0;
        $klientiD = isset($summaryData[$dateKey]['K']['D']) ? $summaryData[$dateKey]['K']['D'] : 0;
        $zamestnanciA = isset($summaryData[$dateKey]['Z']['A']) ? $summaryData[$dateKey]['Z']['A'] : 0;
        $zamestnanciB = isset($summaryData[$dateKey]['Z']['B']) ? $summaryData[$dateKey]['Z']['B'] : 0;
        $spoluA = isset($summaryData[$dateKey]['Spolu']['A']) ? $summaryData[$dateKey]['Spolu']['A'] : 0;
        $spoluB = isset($summaryData[$dateKey]['Spolu']['B']) ? $summaryData[$dateKey]['Spolu']['B'] : 0;
        $spoluD = isset($summaryData[$dateKey]['Spolu']['D']) ? $summaryData[$dateKey]['Spolu']['D'] : 0;

        // Zápis údajov pre daný deň
        $fileContent .= "Dátum: " . $dateFormatted . "\n";
        $fileContent .= "----------------------------------------\n";
        $fileContent .= "Obed A:\n";
        $fileContent .= "  Rozvoz (Dôchodcovia):\t" . $rozvozA . "\n";
        $fileContent .= "  Zamestnanci:\t\t\t" . $zamestnanciA . "\n";
        $fileContent .= "  Klienti DSS:\t\t\t" . $klientiA . "\n";
        $fileContent .= "  Spolu Obed A:\t\t\t" . $spoluA . "\n\n";

        $fileContent .= "Obed B:\n";
        $fileContent .= "  Rozvoz (Dôchodcovia):\t" . $rozvozB . "\n";
        $fileContent .= "  Zamestnanci:\t\t\t" . $zamestnanciB . "\n";
        $fileContent .= "  Klienti DSS:\t\t\t" . $klientiB . "\n";
        $fileContent .= "  Spolu Obed B:\t\t\t" . $spoluB . "\n\n";

        $fileContent .= "Desiata (Obed D):\n";
        $fileContent .= "  Klienti DSS:\t\t\t" . $klientiD . "\n";
        $fileContent .= "  Spolu Desiata:\t\t" . $spoluD . "\n\n";

        $fileContent .= "========================================\n\n";

        // Presun na ďalší deň
        $currentDate->modify('+1 day');
    }

    // Uloženie súboru na server
    file_put_contents($filename, $fileContent);

    // Stiahnutie súboru
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . filesize($filename));
    readfile($filename);
    exit();
}
?>

<?php include 'header.php'; ?>

<h1>Sumár objednávok</h1>

<!-- Nastavenie obdobia -->
<div class="form-container">
    <h2>Nastavenie obdobia</h2>
    <form method="POST" action="sum_orders.php">
        <label for="startDate">Začiatok obdobia:</label>
        <input type="date" id="startDate" name="startDate" value="<?php echo $startDate; ?>" required>

        <label for="endDate">Koniec obdobia:</label>
        <input type="date" id="endDate" name="endDate" value="<?php echo $endDate; ?>" required>

        <button type="submit" name="setPeriod">Nastaviť obdobie</button>
    </form>
    <?php if (isset($message)) : ?>
        <div class="message error"><?php echo $message; ?></div>
    <?php endif; ?>
</div>

<?php if ($startDate && $endDate) : ?>
    <!-- Tabuľka s objednávkami -->
    <center>
    <h2>Objednávky podľa klientov</h2>
    </center>
    <div style="display: flex; justify-content: center;">
        <table class="orders-table" style="width: auto;">
            <thead>
                <tr>
                    <th>Meno a priezvisko</th>
                    <?php
                    $currentDate = new DateTime($startDate);
                    $endDateObj = new DateTime($endDate);
                    while ($currentDate <= $endDateObj) {
                        echo "<th colspan='3'>" . $currentDate->format('d.m.Y') . "</th>";
                        $currentDate->modify('+1 day');
                    }
                    ?>
                </tr>
                <tr>
                    <th></th>
                    <?php
                    $currentDate = new DateTime($startDate);
                    while ($currentDate <= $endDateObj) {
                        echo "<th>A</th><th>B</th><th>D</th>";
                        $currentDate->modify('+1 day');
                    }
                    ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($ordersData as $client => $dates) : ?>
                    <tr>
                        <td><?php echo htmlspecialchars($client); ?></td>
                        <?php
                        $currentDate = new DateTime($startDate);
                        while ($currentDate <= $endDateObj) {
                            $dateKey = $currentDate->format('Y-m-d');
                            if (isset($dates[$dateKey])) {
                                echo "<td>" . $dates[$dateKey]['A'] . "</td>";
                                echo "<td>" . $dates[$dateKey]['B'] . "</td>";
                                echo "<td>" . $dates[$dateKey]['D'] . "</td>";
                            } else {
                                echo "<td>0</td><td>0</td><td>0</td>";
                            }
                            $currentDate->modify('+1 day');
                        }
                        ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Tabuľka so súhrnom -->
    <center>
    <h2>Celkový súčet objednávok za obdobie</h2>
    </center>
    <div style="display: flex; justify-content: center;">
        <table class="summary-table" style="width: auto;">
            <thead>
                <tr>
                    <th>Dátum</th>
                    <th colspan="2">Rozvoz</th>
                    <th colspan="3">Klienti DSS</th>
                    <th colspan="2">Zamestnanci</th>
                    <th colspan="3" class="highlight">Spolu</th> <!-- Zvýraznený stĺpec -->
                </tr>
                <tr>
                    <th></th>
                    <th>A</th>
                    <th>B</th>
                    <th>A</th>
                    <th>B</th>
                    <th>D</th>
                    <th>A</th>
                    <th>B</th>
                    <th class="highlight">A</th> <!-- Zvýraznený podstĺpec -->
                    <th class="highlight">B</th> <!-- Zvýraznený podstĺpec -->
                    <th class="highlight">D</th> <!-- Zvýraznený podstĺpec -->
                </tr>
            </thead>
            <tbody>
                <?php
                $currentDate = new DateTime($startDate);
                $endDateObj = new DateTime($endDate);
                while ($currentDate <= $endDateObj) :
                    $dateKey = $currentDate->format('Y-m-d');
                    $dateFormatted = $currentDate->format('d.m.Y');
                    $rozvozA = isset($summaryData[$dateKey]['D']['A']) ? $summaryData[$dateKey]['D']['A'] : 0;
                    $rozvozB = isset($summaryData[$dateKey]['D']['B']) ? $summaryData[$dateKey]['D']['B'] : 0;
                    $klientiA = isset($summaryData[$dateKey]['K']['A']) ? $summaryData[$dateKey]['K']['A'] : 0;
                    $klientiB = isset($summaryData[$dateKey]['K']['B']) ? $summaryData[$dateKey]['K']['B'] : 0;
                    $klientiD = isset($summaryData[$dateKey]['K']['D']) ? $summaryData[$dateKey]['K']['D'] : 0;
                    $zamestnanciA = isset($summaryData[$dateKey]['Z']['A']) ? $summaryData[$dateKey]['Z']['A'] : 0;
                    $zamestnanciB = isset($summaryData[$dateKey]['Z']['B']) ? $summaryData[$dateKey]['Z']['B'] : 0;
                    $spoluA = isset($summaryData[$dateKey]['Spolu']['A']) ? $summaryData[$dateKey]['Spolu']['A'] : 0;
                    $spoluB = isset($summaryData[$dateKey]['Spolu']['B']) ? $summaryData[$dateKey]['Spolu']['B'] : 0;
                    $spoluD = isset($summaryData[$dateKey]['Spolu']['D']) ? $summaryData[$dateKey]['Spolu']['D'] : 0;
                ?>
                    <tr>
                        <td><?php echo $dateFormatted; ?></td>
                        <td><?php echo $rozvozA; ?></td>
                        <td><?php echo $rozvozB; ?></td>
                        <td><?php echo $klientiA; ?></td>
                        <td><?php echo $klientiB; ?></td>
                        <td><?php echo $klientiD; ?></td>
                        <td><?php echo $zamestnanciA; ?></td>
                        <td><?php echo $zamestnanciB; ?></td>
                        <td class="highlight"><?php echo $spoluA; ?></td> <!-- Zvýraznený podstĺpec -->
                        <td class="highlight"><?php echo $spoluB; ?></td> <!-- Zvýraznený podstĺpec -->
                        <td class="highlight"><?php echo $spoluD; ?></td> <!-- Zvýraznený podstĺpec -->
                    </tr>
                <?php
                    $currentDate->modify('+1 day');
                endwhile;
                ?>
            </tbody>
        </table>
    </div>

    <!-- Tlačidlá -->
    <div class="button-container">
        <form method="POST" action="sum_orders.php">
            <button type="submit" name="generateOrder">Generovať objednávku</button>
        </form>
        </br>
        <button onclick="window.location.href='sum_orders.php?action=reset'">Koniec</button>
    </div>
<?php endif; ?>

<?php include 'footer.php'; ?>