<?php
/**
 * Template: Affiliate Dashboard - Premium Edition
 */
if (!defined('ABSPATH')) exit;

global $wpdb;
$table_affiliates = $wpdb->prefix . 'dltpays_affiliates';
$table_commissions = $wpdb->prefix . 'dltpays_commissions';

// Find affiliate by user ID or wallet from query param
$affiliate = null;
$wallet_param = isset($_GET['wallet']) ? sanitize_text_field($_GET['wallet']) : '';

if (is_user_logged_in()) {
    $affiliate = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table_affiliates WHERE user_id = %d",
        get_current_user_id()
    ));
}

if (!$affiliate && $wallet_param) {
    $affiliate = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table_affiliates WHERE wallet_address = %s",
        $wallet_param
    ));
}

// Get store settings
$rates = json_decode(get_option('dltpays_commission_rates', '[25,5,3,2,1]'), true) ?: [25,5,3,2,1];
$store_name = get_bloginfo('name');
?>

<style>
@import url('https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700&family=JetBrains+Mono:wght@400;500&display=swap');

:root {
    --dlt-bg-primary: #0a0a0b;
    --dlt-bg-secondary: #111113;
    --dlt-bg-card: #16161a;
    --dlt-bg-elevated: #1c1c21;
    --dlt-border: rgba(255,255,255,0.06);
    --dlt-border-hover: rgba(255,255,255,0.12);
    --dlt-text-primary: #ffffff;
    --dlt-text-secondary: #a1a1aa;
    --dlt-text-tertiary: #71717a;
    --dlt-accent: #10b981;
    --dlt-accent-glow: rgba(16, 185, 129, 0.15);
    --dlt-accent-subtle: rgba(16, 185, 129, 0.08);
    --dlt-warning: #f59e0b;
    --dlt-error: #ef4444;
    --dlt-success: #10b981;
    --dlt-blue: #3b82f6;
    --dlt-purple: #8b5cf6;
    --dlt-radius-sm: 8px;
    --dlt-radius-md: 12px;
    --dlt-radius-lg: 16px;
    --dlt-radius-xl: 24px;
    --dlt-font-sans: 'DM Sans', -apple-system, BlinkMacSystemFont, sans-serif;
    --dlt-font-mono: 'JetBrains Mono', monospace;
}

.dlt-dashboard {
    font-family: var(--dlt-font-sans);
    background: var(--dlt-bg-primary);
    min-height: 100vh;
    color: var(--dlt-text-primary);
    padding: 32px 24px;
    position: relative;
    overflow: hidden;
}

.dlt-dashboard * {
    box-sizing: border-box;
}

/* Ambient background effects */
.dlt-dashboard::before {
    content: '';
    position: absolute;
    top: -200px;
    right: -200px;
    width: 600px;
    height: 600px;
    background: radial-gradient(circle, var(--dlt-accent-glow) 0%, transparent 70%);
    pointer-events: none;
    opacity: 0.5;
}

.dlt-dashboard::after {
    content: '';
    position: absolute;
    bottom: -300px;
    left: -200px;
    width: 800px;
    height: 800px;
    background: radial-gradient(circle, rgba(59, 130, 246, 0.08) 0%, transparent 70%);
    pointer-events: none;
}

.dlt-container {
    width: 100%;
    position: relative;
    z-index: 1;
}

/* Not Registered State */
.dlt-not-registered {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 80vh;
}

.dlt-empty-state {
    text-align: center;
    max-width: 440px;
}

.dlt-empty-icon {
    width: 80px;
    height: 80px;
    background: var(--dlt-bg-card);
    border: 1px solid var(--dlt-border);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 24px;
}

.dlt-empty-icon svg {
    width: 36px;
    height: 36px;
    color: var(--dlt-text-tertiary);
}

.dlt-empty-state h2 {
    font-size: 24px;
    font-weight: 600;
    margin: 0 0 12px;
    color: var(--dlt-text-primary);
}

