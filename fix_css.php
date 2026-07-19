<?php
$files = [
    'admin/payments.php',
    'cart/index.php',
    'checkout/index.php',
    'checkout/success.php',
    'courses/details.php',
    'courses/index.php',
    'user/courses.php',
    'user/purchases.php'
];

foreach ($files as $file) {
    $path = __DIR__ . '/' . $file;
    if (file_exists($path)) {
        $content = file_get_contents($path);
        $content = str_replace('href="/assets/css/style.css"', 'href="/styles.css"', $content);
        file_put_contents($path, $content);
        echo "Fixed CSS in $file\n";
    }
}

// Extract navbar from index.html
$index = file(__DIR__ . '/index.html');
$navbarHtml = implode("", array_slice($index, 58, 175 - 59 + 1));

// Fix links in navbar to be absolute
$navbarHtml = str_replace('href="#', 'href="/#', $navbarHtml);
$navbarHtml = str_replace('href="./image1/', 'href="/image1/', $navbarHtml);
$navbarHtml = str_replace('href="auth/', 'href="/auth/', $navbarHtml);

if (!is_dir(__DIR__ . '/includes')) {
    mkdir(__DIR__ . '/includes', 0777, true);
}
file_put_contents(__DIR__ . '/includes/navbar.php', $navbarHtml);
echo "Extracted navbar to includes/navbar.php\n";
