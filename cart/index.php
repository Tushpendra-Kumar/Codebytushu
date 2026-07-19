<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../classes/Auth.php';

Auth::boot();
Auth::requireLogin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Cart - CodeByTushu</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .cart-container { max-width: 800px; margin: 50px auto; padding: 20px; background: #111; border-radius: 12px; border: 1px solid #333; }
        .cart-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #333; padding-bottom: 20px; margin-bottom: 20px; }
        .cart-header h1 { margin: 0; color: #ffc400; }
        .cart-item { display: flex; align-items: center; justify-content: space-between; padding: 15px 0; border-bottom: 1px solid #222; }
        .cart-item:last-child { border-bottom: none; }
        .item-info { display: flex; align-items: center; gap: 20px; }
        .item-thumb { width: 80px; height: 60px; object-fit: cover; border-radius: 6px; }
        .item-title { font-size: 1.1rem; color: #fff; font-weight: bold; }
        .item-price { color: #ffc400; font-size: 1.1rem; }
        .btn-remove { background: none; border: none; color: #ff4444; cursor: pointer; font-size: 1.2rem; transition: 0.3s; }
        .btn-remove:hover { color: #cc0000; }
        .cart-summary { margin-top: 30px; border-top: 1px solid #333; padding-top: 20px; text-align: right; }
        .total-price { font-size: 1.5rem; color: #fff; font-weight: bold; margin-bottom: 20px; }
        .btn-checkout { background: #ffc400; color: #000; padding: 12px 30px; border-radius: 8px; font-weight: bold; text-decoration: none; display: inline-block; font-size: 1.1rem; border: none; cursor: pointer; transition: 0.3s; }
        .btn-checkout:hover { background: #e6b000; }
        .empty-cart { text-align: center; padding: 50px; color: #aaa; }
    </style>
</head>
<body style="background: #0a0a0a; color: #fff; font-family: 'Poppins', sans-serif; margin: 0;">

    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <div class="cart-container">
        <div class="cart-header">
            <h1><i class="fas fa-shopping-cart"></i> Your Cart</h1>
        </div>
        
        <div id="cart-content">
            <div class="empty-cart"><i class="fas fa-spinner fa-spin"></i> Loading cart...</div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', loadCart);

    async function loadCart() {
        try {
            const res = await fetch('/api/cart/view.php');
            const data = await res.json();
            
            const container = document.getElementById('cart-content');
            
            if (!data.success) {
                container.innerHTML = `<div class="empty-cart">Error: ${data.error}</div>`;
                return;
            }
            
            if (data.count === 0) {
                container.innerHTML = `
                    <div class="empty-cart">
                        <h2>Your cart is empty</h2>
                        <p>Browse our <a href="/courses/" style="color:#ffc400;">courses</a> to add something to your cart.</p>
                    </div>`;
                return;
            }
            
            let html = '<div class="cart-items">';
            data.items.forEach(item => {
                html += `
                    <div class="cart-item" id="cart-item-${item.cart_id}">
                        <div class="item-info">
                            <img src="${item.thumbnail_path || '/assets/images/default-course.jpg'}" class="item-thumb" alt="Thumb">
                            <div class="item-title">${item.title}</div>
                        </div>
                        <div style="display:flex; gap: 20px; align-items:center;">
                            <div class="item-price">₹${parseFloat(item.price).toFixed(2)}</div>
                            <button class="btn-remove" onclick="removeItem(${item.cart_id})" title="Remove">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                `;
            });
            html += '</div>';
            
            html += `
                <div class="cart-summary">
                    <div class="total-price">Total: <span style="color:#ffc400;">₹${parseFloat(data.total).toFixed(2)}</span></div>
                    <a href="/checkout/" class="btn-checkout">Proceed to Checkout <i class="fas fa-arrow-right"></i></a>
                </div>
            `;
            
            container.innerHTML = html;
            
        } catch(e) {
            document.getElementById('cart-content').innerHTML = '<div class="empty-cart">A network error occurred.</div>';
        }
    }

    async function removeItem(cartId) {
        if (!confirm('Are you sure you want to remove this item?')) return;
        
        try {
            const formData = new FormData();
            formData.append('cart_id', cartId);
            
            const res = await fetch('/api/cart/remove.php', { method: 'POST', body: formData });
            const data = await res.json();
            
            if (data.success) {
                loadCart(); // Reload cart
            } else {
                alert('Error: ' + data.error);
            }
        } catch(e) {
            alert('A network error occurred.');
        }
    }
    </script>
</body>
</html>
