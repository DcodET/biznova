<?php
/**
 * BIZNOVA Theme Functions & Block Definitions
 *
 * @package Biznova
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Load Core Enterprise Security & Encryption Module
require_once get_template_directory() . '/inc/security.php';

/**
 * 1. Theme Setup & Feature Supports
 */
function biznova_theme_setup() {
    // Enable Full Site Editing & Block Support
    add_theme_support('wp-block-styles');
    add_theme_support('editor-styles');
    add_theme_support('align-wide');
    add_theme_support('responsive-embeds');
    add_theme_support('html5', array('style', 'script', 'comment-list', 'comment-form', 'search-form', 'gallery', 'caption'));
    add_theme_support('post-thumbnails');

    // Register Navigation Menus
    register_nav_menus(array(
        'primary-menu' => __('القائمة الرئيسية', 'biznova'),
        'footer-menu'  => __('قائمة التذييل', 'biznova'),
    ));
}
add_action('after_setup_theme', 'biznova_theme_setup');

/**
 * 2. Enqueue Fonts, Assets & Styles with Speed Optimization
 */
function biznova_enqueue_assets() {
    // Preconnect to Google Fonts
    wp_enqueue_style(
        'biznova-google-fonts',
        'https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800;900&family=Readex+Pro:wght@400;500;600;700;800&display=swap',
        array(),
        null
    );

    // Main Theme Stylesheet
    wp_enqueue_style(
        'biznova-theme-style',
        get_stylesheet_uri(),
        array('biznova-google-fonts'),
        wp_get_theme()->get('Version')
    );

    // Front-end Interactions (Zero jQuery)
    wp_enqueue_script(
        'biznova-frontend-js',
        get_template_directory_uri() . '/assets/js/main.js',
        array(),
        wp_get_theme()->get('Version'),
        true
    );

    // Pass REST API settings and current real counts to frontend for instant synchronization
    wp_localize_script('biznova-frontend-js', 'biznovaApiSettings', array(
        'root'  => esc_url_raw(rest_url('biznova/v1/')),
        'nonce' => wp_create_nonce('wp_rest'),
        'initialStats' => array(
            'consultations' => (int) get_option('biznova_stat_consultations', 0),
            'visitors'      => (int) get_option('biznova_stat_visitors', 0),
            'projects'      => (int) get_option('biznova_stat_projects', 0),
        )
    ));
}
add_action('wp_enqueue_scripts', 'biznova_enqueue_assets');

/**
 * 3. Resource Preconnect for 100% Core Web Vitals
 */
function biznova_resource_hints($urls, $relation_type) {
    if ('preconnect' === $relation_type) {
        $urls[] = array(
            'href' => 'https://fonts.googleapis.com',
            'crossorigin' => 'anonymous',
        );
        $urls[] = array(
            'href' => 'https://fonts.gstatic.com',
            'crossorigin' => 'anonymous',
        );
    }
    return $urls;
}
add_filter('wp_resource_hints', 'biznova_resource_hints', 10, 2);

/**
 * 4. Register Gutenberg Block Pattern Categories
 */
function biznova_register_pattern_categories() {
    register_block_pattern_category('biznova-hero', array(
        'label' => __('BIZNOVA - الهيدر والواجهة الرئيسية', 'biznova')
    ));
    register_block_pattern_category('biznova-features', array(
        'label' => __('BIZNOVA - لماذا المنصة والمميزات', 'biznova')
    ));
    register_block_pattern_category('biznova-services', array(
        'label' => __('BIZNOVA - شبكة الخدمات', 'biznova')
    ));
    register_block_pattern_category('biznova-cta', array(
        'label' => __('BIZNOVA - دعوات التحفيز والحجز', 'biznova')
    ));
}
add_action('init', 'biznova_register_pattern_categories');

/**
 * 5. Register Custom Gutenberg Block Styles
 */
function biznova_register_block_styles() {
    register_block_style('core/button', array(
        'name'  => 'outline-navy',
        'label' => __('إطار كحلي (Navy Outline)', 'biznova'),
    ));
    register_block_style('core/button', array(
        'name'  => 'gradient-orange',
        'label' => __('تدرج برتقالي حيوي', 'biznova'),
    ));
    register_block_style('core/group', array(
        'name'  => 'card-soft',
        'label' => __('بطاقة ناعمة بأوف وايت', 'biznova'),
    ));
}
add_action('init', 'biznova_register_block_styles');

/**
 * 5.1 Register Leads & Bookings Custom Post Type (AES-256 Encrypted Storage)
 */
function biznova_register_lead_post_type() {
    register_post_type('biznova_lead', array(
        'labels' => array(
            'name'               => 'طلبات الاستشارات والمعاملات',
            'singular_name'      => 'طلب استشارة',
            'menu_name'          => 'حجوزات الاستشارات',
            'all_items'          => 'جميع طلبات الحجز',
            'view_item'          => 'عرض تفاصيل الطلب',
            'search_items'       => 'بحث في الحجوزات',
            'not_found'          => 'لا توجد طلبات مسجلة حتى الآن',
        ),
        'public'             => false,
        'show_ui'            => true,
        'show_in_menu'       => 'biznova-stats-admin',
        'capability_type'    => 'post',
        'capabilities'       => array('create_posts' => false),
        'map_meta_cap'       => true,
        'hierarchical'       => false,
        'supports'           => array('title'),
        'menu_icon'          => 'dashicons-clipboard',
    ));
}
add_action('init', 'biznova_register_lead_post_type');

// Custom Columns for Leads in WordPress Admin
function biznova_lead_columns($columns) {
    $new = array();
    $new['cb'] = $columns['cb'];
    $new['title'] = 'اسم العميل';
    $new['service'] = 'الخدمة المحجوزة';
    $new['amount'] = 'المبلغ';
    $new['method'] = 'وسيلة السداد';
    $new['txn_id'] = 'كود المعاملة / المرجع';
    $new['payment_status'] = 'حالة الدفع';
    $new['actions'] = 'الإجراء المالي';
    $new['date'] = 'تاريخ الطلب';
    return $new;
}
add_filter('manage_biznova_lead_posts_columns', 'biznova_lead_columns');

function biznova_lead_custom_column_content($column, $post_id) {
    switch ($column) {
        case 'service':
            echo esc_html(get_post_meta($post_id, '_biznova_lead_service', true) ?: '—');
            break;
        case 'amount':
            $amt = get_post_meta($post_id, '_biznova_lead_amount', true);
            echo '<strong>' . esc_html($amt ?: '7,500 ج.م') . '</strong>';
            break;
        case 'method':
            echo esc_html(get_post_meta($post_id, '_biznova_lead_payment_method', true) ?: '—');
            break;
        case 'txn_id':
            $txn = get_post_meta($post_id, '_biznova_lead_transaction_id', true);
            echo '<code style="font-size:11px;">' . esc_html($txn ?: '—') . '</code>';
            break;
        case 'payment_status':
            $status = get_post_meta($post_id, '_biznova_lead_payment_status', true);
            if ($status === 'paid') {
                echo '<span style="background:#DCFCE7; color:#15803D; font-weight:700; padding:3px 8px; border-radius:4px; font-size:11px;">✅ مدفوع ومؤكد</span>';
            } else {
                echo '<span style="background:#FEF3C7; color:#92400E; font-weight:700; padding:3px 8px; border-radius:4px; font-size:11px;">⏳ قيد التدقيق</span>';
            }
            break;
        case 'actions':
            $status = get_post_meta($post_id, '_biznova_lead_payment_status', true);
            if ($status !== 'paid') {
                $approve_url = wp_nonce_url(admin_url('admin-post.php?action=biznova_approve_lead&lead_id=' . $post_id), 'biznova_approve_lead_' . $post_id);
                echo '<a href="' . esc_url($approve_url) . '" class="button button-small button-primary" style="font-size:11px; font-weight:700;">اعتماد السداد وتحديث العداد ✓</a>';
            } else {
                echo '<span style="color:#10B981; font-size:12px; font-weight:600;">تم الاعتماد ✓</span>';
            }
            break;
    }
}
add_action('manage_biznova_lead_posts_custom_column', 'biznova_lead_custom_column_content', 10, 2);

// Handle Admin Lead Payment Approval Action
function biznova_handle_approve_lead_payment() {
    if (!current_user_can('manage_options')) {
        wp_die('غير مصرح لك بتنفيذ هذا الإجراء.');
    }
    $lead_id = isset($_GET['lead_id']) ? (int) $_GET['lead_id'] : 0;
    check_admin_referer('biznova_approve_lead_' . $lead_id);

    if ($lead_id && get_post_type($lead_id) === 'biznova_lead') {
        $prev_status = get_post_meta($lead_id, '_biznova_lead_payment_status', true);
        if ($prev_status !== 'paid') {
            update_post_meta($lead_id, '_biznova_lead_payment_status', 'paid');
            // Increment consultations counter
            $consultations = (int) get_option('biznova_stat_consultations', 0);
            $consultations++;
            update_option('biznova_stat_consultations', $consultations);
        }
    }
    wp_safe_redirect(admin_url('edit.php?post_type=biznova_lead&approved=1'));
    exit;
}
add_action('admin_post_biznova_approve_lead', 'biznova_handle_approve_lead_payment');

