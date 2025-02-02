<?php
require_once '../config/config.php'; // Načítanie prihlasovacích údajov

// Pripojenie k databáze
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Kontrola pripojenia
if ($conn->connect_error) {
    die("Pripojenie zlyhalo: " . $conn->connect_error);
}

// Spracovanie údajov z formulára
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_POST['id'];
    $meno = $_POST['meno'];
    $priezvisko = $_POST['priezvisko'];
    $ulica = $_POST['ulica'];
    $mesto = $_POST['mesto'];
    $psc = $_POST['psc'];
    $typ = $_POST['typ'];
    $poznamka = $_POST['poznamka'];
    $tel = $_POST['tel'];
    $email = $_POST['email'];
    $akt = isset($_POST['akt']) ? 1 : 0;

    $sql = "UPDATE adress SET meno=?, priezvisko=?, ulica=?, mesto=?, psc=?, typ=?, poznamka=?, tel=?, email=?, akt=? WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssissssii", $meno, $priezvisko, $ulica, $mesto, $psc, $typ, $poznamka, $tel, $email, $akt, $id);

    if ($stmt->execute()) {
        header("Location: list.php?message=Záznam bol úspešne upravený.");
        exit();
    } else {
        echo "Chyba pri úprave záznamu: " . $stmt->error;
    }

    $stmt->close();
}

// Získanie údajov pre editáciu
$id = isset($_GET['id']) ? $_GET['id'] : null;
if ($id) {
    $sql = "SELECT * FROM adress WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
} else {
    header("Location: list.php");
    exit();
}
?>

<?php include 'header.php'; ?>

    <h1>Upraviť záznam</h1>

    <form method="POST" action="edit.php" class="form-container">
        <input type="hidden" name="id" value="<?php echo $row['id']; ?>">

        <label for="meno">Meno:</label>
        <input type="text" id="meno" name="meno" value="<?php echo htmlspecialchars($row['meno']); ?>" required><br>

        <label for="priezvisko">Priezvisko:</label>
        <input type="text" id="priezvisko" name="priezvisko" value="<?php echo htmlspecialchars($row['priezvisko']); ?>" required><br>

        <label for="ulica">Ulica:</label>
        <input type="text" id="ulica" name="ulica" value="<?php echo htmlspecialchars($row['ulica']); ?>" required><br>

        <label for="mesto">Mesto:</label>
        <input type="text" id="mesto" name="mesto" value="<?php echo htmlspecialchars($row['mesto']); ?>" required><br>

        <label for="psc">PSČ:</label>
        <input type="text" id="psc" name="psc" value="<?php echo htmlspecialchars($row['psc']); ?>" required><br>

        <label for="typ">Typ:</label>
        <select id="typ" name="typ" required>
            <option value="D" <?php echo ($row['typ'] == 'D') ? 'selected' : ''; ?>>Dôchodca</option>
            <option value="K" <?php echo ($row['typ'] == 'K') ? 'selected' : ''; ?>>Klient</option>
            <option value="Z" <?php echo ($row['typ'] == 'Z') ? 'selected' : ''; ?>>Zamestnanec</option>
        </select><br>

        <label for="poznamka">Poznámka:</label>
        <input type="text" id="poznamka" name="poznamka" value="<?php echo htmlspecialchars($row['poznamka']); ?>"><br>

        <label for="tel">Telefón:</label>
        <input type="text" id="tel" name="tel" value="<?php echo htmlspecialchars($row['tel']); ?>"><br>

        <label for="email">Email:</label>
        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($row['email']); ?>"><br>

        <label for="akt">Aktívny:</label>
        <input type="checkbox" id="akt" name="akt" <?php echo ($row['akt'] == 1) ? 'checked' : ''; ?>><br>

        <button type="submit">Uložiť zmeny</button>
    </form>

<?php include 'footer.php'; ?>