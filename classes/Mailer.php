<?php
/**
 * CodeByTushu — Mailer Class
 * PHPMailer wrapper for all transactional email.
 * Require Composer: phpmailer/phpmailer
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';
// If Composer is installed:
// require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

class Mailer
{
    private PHPMailer $mail;

    public function __construct()
    {
        if (!class_exists(PHPMailer::class)) {
            throw new \RuntimeException(
                'PHPMailer not found. Run: composer require phpmailer/phpmailer'
            );
        }

        $this->mail = new PHPMailer(true);
        $this->configure();
    }

    private function configure(): void
    {
        $m = $this->mail;
        $m->isSMTP();
        $m->Host       = SMTP_HOST;
        $m->Port       = SMTP_PORT;
        $m->SMTPAuth   = true;
        $m->Username   = SMTP_USER;
        $m->Password   = SMTP_PASS;
        $m->SMTPSecure = (SMTP_PORT === 465) ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
        $m->SMTPDebug  = APP_DEBUG ? SMTP::DEBUG_SERVER : SMTP::DEBUG_OFF;
        $m->CharSet    = 'UTF-8';
        $m->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
    }

    // ─────────────────────────────────────────────────────────────────────
    // PUBLIC API
    // ─────────────────────────────────────────────────────────────────────

    public function send(
        string $toEmail,
        string $toName,
        string $subject,
        string $htmlBody,
        string $plainBody = ''
    ): bool {
        try {
            $m = $this->mail;
            $m->clearAddresses();
            $m->clearAttachments();

            $m->addAddress($toEmail, $toName);
            $m->Subject  = $subject;
            $m->isHTML(true);
            $m->Body     = $htmlBody;
            $m->AltBody  = $plainBody ?: strip_tags($htmlBody);
            return $m->send();
        } catch (PHPMailerException $e) {
            if (APP_DEBUG) error_log('Mailer error: ' . $e->getMessage());
            return false;
        }
    }

    // ─────────────────────────────────────────────────────────────────────
    // PRE-BUILT EMAIL TEMPLATES
    // ─────────────────────────────────────────────────────────────────────

    /** Send password reset email. */
    public function sendPasswordReset(string $email, string $name, string $rawToken): bool
    {
        $link    = SITE_URL . '/auth/reset-password.php?token=' . urlencode($rawToken);
        $subject = 'Reset Your Password — CodeByTushu';
        $html    = $this->layout(
            $subject,
            <<<HTML
            <h2 style="color:#ffc400;">Reset Your Password</h2>
            <p>Hi {$name},</p>
            <p>We received a request to reset your password. Click the button below to choose a new one.</p>
            <p style="text-align:center;margin:30px 0;">
              <a href="{$link}"
                 style="background:#ffc400;color:#000;padding:14px 32px;border-radius:8px;
                        text-decoration:none;font-weight:700;display:inline-block;">
                Reset Password
              </a>
            </p>
            <p style="color:#888;font-size:13px;">
              This link expires in <strong>1 hour</strong>.
              If you didn't request this, you can safely ignore this email.
            </p>
            <p style="color:#888;font-size:12px;">Or copy this URL:<br>{$link}</p>
            HTML
        );
        return $this->send($email, $name, $subject, $html);
    }

    /** Send email verification email. */
    public function sendEmailVerification(string $email, string $name, string $rawToken): bool
    {
        $link    = SITE_URL . '/auth/verify-email.php?token=' . urlencode($rawToken);
        $subject = 'Verify Your Email — CodeByTushu';
        $html    = $this->layout(
            $subject,
            <<<HTML
            <h2 style="color:#ffc400;">Verify Your Email</h2>
            <p>Hi {$name},</p>
            <p>Thanks for signing up! Please verify your email address to unlock all features.</p>
            <p style="text-align:center;margin:30px 0;">
              <a href="{$link}"
                 style="background:#ffc400;color:#000;padding:14px 32px;border-radius:8px;
                        text-decoration:none;font-weight:700;display:inline-block;">
                Verify Email
              </a>
            </p>
            <p style="color:#888;font-size:13px;">Link expires in <strong>24 hours</strong>.</p>
            HTML
        );
        return $this->send($email, $name, $subject, $html);
    }

    /** Notify admin of a new contact form message. */
    public function sendContactNotification(array $message): bool
    {
        $subject = 'New Contact Message — ' . e($message['name']);
        $html    = $this->layout(
            $subject,
            <<<HTML
            <h2 style="color:#ffc400;">New Contact Message</h2>
            <table cellpadding="8" style="width:100%;border-collapse:collapse;">
              <tr><td style="color:#888;">From</td><td>{$message['name']} &lt;{$message['email']}&gt;</td></tr>
              <tr><td style="color:#888;">Source</td><td>{$message['source_page']}</td></tr>
              <tr><td style="color:#888;">Subject</td><td>{$message['subject']}</td></tr>
            </table>
            <hr style="border:none;border-top:1px solid #222;margin:20px 0;">
            <p style="white-space:pre-wrap;">{$message['message']}</p>
            <p style="text-align:center;margin:20px 0;">
              <a href="{SITE_URL}/admin/messages.php"
                 style="background:#ffc400;color:#000;padding:12px 24px;border-radius:8px;
                        text-decoration:none;font-weight:700;display:inline-block;">
                View in Admin Panel
              </a>
            </p>
            HTML
        );
        return $this->send(SMTP_TO_EMAIL, APP_NAME, $subject, $html);
    }

    /** Welcome email for new registrations. */
    public function sendWelcome(string $email, string $name): bool
    {
        $subject = 'Welcome to CodeByTushu!';
        $html    = $this->layout(
            $subject,
            <<<HTML
            <h2 style="color:#ffc400;">Welcome, {$name}! 🎉</h2>
            <p>Your account has been created successfully.</p>
            <p>You now have access to:</p>
            <ul style="color:#ccc;line-height:1.8;">
              <li>📚 Daily LeetCode Solutions</li>
              <li>🎬 Video Editing Tutorials</li>
              <li>💻 Web Development Projects</li>
            </ul>
            <p style="text-align:center;margin:30px 0;">
              <a href="{SITE_URL}/Leetcode/"
                 style="background:#ffc400;color:#000;padding:14px 32px;border-radius:8px;
                        text-decoration:none;font-weight:700;display:inline-block;">
                Explore LeetCode Solutions
              </a>
            </p>
            HTML
        );
        return $this->send($email, $name, $subject, $html);
    }

    // ─────────────────────────────────────────────────────────────────────
    // EMAIL LAYOUT TEMPLATE
    // ─────────────────────────────────────────────────────────────────────

    private function layout(string $subject, string $body): string
    {
        $siteName = APP_NAME;
        $siteUrl  = SITE_URL;
        return <<<HTML
        <!DOCTYPE html>
        <html>
        <head>
          <meta charset="UTF-8">
          <title>{$subject}</title>
        </head>
        <body style="margin:0;padding:0;background:#0a0a0c;font-family:'Segoe UI',Arial,sans-serif;color:#f0f0f0;">
          <table width="100%" cellpadding="0" cellspacing="0">
            <tr>
              <td align="center" style="padding:40px 20px;">
                <table width="600" cellpadding="0" cellspacing="0"
                       style="background:#111118;border-radius:16px;
                              border:1px solid rgba(255,196,0,0.15);overflow:hidden;">
                  <!-- Header -->
                  <tr>
                    <td style="background:#ffc400;padding:24px;text-align:center;">
                      <h1 style="margin:0;color:#000;font-size:22px;font-weight:800;">
                        CODE<span style="font-weight:400;">BY</span>TUSHU
                      </h1>
                    </td>
                  </tr>
                  <!-- Body -->
                  <tr>
                    <td style="padding:40px 32px;font-size:15px;line-height:1.7;color:#e0e0e0;">
                      {$body}
                    </td>
                  </tr>
                  <!-- Footer -->
                  <tr>
                    <td style="padding:20px 32px;border-top:1px solid #1a1a24;
                               text-align:center;color:#666;font-size:12px;">
                      © 2026 {$siteName} &nbsp;|&nbsp;
                      <a href="{$siteUrl}" style="color:#ffc400;text-decoration:none;">
                        {$siteUrl}
                      </a>
                    </td>
                  </tr>
                </table>
              </td>
            </tr>
          </table>
        </body>
        </html>
        HTML;
    }
}
