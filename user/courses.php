<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../config/database.php';

Auth::boot();
Auth::requireLogin();

$pdo = db();
$user = Auth::user();
if (!$user) {
    header('Location: /auth/login.php');
    exit;
}
$user_id = $user['id'];

// Fetch purchased courses with payment status
$stmt = $pdo->prepare("
    SELECT c.*, o.payment_status as status, o.created_at as order_date
    FROM order_items oi
    JOIN orders o ON oi.order_id = o.id
    JOIN courses c ON oi.course_id = c.id
    WHERE o.user_id = ?
    ORDER BY o.created_at DESC
");
$stmt->execute([$user_id]);
$courses = $stmt->fetchAll();

// Optional: also fetch legacy course_enrollments just in case
$legacyStmt = $pdo->prepare("
    SELECT c.*, 'verified' as status, e.enrolled_at as order_date
    FROM course_enrollments e
    JOIN courses c ON e.course_id = c.id
    WHERE e.user_id = ? AND (e.payment_status = 'paid' OR e.payment_status = 'free')
");
$legacyStmt->execute([$user_id]);
$legacyCourses = $legacyStmt->fetchAll();

// Merge and remove duplicates (prefer orders table data)
$allCourses = [];
$seen = [];
foreach (array_merge($courses, $legacyCourses) as $c) {
    if (!isset($seen[$c['id']])) {
        $seen[$c['id']] = true;
        $allCourses[] = $c;
    }
}
$courses = $allCourses;

$activeTab = 'courses';
$error = '';
$success = '';

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>
<style>
    .courses-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 20px; }
    .course-card { background: var(--input); border-radius: 12px; padding: 20px; border: 1px solid var(--border); display: flex; flex-direction: column; }
    .course-thumb { width: 100%; height: 160px; object-fit: cover; border-radius: 8px; margin-bottom: 15px; }
    .course-title { color: var(--text); font-size: 1.1rem; font-weight: 600; margin-bottom: 15px; line-height: 1.4; flex-grow: 1; }
    .btn-download { background: var(--accent); color: #000; padding: 10px 15px; border-radius: 8px; text-decoration: none; display: block; text-align: center; font-weight: bold; transition: 0.3s; margin-top: auto; }
    .btn-download:hover { opacity: 0.9; }
    
    /* Premium Popup Styles */
    .popup-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 1000; justify-content: center; align-items: center; backdrop-filter: blur(5px); }
    .popup-content { background: var(--card); padding: 40px; border-radius: 12px; border: 1px solid var(--accent); max-width: 500px; text-align: center; position: relative; box-shadow: 0 10px 30px rgba(255, 196, 0, 0.2); }
    .popup-content h2 { color: var(--accent); font-size: 2rem; margin-top: 0; }
    .popup-content p { color: var(--muted); line-height: 1.6; font-size: 1.1rem; }
    .popup-icon { font-size: 4rem; color: var(--accent); margin-bottom: 20px; }
    .btn-close { position: absolute; top: 15px; right: 15px; background: none; border: none; color: var(--muted); font-size: 1.5rem; cursor: pointer; }
    .btn-close:hover { color: var(--text); }
    
    /* Status Badges */
    .status-badge { display: inline-block; padding: 5px 10px; border-radius: 20px; font-size: 0.85rem; font-weight: bold; margin-bottom: 15px; align-self: flex-start; }
    .status-pending { background: rgba(255, 193, 7, 0.15); color: #ffc107; border: 1px solid rgba(255,193,7,0.3); }
    .status-active { background: rgba(40, 167, 69, 0.15); color: #28a745; border: 1px solid rgba(40,167,69,0.3); }
    .status-rejected { background: rgba(220, 53, 69, 0.15); color: #dc3545; border: 1px solid rgba(220,53,69,0.3); }
    
    .pending-msg { color: var(--muted); font-size: 0.9rem; margin-top: auto; text-align: center; padding: 10px; border: 1px dashed var(--border); border-radius: 6px; }
</style>

<!-- Main Content -->
<main>
    <div class="card">
        <div class="card-header"><h2 class="card-title">My Courses</h2></div>
        <div class="card-body">
            <?php if (empty($courses)): ?>
                <div class="empty-state">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 48px; height: 48px; color: var(--border); margin-bottom: 16px;"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
                    <h3>No courses found</h3>
                    <p style="margin-top:8px;">You haven't purchased any courses yet.</p>
                    <a href="/courses/" style="color:var(--accent); text-decoration:none; display:inline-block; margin-top:15px; padding:8px 16px; border:1px solid var(--accent); border-radius:8px; font-weight:500;">Browse Courses</a>
                </div>
            <?php else: ?>
                <div class="courses-grid">
                    <?php foreach ($courses as $course): 
                        $status = $course['status'] ?? 'pending';
                        if ($status === 'verified') {
                            $badgeClass = 'status-active';
                            $badgeText = 'Active';
                        } elseif ($status === 'rejected') {
                            $badgeClass = 'status-rejected';
                            $badgeText = 'Rejected';
                        } else {
                            $badgeClass = 'status-pending';
                            $badgeText = 'Pending Approval';
                        }
                    ?>
                        <div class="course-card">
                            <img src="<?= htmlspecialchars($course['thumbnail_path'] ?: '/assets/images/default-course.jpg') ?>" class="course-thumb" alt="Thumb">
                            <span class="status-badge <?= $badgeClass ?>"><i class="fas fa-circle" style="font-size:0.6rem; margin-right:5px; vertical-align:middle;"></i><?= $badgeText ?></span>
                            <h2 class="course-title"><?= htmlspecialchars($course['title']) ?></h2>
                            
                            <?php if ($status === 'verified'): ?>
                                <a href="/api/courses/download.php?course_id=<?= $course['id'] ?>" class="btn-download" onclick="showPremiumPopup(event, this.href)">
                                    <i class="fas fa-download"></i> Access & Download
                                </a>
                            <?php elseif ($status === 'rejected'): ?>
                                <div class="pending-msg" style="color:#ff6b6b; border-color:rgba(255,107,107,0.3);">
                                    <i class="fas fa-times-circle"></i> Payment Rejected. Please contact support.
                                </div>
                            <?php else: ?>
                                <div class="pending-msg">
                                    <i class="fas fa-hourglass-half"></i> Payment is processing. You will get access shortly.
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<!-- Premium Popup -->
<div class="popup-overlay" id="premiumPopup">
    <div class="popup-content">
        <button class="btn-close" onclick="closePopup()"><i class="fas fa-times"></i></button>
        <i class="fas fa-award popup-icon"></i>
        <h2>Thank You!</h2>
        <p>Thank you so much for purchasing this course.</p>
        <p>Your support helps CodeByTushu continue creating high-quality educational content.</p>
        <p style="font-weight: bold; color: var(--accent); font-size: 1.2rem; margin-top: 20px;">Happy Learning! <i class="fas fa-rocket"></i></p>
    </div>
</div>

<script>
function showPremiumPopup(e, href) {
    setTimeout(() => {
        document.getElementById('premiumPopup').style.display = 'flex';
    }, 500);
}

function closePopup() {
    document.getElementById('premiumPopup').style.display = 'none';
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>