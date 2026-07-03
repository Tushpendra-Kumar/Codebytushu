<?php
/**
 * CodeByTushu — Premium Single Blog Detail Page
 * URL: /blog/{slug}  (via .htaccess → blog-detail.php?slug=)
 */
declare(strict_types=1);

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$slug = trim($_GET['slug'] ?? '');
if (!$slug) { header('HTTP/1.0 404 Not Found'); require __DIR__ . '/404.php'; exit; }

$pdo = db();

// ── Fetch the blog post ──────────────────────────────────────────────────────
$stmt = $pdo->prepare("
    SELECT b.*, c.name as category_name, c.slug as category_slug,
           u.name as author_name
    FROM blog_articles b
    LEFT JOIN categories c ON b.category_id = c.id
    LEFT JOIN users u ON b.author_id = u.id
    WHERE b.slug = ? AND b.is_published = 1
    LIMIT 1
");
$stmt->execute([$slug]);
$blog = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$blog) { header('HTTP/1.0 404 Not Found'); require __DIR__ . '/404.php'; exit; }

// ── Update view count ────────────────────────────────────────────────────────
$pdo->prepare("UPDATE blog_articles SET view_count = view_count + 1 WHERE id = ?")
    ->execute([$blog['id']]);

// ── Fetch tags for this post ─────────────────────────────────────────────────
$postTags = [];
try {
    $ts = $pdo->prepare("
        SELECT bt.name, bt.slug, bt.color_hex
        FROM blog_tags bt
        JOIN blog_tag_map btm ON btm.tag_id = bt.id
        WHERE btm.article_id = ?
    ");
    $ts->execute([$blog['id']]);
    $postTags = $ts->fetchAll(PDO::FETCH_ASSOC);
} catch (\Throwable) {}

// ── Related posts (same category, exclude current) ──────────────────────────
$related = [];
if (!empty($blog['category_id'])) {
    try {
        $rs = $pdo->prepare("
            SELECT id, title, slug, thumbnail_path, icon_name, excerpt, read_time_mins, published_at
            FROM blog_articles
            WHERE is_published = 1 AND category_id = ? AND id != ?
            ORDER BY published_at DESC LIMIT 3
        ");
        $rs->execute([$blog['category_id'], $blog['id']]);
        $related = $rs->fetchAll(PDO::FETCH_ASSOC);
    } catch (\Throwable) {}
}

// ── Prev / Next posts ────────────────────────────────────────────────────────
try {
    $prevPost = $pdo->prepare("
        SELECT title, slug FROM blog_articles
        WHERE is_published = 1 AND published_at < ? AND id != ?
        ORDER BY published_at DESC LIMIT 1
    ");
    $prevPost->execute([$blog['published_at'], $blog['id']]);
    $prevPost = $prevPost->fetch(PDO::FETCH_ASSOC) ?: null;

    $nextPost = $pdo->prepare("
        SELECT title, slug FROM blog_articles
        WHERE is_published = 1 AND published_at > ? AND id != ?
        ORDER BY published_at ASC LIMIT 1
    ");
    $nextPost->execute([$blog['published_at'], $blog['id']]);
    $nextPost = $nextPost->fetch(PDO::FETCH_ASSOC) ?: null;
} catch (\Throwable) { $prevPost = $nextPost = null; }

// ── Markdown → HTML Parser (zero dependencies) ───────────────────────────────
function parseMarkdown(string $text): string {
    // Protect code blocks first
    $codeBlocks = [];
    $cbIndex = 0;

    // Fenced code blocks ```lang\n...\n```
    $text = preg_replace_callback('/```(\w*)\n?(.*?)```/s', function($m) use (&$codeBlocks, &$cbIndex) {
        $lang = htmlspecialchars($m[1] ?: 'plaintext');
        $code = htmlspecialchars($m[2]);
        $placeholder = "CODEBLOCK_$cbIndex";
        $codeBlocks[$placeholder] = "<div class=\"code-block-wrap\"><div class=\"code-header\"><span class=\"code-lang\">$lang</span><button class=\"copy-btn\" onclick=\"copyCode(this)\">Copy</button></div><pre><code class=\"language-$lang\">$code</code></pre></div>";
        $cbIndex++;
        return $placeholder;
    }, $text);

    // Inline code
    $text = preg_replace('/`([^`]+)`/', '<code class="inline-code">$1</code>', $text);

    // Horizontal rules
    $text = preg_replace('/^(-{3,}|\*{3,}|_{3,})\s*$/m', '<hr class="md-hr">', $text);

    // Headings
    $text = preg_replace('/^#{6}\s+(.+)$/m', '<h6>$1</h6>', $text);
    $text = preg_replace('/^#{5}\s+(.+)$/m', '<h5>$1</h5>', $text);
    $text = preg_replace('/^#{4}\s+(.+)$/m', '<h4>$1</h4>', $text);
    $text = preg_replace('/^###\s+(.+)$/m', '<h3 class="md-h3" id="$1">$1</h3>', $text);
    $text = preg_replace('/^##\s+(.+)$/m',  '<h2 class="md-h2" id="$1">$1</h2>', $text);
    $text = preg_replace('/^#\s+(.+)$/m',   '<h1 class="md-h1">$1</h1>', $text);

    // Blockquotes (must come before paragraphs)
    // Callout boxes: > [!NOTE], > [!WARNING], > [!TIP], > [!IMPORTANT]
    $text = preg_replace_callback('/^(> \[!(NOTE|WARNING|TIP|IMPORTANT|CAUTION)\]\n(?:^>.*\n?)*)/m',
        function($m) {
            $type = strtolower($m[2]);
            $icons = ['note'=>'ℹ️','warning'=>'⚠️','tip'=>'💡','important'=>'❗','caution'=>'🚨'];
            $icon = $icons[$type] ?? 'ℹ️';
            $content = preg_replace('/^> ?/m', '', trim($m[0]));
            $content = preg_replace('/^\[!.*?\]\n?/m', '', $content);
            return "<div class=\"callout callout-$type\"><span class=\"callout-icon\">$icon</span><div class=\"callout-body\">$content</div></div>";
        }, $text);

    // Regular blockquotes
    $text = preg_replace_callback('/^((?:>.*\n?)+)/m', function($m) {
        $content = preg_replace('/^> ?/m', '', trim($m[1]));
        return "<blockquote class=\"md-quote\">$content</blockquote>";
    }, $text);

    // Unordered lists
    $text = preg_replace_callback('/^((?:[-*+]\s+.+\n?)+)/m', function($m) {
        $items = preg_replace('/^[-*+]\s+(.+)$/m', '<li>$1</li>', trim($m[1]));
        return "<ul class=\"md-ul\">$items</ul>";
    }, $text);

    // Ordered lists
    $text = preg_replace_callback('/^((?:\d+\.\s+.+\n?)+)/m', function($m) {
        $items = preg_replace('/^\d+\.\s+(.+)$/m', '<li>$1</li>', trim($m[1]));
        return "<ol class=\"md-ol\">$items</ol>";
    }, $text);

    // Bold + italic
    $text = preg_replace('/\*\*\*(.+?)\*\*\*/', '<strong><em>$1</em></strong>', $text);
    $text = preg_replace('/\*\*(.+?)\*\*/',     '<strong>$1</strong>', $text);
    $text = preg_replace('/\*(.+?)\*/',          '<em>$1</em>', $text);
    $text = preg_replace('/__(.+?)__/',          '<strong>$1</strong>', $text);
    $text = preg_replace('/_(.+?)_/',            '<em>$1</em>', $text);
    $text = preg_replace('/~~(.+?)~~/',          '<del>$1</del>', $text);

    // Images (must come before links)
    $text = preg_replace('/!\[([^\]]*)\]\(([^)]+)\)/', '<img src="$2" alt="$1" class="md-img" loading="lazy">', $text);

    // Links
    $text = preg_replace('/\[([^\]]+)\]\(([^)]+)\)/', '<a href="$2" class="md-link" target="_blank" rel="noopener">$1</a>', $text);

    // Tables
    $text = preg_replace_callback('/^\|.+\|\n\|[-| :]+\|\n((?:\|.+\|\n?)+)/m', function($m) {
        $rows = array_filter(explode("\n", trim($m[0])));
        $html = '<div class="table-wrap"><table class="md-table"><thead>';
        $first = true;
        foreach ($rows as $row) {
            if (strpos($row, '---') !== false) continue;
            $cells = array_map('trim', explode('|', trim($row, '|')));
            $tag = $first ? 'th' : 'td';
            if (!$first && $tag === 'td' && array_search($row, array_values($rows)) === 1) continue;
            $html .= '<tr>';
            foreach ($cells as $cell) {
                $html .= "<$tag>$cell</$tag>";
            }
            $html .= '</tr>';
            if ($first) { $html .= '</thead><tbody>'; $first = false; }
        }
        $html .= '</tbody></table></div>';
        return $html;
    }, $text);

    // Paragraphs — wrap lines not already wrapped in HTML
    $lines  = explode("\n", $text);
    $output = '';
    $inHtml = false;
    foreach ($lines as $line) {
        $trimmed = trim($line);
        if ($trimmed === '') { $output .= "\n"; continue; }
        if (preg_match('/^<(h[1-6]|ul|ol|li|blockquote|pre|div|hr|img|table|thead|tbody|tr|td|th)/i', $trimmed)) {
            $output .= $trimmed . "\n";
        } elseif (str_starts_with($trimmed, 'CODEBLOCK_')) {
            $output .= $trimmed . "\n";
        } else {
            $output .= "<p class=\"md-p\">{$trimmed}</p>\n";
        }
    }

    // Restore code blocks
    foreach ($codeBlocks as $placeholder => $html) {
        $output = str_replace($placeholder, $html, $output);
    }

    return $output;
}

// ── Generate Table of Contents ───────────────────────────────────────────────
function generateTOC(string $content): array {
    preg_match_all('/^#{2,3}\s+(.+)$/m', $content, $matches);
    $toc = [];
    foreach ($matches[0] as $i => $heading) {
        $level = strlen(rtrim(explode(' ', $heading)[0], ' '));
        $title = trim(ltrim($heading, '# '));
        $id    = strtolower(preg_replace('/[^a-z0-9]+/', '-', $title));
        $toc[] = ['level' => $level, 'title' => $title, 'id' => $id];
    }
    return $toc;
}

$parsedContent = parseMarkdown($blog['content'] ?? '');
$toc           = generateTOC($blog['content'] ?? '');

// ── SEO ──────────────────────────────────────────────────────────────────────
$seoTitle = $blog['seo_title'] ?: $blog['title'] . ' | CodeByTushu';
$seoDesc  = $blog['meta_description'] ?: mb_strimwidth(strip_tags($blog['excerpt'] ?? ''), 0, 160, '...');
$ogImage  = $blog['og_image_url'] ?: $blog['thumbnail_path'] ?: '';
$fullUrl  = SITE_URL . '/blog/' . $blog['slug'];
$heroImg  = $blog['cover_image_path'] ?: $blog['thumbnail_path'] ?: '';
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($seoTitle) ?></title>
    <meta name="description" content="<?= e($seoDesc) ?>">
    <?php if (!empty($blog['meta_keywords'])): ?>
    <meta name="keywords" content="<?= e($blog['meta_keywords']) ?>">
    <?php endif; ?>
    <meta property="og:title"       content="<?= e($blog['title']) ?>">
    <meta property="og:description" content="<?= e($seoDesc) ?>">
    <meta property="og:type"        content="article">
    <meta property="og:url"         content="<?= e($fullUrl) ?>">
    <?php if ($ogImage): ?>
    <meta property="og:image" content="<?= e(SITE_URL . $ogImage) ?>">
    <?php endif; ?>
    <meta name="twitter:card"  content="summary_large_image">
    <meta name="twitter:title" content="<?= e($blog['title']) ?>">
    <link rel="canonical" href="<?= e($fullUrl) ?>">
    <link rel="icon" href="/favicon.ico?v=6" sizes="any">
    <script src="/theme.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Fira+Code:wght@400;500&display=swap" rel="stylesheet">
    <!-- Syntax Highlighting -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/atom-one-dark.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/highlight.min.js" defer></script>
<style>
/* ═══════════════════════════════════════════════════
   BLOG DETAIL — PREMIUM DARK THEME
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
    --font-mono:'Fira Code', 'Courier New', monospace;
    --radius:   12px;
    --content-w: 720px;
}

body { font-family: var(--font); background: var(--bg); color: var(--text); line-height: 1.6; }
a { text-decoration: none; color: inherit; }
img { max-width: 100%; display: block; }

/* ── NAVBAR ── */
.navbar {
    position: sticky; top: 0; z-index: 200;
    background: rgba(10,10,10,0.92);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    border-bottom: 1px solid var(--border);
    padding: 0 24px;
}
.navbar-inner {
    max-width: 1280px; margin: 0 auto; height: 64px;
    display: flex; align-items: center; justify-content: space-between; gap: 20px;
}
.nav-logo { display: flex; align-items: center; gap: 10px; font-size: 18px; font-weight: 800; }
.nav-logo-icon {
    width: 36px; height: 36px; background: var(--accent); border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    color: #000; font-size: 14px; font-weight: 800; flex-shrink: 0;
}
.nav-links { display: flex; align-items: center; gap: 6px; }
.nav-link { color: var(--muted); font-size: 14px; font-weight: 500; padding: 6px 12px; border-radius: 8px; transition: all .2s; }
.nav-link:hover { color: var(--text); background: rgba(255,255,255,.06); }

/* ── HERO COVER ── */
.blog-cover {
    position: relative;
    width: 100%;
    height: 420px;
    overflow: hidden;
    background: linear-gradient(135deg, #0a0a0a, #111);
}
.cover-img {
    width: 100%; height: 100%;
    object-fit: cover;
    opacity: 0.5;
    transition: opacity 0.5s;
}
.cover-overlay {
    position: absolute; inset: 0;
    background: linear-gradient(to bottom, rgba(10,10,10,0.2) 0%, rgba(10,10,10,0.9) 100%);
}
.cover-placeholder {
    width: 100%; height: 100%;
    display: flex; align-items: center; justify-content: center;
    font-size: 96px;
    background: linear-gradient(135deg, #111, #1a1a0a);
}

/* ── BLOG META HEADER ── */
.blog-header-wrap {
    max-width: 1280px; margin: 0 auto; padding: 40px 24px 0;
}
.blog-header {
    max-width: var(--content-w);
}
.header-badges { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; margin-bottom: 16px; }
.cat-badge {
    background: rgba(255,196,0,0.12); color: var(--accent);
    border: 1px solid rgba(255,196,0,0.25); font-size: 12px; font-weight: 600;
    padding: 4px 12px; border-radius: 99px; text-transform: uppercase; letter-spacing: 0.5px;
}
.tag-badge {
    background: var(--bg-card); color: var(--muted);
    border: 1px solid var(--border); font-size: 11px; font-weight: 500;
    padding: 3px 10px; border-radius: 99px;
    transition: all .2s;
}
.tag-badge:hover { color: var(--accent); border-color: rgba(255,196,0,.3); }
.blog-h1 {
    font-size: clamp(26px, 4vw, 44px); font-weight: 800;
    line-height: 1.2; letter-spacing: -0.5px;
    margin-bottom: 20px;
}
.blog-meta-row {
    display: flex; align-items: center; gap: 20px;
    flex-wrap: wrap; margin-bottom: 24px;
}
.meta-item { display: flex; align-items: center; gap: 6px; font-size: 13px; color: var(--muted); }
.meta-item svg { flex-shrink: 0; }
.meta-divider { width: 1px; height: 14px; background: var(--dim); }
.share-row {
    display: flex; align-items: center; gap: 8px;
    flex-wrap: wrap; padding: 20px 0;
    border-top: 1px solid var(--border); border-bottom: 1px solid var(--border);
    margin-bottom: 40px;
}
.share-label { font-size: 13px; font-weight: 600; color: var(--muted); margin-right: 4px; }
.share-btn {
    display: inline-flex; align-items: center; gap: 6px;
    font-size: 13px; font-weight: 500;
    padding: 7px 14px; border-radius: 8px; border: 1px solid var(--border);
    background: var(--bg-card); color: var(--muted);
    cursor: pointer; transition: all .2s; font-family: var(--font);
}
.share-btn:hover { border-color: var(--accent); color: var(--accent); }
.share-btn.twitter:hover  { border-color: #1da1f2; color: #1da1f2; }
.share-btn.linkedin:hover { border-color: #0077b5; color: #0077b5; }
.share-btn.whatsapp:hover { border-color: #25d366; color: #25d366; }
.share-btn.copy-link { cursor: pointer; }
.share-btn.copy-link.copied { border-color: #22c55e; color: #22c55e; }

/* ── MAIN LAYOUT ── */
.blog-body {
    max-width: 1280px; margin: 0 auto;
    padding: 0 24px 80px;
    display: grid;
    grid-template-columns: 1fr 280px;
    gap: 56px;
    align-items: start;
}

/* ── CONTENT AREA ── */
.blog-content-area { max-width: var(--content-w); }

/* ── MARKDOWN STYLES ── */
.md-content { font-size: 17px; line-height: 1.85; color: #ccc; }
.md-content .md-h1 { font-size: 32px; font-weight: 800; color: var(--text); margin: 40px 0 16px; }
.md-content .md-h2 { font-size: 26px; font-weight: 700; color: var(--text); margin: 36px 0 12px; padding-top: 8px; border-top: 1px solid var(--border); }
.md-content .md-h3 { font-size: 20px; font-weight: 700; color: var(--text); margin: 28px 0 10px; }
.md-content .md-p  { margin-bottom: 20px; }
.md-content .md-p:last-child { margin-bottom: 0; }
.md-content strong { color: var(--text); font-weight: 700; }
.md-content em { font-style: italic; color: var(--text); }
.md-content del { color: var(--muted); text-decoration: line-through; }
.md-content .md-hr { border: none; border-top: 1px solid var(--border); margin: 32px 0; }

/* Links */
.md-content .md-link {
    color: var(--accent); border-bottom: 1px solid rgba(255,196,0,0.3);
    transition: border-color .2s;
}
.md-content .md-link:hover { border-bottom-color: var(--accent); }

/* Images */
.md-content .md-img {
    max-width: 100%; border-radius: var(--radius);
    margin: 24px auto; border: 1px solid var(--border);
    box-shadow: 0 4px 20px rgba(0,0,0,0.5);
}

/* Blockquote */
.md-content .md-quote {
    border-left: 4px solid var(--accent);
    background: rgba(255,196,0,0.04);
    border-radius: 0 8px 8px 0;
    padding: 14px 20px;
    margin: 24px 0;
    font-style: italic;
    color: #aaa;
}

/* Lists */
.md-content .md-ul, .md-content .md-ol {
    padding-left: 24px; margin-bottom: 20px;
}
.md-content .md-ul li, .md-content .md-ol li {
    margin-bottom: 8px; color: #ccc;
    padding-left: 4px;
}
.md-content .md-ul li::marker { color: var(--accent); }
.md-content .md-ol li::marker { color: var(--accent); font-weight: 700; }

/* Inline code */
.md-content .inline-code {
    font-family: var(--font-mono); font-size: 14px;
    background: rgba(255,196,0,0.08); color: var(--accent2);
    border: 1px solid rgba(255,196,0,0.15);
    padding: 2px 7px; border-radius: 5px;
}

/* Code blocks */
.code-block-wrap {
    margin: 28px 0;
    border-radius: var(--radius);
    overflow: hidden;
    border: 1px solid rgba(255,255,255,0.08);
    box-shadow: 0 4px 20px rgba(0,0,0,0.5);
}
.code-header {
    display: flex; align-items: center; justify-content: space-between;
    padding: 8px 16px;
    background: #1a1d23;
    border-bottom: 1px solid rgba(255,255,255,0.05);
}
.code-lang {
    font-family: var(--font-mono); font-size: 12px;
    color: #888; text-transform: uppercase; letter-spacing: 1px;
}
.copy-btn {
    background: rgba(255,255,255,0.08); border: none;
    color: #888; font-size: 12px; font-weight: 500;
    padding: 4px 10px; border-radius: 5px;
    cursor: pointer; font-family: var(--font); transition: all .2s;
}
.copy-btn:hover { background: rgba(255,196,0,0.15); color: var(--accent); }
.copy-btn.copied { color: #22c55e; }
.code-block-wrap pre {
    background: #1e2227; padding: 20px; overflow-x: auto;
    margin: 0;
}
.code-block-wrap pre code {
    font-family: var(--font-mono); font-size: 14px;
    line-height: 1.7; background: none; padding: 0;
}

/* Tables */
.table-wrap { overflow-x: auto; margin: 24px 0; }
.md-table {
    width: 100%; border-collapse: collapse;
    font-size: 14px;
}
.md-table th {
    background: rgba(255,196,0,0.08); color: var(--text);
    font-weight: 600; padding: 10px 14px;
    border: 1px solid var(--border); text-align: left;
}
.md-table td {
    padding: 9px 14px; border: 1px solid var(--border);
    color: #ccc;
}
.md-table tr:nth-child(even) td { background: rgba(255,255,255,0.02); }
.md-table tr:hover td { background: rgba(255,196,0,0.03); }

/* Callout boxes */
.callout {
    display: flex; gap: 12px; align-items: flex-start;
    padding: 14px 16px; border-radius: 10px;
    margin: 20px 0; border: 1px solid;
}
.callout-note      { background: rgba(59,130,246,.08); border-color: rgba(59,130,246,.3); }
.callout-warning   { background: rgba(251,191,36,.08); border-color: rgba(251,191,36,.3); }
.callout-tip       { background: rgba(34,197,94,.08);  border-color: rgba(34,197,94,.3); }
.callout-important { background: rgba(239,68,68,.08);  border-color: rgba(239,68,68,.3); }
.callout-caution   { background: rgba(249,115,22,.08); border-color: rgba(249,115,22,.3); }
.callout-icon { font-size: 20px; flex-shrink: 0; margin-top: 1px; }
.callout-body { font-size: 14px; line-height: 1.6; color: #ccc; }

/* ── STICKY SIDEBAR ── */
.blog-sidebar-right {
    position: sticky; top: 80px;
    display: flex; flex-direction: column; gap: 20px;
}
.sb-card {
    background: var(--bg-card); border: 1px solid var(--border);
    border-radius: var(--radius); overflow: hidden;
}
.sb-header {
    padding: 12px 16px; border-bottom: 1px solid var(--border);
    font-size: 12px; font-weight: 700; text-transform: uppercase;
    letter-spacing: 0.5px; color: var(--muted);
    display: flex; align-items: center; gap: 6px;
}
.sb-header svg { color: var(--accent); }
.sb-body { padding: 16px; }

/* TOC */
.toc-list { list-style: none; display: flex; flex-direction: column; gap: 2px; }
.toc-item a {
    font-size: 13px; color: var(--muted); display: block;
    padding: 5px 8px; border-radius: 6px; border-left: 2px solid transparent;
    transition: all .2s; line-height: 1.4;
}
.toc-item a:hover { color: var(--accent); border-left-color: var(--accent); background: rgba(255,196,0,.04); }
.toc-item.active a { color: var(--accent); border-left-color: var(--accent); background: rgba(255,196,0,.05); }
.toc-item.level-3 a { padding-left: 20px; font-size: 12px; }

/* ── ARTICLE END SECTIONS ── */
.article-end { max-width: var(--content-w); }

/* Share at end */
.share-section {
    background: var(--bg-card); border: 1px solid var(--border);
    border-radius: var(--radius); padding: 28px;
    text-align: center; margin-bottom: 32px;
}
.share-section h3 { font-size: 18px; font-weight: 700; margin-bottom: 6px; }
.share-section p { font-size: 14px; color: var(--muted); margin-bottom: 20px; }
.share-buttons { display: flex; justify-content: center; gap: 10px; flex-wrap: wrap; }

/* Author Box */
.author-box {
    background: var(--bg-card); border: 1px solid var(--border);
    border-radius: var(--radius); padding: 28px;
    display: flex; gap: 20px; align-items: flex-start;
    margin-bottom: 32px;
}
.author-avatar {
    width: 70px; height: 70px; border-radius: 50%;
    background: linear-gradient(135deg, var(--accent), #ff8800);
    display: flex; align-items: center; justify-content: center;
    font-size: 26px; font-weight: 800; color: #000;
    flex-shrink: 0; border: 3px solid rgba(255,196,0,0.3);
}
.author-info { flex: 1; }
.author-label { font-size: 11px; color: var(--muted); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px; }
.author-name { font-size: 18px; font-weight: 700; margin-bottom: 4px; }
.author-role { font-size: 13px; color: var(--accent); margin-bottom: 10px; }
.author-bio { font-size: 14px; color: var(--muted); line-height: 1.6; }

/* Related Posts */
.section-heading {
    font-size: 20px; font-weight: 700;
    margin-bottom: 20px;
    display: flex; align-items: center; gap: 8px;
}
.section-heading::before {
    content: ''; width: 4px; height: 20px;
    background: var(--accent); border-radius: 4px; display: block;
}
.related-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 32px; }
.related-card {
    background: var(--bg-card); border: 1px solid var(--border);
    border-radius: var(--radius); overflow: hidden;
    transition: all .3s;
}
.related-card:hover { transform: translateY(-3px); border-color: var(--border-h); }
.related-thumb {
    height: 120px; background: #1a1a1a;
    display: flex; align-items: center; justify-content: center;
    font-size: 36px; overflow: hidden;
}
.related-thumb img { width: 100%; height: 100%; object-fit: cover; }
.related-body { padding: 14px; }
.related-title { font-size: 14px; font-weight: 600; line-height: 1.4; margin-bottom: 6px; color: var(--text); transition: color .2s; }
.related-card:hover .related-title { color: var(--accent); }
.related-meta { font-size: 12px; color: var(--muted); }

/* Prev / Next Nav */
.prev-next {
    display: grid; grid-template-columns: 1fr 1fr; gap: 16px;
    margin-bottom: 32px;
}
.nav-post {
    background: var(--bg-card); border: 1px solid var(--border);
    border-radius: var(--radius); padding: 16px 20px;
    transition: all .2s;
}
.nav-post:hover { border-color: var(--border-h); transform: translateX(0) translateY(-2px); }
.nav-post.next { text-align: right; }
.nav-dir { font-size: 11px; color: var(--muted); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px; display: flex; align-items: center; gap: 5px; }
.nav-post.next .nav-dir { justify-content: flex-end; }
.nav-post-title { font-size: 14px; font-weight: 600; color: var(--text); line-height: 1.4; transition: color .2s; }
.nav-post:hover .nav-post-title { color: var(--accent); }

/* Comments (Future) */
.comments-section {
    background: var(--bg-card); border: 1px solid var(--border);
    border-radius: var(--radius); padding: 28px; margin-bottom: 32px;
}
.comments-section h3 { font-size: 18px; font-weight: 700; margin-bottom: 20px; }
.comment-coming-soon {
    text-align: center; padding: 40px 20px;
    border: 2px dashed var(--border); border-radius: 10px;
}
.comment-coming-soon span { font-size: 36px; display: block; margin-bottom: 12px; }
.comment-coming-soon h4 { font-size: 15px; font-weight: 600; color: var(--text); margin-bottom: 6px; }
.comment-coming-soon p { font-size: 13px; color: var(--muted); }

/* ── FOOTER ── */
.blog-footer { background: #060606; border-top: 1px solid var(--border); padding: 40px 24px 24px; }
.footer-inner { max-width: 1280px; margin: 0 auto; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 20px; }
.footer-brand-sm { display: flex; align-items: center; gap: 10px; font-size: 16px; font-weight: 800; }
.footer-links { display: flex; gap: 20px; flex-wrap: wrap; }
.footer-links a { font-size: 13px; color: var(--muted); transition: color .2s; }
.footer-links a:hover { color: var(--accent); }
.footer-cr { font-size: 12px; color: var(--dim); }

/* ── RESPONSIVE ── */
@media (max-width: 1024px) {
    .blog-body { grid-template-columns: 1fr; }
    .blog-sidebar-right { position: static; }
    .related-grid { grid-template-columns: 1fr 1fr; }
}
@media (max-width: 768px) {
    .blog-cover { height: 260px; }
    .blog-h1 { font-size: 26px; }
    .related-grid { grid-template-columns: 1fr; }
    .prev-next { grid-template-columns: 1fr; }
    .author-box { flex-direction: column; }
    .share-row { gap: 6px; }
    .nav-links { gap: 2px; }
    .nav-link { padding: 6px 8px; font-size: 13px; }
    .blog-body { padding: 0 16px 60px; }
}
@media (max-width: 480px) {
    .blog-header-wrap { padding: 24px 16px 0; }
    .share-btn { padding: 6px 10px; font-size: 12px; }
}

/* ── ANIMATIONS ── */
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(16px); }
    to   { opacity: 1; transform: translateY(0); }
}
.blog-header { animation: fadeIn 0.5s ease both; }
.blog-content-area { animation: fadeIn 0.6s ease 0.1s both; }

/* ── READING PROGRESS ── */
.reading-progress {
    position: fixed; top: 0; left: 0; z-index: 9999;
    height: 3px; background: var(--accent);
    width: 0%; transition: width .1s linear;
}
</style>
</head>
<body>

<!-- Reading Progress -->
<div class="reading-progress" id="readingProgress"></div>

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
            <a href="/blogs.php" class="nav-link">Blog</a>
            <a href="/admin/" class="nav-link">Admin</a>
        </div>
    </div>
</nav>

<!-- ── COVER IMAGE ────────────────────────────────────────────────────────── -->
<?php if ($heroImg): ?>
<div class="blog-cover">
    <img src="<?= e($heroImg) ?>" alt="<?= e($blog['title']) ?>" class="cover-img" loading="eager">
    <div class="cover-overlay"></div>
</div>
<?php elseif (!empty($blog['icon_name'])): ?>
<div class="blog-cover">
    <div class="cover-placeholder"><?= e($blog['icon_name']) ?></div>
</div>
<?php endif; ?>

<!-- ── BLOG HEADER ────────────────────────────────────────────────────────── -->
<div class="blog-header-wrap">
    <div class="blog-header">
        <!-- Badges -->
        <div class="header-badges">
            <?php if (!empty($blog['category_name'])): ?>
            <a href="/blogs.php?cat=<?= e($blog['category_slug'] ?? '') ?>" class="cat-badge">
                <?= e($blog['category_name']) ?>
            </a>
            <?php endif; ?>
            <?php foreach ($postTags as $tag): ?>
            <a href="/blogs.php?tag=<?= e($tag['slug'] ?? '') ?>" class="tag-badge">
                <?= e($tag['name']) ?>
            </a>
            <?php endforeach; ?>
        </div>

        <!-- Title -->
        <h1 class="blog-h1"><?= e($blog['title']) ?></h1>

        <!-- Meta Row -->
        <div class="blog-meta-row">
            <span class="meta-item">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                <?= e($blog['author_name'] ?? 'Tushpendra Kumar') ?>
            </span>
            <div class="meta-divider"></div>
            <span class="meta-item">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                <?= date('d M Y', strtotime($blog['published_at'] ?? 'now')) ?>
            </span>
            <div class="meta-divider"></div>
            <span class="meta-item">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                <?= (int)($blog['read_time_mins'] ?? 3) ?> min read
            </span>
            <div class="meta-divider"></div>
            <span class="meta-item">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                <?= number_format(($blog['view_count'] ?? 0) + 1) ?> views
            </span>
        </div>

        <!-- Share Row -->
        <div class="share-row">
            <span class="share-label">Share:</span>
            <a href="https://twitter.com/intent/tweet?url=<?= urlencode($fullUrl) ?>&text=<?= urlencode($blog['title']) ?>"
               target="_blank" rel="noopener" class="share-btn twitter">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-4.714-6.231-5.401 6.231H2.747l7.73-8.835L1.254 2.25H8.08l4.259 5.63L18.244 2.25zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                Twitter
            </a>
            <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?= urlencode($fullUrl) ?>"
               target="_blank" rel="noopener" class="share-btn linkedin">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 0 1-2.063-2.065 2.064 2.064 0 1 1 2.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
                LinkedIn
            </a>
            <a href="https://wa.me/?text=<?= urlencode($blog['title'] . ' ' . $fullUrl) ?>"
               target="_blank" rel="noopener" class="share-btn whatsapp">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51a12.8 12.8 0 0 0-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413z"/></svg>
                WhatsApp
            </a>
            <button onclick="copyBlogUrl()" class="share-btn copy-link" id="copyBtn">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
                Copy Link
            </button>
        </div>
    </div>
</div>

<!-- ── BLOG BODY ───────────────────────────────────────────────────────────── -->
<div class="blog-body">

    <!-- CONTENT COLUMN -->
    <article class="blog-content-area">
        <!-- Markdown Content -->
        <div class="md-content" id="blogContent">
            <?= $parsedContent ?>
        </div>

        <!-- Article End Sections -->
        <div class="article-end" style="margin-top: 60px;">

            <!-- Share Section -->
            <div class="share-section">
                <h3>📢 Share This Article</h3>
                <p>If you found this helpful, share it with your developer friends!</p>
                <div class="share-buttons">
                    <a href="https://twitter.com/intent/tweet?url=<?= urlencode($fullUrl) ?>&text=<?= urlencode($blog['title']) ?>"
                       target="_blank" rel="noopener" class="share-btn twitter">🐦 Twitter</a>
                    <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?= urlencode($fullUrl) ?>"
                       target="_blank" rel="noopener" class="share-btn linkedin">💼 LinkedIn</a>
                    <a href="https://wa.me/?text=<?= urlencode($blog['title'] . ' ' . $fullUrl) ?>"
                       target="_blank" rel="noopener" class="share-btn whatsapp">💬 WhatsApp</a>
                    <button onclick="copyBlogUrl()" class="share-btn copy-link">🔗 Copy Link</button>
                </div>
            </div>

            <!-- Author Box -->
            <div class="author-box">
                <div class="author-avatar">
                    <?= strtoupper(substr($blog['author_name'] ?? 'T', 0, 1)) ?>
                </div>
                <div class="author-info">
                    <div class="author-label">Written by</div>
                    <div class="author-name"><?= e($blog['author_name'] ?? 'Tushpendra Kumar') ?></div>
                    <div class="author-role">CodeByTushu Team</div>
                    <div class="author-bio">
                        Full Stack Developer, Content Creator, and Problem Solver.
                        Passionate about teaching programming and helping developers grow through practical tutorials.
                    </div>
                </div>
            </div>

            <!-- Related Blogs -->
            <?php if (!empty($related)): ?>
            <div style="margin-bottom: 32px;">
                <h2 class="section-heading">Related Articles</h2>
                <div class="related-grid">
                    <?php foreach ($related as $r): ?>
                    <a href="/blog/<?= e($r['slug']) ?>" class="related-card">
                        <div class="related-thumb">
                            <?php if (!empty($r['thumbnail_path'])): ?>
                                <img src="<?= e($r['thumbnail_path']) ?>" alt="<?= e($r['title']) ?>" loading="lazy">
                            <?php else: ?>
                                <?= e($r['icon_name'] ?? '📝') ?>
                            <?php endif; ?>
                        </div>
                        <div class="related-body">
                            <div class="related-title"><?= e($r['title']) ?></div>
                            <div class="related-meta"><?= (int)($r['read_time_mins'] ?? 3) ?> min read</div>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Prev / Next Navigation -->
            <?php if ($prevPost || $nextPost): ?>
            <div class="prev-next" style="margin-bottom: 32px;">
                <?php if ($prevPost): ?>
                <a href="/blog/<?= e($prevPost['slug']) ?>" class="nav-post prev">
                    <div class="nav-dir">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 5l-7 7 7 7"/></svg>
                        Previous Article
                    </div>
                    <div class="nav-post-title"><?= e($prevPost['title']) ?></div>
                </a>
                <?php else: ?><div></div><?php endif; ?>

                <?php if ($nextPost): ?>
                <a href="/blog/<?= e($nextPost['slug']) ?>" class="nav-post next">
                    <div class="nav-dir">
                        Next Article
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                    </div>
                    <div class="nav-post-title"><?= e($nextPost['title']) ?></div>
                </a>
                <?php else: ?><div></div><?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- Comments (Future Ready) -->
            <div class="comments-section">
                <h3>💬 Comments (0)</h3>
                <div class="comment-coming-soon">
                    <span>🚀</span>
                    <h4>Comments Coming Soon</h4>
                    <p>We're working on the comment system. Stay tuned!</p>
                </div>
            </div>

        </div><!-- /article-end -->
    </article>

    <!-- STICKY SIDEBAR -->
    <aside class="blog-sidebar-right">

        <!-- Table of Contents -->
        <?php if (!empty($toc)): ?>
        <div class="sb-card">
            <div class="sb-header">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
                Table of Contents
            </div>
            <div class="sb-body">
                <ul class="toc-list" id="tocList">
                    <?php foreach ($toc as $item): ?>
                    <li class="toc-item level-<?= $item['level'] ?>" data-id="<?= e($item['id']) ?>">
                        <a href="#<?= e($item['id']) ?>">
                            <?= e($item['title']) ?>
                        </a>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        <?php endif; ?>

        <!-- Share Sidebar -->
        <div class="sb-card">
            <div class="sb-header">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg>
                Share Article
            </div>
            <div class="sb-body" style="display:flex;flex-direction:column;gap:8px;">
                <a href="https://twitter.com/intent/tweet?url=<?= urlencode($fullUrl) ?>&text=<?= urlencode($blog['title']) ?>"
                   target="_blank" rel="noopener" class="share-btn" style="justify-content:center;">🐦 Twitter</a>
                <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?= urlencode($fullUrl) ?>"
                   target="_blank" rel="noopener" class="share-btn" style="justify-content:center;">💼 LinkedIn</a>
                <a href="https://wa.me/?text=<?= urlencode($blog['title'] . ' ' . $fullUrl) ?>"
                   target="_blank" rel="noopener" class="share-btn" style="justify-content:center;">💬 WhatsApp</a>
                <button onclick="copyBlogUrl()" class="share-btn" style="justify-content:center;">🔗 Copy Link</button>
            </div>
        </div>

        <!-- Back to Blog -->
        <a href="/blogs.php" class="share-btn" style="justify-content:center;padding:12px;border-radius:var(--radius);text-align:center;">
            ← Back to All Blogs
        </a>

    </aside>
</div>

<!-- ── FOOTER ─────────────────────────────────────────────────────────────── -->
<footer class="blog-footer">
    <div class="footer-inner">
        <div class="footer-brand-sm">
            <div class="nav-logo-icon">CB</div>
            CodeByTushu
        </div>
        <div class="footer-links">
            <a href="/">Home</a>
            <a href="/blogs.php">Blog</a>
            <a href="/Leetcode/problems.php">LeetCode</a>
            <a href="/admin/">Admin</a>
        </div>
        <div class="footer-cr">&copy; <?= date('Y') ?> CodeByTushu. All rights reserved.</div>
    </div>
</footer>

<script>
// ── Reading Progress Bar ─────────────────────────────────────────────────────
window.addEventListener('scroll', () => {
    const prog = document.getElementById('readingProgress');
    if (!prog) return;
    const docH = document.documentElement.scrollHeight - window.innerHeight;
    prog.style.width = docH > 0 ? (window.scrollY / docH * 100) + '%' : '0%';
});

// ── Heading IDs (re-tag headings for TOC links) ──────────────────────────────
document.querySelectorAll('.md-content h2, .md-content h3').forEach(h => {
    if (!h.id) {
        h.id = h.textContent.trim().toLowerCase().replace(/[^a-z0-9]+/g, '-');
    }
});

// ── TOC Active State on Scroll ───────────────────────────────────────────────
const tocItems = document.querySelectorAll('.toc-item');
const headings = document.querySelectorAll('.md-content h2, .md-content h3');

if (tocItems.length && headings.length) {
    const observer = new IntersectionObserver(entries => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                tocItems.forEach(li => li.classList.remove('active'));
                const active = document.querySelector(`.toc-item[data-id="${entry.target.id}"]`);
                if (active) active.classList.add('active');
            }
        });
    }, { rootMargin: '0px 0px -60% 0px', threshold: 0 });

    headings.forEach(h => observer.observe(h));
}

// ── Copy Blog URL ────────────────────────────────────────────────────────────
function copyBlogUrl() {
    navigator.clipboard.writeText(window.location.href).then(() => {
        document.querySelectorAll('.copy-link').forEach(btn => {
            btn.classList.add('copied');
            btn.textContent = '✅ Copied!';
            setTimeout(() => {
                btn.classList.remove('copied');
                btn.innerHTML = '🔗 Copy Link';
            }, 2000);
        });
    });
}

// ── Copy Code Button ─────────────────────────────────────────────────────────
function copyCode(btn) {
    const code = btn.closest('.code-block-wrap').querySelector('code');
    navigator.clipboard.writeText(code.innerText).then(() => {
        btn.textContent = '✅ Copied!';
        btn.classList.add('copied');
        setTimeout(() => {
            btn.textContent = 'Copy';
            btn.classList.remove('copied');
        }, 2000);
    });
}

// ── Smooth TOC Scroll ────────────────────────────────────────────────────────
document.querySelectorAll('.toc-item a').forEach(a => {
    a.addEventListener('click', e => {
        e.preventDefault();
        const target = document.getElementById(a.getAttribute('href').slice(1));
        if (target) {
            window.scrollTo({ top: target.offsetTop - 90, behavior: 'smooth' });
        }
    });
});

// ── Syntax Highlighting ──────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    if (window.hljs) {
        document.querySelectorAll('pre code').forEach(el => hljs.highlightElement(el));
    }
});
</script>

</body>
</html>
