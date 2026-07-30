<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../classes/Auth.php';

Auth::boot();
Auth::requireAdmin();

$adminSection = 'Store';
$adminTitle   = 'Store Orders — CodeByTushu Admin';
$breadcrumbs  = [
    ['label' => 'Dashboard', 'url' => '/admin/'],
    ['label' => 'Store Orders'],
];

require_once __DIR__ . '/includes/auth_check.php';

$pdo = db();
$unreadCount = 0;
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
          <h1 class="page-title">Store Orders</h1>
          <p class="page-subtitle">Manage physical and digital product orders.</p>
        </div>
      </div>

      <div class="table-card">
        <div class="table-toolbar">
          <div class="toolbar-tabs" style="display:flex; gap:10px; margin-bottom:15px;">
            <button class="btn btn-primary tab-btn active" data-status="all" onclick="filterStatus('all', this)">All</button>
            <button class="btn btn-secondary tab-btn" data-status="pending" onclick="filterStatus('pending', this)">Pending</button>
            <button class="btn btn-secondary tab-btn" data-status="processing" onclick="filterStatus('processing', this)">Processing</button>
            <button class="btn btn-secondary tab-btn" data-status="shipped" onclick="filterStatus('shipped', this)">Shipped</button>
            <button class="btn btn-secondary tab-btn" data-status="delivered" onclick="filterStatus('delivered', this)">Delivered</button>
          </div>
        </div>

        <div class="table-responsive">
          <table class="table" id="ordersTable">
            <thead>
              <tr>
                <th>Order #</th>
                <th>Customer</th>
                <th>Total (₹)</th>
                <th>Payment</th>
                <th>Pay Status</th>
                <th>Fulfillment</th>
                <th>Date</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody id="ordersBody">
              <tr><td colspan="8" class="text-center">Loading...</td></tr>
            </tbody>
          </table>
        </div>
        
        <div class="pagination-wrap" id="paginationWrap"></div>
      </div>
    </main>
  </div>
</div>

<!-- Modal -->
<div class="modal-overlay" id="orderModal" style="display:none; position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.5); z-index:9999; align-items:center; justify-content:center;">
    <div class="modal-content" style="background:var(--bg); padding:20px; border-radius:8px; width:90%; max-width:700px; max-height:90vh; overflow-y:auto; border:1px solid var(--border);">
        <div class="modal-header" style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
            <h2 id="modalTitle">Order Details</h2>
            <button onclick="closeOrderModal()" style="background:none; border:none; color:var(--text); cursor:pointer; font-size:1.5rem;">&times;</button>
        </div>
        
        <div id="orderDetails" style="margin-bottom:20px; font-size:0.95rem; line-height:1.5;"></div>
        
        <form id="orderForm" onsubmit="saveOrder(event)">
            <input type="hidden" id="orderId" name="id">
            
            <div style="display:flex; gap:15px; margin-bottom:15px;">
                <div class="form-group" style="flex:1;">
                    <label class="form-label">Fulfillment Status</label>
                    <select id="fulfillmentStatus" name="fulfillment_status" class="form-input">
                        <option value="pending">Pending</option>
                        <option value="processing">Processing</option>
                        <option value="shipped">Shipped</option>
                        <option value="delivered">Delivered</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
                <div class="form-group" style="flex:1;">
                    <label class="form-label">Tracking Number</label>
                    <input type="text" id="trackingNumber" name="tracking_number" class="form-input" placeholder="e.g. AW123456789IN">
                </div>
            </div>
            
            <div class="form-group" style="margin-bottom:15px;">
                <label class="form-label">Admin Notes</label>
                <textarea id="adminNotes" name="admin_notes" class="form-input" rows="3" placeholder="Notes invisible to user..."></textarea>
            </div>
            
            <div style="text-align:right;">
                <button type="button" class="btn btn-secondary" onclick="closeOrderModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Update Order</button>
            </div>
        </form>
    </div>
</div>

<script>
let currentPage = 1;
let currentStatus = 'all';

