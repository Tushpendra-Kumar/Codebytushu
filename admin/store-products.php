<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../classes/Auth.php';

Auth::boot();
Auth::requireAdmin();

$adminSection = 'Store';
$adminTitle   = 'Store Products — CodeByTushu Admin';
$breadcrumbs  = [
    ['label' => 'Dashboard', 'url' => '/admin/'],
    ['label' => 'Store Products'],
];

require_once __DIR__ . '/includes/auth_check.php';

$pdo = db();
$unreadCount = 0; // Fetch from DB if needed
$newUsers = 0;

require_once __DIR__ . '/includes/head.php';
?>

<div class="admin-layout" id="adminLayout">
  <?php require_once __DIR__ . '/includes/sidebar.php'; ?>

  <div class="admin-main">
    <?php require_once __DIR__ . '/includes/header.php'; ?>

    <main class="admin-content" role="main">
      <div class="page-header">
        <div class="page-title-wrap">
          <h1 class="page-title">Store Products</h1>
          <p class="page-subtitle">Manage physical and digital store products, pricing, and stock.</p>
        </div>
        <div class="page-actions">
          <button class="btn btn-primary" onclick="openProductModal()">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Add Product
          </button>
        </div>
      </div>

      <div class="table-card">
        <div class="table-toolbar">
          <div class="toolbar-search">
            <input type="text" id="searchInput" placeholder="Search products..." class="form-input" onkeyup="if(event.key==='Enter') loadProducts()">
            <button class="btn btn-secondary" onclick="loadProducts()">Search</button>
          </div>
        </div>

        <div class="table-responsive">
          <table class="table" id="productsTable">
            <thead>
              <tr>
                <th style="width:60px;">Image</th>
                <th>Title</th>
                <th>Category</th>
                <th>Price (₹)</th>
                <th>Stock</th>
                <th>Active</th>
                <th style="width:180px;">Actions</th>
              </tr>
            </thead>
            <tbody id="productsBody">
              <tr><td colspan="7" class="text-center">Loading...</td></tr>
            </tbody>
          </table>
        </div>
        
        <div class="pagination-wrap" id="paginationWrap">
            <!-- Pagination injected via JS -->
        </div>
      </div>
    </main>
  </div>
</div>

<!-- Modal -->
<div class="modal-overlay" id="productModal" style="display:none; position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.5); z-index:9999; align-items:center; justify-content:center;">
    <div class="modal-content" style="background:var(--bg); padding:20px; border-radius:8px; width:90%; max-width:600px; max-height:90vh; overflow-y:auto; border:1px solid var(--border);">
        <div class="modal-header" style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
            <h2 id="modalTitle">Add Product</h2>
            <button onclick="closeProductModal()" style="background:none; border:none; color:var(--text); cursor:pointer; font-size:1.5rem;">&times;</button>
        </div>
        <form id="productForm" onsubmit="saveProduct(event)">
            <input type="hidden" id="productId" name="id">
            
            <div class="form-group" style="margin-bottom:15px;">
                <label class="form-label">Title</label>
                <input type="text" id="productTitle" name="title" class="form-input" required>
            </div>
            
            <div class="form-group" style="margin-bottom:15px;">
                <label class="form-label">Description</label>
                <textarea id="productDescription" name="description" class="form-input" rows="3"></textarea>
            </div>
            
            <div style="display:flex; gap:15px; margin-bottom:15px;">
                <div class="form-group" style="flex:1;">
                    <label class="form-label">Price (₹)</label>
                    <input type="number" id="productPrice" name="price" class="form-input" step="0.01" required>
                </div>
                <div class="form-group" style="flex:1;">
                    <label class="form-label">Category</label>
                    <select id="productCategory" name="category" class="form-input" required>
                        <option value="">Select Category</option>
                        <option value="T-Shirts">T-Shirts</option>
                        <option value="Mugs">Mugs</option>
                        <option value="Stickers">Stickers</option>
                        <option value="Hoodies">Hoodies</option>
                        <option value="Digital Products">Digital Products</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
            </div>
            
            <div style="display:flex; gap:15px; margin-bottom:15px;">
                <div class="form-group" style="flex:1;">
                    <label class="form-label">Stock Status</label>
                    <select id="productStock" name="stock_status" class="form-input">
                        <option value="in-stock">In Stock</option>
                        <option value="out-of-stock">Out of Stock</option>
                    </select>
                </div>
                <div class="form-group" style="flex:1;">
                    <label class="form-label">Sort Order</label>
                    <input type="number" id="productSort" name="sort_order" class="form-input" value="0">
                </div>
            </div>
            
            <div class="form-group" style="margin-bottom:15px;">
                <label class="form-label">Thumbnail URL</label>
                <input type="text" id="productThumbnail" name="thumbnail" class="form-input">
            </div>
            
            <div style="display:flex; gap:15px; margin-bottom:20px;">
                <label style="display:flex; align-items:center; gap:5px; cursor:pointer;">
                    <input type="checkbox" id="productActive" name="is_active" value="1" checked> Is Active
                </label>
                <label style="display:flex; align-items:center; gap:5px; cursor:pointer;">
                    <input type="checkbox" id="productNew" name="is_new_arrival" value="1"> Is New Arrival
                </label>
            </div>
            
            <div style="text-align:right;">
                <button type="button" class="btn btn-secondary" onclick="closeProductModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Product</button>
            </div>
        </form>
    </div>