.dlt-empty-state p {
    color: var(--dlt-text-secondary);
    font-size: 15px;
    line-height: 1.6;
    margin: 0 0 32px;
}

.dlt-cta-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 14px 28px;
    font-size: 15px;
    font-weight: 600;
    color: #0a0a0b;
    background: var(--dlt-accent);
    border: none;
    border-radius: var(--dlt-radius-md);
    cursor: pointer;
    text-decoration: none;
    transition: all 0.2s ease;
}

.dlt-cta-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px -8px rgba(16, 185, 129, 0.4);
}

.dlt-cta-btn svg {
    width: 18px;
    height: 18px;
}

.dlt-divider {
    display: flex;
    align-items: center;
    gap: 16px;
    margin: 32px 0;
    color: var(--dlt-text-tertiary);
    font-size: 13px;
}

.dlt-divider::before,
.dlt-divider::after {
    content: '';
    flex: 1;
    height: 1px;
    background: var(--dlt-border);
}

.dlt-wallet-lookup {
    display: flex;
    gap: 8px;
}

.dlt-wallet-lookup input {
    flex: 1;
    padding: 12px 16px;
    font-size: 14px;
    font-family: var(--dlt-font-mono);
    background: var(--dlt-bg-card);
    border: 1px solid var(--dlt-border);
    border-radius: var(--dlt-radius-md);
    color: var(--dlt-text-primary);
    transition: border-color 0.2s;
}

.dlt-wallet-lookup input:focus {
    outline: none;
    border-color: var(--dlt-accent);
}

.dlt-wallet-lookup input::placeholder {
    color: var(--dlt-text-tertiary);
}

.dlt-wallet-lookup button {
    padding: 12px 20px;
    font-size: 14px;
    font-weight: 600;
    color: var(--dlt-text-primary);
    background: var(--dlt-bg-elevated);
    border: 1px solid var(--dlt-border);
    border-radius: var(--dlt-radius-md);
    cursor: pointer;
    transition: all 0.2s;
}

.dlt-wallet-lookup button:hover {
    background: var(--dlt-bg-card);
    border-color: var(--dlt-border-hover);
}

/* Dashboard Header */
.dlt-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 24px;
    margin-bottom: 32px;
    flex-wrap: wrap;
}

.dlt-welcome h1 {
    font-size: 28px;
    font-weight: 700;
    margin: 0 0 8px;
    letter-spacing: -0.5px;
}

.dlt-wallet-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 14px;
    background: var(--dlt-bg-card);
    border: 1px solid var(--dlt-border);
    border-radius: 100px;
    font-family: var(--dlt-font-mono);
    font-size: 13px;
    color: var(--dlt-text-secondary);
}

.dlt-wallet-badge .dot {
    width: 8px;
    height: 8px;
    background: var(--dlt-accent);
    border-radius: 50%;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}

.dlt-header-actions {
    display: flex;
    gap: 12px;
}

.dlt-btn-secondary {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 10px 16px;
    font-size: 14px;
    font-weight: 500;
    color: var(--dlt-text-primary);
    background: var(--dlt-bg-card);
    border: 1px solid var(--dlt-border);
    border-radius: var(--dlt-radius-md);
    cursor: pointer;
    text-decoration: none;
    transition: all 0.2s;
}

.dlt-btn-secondary:hover {
    background: var(--dlt-bg-elevated);
    border-color: var(--dlt-border-hover);
}

.dlt-btn-secondary svg {
    width: 16px;
    height: 16px;
}

/* Stats Grid */
.dlt-stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
    margin-bottom: 32px;
}

@media (max-width: 900px) {
    .dlt-stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 500px) {
    .dlt-stats-grid {
        grid-template-columns: 1fr;
    }
}

.dlt-stat-card {
    background: var(--dlt-bg-card);
    border: 1px solid var(--dlt-border);
    border-radius: var(--dlt-radius-lg);
    padding: 24px;
    position: relative;
    overflow: hidden;
    transition: all 0.3s ease;
}