function getStatusBadge(status) {
    status = (status || '').toLowerCase();
    switch(status) {
        case 'pending': return '<span class="badge" style="background:#f59e0b;color:#fff;">Pending</span>';
        case 'processing': return '<span class="badge" style="background:#3b82f6;color:#fff;">Processing</span>';
        case 'shipped': return '<span class="badge" style="background:#8b5cf6;color:#fff;">Shipped</span>';
        case 'delivered': return '<span class="badge" style="background:#10b981;color:#fff;">Delivered</span>';
        case 'cancelled': return '<span class="badge" style="background:#ef4444;color:#fff;">Cancelled</span>';
        default: return `<span class="badge badge-secondary">${status}</span>`;
    }
}

function getPayStatusBadge(status) {
    status = (status || '').toLowerCase();
    if (status === 'verified' || status === 'paid') return '<span class="badge" style="background:#10b981;color:#fff;">Verified</span>';
    if (status === 'rejected' || status === 'failed') return '<span class="badge" style="background:#ef4444;color:#fff;">Rejected</span>';
    return '<span class="badge" style="background:#f59e0b;color:#fff;">Pending</span>';
}

function filterStatus(status, btn) {
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active', 'btn-primary'));
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.add('btn-secondary'));
    
    btn.classList.remove('btn-secondary');
    btn.classList.add('active', 'btn-primary');
    currentStatus = status;
    loadOrders(1);
}

async function loadOrders(page = 1) {
    currentPage = page;
    const tbody = document.getElementById('ordersBody');
    tbody.innerHTML = '<tr><td colspan="8" class="text-center">Loading...</td></tr>';
    
    try {
        const res = await fetch(`/api/admin/store-orders.php?action=list&page=${page}&status=${currentStatus}`);
        const data = await res.json();
        
        if (data.success) {
            tbody.innerHTML = '';
            if (data.data.orders.length === 0) {
                tbody.innerHTML = '<tr><td colspan="8" class="text-center">No orders found.</td></tr>';
                document.getElementById('paginationWrap').innerHTML = '';
                return;
            }
            
            data.data.orders.forEach(o => {
                const itemsCount = "Items"; // we didn't fetch items count in list API, but we could
                tbody.innerHTML += `
                    <tr>
                        <td><strong>${escapeHtml(o.order_number)}</strong></td>
                        <td>
                            ${escapeHtml(o.full_name)}<br>
                            <small style="color:var(--text-muted);">${escapeHtml(o.email)}</small>
                        </td>
                        <td>₹${parseFloat(o.total_amount).toFixed(2)}</td>
                        <td>${escapeHtml(o.payment_method).toUpperCase()}</td>
                        <td>${getPayStatusBadge(o.payment_status)}</td>
                        <td>${getStatusBadge(o.fulfillment_status)}</td>
                        <td>${new Date(o.created_at).toLocaleDateString()}</td>
                        <td>
                            <button class="btn btn-sm btn-primary" onclick="editOrder(${o.id})">Update</button>
                        </td>
                    </tr>
                `;
            });
            
            let pgHtml = '<div style="display:flex;gap:5px;justify-content:center;margin-top:20px;">';
            if(data.data.page > 1) pgHtml += `<button class="btn btn-secondary" onclick="loadOrders(${data.data.page-1})">Prev</button>`;
            pgHtml += `<span style="padding:8px;">Page ${data.data.page} of ${data.data.pages}</span>`;
            if(data.data.page < data.data.pages) pgHtml += `<button class="btn btn-secondary" onclick="loadOrders(${data.data.page+1})">Next</button>`;
            pgHtml += '</div>';
            document.getElementById('paginationWrap').innerHTML = pgHtml;
            
        } else {
            alert('Failed to load orders: ' + data.message);
        }
    } catch (err) {
        tbody.innerHTML = '<tr><td colspan="8" class="text-center text-danger">Error loading data.</td></tr>';
    }
}

