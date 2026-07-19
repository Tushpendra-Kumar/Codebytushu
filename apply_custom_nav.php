<?php
$file = __DIR__ . '/courses/index.php';
$content = file_get_contents($file);

// 1. Fetch Cart Items at the top
$phpHeaderAdd = "
// Fetch cart items for Add/Remove logic
\$cartItems = [];
if (Auth::isLoggedIn()) {
    \$cartStmt = \$pdo->prepare(\"SELECT course_id FROM cart_items WHERE user_id = ?\");
    \$cartStmt->execute([Auth::user()['id']]);
    \$cartItems = \$cartStmt->fetchAll(PDO::FETCH_COLUMN);
}
?>";
$content = preg_replace('/\?>/s', $phpHeaderAdd, $content, 1);

// 2. Replace the navbar include with the custom navbar HTML
$customNavbar = '
    <nav class="cbt-navbar navbar" id="mainNavbar">
        <div class="cbt-nav-inner">
            <div class="cbt-logo" id="cbt-logo">
                <a href="../index.html" id="cbt-logo-link">
                    <img src="/image1/Black Logo.PNG" alt="Logo" class="cbt-main-logo-img">
                    <span class="cbt-logo-text">CodeBy<span class="cbt-logo-accent">Tushu</span></span>
                </a>
            </div>
            
            <ul class="cbt-center-nav" id="cbt-center-nav">
                <li><a href="index.php" class="cbt-nav-link active">Home</a></li>
                <li><a href="#categories" class="cbt-nav-link">Categories</a></li>
                <li><a href="#all-courses" class="cbt-nav-link">All Courses</a></li>
                <li><a href="/cart/index.php" class="cbt-nav-link">My Cart</a></li>
                <li><a href="#faq" class="cbt-nav-link">FAQ</a></li>
            </ul>

            <div class="cbt-nav-right">
                <a href="/cart/index.php" class="cbt-nav-cart-btn" aria-label="Cart">
                    <span class="material-symbols-rounded">shopping_cart</span>
                    <span class="cbt-cart-counter" id="cartCounter" style="<?= empty($cartItems) ? \'display: none;\' : \'\' ?>"><?= count($cartItems) ?></span>
                </a>
                
                <!-- Auth area for Login/Avatar -->
                <div id="cbt-auth-area" style="display:inline-flex;align-items:center;gap:8px;margin-left:15px;">
                    <a href="/auth/login.php" class="cbt-login-btn" id="cbt-login-btn">
                        <span>Login</span>
                    </a>
                </div>

                <button class="cbt-hamburger-btn" id="cbt-hamburger-btn">
                    <span class="cbt-ham-bar"></span>
                    <span class="cbt-ham-bar"></span>
                    <span class="cbt-ham-bar"></span>
                </button>
            </div>
        </div>
    </nav>
';

$content = str_replace("<?php include __DIR__ . '/../includes/navbar.php'; ?>", $customNavbar, $content);

// 3. Update the Course Card Buttons to dynamic Add/Remove
$oldButtons = '<form action="/api/cart/add.php" method="POST" style="display:inline; margin:0;">
                                <input type="hidden" name="course_id" value="<?= $course[\'id\'] ?>">
                                <button type="submit" class="cbt-btn cbt-btn-primary">Add to Cart <span class="material-symbols-rounded" style="font-size:18px;margin-left:4px;">shopping_cart</span></button>
                            </form>';

$newButtons = '
                            <?php 
                                $inCart = in_array($course[\'id\'], $cartItems);
                                $btnText = $inCart ? \'Remove from Cart <span class="material-symbols-rounded" style="font-size:18px;margin-left:4px;">remove_shopping_cart</span>\' : \'Add to Cart <span class="material-symbols-rounded" style="font-size:18px;margin-left:4px;">shopping_cart</span>\';
                                $btnClass = $inCart ? \'added\' : \'\';
                            ?>
                            <button type="button" class="cbt-btn cbt-btn-primary <?= $btnClass ?>" onclick="toggleCart(<?= $course[\'id\'] ?>, this)"><?= $btnText ?></button>
';
$content = str_replace($oldButtons, $newButtons, $content);

// 4. Add the AJAX script at the bottom of the body
$ajaxScript = '
    <script src="/auth-ui.js"></script>
    <script>
        function toggleCart(courseId, btn) {
            const isAdded = btn.classList.contains(\'added\');
            const endpoint = isAdded ? \'/api/cart/remove.php\' : \'/api/cart/add.php\';
            
            // For not logged in user (Optional: you can redirect to login, but API returns 401 if not logged in)
            fetch(endpoint, {
                method: \'POST\',
                headers: { \'Content-Type\': \'application/x-www-form-urlencoded\' },
                body: \'course_id=\' + courseId
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === \'success\' || data.success) {
                    let counter = document.getElementById(\'cartCounter\');
                    let currentCount = parseInt(counter.innerText) || 0;

                    if (isAdded) {
                        btn.classList.remove(\'added\');
                        btn.innerHTML = \'Add to Cart <span class="material-symbols-rounded" style="font-size:18px;margin-left:4px;">shopping_cart</span>\';
                        currentCount = Math.max(0, currentCount - 1);
                    } else {
                        btn.classList.add(\'added\');
                        btn.innerHTML = \'Remove from Cart <span class="material-symbols-rounded" style="font-size:18px;margin-left:4px;">remove_shopping_cart</span>\';
                        currentCount += 1;
                    }
                    
                    counter.innerText = currentCount;
                    counter.style.display = currentCount > 0 ? \'flex\' : \'none\';
                } else if (data.status === \'error\' && data.message === \'Unauthorized\') {
                    window.location.href = \'/auth/login.php?redirect=\' + encodeURIComponent(window.location.pathname);
                } else {
                    alert(data.message || "An error occurred");
                }
            })
            .catch(err => console.error(err));
        }
    </script>
</body>';

$content = str_replace('</body>', $ajaxScript, $content);

file_put_contents($file, $content);
echo "courses/index.php has been updated with the custom navbar and dynamic AJAX Add/Remove to cart logic.\n";