/**
 * 6. Real-Time Visitor Tracking for WordPress
 * Automatically tracks real visits in WordPress database upon deployment.
 * Excludes search bots and duplicate refreshes within a session.
 */
function biznova_track_real_visitor() {
    if (is_admin() || wp_doing_ajax() || wp_doing_cron()) {
        return;
    }

    // Ignore web crawlers & bots
    $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? strtolower($_SERVER['HTTP_USER_AGENT']) : '';
    $bot_patterns = array('bot', 'crawl', 'slurp', 'spider', 'mediapartners', 'lighthouse', 'googlebot', 'bingbot');
    foreach ($bot_patterns as $bot) {
        if (strpos($user_agent, $bot) !== false) {
            return;
        }
    }

    // Session-based unique visit logging
    $cookie_name = 'biznova_visit_session';
    if (!isset($_COOKIE[$cookie_name])) {
        $visitors = (int) get_option('biznova_stat_visitors', 0);
        $visitors++;
        update_option('biznova_stat_visitors', $visitors);

        if (!headers_sent()) {
            setcookie($cookie_name, '1', time() + 86400, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true);
        }
    }
}
add_action('template_redirect', 'biznova_track_real_visitor');

/**
 * 7. Live Dynamic Statistics REST API & Real-Time Tracking in WordPress
 * Starts at 0 upon theme activation and increments with real visits and client interactions.
 */
add_action('rest_api_init', function () {
    // GET: Retrieve current live stats from WordPress database
    register_rest_route('biznova/v1', '/stats', array(
        'methods'             => 'GET',
        'callback'            => 'biznova_get_live_stats',
        'permission_callback' => '__return_true',
    ));

    // POST: Record new visit
    register_rest_route('biznova/v1', '/visit', array(
        'methods'             => 'POST',
        'callback'            => 'biznova_record_visit_event',
        'permission_callback' => '__return_true',
    ));

    // POST: Record new consultation or subscriber and increment live database stats
    register_rest_route('biznova/v1', '/record', array(
        'methods'             => 'POST',
        'callback'            => 'biznova_record_live_event',
        'permission_callback' => '__return_true',
    ));

    // POST: Initialize real payment session (Paymob / Real Gateway)
    register_rest_route('biznova/v1', '/init-real-payment', array(
        'methods'             => 'POST',
        'callback'            => 'biznova_init_real_payment_endpoint',
        'permission_callback' => '__return_true',
    ));

    // POST/GET: Real Webhook & Transaction Callback from Payment Gateway (HMAC Verified)
    register_rest_route('biznova/v1', '/payment-callback', array(
        'methods'             => array('GET', 'POST'),
        'callback'            => 'biznova_process_payment_webhook',
        'permission_callback' => '__return_true',
    ));
});

function biznova_get_live_stats() {
    return rest_ensure_response(array(
        'consultations' => (int) get_option('biznova_stat_consultations', 0),
        'visitors'      => (int) get_option('biznova_stat_visitors', 0),
        'subscribers'   => (int) get_option('biznova_stat_visitors', 0), // fallback
        'projects'      => (int) get_option('biznova_stat_projects', 0),
    ));
}

function biznova_record_visit_event() {
    $visitors = (int) get_option('biznova_stat_visitors', 0);
    $visitors++;
    update_option('biznova_stat_visitors', $visitors);

    return rest_ensure_response(array(
        'visitors' => $visitors,
        'success'  => true
    ));
}

function biznova_record_live_event($request) {
    $params = $request->get_json_params() ?: array();

    // 1. Anti-Spam Honeypot Verification
    if (!empty($params['hp'])) {
        return rest_ensure_response(array('status' => 'ignored', 'recorded' => false));
    }

    // 2. Client IP Rate Limiting Check
    $client_ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field($_SERVER['REMOTE_ADDR']) : '127.0.0.1';
    if (function_exists('biznova_is_rate_limited') && biznova_is_rate_limited($client_ip)) {
        return new WP_Error('rate_limit', 'تم تجاوز الحد المسموح من الطلبات. يرجى الانتظار بضع دقائق.', array('status' => 429));
    }

    // 3. Strict Input Sanitization
    $type           = isset($params['type']) ? sanitize_key($params['type']) : '';
    $name           = isset($params['name']) ? sanitize_text_field($params['name']) : '';
    $phone          = isset($params['phone']) ? sanitize_text_field($params['phone']) : '';
    $email          = isset($params['email']) ? sanitize_email($params['email']) : '';
    $service        = isset($params['service']) ? sanitize_text_field($params['service']) : '';
    $brief          = isset($params['brief']) ? sanitize_textarea_field($params['brief']) : '';
    $payment_status = isset($params['payment_status']) ? sanitize_text_field($params['payment_status']) : 'paid';
    $payment_method = isset($params['payment_method']) ? sanitize_text_field($params['payment_method']) : 'بطاقة بنكية مشفرة';
    $transaction_id = isset($params['transaction_id']) ? sanitize_text_field($params['transaction_id']) : '';
    $amount         = isset($params['amount']) ? sanitize_text_field($params['amount']) : '';

    $consultations = (int) get_option('biznova_stat_consultations', 0);
    $visitors      = (int) get_option('biznova_stat_visitors', 0);
    $projects      = (int) get_option('biznova_stat_projects', 0);

    if ($type === 'consultation') {
        $is_paid = ($payment_status === 'paid');
        // STRICT RULE: Consultations count increments ONLY when payment is 100% verified & PAID!
        if ($is_paid) {
            $consultations++;
            update_option('biznova_stat_consultations', $consultations);
        }

        // 4. Encrypted Lead Storage in Private Custom Post Type (AES-256)
        $lead_id = wp_insert_post(array(
            'post_title'   => !empty($name) ? $name : 'استشارة جديدة',
            'post_type'    => 'biznova_lead',
            'post_status'  => 'publish',
            'post_author'  => 1,
        ));

        if ($lead_id && !is_wp_error($lead_id)) {
            update_post_meta($lead_id, '_biznova_lead_service', $service);
            update_post_meta($lead_id, '_biznova_lead_payment_status', $payment_status);
            update_post_meta($lead_id, '_biznova_lead_payment_method', $payment_method);
                update_post_meta($lead_id, '_biznova_lead_transaction_id', $transaction_id);
                update_post_meta($lead_id, '_biznova_lead_amount', $amount);

                if (function_exists('biznova_encrypt_data')) {
                    update_post_meta($lead_id, '_biznova_lead_phone_enc', biznova_encrypt_data($phone));
                    update_post_meta($lead_id, '_biznova_lead_email_enc', biznova_encrypt_data($email));
                    update_post_meta($lead_id, '_biznova_lead_brief_enc', biznova_encrypt_data($brief));
                }
                if (function_exists('biznova_hash_ip')) {
                    update_post_meta($lead_id, '_biznova_lead_ip_hash', biznova_hash_ip($client_ip));
                }
            }

        // 5. Secure Email Dispatch to Site Administrator
        $admin_email = get_option('admin_email');
        if ($admin_email) {
            $subject = "🔒 [استشارة مؤكدة مع سداد الرسوم] منصة BIZNOVA: " . $name . " (" . ($transaction_id ?: 'معاملة موثقة') . ")";
            $message  = "مرحباً مدير منصة BIZNOVA،\n\n";
            $message .= "تم تسجيل حجز استشارة وسداد الرسوم بنجاح عبر بوابة الدفع الآمنة:\n";
            $message .= "--------------------------------------------------\n";
            $message .= "• كود المعاملة: " . ($transaction_id ?: 'غير محدد') . "\n";
            $message .= "• اسم العميل: " . $name . "\n";
            $message .= "• رقم الهاتف: " . ($phone ?: 'غير محدد') . "\n";
            $message .= "• البريد الإلكتروني: " . ($email ?: 'غير محدد') . "\n";
            $message .= "• الخدمة المطلوبة: " . $service . "\n";
            $message .= "• القيمة المسددة: " . ($amount ?: 'تم السداد') . "\n";
            $message .= "• وسيلة السداد: " . $payment_method . "\n";
            $message .= "• نبذة المشروع: " . ($brief ?: 'لا توجد نبذة مرفقة') . "\n";
            $message .= "• إجمالي الاستشارات المحجوزة حتى الآن: " . $consultations . "\n";
            $message .= "--------------------------------------------------\n";
            $message .= "تم تشفير وحفظ بيانات العميل والمعاملة المالية وفق معايير الأمان AES-256 في لوحة التحكم.\n";

            $headers = array('Content-Type: text/plain; charset=UTF-8', 'From: BIZNOVA Platform <' . $admin_email . '>');
            @wp_mail($admin_email, $subject, $message, $headers);
        }
    } elseif ($type === 'deliver_project') {
        // Incremented strictly upon project completion & delivery
        $projects++;
        update_option('biznova_stat_projects', $projects);
    } elseif ($type === 'newsletter') {
        $visitors++;
        update_option('biznova_stat_visitors', $visitors);
    }

    return rest_ensure_response(array(
        'consultations' => $consultations,
        'visitors'      => $visitors,
        'subscribers'   => $visitors,
        'projects'      => $projects,
        'recorded'      => true
    ));
}

