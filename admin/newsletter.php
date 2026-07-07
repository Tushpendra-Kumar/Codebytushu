<?php
/**
 * Admin Module: Newsletter Subscribers
 * View, search, and manage newsletter subscribers.
 */
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/includes/auth_check.php';

$pdo = db();

// Handle Delete Request
if (isset($_POST['delete_subscriber']) && isset($_POST['subscriber_id'])) {
    $id = (int)$_POST['subscriber_id'];
    try {
        $delStmt = $pdo->prepare("DELETE FROM newsletter_subscribers WHERE id = :id");
        $delStmt->execute(['id' => $id]);
        $_SESSION['success_msg'] = "Subscriber deleted successfully.";
        header("Location: newsletter.php");
        exit;
    } catch (PDOException $e) {
        $_SESSION['error_msg'] = "Failed to delete subscriber.";
    }
}

// Handle Search and Pagination
$search = $_GET['search'] ?? '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

$whereClause = "";
$params = [];
if (!empty($search)) {
    $whereClause = "WHERE email LIKE :search";
    $params['search'] = "%$search%";
}

// Get Total Count
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM newsletter_subscribers $whereClause");
$countStmt->execute($params);
$totalSubscribers = $countStmt->fetchColumn();
$totalPages = ceil($totalSubscribers / $limit);

// Get Subscribers
$query = "SELECT * FROM newsletter_subscribers $whereClause ORDER BY subscribed_at DESC LIMIT $limit OFFSET $offset";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$subscribers = $stmt->fetchAll();

// Page settings for template
$pageTitle = 'Newsletter Subscribers';
$pageBreadcrumb = [
    ['label' => 'Dashboard', 'url' => 'index.php'],
    ['label' => 'Newsletter', 'active' => true]
];
?>

<?php require_once __DIR__ . '/includes/head.php'; ?>
<body>
    <div class="cbt-admin-wrapper">
        <?php require_once __DIR__ . '/includes/sidebar.php'; ?>
        
        <div class="cbt-admin-main">
            <?php require_once __DIR__ . '/includes/header.php'; ?>
            <?php require_once __DIR__ . '/includes/breadcrumb.php'; ?>

            <div class="cbt-admin-content">
                
                <!-- Header & Actions -->
                <div class="cbt-module-header">
                    <div class="cbt-module-info">
                        <h1>Newsletter Subscribers</h1>
                        <p>Total Subscribers: <strong><?= number_format($totalSubscribers) ?></strong></p>
                    </div>
                    <div class="cbt-module-actions">
                        <form method="GET" action="newsletter.php" class="cbt-search-form">
                            <div class="cbt-search-group">
                                <i class="fa-solid fa-magnifying-glass"></i>
                                <input type="text" name="search" placeholder="Search email..." value="<?= htmlspecialchars($search) ?>">
                            </div>
                        </form>
                        <a href="#" class="cbt-btn cbt-btn-outline" onclick="alert('Export functionality will be enabled soon.');"><i class="fa-solid fa-file-csv"></i> Export CSV</a>
                    </div>
                </div>

                <!-- Messages -->
                <?php if(isset($_SESSION['success_msg'])): ?>
                    <div class="cbt-alert cbt-alert-success"><?= $_SESSION['success_msg']; unset($_SESSION['success_msg']); ?></div>
                <?php endif; ?>
                <?php if(isset($_SESSION['error_msg'])): ?>
                    <div class="cbt-alert cbt-alert-danger"><?= $_SESSION['error_msg']; unset($_SESSION['error_msg']); ?></div>
                <?php endif; ?>

                <!-- Data Table -->
                <div class="cbt-data-card">
                    <div class="cbt-table-responsive">
                        <table class="cbt-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Email</th>
                                    <th>Status</th>
                                    <th>Subscribed At</th>
                                    <th class="cbt-text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($subscribers)): ?>
                                    <tr>
                                        <td colspan="5" class="cbt-text-center cbt-empty-state">
                                            <i class="fa-regular fa-folder-open"></i>
                                            <p>No subscribers found.</p>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($subscribers as $sub): ?>
                                        <tr>
                                            <td>#<?= $sub['id'] ?></td>
                                            <td><strong><?= htmlspecialchars($sub['email']) ?></strong></td>
                                            <td>
                                                <?php if($sub['status'] === 'active'): ?>
                                                    <span class="cbt-badge cbt-badge-success">Active</span>
                                                <?php else: ?>
                                                    <span class="cbt-badge cbt-badge-warning">Unsubscribed</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= date('M d, Y h:i A', strtotime($sub['subscribed_at'])) ?></td>
                                            <td class="cbt-text-right">
                                                <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this subscriber?');">
                                                    <input type="hidden" name="subscriber_id" value="<?= $sub['id'] ?>">
                                                    <button type="submit" name="delete_subscriber" class="cbt-btn-icon cbt-btn-danger" title="Delete">
                                                        <i class="fa-regular fa-trash-can"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                        <div class="cbt-pagination">
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <a href="?page=<?= $i ?><?= !empty($search) ? '&search='.urlencode($search) : '' ?>" class="cbt-page-link <?= ($i === $page) ? 'active' : '' ?>">
                                    <?= $i ?>
                                </a>
                            <?php endfor; ?>
                        </div>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    </div>
    <?php require_once __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
