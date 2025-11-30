<?php
/**
 * Template: Admin Dashboard
 * Main DLTPays overview page with stats and recent activity
 */
if (!defined('ABSPATH')) exit;
if (!current_user_can('manage_options')) return;

global $wpdb;
$table_affiliates = $wpdb->prefix . 'dltpays_affiliates';
$table_commissions = $wpdb->prefix . 'dltpays_commissions';

// Stats from local database
$total_affiliates = $wpdb->get_var("SELECT COUNT(*) FROM $table_affiliates WHERE status = 'active'");
$total_paid = $wpdb->get_var("SELECT SUM(amount) FROM $table_commissions WHERE status = 'paid'") ?: 0;
$total_pending = $wpdb->get_var("SELECT SUM(amount) FROM $table_commissions WHERE status IN ('pending', 'queued')") ?: 0;
$recent_payouts = $wpdb->get_var("SELECT COUNT(*) FROM $table_commissions WHERE status = 'paid' AND paid_at > DATE_SUB(NOW(), INTERVAL 7 DAY)");
$total_orders = $wpdb->get_var("SELECT COUNT(DISTINCT order_id) FROM $table_commissions");

// Recent activity
$recent = $wpdb->get_results("
    SELECT c.*, a.wallet_address, a.referral_code 
    FROM $table_commissions c 
    LEFT JOIN $table_affiliates a ON c.affiliate_id = a.id 
    ORDER BY c.created_at DESC 
    LIMIT 10
");

// Top affiliates
$top_affiliates = $wpdb->get_results("
    SELECT a.wallet_address, a.referral_code, a.total_earned, a.created_at,
           COUNT(DISTINCT c.order_id) as order_count
    FROM $table_affiliates a
    LEFT JOIN $table_commissions c ON a.id = c.affiliate_id AND c.status = 'paid'
    WHERE a.status = 'active'
    GROUP BY a.id
    ORDER BY a.total_earned DESC
    LIMIT 5
");

$store_id = get_option('dltpays_store_id');
$has_credentials = !empty($store_id) && !empty(get_option('dltpays_api_secret'));
?>

<div class="wrap dltpays-admin">
    <h1>
        <span style="display: inline-flex; align-items: center; gap: 10px;">
            <svg width="32" height="32" viewBox="0 0 100 100" fill="none">
                <circle cx="50" cy="50" r="45" fill="#1e3a5f"/>
                <path d="M30 50 L45 65 L70 35" stroke="#4ade80" stroke-width="8" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <?php _e('YesAllofUs Dashboard', 'dltpays'); ?>
        </span>
    </h1>
    
    <?php if (!$has_credentials): ?>
    <div class="dltpays-welcome-card">
        <h2><?php _e('Welcome to YesAllofUs!', 'dltpays'); ?></h2>
        <p><?php _e('Instant RLUSD affiliate commissions for your WooCommerce store.', 'dltpays'); ?></p>
        <a href="<?php echo admin_url('admin.php?page=dltpays-settings'); ?>" class="button button-primary button-hero">
            <?php _e('Connect Your Store →', 'dltpays'); ?>
        </a>
    </div>
    <?php else: ?>
    
    <!-- Connection Status Bar -->
    <div id="connection-bar" class="dltpays-connection-bar loading">
        <span class="status-icon">⏳</span>
        <span class="status-text"><?php _e('Checking connection...', 'dltpays'); ?></span>
    </div>
    
    <!-- Stats Grid -->
    <div class="dltpays-stats-grid">
        <div class="stat-box">
            <span class="stat-icon">👥</span>
            <span class="stat-number"><?php echo number_format($total_affiliates); ?></span>
            <span class="stat-label"><?php _e('Active Affiliates', 'dltpays'); ?></span>
        </div>
        <div class="stat-box highlight">
            <span class="stat-icon">💰</span>
            <span class="stat-number">$<?php echo number_format($total_paid, 2); ?></span>
            <span class="stat-label"><?php _e('Total Paid (RLUSD)', 'dltpays'); ?></span>
        </div>
        <div class="stat-box">
            <span class="stat-icon">⏳</span>
            <span class="stat-number">$<?php echo number_format($total_pending, 2); ?></span>
            <span class="stat-label"><?php _e('Pending Approval', 'dltpays'); ?></span>
        </div>
        <div class="stat-box">
            <span class="stat-icon">🛒</span>
            <span class="stat-number"><?php echo number_format($total_orders); ?></span>
            <span class="stat-label"><?php _e('Referred Orders', 'dltpays'); ?></span>
        </div>
    </div>
    
    <div class="dltpays-two-column">
        <!-- Recent Activity -->
        <div class="dltpays-card">
            <h2><?php _e('Recent Activity', 'dltpays'); ?></h2>
            
            <?php if (empty($recent)): ?>
                <div class="empty-state">
                    <span class="empty-icon">📋</span>
                    <p><?php _e('No commission activity yet.', 'dltpays'); ?></p>
                    <p class="hint"><?php _e('Commissions appear when orders complete with a referral code.', 'dltpays'); ?></p>
                </div>
            <?php else: ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Date', 'dltpays'); ?></th>
                            <th><?php _e('Order', 'dltpays'); ?></th>
                            <th><?php _e('Amount', 'dltpays'); ?></th>
                            <th><?php _e('Status', 'dltpays'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent as $row): ?>
                        <tr>
                            <td><?php echo date('M j, H:i', strtotime($row->created_at)); ?></td>
                            <td>
                                <a href="<?php echo admin_url('post.php?post=' . $row->order_id . '&action=edit'); ?>">
                                    #<?php echo $row->order_id; ?>
                                </a>
                            </td>
                            <td>
                                <strong>$<?php echo number_format($row->amount, 2); ?></strong>
                                <small style="color: #666;">L<?php echo $row->level; ?></small>
                            </td>
                            <td>
                                <?php if ($row->status === 'paid' && $row->tx_hash): ?>
                                    <a href="https://xrpscan.com/tx/<?php echo esc_attr($row->tx_hash); ?>" 
                                       target="_blank" 
                                       class="status-badge status-paid"
                                       title="<?php echo esc_attr($row->tx_hash); ?>">
                                        ✓ Paid ↗
                                    </a>
                                <?php else: ?>
                                    <span class="status-badge status-<?php echo esc_attr($row->status); ?>">
                                        <?php echo ucfirst($row->status); ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        
        <!-- Top Affiliates -->
        <div class="dltpays-card">
            <h2><?php _e('Top Affiliates', 'dltpays'); ?></h2>
            
            <?php if (empty($top_affiliates)): ?>
                <div class="empty-state">
                    <span class="empty-icon">🏆</span>
                    <p><?php _e('No affiliates registered yet.', 'dltpays'); ?></p>
                    <p class="hint"><?php _e('Share your affiliate signup page to get started.', 'dltpays'); ?></p>
                </div>
            <?php else: ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Affiliate', 'dltpays'); ?></th>
                            <th><?php _e('Earned', 'dltpays'); ?></th>
                            <th><?php _e('Orders', 'dltpays'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($top_affiliates as $aff): ?>
                        <tr>
                            <td>
                                <code style="font-size: 11px;"><?php echo esc_html(substr($aff->wallet_address, 0, 8)); ?>...</code>
                                <br>
                                <small style="color: #2563eb;"><?php echo esc_html($aff->referral_code); ?></small>
                            </td>
                            <td><strong>$<?php echo number_format($aff->total_earned, 2); ?></strong></td>
                            <td><?php echo number_format($aff->order_count); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
            
            <p style="margin-top: 15px;">
                <a href="<?php echo admin_url('admin.php?page=dltpays-affiliates'); ?>" class="button">
                    <?php _e('View All Affiliates →', 'dltpays'); ?>
                </a>
            </p>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="dltpays-card" style="margin-top: 20px;">
        <h2><?php _e('Quick Actions', 'dltpays'); ?></h2>
        <div class="quick-actions">
            <a href="<?php echo admin_url('admin.php?page=dltpays-settings'); ?>" class="action-button">
                <span class="action-icon">⚙️</span>
                <span class="action-label"><?php _e('Settings', 'dltpays'); ?></span>
            </a>
            <a href="<?php echo admin_url('admin.php?page=dltpays-affiliates'); ?>" class="action-button">
                <span class="action-icon">👥</span>
                <span class="action-label"><?php _e('Affiliates', 'dltpays'); ?></span>
            </a>
            <a href="<?php echo home_url('?page_id=affiliate-signup'); ?>" class="action-button" target="_blank">
                <span class="action-icon">📝</span>
                <span class="action-label"><?php _e('Signup Page', 'dltpays'); ?></span>
            </a>
            <a href="https://yesallofus.com/docs" class="action-button" target="_blank">
                <span class="action-icon">📚</span>
                <span class="action-label"><?php _e('Documentation', 'dltpays'); ?></span>
            </a>
        </div>
    </div>
    
    <?php endif; ?>
</div>

<style>
.dltpays-admin {
    max-width: 1200px;
}

.dltpays-welcome-card {
    background: linear-gradient(135deg, #1e3a5f 0%, #2563eb 100%);
    color: white;
    padding: 40px;
    border-radius: 12px;
    text-align: center;
    margin: 20px 0;
}
.dltpays-welcome-card h2 {
    color: white;
    margin: 0 0 10px;
    font-size: 28px;
}
.dltpays-welcome-card p {
    opacity: 0.9;
    font-size: 16px;
    margin-bottom: 20px;
}
.dltpays-welcome-card .button-hero {
    background: #4ade80 !important;
    border: none !important;
    color: #1e3a5f !important;
    font-weight: 600;
}

.dltpays-connection-bar {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 16px;
    border-radius: 8px;
    margin: 20px 0;
    font-weight: 500;
}
.dltpays-connection-bar.loading {
    background: #f0f0f0;
    color: #666;
}
.dltpays-connection-bar.connected {
    background: #d4edda;
    color: #155724;
}
.dltpays-connection-bar.error {
    background: #f8d7da;
    color: #721c24;
}
.dltpays-connection-bar.warning {
    background: #fff3cd;
    color: #856404;
}
.dltpays-connection-bar .status-icon {
    font-size: 18px;
}

.dltpays-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin: 20px 0;
}
.stat-box {
    background: #fff;
    padding: 30px 24px;
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    text-align: center;
    transition: transform 0.2s, box-shadow 0.2s;
}
.stat-box:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}
.stat-box.highlight {
    background: linear-gradient(135deg, #1e3a5f 0%, #2563eb 100%);
    color: white;
}
.stat-box.highlight .stat-label {
    color: rgba(255,255,255,0.8);
}
.stat-icon {
    display: block;
    font-size: 28px;
    margin-bottom: 15px;
}
.stat-number {
    display: block;
    font-size: 32px;
    font-weight: bold;
    color: #1e3a5f;
    margin-bottom: 8px;
}
.stat-box.highlight .stat-number {
    color: white;
}
.stat-label {
    color: #666;
    font-size: 14px;
}

.dltpays-two-column {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin: 20px 0;
}
@media (max-width: 900px) {
    .dltpays-two-column {
        grid-template-columns: 1fr;
    }
}

.dltpays-card {
    background: #fff;
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}
.dltpays-card h2 {
    margin: 0 0 15px;
    font-size: 18px;
    color: #1e3a5f;
}

.empty-state {
    text-align: center;
    padding: 30px;
    color: #666;
}
.empty-icon {
    font-size: 48px;
    display: block;
    margin-bottom: 10px;
    opacity: 0.5;
}
.empty-state .hint {
    font-size: 13px;
    color: #999;
}

.status-badge {
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 500;
    text-decoration: none;
    display: inline-block;
}
.status-paid { background: #d4edda; color: #155724; }
.status-pending { background: #fff3cd; color: #856404; }
.status-queued { background: #e7f3ff; color: #0066cc; }
.status-failed { background: #f8d7da; color: #721c24; }
.status-expired { background: #e9ecef; color: #495057; }

.quick-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
}
.action-button {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 20px 30px;
    background: #f8f9fa;
    border-radius: 8px;
    text-decoration: none;
    color: #1e3a5f;
    transition: background 0.2s;
}
.action-button:hover {
    background: #e9ecef;
}
.action-icon {
    font-size: 24px;
    margin-bottom: 8px;
}
.action-label {
    font-weight: 500;
    font-size: 14px;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Check API connection status
    $.ajax({
        url: ajaxurl,
        method: 'POST',
        data: {
            action: 'dltpays_check_connection',
            nonce: '<?php echo wp_create_nonce('dltpays_admin_nonce'); ?>'
        },
        success: function(response) {
            var bar = $('#connection-bar');
            bar.removeClass('loading');
            
            if (response.success) {
                var data = response.data;
                
                if (!data.email_verified) {
                    bar.addClass('warning');
                    bar.find('.status-icon').text('⚠️');
                    bar.find('.status-text').html(
                        '<?php _e('Store not verified', 'dltpays'); ?> - ' +
                        '<a href="<?php echo admin_url('admin.php?page=dltpays-settings'); ?>"><?php _e('Complete setup', 'dltpays'); ?></a>'
                    );
                } else if (!data.xaman_connected) {
    bar.addClass('warning');
    bar.find('.status-icon').text('⚠️');
    bar.find('.status-text').html(
        '<?php _e('Xaman wallet not connected', 'dltpays'); ?> - ' +
        '<a href="<?php echo admin_url('admin.php?page=dltpays-settings'); ?>"><?php _e('Connect now', 'dltpays'); ?></a>'
    );
} else {
    bar.addClass('connected');
    bar.find('.status-icon').text('✓');
    bar.find('.status-text').html(
        '<?php _e('Connected & Ready', 'dltpays'); ?> | ' +
        '<?php _e('Wallet:', 'dltpays'); ?> <code style="font-size: 12px;">' + 
        data.wallet_address.substring(0, 8) + '...' + data.wallet_address.slice(-4) + '</code>'
    );
}
            } else {
                bar.addClass('error');
                bar.find('.status-icon').text('✗');
                bar.find('.status-text').html(
                    '<?php _e('Connection failed', 'dltpays'); ?> - ' +
                    '<a href="<?php echo admin_url('admin.php?page=dltpays-settings'); ?>"><?php _e('Check settings', 'dltpays'); ?></a>'
                );
            }
        },
        error: function() {
            var bar = $('#connection-bar');
            bar.removeClass('loading').addClass('error');
            bar.find('.status-icon').text('✗');
            bar.find('.status-text').text('<?php _e('Connection failed', 'dltpays'); ?>');
        }
    });
});
</script>