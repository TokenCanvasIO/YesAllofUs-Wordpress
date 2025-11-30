<?php
/**
 * Template: Admin Affiliates List
 */
if (!defined('ABSPATH')) exit;
if (!current_user_can('manage_options')) return;

global $wpdb;
$table = $wpdb->prefix . 'dltpays_affiliates';
$table_commissions = $wpdb->prefix . 'dltpays_commissions';

// Pagination
$per_page = 20;
$current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$offset = ($current_page - 1) * $per_page;

// Search
$search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
$where = '';
if ($search) {
    $where = $wpdb->prepare(
        " WHERE wallet_address LIKE %s OR referral_code LIKE %s",
        '%' . $wpdb->esc_like($search) . '%',
        '%' . $wpdb->esc_like($search) . '%'
    );
}

$total = $wpdb->get_var("SELECT COUNT(*) FROM $table $where");
$affiliates = $wpdb->get_results("
    SELECT a.*, 
           (SELECT COUNT(*) FROM $table WHERE referred_by = a.id) as referral_count,
           (SELECT SUM(amount) FROM $table_commissions WHERE affiliate_id = a.id AND status = 'paid') as total_paid
    FROM $table a 
    $where
    ORDER BY a.created_at DESC 
    LIMIT $per_page OFFSET $offset
");

$total_pages = ceil($total / $per_page);
?>

<div class="wrap dltpays-admin">
    <h1>
        <?php _e('Affiliates', 'dltpays'); ?>
        <span class="title-count">(<?php echo number_format($total); ?>)</span>
    </h1>
    
    <form method="get" class="search-box" style="margin: 20px 0;">
        <input type="hidden" name="page" value="dltpays-affiliates">
        <input type="search" name="s" value="<?php echo esc_attr($search); ?>" placeholder="<?php _e('Search wallet or code...', 'dltpays'); ?>">
        <button type="submit" class="button"><?php _e('Search', 'dltpays'); ?></button>
        <?php if ($search): ?>
            <a href="<?php echo admin_url('admin.php?page=dltpays-affiliates'); ?>" class="button"><?php _e('Clear', 'dltpays'); ?></a>
        <?php endif; ?>
    </form>
    
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th style="width: 60px;"><?php _e('ID', 'dltpays'); ?></th>
                <th><?php _e('Wallet', 'dltpays'); ?></th>
                <th><?php _e('Code', 'dltpays'); ?></th>
                <th><?php _e('Currency', 'dltpays'); ?></th>
                <th><?php _e('Rate', 'dltpays'); ?></th>
                <th><?php _e('Referrals', 'dltpays'); ?></th>
                <th><?php _e('Earned', 'dltpays'); ?></th>
                <th><?php _e('Status', 'dltpays'); ?></th>
                <th><?php _e('Joined', 'dltpays'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($affiliates)): ?>
                <tr>
                    <td colspan="9"><?php _e('No affiliates found.', 'dltpays'); ?></td>
                </tr>
            <?php else: ?>
                <?php foreach ($affiliates as $aff): ?>
                <tr>
                    <td><?php echo $aff->id; ?></td>
                    <td>
                        <code title="<?php echo esc_attr($aff->wallet_address); ?>">
                            <?php echo esc_html(substr($aff->wallet_address, 0, 8)); ?>...<?php echo esc_html(substr($aff->wallet_address, -4)); ?>
                        </code>
                        <br>
                        <a href="https://xrpscan.com/account/<?php echo esc_attr($aff->wallet_address); ?>" target="_blank" style="font-size: 11px;">
                            View on XRPScan ↗
                        </a>
                    </td>
                    <td>
                        <strong><?php echo esc_html($aff->referral_code); ?></strong>
                        <br>
                        <button type="button" 
                                onclick="navigator.clipboard.writeText('<?php echo esc_url(home_url('?ref=' . $aff->referral_code)); ?>')" 
                                style="font-size: 11px; padding: 2px 6px; cursor: pointer;">
                            Copy Link
                        </button>
                    </td>
                    <td><?php echo esc_html($aff->wallet_currency); ?></td>
                    <td><?php echo ($aff->commission_rate * 100); ?>%</td>
                    <td><?php echo number_format($aff->referral_count); ?></td>
                    <td>$<?php echo number_format($aff->total_paid ?: 0, 2); ?></td>
                    <td>
                        <span class="status-badge status-<?php echo $aff->status; ?>">
                            <?php echo ucfirst($aff->status); ?>
                        </span>
                    </td>
                    <td><?php echo date('M j, Y', strtotime($aff->created_at)); ?></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
    
    <?php if ($total_pages > 1): ?>
    <div class="tablenav bottom">
        <div class="tablenav-pages">
            <span class="displaying-num"><?php printf(_n('%s item', '%s items', $total, 'dltpays'), number_format($total)); ?></span>
            <span class="pagination-links">
                <?php if ($current_page > 1): ?>
                    <a href="<?php echo add_query_arg('paged', $current_page - 1); ?>" class="prev-page button">‹</a>
                <?php endif; ?>
                
                <span class="paging-input">
                    <?php echo $current_page; ?> / <?php echo $total_pages; ?>
                </span>
                
                <?php if ($current_page < $total_pages): ?>
                    <a href="<?php echo add_query_arg('paged', $current_page + 1); ?>" class="next-page button">›</a>
                <?php endif; ?>
            </span>
        </div>
    </div>
    <?php endif; ?>
</div>

<style>
.status-badge {
    padding: 3px 8px;
    border-radius: 3px;
    font-size: 12px;
}
.status-active { background: #d4edda; color: #155724; }
.status-inactive { background: #e2e3e5; color: #383d41; }
.status-suspended { background: #f8d7da; color: #721c24; }
.title-count { font-weight: normal; color: #666; }
</style>
