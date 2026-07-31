function toggleModal(modalId, show) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = show ? 'flex' : 'none';
    }
}

function openAddProductModal(sessionId, existingProducts) {

    const inputSession = document.getElementById('modal_session_id');
    if (inputSession) {
        inputSession.value = sessionId;
    }

    const select = document.getElementById('filteredSelect');
    if (select) {
        Array.from(select.options).forEach(option => {
            const pname = option.getAttribute('data-pname');
            if (pname && existingProducts.includes(pname)) {
                option.style.display = 'none';
            } else {
                option.style.display = 'block';
            }
        });
        select.value = ''; // Reset select dropdown
    }

    toggleModal('addProductModal', true);
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