async function editOrder(id) {
    try {
        const res = await fetch(`/api/admin/store-orders.php?action=get&id=${id}`);
        const data = await res.json();
        if (data.success) {
            const o = data.data;
            document.getElementById('orderId').value = o.id;
            document.getElementById('fulfillmentStatus').value = o.fulfillment_status || 'pending';
            document.getElementById('trackingNumber').value = o.tracking_number || '';
            document.getElementById('adminNotes').value = o.admin_notes || '';
            
            let itemsHtml = '<table class="table" style="width:100%; text-align:left; border-collapse:collapse; margin-bottom:10px;"><thead><tr style="border-bottom:1px solid var(--border);"><th style="padding:8px;">Product</th><th style="padding:8px;">Qty</th><th style="padding:8px;">Price</th></tr></thead><tbody>';
            (o.items || []).forEach(item => {
                itemsHtml += `<tr style="border-bottom:1px solid var(--border);">
                    <td style="padding:8px;">${escapeHtml(item.title || 'Product #' + item.product_id)}</td>
                    <td style="padding:8px;">${item.quantity}</td>
                    <td style="padding:8px;">₹${parseFloat(item.price).toFixed(2)}</td>
                </tr>`;
            });
            itemsHtml += '</tbody></table>';
            
            let payActions = '';
            if (o.payment_method === 'upi' && o.payment_status === 'pending') {
                payActions = `
                    <div style="margin-top:10px; padding:12px; background:rgba(245,158,11,0.1); border-left:3px solid #f59e0b; border-radius:4px;">
                        <strong style="color:#f59e0b; display:block; margin-bottom:8px;">UPI Payment Pending Verification</strong>
                        <button type="button" class="btn btn-sm" style="background:#10b981; color:#fff; border:none; margin-right:5px;" onclick="verifyPayment(${o.id})">Verify Payment</button>
                        <button type="button" class="btn btn-sm" style="background:#ef4444; color:#fff; border:none;" onclick="rejectPayment(${o.id})">Reject Payment</button>
                    </div>
                `;
            }

            document.getElementById('orderDetails').innerHTML = `
                <div style="display:flex; justify-content:space-between; flex-wrap:wrap; gap:20px;">
                    <div>
                        <p style="margin:0 0 5px;"><strong>Order ID:</strong> ${escapeHtml(o.order_number)}</p>
                        <p style="margin:0 0 5px;"><strong>Customer:</strong> ${escapeHtml(o.full_name)}</p>
                        <p style="margin:0 0 5px;"><strong>Email:</strong> ${escapeHtml(o.email)}</p>
                    </div>
                    <div>
                        <p style="margin:0 0 5px;"><strong>Payment Method:</strong> ${escapeHtml(o.payment_method).toUpperCase()}</p>
                        <p style="margin:0 0 5px;"><strong>Payment Status:</strong> ${getPayStatusBadge(o.payment_status)}</p>
                    </div>
                </div>
                ${payActions}
                <hr style="border-color:var(--border); margin:15px 0;">
                <strong style="display:block; margin-bottom:10px;">Order Items:</strong>
                ${itemsHtml}
                <div style="text-align:right; margin-top:10px;">
                    <strong>Total: ₹${parseFloat(o.total_amount).toFixed(2)}</strong>
                </div>
                <hr style="border-color:var(--border); margin:15px 0;">
            `;
            
            document.getElementById('orderModal').style.display = 'flex';
        } else {
            alert(data.message);
        }
    } catch(e) { alert('Error fetching order'); }
}

function closeOrderModal() {
    document.getElementById('orderModal').style.display = 'none';
}

async function saveOrder(e) {
    e.preventDefault();
    const fd = new FormData(e.target);
    const payload = Object.fromEntries(fd.entries());
    payload.action = 'update_fulfillment';
    
    try {
        const res = await fetch('/api/admin/store-orders.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(payload)
        });
        const data = await res.json();
        if(data.success) {
            closeOrderModal();
            loadOrders(currentPage);
        } else {
            alert(data.message);
        }
    } catch(e) { alert('Save failed'); }
}

async function verifyPayment(id) {
    if(!confirm('Mark this payment as verified?')) return;
    try {
        const res = await fetch('/api/admin/store-orders.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action: 'verify_payment', id})
        });
        const data = await res.json();
        if(data.success) {
            closeOrderModal();
            loadOrders(currentPage);
        } else {
            alert(data.message);
        }
    } catch(e) {}
}

async function rejectPayment(id) {
    if(!confirm('Mark this payment as rejected?')) return;
    try {
        const res = await fetch('/api/admin/store-orders.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action: 'reject_payment', id})
        });
        const data = await res.json();
        if(data.success) {
            closeOrderModal();
            loadOrders(currentPage);
        } else {
            alert(data.message);
        }
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
    loadOrders();
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