.dlt-stat-card:hover {
    border-color: var(--dlt-border-hover);
    transform: translateY(-2px);
}

.dlt-stat-card.accent {
    background: linear-gradient(135deg, rgba(16, 185, 129, 0.12) 0%, var(--dlt-bg-card) 100%);
    border-color: rgba(16, 185, 129, 0.2);
}

.dlt-stat-card.accent::before {
    content: '';
    position: absolute;
    top: 0;
    right: 0;
    width: 120px;
    height: 120px;
    background: radial-gradient(circle, var(--dlt-accent-glow) 0%, transparent 70%);
    pointer-events: none;
}

.dlt-stat-label {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    font-weight: 500;
    color: var(--dlt-text-tertiary);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 12px;
}

.dlt-stat-label svg {
    width: 16px;
    height: 16px;
    opacity: 0.6;
}

.dlt-stat-value {
    font-size: 32px;
    font-weight: 700;
    color: var(--dlt-text-primary);
    letter-spacing: -1px;
    line-height: 1;
}

.dlt-stat-card.accent .dlt-stat-value {
    background: linear-gradient(135deg, #10b981 0%, #34d399 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.dlt-stat-change {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: 12px;
    font-weight: 600;
    margin-top: 8px;
    padding: 4px 8px;
    border-radius: 6px;
}

.dlt-stat-change.up {
    color: var(--dlt-success);
    background: rgba(16, 185, 129, 0.1);
}

.dlt-stat-change.down {
    color: var(--dlt-error);
    background: rgba(239, 68, 68, 0.1);
}

/* Referral Link Card */
.dlt-referral-card {
    background: var(--dlt-bg-card);
    border: 1px solid var(--dlt-border);
    border-radius: var(--dlt-radius-lg);
    padding: 28px;
    margin-bottom: 24px;
}

.dlt-referral-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 20px;
    flex-wrap: wrap;
    gap: 12px;
}

.dlt-referral-header h3 {
    font-size: 16px;
    font-weight: 600;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 10px;
}

.dlt-referral-header h3 svg {
    width: 20px;
    height: 20px;
    color: var(--dlt-accent);
}

.dlt-commission-badge {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    background: var(--dlt-accent-subtle);
    border: 1px solid rgba(16, 185, 129, 0.2);
    border-radius: 100px;
    font-size: 13px;
    font-weight: 600;
    color: var(--dlt-accent);
}

.dlt-link-box {
    display: flex;
    gap: 8px;
    background: var(--dlt-bg-primary);
    border: 1px solid var(--dlt-border);
    border-radius: var(--dlt-radius-md);
    padding: 6px;
}

.dlt-link-box input {
    flex: 1;
    padding: 12px 14px;
    font-size: 14px;
    font-family: var(--dlt-font-mono);
    background: transparent;
    border: none;
    color: var(--dlt-text-primary);
    min-width: 0;
}

.dlt-link-box input:focus {
    outline: none;
}

.dlt-copy-btn {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 12px 20px;
    font-size: 14px;
    font-weight: 600;
    color: #0a0a0b;
    background: var(--dlt-accent);
    border: none;
    border-radius: var(--dlt-radius-sm);
    cursor: pointer;
    transition: all 0.2s;
    white-space: nowrap;
}

.dlt-copy-btn:hover {
    background: #0ea572;
}

.dlt-copy-btn.copied {
    background: #34d399;
}

.dlt-copy-btn svg {
    width: 16px;
    height: 16px;
}

.dlt-share-row {
    display: flex;
    gap: 8px;
    margin-top: 16px;
}

.dlt-share-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    flex: 1;
    padding: 10px 16px;
    font-size: 13px;
    font-weight: 500;
    color: var(--dlt-text-primary);
    background: var(--dlt-bg-elevated);
    border: 1px solid var(--dlt-border);
    border-radius: var(--dlt-radius-sm);
    cursor: pointer;
    text-decoration: none;
    transition: all 0.2s;
}

