function toggleModal(id, show) { document.getElementById(id).style.display = show ? 'flex' : 'none'; }
    function updateDropdowns() {
        const tbody = document.getElementById('morningRows');
        const selects = tbody.querySelectorAll('select');
        const selectedValues = Array.from(selects).map(s => s.value).filter(v => v !== "");

        selects.forEach(s => {
            const options = s.options;
            for (let i = 0; i < options.length; i++) {
                if (options[i].value !== "" && options[i].value !== s.value) {
                    options[i].style.display = selectedValues.includes(options[i].value) ? "none" : "block";
                }
            }
        });
    }

    function addNewRow() {
        const tbody = document.getElementById('morningRows');
        const firstRow = tbody.querySelector('tr');
        const newRow = firstRow.cloneNode(true);
        newRow.querySelector('input').value = 1;
        newRow.querySelector('select').value = "";
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
    
    function openAddProductModal(sid, existingItems) {
        document.getElementById('modal_session_id').value = sid;
        const select = document.getElementById('filteredSelect');
        const options = select.options;

        for (let i = 0; i < options.length; i++) {
            const name = options[i].getAttribute('data-pname');
            if (name) {
                options[i].style.display = existingItems.includes(name) ? "none" : "block";
            }
        }
        select.value = "";
        toggleModal('addProductModal', true);
        }

        document.getElementById('sidebarToggle').addEventListener('click', () => {
        document.querySelector('.sidebar').classList.toggle('active');
        });
         document.getElementById('sidebarToggle').addEventListener('click', () => {
            document.querySelector('.sidebar').classList.toggle('collapsed');
        });

        document.getElementById('inventorySearch').addEventListener('input', function(e) {
        const searchTerm = e.target.value.toLowerCase();
        const workerGroups = document.querySelectorAll('.worker-group')
        workerGroups.forEach(group => {
        const workerNameElement = group.querySelector('.worker-header span');
        if (workerNameElement) {
            const workerName = workerNameElement.textContent.toLowerCase();
            if (workerName.includes(searchTerm)) {
                group.style.display = "";
            } else {
                group.style.display = "none";
            }
        }
    });
});