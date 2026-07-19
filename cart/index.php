<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../classes/Auth.php';

Auth::boot();
Auth::requireLogin('/cart/index.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout | CodeByTushu</title>
    <link rel="icon" href="/favicon.ico?v=6" sizes="any">
    <!-- Main Site Styles -->
    <link rel="stylesheet" href="/styles.css?v=40">
    <!-- FontAwesome & Material Symbols -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,1,0" rel="stylesheet">
    <style>
        body {
            background-color: #050505;
            color: #f5f5f5;
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 0;
        }
        /* Minimal Header */
        .checkout-header {
            padding: 20px 0;
            text-align: center;
            border-bottom: 1px solid rgba(255, 196, 0, 0.1);
            background: #0a0a0a;
            display: flex;
            justify-content: center;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .checkout-logo {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
        }
        .checkout-logo img {
            height: 40px;
        }
        .checkout-logo span {
            font-size: 1.5rem;
            font-weight: 700;
            color: #fff;
        }
        .checkout-logo span span {
            color: #ffc400;
        }

        /* Cart Container */
        .cart-wrapper {
            max-width: 900px;
            margin: 40px auto;
            padding: 0 20px;
        }
        
        .cart-title {
            font-size: 2rem;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .cart-layout {
            display: grid;
            grid-template-columns: 1fr;
            gap: 30px;
        }
        
        /* Empty Cart */
        .empty-cart-ui {
            text-align: center;
            padding: 60px 20px;
            background: #111;
            border: 1px solid rgba(255, 196, 0, 0.1);
            border-radius: 16px;
        }
        .empty-cart-ui span {
            font-size: 4rem;
            color: rgba(255, 196, 0, 0.3);
            margin-bottom: 20px;
            display: block;
        }
        .empty-cart-ui h2 {
            font-size: 1.8rem;
            margin-bottom: 15px;
        }
        .empty-cart-ui p {
            color: #a0a0a0;
            margin-bottom: 30px;
        }
        .cbt-btn {
            display: inline-block;
            padding: 12px 24px;
            background: #ffc400;
            color: #000;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            font-size: 1rem;
        }
        .cbt-btn:hover {
            background: #e6b000;
            transform: translateY(-2px);
        }
        .cbt-btn-outline {
            background: transparent;
            color: #ffc400;
            border: 1px solid #ffc400;
        }
        .cbt-btn-outline:hover {
            background: rgba(255,196,0,0.1);
        }

        /* Cart Items */
        .cart-items-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        .cart-item-card {
            display: flex;
            background: #111;
            border: 1px solid rgba(255,255,255,0.05);
            border-radius: 12px;
            padding: 15px;
            align-items: center;
            gap: 20px;
            transition: 0.3s ease;
        }
        .cart-item-card:hover {
            border-color: rgba(255, 196, 0, 0.2);
        }
        .cart-item-img {
            width: 120px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
        }
        .cart-item-details {
            flex-grow: 1;
        }
        .cart-item-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin: 0 0 5px 0;
            color: #fff;
        }
        .cart-item-qty {
            font-size: 0.85rem;
            color: #888;
        }
        .cart-item-price {
            font-size: 1.2rem;
            font-weight: 700;
            color: #ffc400;
        }
        .cart-item-remove {
            background: rgba(244, 67, 54, 0.1);
            color: #f44336;
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: 0.3s;
        }
        .cart-item-remove:hover {
            background: #f44336;
            color: #fff;
        }

        /* Cart Summary */
        .cart-summary-box {
            background: #111;
            border: 1px solid rgba(255, 196, 0, 0.1);
            border-radius: 16px;
            padding: 25px;
            position: sticky;
            top: 100px;
            height: fit-content;
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            color: #a0a0a0;
        }
        .summary-total {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid rgba(255,255,255,0.1);
            font-size: 1.3rem;
            font-weight: 700;
            color: #fff;
        }
        .summary-total span:last-child {
            color: #ffc400;
        }
        .btn-checkout-full {
            width: 100%;
            margin-top: 25px;
            text-align: center;
            font-size: 1.1rem;
            padding: 15px;
            box-sizing: border-box;
        }

        @media (min-width: 768px) {
            .cart-layout {
                grid-template-columns: 2fr 1fr;
            }
        }

        @media (max-width: 576px) {
            .cart-item-card {
                flex-direction: column;
                align-items: flex-start;
                position: relative;
            }
            .cart-item-img {
                width: 100%;
                height: 140px;
            }
            .cart-item-remove {
                position: absolute;
                top: 15px;
                right: 15px;
                background: rgba(0,0,0,0.5);
            }
        }
    </style>
</head>
<body>

    <header class="checkout-header">
        <a href="/courses/" class="checkout-logo">
            <img src="/image1/Black%20Logo.PNG" alt="CodeByTushu">
            <span>CodeBy<span>Tushu</span></span>
        </a>
    </header>

    <div class="cart-wrapper">
        <h1 class="cart-title"><span class="material-symbols-rounded" style="font-size: 2.2rem; color: #ffc400;">shopping_cart</span> My Cart</h1>
        
        <div id="cart-content">
            <div style="text-align:center; padding: 50px; color: #888;">
                <i class="fas fa-spinner fa-spin" style="font-size: 2rem;"></i>
                <p style="margin-top:15px;">Loading your cart...</p>
            </div>
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
                container.innerHTML = `
                    <div class="empty-cart-ui">
                        <span class="material-symbols-rounded">error</span>
                        <h2>Error loading cart</h2>
                        <p>${data.error}</p>
                        <button onclick="window.location.reload()" class="cbt-btn">Try Again</button>
                    </div>`;
                return;
            }
            
            if (!data.items || data.items.length === 0) {
                container.innerHTML = `
                    <div class="empty-cart-ui">
                        <span class="material-symbols-rounded">production_quantity_limits</span>
                        <h2>Your cart is empty</h2>
                        <p>Looks like you haven't added any courses to your cart yet.</p>
                        <a href="/courses/" class="cbt-btn">Continue Shopping</a>
                    </div>`;
                return;
            }
            
            let itemsHtml = '<div class="cart-items-list">';
            data.items.forEach(item => {
                itemsHtml += `
                    <div class="cart-item-card" id="cart-item-${item.cart_id}">
                        <img src="${item.thumbnail_path || '/assets/images/default-course.jpg'}" class="cart-item-img" alt="${item.title}">
                        <div class="cart-item-details">
                            <h3 class="cart-item-title">${item.title}</h3>
                            <div class="cart-item-qty">Qty: 1</div>
                        </div>
                        <div class="cart-item-price">₹${parseFloat(item.price).toFixed(2)}</div>
                        <button class="cart-item-remove" onclick="removeItem(${item.cart_id}, ${item.course_id})" title="Remove from cart">
                            <span class="material-symbols-rounded">delete</span>
                        </button>
                    </div>
                `;
            });
            itemsHtml += '</div>';
            
            let summaryHtml = `
                <div class="cart-summary-box">
                    <h3 style="margin-top:0; border-bottom:1px solid rgba(255,255,255,0.1); padding-bottom:15px;">Order Summary</h3>
                    <div class="summary-row">
                        <span>Original Price</span>
                        <span>₹${parseFloat(data.total).toFixed(2)}</span>
                    </div>
                    <div class="summary-row">
                        <span>Discount</span>
                        <span style="color:#4caf50;">-₹0.00</span>
                    </div>
                    <div class="summary-total">
                        <span>Total</span>
                        <span>₹${parseFloat(data.total).toFixed(2)}</span>
                    </div>
                    <a href="/checkout/" class="cbt-btn btn-checkout-full">Proceed to Checkout</a>
                    <div style="text-align:center; margin-top:20px;">
                        <a href="/courses/" class="cbt-btn-outline" style="border:none; font-size:0.9rem; text-decoration:none; padding:10px 20px; display:inline-block;">Continue Shopping</a>
                    </div>
                </div>
            `;
            
            container.innerHTML = `
                <div class="cart-layout">
                    ${itemsHtml}
                    ${summaryHtml}
                </div>
            `;
            
        } catch(e) {
            document.getElementById('cart-content').innerHTML = `
                <div class="empty-cart-ui">
                    <span class="material-symbols-rounded">wifi_off</span>
                    <h2>Connection Error</h2>
                    <p>Unable to connect to the server.</p>
                    <button onclick="loadCart()" class="cbt-btn">Retry</button>
                </div>
            `;
        }
    }

    async function removeItem(cartId, courseId) {
        if (!confirm('Remove this course from cart?')) return;
        
        const itemCard = document.getElementById('cart-item-' + cartId);
        if (itemCard) itemCard.style.opacity = '0.5';
        
        try {
            const formData = new FormData();
            formData.append('cart_id', cartId);
            formData.append('course_id', courseId);
            
            const res = await fetch('/api/cart/remove.php', { method: 'POST', body: formData });
            const data = await res.json();
            
            if (data.success) {
                loadCart(); // Reload cart cleanly to reflect new total
            } else {
                alert('Error: ' + data.error);
                if (itemCard) itemCard.style.opacity = '1';
            }
        } catch(e) {
            alert('A network error occurred.');
            if (itemCard) itemCard.style.opacity = '1';
        }
    }
    </script>
</body>
</html>
