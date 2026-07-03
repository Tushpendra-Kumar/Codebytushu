<?php
/**
 * CodeByTushu — Single Blog Detail Page
 * Fetches a blog by slug and renders it.
 */
declare(strict_types=1);

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$slug = $_GET['slug'] ?? '';
if (!$slug) {
    header("HTTP/1.0 404 Not Found");
    require __DIR__ . '/404.php';
    exit;
}

$pdo = db();

// Fetch the blog
$stmt = $pdo->prepare("
    SELECT b.*, c.name as category_name
    FROM blog_articles b
    LEFT JOIN categories c ON b.category_id = c.id
    WHERE b.slug = ? AND b.is_published = 1
    LIMIT 1
");
$stmt->execute([$slug]);
$blog = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$blog) {
    header("HTTP/1.0 404 Not Found");
    require __DIR__ . '/404.php';
    exit;
}

// Update view count
$pdo->prepare("UPDATE blog_articles SET view_count = view_count + 1 WHERE id = ?")->execute([$blog['id']]);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($blog['title']) ?> | CodeByTushu</title>
    
    <!-- SEO Meta Tags -->
    <meta name="description" content="<?= htmlspecialchars($blog['excerpt'] ?? '') ?>">
    
    <link rel="icon" href="/favicon.ico?v=6" sizes="any">
    <script src="/theme.js"></script>
    <link rel="stylesheet" href="/styles.css">
    
    <!-- Marked.js for Markdown parsing -->
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
    
    <!-- Highlight.js for Code syntax highlighting -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.8.0/styles/atom-one-dark.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.8.0/highlight.min.js"></script>

    <style>
        .blog-header {
            max-width: 800px;
            margin: 60px auto 40px;
            padding: 0 20px;
            text-align: center;
        }
        .blog-meta {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
            font-size: 14px;
            color: var(--text-dim);
            margin-bottom: 20px;
        }
        .blog-category {
            background: rgba(255,196,0,0.1);
            color: var(--accent);
            padding: 4px 12px;
            border-radius: 99px;
            font-weight: 600;
        }
        .blog-title {
            font-size: 42px;
            font-weight: 800;
            color: var(--text);
            line-height: 1.3;
            margin-bottom: 30px;
        }
        
        .blog-hero-img {
            width: 100%;
            max-width: 900px;
            height: auto;
            max-height: 500px;
            object-fit: cover;
            border-radius: 16px;
            margin: 0 auto 40px;
            display: block;
            border: 1px solid var(--border);
        }
        
        .blog-content {
            max-width: 800px;
            margin: 0 auto;
            padding: 0 20px 80px;
            font-size: 18px;
            line-height: 1.8;
            color: var(--text-muted);
        }
        
        /* Markdown Styling inside blog-content */
        .blog-content h1, .blog-content h2, .blog-content h3 {
            color: var(--text);
            margin-top: 40px;
            margin-bottom: 15px;
            font-weight: 700;
            line-height: 1.3;
        }
        .blog-content h1 { font-size: 32px; }
        .blog-content h2 { font-size: 26px; }
        .blog-content h3 { font-size: 22px; }
        
        .blog-content p {
            margin-bottom: 20px;
        }
        
        .blog-content a {
            color: var(--accent);
            text-decoration: none;
            border-bottom: 1px solid transparent;
            transition: border-color 0.2s;
        }
        .blog-content a:hover {
            border-bottom-color: var(--accent);
        }
        
        .blog-content img {
            max-width: 100%;
            border-radius: 8px;
            margin: 20px 0;
            display: block;
        }
        
        .blog-content pre {
            background: #1e1e2e;
            padding: 20px;
            border-radius: 12px;
            overflow-x: auto;
            margin: 30px 0;
            border: 1px solid var(--border);
        }
        .blog-content code {
            font-family: 'Fira Code', monospace;
            font-size: 14px;
        }
        /* inline code */
        .blog-content p code, .blog-content li code {
            background: var(--input-bg);
            color: var(--text);
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 14px;
            border: 1px solid var(--border);
        }
        
        .blog-content blockquote {
            border-left: 4px solid var(--accent);
            margin: 30px 0;
            padding: 10px 20px;
            background: var(--bg-hover);
            border-radius: 0 8px 8px 0;
            font-style: italic;
        }
        
        .blog-content ul, .blog-content ol {
            margin-bottom: 20px;
            padding-left: 20px;
        }
        .blog-content li {
            margin-bottom: 10px;
        }
        
        @media(max-width: 768px) {
            .blog-title { font-size: 32px; }
            .blog-content { font-size: 16px; }
        }
    </style>
</head>
<body class="dark-mode">

    <!-- Navbar -->
    <nav style="display:flex; justify-content:space-between; padding:20px; max-width:1200px; margin:0 auto; align-items:center;">
        <a href="/" style="color:var(--text); font-weight:800; font-size:24px; text-decoration:none; display:flex; align-items:center; gap:10px;">
            <div style="width:40px;height:40px;background:var(--accent);border-radius:50%;display:flex;align-items:center;justify-content:center;color:#000;font-size:18px;">CB</div>
            CodeByTushu
        </a>
        <div style="display:flex; gap:15px;">
            <a href="/blogs.php" class="btn btn-ghost" style="text-decoration:none;">All Blogs</a>
        </div>
    </nav>

    <header class="blog-header">
        <div class="blog-meta">
            <?php if (!empty($blog['category_name'])): ?>
                <span class="blog-category"><?= htmlspecialchars($blog['category_name']) ?></span>
            <?php endif; ?>
            <span><?= date('M j, Y', strtotime($blog['published_at'] ?? 'now')) ?></span>
            <span>•</span>
            <span><?= (int)($blog['read_time_mins'] ?? 3) ?> min read</span>
            <span>•</span>
            <span><?= number_format(($blog['view_count'] ?? 0) + 1) ?> views</span>
        </div>
        
        <h1 class="blog-title"><?= htmlspecialchars($blog['title']) ?></h1>
    </header>

    <?php 
        $hero_img = $blog['cover_image_path'] ?: $blog['thumbnail_path'];
        if (!empty($hero_img)): 
    ?>
        <img src="<?= htmlspecialchars($hero_img) ?>" alt="Cover Image" class="blog-hero-img">
    <?php endif; ?>

    <main class="blog-content" id="markdown-container">
        <!-- Rendered by JS -->
    </main>
    
    <!-- Hidden textarea to safely pass markdown to JS -->
    <textarea id="raw-markdown" style="display:none;"><?= htmlspecialchars($blog['content'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE) ?></textarea>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const raw = document.getElementById('raw-markdown').value;
            const container = document.getElementById('markdown-container');
            
            // Parse Markdown
            container.innerHTML = marked.parse(raw);
            
            // Apply Syntax Highlighting
            document.querySelectorAll('pre code').forEach((el) => {
                hljs.highlightElement(el);
            });
        });
    </script>

    <footer style="text-align:center; padding:40px; color:var(--text-dim); margin-top:60px; border-top:1px solid var(--border);">
        <p>&copy; <?= date('Y') ?> CodeByTushu. All rights reserved.</p>
    </footer>

</body>
</html>
