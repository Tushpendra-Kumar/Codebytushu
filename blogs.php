<?php
/**
 * CodeByTushu — Premium Blog Listing Page
 * URL: /blogs.php  (or /blog via .htaccess)
 */
declare(strict_types=1);

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$pdo = db();

// ── Pagination ──────────────────────────────────────────────────────────────
$perPage     = 6;
$currentPage = max(1, (int)($_GET['page'] ?? 1));
$offset      = ($currentPage - 1) * $perPage;

// ── Filters ─────────────────────────────────────────────────────────────────
$search   = trim($_GET['q']   ?? '');
$catSlug  = trim($_GET['cat'] ?? '');
$tagSlug  = trim($_GET['tag'] ?? '');

// ── Build WHERE clause ───────────────────────────────────────────────────────
$where  = ['b.is_published = 1'];
$params = [];

if ($search !== '') {
    $where[]  = '(b.title LIKE ? OR b.excerpt LIKE ?)';
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($catSlug !== '') {
    $where[]  = 'c.slug = ?';
    $params[] = $catSlug;
}
if ($tagSlug !== '') {
    $where[]  = 'EXISTS (SELECT 1 FROM blog_tag_map btm JOIN blog_tags bt ON btm.tag_id = bt.id WHERE btm.article_id = b.id AND bt.slug = ?)';
    $params[] = $tagSlug;
}

$whereSQL = 'WHERE ' . implode(' AND ', $where);

// ── Total count ──────────────────────────────────────────────────────────────
$countSQL  = "SELECT COUNT(*) FROM blog_articles b LEFT JOIN categories c ON b.category_id = c.id $whereSQL";
$countStmt = $pdo->prepare($countSQL);
$countStmt->execute($params);
$totalPosts = (int)$countStmt->fetchColumn();
$totalPages = max(1, (int)ceil($totalPosts / $perPage));
$currentPage = min($currentPage, $totalPages);

// ── Fetch posts ──────────────────────────────────────────────────────────────
$postsSQL = "
    SELECT b.id, b.title, b.slug, b.excerpt, b.thumbnail_path, b.icon_name,
           b.published_at, b.read_time_mins, b.view_count,
           c.name as category_name, c.slug as category_slug,
           u.name as author_name
    FROM blog_articles b
    LEFT JOIN categories c ON b.category_id = c.id
    LEFT JOIN users u ON b.author_id = u.id
    $whereSQL
    ORDER BY b.published_at DESC
    LIMIT $perPage OFFSET $offset
";
$postsStmt = $pdo->prepare($postsSQL);
$postsStmt->execute($params);
$posts = $postsStmt->fetchAll(PDO::FETCH_ASSOC);

// ── Sidebar: Categories ──────────────────────────────────────────────────────
try {
    $cats = $pdo->query("
        SELECT c.name, c.slug, COUNT(b.id) as cnt
        FROM categories c
        JOIN blog_articles b ON b.category_id = c.id AND b.is_published = 1
        WHERE c.type = 'blog'
        GROUP BY c.id ORDER BY cnt DESC
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (\Throwable) { $cats = []; }

// ── Sidebar: Popular Tags ────────────────────────────────────────────────────
try {
    $tags = $pdo->query("
        SELECT bt.name, bt.slug, bt.color_hex, COUNT(btm.article_id) as cnt
        FROM blog_tags bt
        JOIN blog_tag_map btm ON btm.tag_id = bt.id
        JOIN blog_articles ba ON btm.article_id = ba.id AND ba.is_published = 1
        WHERE bt.is_active = 1
        GROUP BY bt.id ORDER BY cnt DESC LIMIT 12
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (\Throwable) { $tags = []; }

// ── Sidebar: Latest posts ─────────────────────────────────────────────────────
try {
    $latest = $pdo->query("
        SELECT title, slug, thumbnail_path, icon_name, published_at, read_time_mins
        FROM blog_articles WHERE is_published = 1
        ORDER BY published_at DESC LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (\Throwable) { $latest = []; }

// ── Helpers ──────────────────────────────────────────────────────────────────
function blogUrl(string $slug): string {
    return '/blog/' . htmlspecialchars($slug);
}
function catUrl(string $slug): string {
    return '/blogs.php?cat=' . urlencode($slug);
}
function tagUrl(string $slug): string {
    return '/blogs.php?tag=' . urlencode($slug);
}
function timeAgo(string $date): string {
    $diff = time() - strtotime($date);
    if ($diff < 3600) return round($diff/60).'m ago';
    if ($diff < 86400) return round($diff/3600).'h ago';
    if ($diff < 2592000) return round($diff/86400).'d ago';
    return date('M j, Y', strtotime($date));
}

$pageTitle = $search ? "Search: $search — Blog" : ($catSlug ? "Category — Blog" : 'Blog | CodeByTushu');
$metaDesc  = 'Tutorials, guides, and insights to help you become a better developer. Programming, Web Dev, LeetCode and more.';
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <meta name="description" content="<?= htmlspecialchars($metaDesc) ?>">
    <meta property="og:title" content="Blog | CodeByTushu">
    <meta property="og:description" content="<?= htmlspecialchars($metaDesc) ?>">
    <meta property="og:type" content="website">
    <link rel="canonical" href="<?= SITE_URL ?>/blogs.php">
    <link rel="icon" href="/favicon.ico?v=6" sizes="any">
    <script src="/theme.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
/* ═══════════════════════════════════════════════════
   BLOG LISTING — PREMIUM DARK THEME
   ═══════════════════════════════════════════════════ */
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

:root {
    --bg:       #0a0a0a;
    --bg-card:  #111111;
    --bg-hover: #161616;
    --border:   rgba(255,196,0,0.15);
    --border-h: rgba(255,196,0,0.45);
    --accent:   #ffc400;
    --accent2:  #ffda6a;
    --text:     #f0f0f0;
    --muted:    #777;
    --dim:      #444;
    --font:     'Inter', -apple-system, sans-serif;
    --radius:   12px;
    --shadow:   0 4px 24px rgba(0,0,0,0.6);
}

body {
    font-family: var(--font);
    background: var(--bg);
    color: var(--text);
    line-height: 1.6;
    min-height: 100vh;
}

a { text-decoration: none; color: inherit; }
img { max-width: 100%; display: block; }

/* ── NAVBAR ── */
.navbar {
    position: sticky;
    top: 0;
    z-index: 100;
    background: rgba(10,10,10,0.9);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    border-bottom: 1px solid var(--border);
    padding: 0 24px;
}
.navbar-inner {
    max-width: 1280px;
    margin: 0 auto;
    height: 64px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
}
.nav-logo {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 18px;
    font-weight: 800;
    color: var(--text);
}
.nav-logo-icon {
    width: 36px; height: 36px;
    background: var(--accent);
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    color: #000; font-size: 14px; font-weight: 800;
    flex-shrink: 0;
}
.nav-links { display: flex; align-items: center; gap: 8px; }
.nav-link {
    color: var(--muted);
    font-size: 14px;
    font-weight: 500;
    padding: 6px 12px;
    border-radius: 8px;
    transition: all 0.2s;
}
.nav-link:hover, .nav-link.active { color: var(--text); background: rgba(255,255,255,0.06); }
.nav-link.active { color: var(--accent); }

/* ── HERO ── */
.blog-hero {
    position: relative;
    background: linear-gradient(135deg, #0a0a0a 0%, #0f0f0f 50%, #0a0a0a 100%);
    padding: 80px 24px 60px;
    text-align: center;
    overflow: hidden;
}
.blog-hero::before {
    content: '';
    position: absolute;
    top: -60px; left: 50%; transform: translateX(-50%);
    width: 600px; height: 300px;
    background: radial-gradient(ellipse, rgba(255,196,0,0.08) 0%, transparent 70%);
    pointer-events: none;
}
.hero-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: rgba(255,196,0,0.1);
    border: 1px solid rgba(255,196,0,0.25);
    color: var(--accent);
    font-size: 12px;
    font-weight: 600;
    padding: 4px 12px;
    border-radius: 99px;
    margin-bottom: 20px;
    letter-spacing: 0.5px;
    text-transform: uppercase;
}
.hero-title {
    font-size: clamp(36px, 5vw, 60px);
    font-weight: 800;
    line-height: 1.15;
    letter-spacing: -0.5px;
    margin-bottom: 16px;
}
.hero-title span { color: var(--accent); }
.hero-desc {
    font-size: 17px;
    color: var(--muted);
    max-width: 560px;
    margin: 0 auto 32px;
    line-height: 1.7;
}
.hero-stats {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 32px;
    flex-wrap: wrap;
}
.hero-stat { text-align: center; }
.hero-stat-n { font-size: 24px; font-weight: 800; color: var(--accent); }
.hero-stat-l { font-size: 12px; color: var(--muted); margin-top: 2px; text-transform: uppercase; letter-spacing: 0.5px; }

/* ── LAYOUT ── */
.blog-layout {
    max-width: 1280px;
    margin: 0 auto;
    padding: 48px 24px 80px;
    display: grid;
    grid-template-columns: 1fr 340px;
    gap: 40px;
    align-items: start;
}

/* ── SECTION HEADER ── */
.section-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 28px;
    gap: 12px;
    flex-wrap: wrap;
}
.section-title {
    font-size: 22px;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 8px;
}
.section-title::before {
    content: '';
    width: 4px; height: 22px;
    background: var(--accent);
    border-radius: 4px;
    display: block;
}
.sort-bar {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    color: var(--muted);
}

/* ── BLOG CARDS ── */
.blog-grid {
    display: grid;
    gap: 24px;
}
.blog-card {
    display: grid;
    grid-template-columns: 200px 1fr;
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    overflow: hidden;
    transition: transform 0.3s ease, border-color 0.3s ease, box-shadow 0.3s ease;
}
.blog-card:hover {
    transform: translateY(-3px);
    border-color: var(--border-h);
    box-shadow: 0 8px 32px rgba(255,196,0,0.08);
}
.blog-thumb-wrap {
    position: relative;
    overflow: hidden;
    background: #1a1a1a;
    min-height: 180px;
}
.blog-thumb-wrap img {
    width: 100%; height: 100%;
    object-fit: cover;
    transition: transform 0.4s ease;
}
.blog-card:hover .blog-thumb-wrap img { transform: scale(1.05); }
.blog-thumb-placeholder {
    width: 100%; height: 100%;
    display: flex; align-items: center; justify-content: center;
    font-size: 48px;
    background: linear-gradient(135deg, #1a1a1a, #222);
    min-height: 180px;
}
.blog-card-body {
    padding: 24px;
    display: flex;
    flex-direction: column;
    gap: 10px;
}
.card-top {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}
.cat-badge {
    background: rgba(255,196,0,0.1);
    color: var(--accent);
    border: 1px solid rgba(255,196,0,0.2);
    font-size: 11px;
    font-weight: 600;
    padding: 3px 10px;
    border-radius: 99px;
    text-transform: uppercase;
    letter-spacing: 0.4px;
}
.card-title {
    font-size: 18px;
    font-weight: 700;
    line-height: 1.4;
    color: var(--text);
    transition: color 0.2s;
}
.blog-card:hover .card-title { color: var(--accent); }
.card-excerpt {
    font-size: 14px;
    color: var(--muted);
    line-height: 1.65;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    flex-grow: 1;
}
.card-meta {
    display: flex;
    align-items: center;
    gap: 14px;
    font-size: 12px;
    color: var(--muted);
    flex-wrap: wrap;
    margin-top: 4px;
}
.card-meta-item {
    display: flex;
    align-items: center;
    gap: 4px;
}
.card-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding-top: 12px;
    border-top: 1px solid var(--border);
    margin-top: 4px;
}
.read-more-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    color: var(--accent);
    font-size: 13px;
    font-weight: 600;
    transition: gap 0.2s;
}
.blog-card:hover .read-more-btn { gap: 10px; }
.read-more-btn svg { transition: transform 0.2s; }
.blog-card:hover .read-more-btn svg { transform: translateX(3px); }
.views-badge {
    font-size: 12px;
    color: var(--dim);
    display: flex;
    align-items: center;
    gap: 4px;
}

/* ── EMPTY STATE ── */
.empty-state {
    text-align: center;
    padding: 80px 40px;
    color: var(--muted);
}
.empty-icon { font-size: 56px; margin-bottom: 16px; }
.empty-state h3 { font-size: 20px; color: var(--text); margin-bottom: 8px; }
.empty-state p { font-size: 14px; }

/* ── SEARCH RESULTS HEADER ── */
.results-header {
    background: rgba(255,196,0,0.05);
    border: 1px solid var(--border);
    border-radius: 10px;
    padding: 12px 16px;
    font-size: 14px;
    color: var(--muted);
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 8px;
}
.results-header strong { color: var(--accent); }

/* ── PAGINATION ── */
.pagination {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    margin-top: 48px;
    flex-wrap: wrap;
}
.page-btn {
    width: 38px; height: 38px;
    display: flex; align-items: center; justify-content: center;
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: 8px;
    font-size: 14px;
    font-weight: 500;
    color: var(--muted);
    cursor: pointer;
    transition: all 0.2s;
}
.page-btn:hover { border-color: var(--accent); color: var(--accent); }
.page-btn.active { background: var(--accent); border-color: var(--accent); color: #000; font-weight: 700; }
.page-btn.disabled { opacity: 0.3; cursor: not-allowed; pointer-events: none; }
.page-btn-text { padding: 0 14px; width: auto; }

/* ── SIDEBAR ── */
.sidebar { display: flex; flex-direction: column; gap: 24px; }
.sidebar-card {
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    overflow: hidden;
}
.sidebar-header {
    padding: 16px 20px;
    border-bottom: 1px solid var(--border);
    font-size: 14px;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 8px;
    color: var(--text);
}
.sidebar-header svg { color: var(--accent); flex-shrink: 0; }
.sidebar-body { padding: 20px; }

/* Search */
.search-form { display: flex; gap: 8px; }
.search-input {
    flex: 1;
    background: var(--bg);
    border: 1px solid var(--border);
    border-radius: 8px;
    padding: 10px 14px;
    color: var(--text);
    font-size: 14px;
    font-family: var(--font);
    outline: none;
    transition: border-color 0.2s;
}
.search-input:focus { border-color: var(--accent); }
.search-input::placeholder { color: var(--dim); }
.search-btn {
    background: var(--accent);
    color: #000;
    border: none;
    border-radius: 8px;
    padding: 10px 16px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    font-family: var(--font);
    transition: opacity 0.2s;
}
.search-btn:hover { opacity: 0.85; }

/* Categories */
.cat-list { list-style: none; display: flex; flex-direction: column; gap: 2px; }
.cat-list li a {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 9px 12px;
    border-radius: 8px;
    font-size: 13px;
    color: var(--muted);
    transition: all 0.2s;
    border: 1px solid transparent;
}
.cat-list li a:hover, .cat-list li a.active {
    background: rgba(255,196,0,0.08);
    color: var(--accent);
    border-color: rgba(255,196,0,0.15);
}
.cat-count {
    background: var(--bg);
    border-radius: 99px;
    padding: 2px 8px;
    font-size: 11px;
    font-weight: 600;
}

/* Tags */
.tag-cloud { display: flex; flex-wrap: wrap; gap: 8px; }
.tag-pill {
    background: var(--bg);
    border: 1px solid var(--border);
    border-radius: 99px;
    padding: 5px 12px;
    font-size: 12px;
    font-weight: 500;
    color: var(--muted);
    transition: all 0.2s;
    cursor: pointer;
}
.tag-pill:hover, .tag-pill.active {
    background: rgba(255,196,0,0.1);
    border-color: rgba(255,196,0,0.35);
    color: var(--accent);
}

/* Latest Mini List */
.latest-list { display: flex; flex-direction: column; gap: 16px; }
.latest-item { display: flex; gap: 12px; align-items: flex-start; }
.latest-thumb {
    width: 60px; height: 60px;
    border-radius: 8px;
    object-fit: cover;
    background: #1a1a1a;
    flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
    font-size: 22px;
    overflow: hidden;
}
.latest-thumb img { width: 100%; height: 100%; object-fit: cover; }
.latest-info { flex: 1; min-width: 0; }
.latest-title {
    font-size: 13px;
    font-weight: 600;
    line-height: 1.4;
    color: var(--text);
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    transition: color 0.2s;
}
.latest-item:hover .latest-title { color: var(--accent); }
.latest-meta { font-size: 11px; color: var(--muted); margin-top: 4px; }

/* Newsletter */
.newsletter-wrap { display: flex; flex-direction: column; gap: 12px; }
.newsletter-desc { font-size: 13px; color: var(--muted); line-height: 1.6; }
.newsletter-form { display: flex; flex-direction: column; gap: 8px; }
.newsletter-input {
    background: var(--bg);
    border: 1px solid var(--border);
    border-radius: 8px;
    padding: 10px 14px;
    color: var(--text);
    font-size: 14px;
    font-family: var(--font);
    outline: none;
    transition: border-color 0.2s;
}
.newsletter-input:focus { border-color: var(--accent); }
.newsletter-btn {
    background: var(--accent);
    color: #000;
    border: none;
    border-radius: 8px;
    padding: 11px;
    font-size: 14px;
    font-weight: 700;
    cursor: pointer;
    font-family: var(--font);
    transition: opacity 0.2s;
}
.newsletter-btn:hover { opacity: 0.85; }

/* ── ACTIVE FILTER PILLS ── */
.active-filters {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
    margin-bottom: 20px;
}
.filter-pill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: rgba(255,196,0,0.1);
    border: 1px solid rgba(255,196,0,0.3);
    color: var(--accent);
    font-size: 12px;
    font-weight: 600;
    padding: 4px 10px;
    border-radius: 99px;
}
.filter-pill-remove {
    color: var(--accent);
    font-size: 14px;
    line-height: 1;
    cursor: pointer;
    opacity: 0.7;
}
.filter-pill-remove:hover { opacity: 1; }

/* ── FOOTER ── */
.blog-footer {
    background: #060606;
    border-top: 1px solid var(--border);
    padding: 60px 24px 30px;
}
.footer-inner {
    max-width: 1280px;
    margin: 0 auto;
    display: grid;
    grid-template-columns: 2fr 1fr 1fr 1.5fr;
    gap: 48px;
}
.footer-brand { display: flex; flex-direction: column; gap: 12px; }
.footer-logo { display: flex; align-items: center; gap: 10px; font-size: 18px; font-weight: 800; }
.footer-tagline { font-size: 13px; color: var(--muted); line-height: 1.6; }
.footer-col h4 { font-size: 13px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--text); margin-bottom: 14px; }
.footer-col ul { list-style: none; display: flex; flex-direction: column; gap: 8px; }
.footer-col ul li a { font-size: 13px; color: var(--muted); transition: color 0.2s; }
.footer-col ul li a:hover { color: var(--accent); }
.footer-bottom {
    max-width: 1280px;
    margin: 40px auto 0;
    padding-top: 20px;
    border-top: 1px solid var(--border);
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-size: 12px;
    color: var(--muted);
    flex-wrap: wrap;
    gap: 10px;
}

/* ── RESPONSIVE ── */
@media (max-width: 1024px) {
    .blog-layout { grid-template-columns: 1fr; }
    .sidebar { order: -1; display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
    .footer-inner { grid-template-columns: 1fr 1fr; }
}
@media (max-width: 768px) {
    .blog-card { grid-template-columns: 1fr; }
    .blog-thumb-wrap { min-height: 200px; }
    .blog-hero { padding: 60px 16px 40px; }
    .sidebar { grid-template-columns: 1fr; }
    .nav-links { gap: 2px; }
    .nav-link { padding: 6px 8px; font-size: 13px; }
    .footer-inner { grid-template-columns: 1fr; gap: 32px; }
    .blog-layout { padding: 24px 16px 60px; }
}
@media (max-width: 480px) {
    .hero-stats { gap: 20px; }
    .pagination { gap: 4px; }
    .page-btn { width: 34px; height: 34px; font-size: 13px; }
}

/* ── ANIMATIONS ── */
@keyframes fadeUp {
    from { opacity: 0; transform: translateY(20px); }
    to   { opacity: 1; transform: translateY(0); }
}
.blog-card { animation: fadeUp 0.4s ease both; }
.blog-card:nth-child(1) { animation-delay: 0.05s; }
.blog-card:nth-child(2) { animation-delay: 0.10s; }
.blog-card:nth-child(3) { animation-delay: 0.15s; }
.blog-card:nth-child(4) { animation-delay: 0.20s; }
.blog-card:nth-child(5) { animation-delay: 0.25s; }
.blog-card:nth-child(6) { animation-delay: 0.30s; }
</style>
</head>
<body>

<!-- ── NAVBAR ─────────────────────────────────────────────────────────────── -->
<nav class="navbar">
    <div class="navbar-inner">
        <a href="/" class="nav-logo">
            <div class="nav-logo-icon">CB</div>
            CodeByTushu
        </a>
        <div class="nav-links">
            <a href="/" class="nav-link">Home</a>
            <a href="/Leetcode/problems.php" class="nav-link">LeetCode</a>
            <a href="/blogs.php" class="nav-link active">Blog</a>
            <a href="/admin/" class="nav-link">Admin</a>
        </div>
    </div>
</nav>

<!-- ── HERO ───────────────────────────────────────────────────────────────── -->
<section class="blog-hero">
    <div class="hero-badge">
        <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01z"/></svg>
        CodeByTushu Blog
    </div>
    <h1 class="hero-title">
        Tutorials, Guides &<br><span>Dev Insights</span>
    </h1>
    <p class="hero-desc">
        Real-world programming knowledge — JavaScript, DSA, Web Dev, and everything in between.
    </p>
    <?php if ($totalPosts > 0): ?>
    <div class="hero-stats">
        <div class="hero-stat">
            <div class="hero-stat-n"><?= $totalPosts ?>+</div>
            <div class="hero-stat-l">Articles</div>
        </div>
        <div class="hero-stat">
            <div class="hero-stat-n"><?= count($cats) ?: '—' ?></div>
            <div class="hero-stat-l">Categories</div>
        </div>
        <div class="hero-stat">
            <div class="hero-stat-n"><?= count($tags) ?: '—' ?></div>
            <div class="hero-stat-l">Topics</div>
        </div>
    </div>
    <?php endif; ?>
</section>

<!-- ── MAIN LAYOUT ─────────────────────────────────────────────────────────── -->
<div class="blog-layout">

    <!-- MAIN COLUMN -->
    <main>
        <div class="section-header">
            <h2 class="section-title">
                <?= $search ? 'Search Results' : ($catSlug ? htmlspecialchars($catSlug) . ' Posts' : 'Latest Blogs') ?>
            </h2>
            <?php if ($totalPosts > 0): ?>
            <div class="sort-bar">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M7 12h10M11 18h2"/></svg>
                <?= $totalPosts ?> posts
            </div>
            <?php endif; ?>
        </div>

        <!-- Active filters -->
        <?php if ($search || $catSlug || $tagSlug): ?>
        <div class="active-filters">
            <span style="font-size:12px;color:var(--muted);">Filters:</span>
            <?php if ($search): ?>
            <span class="filter-pill">
                "<?= htmlspecialchars($search) ?>"
                <a href="/blogs.php" class="filter-pill-remove">×</a>
            </span>
            <?php endif; ?>
            <?php if ($catSlug): ?>
            <span class="filter-pill">
                Category: <?= htmlspecialchars($catSlug) ?>
                <a href="/blogs.php<?= $search ? '?q='.urlencode($search) : '' ?>" class="filter-pill-remove">×</a>
            </span>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Search results header -->
        <?php if ($search && $totalPosts > 0): ?>
        <div class="results-header">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
            Found <strong><?= $totalPosts ?></strong> results for "<strong><?= htmlspecialchars($search) ?></strong>"
        </div>
        <?php endif; ?>

        <!-- Blog Cards -->
        <?php if (empty($posts)): ?>
        <div class="empty-state">
            <div class="empty-icon">📝</div>
            <h3><?= $search ? 'No results found' : 'No posts yet' ?></h3>
            <p><?= $search ? 'Try different keywords or browse all articles.' : 'Check back soon — amazing content is coming!' ?></p>
            <?php if ($search || $catSlug || $tagSlug): ?>
            <a href="/blogs.php" style="display:inline-block;margin-top:20px;padding:10px 20px;background:var(--accent);color:#000;border-radius:8px;font-weight:600;font-size:14px;">View All Posts</a>
            <?php endif; ?>
        </div>
        <?php else: ?>
        <div class="blog-grid">
            <?php foreach ($posts as $post): ?>
            <a href="<?= blogUrl($post['slug']) ?>" class="blog-card">
                <div class="blog-thumb-wrap">
                    <?php if (!empty($post['thumbnail_path'])): ?>
                        <img src="<?= htmlspecialchars($post['thumbnail_path']) ?>"
                             alt="<?= htmlspecialchars($post['title']) ?>"
                             loading="lazy">
                    <?php else: ?>
                        <div class="blog-thumb-placeholder">
                            <?= htmlspecialchars($post['icon_name'] ?? '📝') ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="blog-card-body">
                    <div class="card-top">
                        <?php if (!empty($post['category_name'])): ?>
                        <span class="cat-badge"><?= htmlspecialchars($post['category_name']) ?></span>
                        <?php endif; ?>
                    </div>
                    <h3 class="card-title"><?= htmlspecialchars($post['title']) ?></h3>
                    <p class="card-excerpt"><?= htmlspecialchars(mb_strimwidth($post['excerpt'] ?? '', 0, 140, '...')) ?></p>
                    <div class="card-meta">
                        <span class="card-meta-item">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                            <?= htmlspecialchars($post['author_name'] ?? 'Tushpendra Kumar') ?>
                        </span>
                        <span class="card-meta-item">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                            <?= date('M j, Y', strtotime($post['published_at'] ?? 'now')) ?>
                        </span>
                        <span class="card-meta-item">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                            <?= (int)($post['read_time_mins'] ?? 3) ?> min read
                        </span>
                    </div>
                    <div class="card-footer">
                        <span class="read-more-btn">
                            Read Article
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                        </span>
                        <span class="views-badge">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                            <?= number_format($post['view_count'] ?? 0) ?>
                        </span>
                    </div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>

        <!-- PAGINATION -->
        <?php if ($totalPages > 1): ?>
        <nav class="pagination" aria-label="Pagination">
            <?php
            $qBase = '';
            if ($search)  $qBase .= '&q=' . urlencode($search);
            if ($catSlug) $qBase .= '&cat=' . urlencode($catSlug);
            if ($tagSlug) $qBase .= '&tag=' . urlencode($tagSlug);

            $prevPage = $currentPage - 1;
            $nextPage = $currentPage + 1;
            ?>
            <a href="?page=<?= $prevPage . $qBase ?>"
               class="page-btn page-btn-text <?= $currentPage <= 1 ? 'disabled' : '' ?>">
                ← Prev
            </a>
            <?php
            $start = max(1, $currentPage - 2);
            $end   = min($totalPages, $currentPage + 2);
            if ($start > 1): ?>
                <a href="?page=1<?= $qBase ?>" class="page-btn">1</a>
                <?php if ($start > 2): ?><span style="color:var(--muted);padding:0 4px;">…</span><?php endif; ?>
            <?php endif;
            for ($i = $start; $i <= $end; $i++): ?>
                <a href="?page=<?= $i . $qBase ?>"
                   class="page-btn <?= $i === $currentPage ? 'active' : '' ?>">
                    <?= $i ?>
                </a>
            <?php endfor;
            if ($end < $totalPages): ?>
                <?php if ($end < $totalPages - 1): ?><span style="color:var(--muted);padding:0 4px;">…</span><?php endif; ?>
                <a href="?page=<?= $totalPages . $qBase ?>" class="page-btn"><?= $totalPages ?></a>
            <?php endif; ?>
            <a href="?page=<?= $nextPage . $qBase ?>"
               class="page-btn page-btn-text <?= $currentPage >= $totalPages ? 'disabled' : '' ?>">
                Next →
            </a>
        </nav>
        <?php endif; ?>
        <?php endif; ?>
    </main>

    <!-- SIDEBAR -->
    <aside class="sidebar">

        <!-- Search -->
        <div class="sidebar-card">
            <div class="sidebar-header">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                Search Blogs
            </div>
            <div class="sidebar-body">
                <form class="search-form" method="GET" action="/blogs.php">
                    <input type="text" name="q" class="search-input"
                           placeholder="Search articles..."
                           value="<?= htmlspecialchars($search) ?>">
                    <button type="submit" class="search-btn">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                    </button>
                </form>
            </div>
        </div>

        <!-- Categories -->
        <?php if (!empty($cats)): ?>
        <div class="sidebar-card">
            <div class="sidebar-header">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
                Categories
            </div>
            <div class="sidebar-body" style="padding:12px;">
                <ul class="cat-list">
                    <li>
                        <a href="/blogs.php" class="<?= !$catSlug ? 'active' : '' ?>">
                            All Categories
                            <span class="cat-count"><?= $totalPosts ?></span>
                        </a>
                    </li>
                    <?php foreach ($cats as $cat): ?>
                    <li>
                        <a href="<?= catUrl($cat['slug'] ?? '') ?>"
                           class="<?= ($catSlug === ($cat['slug'] ?? '')) ? 'active' : '' ?>">
                            <?= htmlspecialchars($cat['name']) ?>
                            <span class="cat-count"><?= (int)$cat['cnt'] ?></span>
                        </a>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        <?php endif; ?>

        <!-- Popular Tags -->
        <?php if (!empty($tags)): ?>
        <div class="sidebar-card">
            <div class="sidebar-header">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
                Popular Tags
            </div>
            <div class="sidebar-body">
                <div class="tag-cloud">
                    <?php foreach ($tags as $tag): ?>
                    <a href="<?= tagUrl($tag['slug'] ?? $tag['name']) ?>"
                       class="tag-pill <?= ($tagSlug === ($tag['slug'] ?? '')) ? 'active' : '' ?>">
                        <?= htmlspecialchars($tag['name']) ?>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Latest Posts -->
        <?php if (!empty($latest)): ?>
        <div class="sidebar-card">
            <div class="sidebar-header">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>
                Popular Blogs
            </div>
            <div class="sidebar-body">
                <div class="latest-list">
                    <?php foreach ($latest as $l): ?>
                    <a href="/blog/<?= htmlspecialchars($l['slug']) ?>" class="latest-item">
                        <div class="latest-thumb">
                            <?php if (!empty($l['thumbnail_path'])): ?>
                                <img src="<?= htmlspecialchars($l['thumbnail_path']) ?>"
                                     alt="<?= htmlspecialchars($l['title']) ?>"
                                     loading="lazy">
                            <?php else: ?>
                                <?= htmlspecialchars($l['icon_name'] ?? '📝') ?>
                            <?php endif; ?>
                        </div>
                        <div class="latest-info">
                            <div class="latest-title"><?= htmlspecialchars($l['title']) ?></div>
                            <div class="latest-meta">
                                <?= (int)($l['read_time_mins'] ?? 3) ?> min read &middot;
                                <?= date('M j', strtotime($l['published_at'] ?? 'now')) ?>
                            </div>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Newsletter -->
        <div class="sidebar-card" style="background:linear-gradient(135deg,#111,#161200);">
            <div class="sidebar-header" style="border-color:rgba(255,196,0,0.2);">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                Newsletter
            </div>
            <div class="sidebar-body">
                <div class="newsletter-wrap">
                    <p class="newsletter-desc">Get the latest tutorials and updates delivered to your inbox. No spam.</p>
                    <form class="newsletter-form" onsubmit="return false;">
                        <input type="email" class="newsletter-input" placeholder="your@email.com">
                        <button type="submit" class="newsletter-btn">
                            Subscribe — It's Free ✨
                        </button>
                    </form>
                    <p style="font-size:11px;color:var(--dim);text-align:center;">Coming soon · We respect your privacy</p>
                </div>
            </div>
        </div>

    </aside>
</div>

<!-- ── FOOTER ─────────────────────────────────────────────────────────────── -->
<footer class="blog-footer">
    <div class="footer-inner">
        <div class="footer-brand">
            <div class="footer-logo">
                <div class="nav-logo-icon">CB</div>
                CodeByTushu
            </div>
            <p class="footer-tagline">Sharing knowledge about programming, web development, and problem solving. Helping developers grow.</p>
        </div>
        <div class="footer-col">
            <h4>Quick Links</h4>
            <ul>
                <li><a href="/">Home</a></li>
                <li><a href="/Leetcode/problems.php">LeetCode Solutions</a></li>
                <li><a href="/blogs.php">Blogs</a></li>
                <li><a href="/admin/">Admin</a></li>
            </ul>
        </div>
        <div class="footer-col">
            <h4>Categories</h4>
            <ul>
                <?php foreach (array_slice($cats, 0, 5) as $cat): ?>
                <li><a href="<?= catUrl($cat['slug'] ?? '') ?>"><?= htmlspecialchars($cat['name']) ?></a></li>
                <?php endforeach; ?>
                <?php if (empty($cats)): ?>
                <li><a href="/blogs.php">All Posts</a></li>
                <?php endif; ?>
            </ul>
        </div>
        <div class="footer-col">
            <h4>Contact</h4>
            <ul>
                <li><a href="mailto:info@codebytushu.com">info@codebytushu.com</a></li>
                <li><a href="#">Location: India</a></li>
            </ul>
        </div>
    </div>
    <div class="footer-bottom">
        <span>&copy; <?= date('Y') ?> CodeByTushu. All rights reserved.</span>
        <span>Made with ❤️ for developers</span>
    </div>
</footer>

</body>
</html>
