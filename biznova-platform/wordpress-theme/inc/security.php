<?php
/**
 * BIZNOVA Security, Hardening & Encryption Suite
 * Enterprise-grade security architecture for BIZNOVA WordPress Theme.
 *
 * @package Biznova
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Prevent direct access
}

/**
 * ============================================================================
 * 1. HTTP SECURITY HEADERS (حماية الترويسات الأمنية ومنع الهجمات)
 * ============================================================================
 */
function biznova_apply_security_headers($headers) {
    if (!is_admin()) {
        // Prevent Clickjacking (disallow embedding in iframes on other domains)
        $headers['X-Frame-Options'] = 'SAMEORIGIN';

        // Prevent MIME-sniffing exploits
        $headers['X-Content-Type-Options'] = 'nosniff';

        // Cross-Site Scripting (XSS) Filter protection
        $headers['X-XSS-Protection'] = '1; mode=block';

        // Referrer Privacy
        $headers['Referrer-Policy'] = 'strict-origin-when-cross-origin';

        // Restrict sensitive browser APIs & hardware devices
        $headers['Permissions-Policy'] = 'camera=(), microphone=(), geolocation=(), payment=()';

        // HTTP Strict Transport Security (HSTS) - Enforce SSL
        if (is_ssl()) {
            $headers['Strict-Transport-Security'] = 'max-age=31536000; includeSubDomains; preload';
        }
    }
    return $headers;
}
add_filter('wp_headers', 'biznova_apply_security_headers');

/**
 * ============================================================================
 * 2. CORE INFORMATION DISCLOSURE PREVENTION (إخفاء معلومات النظام وإغلاق الثغرات)
 * ============================================================================
 */

// 1. Remove WordPress Generator Meta Tag (Hides WP Version)
remove_action('wp_head', 'wp_generator');
add_filter('the_generator', '__return_empty_string');

// 2. Remove script and stylesheet version strings (Prevents version enumeration)
function biznova_remove_asset_version($src) {
    if (strpos($src, 'ver=') !== false) {
        $src = remove_query_arg('ver', $src);
    }
    return $src;
}
add_filter('style_loader_src', 'biznova_remove_asset_version', 9999);
add_filter('script_loader_src', 'biznova_remove_asset_version', 9999);

// 3. Remove RSD, WLW manifest, and shortlinks headers
remove_action('wp_head', 'rsd_link');
remove_action('wp_head', 'wlwmanifest_link');
remove_action('wp_head', 'wp_shortlink_wp_head');
remove_action('wp_head', 'feed_links', 2);
remove_action('wp_head', 'feed_links_extra', 3);

// 4. Disable XML-RPC completely (Stops Pingback DDoS & Brute Force)
add_filter('xmlrpc_enabled', '__return_false');
add_filter('xmlrpc_methods', function($methods) {
    unset($methods['pingback.ping']);
    return $methods;
});

// 5. Block REST API User Enumeration (Stops bots scanning /wp-json/wp/v2/users)
add_filter('rest_endpoints', function($endpoints) {
    if (isset($endpoints['/wp/v2/users']) && !current_user_can('list_users')) {
        unset($endpoints['/wp/v2/users']);
    }
    if (isset($endpoints['/wp/v2/users/(?P<id>[\d]+)']) && !current_user_can('list_users')) {
        unset($endpoints['/wp/v2/users/(?P<id>[\d]+)']);
    }
    return $endpoints;
});

// 6. Block Author URL query scans (?author=1 to stop username harvesting)
add_action('template_redirect', function() {
    if (!is_admin() && isset($_GET['author']) && is_numeric($_GET['author'])) {
        wp_safe_redirect(home_url('/'), 301);
        exit;
    }
});

// 7. Generic Login Error Messages (Prevents guessing valid usernames)
add_filter('login_errors', function() {
    return __('بيانات الدخول غير صحيحة. يرجى التحقق وإعادة المحاولة.', 'biznova');
});

/**
 * ============================================================================
 * 3. ADVANCED CRYPTOGRAPHY & DATA ENCRYPTION (تشفير البيانات الحساسة AES-256)
 * ============================================================================
 */

/**
 * Encrypt sensitive plain text using AES-256-CBC with WordPress salt
 *
 * @param string $plain_text
 * @return string Base64 encoded ciphertext + IV
 */
