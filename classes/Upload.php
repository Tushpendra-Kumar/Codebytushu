<?php
/**
 * CodeByTushu — Upload Class
 * Validates, renames, and stores user-uploaded files securely.
 * Blocks PHP execution inside /uploads/ via .htaccess.
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

class Upload
{
    private string $context;
    private ?int   $uploadedBy;

    // Resolved after upload()
    public ?string $storedName = null;
    public ?string $filePath   = null;
    public ?string $fileUrl    = null;
    public ?string $error      = null;

    public function __construct(string $context = 'general', ?int $uploadedBy = null)
    {
        $this->context    = $context;
        $this->uploadedBy = $uploadedBy;
    }

    // ─────────────────────────────────────────────────────────────────────
    // UPLOAD SINGLE FILE
    // ─────────────────────────────────────────────────────────────────────

    /**
     * Process a single file from $_FILES[$fieldName].
     * Returns true on success, false on failure (check $this->error).
     */
    public function upload(string $fieldName, string $category = 'image'): bool
    {
        if (!isset($_FILES[$fieldName]) || $_FILES[$fieldName]['error'] === UPLOAD_ERR_NO_FILE) {
            $this->error = 'No file was uploaded.';
            return false;
        }

        $file = $_FILES[$fieldName];

        // PHP upload error check
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $this->error = $this->phpUploadError($file['error']);
            return false;
        }

        // Size validation
        $maxSize = match ($category) {
            'image' => UPLOAD_MAX_IMAGE,
            'pdf'   => UPLOAD_MAX_PDF,
            'video' => UPLOAD_MAX_VIDEO,
            'zip'   => UPLOAD_MAX_ZIP,
            default => UPLOAD_MAX_IMAGE,
        };

        if ($file['size'] > $maxSize) {
            $this->error = sprintf(
                'File too large. Maximum allowed: %s.',
                $this->formatBytes($maxSize)
            );
            return false;
        }

        // Extension validation
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed   = $this->allowedExtensions($category);
        if (!in_array($extension, $allowed, true)) {
            $this->error = 'Invalid file type. Allowed: ' . implode(', ', $allowed);
            return false;
        }

        // MIME type validation via finfo (not trusting client Content-Type)
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime  = $finfo->file($file['tmp_name']);
        if (!$mime || !in_array($mime, $this->allowedMimes($category), true)) {
            $this->error = 'File content does not match expected type. Upload rejected.';
            return false;
        }

        // Build destination path
        $subDir  = $this->resolveSubDir($category);
        $destDir = UPLOAD_DIR . '/' . $subDir;
        if (!is_dir($destDir) && !mkdir($destDir, 0755, true)) {
            $this->error = 'Upload directory could not be created.';
            return false;
        }

        // Generate a unique filename — never keep the original name
        $newName = $this->generateFilename($extension);
        $destPath = $destDir . '/' . $newName;

        if (!move_uploaded_file($file['tmp_name'], $destPath)) {
            $this->error = 'Failed to move uploaded file. Check server permissions.';
            return false;
        }

        // Set public properties
        $this->storedName = $newName;
        $this->filePath   = '/uploads/' . $subDir . '/' . $newName;
        $this->fileUrl    = SITE_URL . $this->filePath;

        // Log to DB
        $this->logUpload($file['name'], $newName, $this->filePath, $mime, $file['size'], $category);

        return true;
    }

    // ─────────────────────────────────────────────────────────────────────
    // VALIDATION ONLY (ARCH-01)
    // ─────────────────────────────────────────────────────────────────────

    /**
     * Validates an uploaded $_FILES entry without moving it.
     * Returns ['ok' => bool, 'error' => string|null, 'mime' => string|null, 'ext' => string|null]
     */
    public static function validateFile(array $file, string $category = 'image'): array
    {
        $u = new self();
        
        if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
            return ['ok' => false, 'error' => $u->phpUploadError($file['error'] ?? UPLOAD_ERR_NO_FILE)];
        }

        // Size validation
        $maxSize = match ($category) {
            'image' => UPLOAD_MAX_IMAGE,
            'pdf'   => UPLOAD_MAX_PDF,
            'video' => UPLOAD_MAX_VIDEO,
            'zip'   => UPLOAD_MAX_ZIP,
            default => UPLOAD_MAX_IMAGE,
        };

        if ($file['size'] > $maxSize) {
            return ['ok' => false, 'error' => sprintf('File too large. Maximum allowed: %s.', $u->formatBytes($maxSize))];
        }

        // Extension validation
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowedExts = $u->allowedExtensions($category);
        if (!in_array($extension, $allowedExts, true)) {
            return ['ok' => false, 'error' => 'Invalid file extension. Allowed: ' . implode(', ', $allowedExts)];
        }

        // MIME type validation via finfo
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime  = $finfo->file($file['tmp_name']);
        if (!$mime || !in_array($mime, $u->allowedMimes($category), true)) {
            return ['ok' => false, 'error' => 'File content does not match expected type. Upload rejected.'];
        }

        return ['ok' => true, 'error' => null, 'mime' => $mime, 'ext' => $extension];
    }

    // ─────────────────────────────────────────────────────────────────────
    // DELETE
    // ─────────────────────────────────────────────────────────────────────

    public static function delete(string $rootRelativePath): bool
    {
        $fullPath = ROOT_DIR . $rootRelativePath;
        if (file_exists($fullPath) && is_file($fullPath)) {
            $deleted = unlink($fullPath);
            if ($deleted) {
                db()->prepare('UPDATE file_uploads SET is_active = 0 WHERE file_path = ?')
                   ->execute([$rootRelativePath]);
            }
            return $deleted;
        }
        return false;
    }

    // ─────────────────────────────────────────────────────────────────────
    // PRIVATE HELPERS
    // ─────────────────────────────────────────────────────────────────────

    private function generateFilename(string $extension): string
    {
        return date('YmdHis') . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
    }

    private function resolveSubDir(string $category): string
    {
        return match ($category) {
            'image' => 'images/' . $this->context,
            'video' => 'videos/' . $this->context,
            'pdf'   => 'pdfs/'   . $this->context,
            'zip'   => 'zips/'   . $this->context,
            default => 'misc/'   . $this->context,
        };
    }

    private function allowedExtensions(string $category): array
    {
        return match ($category) {
            'image' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
            'pdf'   => ['pdf'],
            'video' => ['mp4', 'webm', 'ogg'],
            'zip'   => ['zip'],
            default => ['jpg', 'jpeg', 'png', 'pdf'],
        };
    }

    private function allowedMimes(string $category): array
    {
        return match ($category) {
            'image' => ALLOWED_IMAGE_MIME,
            'pdf'   => ALLOWED_PDF_MIME,
            'video' => ALLOWED_VIDEO_MIME,
            'zip'   => ALLOWED_ZIP_MIME,
            default => [...ALLOWED_IMAGE_MIME, ...ALLOWED_PDF_MIME],
        };
    }

    private function formatBytes(int $bytes): string
    {
        // Delegate to global helper (includes/functions.php) — avoids duplication
        return formatFileSize($bytes);
    }

    private function phpUploadError(int $code): string
    {
        return match ($code) {
            UPLOAD_ERR_INI_SIZE   => 'File exceeds server upload limit.',
            UPLOAD_ERR_FORM_SIZE  => 'File exceeds form upload limit.',
            UPLOAD_ERR_PARTIAL    => 'File was only partially uploaded.',
            UPLOAD_ERR_NO_TMP_DIR => 'Server has no temp directory.',
            UPLOAD_ERR_CANT_WRITE => 'Server cannot write file to disk.',
            UPLOAD_ERR_EXTENSION  => 'Upload blocked by server extension.',
            default               => 'Unknown upload error.',
        };
    }

    private function logUpload(
        string $original, string $stored, string $path,
        string $mime, int $size, string $category
    ): void {
        try {
            db()->prepare(
                'INSERT INTO file_uploads
                   (uploaded_by, original_name, stored_name, file_path, file_type, file_size, file_category, context)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
            )->execute([
                $this->uploadedBy, $original, $stored, $path,
                $mime, $size, $category, $this->context,
            ]);
        } catch (\Throwable) {
            // Non-fatal — logging failure should not block the upload
        }
    }
}
