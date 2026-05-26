function toggleModal(id, show) { 
    document.getElementById(id).style.display = show ? 'flex' : 'none'; 
}

function updateDropdowns() {
    const tbody = document.getElementById('morningRows');
    if (!tbody) return;
    
    const selects = tbody.querySelectorAll('select');
    const selectedValues = Array.from(selects).map(s => s.value).filter(v => v !== "");

    selects.forEach(s => {
        Array.from(s.options).forEach(option => {
            if (option.value !== "") {
                const isSelectedElsewhere = selectedValues.includes(option.value) && option.value !== s.value;
                option.disabled = isSelectedElsewhere;
                option.hidden = isSelectedElsewhere;
            }
        });
    });
}

function addNewRow() {
    const tbody = document.getElementById('morningRows');
    const firstRow = tbody.querySelector('tr');
    if (!firstRow) return;

    const newRow = firstRow.cloneNode(true);
    newRow.querySelector('input').value = 1;
    const newSelect = newRow.querySelector('select');
    newSelect.value = "";

    newSelect.addEventListener('change', updateDropdowns);

    tbody.appendChild(newRow);
    updateDropdowns();
}

function removeRow(btn) {
    const tbody = document.getElementById('morningRows');
    if (tbody.rows.length > 1) {
        btn.closest('tr').remove();
        updateDropdowns();
    }
}

const sidebarBtn = document.getElementById('sidebarToggle');
if (sidebarBtn) {
    sidebarBtn.addEventListener('click', () => {
        const sidebar = document.querySelector('.sidebar');
        sidebar.classList.toggle('active');
        sidebar.classList.toggle('collapsed');
    });
}

const invSearch = document.getElementById('inventorySearch');
if (invSearch) {
    invSearch.addEventListener('input', function(e) {
        const searchTerm = e.target.value.toLowerCase().trim();
        document.querySelectorAll('.worker-group').forEach(group => {
            const workerName = group.querySelector('.worker-header span')?.textContent.toLowerCase() || "";
            group.style.display = workerName.includes(searchTerm) ? "" : "none";
        });
    });
}