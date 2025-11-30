<?php
/**
 * Plugin Name: DLTPays - Instant RLUSD Commissions
 * Plugin URI: https://dltpays.com
 * Description: Instant affiliate commission payouts via RLUSD/XRP. Zero fees. 5-level MLM.
 * Version: 1.0.0
 * Author: DLTPays
 * License: GPL v2 or later
 * Text Domain: dltpays
 */
if (!defined('ABSPATH')) exit;

define('DLTPAYS_VERSION', '1.0.0');
define('DLTPAYS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('DLTPAYS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('DLTPAYS_API_URL', 'https://api.dltpays.com/api/v1');

class DLTPays {
    private static $instance = null;
    
    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function __construct() {
        $this->init_hooks();
    }
    
    private function init_hooks() {
        // Activation
        register_activation_hook(__FILE__, [$this, 'activate']);
        
        // Webhook for payment status updates
        add_action('rest_api_init', [$this, 'register_webhook']);
        
        // Admin
        add_action('admin_menu', [$this, 'admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        
        // WooCommerce hooks
        add_action('woocommerce_order_status_completed', [$this, 'process_commission'], 10, 1);
        add_action('woocommerce_thankyou', [$this, 'track_referral'], 10, 1);
        add_action('woocommerce_checkout_create_order', [$this, 'save_referral_on_create'], 10, 2);
        add_action('woocommerce_checkout_order_created', [$this, 'save_referral_from_session'], 10, 1);
        
        // Shortcodes
        add_shortcode('dltpays_affiliate_signup', [$this, 'affiliate_signup_shortcode']);
        add_shortcode('dltpays_affiliate_dashboard', [$this, 'affiliate_dashboard_shortcode']);
        
        // AJAX
        add_action('wp_ajax_dltpays_register_affiliate', [$this, 'ajax_register_affiliate']);
        add_action('wp_ajax_nopriv_dltpays_register_affiliate', [$this, 'ajax_register_affiliate']);
        
        // Track referral cookie
        add_action('init', [$this, 'track_referral_cookie']);
        
        // Enqueue scripts
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
        
        // Admin AJAX handlers (secure proxy for API calls)
        add_action('wp_ajax_dltpays_check_connection', [$this, 'ajax_check_connection']);
        add_action('wp_ajax_dltpays_connect_xaman', [$this, 'ajax_connect_xaman']);
        add_action('wp_ajax_dltpays_poll_xaman', [$this, 'ajax_poll_xaman']);
        add_action('wp_ajax_dltpays_disconnect_xaman', array($this, 'ajax_disconnect_xaman'));
        add_action('wp_ajax_dltpays_delete_store', [$this, 'ajax_delete_store']);
        add_action('wp_ajax_dltpays_validate_promo_code', [$this, 'ajax_validate_promo_code']);
        add_action('admin_notices', [$this, 'show_beta_notice']);


        // --- NEW AUTO-SIGN AJAX HANDLERS ---
        add_action('wp_ajax_dltpays_get_autosign_settings', [$this, 'ajax_get_autosign_settings']);
        add_action('wp_ajax_dltpays_update_autosign_settings', [$this, 'ajax_update_autosign_settings']);
        add_action('wp_ajax_dltpays_setup_autosign', [$this, 'ajax_setup_autosign']);
        add_action('wp_ajax_dltpays_verify_autosign', [$this, 'ajax_verify_autosign']);
        add_action('wp_ajax_dltpays_revoke_autosign', [$this, 'ajax_revoke_autosign']);
        add_action('wp_ajax_dltpays_save_crossmark_wallet', [$this, 'ajax_save_crossmark_wallet']);
        // ------------------------------------
    }
    
    /**
     * Show beta notice in admin
     */
    public function show_beta_notice() {
        if (!get_option('dltpays_store_id')) {
            echo '<div class="notice notice-info"><p><strong>DLTPays Beta:</strong> Limited to first 5 stores. <a href="https://dltpays.com">Join waitlist</a> if registration fails.</p></div>';
        }
    }
    /**
     * AJAX: Check API connection (proxied - api_secret never exposed to browser)
     */
    public function ajax_check_connection() {
        check_ajax_referer('dltpays_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        
        $api_secret = get_option('dltpays_api_secret');
        if (!$api_secret) {
            wp_send_json_error('Not configured');
        }
        
        $response = wp_remote_get(DLTPAYS_API_URL . '/store/stats', [
            'headers' => [
                'Authorization' => 'Bearer ' . $api_secret
            ],
            'timeout' => 15
        ]);
        
        if (is_wp_error($response)) {
            wp_send_json_error($response->get_error_message());
        }
        
        $code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if ($code !== 200) {
            wp_send_json_error($body['error'] ?? 'Connection failed');
        }
        
       wp_send_json_success([
            'store_id' => $body['store_id'] ?? '',
            'store_name' => $body['store_name'] ?? '',
            'status' => $body['status'] ?? '',
            'xaman_connected' => $body['xaman_connected'] ?? false,
            'push_enabled' => $body['push_enabled'] ?? false,
            'store_referral_code' => $body['store_referral_code'] ?? null,
            'chainb_earned' => $body['chainb_earned'] ?? 0,
            'wallet_address' => $body['wallet_address'] ?? null,
            'payout_mode' => $body['payout_mode'] ?? 'manual',
            'affiliates_count' => $body['affiliates_count'] ?? 0,
            'total_paid' => $body['total_paid'] ?? 0
        ]);
    }
    
    public function ajax_disconnect_xaman() {
    check_ajax_referer('dltpays_admin_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized');
    }
    
    $api_secret = get_option('dltpays_api_secret');
    if (!$api_secret) {
        wp_send_json_error('Not configured');
    }
    
    $response = wp_remote_post(DLTPAYS_API_URL . '/xaman/disconnect', [
        'headers' => [
            'Authorization' => 'Bearer ' . $api_secret,
            'Content-Type' => 'application/json'
        ],
        'timeout' => 15
    ]);
    
    if (is_wp_error($response)) {
        wp_send_json_error($response->get_error_message());
    }
    
    $body = json_decode(wp_remote_retrieve_body($response), true);
    wp_send_json_success($body);
}

    // ----------------------------------------
    // NEW AUTO-SIGN AJAX HANDLERS START HERE
    // ----------------------------------------

    public function ajax_get_autosign_settings() {
        check_ajax_referer('dltpays_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        
        $api_secret = get_option('dltpays_api_secret');
        if (!$api_secret) {
            wp_send_json_error('Not configured');
        }
        
        $response = wp_remote_get(DLTPAYS_API_URL . '/store/autosign-settings', [
            'headers' => ['Authorization' => 'Bearer ' . $api_secret],
            'timeout' => 15
        ]);
        
        if (is_wp_error($response)) {
            wp_send_json_error($response->get_error_message());
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        // Add platform signer address
        $body['platform_signer_address'] = 'rQsRwh841n8DDwx4Bs2KZ4fHPKSt7VeULH';
        
        wp_send_json_success($body);
    }

    public function ajax_update_autosign_settings() {
        check_ajax_referer('dltpays_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        
        $api_secret = get_option('dltpays_api_secret');
        if (!$api_secret) {
            wp_send_json_error('Not configured');
        }
        
        $payload = [];
        
        if (isset($_POST['auto_sign_terms_accepted'])) {
            $payload['auto_sign_terms_accepted'] = true;
        }
        if (isset($_POST['auto_sign_max_single_payout'])) {
            $payload['auto_sign_max_single_payout'] = floatval($_POST['auto_sign_max_single_payout']);
        }
        if (isset($_POST['auto_sign_daily_limit'])) {
            $payload['auto_sign_daily_limit'] = floatval($_POST['auto_sign_daily_limit']);
        }
        
        $response = wp_remote_post(DLTPAYS_API_URL . '/store/autosign-settings', [
            'headers' => [
                'Authorization' => 'Bearer ' . $api_secret,
                'Content-Type' => 'application/json'
            ],
            'body' => json_encode($payload),
            'timeout' => 15
        ]);
        
        if (is_wp_error($response)) {
            wp_send_json_error($response->get_error_message());
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        $code = wp_remote_retrieve_response_code($response);
        
        if ($code === 200) {
            wp_send_json_success($body);
        } else {
            wp_send_json_error($body['error'] ?? 'Failed to update settings');
        }
    }

    public function ajax_setup_autosign() {
        check_ajax_referer('dltpays_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        
        $store_id = get_option('dltpays_store_id');
        if (!$store_id) {
            wp_send_json_error('Store not configured');
        }
        
        $response = wp_remote_post(DLTPAYS_API_URL . '/xaman/setup-autosign', [
            'headers' => ['Content-Type' => 'application/json'],
            'body' => json_encode(['store_id' => $store_id]),
            'timeout' => 30
        ]);
        
        if (is_wp_error($response)) {
            wp_send_json_error($response->get_error_message());
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (!empty($body['already_enabled'])) {
            wp_send_json_success(['already_enabled' => true]);
        }
        
        // Return Crossmark setup URL (even though the backend API call is for Xaman/autosing setup)
        wp_send_json_success([
            'crossmark_url' => 'https://api.dltpays.com/test-crossmark.html?store_id=' . urlencode($store_id),
            'platform_signer_address' => $body['platform_signer_address'] ?? 'rQsRwh841n8DDwx4Bs2KZ4fHPKSt7VeULH',
            'instructions' => $body['instructions'] ?? []
        ]);
    }

    public function ajax_verify_autosign() {
        check_ajax_referer('dltpays_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        
        $store_id = get_option('dltpays_store_id');
        if (!$store_id) {
            wp_send_json_error('Store not configured');
        }
        
        $response = wp_remote_get(DLTPAYS_API_URL . '/xaman/verify-autosign?store_id=' . urlencode($store_id), [
            'timeout' => 15
        ]);
        
        if (is_wp_error($response)) {
            wp_send_json_error($response->get_error_message());
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        wp_send_json_success($body);
    }

    public function ajax_revoke_autosign() {
        check_ajax_referer('dltpays_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        
        $store_id = get_option('dltpays_store_id');
        if (!$store_id) {
            wp_send_json_error('Store not configured');
        }
        
        $response = wp_remote_post(DLTPAYS_API_URL . '/xaman/revoke-autosign', [
            'headers' => ['Content-Type' => 'application/json'],
            'body' => json_encode(['store_id' => $store_id]),
            'timeout' => 15
        ]);
        
        if (is_wp_error($response)) {
            wp_send_json_error($response->get_error_message());
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        wp_send_json_success($body);
    }

    // ----------------------------------------
    // END OF NEW AUTO-SIGN AJAX HANDLERS
    // ----------------------------------------
    
    /**
     * AJAX: Connect Xaman wallet
     */
    public function ajax_connect_xaman() {
        check_ajax_referer('dltpays_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        
        $store_id = get_option('dltpays_store_id');
        
        if (!$store_id) {
            wp_send_json_error('Store not configured');
        }
        
        $response = wp_remote_post(DLTPAYS_API_URL . '/xaman/connect', [
            'headers' => [
                'Content-Type' => 'application/json'
            ],
            'body' => json_encode(['store_id' => $store_id]),
            'timeout' => 30
        ]);
        
        if (is_wp_error($response)) {
            wp_send_json_error($response->get_error_message());
        }
        
        $code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if ($code === 200 && !empty($body['connection_id'])) {
            wp_send_json_success([
                'connection_id' => $body['connection_id'],
                'qr_png' => $body['qr_png'],
                'deep_link' => $body['deep_link']
            ]);
        } else {
            wp_send_json_error($body['error'] ?? 'Connection failed');
        }
    }

    // ----------------------------------------
    // END OF NEW AUTO-SIGN AJAX HANDLERS
    // ----------------------------------------

    /**
     * AJAX: Save Crossmark wallet address
     */
    public function ajax_save_crossmark_wallet() {
        check_ajax_referer('dltpays_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        
        $wallet_address = sanitize_text_field($_POST['wallet_address']);
        if (empty($wallet_address) || !preg_match('/^r[a-zA-Z0-9]{24,34}$/', $wallet_address)) {
            wp_send_json_error('Invalid wallet address');
        }
        
        $api_secret = get_option('dltpays_api_secret');
        
        $response = wp_remote_post(DLTPAYS_API_URL . '/store/save-wallet', [
            'headers' => [
                'Authorization' => 'Bearer ' . $api_secret,
                'Content-Type' => 'application/json'
            ],
            'body' => json_encode(['wallet_address' => $wallet_address]),
            'timeout' => 15
        ]);
        
        if (is_wp_error($response)) {
            wp_send_json_error($response->get_error_message());
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (!empty($body['success'])) {
    // Update local payout mode to auto for Crossmark
    update_option('dltpays_payout_mode', 'auto');
    wp_send_json_success($body);
} else {
            wp_send_json_error($body['error'] ?? 'Failed to save wallet');
        }
    }
    
    /**
     * AJAX: Poll Xaman connection status
     */
    public function ajax_poll_xaman() {
        check_ajax_referer('dltpays_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        
        $connection_id = sanitize_text_field($_POST['connection_id'] ?? '');
        $store_id = get_option('dltpays_store_id');
        
        if (!$connection_id || !$store_id) {
            wp_send_json_error('Missing parameters');
        }
        
        $response = wp_remote_get(DLTPAYS_API_URL . '/xaman/poll/' . urlencode($connection_id) . '?store_id=' . urlencode($store_id), [
            'timeout' => 15
        ]);
        
        if (is_wp_error($response)) {
            wp_send_json_error($response->get_error_message());
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        wp_send_json_success([
            'status' => $body['status'] ?? 'pending',
            'wallet_address' => $body['wallet_address'] ?? null
        ]);
    }

    /**
 * AJAX: Permanently delete store
 */
public function ajax_delete_store() {
    check_ajax_referer('dltpays_admin_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized');
    }
    
    $api_secret = get_option('dltpays_api_secret');
    $confirm = sanitize_text_field($_POST['confirm'] ?? '');
    
    if (!$api_secret) {
        wp_send_json_error('Not configured');
    }
    
    $response = wp_remote_request(DLTPAYS_API_URL . '/store', [
        'method' => 'DELETE',
        'headers' => [
            'Authorization' => 'Bearer ' . $api_secret,
            'Content-Type' => 'application/json'
        ],
        'body' => json_encode(['confirm' => $confirm]),
        'timeout' => 30
    ]);
    
    if (is_wp_error($response)) {
        wp_send_json_error($response->get_error_message());
    }
    
    $code = wp_remote_retrieve_response_code($response);
    
    if ($code === 200) {
        delete_option('dltpays_store_id');
        delete_option('dltpays_api_secret');
        delete_option('dltpays_api_key');
        wp_send_json_success();
    } else {
        $body = json_decode(wp_remote_retrieve_body($response), true);
        wp_send_json_error($body['error'] ?? 'Deletion failed');
    }
}
    /**
     * AJAX: Validate promotional code
     */
    public function ajax_validate_promo_code() {
        check_ajax_referer('dltpays_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        
        $code = strtoupper(sanitize_text_field($_POST['code'] ?? ''));
        
        if (empty($code)) {
            wp_send_json_error('Please enter a code');
        }
        
        // Validate code against API
        $response = wp_remote_get(DLTPAYS_API_URL . '/store/lookup-referral/' . $code, [
            'timeout' => 15
        ]);
        
        if (is_wp_error($response)) {
            wp_send_json_error('Connection failed');
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if ($status_code === 200 && !empty($body['store_id'])) {
            // Save the referral code
            update_option('dltpays_referral_code', $code);
            
            wp_send_json_success([
                'store_name' => $body['store_name'] ?? 'Partner Store',
                'code' => $code
            ]);
        } else {
            wp_send_json_error('Invalid promotional code');
        }
    }

    public function activate() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        
        // Affiliates table
        $table_affiliates = $wpdb->prefix . 'dltpays_affiliates';
        $sql_affiliates = "CREATE TABLE IF NOT EXISTS $table_affiliates (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) DEFAULT NULL,
            wallet_address varchar(50) NOT NULL,
            wallet_currency varchar(10) DEFAULT 'RLUSD',
            referral_code varchar(20) NOT NULL,
            referred_by bigint(20) DEFAULT NULL,
            upline_chain text,
            commission_rate decimal(5,4) DEFAULT 0.2500,
            total_earned decimal(15,4) DEFAULT 0,
            status varchar(20) DEFAULT 'active',
            api_affiliate_id varchar(50) DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY wallet_address (wallet_address),
            UNIQUE KEY referral_code (referral_code),
            KEY referred_by (referred_by),
            KEY status (status)
        ) $charset_collate;";
        
        // Commissions table
        $table_commissions = $wpdb->prefix . 'dltpays_commissions';
        $sql_commissions = "CREATE TABLE IF NOT EXISTS $table_commissions (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            order_id bigint(20) NOT NULL,
            affiliate_id bigint(20) NOT NULL,
            level tinyint(1) DEFAULT 1,
            amount decimal(15,4) NOT NULL,
            currency varchar(10) DEFAULT 'RLUSD',
            status varchar(20) DEFAULT 'pending',
            payout_id varchar(50) DEFAULT NULL,
            tx_hash varchar(100) DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            paid_at datetime DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY order_affiliate_level (order_id, affiliate_id, level),
            KEY order_id (order_id),
            KEY affiliate_id (affiliate_id),
            KEY status (status)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_affiliates);
        dbDelta($sql_commissions);
        
        // Default options
        add_option('dltpays_commission_rates', json_encode([25, 5, 3, 2, 1]));
        add_option('dltpays_cookie_days', 30);
        
        // Auto-register with DLTPays API if not already registered
        $this->auto_register_store();
    }
    
    /**
     * Auto-register store with DLTPays API
     */
    private function auto_register_store() {
        // Check if already registered
        if (get_option('dltpays_store_id')) {
            return;
        }
        
        // Get site info
        $store_name = get_bloginfo('name') ?: parse_url(home_url(), PHP_URL_HOST);
        $store_url = home_url();
        $admin_email = get_option('admin_email');
        
        // Call DLTPays API to register
        $body = [
            'store_name' => $store_name,
            'store_url' => $store_url,
            'email' => $admin_email
        ];

        // Check for referral code
        $referral_code = get_option('dltpays_referral_code', '');
        if (!empty($referral_code)) {
            $body['referred_by_store'] = $referral_code;
        }

        $response = wp_remote_post(DLTPAYS_API_URL . '/store/register', [
            'timeout' => 30,
            'headers' => [
                'Content-Type' => 'application/json'
            ],
            'body' => json_encode($body)
        ]);
        
        if (is_wp_error($response)) {
            error_log('DLTPays: Auto-registration failed - ' . $response->get_error_message());
            return;
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $result = json_decode(wp_remote_retrieve_body($response), true);
        
        if (($status_code === 201 || $status_code === 200) && !empty($result['store_id'])) {
            update_option('dltpays_store_id', sanitize_text_field($result['store_id']));
            update_option('dltpays_api_secret', sanitize_text_field($result['api_secret']));
            if (!empty($result['api_key'])) {
                update_option('dltpays_api_key', sanitize_text_field($result['api_key']));
            }
            $action = $status_code === 200 ? 'reconnected' : 'registered';
            error_log('DLTPays: Store ' . $action . ' successfully - ' . $result['store_id']);
        } elseif ($status_code === 409) {
            error_log('DLTPays: Store URL already registered. Manual credential entry required.');
        } else {
            error_log('DLTPays: Registration failed - ' . ($result['error'] ?? 'Unknown error'));
        }
    }
    
    public function admin_menu() {
        add_menu_page(
            'DLTPays',
            'DLTPays',
            'manage_options',
            'dltpays',
            [$this, 'admin_page'],
            'dashicons-money-alt',
            56
        );
        
        add_submenu_page(
            'dltpays',
            'Settings',
            'Settings',
            'manage_options',
            'dltpays-settings',
            [$this, 'settings_page']
        );
        
        add_submenu_page(
            'dltpays',
            'Affiliates',
            'Affiliates',
            'manage_options',
            'dltpays-affiliates',
            [$this, 'affiliates_page']
        );
    }
    
    public function register_settings() {
        register_setting('dltpays_settings', 'dltpays_store_id');
        register_setting('dltpays_settings', 'dltpays_api_secret', [
            'sanitize_callback' => [$this, 'preserve_api_secret']
        ]);
        register_setting('dltpays_settings', 'dltpays_commission_rates');
        register_setting('dltpays_settings', 'dltpays_cookie_days');
    }
    
    /**
     * Preserve api_secret if not provided in form (prevents wiping on settings save)
     */
    public function preserve_api_secret($new_value) {
        // If empty (not in form), keep existing value
        if (empty($new_value)) {
            return get_option('dltpays_api_secret');
        }
        return sanitize_text_field($new_value);
    }
    
    public function track_referral_cookie() {
    if (isset($_GET['ref']) && !empty($_GET['ref'])) {
        $ref_code = sanitize_text_field($_GET['ref']);
        $cookie_days = (int) get_option('dltpays_cookie_days', 30);
        setcookie('dltpays_ref', $ref_code, time() + ($cookie_days * DAY_IN_SECONDS), '/');
    }
}
    
    public function track_referral($order_id) {
        if (!isset($_COOKIE['dltpays_ref'])) return;
        
        $ref_code = sanitize_text_field($_COOKIE['dltpays_ref']);
        update_post_meta($order_id, '_dltpays_referral_code', $ref_code);
    }

    public function save_referral_from_session($order) {
        if (function_exists('WC') && WC()->session) {
            $ref_code = WC()->session->get('dltpays_ref');
                error_log('DLTPays: ref_code from session = ' . var_export($ref_code, true));
            if ($ref_code) {
                $order->update_meta_data('_dltpays_referral_code', $ref_code);
                $order->save();
            }
        }
    }
    
    /**
     * Main commission processing - triggered on order complete
     */
    public function process_commission($order_id) {
        error_log("DLTPays: process_commission called for order " . $order_id);
        global $wpdb;
        
        // Check if already processed
        if (get_post_meta($order_id, '_dltpays_processed', true)) {
            return;
        }
        
        $order = wc_get_order($order_id);
        if (!$order) return;
        
        $ref_code = $order->get_meta('_dltpays_referral_code');
        error_log('DLTPays: ref_code from order meta = ' . var_export($ref_code, true));

// Fallback to WC session if not on order
if (!$ref_code && function_exists('WC') && WC()->session) {
    $ref_code = WC()->session->get('dltpays_ref');
                error_log('DLTPays: ref_code from session = ' . var_export($ref_code, true));
    if ($ref_code) {
        $order->update_meta_data('_dltpays_referral_code', $ref_code);
        $order->save();
    }
}

if (!$ref_code) return;
        
        $order_total = (float) $order->get_total();
        
        // Send to DLTPays API - it calculates commissions
        $result = $this->send_to_dltpays($order_id, $order_total, $ref_code);
        
        if ($result['success']) {
            update_post_meta($order_id, '_dltpays_processed', true);
            update_post_meta($order_id, '_dltpays_payout_id', $result['payout_id']);
            
            // Store locally for dashboard display
            $table = $wpdb->prefix . 'dltpays_commissions';
            $wpdb->insert($table, [
                'order_id' => $order_id,
                'affiliate_id' => 0,
                'level' => 1,
                'amount' => 0,
                'currency' => 'RLUSD',
                'status' => 'queued',
                'payout_id' => $result['payout_id']
            ]);
        } else {
            update_post_meta($order_id, '_dltpays_error', $result['error']);
        }
    }
    
    /**
     * Send payout request to DLTPays API
     */
    private function send_to_dltpays($order_id, $order_total, $ref_code) {
        $api_secret = get_option('dltpays_api_secret');
        
        if (!$api_secret) {
            return ['success' => false, 'error' => 'DLTPays not configured'];
        }
        
        $payload = [
            'order_id' => 'wc_' . $order_id,
            'order_total' => $order_total,
            'referral_code' => $ref_code
        ];
        
        $response = wp_remote_post(DLTPAYS_API_URL . '/payout', [
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $api_secret
            ],
            'body' => json_encode($payload),
            'timeout' => 30
        ]);
        
        if (is_wp_error($response)) {
            return ['success' => false, 'error' => $response->get_error_message()];
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        $code = wp_remote_retrieve_response_code($response);
        
        if ($code === 201 || $code === 200) {
            return ['success' => true, 'payout_id' => $body['payout_id'] ?? null];
        }
        
        return ['success' => false, 'error' => $body['error'] ?? 'Unknown error'];
    }
    
    /**
     * AJAX: Register new affiliate
     */
    public function ajax_register_affiliate() {
error_log('DLTPays: ajax_register_affiliate called');    
        
        check_ajax_referer('dltpays_nonce', 'nonce');
        global $wpdb;
        error_log('DLTPays: nonce passed, wallet=' . $_POST['wallet_address']);
        $table = $wpdb->prefix . 'dltpays_affiliates';
        
        $wallet = sanitize_text_field($_POST['wallet_address']);
        $currency = sanitize_text_field($_POST['currency']) ?: 'RLUSD';
        $ref_code = '';
if (isset($_POST['referral_code']) && !empty($_POST['referral_code'])) {
    $ref_code = sanitize_text_field($_POST['referral_code']);
} elseif (isset($_COOKIE['dltpays_ref'])) {
    $ref_code = sanitize_text_field($_COOKIE['dltpays_ref']);
}
        
        // Validate wallet format
        if (!preg_match('/^r[1-9A-HJ-NP-Za-km-z]{25,34}$/', $wallet)) {
            wp_send_json_error(['message' => 'Invalid wallet address']);
        }
        
        // Check if wallet already registered locally
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table WHERE wallet_address = %s",
            $wallet
        ));
        
        error_log("DLTPays: exists check = " . var_export($exists, true));
        if ($exists) {
            wp_send_json_error(['message' => 'Wallet already registered']);
        }
        
        // Register with DLTPays API
        $api_secret = get_option('dltpays_api_secret');
        error_log('DLTPays: ref_code=' . $ref_code . ' | POST referral_code=' . ($_POST['referral_code'] ?? 'NOT SET'));
        $payload = [
            'wallet' => $wallet,
            'parent_referral_code' => $ref_code ?: null
        ];
        
        $response = wp_remote_post(DLTPAYS_API_URL . '/affiliate/register', [
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $api_secret
            ],
            'body' => json_encode($payload),
            'timeout' => 30
        ]);
        
        if (is_wp_error($response)) {
            wp_send_json_error(['message' => 'Connection failed: ' . $response->get_error_message()]);
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        $code = wp_remote_retrieve_response_code($response);
        
        error_log("DLTPays API response code: " . $code . " body: " . wp_remote_retrieve_body($response));
        if ($code !== 201) {
            wp_send_json_error(['message' => $body['error'] ?? 'Registration failed']);
        }
        
        // Store locally
        $new_ref_code = $body['referral_code'];
        $affiliate_id = $body['affiliate_id'];
        
        // Get referrer for local chain
        $referred_by = null;
        $upline_chain = [];
        
        if ($ref_code) {
            $referrer = $wpdb->get_row($wpdb->prepare(
                "SELECT id, wallet_address, upline_chain FROM $table WHERE referral_code = %s",
                $ref_code
            ));
            
            if ($referrer) {
                $referred_by = $referrer->id;
                $referrer_chain = json_decode($referrer->upline_chain, true) ?: [];
                $upline_chain = array_merge(
                    [$referrer->wallet_address],
                    array_slice($referrer_chain, 0, 4)
                );
            }
        }
        
        $rates = json_decode(get_option('dltpays_commission_rates', '[25,5,3,2,1]'), true);
        $default_rate = $rates[0] / 100;
        
        $wpdb->insert($table, [
            'wallet_address' => $wallet,
            'wallet_currency' => $currency,
            'referral_code' => $new_ref_code,
            'referred_by' => $referred_by,
            'upline_chain' => json_encode($upline_chain),
            'commission_rate' => $default_rate,
            'user_id' => get_current_user_id() ?: null,
            'status' => 'active',
            'api_affiliate_id' => $affiliate_id
        ]);
        
        // Clear referral cookie
        setcookie('dltpays_ref', '', time() - 3600, '/');
        
        $site_url = get_site_url();
        
        wp_send_json_success([
            'message' => 'Welcome! You are now an affiliate.',
            'referral_code' => $new_ref_code,
            'referral_link' => $site_url . '?ref=' . $new_ref_code
        ]);
    }
    
    /**
     * Shortcode: Affiliate signup form
     */
    public function affiliate_signup_shortcode($atts) {
        ob_start();
        include DLTPAYS_PLUGIN_DIR . 'templates/affiliate-signup.php';
        return ob_get_clean();
    }
    
    /**
     * Shortcode: Affiliate dashboard
     */
    public function affiliate_dashboard_shortcode($atts) {
        ob_start();
        include DLTPAYS_PLUGIN_DIR . 'templates/affiliate-dashboard.php';
        return ob_get_clean();
    }
    
    public function enqueue_scripts() {
        wp_enqueue_style('dltpays-style', DLTPAYS_PLUGIN_URL . 'assets/style.css', [], DLTPAYS_VERSION);
        wp_enqueue_script('dltpays-script', DLTPAYS_PLUGIN_URL . 'assets/script.js', ['jquery'], filemtime(DLTPAYS_PLUGIN_DIR . 'assets/script.js'), true);
        
        wp_localize_script('dltpays-script', 'dltpays', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('dltpays_nonce')
        ]);
    }
    
    public function admin_page() {
        include DLTPAYS_PLUGIN_DIR . 'templates/admin-dashboard.php';
    }
    
    public function settings_page() {
        include DLTPAYS_PLUGIN_DIR . 'templates/admin-settings.php';
    }
    
    public function affiliates_page() {
        include DLTPAYS_PLUGIN_DIR . 'templates/admin-affiliates.php';
    }
    
    /**
     * Register webhook endpoint
     */
    public function register_webhook() {
        register_rest_route('dltpays/v1', '/webhook', [
            'methods' => 'POST',
            'callback' => [$this, 'handle_webhook'],
            'permission_callback' => '__return_true'
        ]);
    }
    
    /**
     * Handle webhook from DLTPays API
     */
    public function handle_webhook($request) {
        global $wpdb;
        
        $body = $request->get_json_params();
        
        // Verify signature
        $api_secret = get_option('dltpays_api_secret');
        $signature = $request->get_header('X-DLTPays-Signature');
        $expected = hash_hmac('sha256', json_encode($body), $api_secret);
        
        if (!hash_equals($expected, $signature ?? '')) {
            return new WP_REST_Response(['error' => 'Invalid signature'], 401);
        }
        
        $payout_id = sanitize_text_field($body['payout_id'] ?? '');
        $status = sanitize_text_field($body['status'] ?? '');
        $tx_hashes = $body['tx_hashes'] ?? [];
        
        if (!$payout_id || !$status) {
            return new WP_REST_Response(['error' => 'Missing fields'], 400);
        }
        
        // Find order by payout_id
        $order_id = $wpdb->get_var($wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_dltpays_payout_id' AND meta_value = %s",
            $payout_id
        ));
        
        if (!$order_id) {
            return new WP_REST_Response(['error' => 'Order not found'], 404);
        }
        
        $table = $wpdb->prefix . 'dltpays_commissions';
        
        if ($status === 'paid') {
            // Update commission records with tx_hash
            foreach ($tx_hashes as $tx) {
                if (isset($tx['wallet']) && isset($tx['tx_hash'])) {
                    $wpdb->query($wpdb->prepare(
                        "UPDATE $table SET status = 'paid', tx_hash = %s, paid_at = %s  
                         WHERE order_id = %d", // This WHERE clause looks wrong for updating individual commissions
                        $tx['tx_hash'],
                        current_time('mysql'),
                        $order_id
                    ));
                }
            }
        }
        
        // Final response for webhook success
        return new WP_REST_Response(['success' => true], 200);
    }
}

DLTPays::instance();

// Store ref in WC session when visiting with ?ref=
add_action('wp', function() {
    if (isset($_GET['ref']) && !empty($_GET['ref']) && function_exists('WC') && WC()->session) {
        error_log('DLTPays: Setting session ref = ' . $_GET['ref']);
        WC()->session->set('dltpays_ref', sanitize_text_field($_GET['ref']));
    }
});

// Hook for WooCommerce Blocks checkout
add_action('woocommerce_store_api_checkout_order_processed', function($order) {
    error_log('DLTPays: store_api_checkout_order_processed called');
    if (function_exists('WC') && WC()->session) {
        $ref_code = WC()->session->get('dltpays_ref');
        error_log('DLTPays: session ref in store_api hook = ' . var_export($ref_code, true));
        if ($ref_code) {
            $order->update_meta_data('_dltpays_referral_code', $ref_code);
            $order->save();
            error_log('DLTPays: saved ref_code to order');
        }
    }
});