</div>

<script>
let currentPage = 1;

async function loadProducts(page = 1) {
    currentPage = page;
    const search = document.getElementById('searchInput').value;
    const tbody = document.getElementById('productsBody');
    tbody.innerHTML = '<tr><td colspan="7" class="text-center">Loading...</td></tr>';
    
    try {
        const res = await fetch(`/api/admin/store-products.php?action=list&page=${page}&search=${encodeURIComponent(search)}`);
        const data = await res.json();
        
        if (data.success) {
            tbody.innerHTML = '';
            if (data.data.products.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" class="text-center">No products found.</td></tr>';
                document.getElementById('paginationWrap').innerHTML = '';
                return;
            }
            
            data.data.products.forEach(p => {
                const img = p.thumbnail ? `<img src="${p.thumbnail}" style="width:40px;height:40px;object-fit:cover;border-radius:4px;">` : `<div style="width:40px;height:40px;background:var(--border);border-radius:4px;"></div>`;
                const stockBadge = p.stock_status === 'in-stock' ? `<span class="badge" style="background:#10b981;color:#fff;">In Stock</span>` : `<span class="badge" style="background:#ef4444;color:#fff;">Out of Stock</span>`;
                const activeIcon = p.is_active ? `<span style="color:#10b981;">✓</span>` : `<span style="color:#ef4444;">✗</span>`;
                
                tbody.innerHTML += `
                    <tr>
                        <td>${img}</td>
                        <td>
                            <strong>${escapeHtml(p.title)}</strong>
                            ${p.is_new_arrival ? '<span class="badge" style="background:#3b82f6;color:#fff;font-size:10px;margin-left:5px;">New</span>' : ''}
                        </td>
                        <td>${escapeHtml(p.category)}</td>
                        <td>₹${parseFloat(p.price).toFixed(2)}</td>
                        <td>${stockBadge}</td>
                        <td>${activeIcon}</td>
                        <td>
                            <div class="action-buttons" style="display:flex; gap:5px;">
                                <button class="btn btn-icon btn-secondary" onclick="editProduct(${p.id})" title="Edit"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg></button>
                                <button class="btn btn-icon btn-secondary" onclick="toggleStock(${p.id})" title="Toggle Stock"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg></button>
                                <button class="btn btn-icon btn-secondary" onclick="toggleActive(${p.id})" title="Toggle Active"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></button>
                                <button class="btn btn-icon btn-danger" onclick="deleteProduct(${p.id})" title="Delete" style="background:#ef4444;border:none;color:#fff;border-radius:4px;padding:4px 8px;cursor:pointer;"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg></button>
                            </div>
                        </td>
                    </tr>
                `;
            });
            
            // Render pagination
            let pgHtml = '<div style="display:flex;gap:5px;justify-content:center;margin-top:20px;">';
            if(data.data.page > 1) pgHtml += `<button class="btn btn-secondary" onclick="loadProducts(${data.data.page-1})">Prev</button>`;
            pgHtml += `<span style="padding:8px;">Page ${data.data.page} of ${data.data.pages}</span>`;
            if(data.data.page < data.data.pages) pgHtml += `<button class="btn btn-secondary" onclick="loadProducts(${data.data.page+1})">Next</button>`;
            pgHtml += '</div>';
            document.getElementById('paginationWrap').innerHTML = pgHtml;
            
        } else {
            alert('Failed to load products: ' + data.message);
        }
    } catch (err) {
        console.error(err);
        tbody.innerHTML = '<tr><td colspan="7" class="text-center" style="color:#ef4444;">Error loading data.</td></tr>';
    }
}

