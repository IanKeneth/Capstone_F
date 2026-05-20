function loadInventory() {
    const grid = document.getElementById('inventory-grid');
    if (!grid) return; 
    
    grid.innerHTML = '<div style="grid-column: 1/-1; text-align: center; padding: 50px; color: #7f8c8d;">Loading catalog...</div>';

    fetch('function/get_product_api.php')
        .then(response => {
            if (!response.ok) throw new Error('Network response was not ok');
            return response.json();
        })
        .then(result => {
            grid.innerHTML = ''; 

            if (result.status === 'success' && Array.isArray(result.data) && result.data.length > 0) {
                let allCardsHTML = '';

                result.data.forEach(product => {
                    const max = parseInt(product.max_quantity) || 100;
                    const current = parseInt(product.quantity) || 0;
                    const percent = Math.min((current / max) * 100, 100);
                    const healthColor = percent <= 15 ? '#e74c3c' : '#2ecc71';
                    
                    const wholesale = parseFloat(product.wholesale_price || 0).toFixed(2);
                    const retail = parseFloat(product.retail_price || 0).toFixed(2);
                    const margin = (retail - wholesale).toFixed(2);
                    
                    const imgBase = 'uploads/'; 
                    const fileName = (product.image_path && product.image_path !== 'default-product.png') 
                        ? product.image_path 
                        : 'default-product.png';
                    const imageSrc = imgBase + fileName;
                    allCardsHTML += `
                        <div class="product-card">
                            <!-- Buttons are now correctly nested inside the card -->
                            <div class="card-actions">
                                <a href="function/edit_product.php?id=${product.id}" class="action-btn" title="Edit">
                                    <i class="fa-solid fa-pen"></i>
                                </a>
                                <div class="action-btn" onclick="confirmDelete(${product.id})" style="color: #e74c3c; cursor: pointer;" title="Delete">
                                    <i class="fa-solid fa-trash"></i>
                                </div>
                            </div>
                            
                            <div class="card-image-wrapper">
                                <img src="${imageSrc}" alt="${product.product_name}" onerror="this.onerror=null; this.src='assets/uploads/default-product.png';">
                            </div>
                            
                            <div class="card-info">
                                <div class="card-title">${product.product_name}</div>
                                <div class="card-variation">${product.variation || 'Standard'}</div>
                                
                                <div class="card-description" style="font-size: 0.8rem; color: #5f6769; line-height: 1.4; margin-bottom: 15px; font-style: normal;">
                                    ${product.description || 'No description available.'}
                                </div>

                                <div class="price-details-box">
                                    <div class="price-line">
                                        <span>Wholesale:</span>
                                        <span style="font-weight: 500;">₱${wholesale}</span>
                                    </div>
                                    <div class="price-line">
                                        <span>Retail:</span>
                                        <span style="font-weight: 600; color: #2ecc71;">₱${retail}</span>
                                    </div>
                                    <div class="price-line profit-border">
                                        <span style="color: #7f8c8d;">Profit/Unit:</span>
                                        <span style="font-weight: bold; color: #3498db;">₱${margin}</span>
                                    </div>
                                </div>
                                
                                <div class="progress-bar-bg" style="width: 100%; height: 6px; background: #eee; border-radius: 10px; overflow: hidden;">
                                    <div class="progress-fill" style="width:${percent}%; background:${healthColor}; height: 100%;"></div>
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
                    grid.innerHTML = allCardsHTML;
                    } else {
                        grid.innerHTML = '<div style="grid-column: 1/-1; text-align: center; padding: 50px; color: #7f8c8d;">No products found in the database.</div>';
                    }
                })
                .catch(error => {
                    console.error('Inventory Load Error:', error);
                    grid.innerHTML = `<div style="grid-column: 1/-1; text-align: center; padding: 50px; color: #e74c3c;">Connection Error: ${error.message}</div>`;
                });
            }
            function confirmDelete(id) {
            if (!confirm("Are you sure you want to delete this product? All logs for this item will be lost.")) {
                return;
            }

            fetch(`function/delete_product.php?id=${id}`)
                .then(response => {
                    if (!response.ok) throw new Error('Network response was not ok');
                    return response.json();
                })
                .then(result => {
                    if (result.status === 'success') {
                        alert("Product removed successfully!");
                        loadInventory(); 
                    } else {
                        alert("Database Error: " + (result.message || "Unknown error"));
                    }
                })
                .catch(error => {
                    console.error('Delete Error:', error);
                    alert("Could not connect to the server. Please check your connection.");
                });
        }

        const searchInput = document.getElementById('inventorySearch');
        if (searchInput) {
            searchInput.addEventListener('keyup', function() {
                const searchTerm = this.value.toLowerCase();
                const cards = document.querySelectorAll('.product-card');

                cards.forEach(card => {
                    const cardText = card.textContent.toLowerCase();
                    card.style.display = cardText.includes(searchTerm) ? '' : 'none';
                });
            });
        }
document.addEventListener('DOMContentLoaded', loadInventory);