-- ============================================================
-- Migration 007: Website Settings v2
-- Adds new site_config keys for branding, footer, SEO, email,
-- maintenance, and social contact toggle.
-- ============================================================

-- 1. Add show_in_contact to social_links if missing
ALTER TABLE `social_links`
  ADD COLUMN IF NOT EXISTS `show_in_contact` TINYINT(1) NOT NULL DEFAULT 1
    COMMENT 'Show on contact page' AFTER `show_in_footer`;

-- 2. Seed new site_config keys (uses INSERT IGNORE so existing data is preserved)
INSERT IGNORE INTO `site_config` (`config_key`, `config_value`, `value_type`, `label`, `config_group`) VALUES

  -- Branding
  ('logo_path',                 NULL,         'url',    'Logo Path',                  'branding'),
  ('favicon_path',              NULL,         'url',    'Favicon Path',               'branding'),
  ('color_primary',             '#ffc400',    'text',   'Primary Color',              'branding'),
  ('color_secondary',           '#09090e',    'text',   'Secondary Color',            'branding'),
  ('color_bg',                  '#09090e',    'text',   'Background Color',           'branding'),

  -- Footer
  ('footer_tagline',            'Made with ❤️ for developers', 'text', 'Footer Tagline', 'footer'),
  ('footer_about',              'Daily LeetCode solutions, web dev tutorials, and projects.', 'text', 'Footer About', 'footer'),
  ('privacy_policy_url',        '/privacy',   'url',    'Privacy Policy URL',         'footer'),
  ('terms_url',                 '/terms',     'url',    'Terms of Service URL',       'footer'),

  -- SEO extensions
  ('seo_og_image',              NULL,         'url',    'Default OG Image',           'seo'),
  ('seo_keywords',              'leetcode, dsa, web development, coding tutorials', 'text', 'Default Keywords', 'seo'),

  -- Email
  ('admin_notification_email',  'codebytushu@gmail.com', 'text', 'Admin Notification Email', 'email'),
  ('email_from_name',           'CodeByTushu',           'text', 'Email From Name',           'email'),
  ('email_autoreply_text',      'Thank you for reaching out! I will get back to you within 24 hours.', 'text', 'Auto-Reply Text', 'email'),

  -- Maintenance
  ('maintenance_title',         'We''ll be back soon!',  'text', 'Maintenance Headline', 'maintenance'),
  ('maintenance_message',       'We are currently performing scheduled maintenance. Thank you for your patience.', 'text', 'Maintenance Message', 'maintenance'),
  ('maintenance_eta',           NULL,          'text',   'Maintenance ETA',            'maintenance');

-- 3. Add index on config_group if not present (already exists in base schema, but safe)
ALTER TABLE `site_config`
  ADD INDEX IF NOT EXISTS `idx_group_public` (`config_group`, `is_public`);

-- 4. Verify
SELECT config_key, config_group, value_type
FROM site_config
ORDER BY config_group, config_key;