function openProductModal() {
    document.getElementById('productForm').reset();
    document.getElementById('productId').value = '';
    document.getElementById('modalTitle').innerText = 'Add Product';
    document.getElementById('productModal').style.display = 'flex';
}

function closeProductModal() {
    document.getElementById('productModal').style.display = 'none';
}

async function editProduct(id) {
    try {
        const res = await fetch(`/api/admin/store-products.php?action=get&id=${id}`);
        const data = await res.json();
        if (data.success) {
            const p = data.data;
            document.getElementById('productId').value = p.id;
            document.getElementById('productTitle').value = p.title;
            document.getElementById('productDescription').value = p.description || '';
            document.getElementById('productPrice').value = p.price;
            document.getElementById('productCategory').value = p.category;
            document.getElementById('productStock').value = p.stock_status;
            document.getElementById('productSort').value = p.sort_order;
            document.getElementById('productThumbnail').value = p.thumbnail || '';
            document.getElementById('productActive').checked = p.is_active == 1;
            document.getElementById('productNew').checked = p.is_new_arrival == 1;
            
            document.getElementById('modalTitle').innerText = 'Edit Product';
            document.getElementById('productModal').style.display = 'flex';
        } else {
            alert(data.message);
        }
    } catch(e) { alert('Error fetching product'); }
}

async function saveProduct(e) {
    e.preventDefault();
    const fd = new FormData(e.target);
    const payload = Object.fromEntries(fd.entries());
    payload.action = payload.id ? 'update' : 'create';
    payload.is_active = document.getElementById('productActive').checked ? 1 : 0;
    payload.is_new_arrival = document.getElementById('productNew').checked ? 1 : 0;
    
    try {
        const res = await fetch('/api/admin/store-products.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(payload)
        });
        const data = await res.json();
        if(data.success) {
            closeProductModal();
            loadProducts(currentPage);
        } else {
            alert(data.message);
        }
    } catch(e) { alert('Save failed'); }
}

async function toggleActive(id) {
    if(!confirm('Toggle active status?')) return;
    try {
        const res = await fetch('/api/admin/store-products.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action: 'toggle_active', id})
        });
        const data = await res.json();
        if(data.success) loadProducts(currentPage);
        else alert(data.message);
    } catch(e) {}
}

async function toggleStock(id) {
    if(!confirm('Toggle stock status?')) return;
    try {
        const res = await fetch('/api/admin/store-products.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action: 'toggle_stock', id})
        });
        const data = await res.json();
        if(data.success) loadProducts(currentPage);
        else alert(data.message);
    } catch(e) {}
}

async function deleteProduct(id) {
    if(!confirm('Are you sure you want to delete this product?')) return;
    try {
        const res = await fetch('/api/admin/store-products.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action: 'delete', id})
        });
        const data = await res.json();
        if(data.success) loadProducts(currentPage);
        else alert(data.message);
    } catch(e) {}
}

function escapeHtml(unsafe) {
    if(!unsafe) return '';
    return unsafe.toString()
         .replace(/&/g, "&amp;")
         .replace(/</g, "&lt;")
         .replace(/>/g, "&gt;")
         .replace(/"/g, "&quot;")
         .replace(/'/g, "&#039;");
}

document.addEventListener('DOMContentLoaded', () => {
    loadProducts();
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
