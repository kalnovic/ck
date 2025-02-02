// functions.js

// Funkcia pre načítanie objednávok
function loadOrders() {
    const startDate = new Date(document.getElementById('startDate').value);
    const endDate = new Date(document.getElementById('endDate').value);
    const [addressId, addressType] = document.getElementById('addressList').value.split('|');

    // Kontrola, či obdobie je práve jeden pracovný týždeň (5 dní)
    if (!isWorkWeek(startDate, endDate)) {
        alert("Obdobie musí byť presne jeden pracovný týždeň (pondelok až piatok).");
        return;
    }

    // Nastavenie ID adresy
    document.getElementById('id_adress').value = addressId;

    // Vygenerovanie riadkov pre dátumy
    const tbody = document.querySelector('#orderTable tbody');
    tbody.innerHTML = '';
    let currentDate = new Date(startDate);

    while (currentDate <= endDate) {
        const row = document.createElement('tr');
        const dateCell = document.createElement('td');
        dateCell.textContent = currentDate.toLocaleDateString('sk-SK');
        row.appendChild(dateCell);

        const dayCell = document.createElement('td');
        dayCell.textContent = currentDate.toLocaleDateString('sk-SK', { weekday: 'short' });
        row.appendChild(dayCell);

        const aCell = document.createElement('td');
        aCell.innerHTML = '<input type="number" name="obed_a[]" min="0" max="9" value="0" required>';
        row.appendChild(aCell);

        const bCell = document.createElement('td');
        bCell.innerHTML = '<input type="number" name="obed_b[]" min="0" max="9" value="0" required>';
        row.appendChild(bCell);

        const dCell = document.createElement('td');
        dCell.innerHTML = `<input type="number" name="obed_d[]" min="0" max="9" value="0" ${addressType === 'D' || addressType === 'Z' ? 'readonly' : ''}>`;
        row.appendChild(dCell);

        tbody.appendChild(row);

        // Pridanie dátumu do skrytého poľa
        const hiddenDate = document.createElement('input');
        hiddenDate.type = 'hidden';
        hiddenDate.name = 'dates[]';
        hiddenDate.value = currentDate.toISOString().split('T')[0];
        row.appendChild(hiddenDate);

        currentDate.setDate(currentDate.getDate() + 1);
    }
}

// Funkcia na kontrolu, či obdobie je pracovný týždeň
function isWorkWeek(startDate, endDate) {
    const startDay = startDate.getDay(); // 1 = pondelok, 5 = piatok
    const endDay = endDate.getDay();
    const daysDifference = Math.ceil((endDate - startDate) / (1000 * 60 * 60 * 24)) + 1;

    // Kontrola, či obdobie začína v pondelok, končí v piatok a má presne 5 dní
    if (startDay === 1 && endDay === 5 && daysDifference === 5) {
        return true;
    }
    return false;
}

// Funkcia pre zobrazenie histórie cien v modálnom okne
function showHistory(historyType) {
    const history = {
        historyL: window.historyL || [],
        historyB: window.historyB || [],
        historyS: window.historyS || []
    }[historyType];

    let content;
    if (history.length > 0) {
        content = "<table><tr><th>Cena</th><th>Platnosť</th></tr>";
        history.forEach(item => {
            content += `<tr><td>${item.price}</td><td>${item.period}</td></tr>`;
        });
        content += "</table>";
    } else {
        content = "<p>História cien neexistuje.</p>";
    }

    document.getElementById('historyContent').innerHTML = content;
    document.getElementById('historyModal').style.display = 'block';
}

// Funkcia pre zatvorenie modálneho okna
function closeModal() {
    document.getElementById('historyModal').style.display = 'none';
}

// Zavrie modálne okno pri kliknutí mimo neho
window.onclick = function(event) {
    const modal = document.getElementById('historyModal');
    if (event.target === modal) {
        modal.style.display = 'none';
    }
}