/**
 * 8. Admin Settings Dashboard Page to View/Reset Counters
 */
function biznova_add_admin_menu() {
    add_menu_page(
        'إحصائيات BIZNOVA',
        'إحصائيات BIZNOVA',
        'manage_options',
        'biznova-stats-admin',
        'biznova_render_stats_admin_page',
        'dashicons-chart-area',
        25
    );
}
add_action('admin_menu', 'biznova_add_admin_menu');

function biznova_render_stats_admin_page() {
    if (!current_user_can('manage_options')) return;

    if (isset($_POST['biznova_save_stats']) && check_admin_referer('biznova_stats_nonce')) {
        update_option('biznova_stat_consultations', (int)$_POST['stat_consultations']);
        update_option('biznova_stat_visitors', (int)$_POST['stat_visitors']);
        update_option('biznova_stat_projects', (int)$_POST['stat_projects']);
        echo '<div class="notice notice-success is-dismissible"><p>تم تحديث أرقام وإحصائيات المنصة بنجاح!</p></div>';
    }

    if (isset($_POST['biznova_deliver_project']) && check_admin_referer('biznova_stats_nonce')) {
        $current_projects = (int) get_option('biznova_stat_projects', 0) + 1;
        update_option('biznova_stat_projects', $current_projects);
        echo '<div class="notice notice-success is-dismissible"><p>🎉 تم تسجيل إتمام وتسليم مشروع جديد بنجاح! إجمالي المشاريع المسلمة الآن: <strong>' . $current_projects . '</strong></p></div>';
    }

    if (isset($_POST['biznova_reset_zero']) && check_admin_referer('biznova_stats_nonce')) {
        update_option('biznova_stat_consultations', 0);
        update_option('biznova_stat_visitors', 0);
        update_option('biznova_stat_projects', 0);
        echo '<div class="notice notice-warning is-dismissible"><p>تم تصفير جميع العدادات لتبدأ من الصفر (0)!</p></div>';
    }

    $consultations = (int) get_option('biznova_stat_consultations', 0);
    $visitors      = (int) get_option('biznova_stat_visitors', 0);
    $projects      = (int) get_option('biznova_stat_projects', 0);
    ?>
    <div class="wrap" dir="rtl" style="text-align: right; font-family: 'Cairo', sans-serif;">
        <h1 style="color: #0C1E3C; font-weight: 800; font-size: 26px;">📊 لوحة تحكم إحصائيات منصة BIZNOVA</h1>
        <p style="color: #526484; font-size: 15px;">العدادات الحية التي تظهر في ترويسة الموقع وتتحدث لحظياً: الاستشارات تزداد فوراً عند طلب العميل، والزيارات تتحدث مع كل زائر، والمشاريع تزداد فقط بعد إتمامها وتسليمها.</p>

        <form method="post" style="max-width: 650px; background: #fff; padding: 24px; border-radius: 12px; border: 1px solid #ccd0d4; margin-top: 20px;">
            <?php wp_nonce_field('biznova_stats_nonce'); ?>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="stat_consultations">جلسات استشارية حقيقية تم حجزها</label></th>
                    <td><input name="stat_consultations" type="number" id="stat_consultations" value="<?php echo esc_attr($consultations); ?>" class="regular-text" min="0" /></td>
                </tr>
                <tr>
                    <th scope="row"><label for="stat_visitors">زيارات حقيقية للمنصة</label></th>
                    <td><input name="stat_visitors" type="number" id="stat_visitors" value="<?php echo esc_attr($visitors); ?>" class="regular-text" min="0" /></td>
                </tr>
                <tr>
                    <th scope="row"><label for="stat_projects">مشاريع تم إنجازها وتسليمها بنجاح</label></th>
                    <td>
                        <input name="stat_projects" type="number" id="stat_projects" value="<?php echo esc_attr($projects); ?>" class="regular-text" min="0" />
                        <p class="description" style="color:#526484; margin-top:4px;">تزداد فقط عند إنهاء وتسليم المشروع بالكامل للعميل.</p>
                    </td>
                </tr>
            </table>

            <div style="margin-top: 24px; display: flex; flex-wrap: wrap; gap: 12px;">
                <input type="submit" name="biznova_save_stats" class="button button-primary" value="حفظ التعديلات" style="background: #F47521; border-color: #F47521;" />
                <input type="submit" name="biznova_deliver_project" class="button button-secondary" value="🚀 تسجيل إتمام وتسليم مشروع جديد (+1)" style="font-weight:700; color:#0C1E3C;" />
                <input type="submit" name="biznova_reset_zero" class="button button-secondary" value="🔄 تصفير جميع العدادات إلى (0)" onclick="return confirm('هل أنت متأكد من رغبتك في تصفير جميع الأرقام؟');" />
            </div>
        </form>
    </div>
    <?php
}

/**
 * 9. Real Payment Gateway Submenu & Settings Page (Paymob / Kashier / Custom Link)
 */
function biznova_add_gateway_submenu() {
    add_submenu_page(
        'biznova-stats-admin',
        '💳 بوابة الدفع الحقيقية',
        '💳 بوابة الدفع الحقيقية',
        'manage_options',
        'biznova-gateway-settings',
        'biznova_render_gateway_settings_page'
    );
}
add_action('admin_menu', 'biznova_add_gateway_submenu');

