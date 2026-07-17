<?php
/**
 * CodeByTushu — User Dashboard (Profile)
 */
declare(strict_types=1);
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../classes/Auth.php';

Auth::boot();
Auth::requireLogin();

$pdo  = db();
$user = Auth::user(true);

$error   = '';
$success = '';
$activeTab = 'profile';

if (isPost()) {
    requireCsrf();
    $sub = post('_sub');

    if ($sub === 'profile') {
        $fullName = sanitize(post('full_name'));
        
        // Handle profile image upload
        $profileImage = $user['profile_image'];
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['profile_image'];
            $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
            
            if (in_array($file['type'], $allowedTypes)) {
                $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                if (empty($ext)) {
                    $ext = ($file['type'] === 'image/png') ? 'png' : (($file['type'] === 'image/webp') ? 'webp' : 'jpg');
                }
                
                $uploadDir = __DIR__ . '/../uploads/images/avatars/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                $filename = 'avatar_' . $user['id'] . '_' . time() . '.' . $ext;
                $targetFile = $uploadDir . $filename;
                
                if (move_uploaded_file($file['tmp_name'], $targetFile)) {
                    // Delete old local avatar if exists
                    if (!empty($user['profile_image']) && strpos($user['profile_image'], '/uploads/images/avatars/') === 0) {
                        $oldPath = __DIR__ . '/..' . $user['profile_image'];
                        if (file_exists($oldPath)) {
                            unlink($oldPath);
                        }
                    }
                    $profileImage = '/uploads/images/avatars/' . $filename;
                } else {
                    $error = 'Failed to save uploaded image.';
                }
            } else {
                $error = 'Invalid file type. Only JPG, PNG, and WebP are allowed.';
            }
        }
        
        if (!$error) {
            $pdo->prepare('UPDATE users SET full_name=?, profile_image=?, updated_at=NOW() WHERE id=?')
                ->execute([$fullName, $profileImage, $user['id']]);
            $success = 'Profile updated successfully.';
            $user = Auth::user(true); // refresh user data
        }
    }
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>
    <!-- Main Content -->
    <main>
      <div class="card">
        <div class="card-header"><h2 class="card-title">Google Account</h2></div>
        <div class="card-body">
          <div class="avatar-section">
            <?php if (!empty($user['profile_image'])): ?>
              <img src="<?= e($user['profile_image']) ?>" class="avatar-img" alt="Profile">
            <?php else: ?>
              <div class="avatar-img"><?= strtoupper(substr($user['full_name'] ?? 'U',0,1)) ?></div>
            <?php endif; ?>
            <div>
              <h3 style="font-size:18px;font-weight:600;"><?= e($user['full_name']) ?></h3>
              <p style="color:var(--muted);font-size:14px;"><?= e($user['email']) ?></p>
            </div>
          </div>

          <form method="POST" enctype="multipart/form-data">
            <?= csrfField() ?>
            <input type="hidden" name="_sub" value="profile">
            
            <div class="form-group">
              <label class="form-label">Profile Photo</label>
              <div style="display: flex; gap: 15px; align-items: center;">
                <?php if (!empty($user['profile_image'])): ?>
                  <img id="avatarPreview" src="<?= e($user['profile_image']) ?>" style="width:60px; height:60px; border-radius:50%; object-fit:cover; border:2px solid var(--border);" alt="Profile">
                <?php else: ?>
                  <div id="avatarPreview" style="width:60px; height:60px; border-radius:50%; background:var(--accent); color:#fff; display:flex; align-items:center; justify-content:center; font-size:24px; font-weight:bold;">
                    <?= strtoupper(substr($user['full_name'] ?? 'U',0,1)) ?>
                  </div>
                <?php endif; ?>
                
                <input type="file" id="profileImageInput" name="profile_image" accept="image/jpeg, image/png, image/webp" style="display:none;" onchange="previewAvatar(this)">
                <button type="button" class="btn-save" style="background:#222; color:#fff; border:1px solid #444;" onclick="document.getElementById('profileImageInput').click();">
                  Change Photo
                </button>
              </div>
            </div>

            <div class="form-group">
              <label class="form-label">Email (Managed by Google)</label>
              <input type="email" class="form-control" value="<?= e($user['email']) ?>" readonly>
            </div>
            <div class="form-group">
              <label class="form-label">Full Name</label>
              <input type="text" name="full_name" class="form-control" value="<?= e($user['full_name']) ?>">
            </div>

            <button type="submit" class="btn-save">Save Profile</button>
          </form>
        </div>
      </div>
    </main>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
