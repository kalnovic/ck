<?php include 'config/config.php'; ?>
<?php
require_once '../config/config.php'; // Načítanie prihlasovacích údajov

// Pripojenie k databáze
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Kontrola pripojenia
if ($conn->connect_error) {
    die("Pripojenie zlyhalo: " . $conn->connect_error);
}

// Získanie ID záznamu na zmazanie
$id = isset($_GET['id']) ? intval($_GET['id']) : null;

if ($id) {
    // Kontrola, či klient má aktívne objednávky
    $sqlCheckOrders = "SELECT id FROM orders WHERE id_adress = ?";
    $stmtCheckOrders = $conn->prepare($sqlCheckOrders);
    $stmtCheckOrders->bind_param("i", $id);
    $stmtCheckOrders->execute();
    $stmtCheckOrders->store_result();

    if ($stmtCheckOrders->num_rows > 0) {
        // Klient má aktívne objednávky, zmazanie nie je možné
        $message = "Klienta možno vymazať až po spracovaní objednávok.";
        $messageType = "error"; // Typ správy: neúspech
    } else {
        // Klient nemá aktívne objednávky, môžeme ho zmazať
        $sqlDelete = "DELETE FROM adress WHERE id = ?";
        $stmtDelete = $conn->prepare($sqlDelete);
        $stmtDelete->bind_param("i", $id);

        // Vykonanie dotazu
        if ($stmtDelete->execute()) {
            $message = "Záznam bol úspešne zmazaný.";
            $messageType = "success"; // Typ správy: úspech
        } else {
            $message = "Chyba pri mazaní záznamu: " . $stmtDelete->error;
            $messageType = "error"; // Typ správy: neúspech
        }

        $stmtDelete->close();
    }

    $stmtCheckOrders->close();
} else {
    $message = "Neplatné ID záznamu.";
    $messageType = "error"; // Typ správy: neúspech
}

$conn->close();

// Presmerovanie na hlavnú stránku so správou
header("Location: list.php?message=" . urlencode($message) . "&messageType=" . urlencode($messageType));
exit();
?>