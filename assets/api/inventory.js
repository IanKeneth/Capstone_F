function loadInventory() {
    const grid = document.getElementById('inventory-grid');
    if (!grid) return; 
    
    grid.innerHTML = '<div style="grid-column: 1/-1; text-align: center; padding: 50px; color: #7f8c8d;"><i class="fa-solid fa-spinner fa-spin"></i> Loading catalog...</div>';

    fetch('function/get_product_api.php')
        .then(response => {
            if (!response.ok) throw new Error('Network response was not ok');
            return response.json();
        })
        .then(result => {
            if (result.status === 'success' && Array.isArray(result.data) && result.data.length > 0) {
                const cards = result.data.map(product => {
                    const max = parseInt(product.max_quantity) || 100;
                    const current = parseInt(product.quantity) || 0;
                    const percent = Math.min((current / max) * 100, 100);
                    const healthColor = percent <= 15 ? '#e74c3c' : '#2ecc71';

                    const wholesale = parseFloat(product.wholesale_price || 0);
                    const retail = parseFloat(product.retail_price || 0);
                    const marginVal = retail - wholesale;
                    const margin = marginVal.toFixed(2);

                    const isLoss = marginVal < 0;
                    const profitColor = isLoss ? '#e74c3c' : '#3498db';
                    const profitLabel = isLoss ? 'LOSS/Unit:' : 'Profit/Unit:';

                    const alertBadge = isLoss 
                        ? `<div style="background: #fff5f5; color: #e74c3c; border: 1px solid #feb2b2; padding: 4px; border-radius: 4px; font-size: 0.7rem; margin-bottom: 10px; text-align: center; font-weight: bold;">
                            <i class="fa-solid fa-triangle-exclamation"></i> PRICING ERROR
                           </div>` 
                        : '';
                    
                    const fileName = (product.image_path && product.image_path !== 'default-product.png') 
                        ? product.image_path 
                        : 'default-product.png';
                    const imageSrc = 'uploads/' + fileName;
                    const safeName = product.product_name.replace(/"/g, '&quot;');

                    return `
                        <div class="product-card">
                            <div class="card-actions">
                                <a href="edit_product.php?id=${product.id}" class="action-btn" title="Edit">
                                    <i class="fa-solid fa-pen"></i>
                                </a>
                                <div class="action-btn" onclick="confirmDelete(${product.id})" style="color: #e74c3c; cursor: pointer;" title="Delete">
                                    <i class="fa-solid fa-trash"></i>
                                </div>
                            </div>
                            
                            <div class="card-image-wrapper">
                                <img src="${imageSrc}" alt="${safeName}" onerror="this.onerror=null; this.src='uploads/default-product.png';">
                            </div>
                            
                            <div class="card-info">
                                <div class="card-title">${product.product_name}</div>
                                <div class="card-variation">${product.variation || 'Standard'}</div>
                                
                                ${alertBadge}

                                <div class="card-description" style="font-size: 0.8rem; color: #5f6769; line-height: 1.4; margin-bottom: 15px;">
                                    ${product.description || 'No description available.'}
                                </div>

                                <div class="price-details-box" style="${isLoss ? 'border-color: #e74c3c; background: #fffcfc;' : ''}">
                                    <div class="price-line">
                                        <span>Wholesale:</span>
                                        <span style="font-weight: 500;">₱${wholesale.toFixed(2)}</span>
                                    </div>
                                    <div class="price-line">
                                        <span>Retail:</span>
                                        <span style="font-weight: 600; color: #2ecc71;">₱${retail.toFixed(2)}</span>
                                    </div>
                                    <div class="price-line profit-border">
                                        <span style="color: #7f8c8d;">${profitLabel}</span>
                                        <span style="font-weight: bold; color: ${profitColor};">₱${margin}</span>
                                    </div>
                                </div>
                                
                                <div class="progress-bar-bg" style="width: 100%; height: 6px; background: #eee; border-radius: 10px; overflow: hidden; margin-top:10px;">
                                    <div class="progress-fill" style="width:${percent}%; background:${healthColor}; height: 100%; transition: width 0.5s ease;"></div>
                                </div>
                                <div style="display: flex; justify-content: space-between; margin-top: 5px; margin-bottom: 10px;">
                                    <small style="color: #7f8c8d;">Stock Level</small>
                                    <small style="font-weight: bold; color: ${healthColor};">${current} / ${max}</small>
                                </div>
                                
                                <div class="card-footer" style="text-align: center; border-top: 1px solid #eee; padding-top: 10px; font-weight: bold;">
                                    ${current} units available
                                </div>
                            </div>
                        </div>`;
                });

                grid.innerHTML = cards.join('');
            } else {
                grid.innerHTML = '<div style="grid-column: 1/-1; text-align: center; padding: 50px; color: #7f8c8d;">No products found.</div>';
            }
        })
        .catch(error => {
            console.error('Inventory Load Error:', error);
            grid.innerHTML = `<div style="grid-column: 1/-1; text-align: center; padding: 50px; color: #e74c3c;">Connection Error: ${error.message}</div>`;
        });
}

function confirmDelete(id) {
    if (!confirm("Are you sure? This will permanently delete the product and its history.")) {
        return;
    }
    fetch(`function/delete_product.php?id=${id}`)
        .then(async response => {
            const isJson = response.headers.get('content-type')?.includes('application/json');
            const data = isJson ? await response.json() : null;

            if (!response.ok) {
                throw new Error(data?.message || `Server error: ${response.status}`);
            }
            return data;
        })
        .then(result => {
            if (result.status === 'success') {
                alert("Product removed successfully!");
                if (typeof loadInventory === 'function') {
                    loadInventory(); 
                }
            } else {
                alert("Error: " + result.message);
            }
        })
        .catch(error => {
            console.error('Delete Error:', error);
            alert("Could not delete item: " + error.message);
        });
}

const searchInput = document.getElementById('inventorySearch');
if (searchInput) {
    searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase().trim();
        const cards = document.querySelectorAll('.product-card');

        cards.forEach(card => {
            const productName = card.querySelector('h5')?.textContent.toLowerCase() || "";
            const category = card.querySelector('.category-badge')?.textContent.toLowerCase() || "";
            
            if (productName.includes(searchTerm) || category.includes(searchTerm)) {
                card.style.display = '';
            } else {
                card.style.display = 'none';
            }
        });
    });
}

document.addEventListener('DOMContentLoaded', loadInventory);