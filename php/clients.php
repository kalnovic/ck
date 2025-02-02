<?php
require_once '../config/config.php'; // Načítanie konfigurácie

// Pripojenie k databáze
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
if ($conn->connect_error) {
    die("Pripojenie zlyhalo: " . $conn->connect_error);
}

// Spracovanie údajov z formulára
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Maximálne dĺžky pre jednotlivé polia
    $maxLengths = [
        'meno' => 50,
        'priezvisko' => 50,
        'ulica' => 50,
        'mesto' => 50,
        'poznamka' => 50,
        'tel' => 20,
        'email' => 50,
    ];

    // Získanie údajov z formulára
    $meno = $_POST['meno'];
    $priezvisko = $_POST['priezvisko'];
    $ulica = $_POST['ulica'];
    $mesto = $_POST['mesto'];
    $psc = $_POST['psc'];
    $typ = $_POST['typ'];
    $poznamka = $_POST['poznamka'];
    $tel = $_POST['tel'];
    $email = $_POST['email'];
    $akt = 1; // Nový klient má vždy akt = true

    // Validácia dĺžky textu
    $errors = [];
    foreach ($maxLengths as $field => $maxLength) {
        if (strlen($$field) > $maxLength) {
            $errors[] = "Pole '$field' presahuje maximálnu povolenú dĺžku ($maxLength znakov).";
        }
    }

    // Ak sú chyby, zobrazíme ich
    if (!empty($errors)) {
        $message = "Chyba: " . implode(" ", $errors);
    } else {
        // Ak nie sú chyby, uložíme údaje
        $sql = "INSERT INTO adress (meno, priezvisko, ulica, mesto, psc, typ, poznamka, tel, email, akt)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            die("Chyba pri príprave dotazu: " . $conn->error);
        }

        // Bind parametrov
        $stmt->bind_param("ssssissssi", $meno, $priezvisko, $ulica, $mesto, $psc, $typ, $poznamka, $tel, $email, $akt);

        // Vykonanie dotazu
        if ($stmt->execute()) {
            $message = "Údaje boli úspešne uložené!";
        } else {
            $message = "Chyba pri ukladaní údajov: " . $stmt->error;
        }

        // Uzavretie spojenia
        $stmt->close();
    }
    $conn->close();
}
?>

<?php include 'header.php'; ?>

<div class="form-container">
    <h2>Pridanie nového klienta</h2>
    <?php if (isset($message)) : ?>
        <div class="message <?php echo (strpos($message, 'Chyba') !== false) ? 'error' : 'success'; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="clients.php">
        <label for="meno">Meno:</label>
        <input type="text" id="meno" name="meno" maxlength="50" required>

        <label for="priezvisko">Priezvisko:</label>
        <input type="text" id="priezvisko" name="priezvisko" maxlength="50" required>

        <label for="ulica">Ulica:</label>
        <input type="text" id="ulica" name="ulica" maxlength="50" required>

        <label for="mesto">Mesto:</label>
        <input type="text" id="mesto" name="mesto" value="Banská Štiavnica" maxlength="50" required>

        <label for="psc">PSČ:</label>
        <input type="text" id="psc" name="psc" value= "96901" maxlength="5" required>

        <label for="typ">Typ:</label>
        <select id="typ" name="typ" required>
            <option value="D">Dôchodca</option>
            <option value="K">Klient</option>
            <option value="Z">Zamestnanec</option>
        </select>

        <label for="poznamka">Poznámka:</label>
        <input type="text" id="poznamka" name="poznamka" maxlength="50">

        <label for="tel">Telefón:</label>
        <input type="text" id="tel" name="tel" maxlength="20">

        <label for="email">Email:</label>
        <input type="email" id="email" name="email" maxlength="50">

        <button type="submit">Uložiť</button>
        <button type="button" onclick="window.location.href='list.php'">Koniec</button>
    </form>
</div>

<?php include 'footer.php'; ?>