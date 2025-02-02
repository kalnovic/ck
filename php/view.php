<?php include 'header.php'; ?>
<div class="form-container">
    <h2>Formulár na vloženie údajov</h2>
    <form action="controll.php" method="post" class="form-container">
        <label for="meno">Meno:</label>
        <input type="text" id="meno" name="meno" maxlength="50" required>
        <label for="priezvisko">Priezvisko:</label>
        <input type="text" id="priezvisko" name="priezvisko" maxlength="50" required>
        <label for="ulica">Ulica:</label>
        <input type="text" id="ulica" name="ulica" maxlength="50" required>
        <label for="mesto">Mesto:</label>
        <input type="text" id="mesto" name="mesto" maxlength="50" required>
        <label for="psc">PSČ:</label>
        <input type="text" id="psc" name="psc" maxlength="5" required>
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
        <input type="submit" value="Uložiť">
    </form>
</div>
<?php include 'footer.php'; ?>