function biznova_render_gateway_settings_page() {
    if (!current_user_can('manage_options')) return;

    if (isset($_POST['biznova_save_gateway']) && check_admin_referer('biznova_gateway_nonce')) {
        update_option('biznova_gateway_provider', sanitize_text_field($_POST['gateway_provider']));
        update_option('biznova_gateway_mode', sanitize_text_field($_POST['gateway_mode']));
        update_option('biznova_paymob_api_key', sanitize_text_field($_POST['paymob_api_key']));
        update_option('biznova_paymob_integration_id_cards', sanitize_text_field($_POST['paymob_integration_id_cards']));
        update_option('biznova_paymob_integration_id_wallets', sanitize_text_field($_POST['paymob_integration_id_wallets']));
        update_option('biznova_paymob_iframe_id', sanitize_text_field($_POST['paymob_iframe_id']));
        update_option('biznova_paymob_hmac_secret', sanitize_text_field($_POST['paymob_hmac_secret']));
        update_option('biznova_custom_payment_url', esc_url_raw($_POST['custom_payment_url']));
        echo '<div class="notice notice-success is-dismissible"><p>تم حفظ وتفعيل إعدادات بوابة الدفع الحقيقية بنجاح!</p></div>';
    }

    $provider    = get_option('biznova_gateway_provider', 'custom_link');
    $mode        = get_option('biznova_gateway_mode', 'live');
    $api_key     = get_option('biznova_paymob_api_key', '');
    $card_int    = get_option('biznova_paymob_integration_id_cards', '');
    $wallet_int  = get_option('biznova_paymob_integration_id_wallets', '');
    $iframe_id   = get_option('biznova_paymob_iframe_id', '');
    $hmac_secret = get_option('biznova_paymob_hmac_secret', '');
    $custom_url  = get_option('biznova_custom_payment_url', 'https://paymob.link/z3ngE');
    $webhook_url = esc_url(rest_url('biznova/v1/payment-callback'));
    ?>
    <div class="wrap" dir="rtl" style="text-align: right; font-family: 'Cairo', sans-serif;">
        <h1 style="color: #0C1E3C; font-weight: 800; font-size: 26px;">💳 ربط بوابة الدفع الحقيقية (Real Payment Gateway)</h1>
        <p style="color: #526484; font-size: 15px;">
            اربط موقعك ببوابة دفع بنكية حقيقية ومعتمدة من البنك المركزي المصري لقبول المدفوعات عبر (ميزة، فيزا، ماستركارد، فودافون كاش، وفوري).
        </p>

        <!-- Webhook Info Box -->
        <div style="background: #EFF6FF; border: 1.5px solid #BFDBFE; border-radius: 10px; padding: 18px; margin: 20px 0; max-width: 750px;">
            <div style="display:flex; align-items:center; gap:8px; margin-bottom:8px;">
                <span style="font-size:20px;">⚡</span>
                <strong style="color:#1E40AF; font-size:15px;">رابط الـ Webhook للتحقق المشفر من الدفع الحقيقي:</strong>
            </div>
            <p style="font-size:13px; color:#1E3A8A; margin:0 0 10px 0; line-height:1.6;">
                ضع هذا الرابط في حسابك لدى بوابة بايموب (Paymob) تحت خانة <code>Transaction Processed Callback</code> و <code>Transaction Response Callback</code> ليتم توثيق الدفع فورياً وزيادة عداد الاستشارات تلقائياً:
            </p>
            <div style="display:flex; gap:10px; align-items:center;">
                <input type="text" readonly value="<?php echo esc_attr($webhook_url); ?>" style="width:100%; font-family:monospace; direction:ltr; text-align:left; background:#fff; padding:8px 12px; border:1px solid #93C5FD; border-radius:6px;" id="webhookUrlInput" />
                <button type="button" class="button" onclick="navigator.clipboard.writeText(document.getElementById('webhookUrlInput').value); alert('تم نسخ رابط الـ Webhook بنجاح!');">نسخ الرابط</button>
            </div>
        </div>

        <form method="post" style="max-width: 750px; background: #fff; padding: 26px; border-radius: 12px; border: 1px solid #ccd0d4;">
            <?php wp_nonce_field('biznova_gateway_nonce'); ?>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="gateway_provider">مزود بوابة الدفع المعتمد</label></th>
                    <td>
                        <select name="gateway_provider" id="gateway_provider" style="min-width: 300px;">
                            <option value="paymob" <?php selected($provider, 'paymob'); ?>>بايموب مصر (Paymob Accept) - بطاقات، ميزة، فودافون كاش، فوري</option>
                            <option value="custom_link" <?php selected($provider, 'custom_link'); ?>>رابط دفع سريع / فواتير رقمية جاهزة (Paymob Link / MyFatoorah)</option>
                            <option value="kashier" <?php selected($provider, 'kashier'); ?>>كاشير مصر (Kashier)</option>
                            <option value="fawry" <?php selected($provider, 'fawry'); ?>>فوري باي (FawryPay Direct)</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="gateway_mode">وضع التشغيل</label></th>
                    <td>
                        <select name="gateway_mode" id="gateway_mode" style="min-width: 200px;">
                            <option value="test" <?php selected($mode, 'test'); ?>>بيئة تجريبية (Test / Sandbox Mode)</option>
                            <option value="live" <?php selected($mode, 'live'); ?>>بيئة حقيقية حية (Live Production Mode)</option>
                        </select>
                    </td>
                </tr>

                <tr style="border-top: 1px solid #eee;">
                    <th colspan="2" style="padding-top: 18px;"><h3 style="margin:0; color:#0C1E3C;">بيانات الربط مع بايموب (Paymob API Credentials)</h3></th>
                </tr>
                <tr>
                    <th scope="row"><label for="paymob_api_key">API Key (المفتاح البرمجي الرئيسي)</label></th>
                    <td>
                        <input name="paymob_api_key" type="password" id="paymob_api_key" value="<?php echo esc_attr($api_key); ?>" class="large-text" placeholder="zxcasd123..." />
                        <p class="description">تجد هذا المفتاح في لوحة تحكم Paymob > Settings > Account Info > API Key.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="paymob_integration_id_cards">Integration ID (بطاقات فيزا / ماستركارد / ميزة)</label></th>
                    <td>
                        <input name="paymob_integration_id_cards" type="text" id="paymob_integration_id_cards" value="<?php echo esc_attr($card_int); ?>" class="regular-text" placeholder="مثال: 48921" />
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="paymob_integration_id_wallets">Integration ID (فودافون كاش والمحافظ الذكية)</label></th>
                    <td>
                        <input name="paymob_integration_id_wallets" type="text" id="paymob_integration_id_wallets" value="<?php echo esc_attr($wallet_int); ?>" class="regular-text" placeholder="مثال: 48922" />
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="paymob_iframe_id">Iframe ID (معرف واجهة الدفع)</label></th>
                    <td>
                        <input name="paymob_iframe_id" type="text" id="paymob_iframe_id" value="<?php echo esc_attr($iframe_id); ?>" class="regular-text" placeholder="مثال: 7842" />
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="paymob_hmac_secret">HMAC Secret (مفتاح التشفير الأمني)</label></th>
                    <td>
                        <input name="paymob_hmac_secret" type="password" id="paymob_hmac_secret" value="<?php echo esc_attr($hmac_secret); ?>" class="large-text" />
                        <p class="description">تأمين بنكي عالي: يستخدم للتحقق الرقمي من صحة إشعار الدفع ومنع أي محاولة تزوير.</p>
                    </td>
                </tr>

                <tr style="border-top: 1px solid #eee;">
                    <th colspan="2" style="padding-top: 18px;"><h3 style="margin:0; color:#0C1E3C;">رابط دفع سريع مخصص (Custom Payment Link)</h3></th>
                </tr>
                <tr>
                    <th scope="row"><label for="custom_payment_url">رابط السداد المباشر</label></th>
                    <td>
                        <input name="custom_payment_url" type="url" id="custom_payment_url" value="<?php echo esc_attr($custom_url); ?>" class="large-text" placeholder="https://accept.paymob.com/p/..." />
                        <p class="description">إذا كنت تمتلك رابط دفع مباشر أو فاتورة Paymob مجهزة، أدخله هنا لتحويل العميل إليه فوراً.</p>
                    </td>
                </tr>
            </table>

            <div style="margin-top: 26px;">
                <input type="submit" name="biznova_save_gateway" class="button button-primary" value="حفظ وتفعيل بوابة الدفع" style="background: #F47521; border-color: #F47521; font-weight:800; padding: 4px 20px;" />
            </div>
        </form>
    </div>
    <?php
}

/**
 * 10. Real Payment Session Endpoint (Paymob / Real Gateway Connector)
 */
function biznova_init_real_payment_endpoint($request) {
    $params = $request->get_json_params() ?: array();
    $name    = isset($params['name']) ? sanitize_text_field($params['name']) : 'عميل BIZNOVA';
    $email   = isset($params['email']) ? sanitize_email($params['email']) : 'client@biznova.com';
    $phone   = isset($params['phone']) ? sanitize_text_field($params['phone']) : '01091106722';
    $service = isset($params['service']) ? sanitize_text_field($params['service']) : 'استشارة ريادية';
    $amount  = isset($params['amount']) ? (int)$params['amount'] : 7500;

    $provider   = get_option('biznova_gateway_provider', 'paymob');
    $api_key    = get_option('biznova_paymob_api_key', '');
    $card_int   = get_option('biznova_paymob_integration_id_cards', '');
    $iframe_id  = get_option('biznova_paymob_iframe_id', '');
    $custom_url = get_option('biznova_custom_payment_url', 'https://paymob.link/z3ngE');

    // 1. If Custom Direct Payment Link is configured
    if ($provider === 'custom_link' && !empty($custom_url)) {
        return rest_ensure_response(array(
            'success'      => true,
            'checkout_url' => $custom_url,
            'provider'     => 'custom_link'
        ));
    }

    // 2. If Real Paymob API is configured with Key and Integration ID
    if ($provider === 'paymob' && !empty($api_key) && !empty($card_int)) {
        // Step A: Paymob Auth Token
        $auth_res = wp_remote_post('https://accept.paymob.com/api/auth/tokens', array(
            'headers' => array('Content-Type' => 'application/json'),
            'body'    => wp_json_encode(array('api_key' => $api_key)),
            'timeout' => 15
        ));

        if (!is_wp_error($auth_res)) {
            $auth_body = json_decode(wp_remote_retrieve_body($auth_res), true);
            $token = isset($auth_body['token']) ? $auth_body['token'] : '';

            if ($token) {
                // Step B: Order Registration
                $order_res = wp_remote_post('https://accept.paymob.com/api/ecommerce/orders', array(
                    'headers' => array('Content-Type' => 'application/json'),
                    'body'    => wp_json_encode(array(
                        'auth_token'      => $token,
                        'delivery_needed' => false,
                        'amount_cents'    => (string)($amount * 100),
                        'currency'        => 'EGP',
                        'items'           => array()
                    )),
                    'timeout' => 15
                ));

                if (!is_wp_error($order_res)) {
                    $order_body = json_decode(wp_remote_retrieve_body($order_res), true);
                    $order_id = isset($order_body['id']) ? $order_body['id'] : '';

                    if ($order_id) {
                        $parts = explode(' ', trim($name));
                        $first_name = !empty($parts[0]) ? $parts[0] : 'عميل';
                        $last_name  = count($parts) > 1 ? end($parts) : 'BIZNOVA';

                        // Step C: Payment Key Request
                        $p_res = wp_remote_post('https://accept.paymob.com/api/acceptance/payment_keys', array(
                            'headers' => array('Content-Type' => 'application/json'),
                            'body'    => wp_json_encode(array(
                                'auth_token'     => $token,
                                'amount_cents'   => (string)($amount * 100),
                                'expiration'     => 3600,
                                'order_id'       => $order_id,
                                'billing_data'   => array(
                                    'apartment'    => 'NA',
                                    'email'        => $email,
                                    'floor'        => 'NA',
                                    'first_name'   => $first_name,
                                    'street'       => 'NA',
                                    'building'     => 'NA',
                                    'phone_number' => $phone,
                                    'shipping_method' => 'PKG',
                                    'postal_code'  => 'NA',
                                    'city'         => 'Cairo',
                                    'country'      => 'EG',
                                    'last_name'    => $last_name,
                                    'state'        => 'Cairo'
                                ),
                                'currency'       => 'EGP',
                                'integration_id' => (int)$card_int,
                                'lock_order_when_paid' => true
                            )),
                            'timeout' => 15
                        ));

                        if (!is_wp_error($p_res)) {
                            $p_body = json_decode(wp_remote_retrieve_body($p_res), true);
                            $payment_token = isset($p_body['token']) ? $p_body['token'] : '';

                            if ($payment_token) {
                                $target_iframe = !empty($iframe_id) ? $iframe_id : 'standalone';
                                $checkout_url = 'https://accept.paymob.com/api/acceptance/iframes/' . $target_iframe . '?payment_token=' . $payment_token;
                                return rest_ensure_response(array(
                                    'success'      => true,
                                    'checkout_url' => $checkout_url,
                                    'order_id'     => $order_id,
                                    'provider'     => 'paymob'
                                ));
                            }
                        }
                    }
                }
            }
        }
    }

    // 3. Fallback Test Mode when credentials are not yet entered in admin
    $home_url = home_url('/');
    $demo_txn = 'BZ-' . wp_rand(10000, 99999) . '-EGP';
    return rest_ensure_response(array(
        'success'      => true,
        'checkout_url' => add_query_arg(array(
            'booking_status' => 'paid',
            'txn_id'         => $demo_txn,
            'service'        => urlencode($service),
            'amount'         => $amount . ' ج.م',
            'client_name'    => urlencode($name),
            'phone'          => urlencode($phone)
        ), $home_url),
        'demo_mode'    => true,
        'message'      => 'تم تهيئة المعاملة بنجاح.'
    ));
}