.dlt-share-btn:hover {
    background: var(--dlt-bg-card);
    border-color: var(--dlt-border-hover);
}

.dlt-share-btn svg {
    width: 16px;
    height: 16px;
}

.dlt-share-btn.twitter:hover { color: #1da1f2; }
.dlt-share-btn.telegram:hover { color: #0088cc; }
.dlt-share-btn.whatsapp:hover { color: #25d366; }

/* Two Column Layout */
.dlt-two-col {
    display: grid;
    grid-template-columns: 1fr 380px;
    gap: 24px;
}

@media (max-width: 900px) {
    .dlt-two-col {
        grid-template-columns: 1fr;
    }
}

/* Activity Table */
.dlt-activity-card {
    background: var(--dlt-bg-card);
    border: 1px solid var(--dlt-border);
    border-radius: var(--dlt-radius-lg);
    overflow: hidden;
}

.dlt-card-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 20px 24px;
    border-bottom: 1px solid var(--dlt-border);
}

.dlt-card-header h3 {
    font-size: 16px;
    font-weight: 600;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 10px;
}

.dlt-card-header h3 svg {
    width: 20px;
    height: 20px;
    color: var(--dlt-text-tertiary);
}

.dlt-table-wrap {
    overflow-x: auto;
}

.dlt-table {
    width: 100%;
    border-collapse: collapse;
}

.dlt-table th {
    padding: 14px 24px;
    font-size: 11px;
    font-weight: 600;
    color: var(--dlt-text-tertiary);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    text-align: left;
    background: var(--dlt-bg-secondary);
}

.dlt-table td {
    padding: 16px 24px;
    font-size: 14px;
    color: var(--dlt-text-primary);
    border-bottom: 1px solid var(--dlt-border);
}

.dlt-table tr:last-child td {
    border-bottom: none;
}

.dlt-table tr:hover td {
    background: var(--dlt-bg-secondary);
}

.dlt-level-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 28px;
    height: 28px;
    font-size: 11px;
    font-weight: 700;
    background: var(--dlt-bg-elevated);
    border: 1px solid var(--dlt-border);
    border-radius: 6px;
}

.dlt-level-badge.l1 { color: var(--dlt-accent); border-color: rgba(16, 185, 129, 0.3); background: rgba(16, 185, 129, 0.1); }
.dlt-level-badge.l2 { color: var(--dlt-blue); border-color: rgba(59, 130, 246, 0.3); background: rgba(59, 130, 246, 0.1); }
.dlt-level-badge.l3 { color: var(--dlt-purple); border-color: rgba(139, 92, 246, 0.3); background: rgba(139, 92, 246, 0.1); }
.dlt-level-badge.l4 { color: var(--dlt-warning); border-color: rgba(245, 158, 11, 0.3); background: rgba(245, 158, 11, 0.1); }
.dlt-level-badge.l5 { color: #ec4899; border-color: rgba(236, 72, 153, 0.3); background: rgba(236, 72, 153, 0.1); }

.dlt-amount {
    font-family: var(--dlt-font-mono);
    font-weight: 600;
}

.dlt-amount.positive {
    color: var(--dlt-accent);
}

.dlt-status {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 5px 10px;
    font-size: 12px;
    font-weight: 600;
    border-radius: 6px;
}

.dlt-status.paid {
    color: var(--dlt-success);
    background: rgba(16, 185, 129, 0.1);
}

.dlt-status.pending {
    color: var(--dlt-warning);
    background: rgba(245, 158, 11, 0.1);
}

.dlt-status.failed {
    color: var(--dlt-error);
    background: rgba(239, 68, 68, 0.1);
}

.dlt-status svg {
    width: 12px;
    height: 12px;
}

.dlt-tx-link {
    color: inherit;
    text-decoration: none;
}

.dlt-tx-link:hover {
    text-decoration: underline;
}

.dlt-empty-activity {
    padding: 48px 24px;
    text-align: center;
}

.dlt-empty-activity svg {
    width: 48px;
    height: 48px;
    color: var(--dlt-text-tertiary);
    margin-bottom: 16px;
    opacity: 0.5;
}

.dlt-empty-activity p {
    color: var(--dlt-text-secondary);
    font-size: 14px;
    margin: 0;
}

/* Network Card */
.dlt-network-card {
    background: var(--dlt-bg-card);
    border: 1px solid var(--dlt-border);
    border-radius: var(--dlt-radius-lg);
    padding: 24px;
}

.dlt-network-card h3 {
    font-size: 16px;
    font-weight: 600;
    margin: 0 0 8px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.dlt-network-card h3 svg {
    width: 20px;
    height: 20px;
    color: var(--dlt-purple);
}

.dlt-network-card > p {
    font-size: 13px;
    color: var(--dlt-text-secondary);
    margin: 0 0 24px;
    line-height: 1.5;
}

.dlt-network-tree {
    position: relative;
}

.dlt-network-level {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px 16px;
    background: var(--dlt-bg-secondary);
    border: 1px solid var(--dlt-border);
    border-radius: var(--dlt-radius-md);
    margin-bottom: 8px;
    transition: all 0.2s;
}

.dlt-network-level:hover {
    border-color: var(--dlt-border-hover);
}

.dlt-network-level.you {
    background: linear-gradient(135deg, rgba(16, 185, 129, 0.1) 0%, var(--dlt-bg-secondary) 100%);
    border-color: rgba(16, 185, 129, 0.2);
}

.dlt-level-indicator {
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    font-weight: 700;
    background: var(--dlt-bg-elevated);
    border: 1px solid var(--dlt-border);
    border-radius: 8px;
    flex-shrink: 0;
}

.dlt-network-level.you .dlt-level-indicator {
    background: var(--dlt-accent);
    border-color: var(--dlt-accent);
    color: #0a0a0b;
}

.dlt-level-info {
    flex: 1;
    min-width: 0;
}

.dlt-level-title {
    font-size: 14px;
    font-weight: 600;
    color: var(--dlt-text-primary);
    margin-bottom: 2px;
}

.dlt-level-wallet {
    font-size: 12px;
    font-family: var(--dlt-font-mono);
    color: var(--dlt-text-tertiary);
}

.dlt-level-rate {
    font-size: 14px;
    font-weight: 700;
    color: var(--dlt-accent);
    padding: 6px 12px;
    background: var(--dlt-accent-subtle);
    border-radius: 6px;
}

/* Rates Breakdown */
.dlt-rates-card {
    background: var(--dlt-bg-card);
    border: 1px solid var(--dlt-border);
    border-radius: var(--dlt-radius-lg);
    padding: 24px;
    margin-top: 16px;
}

.dlt-rates-card h4 {
    font-size: 14px;
    font-weight: 600;
    margin: 0 0 16px;
    color: var(--dlt-text-secondary);
}

.dlt-rate-row {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 0;
    border-bottom: 1px solid var(--dlt-border);
}

.dlt-rate-row:last-child {
    border-bottom: none;
}

.dlt-rate-level {
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 11px;
    font-weight: 700;
    border-radius: 6px;
}

.dlt-rate-row:nth-child(1) .dlt-rate-level { color: var(--dlt-accent); background: rgba(16, 185, 129, 0.1); }
.dlt-rate-row:nth-child(2) .dlt-rate-level { color: var(--dlt-blue); background: rgba(59, 130, 246, 0.1); }
.dlt-rate-row:nth-child(3) .dlt-rate-level { color: var(--dlt-purple); background: rgba(139, 92, 246, 0.1); }
.dlt-rate-row:nth-child(4) .dlt-rate-level { color: var(--dlt-warning); background: rgba(245, 158, 11, 0.1); }
.dlt-rate-row:nth-child(5) .dlt-rate-level { color: #ec4899; background: rgba(236, 72, 153, 0.1); }

.dlt-rate-desc {
    flex: 1;
    font-size: 13px;
    color: var(--dlt-text-secondary);
}

.dlt-rate-percent {
    font-size: 14px;
    font-weight: 700;
    font-family: var(--dlt-font-mono);
    color: var(--dlt-text-primary);
}

/* Footer */
.dlt-footer {
    margin-top: 48px;
    padding-top: 24px;
    border-top: 1px solid var(--dlt-border);
    text-align: center;
}

.dlt-powered {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    color: var(--dlt-text-tertiary);
    text-decoration: none;
}

.dlt-powered:hover {
    color: var(--dlt-text-secondary);
}

.dlt-powered svg {
    width: 16px;
    height: 16px;
}

/* Animation keyframes */
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.dlt-dashboard > .dlt-container > * {
    animation: fadeIn 0.4s ease backwards;
}

.dlt-dashboard > .dlt-container > *:nth-child(1) { animation-delay: 0s; }
.dlt-dashboard > .dlt-container > *:nth-child(2) { animation-delay: 0.05s; }
.dlt-dashboard > .dlt-container > *:nth-child(3) { animation-delay: 0.1s; }
.dlt-dashboard > .dlt-container > *:nth-child(4) { animation-delay: 0.15s; }
.dlt-dashboard > .dlt-container > *:nth-child(5) { animation-delay: 0.2s; }
</style>

<div class="dlt-dashboard">
    <div class="dlt-container">
        
        <?php if (!$affiliate): ?>
        <!-- Not Registered State -->
        <div class="dlt-not-registered">
            <div class="dlt-empty-state">
                <div class="dlt-empty-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                        <circle cx="9" cy="7" r="4"/>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                    </svg>
                </div>
                <h2>Not an Affiliate Yet</h2>
                <p>Join our affiliate programme and start earning instant commissions on every sale you refer. Payouts are sent directly to your wallet.</p>
                <a href="<?php echo home_url('/become-affiliate'); ?>" class="dlt-cta-btn">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/>
                    </svg>
                    Become an Affiliate
                </a>
                
                <div class="dlt-divider">or lookup existing account</div>
                
                <form method="get" class="dlt-wallet-lookup">
                    <input type="text" name="wallet" placeholder="rXXXX..." pattern="^r[1-9A-HJ-NP-Za-km-z]{25,34}$">
                    <button type="submit">View Dashboard</button>
                </form>
            </div>
        </div>
        
        <?php else: 
        // Get stats
        $total_earned = (float) $affiliate->total_earned;
        $pending = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(amount) FROM $table_commissions WHERE affiliate_id = %d AND status = 'pending'",
            $affiliate->id
        )) ?: 0;

        $paid_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_commissions WHERE affiliate_id = %d AND status = 'paid'",
            $affiliate->id
        )) ?: 0;

        $direct_referrals = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_affiliates WHERE referred_by = %d",
            $affiliate->id
        )) ?: 0;

        // Recent commissions
        $recent = $wpdb->get_results($wpdb->prepare(
            "SELECT c.*, o.post_date as order_date 
             FROM $table_commissions c 
             LEFT JOIN {$wpdb->posts} o ON c.order_id = o.ID
             WHERE c.affiliate_id = %d 
             ORDER BY c.created_at DESC 
             LIMIT 10",
            $affiliate->id
        ));

        $referral_link = get_site_url() . '?ref=' . $affiliate->referral_code;
        $upline = json_decode($affiliate->upline_chain, true) ?: [];
        ?>
        
        <!-- Header -->
        <div class="dlt-header">
            <div class="dlt-welcome">
                <h1>Welcome back</h1>
                <div class="dlt-wallet-badge">
                    <span class="dot"></span>
                    <?php echo esc_html(substr($affiliate->wallet_address, 0, 6) . '...' . substr($affiliate->wallet_address, -4)); ?>
                </div>
            </div>
            <div class="dlt-header-actions">
                <a href="https://xrpscan.com/account/<?php echo esc_attr($affiliate->wallet_address); ?>" target="_blank" class="dlt-btn-secondary">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/>
                        <polyline points="15 3 21 3 21 9"/>
                        <line x1="10" y1="14" x2="21" y2="3"/>
                    </svg>
                    View on XRPL
                </a>
            </div>
        </div>
        
        <!-- Stats Grid -->
        <div class="dlt-stats-grid">
            <div class="dlt-stat-card accent">
                <div class="dlt-stat-label">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="12" y1="1" x2="12" y2="23"/>
                        <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                    </svg>
                    Total Earned
                </div>
                <div class="dlt-stat-value">$<?php echo number_format($total_earned, 2); ?></div>
            </div>
            <div class="dlt-stat-card">
                <div class="dlt-stat-label">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/>
                        <polyline points="12 6 12 12 16 14"/>
                    </svg>
                    Pending
                </div>
                <div class="dlt-stat-value">$<?php echo number_format($pending, 2); ?></div>
            </div>
            <div class="dlt-stat-card">
                <div class="dlt-stat-label">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20 6L9 17l-5-5"/>
                    </svg>
                    Paid Sales
                </div>
                <div class="dlt-stat-value"><?php echo number_format($paid_count); ?></div>
            </div>
            <div class="dlt-stat-card">
                <div class="dlt-stat-label">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                        <circle cx="9" cy="7" r="4"/>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                    </svg>
                    Your Referrals
                </div>
                <div class="dlt-stat-value"><?php echo number_format($direct_referrals); ?></div>
            </div>
        </div>
        
        <!-- Referral Link Card -->
        <div class="dlt-referral-card">
            <div class="dlt-referral-header">
                <h3>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/>
                        <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/>
                    </svg>
                    Your Referral Link
                </h3>
                <div class="dlt-commission-badge">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14">
                        <path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/>
                    </svg>
                    <?php echo $rates[0]; ?>% Commission
                </div>
            </div>
            <div class="dlt-link-box">
                <input type="text" value="<?php echo esc_url($referral_link); ?>" readonly id="dlt-ref-link">
                <button type="button" class="dlt-copy-btn" onclick="dltCopyLink(this)">
                    <svg class="copy-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="9" y="9" width="13" height="13" rx="2" ry="2"/>
                        <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>
                    </svg>
                    <svg class="check-icon" style="display:none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20 6L9 17l-5-5"/>
                    </svg>
                    <span>Copy</span>
                </button>
            </div>
            <div class="dlt-share-row">
                <a href="https://twitter.com/intent/tweet?text=<?php echo urlencode('Check this out! ' . $referral_link); ?>" target="_blank" class="dlt-share-btn twitter">
                    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                    Twitter
                </a>
                <a href="https://t.me/share/url?url=<?php echo urlencode($referral_link); ?>" target="_blank" class="dlt-share-btn telegram">
                    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/></svg>
                    Telegram
                </a>
                <a href="https://wa.me/?text=<?php echo urlencode('Check this out! ' . $referral_link); ?>" target="_blank" class="dlt-share-btn whatsapp">
                    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                    WhatsApp
                </a>
            </div>
        </div>
        
        <!-- Two Column Layout -->
        <div class="dlt-two-col">
            <!-- Activity Table -->
            <div class="dlt-activity-card">
                <div class="dlt-card-header">
                    <h3>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>
                        </svg>
                        Recent Activity
                    </h3>
                </div>
                <?php if (empty($recent)): ?>
                <div class="dlt-empty-activity">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                        <line x1="3" y1="9" x2="21" y2="9"/>
                        <line x1="9" y1="21" x2="9" y2="9"/>
                    </svg>
                    <p>No commissions yet. Share your referral link to start earning!</p>
                </div>
                <?php else: ?>
                <div class="dlt-table-wrap">
                    <table class="dlt-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Level</th>
                                <th>Amount</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent as $commission): ?>
                            <tr>
                                <td><?php echo date('M j, Y', strtotime($commission->created_at)); ?></td>
                                <td><span class="dlt-level-badge l<?php echo $commission->level; ?>">L<?php echo $commission->level; ?></span></td>
                                <td><span class="dlt-amount positive">+$<?php echo number_format($commission->amount, 2); ?></span></td>
                                <td>
                                    <?php if ($commission->status === 'paid' && $commission->tx_hash): ?>
                                    <a href="https://xrpscan.com/tx/<?php echo esc_attr($commission->tx_hash); ?>" target="_blank" class="dlt-status paid dlt-tx-link">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6L9 17l-5-5"/></svg>
                                        Paid
                                    </a>
                                    <?php elseif ($commission->status === 'pending'): ?>
                                    <span class="dlt-status pending">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                        Pending
                                    </span>
                                    <?php else: ?>
                                    <span class="dlt-status <?php echo esc_attr($commission->status); ?>">
                                        <?php echo ucfirst($commission->status); ?>
                                    </span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Right Column -->
            <div class="dlt-sidebar">
                <!-- Network Card -->
                <div class="dlt-network-card">
                    <h3>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="18" cy="5" r="3"/>
                            <circle cx="6" cy="12" r="3"/>
                            <circle cx="18" cy="19" r="3"/>
                            <line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/>
                            <line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/>
                        </svg>
                        Your Network
                    </h3>
                    <p>Earn on 5 levels when your network makes sales.</p>
                    
                    <div class="dlt-network-tree">
                        <div class="dlt-network-level you">
                            <div class="dlt-level-indicator">YOU</div>
                            <div class="dlt-level-info">
                                <div class="dlt-level-title">Direct Sales</div>
                                <div class="dlt-level-wallet"><?php echo esc_html(substr($affiliate->wallet_address, 0, 8)); ?>...</div>
                            </div>
                            <div class="dlt-level-rate"><?php echo $rates[0]; ?>%</div>
                        </div>
                        
                        <?php if (!empty($upline)): ?>
                            <?php foreach ($upline as $i => $wallet): ?>
                            <div class="dlt-network-level">
                                <div class="dlt-level-indicator">L<?php echo $i + 2; ?></div>
                                <div class="dlt-level-info">
                                    <div class="dlt-level-title">Level <?php echo $i + 2; ?> Upline</div>
                                    <div class="dlt-level-wallet"><?php echo esc_html(substr($wallet, 0, 8)); ?>...</div>
                                </div>
                                <div class="dlt-level-rate"><?php echo $rates[$i + 1] ?? 0; ?>%</div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Commission Rates -->
                <div class="dlt-rates-card">
                    <h4>Commission Structure</h4>
                    <?php 
                    $level_names = ['Your Sales', 'Level 2', 'Level 3', 'Level 4', 'Level 5'];
                    foreach ($rates as $i => $rate): 
                    ?>
                    <div class="dlt-rate-row">
                        <div class="dlt-rate-level">L<?php echo $i + 1; ?></div>
                        <div class="dlt-rate-desc"><?php echo $level_names[$i]; ?></div>
                        <div class="dlt-rate-percent"><?php echo $rate; ?>%</div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="dlt-footer">
            <a href="https://yesallofus.com" target="_blank" class="dlt-powered">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/>
    </svg>
    Powered by YesAllofUs
</a>
        </div>
        
        <?php endif; ?>
        
    </div>
</div>

<script>
function dltCopyLink(btn) {
    const input = document.getElementById('dlt-ref-link');
    navigator.clipboard.writeText(input.value).then(function() {
        btn.classList.add('copied');
        btn.querySelector('.copy-icon').style.display = 'none';
        btn.querySelector('.check-icon').style.display = 'block';
        btn.querySelector('span').textContent = 'Copied!';
        
        setTimeout(function() {
            btn.classList.remove('copied');
            btn.querySelector('.copy-icon').style.display = 'block';
            btn.querySelector('.check-icon').style.display = 'none';
            btn.querySelector('span').textContent = 'Copy';
        }, 2000);
    });
}
</script>