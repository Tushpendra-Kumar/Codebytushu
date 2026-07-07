document.addEventListener('DOMContentLoaded', () => {
    
    // Initialize Product Grid
    const productGrid = document.getElementById('productGrid');
    const storeFilters = document.getElementById('storeFilters');
    const storeSearch = document.getElementById('storeSearch');

    function renderProducts(products) {
        if (!productGrid) return;
        
        productGrid.innerHTML = '';
        if (products.length === 0) {
            productGrid.innerHTML = '<p style="color:var(--text-muted); grid-column: 1/-1; text-align:center;">No products found.</p>';
            return;
        }

        products.forEach(product => {
            const stockClass = product.stockStatus === 'in-stock' ? 'stock-in' : 'stock-out';
            const stockText = product.stockStatus === 'in-stock' ? 'In Stock' : 'Out of Stock';
            
            const card = document.createElement('div');
            card.className = 'cbt-product-card';
            card.innerHTML = `
                <div class="cbt-product-img-wrapper">
                    ${product.newArrival ? '<div class="cbt-product-badge">New</div>' : ''}
                    <div class="cbt-product-stock-badge ${stockClass}">${stockText}</div>
                    <img src="${product.image}" alt="${product.title}" loading="lazy">
                </div>
                <div class="cbt-product-content">
                    <div class="cbt-product-category">${product.category}</div>
                    <h3 class="cbt-product-title">${product.title}</h3>
                    <p class="cbt-product-desc">${product.description.substring(0, 80)}...</p>
                    
                    <div class="cbt-product-meta">
                        <div class="cbt-product-price">$${product.price}</div>
                        <div class="cbt-product-rating">
                            <i class="fa-solid fa-star"></i> ${product.rating} <span>(${product.reviews})</span>
                        </div>
                    </div>
                    
                    <div class="cbt-product-actions">
                        <a href="product-details/index.html?id=${product.id}" class="cbt-btn-secondary">View Details</a>
                        ${product.stockStatus === 'in-stock' 
                            ? `<button class="cbt-btn-primary" onclick="addToCart('${product.id}')">Add to Cart</button>`
                            : `<button class="cbt-btn-primary" disabled style="opacity:0.5; cursor:not-allowed;">Out of Stock</button>`
                        }
                    </div>
                </div>
            `;
            productGrid.appendChild(card);
        });
    }

    if (productGrid) {
        renderProducts(getAllProducts());
    }

    // Search and Filter Logic
    if (storeFilters && storeSearch) {
        const filterBtns = storeFilters.querySelectorAll('.cbt-filter-btn');
        
        function applyFilters() {
            const searchTerm = storeSearch.value.toLowerCase();
            const activeFilter = storeFilters.querySelector('.active').getAttribute('data-filter');
            
            let filtered = getAllProducts();
            
            // Category Filter
            if (activeFilter !== 'all') {
                filtered = filtered.filter(p => p.category.toLowerCase() === activeFilter);
            }
            
            // Search Filter
            if (searchTerm) {
                filtered = filtered.filter(p => 
                    p.title.toLowerCase().includes(searchTerm) || 
                    p.category.toLowerCase().includes(searchTerm)
                );
            }
            
            renderProducts(filtered);
        }

        storeSearch.addEventListener('input', applyFilters);

        filterBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                filterBtns.forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                applyFilters();
            });
        });
    }

    // Add To Cart Global Function for Products
    window.addToCart = function(productId, qty = 1) {
        const product = getProductById(productId);
        if (!product) return;

        let cartItems = JSON.parse(localStorage.getItem('cbt_cart')) || [];
        
        const existingItem = cartItems.find(item => item.id === productId);
        
        if (existingItem) {
            existingItem.quantity = (existingItem.quantity || 1) + qty;
        } else {
            cartItems.push({
                id: product.id,
                title: product.title,
                price: product.price,
                image: product.image,
                category: product.category,
                isCourse: false,
                quantity: qty
            });
        }
        
        localStorage.setItem('cbt_cart', JSON.stringify(cartItems));
        
        // Dispatch event so nav icon updates
        window.dispatchEvent(new Event('cartUpdated'));
        
        // Show temporary alert or toast (Simple alert for now)
        alert(`${product.title} added to cart!`);
    };

});