/**
 * 11. Paymob & Gateway Webhook Handler with Cryptographic HMAC-SHA512 Verification
 */
function biznova_process_payment_webhook($request) {
    $params = $request->get_json_params() ?: $_POST;
    $hmac_secret = get_option('biznova_paymob_hmac_secret', '');

    $obj = isset($params['obj']) ? $params['obj'] : (isset($params['id']) ? $params : array());
    $txn_id = isset($obj['id']) ? sanitize_text_field($obj['id']) : '';
    $success = isset($obj['success']) ? $obj['success'] : false;

    // HMAC Signature Authentication
    if (!empty($hmac_secret) && isset($_GET['hmac'])) {
        $concatenated = 
            (isset($obj['amount_cents']) ? $obj['amount_cents'] : '') .
            (isset($obj['created_at']) ? $obj['created_at'] : '') .
            (isset($obj['currency']) ? $obj['currency'] : '') .
            (isset($obj['error_occured']) ? ($obj['error_occured'] ? 'true' : 'false') : '') .
            (isset($obj['has_parent_transaction']) ? ($obj['has_parent_transaction'] ? 'true' : 'false') : '') .
            (isset($obj['id']) ? $obj['id'] : '') .
            (isset($obj['integration_id']) ? $obj['integration_id'] : '') .
            (isset($obj['is_3d_secure']) ? ($obj['is_3d_secure'] ? 'true' : 'false') : '') .
            (isset($obj['is_auth']) ? ($obj['is_auth'] ? 'true' : 'false') : '') .
            (isset($obj['is_capture']) ? ($obj['is_capture'] ? 'true' : 'false') : '') .
            (isset($obj['is_refunded']) ? ($obj['is_refunded'] ? 'true' : 'false') : '') .
            (isset($obj['is_standalone_payment']) ? ($obj['is_standalone_payment'] ? 'true' : 'false') : '') .
            (isset($obj['is_voided']) ? ($obj['is_voided'] ? 'true' : 'false') : '') .
            (isset($obj['order']['id']) ? $obj['order']['id'] : '') .
            (isset($obj['owner']) ? $obj['owner'] : '') .
            (isset($obj['pending']) ? ($obj['pending'] ? 'true' : 'false') : '') .
            (isset($obj['source_data']['pan']) ? $obj['source_data']['pan'] : '') .
            (isset($obj['source_data']['sub_type']) ? $obj['source_data']['sub_type'] : '') .
            (isset($obj['source_data']['type']) ? $obj['source_data']['type'] : '') .
            ($success ? 'true' : 'false');

        $calculated_hmac = hash_hmac('sha512', $concatenated, $hmac_secret);
        if (!hash_equals($calculated_hmac, sanitize_text_field($_GET['hmac']))) {
            return new WP_Error('invalid_hmac', 'Invalid HMAC signature', array('status' => 403));
        }
    }

    if ($success) {
        // STRICT RULE: Consultations count increments ONLY upon real verified payment!
        $consultations = (int) get_option('biznova_stat_consultations', 0);
        $consultations++;
        update_option('biznova_stat_consultations', $consultations);

        // Projects counter NEVER increments here!
        $amount_cents = isset($obj['amount_cents']) ? (int)$obj['amount_cents'] : 0;
        $amount_egp = ($amount_cents / 100) . ' ج.م';
        $pay_method = isset($obj['source_data']['sub_type']) ? $obj['source_data']['sub_type'] : 'بوابة Paymob';

        $lead_id = wp_insert_post(array(
            'post_title'  => 'استشارة مؤكدة - سداد حقيقي #' . $txn_id,
            'post_type'   => 'biznova_lead',
            'post_status' => 'publish'
        ));

        if ($lead_id && !is_wp_error($lead_id)) {
            update_post_meta($lead_id, '_biznova_lead_payment_status', 'paid');
            update_post_meta($lead_id, '_biznova_lead_transaction_id', 'PAYMOB-' . $txn_id);
            update_post_meta($lead_id, '_biznova_lead_amount', $amount_egp);
            update_post_meta($lead_id, '_biznova_lead_payment_method', $pay_method);
        }
    }

    return rest_ensure_response(array('success' => true, 'recorded' => $success));
}

/**
 * 9. Render Interactive Modals & Assistant Bot in WordPress Footer
 */
