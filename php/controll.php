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
    // Maximálne dĺžky pre jednotlivé polia (podľa štruktúry tabuľky)
    $maxLengths = [
        'meno' => 50,
        'priezvisko' => 50,
        'ulica' => 50,
        'mesto' => 50,
        'poznamka' => 50,
        'tel' => 20,
        'email' => 50,
        'akt'=> 1,
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
    $akt = isset($_POST['akt']) ? 1 : 0;

    // Validácia dĺžky textu
    $errors = [];
    foreach ($maxLengths as $field => $maxLength) {
        if (strlen($$field) > $maxLength) {
            $errors[] = "Pole '$field' presahuje maximálnu povolenú dĺžku ($maxLength znakov).";
        }
    }

    // Ak sú chyby, zobrazíme ich a neukladáme údaje
    if (!empty($errors)) {
        $message = "Chyba: " . implode(" ", $errors);
        header("Location: view.php?message=" . urlencode($message));
        exit();
    }

    // Ak nie sú chyby, uložíme údaje
    $sql = "INSERT INTO adress (meno, priezvisko, ulica, mesto, psc, typ, poznamka, tel, email, akt)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    // Použitie prepared statements pre bezpečnosť
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
    $conn->close();

    // Presmerovanie na hlavnú stránku so správou
    header("Location: ../index.php?message=" . urlencode($message));
    exit();
} else {
    // Ak formulár nebol odoslaný, presmerujte na view.php
    header("Location: view.php");
    exit();
}
?>