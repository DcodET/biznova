<?php
/**
 * BIZNOVA Platform - Recommended wp-config.php Security Hardening Constants
 * Add these lines directly into your root wp-config.php file before:
 * "That's all, stop editing! Happy publishing."
 */

// 1. Disable Theme & Plugin File Editor in WordPress Admin (Prevents Admin Backdoors)
define('DISALLOW_FILE_EDIT', true);

// 2. Enforce SSL & HTTPS for Admin Panel & Login Sessions
define('FORCE_SSL_ADMIN', true);

// 3. Prevent Unfiltered HTML for Non-Admins
define('DISALLOW_UNFILTERED_HTML', true);

// 4. Limit Post Revisions to Prevent Database Bloat
define('WP_POST_REVISIONS', 5);

// 5. Secure Session Garbage Collection (14 Days)
define('EMPTY_TRASH_DAYS', 14);

// 6. Disable Debug Output in Production
define('WP_DEBUG', false);
define('WP_DEBUG_LOG', false);
define('WP_DEBUG_DISPLAY', false);
@ini_set('display_errors', 0);