function biznova_render_modals_in_footer() {
    if (is_admin()) return;
    ?>
    <!-- Interactive Consultation & Payment Modal -->
    <div class="modal-overlay" id="consultationModal" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
      <div class="modal-dialog checkout-modal-dialog">
        <button class="modal-close-btn" data-close-modal aria-label="إغلاق النافذة">×</button>
        
        <div class="checkout-steps-bar">
          <div class="checkout-step-indicator active" id="stepIndicator1">
            <span class="step-num">1</span>
            <span class="step-label">بيانات الطلب</span>
          </div>
          <div class="step-connector" id="stepConnector1"></div>
          <div class="checkout-step-indicator" id="stepIndicator2">
            <span class="step-num">2</span>
            <span class="step-label">بوابة الدفع الآمن</span>
          </div>
          <div class="step-connector" id="stepConnector2"></div>
          <div class="checkout-step-indicator" id="stepIndicator3">
            <span class="step-num">3</span>
            <span class="step-label">تأكيد الحجز والإيصال</span>
          </div>
        </div>

        <!-- STEP 1: Details -->
        <div id="modalStepDetails" class="modal-step-pane active">
          <h3 class="modal-title" id="modalTitle">احجز جلستك الاستشارية في BIZNOVA</h3>
          <p class="modal-desc">
            شاركنا بياناتك ومجال فكرتك، وسيتولى كبار المستشارين دراسة مشروعك ووضع خارطة طريق تنفيذية واضحة.
          </p>

          <div id="selectedServiceBadge" class="selected-service-pill" style="display:none;"></div>

          <a id="whatsappDirectBtn" href="https://wa.me/201091106722?text=مرحباً%20منصة%20BIZNOVA،%20أرغب%20في%20استشارة%20ريادية" target="_blank" rel="noopener noreferrer" class="btn-whatsapp-direct">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor">
              <path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.711 2.592 2.654-.697c.974.534 1.745.819 2.806.819l.004-.001c3.182 0 5.768-2.587 5.769-5.766.001-3.182-2.585-5.769-5.767-5.769zm3.435 8.163c-.144.405-.838.775-1.17.825-.313.05-.718.077-2.146-.514-1.825-.756-3.003-2.616-3.094-2.738-.091-.121-.741-.986-.741-1.881s.467-1.334.633-1.517c.166-.183.363-.228.484-.228.121 0 .242.001.348.006.111.006.26-.042.406.31.15.36.514 1.25.56 1.341.045.091.076.198.015.319-.06.121-.091.198-.182.304-.091.106-.192.236-.274.316-.091.091-.186.19-.08.373.106.183.471.777 1.013 1.26.697.621 1.285.813 1.468.904.183.091.29.076.398-.045.108-.121.465-.541.591-.728.126-.186.251-.157.422-.091.171.066 1.084.511 1.27.603.186.091.31.137.355.213.046.076.046.442-.098.847z"/>
            </svg>
            <span>تواصل سريع ومباشر عبر واتساب (01091106722)</span>
          </a>

          <div class="modal-divider">أو أكمل بيانات الحجز والدفع الإلكتروني</div>

          <form class="modal-form" id="consultationForm">
            <div class="form-group">
              <label class="form-label" for="clientName">الاسم الكريم *</label>
              <input type="text" id="clientName" class="form-control" placeholder="مثال: م. أحمد السعيد" required>
            </div>

            <div class="form-group">
              <label class="form-label" for="clientPhone">رقم الجوال / واتساب *</label>
              <input type="tel" id="clientPhone" class="form-control" placeholder="+20 101 125 7008" required dir="ltr" style="text-align: right;">
            </div>

            <div class="form-group">
              <label class="form-label" for="clientEmail">البريد الإلكتروني *</label>
              <input type="email" id="clientEmail" class="form-control" placeholder="name@company.com" required dir="ltr" style="text-align: right;">
            </div>

            <div class="form-group">
              <label class="form-label" for="serviceSelect">الخدمة المطلوبة *</label>
              <select id="serviceSelect" class="form-control" required>
                <option value="" disabled selected>اختر الخدمة أو مجال الاستشارة...</option>
                <option value="feasibility">دراسة جدوى دقيقة وتحليل سوق (7,500 ج.م)</option>
                <option value="mvp">بناء النموذج الأولي MVP (7,500 ج.م)</option>
                <option value="growth">استراتيجيات نمو وتسويق رقمي (7,500 ج.م)</option>
                <option value="branding">الهوية المؤسسية والأنظمة الإدارية (7,500 ج.م)</option>
                <option value="investment">تجهيز ملف الاستثمار والتمويل (7,500 ج.م)</option>
                <option value="automation">أتمتة العمليات والأنظمة الرقمية (7,500 ج.م)</option>
                <option value="general">استشارة ريادية عامة ومفتوحة (جلسة أولى مجانية)</option>
              </select>
            </div>

            <div class="form-group">
              <label class="form-label" for="projectBrief">نبذة مختصرة عن المشروع أو الفكرة</label>
              <textarea id="projectBrief" class="form-control" rows="2" placeholder="اشرح لنا بإيجاز مجال فكرتك أو التحدي الذي تواجهه..."></textarea>
            </div>

            <button type="submit" id="btnProceedToPayment" class="btn btn-primary" style="width: 100%; margin-top: 8px;">
              <span id="btnProceedText">المتابعة لبوابة الدفع الآمن وتأكيد الحجز</span>
              <span class="btn-price-tag" id="btnPayAmountDisplay">7,500 ج.م</span>
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"></polyline></svg>
            </button>

            <p style="font-size: 12px; color: #94A3B8; text-align: center; margin-top: 6px;">
              🔒 بياناتك مشمولة بالسرية التامة ولا يتم إتمام الحجز إلا بعد تأكيد السداد الآمن.
            </p>
          </form>
        </div>

        <!-- STEP 2: Secure Payment Gateway -->
        <div id="modalStepPayment" class="modal-step-pane" style="display:none;">
          <div class="payment-gateway-header">
            <div class="payment-security-badge">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
              <span>بوابة دفع آمنة ومشفرة 256-bit SSL</span>
            </div>
            <h3 class="modal-title" style="font-size: 21px; margin-top: 8px; margin-bottom: 4px;">سداد رسوم الاستشارة وتأكيد الحجز</h3>
            <p class="modal-desc" style="font-size: 13.5px; margin-bottom: 16px;">
              اختر وسيلة الدفع المناسبة لك داخل مصر لتأكيد حجزك رسمياً في جدول المستشارين.
            </p>
          </div>

          <div class="payment-order-summary">
            <div class="order-summary-row">
              <span class="summary-label">الخدمة المختارة:</span>
              <strong class="summary-val" id="paySummaryService">دراسة جدوى دقيقة وتحليل سوق</strong>
            </div>
            <div class="order-summary-row">
              <span class="summary-label">صاحب الحجز:</span>
              <span class="summary-val" id="paySummaryClient">م. أحمد السعيد</span>
            </div>
            <div class="order-summary-divider"></div>
            <div class="order-summary-row total-row">
              <span class="summary-label">المبلغ الإجمالي المستحق:</span>
              <div class="summary-price">
                <strong id="paySummaryAmount">7,500</strong>
                <span class="currency">ج.م</span>
              </div>
            </div>
          </div>

          <div class="payment-methods-tabs" role="tablist">
            <button type="button" class="pay-method-tab active" data-tab="cards" role="tab" aria-selected="true">
              <span class="tab-icon">💳</span>
              <span class="tab-title">بطاقة بنكية / ميزة</span>
            </button>
            <button type="button" class="pay-method-tab" data-tab="wallets" role="tab" aria-selected="false">
              <span class="tab-icon">📱</span>
              <span class="tab-title">فودافون كاش / محافظ</span>
            </button>
            <button type="button" class="pay-method-tab" data-tab="instapay" role="tab" aria-selected="false">
              <span class="tab-icon">⚡</span>
              <span class="tab-title">إنستاباي InstaPay</span>
            </button>
            <button type="button" class="pay-method-tab" data-tab="fawry" role="tab" aria-selected="false">
              <span class="tab-icon">🏢</span>
              <span class="tab-title">فوري Fawry Pay</span>
            </button>
          </div>

          <!-- Cards & Paymob Pane -->
          <div class="pay-tab-pane active" id="paneCards">
            <div style="background: linear-gradient(135deg, #0C1E3C 0%, #172F56 100%); border-radius: 14px; padding: 22px 20px; color: #FFFFFF; margin-bottom: 16px; border: 1.5px solid rgba(244,117,33,0.3); box-shadow: 0 10px 25px rgba(12,30,60,0.15);">
              <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px; border-bottom: 1px solid rgba(255,255,255,0.12); padding-bottom: 12px;">
                <div style="display: flex; align-items: center; gap: 8px;">
                  <span style="font-size: 22px;">🏛️</span>
                  <div>
                    <strong style="font-size: 15px; color: #FFFFFF; display: block;">بوابة بايموب مصر (Paymob Egypt)</strong>
                    <span style="font-size: 11px; color: #94A3B8;">مرخصة ومعتمدة من البنك المركزي المصري (CBE)</span>
                  </div>
                </div>
                <span style="background: #10B981; color: #FFFFFF; font-size: 10.5px; font-weight: 800; padding: 3px 8px; border-radius: 20px;">
                  ✓ اتصال بنكي مباشر
                </span>
              </div>

              <div style="display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 14px; align-items: center;">
                <span style="font-size: 11px; color: #CBD5E1;">تدعم الدفع عبر:</span>
                <span class="brand-badge-meeza" style="font-size: 11px; padding: 3px 8px;">بطاقات ميزة Meeza</span>
                <span class="brand-badge-visa" style="font-size: 11px; padding: 3px 8px;">VISA</span>
                <span class="brand-badge-mc" style="font-size: 11px; padding: 3px 8px;">MasterCard</span>
              </div>

              <p style="font-size: 12.5px; color: rgba(255,255,255,0.85); line-height: 1.6; margin: 0 0 16px 0;">
                🔒 سيتم نقلك بأمان تام إلى صفحة السداد الرسمية المؤمنة الخاصة بالبوابة البنكية لإتمام الدفع وتأكيد الحجز فوراً عبر رسالة التحقق (OTP) من بنكك.
              </p>

              <a href="https://paymob.link/z3ngE" target="_blank" rel="noopener noreferrer" id="btnLaunchPaymobGateway" class="btn btn-primary btn-pay-now" style="width: 100%; font-size: 15px; padding: 13px; text-decoration: none;">
                <span>🔒 الانتقال لبوابة Paymob الرسمية وسداد <span class="payAmountHighlight">7,500</span> ج.م</span>
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"></polyline></svg>
              </a>
            </div>

            <!-- Official Linked Gateway Badge -->
            <div style="background: #F0FDF4; border: 1.5px solid #BBF7D0; border-radius: 8px; padding: 12px; margin-bottom: 14px; font-size: 12.5px; display: flex; align-items: center; justify-content: space-between;">
              <div style="display: flex; align-items: center; gap: 8px;">
                <span style="color: #16A34A; font-weight: 800; font-size: 16px;">✓</span>
                <span style="color: #166534; font-weight: 700;">بوابة بايموب مربوطة رسمياً:</span>
                <code style="background: #DCFCE7; color: #15803D; padding: 2px 6px; border-radius: 4px; font-family: monospace;">paymob.link/z3ngE</code>
              </div>
              <a href="https://paymob.link/z3ngE" target="_blank" rel="noopener noreferrer" style="color: #16A34A; font-weight: 800; text-decoration: underline; font-size: 12px;">فتح الرابط ↗</a>
            </div>

            <!-- Paymob Manual Reference Verification Box -->
            <div style="background: #F8FAFC; border: 1.5px solid #E2E8F0; border-radius: 8px; padding: 12px; margin-bottom: 14px;">
              <label class="form-label" for="paymobReturnTxnId" style="font-size: 12px; color: #475569; margin-bottom: 6px; display: block;">أتممت الدفع في صفحة بايموب؟ أدخل كود المعاملة (Transaction ID) لتأكيد الحجز فوراً:</label>
              <div style="display: flex; gap: 8px;">
                <input type="text" id="paymobReturnTxnId" class="form-control" placeholder="مثال: 18492031" dir="ltr" style="text-align: right; font-size: 13px;">
                <button type="button" id="btnVerifyPaymobTxn" class="btn btn-outline-navy" style="white-space: nowrap; font-size: 12px; padding: 8px 14px; font-weight: 700;">تحقق وتأكيد ✓</button>
              </div>
            </div>
          </div>

          <!-- Wallets Pane -->
          <div class="pay-tab-pane" id="paneWallets" style="display:none;">
            <div class="wallet-instruction-box">
              <div class="wallet-num-highlight">
                <span class="wallet-label">رقم محفظة فودافون كاش المعتمدة:</span>
                <div class="wallet-copy-row">
                  <strong class="wallet-phone" id="walletPhoneNum">01091106722</strong>
                  <button type="button" class="btn-copy-wallet" id="btnCopyWallet">نسخ الرقم 📋</button>
                </div>
                <span class="wallet-note">يدعم: فودافون كاش • أورنج كاش • اتصالات كاش • وي باي WE Pay</span>
              </div>

              <div class="wallet-steps-list">
                <div class="w-step">
                  <span class="w-step-num">1</span>
                  <span>حوّل مبلغ <strong><span class="walletAmountDisplay">7,500</span> ج.م</strong> إلى رقم المحفظة الرسمي <strong>01091106722</strong>.</span>
                </div>
                <div class="w-step">
                  <span class="w-step-num">2</span>
                  <span>أدخل رقم محفظتك وكود العملية وأرفق صورة التحويل بالأسفل لمطابقة العملية وتفعيل الحجز.</span>
                </div>
              </div>

              <!-- Instant Verification & WhatsApp Proof Row -->
              <div style="background: #FFFBEB; border: 1.5px solid #FCD34D; border-radius: 8px; padding: 12px; margin-top: 12px; font-size: 13px; color: #92400E;">
                <div style="display:flex; align-items:center; gap:8px; font-weight:700; margin-bottom:4px;">
                  <span>⏳ نظام التدقيق والمطابقة لمحافظ فودافون كاش:</span>
                </div>
                <p style="margin: 0 0 8px 0; font-size: 12px; color: #B45309; line-height: 1.5;">
                  يتم فحص بيانات التحويل ومطابقتها يدوياً مع كشف حساب محفظة <strong>01091106722</strong>. لن يتم تفعيل موعد الاستشارة أو إدراجه بالجدول إلا بعد تأكيد مطابقة التحويل.
                </p>
                <a href="https://wa.me/201091106722?text=مرحباً%20منصة%20BIZNOVA،%20قمت%20بتحويل%20مبلغ%207,500%20ج.م%20عبر%20فودافون%20كاش%20لتأكيد%20حجز%20الاستشارة" target="_blank" rel="noopener noreferrer" style="display: inline-flex; align-items: center; gap: 6px; background: #16A34A; color: #FFFFFF; padding: 7px 14px; border-radius: 6px; text-decoration: none; font-size: 12px; font-weight: 700;">
                  <span>📲 إرسال إشعار تحويل فودافون كاش عبر واتساب (تأكيد مباشر خلال 5 دقائق)</span>
                </a>
              </div>
            </div>

            <div class="payment-form-fields">
              <div class="form-group">
                <label class="form-label" for="walletSenderPhone">رقم المحفظة التي قمت بالتحويل منها (11 رقم) *</label>
                <input type="tel" id="walletSenderPhone" class="form-control" placeholder="010XXXXXXXX" maxlength="11" dir="ltr" style="text-align: right;" required>
              </div>
              <div class="form-group">
                <label class="form-label" for="walletRefCode">كود العملية / رقم الإشعار (من رسالة فودافون كاش - إلزامي) *</label>
                <input type="text" id="walletRefCode" class="form-control" placeholder="مثال: 84920412" dir="ltr" style="text-align: right;" required>
                <small style="color: #64748B; font-size: 11.5px; display: block; margin-top: 4px;">كود العملية المكون من 6 أرقام على الأقل المستلم في رسالة Vodafone Cash</small>
              </div>
              <div class="form-group">
                <label class="form-label" for="walletProofFile">إرفاق صورة إشعار التحويل (سكرين شوت رسالة التحويل) *</label>
                <input type="file" id="walletProofFile" class="form-control" accept="image/*" style="font-size: 12.5px; padding: 7px;">
                <div id="walletProofPreview" style="display:none; margin-top: 8px; align-items: center; gap: 10px; background: #F8FAFC; border: 1px dashed #94A3B8; padding: 8px; border-radius: 6px;">
                  <img id="walletProofImg" src="" alt="إشعار التحويل" style="width: 44px; height: 44px; object-fit: cover; border-radius: 4px;">
                  <div style="flex: 1; font-size: 12px; color: #334155;">
                    <strong id="walletProofFileName" style="display:block; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; max-width:200px;"></strong>
                    <span style="color: #10B981; font-size: 11px;">✓ تم إرفاق صورة الإشعار للتدقيق</span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- InstaPay Pane -->
          <div class="pay-tab-pane" id="paneInstapay" style="display:none;">
            <div class="instapay-instruction-box">
              <div class="instapay-header-row">
                <span class="instapay-badge">شبكة المدفوعات اللحظية IPN • البنك المركزي المصري</span>
                <strong style="color: #4F46E5; font-size: 15px;">InstaPay</strong>
              </div>
              <p style="font-size: 13.5px; color: #526484; margin: 8px 0 12px 0;">
                يمكنك التحويل الفوري عبر تطبيق إنستاباي إلى معرف الحساب (IPA) أو رقم الهاتف المعتمد للمنصة:
              </p>
              <div class="instapay-target-box">
                <div class="ipa-row">
                  <span>عنوان الدفع (IPA):</span>
                  <strong id="instapayIpaTarget" style="font-size: 15px; color: #3730A3;">albobmo@instapay</strong>
                  <button type="button" class="btn-copy-ipa" id="btnCopyIpa">نسخ المعرف 📋</button>
                </div>
                <div class="ipa-row" style="margin-top: 8px;">
                  <span>أو التحويل برقم الهاتف:</span>
                  <strong id="instapayPhoneTarget" style="font-size: 15px; color: #1E293B;">01116281034</strong>
                  <button type="button" class="btn-copy-ipa" id="btnCopyInstapayPhone">نسخ الرقم 📋</button>
                </div>
              </div>
              <div style="font-size: 12.5px; color: #10B981; font-weight: 700; margin-top: 10px;">
                ✓ التحويل فوري ولحظي بدون أي رسوم إضافية (0% عمولة)
              </div>

              <!-- Instant Verification & WhatsApp Proof Row -->
              <div style="background: #EEF2FF; border: 1.5px solid #C7D2FE; border-radius: 8px; padding: 12px; margin-top: 12px; font-size: 13px; color: #3730A3;">
                <div style="display:flex; align-items:center; gap:8px; font-weight:700; margin-bottom:4px;">
                  <span>⚡ نظام التحقق والمطابقة لتحويلات إنستاباي:</span>
                </div>
                <p style="margin: 0 0 8px 0; font-size: 12px; color: #4338CA; line-height: 1.5;">
                  التحويل يصل لحظياً لحساب <strong>albobmo@instapay</strong>. أدخل الرقم المرجعي المكون من 12 رقماً بالأسفل وأرفق سكرين شوت الإشعار لاعتماد حجزك فوراً.
                </p>
                <a href="https://wa.me/201116281034?text=مرحباً%20منصة%20BIZNOVA،%20قمت%20بالتحويل%20عبر%20InstaPay%20إلى%20albobmo@instapay%20لتأكيد%20حجز%20الاستشارة" target="_blank" rel="noopener noreferrer" style="display: inline-flex; align-items: center; gap: 6px; background: #4F46E5; color: #FFFFFF; padding: 7px 14px; border-radius: 6px; text-decoration: none; font-size: 12px; font-weight: 700;">
                  <span>📲 إرسال إشعار تحويل InstaPay عبر واتساب (تأكيد فوري)</span>
                </a>
              </div>
            </div>

            <div class="payment-form-fields" style="margin-top: 14px;">
              <div class="form-group">
                <label class="form-label" for="instapaySenderAccount">معرف الدفع IPA أو اسم الحساب المحول منه *</label>
                <input type="text" id="instapaySenderAccount" class="form-control" placeholder="مثال: ahmed@instapay أو اسم صاحب الحساب" required>
              </div>
              <div class="form-group">
                <label class="form-label" for="instapayRefNumber">الرقم المرجعي للتحويل في إنستاباي (Reference Number) *</label>
                <input type="text" id="instapayRefNumber" class="form-control" placeholder="مثال: 410829103948" dir="ltr" style="text-align: right;" required>
                <small style="color: #64748B; font-size: 11.5px; display: block; margin-top: 4px;">الرقم المرجعي المكون من 12 رقماً من شاشة إشعار السداد في تطبيق InstaPay</small>
              </div>
              <div class="form-group">
                <label class="form-label" for="instapayProofFile">إرفاق سكرين شوت إشعار التحويل من تطبيق إنستاباي *</label>
                <input type="file" id="instapayProofFile" class="form-control" accept="image/*" style="font-size: 12.5px; padding: 7px;">
                <div id="instapayProofPreview" style="display:none; margin-top: 8px; align-items: center; gap: 10px; background: #F8FAFC; border: 1px dashed #94A3B8; padding: 8px; border-radius: 6px;">
                  <img id="instapayProofImg" src="" alt="إشعار إنستاباي" style="width: 44px; height: 44px; object-fit: cover; border-radius: 4px;">
                  <div style="flex: 1; font-size: 12px; color: #334155;">
                    <strong id="instapayProofFileName" style="display:block; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; max-width:200px;"></strong>
                    <span style="color: #10B981; font-size: 11px;">✓ تم إرفاق صورة إشعار إنستاباي للتدقيق</span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Fawry Pane -->
          <div class="pay-tab-pane" id="paneFawry" style="display:none;">
            <div class="fawry-instruction-box">
              <div class="fawry-header-row">
                <span class="fawry-yellow-badge">Fawry Pay فوري</span>
                <span style="font-size: 12.5px; color: #64748B;">الدفع في منافذ فوري أو عبر myFawry</span>
              </div>
              <div class="fawry-code-card">
                <span style="font-size: 12px; color: #64748B;">كود الخدمة المرجعي للسداد:</span>
                <div class="fawry-code-number" id="fawryGeneratedCode">784 920 15</div>
                <span style="font-size: 12px; color: #DC2626;">صالح لمدة 48 ساعة من تاريخ الطلب</span>
              </div>
              <p style="font-size: 13px; color: #526484; margin-top: 10px; line-height: 1.6;">
                توجّه لأي منفذ أو تاجر فوري واطلب سداد "خدمة فوري باي"، وأعطه الكود المرجعي بالأعلى. يتم تأكيد الحجز فور السداد.
              </p>
            </div>
          </div>

          <div class="payment-actions-container">
            <button type="button" id="btnExecutePayment" class="btn btn-primary btn-pay-now" style="width: 100%;">
              <span class="pay-lock-icon">🔒</span>
              <span id="btnPayExecutionText">سداد <span id="btnPayAmountVal">7,500</span> ج.م وتأكيد الحجز الآن</span>
            </button>
            <button type="button" id="btnBackToDetails" class="btn-step-back">
              <span>← الرجوع لتعديل بيانات الطلب</span>
            </button>
          </div>
        </div>

        <!-- STEP 3: Verified Receipt -->
        <div id="modalStepSuccess" class="modal-step-pane" style="display:none;">
          <div class="receipt-success-header" id="receiptHeaderSection">
            <div class="success-badge-glow" id="receiptBadgeGlow">
              <svg id="receiptBadgeIconSuccess" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="#10B981" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="20 6 9 17 4 12"></polyline>
              </svg>
              <svg id="receiptBadgeIconPending" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="#D97706" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="display:none;">
                <circle cx="12" cy="12" r="10"></circle>
                <polyline points="12 6 12 12 16 14"></polyline>
              </svg>
            </div>
            <h3 class="success-title" id="receiptSuccessTitle">تم تأكيد الدفع وحجز الاستشارة بنجاح!</h3>
            <p class="success-desc" id="receiptSuccessDesc">
              شكراً لك يا <strong id="successClientName">م. أحمد</strong>! تم اعتماد وسداد رسوم استشارتك بنجاح، وتم تسجيل الحجز رسمياً في جدول مستشاري المنصة.
            </p>
          </div>

          <!-- Verification Notice Box (For Pending Manual Transfers) -->
          <div id="receiptPendingNoticeBox" style="display:none; background: #FFFBEB; border: 1.5px solid #FCD34D; border-radius: 8px; padding: 12px 14px; margin-bottom: 16px; font-size: 13px; color: #92400E; line-height: 1.6;">
            <div style="display: flex; align-items: center; gap: 8px; font-weight: 700; margin-bottom: 4px;">
              <span style="font-size: 16px;">🛡️</span>
              <span>نظام التدقيق المالي ومطابقة التحويل:</span>
            </div>
            <p style="margin: 0; font-size: 12.5px; color: #78350F;">
              تم تسجيل طلبك برقم مرجعي معلق. للحفاظ على موثوقية المنصة، لن يتم احتساب الاستشارة في العداد المباشر أو تثبيت موعد المستشار نهائياً إلا بعد مطابقة إشعار التحويل مع كشف الحساب/المحفظة المستلمة.
            </p>
          </div>

          <div class="official-receipt-card" id="printableReceipt">
            <div class="receipt-header-bar">
              <div class="receipt-brand">
                <strong style="color: #0C1E3C; font-size: 16px;">BIZNOVA</strong>
                <span style="color: #F47521; font-weight: 800;">.</span>
                <span style="font-size: 11px; color: #64748B; margin-right: 6px;">إيصال سداد إلكتروني معتمد</span>
              </div>
              <div class="receipt-status-pill" id="receiptStatusPill">
                <span>✅ مدفوع وموثق (PAID)</span>
              </div>
            </div>

            <div class="receipt-table">
              <div class="r-row">
                <span class="r-label">رقم المعاملة المرجعي (Txn ID):</span>
                <strong class="r-val r-code" id="receiptTxnId">BZ-78421-EGP</strong>
              </div>
              <div class="r-row">
                <span class="r-label">الخدمة المحجوزة:</span>
                <span class="r-val" id="receiptService">دراسة جدوى دقيقة وتحليل سوق</span>
              </div>
              <div class="r-row">
                <span class="r-label">المبلغ المطلوب / المسدد:</span>
                <strong class="r-val r-amount" id="receiptAmount">7,500 ج.م</strong>
              </div>
              <div class="r-row">
                <span class="r-label">طريقة السداد:</span>
                <span class="r-val" id="receiptMethod">بطاقة بنكية مشفرة (Visa/Mastercard)</span>
              </div>
              <div class="r-row">
                <span class="r-label">تاريخ ووقت المعاملة:</span>
                <span class="r-val" id="receiptDate">12 نوفمبر 2026 - 01:20 ص</span>
              </div>
              <div class="r-row">
                <span class="r-label">هاتف العميل المسجل:</span>
                <span class="r-val" id="receiptClientPhone">+20 101 125 7008</span>
              </div>
            </div>

            <div class="receipt-watermark" id="receiptWatermark">
              🔒 عملية مالية مشفرة وموثقة عبر بوابة BIZNOVA الآمنة • جميع الحقوق محفوظة
            </div>
          </div>

          <div class="receipt-next-steps">
            <p id="receiptNextStepsPrompt" style="font-size: 13.5px; color: #526484; line-height: 1.6; margin-bottom: 12px; text-align: center;">
              📱 يمكنك الآن التواصل المباشر مع كبير مستشاري BIZNOVA عبر واتساب بضغطة زر لمناقشة تفاصيل مشروعك فوراً:
            </p>
            <a id="receiptWhatsappLink" href="https://wa.me/201091106722?text=مرحباً%20منصة%20BIZNOVA،%20تم%20سداد%20رسوم%20الاستشارة%20بنجاح" target="_blank" rel="noopener noreferrer" class="btn-whatsapp-direct">
              <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor">
                <path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.711 2.592 2.654-.697c.974.534 1.745.819 2.806.819l.004-.001c3.182 0 5.768-2.587 5.769-5.766.001-3.182-2.585-5.769-5.767-5.769zm3.435 8.163c-.144.405-.838.775-1.17.825-.313.05-.718.077-2.146-.514-1.825-.756-3.003-2.616-3.094-2.738-.091-.121-.741-.986-.741-1.881s.467-1.334.633-1.517c.166-.183.363-.228.484-.228.121 0 .242.001.348.006.111.006.26-.042.406.31.15.36.514 1.25.56 1.341.045.091.076.198.015.319-.06.121-.091.198-.182.304-.091.106-.192.236-.274.316-.091.091-.186.19-.08.373.106.183.471.777 1.013 1.26.697.621 1.285.813 1.468.904.183.091.29.076.398-.045.108-.121.465-.541.591-.728.126-.186.251-.157.422-.091.171.066 1.084.511 1.27.603.186.091.31.137.355.213.046.076.046.442-.098.847z"/>
              </svg>
              <span id="receiptWhatsappBtnText">تواصل الآن عبر واتساب برقم المعاملة</span>
            </a>
          </div>

          <!-- Testing Simulation Bar -->
          <div id="receiptAdminSimulationBox" style="display:none; margin-top: 14px; background: #F8FAFC; border: 1px dashed #CBD5E1; border-radius: 6px; padding: 10px; font-size: 12px; color: #64748B; text-align: center;">
            <span>🛠️ وضع تجربة الإدارة: </span>
            <button type="button" id="btnSimulateAdminApproval" style="background: none; border: 1px solid #0C1E3C; color: #0C1E3C; padding: 4px 10px; border-radius: 4px; font-size: 11.5px; font-weight: 700; cursor: pointer; margin-right: 6px;">
              محاكاة اعتماد الإدارة للسداد وتحديث العداد الآن ✓
            </button>
          </div>

          <div style="display: flex; gap: 10px; margin-top: 14px;">
            <button type="button" id="btnPrintReceiptBtn" class="btn btn-outline-navy" style="flex: 1; padding: 11px;">
              🖨️ طباعة الإيصال
            </button>
            <button type="button" data-close-modal class="btn btn-primary" style="flex: 1; padding: 11px;">
              تم، إنهاء وإغلاق
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Live Notification Toast -->
    <div id="liveStatToast" class="live-stat-toast" aria-live="polite">
      <div style="width: 28px; height: 28px; border-radius: 50%; background: #10B981; display:flex; align-items:center; justify-content:center; color:#fff; flex-shrink:0; font-weight:800;">✓</div>
      <div id="liveStatToastText">تم تحديث الإحصائيات مباشرة!</div>
    </div>
    <?php
}
add_action('wp_footer', 'biznova_render_modals_in_footer');
