<?php
/**
 * CodeByTushu — Blogs Listing Page
 * Displays a list of all published blogs in a responsive grid.
 */
declare(strict_types=1);

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$pdo = db();

// Fetch all published blogs
$stmt = $pdo->prepare("
    SELECT b.id, b.title, b.slug, b.card_title, b.icon_name, b.excerpt, b.thumbnail_path, 
           b.published_at, b.read_time_mins, c.name as category_name
    FROM blog_articles b
    LEFT JOIN categories c ON b.category_id = c.id
    WHERE b.is_published = 1
    ORDER BY b.published_at DESC
");
$stmt->execute();
$blogs = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blogs | CodeByTushu</title>
    <meta name="description" content="Read the latest articles, tutorials, and tech updates from CodeByTushu.">
    
    <link rel="icon" href="/favicon.ico?v=6" sizes="any">
    <script src="/theme.js"></script>
    <link rel="stylesheet" href="/styles.css">
    
    <style>
        .blog-header {
            text-align: center;
            padding: 80px 20px 40px;
        }
        .blog-header h1 {
            font-size: 42px;
            margin-bottom: 15px;
            color: var(--text);
            font-weight: 800;
        }
        .blog-header p {
            color: var(--text-dim);
            font-size: 18px;
            max-width: 600px;
            margin: 0 auto;
        }
        
        .blog-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 30px;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .blog-card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 16px;
            overflow: hidden;
            transition: transform 0.3s, border-color 0.3s, box-shadow 0.3s;
            display: flex;
            flex-direction: column;
            text-decoration: none;
        }
        
        .blog-card:hover {
            transform: translateY(-5px);
            border-color: var(--border-hover);
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
        }
        
        .blog-thumb {
            width: 100%;
            height: 200px;
            object-fit: cover;
            background: var(--bg-hover);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
        }
        
        .blog-content {
            padding: 24px;
            display: flex;
            flex-direction: column;
            flex-grow: 1;
        }
        
        .blog-meta {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 12px;
            color: var(--text-dim);
            margin-bottom: 12px;
        }
        
        .blog-category {
            background: rgba(255,196,0,0.1);
            color: var(--accent);
            padding: 4px 10px;
            border-radius: 99px;
            font-weight: 600;
        }
        
        .blog-title {
            color: var(--text);
            font-size: 20px;
            font-weight: 700;
            margin: 0 0 12px 0;
            line-height: 1.4;
        }
        
        .blog-excerpt {
            color: var(--text-muted);
            font-size: 14px;
            line-height: 1.6;
            margin: 0 0 20px 0;
            flex-grow: 1;
        }
        
        .blog-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: auto;
            border-top: 1px solid var(--border);
            padding-top: 16px;
        }
        
        .read-more {
            color: var(--text);
            font-weight: 600;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: color 0.2s;
        }
        .blog-card:hover .read-more {
            color: var(--accent);
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            grid-column: 1 / -1;
            color: var(--text-muted);
        }
        .empty-state span {
            font-size: 48px;
            display: block;
            margin-bottom: 20px;
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
            <a href="/Leetcode/problems.php" class="btn btn-ghost" style="text-decoration:none;">LeetCode</a>
            <a href="/admin/" class="btn" style="text-decoration:none;">Admin</a>
        </div>
    </nav>

    <header class="blog-header">
        <h1>Blog</h1>
        <p>Insights, tutorials, and behind-the-scenes building CodeByTushu.</p>
    </header>

    <main class="blog-grid">
        <?php if (empty($blogs)): ?>
            <div class="empty-state">
                <span>📝</span>
                <h2>No posts yet</h2>
                <p>Check back later for new articles and tutorials!</p>
            </div>
        <?php else: ?>
            <?php foreach ($blogs as $post): ?>
                <a href="/blog/<?= htmlspecialchars($post['slug'] ?? '') ?>" class="blog-card">
                    <?php if (!empty($post['thumbnail_path'])): ?>
                        <img src="<?= htmlspecialchars($post['thumbnail_path']) ?>" alt="<?= htmlspecialchars($post['title']) ?>" class="blog-thumb">
                    <?php else: ?>
                        <div class="blog-thumb"><?= htmlspecialchars($post['icon_name'] ?? '📝') ?></div>
                    <?php endif; ?>
                    
                    <div class="blog-content">
                        <div class="blog-meta">
                            <?php if (!empty($post['category_name'])): ?>
                                <span class="blog-category"><?= htmlspecialchars($post['category_name']) ?></span>
                            <?php endif; ?>
                            <span><?= date('M j, Y', strtotime($post['published_at'] ?? 'now')) ?></span>
                            <span>•</span>
                            <span><?= (int)($post['read_time_mins'] ?? 3) ?> min read</span>
                        </div>
                        
                        <h2 class="blog-title"><?= htmlspecialchars($post['card_title'] ?: $post['title']) ?></h2>
                        <p class="blog-excerpt"><?= htmlspecialchars(mb_strimwidth($post['excerpt'] ?? '', 0, 120, '...')) ?></p>
                        
                        <div class="blog-footer">
                            <span class="read-more">Read Article <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg></span>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        <?php endif; ?>
    </main>

    <footer style="text-align:center; padding:40px; color:var(--text-dim); margin-top:60px; border-top:1px solid var(--border);">
        <p>&copy; <?= date('Y') ?> CodeByTushu. All rights reserved.</p>
    </footer>

</body>
</html>
