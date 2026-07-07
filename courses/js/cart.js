// cart.js - Handles localStorage cart logic for both Courses and Store Products

function getCart() {
    const cart = localStorage.getItem('cbt_cart');
    if (!cart) return [];
    
    try {
        let parsedCart = JSON.parse(cart);
        // Migration for old string-based carts (Courses)
        parsedCart = parsedCart.map(item => {
            if (typeof item === 'string') {
                const course = (typeof getCourseById === 'function') ? getCourseById(item) : null;
                return {
                    id: item,
                    title: course ? course.title : "Course",
                    price: course ? course.price : 0,
                    image: course ? course.image : "",
                    category: course ? course.category : "Course",
                    isCourse: true,
                    quantity: 1
                };
            }
            return item;
        });
        return parsedCart;
    } catch (e) {
        return [];
    }
}

function saveCart(cart) {
    localStorage.setItem('cbt_cart', JSON.stringify(cart));
    updateCartCounter();
    window.dispatchEvent(new Event('cartUpdated'));
}

// Kept for backward compatibility with courses module
function addToCart(courseId) {
    const cart = getCart();
    if (!cart.some(item => item.id === courseId)) {
        const course = (typeof getCourseById === 'function') ? getCourseById(courseId) : null;
        if (course) {
            cart.push({
                id: course.id,
                title: course.title,
                price: course.price,
                image: course.image,
                category: course.category,
                isCourse: true,
                quantity: 1
            });
            saveCart(cart);
            return true;
        }
    }
    return false; // Already in cart or not found
}

function removeFromCart(id) {
    let cart = getCart();
    cart = cart.filter(item => item.id !== id);
    saveCart(cart);
}

function updateCartQuantity(id, change) {
    let cart = getCart();
    const item = cart.find(i => i.id === id);
    if (item && !item.isCourse) {
        item.quantity += change;
        if (item.quantity < 1) item.quantity = 1;
        saveCart(cart);
    }
}

function isInCart(id) {
    const cart = getCart();
    return cart.some(item => item.id === id);
}

function clearCart() {
    localStorage.removeItem('cbt_cart');
    updateCartCounter();
    window.dispatchEvent(new Event('cartUpdated'));
}

function updateCartCounter() {
    const counters = document.querySelectorAll('.cbt-cart-counter, .cbt-cart-count');
    const cart = getCart();
    let totalItems = 0;
    cart.forEach(item => {
        totalItems += (item.quantity || 1);
    });

    counters.forEach(counter => {
        counter.textContent = totalItems;
        if (totalItems > 0) {
            counter.style.display = 'flex';
        } else {
            counter.style.display = 'none';
        }
    });
}

function toggleCart(courseId, btnElement) {
    if (isInCart(courseId)) {
        removeFromCart(courseId);
        if (btnElement) {
            btnElement.innerHTML = 'Add to Cart <span class="material-symbols-rounded">shopping_cart</span>';
            btnElement.classList.remove('added');
        }
    } else {
        addToCart(courseId);
        if (btnElement) {
            btnElement.innerHTML = 'Remove from Cart <span class="material-symbols-rounded">remove_shopping_cart</span>';
            btnElement.classList.add('added');
        }
    }
}

// Initialize counter on page load
document.addEventListener('DOMContentLoaded', () => {
    updateCartCounter();
});