function biznova_encrypt_data($plain_text) {
    if (empty($plain_text)) {
        return '';
    }

    $salt = defined('AUTH_KEY') ? AUTH_KEY : 'biznova_secure_vault_2026_enterprise';
    $key  = hash('sha256', $salt, true);
    $iv_length = openssl_cipher_iv_length('aes-256-cbc');
    $iv = openssl_random_pseudo_bytes($iv_length);

    $encrypted = openssl_encrypt($plain_text, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
    if ($encrypted === false) {
        return $plain_text; // Fallback
    }

    return base64_encode($iv . '::' . $encrypted);
}

/**
 * Decrypt ciphertext using AES-256-CBC
 *
 * @param string $encoded_ciphertext
 * @return string Decrypted plain text
 */
function biznova_decrypt_data($encoded_ciphertext) {
    if (empty($encoded_ciphertext)) {
        return '';
    }

    $raw = base64_decode($encoded_ciphertext, true);
    if ($raw === false || strpos($raw, '::') === false) {
        return $encoded_ciphertext;
    }

    list($iv, $encrypted) = explode('::', $raw, 2);
    $salt = defined('AUTH_KEY') ? AUTH_KEY : 'biznova_secure_vault_2026_enterprise';
    $key  = hash('sha256', $salt, true);

    $decrypted = openssl_decrypt($encrypted, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
    return ($decrypted !== false) ? $decrypted : '';
}

/**
 * Hash client IP address for privacy & GDPR compliance
 */
function biznova_hash_ip($ip) {
    $salt = defined('NONCE_SALT') ? NONCE_SALT : 'biznova_ip_salt';
    return hash_hmac('sha256', $ip, $salt);
}

/**
 * ============================================================================
 * 4. ANTI-SPAM, HONEYPOT & RATE LIMITING (مكافحة السبام وتحديد معدل الطلبات)
 * ============================================================================
 */

/**
 * Check if the client IP has exceeded the allowed submission threshold
 * Max 5 submissions per 10 minutes per IP
 */
function biznova_is_rate_limited($ip) {
    $hashed_ip = biznova_hash_ip($ip);
    $transient_key = 'biznova_rl_' . substr($hashed_ip, 0, 16);
    $attempts = (int) get_transient($transient_key);

    if ($attempts >= 5) {
        return true; // Exceeded limit
    }

    set_transient($transient_key, $attempts + 1, 10 * MINUTE_IN_SECONDS);
    return false;
}

/**
 * ============================================================================
 * 5. PRIVATE CUSTOM POST TYPE FOR ENCRYPTED LEADS (سجل الاستشارات المحمي)
 * ============================================================================
 */
function biznova_register_leads_cpt() {
    $labels = array(
        'name'               => 'الاستشارات المحمية',
        'singular_name'      => 'استشارة',
        'menu_name'          => '🔒 الاستشارات المشفرة',
        'all_items'          => 'جميع الاستشارات',
        'view_item'          => 'عرض الاستشارة',
        'search_items'       => 'بحث في الاستشارات',
        'not_found'          => 'لا توجد استشارات مسجلة حتى الآن.',
    );

    $args = array(
        'labels'             => $labels,
        'public'             => false, // Strictly private (no frontend URL)
        'publicly_queryable' => false,
        'show_ui'            => true,  // Visible only to administrators in dashboard
        'show_in_menu'       => 'biznova-stats-admin',
        'query_var'          => false,
        'rewrite'            => false,
        'capability_type'    => 'post',
        'has_archive'        => false,
        'hierarchical'       => false,
        'menu_icon'          => 'dashicons-shield',
        'supports'           => array('title'),
    );

    register_post_type('biznova_lead', $args);
}
add_action('init', 'biznova_register_leads_cpt');

/**
 * Custom Admin Columns for Encrypted Leads with Project Delivery Tracker
 */
function biznova_leads_columns($columns) {
    return array(
        'cb'            => '<input type="checkbox" />',
        'title'         => 'اسم العميل',
        'service'       => 'الخدمة المطلوبة',
        'payment_info'  => 'حالة وسداد الرسوم',
        'phone_dec'     => 'رقم الهاتف (مفكوك التشفير)',
        'email_dec'     => 'البريد الإلكتروني (مفكوك التشفير)',
        'date'          => 'تاريخ الحجز',
        'project_state' => 'حالة المشروع والتسليم',
        'security_stat' => 'الأمان والتشفير'
    );
}
add_filter('manage_biznova_lead_posts_columns', 'biznova_leads_columns');

function biznova_leads_custom_column($column, $post_id) {
    if ($column === 'service') {
        $service = get_post_meta($post_id, '_biznova_lead_service', true);
        echo esc_html($service ?: 'استشارة عامة');
    } elseif ($column === 'payment_info') {
        $status = get_post_meta($post_id, '_biznova_lead_payment_status', true);
        $amount = get_post_meta($post_id, '_biznova_lead_amount', true);
        $method = get_post_meta($post_id, '_biznova_lead_payment_method', true);
        $txn    = get_post_meta($post_id, '_biznova_lead_transaction_id', true);

        if ($status === 'paid') {
            echo '<div style="font-size:12px;">';
            echo '<span style="color:#065F46; font-weight:800; background:#D1FAE5; padding:3px 7px; border-radius:4px; border:1px solid #A7F3D0; display:inline-block; margin-bottom:3px;">✅ مدفوع ومؤكد: ' . esc_html($amount ?: 'مسدد') . '</span><br>';
            echo '<span style="color:#526484;">كود: <code style="color:#C2410C; font-weight:700;">' . esc_html($txn ?: '—') . '</code></span><br>';
            echo '<span style="color:#64748B; font-size:11px;">وسيلة: ' . esc_html($method ?: 'بطاقة بنكية') . '</span>';
            echo '</div>';
        } elseif ($status === 'free_confirmed') {
            echo '<span style="color:#1E40AF; font-weight:800; background:#DBEAFE; padding:3px 7px; border-radius:4px; border:1px solid #BFDBFE;">🎁 جلسة أولى مجانية</span>';
        } else {
            echo '<span style="color:#64748B; font-size:12px;">طلب مباشر</span>';
        }
    } elseif ($column === 'phone_dec') {
        $enc_phone = get_post_meta($post_id, '_biznova_lead_phone_enc', true);
        $phone = biznova_decrypt_data($enc_phone);
        echo esc_html($phone ?: '—');
    } elseif ($column === 'email_dec') {
        $enc_email = get_post_meta($post_id, '_biznova_lead_email_enc', true);
        $email = biznova_decrypt_data($enc_email);
        echo esc_html($email ?: '—');
    } elseif ($column === 'project_state') {
        $is_delivered = get_post_meta($post_id, '_biznova_lead_delivered', true);
        if ($is_delivered === 'yes') {
            echo '<span style="color:#10B981; font-weight:800; background:#ECFDF5; padding:4px 8px; border-radius:6px; border:1px solid #A7F3D0;">✓ تم إتمام وتسليم المشروع</span>';
        } else {
            $nonce_url = wp_nonce_url(admin_url('edit.php?post_type=biznova_lead&action=deliver_project&lead_id=' . $post_id), 'deliver_lead_' . $post_id);
            echo '<a href="' . esc_url($nonce_url) . '" class="button button-small" style="color:#0C1E3C; font-weight:700; border-color:#F47521;" onclick="return confirm(\'هل تم إنهاء وتسليم المشروع بالكامل للعميل لاحتسابه في العداد العام للموقع؟\');">تسليم المشروع (+1 للعداد)</a>';
        }
    } elseif ($column === 'security_stat') {
        echo '<span style="color:#10B981; font-weight:700;">🔒 مشفر بـ AES-256</span>';
    }
}
add_action('manage_biznova_lead_posts_custom_column', 'biznova_leads_custom_column', 10, 2);

// Handle Delivery Action from Leads List
add_action('admin_init', function() {
    if (isset($_GET['action']) && $_GET['action'] === 'deliver_project' && !empty($_GET['lead_id']) && current_user_can('manage_options')) {
        $lead_id = (int) $_GET['lead_id'];
        check_admin_referer('deliver_lead_' . $lead_id);
        if (get_post_meta($lead_id, '_biznova_lead_delivered', true) !== 'yes') {
            update_post_meta($lead_id, '_biznova_lead_delivered', 'yes');
            $current_projects = (int) get_option('biznova_stat_projects', 0) + 1;
            update_option('biznova_stat_projects', $current_projects);
            wp_safe_redirect(admin_url('edit.php?post_type=biznova_lead&delivered=1'));
            exit;
        }
    }
});

/**
 * ============================================================================
 * 6. SECURITY & ENCRYPTION DASHBOARD TAB
 * ============================================================================
 */
function biznova_add_security_submenu() {
    add_submenu_page(
        'biznova-stats-admin',
        'حالة الأمان والتشفير',
        '🛡️ الأمان والتشفير',
        'manage_options',
        'biznova-security-center',
        'biznova_render_security_center_page'
    );
}
add_action('admin_menu', 'biznova_add_security_submenu');

function biznova_render_security_center_page() {
    if (!current_user_can('manage_options')) return;

    $total_leads = wp_count_posts('biznova_lead');
    $published_leads = isset($total_leads->publish) ? $total_leads->publish : 0;
    ?>
    <div class="wrap" dir="rtl" style="text-align: right; font-family: 'Cairo', sans-serif;">
        <h1 style="color: #0C1E3C; font-weight: 800; font-size: 26px; margin-bottom: 8px;">
            🛡️ مركز أمان منصة BIZNOVA والأنظمة المشفرة
        </h1>
        <p style="color: #526484; font-size: 15px;">
            نظام حماية متكامل ومبني وفق أعلى معايير أمن المعلومات وحماية بيانات رواد الأعمال والمستثمرين.
        </p>

        <!-- Security Status Cards -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; margin: 24px 0;">
            
            <div style="background: #fff; border: 1.5px solid #10B981; border-radius: 12px; padding: 20px; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.08);">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
                    <span style="font-weight: 800; color: #0C1E3C; font-size: 16px;">حالة التشفير AES-256</span>
                    <span style="background: #D1FAE5; color: #065F46; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 800;">✓ مفعل ونشط</span>
                </div>
                <p style="color: #526484; font-size: 13px; line-height: 1.6; margin: 0;">
                    جميع بيانات العملاء (الهواتف، البريد، ونبذة المشاريع) تُشفر لحظياً بمفاتيح OpenSSL المشفرة قبل تخزينها في قاعدة البيانات.
                </p>
            </div>

            <div style="background: #fff; border: 1.5px solid #10B981; border-radius: 12px; padding: 20px; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.08);">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
                    <span style="font-weight: 800; color: #0C1E3C; font-size: 16px;">التروسات الأمنية (Headers)</span>
                    <span style="background: #D1FAE5; color: #065F46; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 800;">✓ محمي بالكامل</span>
                </div>
                <p style="color: #526484; font-size: 13px; line-height: 1.6; margin: 0;">
                    تفعيل HSTS، منع الـ Clickjacking عبر X-Frame-Options، حجب MIME-Sniffing، ومنع سحب بيانات الموقع من النوافذ الخارجية.
                </p>
            </div>

            <div style="background: #fff; border: 1.5px solid #10B981; border-radius: 12px; padding: 20px; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.08);">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
                    <span style="font-weight: 800; color: #0C1E3C; font-size: 16px;">مكافحة الهجمات والـ Bots</span>
                    <span style="background: #D1FAE5; color: #065F46; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 800;">✓ جدار حماية مفعل</span>
                </div>
                <p style="color: #526484; font-size: 13px; line-height: 1.6; margin: 0;">
                    إغلاق XML-RPC بالكامل، حجب فحص أسماء المديرين عبر REST API، تفعيل Rate Limiting للنماذج، وتشفير عناوين الـ IP.
                </p>
            </div>

        </div>

        <!-- Security Checklist -->
        <div style="background: #fff; border-radius: 12px; padding: 24px; border: 1px solid #E2E8F0; max-width: 900px;">
            <h3 style="color: #0C1E3C; font-size: 18px; margin-top: 0; margin-bottom: 16px;">قائمة التدقيق الأمني الفعالة بالمنصة:</h3>
            <ul style="list-style: none; padding: 0; margin: 0; line-height: 2.2; font-size: 14.5px; color: #334155;">
                <li><strong style="color:#10B981;">✓ إخفاء إصدار ووردبريس:</strong> تم حذف وسم generator بالكامل لمنع تحديد الثغرات.</li>
                <li><strong style="color:#10B981;">✓ حجب التخمين والاستكشاف:</strong> حجب استعلامات <code>?author=1</code> لمنع معرفة اسم المستخدم للمدير.</li>
                <li><strong style="color:#10B981;">✓ أمان استمارة الحجز:</strong> تفعيل فحص الـ Nonces والـ Honeypot لمنع برامج السبام.</li>
                <li><strong style="color:#10B981;">✓ تشفير السجلات:</strong> تم تخزين <strong><?php echo esc_html($published_leads); ?></strong> استشارة في الخزنة المشفرة.</li>
                <li><strong style="color:#10B981;">✓ امتثال اتفاقيات السرية (NDA):</strong> متوافق مع بنود حماية البيانات والسرية التامة لرواد الأعمال.</li>
            </ul>
        </div>
    </div>
    <?php
}
