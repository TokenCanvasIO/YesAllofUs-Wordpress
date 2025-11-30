<?php
/**
 * Template: Admin Settings Page
 * Security: API calls proxied through WordPress AJAX - api_secret never exposed to browser
 * Version: 2.0.0 - Added Auto-Signing with Crossmark
 */
if (!defined('ABSPATH')) exit;
if (!current_user_can('manage_options')) return;

$store_id = get_option('dltpays_store_id');
$has_credentials = !empty($store_id) && !empty(get_option('dltpays_api_secret'));
$rates = json_decode(get_option('dltpays_commission_rates', '[25,5,3,2,1]'), true) ?: [25, 5, 3, 2, 1];
$has_referral = !empty(get_option('dltpays_referral_code'));
$payout_mode = get_option('dltpays_payout_mode', 'manual');
?>

<div class="wrap dltpays-admin">
    <h1><?php _e('YesAllofUs Settings', 'dltpays'); ?></h1>
    
    <?php if (!$has_credentials): ?>
    <div class="notice notice-warning">
        <p><strong><?php _e('Setup Required', 'dltpays'); ?></strong></p>
        <p><?php _e('Your store could not be auto-registered. Please try deactivating and reactivating the plugin.', 'dltpays'); ?></p>
        <?php _e('If the problem persists, contact support at mark@yesallofus.com', 'dltpays'); ?>
    </div>
    
    <!-- Promotional Code Section for New Stores -->
    <div class="dltpays-promo-card" style="background: linear-gradient(135deg, #059669 0%, #10b981 100%); padding: 24px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.15); margin: 20px 0; color: #fff;">
        <div style="display: flex; align-items: center; margin-bottom: 16px;">
            <span style="font-size: 28px; margin-right: 12px;">🎁</span>
            <h2 style="margin: 0; color: #fff; font-size: 22px;"><?php _e('Have a Promotional Code?', 'dltpays'); ?></h2>
        </div>
        
        <p style="font-size: 16px; line-height: 1.6; margin-bottom: 20px; color: #e0e7ef;">
    <?php _e('Share your referral code with other store owners. Earn 25% of their platform fees every time they pay affiliates — plus 5 levels deep on stores they refer.', 'dltpays'); ?>
</p>
        
        <div style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
            <input type="text" 
                   id="promo-code-input" 
                   placeholder="<?php _e('Enter code (e.g. A1B2C3D4)', 'dltpays'); ?>"
                   value="<?php echo esc_attr(get_option('dltpays_referral_code', '')); ?>"
                   style="padding: 12px 16px; font-size: 16px; border: 2px solid rgba(255,255,255,0.3); border-radius: 6px; background: rgba(255,255,255,0.1); color: #fff; width: 200px; text-transform: uppercase; letter-spacing: 2px;"
                   maxlength="8">
            <button type="button" 
                    id="apply-promo-code" 
                    style="background: #fff; color: #059669; border: none; padding: 12px 24px; font-size: 14px; font-weight: bold; cursor: pointer; border-radius: 6px; transition: all 0.2s;">
                <?php _e('Apply Code', 'dltpays'); ?>
            </button>
        </div>
        
        <div id="promo-code-message" style="margin-top: 12px; display: none;"></div>
        
        <?php if ($has_referral): ?>
        <div style="margin-top: 16px; padding: 12px; background: rgba(255,255,255,0.15); border-radius: 6px;">
            <span style="color: #d1fae5;">✓ <?php _e('Promotional code applied:', 'dltpays'); ?></span>
            <strong style="color: #fff; letter-spacing: 2px;"><?php echo esc_html(get_option('dltpays_referral_code')); ?></strong>
            <span style="color: #d1fae5;"> — <?php _e('50% off first month fees!', 'dltpays'); ?></span>
        </div>
        <?php endif; ?>
        
        <p style="margin-top: 16px; font-size: 13px; color: #a7f3d0;">
            <?php _e('After applying your code, deactivate and reactivate the plugin to complete registration.', 'dltpays'); ?>
        </p>
    </div>
    
    <?php else: ?>
    
    <div class="dltpays-connection-card" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin: 20px 0;">
        <h2 style="margin-top: 0;"><?php _e('Connection Status', 'dltpays'); ?></h2>
        
        <table class="form-table" style="margin: 0;">
            <tr>
                <th scope="row"><?php _e('Store ID', 'dltpays'); ?></th>
                <td><code style="font-size: 14px;"><?php echo esc_html($store_id); ?></code></td>
            </tr>
            <tr>
                <th scope="row"><?php _e('API Status', 'dltpays'); ?></th>
                <td><div id="connection-status"><span style="color: #666;">Checking...</span></div></td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Wallet', 'dltpays'); ?></th>
                <td><div id="wallet-status"><span style="color: #666;">Checking...</span></div></td>
            </tr>
        </table>
        
        <p style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #eee;">
            <a href="#" id="disconnect-store" style="color: #f0ad4e; text-decoration: none;">
                <?php _e('Disconnect Store', 'dltpays'); ?>
            </a>
            <span style="color: #999; margin-left: 10px; font-size: 12px;"><?php _e('(You can reconnect later by reactivating the plugin)', 'dltpays'); ?></span>
        </p>
    </div>
    
    <!-- Referral Program Section -->
    <div class="dltpays-referral-card" style="background: linear-gradient(135deg, #1e3a5f 0%, #2d5a87 100%); padding: 24px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.15); margin: 20px 0; color: #fff;">
        <div style="display: flex; align-items: center; margin-bottom: 16px;">
            <span style="font-size: 28px; margin-right: 12px;">💰</span>
            <h2 style="margin: 0; color: #fff; font-size: 22px;"><?php _e('Earn by Sharing YesAllofUs', 'dltpays'); ?></h2>
        </div>
        
        <p style="font-size: 16px; line-height: 1.6; margin-bottom: 20px; color: #e0e7ef;">
            <?php _e('Share your referral code with other store owners. When they install YesAllofUs and process sales...', 'dltpays'); ?>
        </p>
        
        <div style="background: rgba(255,255,255,0.1); border-radius: 8px; padding: 16px; margin-bottom: 16px;">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
                <div>
                    <span style="font-size: 12px; text-transform: uppercase; letter-spacing: 1px; color: #a0b4c8;"><?php _e('Your Referral Code', 'dltpays'); ?></span>
                    <div id="store-referral-code" style="font-size: 28px; font-weight: bold; font-family: monospace; letter-spacing: 3px; color: #4ade80; margin-top: 4px;">
                        <span style="color: #666;">Loading...</span>
                    </div>
                </div>
                <button type="button" id="copy-referral-code" style="
                    background: #4ade80;
                    color: #1e3a5f;
                    border: none;
                    padding: 12px 24px;
                    font-size: 14px;
                    font-weight: bold;
                    cursor: pointer;
                    border-radius: 6px;
                    transition: all 0.2s;
                " onmouseover="this.style.background='#22c55e'" onmouseout="this.style.background='#4ade80'">
                    <?php _e('📋 Copy Code', 'dltpays'); ?>
                </button>
            </div>
        </div>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 12px; margin-bottom: 16px;">
            <div style="background: rgba(255,255,255,0.08); border-radius: 6px; padding: 12px; text-align: center;">
                <div style="font-size: 11px; text-transform: uppercase; color: #a0b4c8;">Level 1</div>
                <div style="font-size: 20px; font-weight: bold; color: #4ade80;">25%</div>
            </div>
            <div style="background: rgba(255,255,255,0.08); border-radius: 6px; padding: 12px; text-align: center;">
                <div style="font-size: 11px; text-transform: uppercase; color: #a0b4c8;">Level 2</div>
                <div style="font-size: 20px; font-weight: bold; color: #4ade80;">5%</div>
            </div>
            <div style="background: rgba(255,255,255,0.08); border-radius: 6px; padding: 12px; text-align: center;">
                <div style="font-size: 11px; text-transform: uppercase; color: #a0b4c8;">Level 3</div>
                <div style="font-size: 20px; font-weight: bold; color: #4ade80;">3%</div>
            </div>
            <div style="background: rgba(255,255,255,0.08); border-radius: 6px; padding: 12px; text-align: center;">
                <div style="font-size: 11px; text-transform: uppercase; color: #a0b4c8;">Level 4</div>
                <div style="font-size: 20px; font-weight: bold; color: #4ade80;">2%</div>
            </div>
            <div style="background: rgba(255,255,255,0.08); border-radius: 6px; padding: 12px; text-align: center;">
                <div style="font-size: 11px; text-transform: uppercase; color: #a0b4c8;">Level 5</div>
                <div style="font-size: 20px; font-weight: bold; color: #4ade80;">1%</div>
            </div>
        </div>
        
        <div style="display: flex; justify-content: space-between; align-items: center; padding-top: 16px; border-top: 1px solid rgba(255,255,255,0.1);">
            <div>
                <span style="font-size: 12px; color: #a0b4c8;"><?php _e('Your Referral Earnings', 'dltpays'); ?></span>
                <div id="chainb-earnings" style="font-size: 24px; font-weight: bold; color: #4ade80;">$0.00</div>
            </div>
            <div style="font-size: 13px; color: #a0b4c8; text-align: right;">
                <?php _e('% of platform fees paid by<br>stores you refer', 'dltpays'); ?>
            </div>
        </div>
    </div>
    
    <form method="post" action="options.php">
        <?php settings_fields('dltpays_settings'); ?>
        
        <!-- Hidden fields to preserve store_id only - api_secret handled separately -->
        <input type="hidden" name="dltpays_store_id" value="<?php echo esc_attr($store_id); ?>">
        
        <h2><?php _e('Commission Rates', 'dltpays'); ?></h2>
        <p class="description"><?php _e('Set commission percentages for each MLM level. These are percentages of the platform fee that YesAllofUs charges.', 'dltpays'); ?></p>
        
        <table class="form-table">
            <?php for ($i = 1; $i <= 5; $i++): ?>
            <tr>
                <th scope="row">
                    <label for="rate_l<?php echo $i; ?>"><?php printf(__('Level %d', 'dltpays'), $i); ?></label>
                </th>
                <td>
                    <input type="number" 
                           id="rate_l<?php echo $i; ?>" 
                           name="dltpays_rate_l<?php echo $i; ?>" 
                           value="<?php echo esc_attr($rates[$i-1] ?? 0); ?>" 
                           min="0" 
                           max="50" 
                           step="0.5"
                           style="width: 80px;">
                    <span>%</span>
                    <?php if ($i === 1): ?>
                        <span class="description"><?php _e('(Direct referrer)', 'dltpays'); ?></span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endfor; ?>
            <tr>
                <th scope="row"><?php _e('Total Commission', 'dltpays'); ?></th>
                <td>
                    <strong id="total-rate" style="font-size: 16px;"><?php echo array_sum($rates); ?>%</strong>
                    <span class="description"><?php _e('of platform fee goes to affiliates', 'dltpays'); ?></span>
                </td>
            </tr>
        </table>
        
        <h2><?php _e('Payout Settings', 'dltpays'); ?></h2>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="dltpays_payout_mode"><?php _e('Payout Mode', 'dltpays'); ?></label>
                </th>
                <td>
                    <select id="dltpays_payout_mode" name="dltpays_payout_mode" style="min-width: 200px;">
                        <option value="manual" <?php selected($payout_mode, 'manual'); ?>><?php _e('Manual - Sign each payout yourself', 'dltpays'); ?></option>
                        <option value="auto" <?php selected($payout_mode, 'auto'); ?>><?php _e('Auto - Payouts sign automatically', 'dltpays'); ?></option>
                    </select>
                    
                    <div id="payout-mode-description" style="margin-top: 10px; padding: 12px; background: #f8f9fa; border-left: 3px solid #2563eb; border-radius: 4px;">
                        <div id="manual-mode-info" style="<?php echo $payout_mode === 'manual' ? '' : 'display:none;'; ?>">
                            <strong style="color: #1e40af;">📱 Manual Mode (Recommended for low volume)</strong>
                            <ul style="margin: 8px 0 0 20px; color: #555;">
                                <li>You receive a push notification for each affiliate payout</li>
                                <li>Open Xaman and approve the transaction</li>
                                <li>Full control - you see every payment before it happens</li>
                                <li>Best for: stores wanting maximum oversight</li>
                            </ul>
                        </div>
                        <div id="auto-mode-info" style="<?php echo $payout_mode === 'auto' ? '' : 'display:none;'; ?>">
                            <strong style="color: #059669;">⚡ Auto Mode (Recommended for high volume)</strong>
                            <ul style="margin: 8px 0 0 20px; color: #555;">
                                <li>Payouts sign automatically - no manual approval needed</li>
                                <li>Set your own limits for security</li>
                                <li>Uses Crossmark wallet for secure delegation</li>
                                <li>Revoke anytime from your wallet settings</li>
                                <li>Best for: hands-off operation, 10+ orders per day</li>
                            </ul>
                        </div>
                    </div>
                </td>
            </tr>
        </table>
        
        <!-- ================================================================= -->
        <!-- AUTO-SIGNING SETUP SECTION -->
        <!-- ================================================================= -->
        <div id="auto-signing-section" style="<?php echo $payout_mode === 'auto' ? '' : 'display:none;'; ?> margin-top: 30px;">
            <div style="background: linear-gradient(135deg, #7c3aed 0%, #a855f7 100%); padding: 24px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.15); color: #fff;">
                <div style="display: flex; align-items: center; margin-bottom: 16px;">
                    <span style="font-size: 28px; margin-right: 12px;">⚡</span>
                    <h2 style="margin: 0; color: #fff; font-size: 22px;"><?php _e('Auto-Signing Setup', 'dltpays'); ?></h2>
                </div>
                
                <!-- Status Box -->
                <div id="autosign-status-box" style="background: rgba(0,0,0,0.2); border-radius: 8px; padding: 16px; margin-bottom: 20px;">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <span style="font-size: 12px; text-transform: uppercase; letter-spacing: 1px; color: #c4b5fd;"><?php _e('Status', 'dltpays'); ?></span>
                            <div id="autosign-status" style="font-size: 18px; font-weight: bold; margin-top: 4px;">
                                <span style="color: #fbbf24;">⏳ <?php _e('Checking...', 'dltpays'); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- STEP 1: Terms & Conditions -->
                <div id="autosign-terms-section" style="display: none; background: rgba(255,255,255,0.1); border-radius: 8px; padding: 20px; margin-bottom: 20px;">
                    <h3 style="margin: 0 0 16px 0; color: #fff;">⚠️ <?php _e('Step 1: Read & Accept Terms', 'dltpays'); ?></h3>
                    
                    <div style="background: #fef3c7; color: #92400e; padding: 16px; border-radius: 6px; margin-bottom: 16px; font-size: 14px; line-height: 1.6;">
                        <p style="margin: 0 0 12px 0;"><strong><?php _e('By enabling auto-signing, you authorize YesAllofUs to:', 'dltpays'); ?></strong></p>
                        <ul style="margin: 0 0 12px 20px; padding: 0;">
                            <li><?php _e('Automatically sign and send affiliate commission payments from your connected wallet', 'dltpays'); ?></li>
                            <li><?php _e('Process payments up to your configured limits without manual approval', 'dltpays'); ?></li>
                        </ul>
                        
                        <p style="margin: 0 0 12px 0;"><strong style="color: #dc2626;"><?php _e('🛡️ Security Recommendations:', 'dltpays'); ?></strong></p>
                        <ul style="margin: 0 0 12px 20px; padding: 0;">
                            <li><strong><?php _e('Keep only 1-2 days worth of expected commissions in this wallet', 'dltpays'); ?></strong></li>
                            <li><?php _e('Top up your wallet regularly rather than storing large balances', 'dltpays'); ?></li>
                            <li><?php _e('Set conservative limits below to minimize risk', 'dltpays'); ?></li>
                        </ul>
                        
                        <p style="margin: 0; background: #fde68a; padding: 8px; border-radius: 4px;"><strong><?php _e('You can revoke auto-signing permission at any time from your Xaman wallet settings.', 'dltpays'); ?></strong></p>
                    </div>
                    
                    <label style="display: flex; align-items: flex-start; cursor: pointer; color: #fff;">
                        <input type="checkbox" id="autosign-terms-checkbox" style="margin: 4px 12px 0 0; width: 20px; height: 20px; cursor: pointer;">
                        <span style="line-height: 1.5;">
                            <?php _e('I understand and accept the risks. I have read the security recommendations and agree to the terms of auto-signing.', 'dltpays'); ?>
                        </span>
                    </label>
                    
                    <button type="button" id="accept-autosign-terms" disabled style="
                        margin-top: 16px;
                        background: #4ade80;
                        color: #1e3a5f;
                        border: none;
                        padding: 12px 24px;
                        font-size: 14px;
                        font-weight: bold;
                        cursor: not-allowed;
                        border-radius: 6px;
                        opacity: 0.5;
                    ">
                        <?php _e('Accept & Continue →', 'dltpays'); ?>
                    </button>
                </div>
                
                <!-- STEP 2: Configure Limits -->
                <div id="autosign-limits-section" style="display: none;">
                    <h3 style="margin: 0 0 16px 0; color: #fff;">🎚️ <?php _e('Step 2: Configure Your Limits', 'dltpays'); ?></h3>
                    
                    <div style="display: grid; gap: 24px;">
                        <!-- Max Single Payout Slider -->
                        <div style="background: rgba(255,255,255,0.1); border-radius: 8px; padding: 16px;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                                <label style="font-weight: bold; color: #fff;"><?php _e('Max Single Payout', 'dltpays'); ?></label>
                                <span id="max-single-value" style="font-size: 24px; font-weight: bold; color: #4ade80;">$100</span>
                            </div>
                            <input type="range" 
                                   id="autosign-max-single" 
                                   min="1" 
                                   max="10000" 
                                   value="100"
                                   style="width: 100%; height: 8px; cursor: pointer; accent-color: #4ade80;">
                            <div style="display: flex; justify-content: space-between; font-size: 12px; color: #c4b5fd; margin-top: 4px;">
                                <span>$1</span>
                                <span>$10,000</span>
                            </div>
                            <p style="margin: 8px 0 0 0; font-size: 13px; color: #c4b5fd;">
                                <?php _e('Any single payment above this amount will require manual approval.', 'dltpays'); ?>
                            </p>
                        </div>
                        
                        <!-- Daily Limit Slider -->
                        <div style="background: rgba(255,255,255,0.1); border-radius: 8px; padding: 16px;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                                <label style="font-weight: bold; color: #fff;"><?php _e('Daily Auto-Sign Limit', 'dltpays'); ?></label>
                                <span id="daily-limit-value" style="font-size: 24px; font-weight: bold; color: #4ade80;">$1,000</span>
                            </div>
                            <input type="range" 
                                   id="autosign-daily-limit" 
                                   min="10" 
                                   max="50000" 
                                   value="1000"
                                   step="10"
                                   style="width: 100%; height: 8px; cursor: pointer; accent-color: #4ade80;">
                            <div style="display: flex; justify-content: space-between; font-size: 12px; color: #c4b5fd; margin-top: 4px;">
                                <span>$10</span>
                                <span>$50,000</span>
                            </div>
                            <p style="margin: 8px 0 0 0; font-size: 13px; color: #c4b5fd;">
                                <?php _e('Total auto-signed payouts per day. Once exceeded, manual approval required until next day.', 'dltpays'); ?>
                            </p>
                        </div>
                    </div>
                    
                    <button type="button" id="save-autosign-limits" style="
                        margin-top: 20px;
                        background: #4ade80;
                        color: #1e3a5f;
                        border: none;
                        padding: 14px 28px;
                        font-size: 16px;
                        font-weight: bold;
                        cursor: pointer;
                        border-radius: 8px;
                        width: 100%;
                    ">
                        <?php _e('💾 Save Limits & Continue →', 'dltpays'); ?>
                    </button>
                </div>
                
                <!-- STEP 3: Setup Wallet -->
                <div id="autosign-setup-section" style="display: none;">
                    <h3 style="margin: 0 0 16px 0; color: #fff;">🔗 <?php _e('Step 3: Connect Your Wallet', 'dltpays'); ?></h3>
                    
                    <p style="color: #e9d5ff; margin-bottom: 16px; line-height: 1.6;">
                        <?php _e('Add YesAllofUs as an authorized signer on your wallet. This requires the Crossmark browser extension.', 'dltpays'); ?>
                    </p>
                    
                    <div style="background: rgba(255,255,255,0.1); border-radius: 8px; padding: 16px; margin-bottom: 16px;">
                        <p style="margin: 0 0 8px 0; color: #fff;"><strong><?php _e('Platform Signer Address:', 'dltpays'); ?></strong></p>
                        <code id="platform-signer-address" style="background: rgba(0,0,0,0.3); padding: 8px 12px; border-radius: 4px; font-size: 12px; word-break: break-all; display: block; color: #4ade80;">
                            Loading...
                        </code>
                        <button type="button" id="copy-signer-address" style="margin-top: 8px; background: rgba(255,255,255,0.2); border: none; color: #fff; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-size: 12px;">
                            📋 <?php _e('Copy Address', 'dltpays'); ?>
                        </button>
                    </div>
                    
                    <div style="background: rgba(0,0,0,0.2); border-radius: 8px; padding: 16px; margin-bottom: 16px;">
                        <p style="margin: 0 0 8px 0; color: #fff;"><strong><?php _e("Don't have Crossmark?", 'dltpays'); ?></strong></p>
                        <a href="https://crossmark.io/" target="_blank" style="color: #4ade80; text-decoration: underline;">
                            <?php _e('Download Crossmark browser extension →', 'dltpays'); ?>
                        </a>
                    </div>
                    
                    <button type="button" id="setup-autosign-crossmark" style="
                        background: #fff;
                        color: #7c3aed;
                        border: none;
                        padding: 14px 28px;
                        font-size: 16px;
                        font-weight: bold;
                        cursor: pointer;
                        border-radius: 8px;
                        width: 100%;
                        margin-bottom: 12px;
                    ">
                        🦊 <?php _e('Open Crossmark Setup', 'dltpays'); ?>
                    </button>
                    
                    <button type="button" id="verify-autosign-setup" style="
                        background: transparent;
                        color: #fff;
                        border: 2px solid rgba(255,255,255,0.3);
                        padding: 12px 24px;
                        font-size: 14px;
                        cursor: pointer;
                        border-radius: 8px;
                        width: 100%;
                    ">
                        ✓ <?php _e("I've Added the Signer - Verify Setup", 'dltpays'); ?>
                    </button>
                </div>
                
                <!-- SUCCESS: Auto-signing enabled -->
                <div id="autosign-enabled-section" style="display: none;">
                    <div style="text-align: center; padding: 20px;">
                        <span style="font-size: 48px;">✅</span>
                        <h3 style="color: #4ade80; margin: 12px 0;"><?php _e('Auto-Signing Active!', 'dltpays'); ?></h3>
                        <p style="color: #e9d5ff;"><?php _e('Affiliate payouts will be processed automatically within your limits.', 'dltpays'); ?></p>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 16px;">
                        <div style="background: rgba(255,255,255,0.1); padding: 12px; border-radius: 6px; text-align: center;">
                            <div style="font-size: 12px; color: #c4b5fd;"><?php _e('Max Single Payout', 'dltpays'); ?></div>
                            <div id="current-max-single" style="font-size: 20px; font-weight: bold; color: #4ade80;">$100</div>
                        </div>
                        <div style="background: rgba(255,255,255,0.1); padding: 12px; border-radius: 6px; text-align: center;">
                            <div style="font-size: 12px; color: #c4b5fd;"><?php _e('Daily Limit', 'dltpays'); ?></div>
                            <div id="current-daily-limit" style="font-size: 20px; font-weight: bold; color: #4ade80;">$1,000</div>
                        </div>
                    </div>
                    
                    <button type="button" id="edit-autosign-limits" style="
                        margin-top: 16px;
                        background: rgba(255,255,255,0.1);
                        color: #fff;
                        border: none;
                        padding: 10px 20px;
                        font-size: 14px;
                        cursor: pointer;
                        border-radius: 6px;
                        width: 100%;
                    ">
                        ✏️ <?php _e('Edit Limits', 'dltpays'); ?>
                    </button>
                    
                    <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid rgba(255,255,255,0.2);">
                        <button type="button" id="revoke-autosign" style="
                            background: transparent;
                            color: #fca5a5;
                            border: 1px solid #fca5a5;
                            padding: 10px 20px;
                            font-size: 14px;
                            cursor: pointer;
                            border-radius: 6px;
                        ">
                            <?php _e('Revoke Auto-Signing Permission', 'dltpays'); ?>
                        </button>
                        <p style="margin-top: 8px; font-size: 12px; color: #c4b5fd;">
                            <?php _e('To fully revoke, also remove the signer from your Xaman wallet settings.', 'dltpays'); ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>
        <!-- END AUTO-SIGNING SECTION -->
        
        <h2><?php _e('Tracking Settings', 'dltpays'); ?></h2>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="dltpays_cookie_days"><?php _e('Cookie Duration', 'dltpays'); ?></label>
                </th>
                <td>
                    <input type="number" 
                           id="dltpays_cookie_days" 
                           name="dltpays_cookie_days" 
                           value="<?php echo esc_attr(get_option('dltpays_cookie_days', 30)); ?>" 
                           min="1" 
                           max="365"
                           style="width: 80px;">
                    <span><?php _e('days', 'dltpays'); ?></span>
                    <p class="description"><?php _e('How long referral tracking lasts after someone clicks an affiliate link.', 'dltpays'); ?></p>
                </td>
            </tr>
        </table>
        
        <?php submit_button(__('Save Settings', 'dltpays')); ?>
    </form>
    
    <?php endif; ?>
    
    <hr>
    
    <h2><?php _e('Shortcodes', 'dltpays'); ?></h2>
    <table class="widefat" style="max-width: 600px;">
        <thead>
            <tr>
                <th><?php _e('Shortcode', 'dltpays'); ?></th>
                <th><?php _e('Description', 'dltpays'); ?></th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><code>[dltpays_affiliate_signup]</code></td>
                <td><?php _e('Affiliate registration form', 'dltpays'); ?></td>
            </tr>
            <tr>
                <td><code>[dltpays_affiliate_dashboard]</code></td>
                <td><?php _e('Affiliate dashboard with stats and referral link', 'dltpays'); ?></td>
            </tr>
        </tbody>
    </table>
    
    <?php if ($store_id): ?>
    <!-- Danger Zone -->
    <div style="margin-top: 40px; padding: 20px; border: 2px solid #dc2626; border-radius: 8px; background: #fef2f2;">
        <h2 style="color: #991b1b; margin-top: 0; margin-bottom: 10px;">⚠️ <?php _e('Danger Zone', 'dltpays'); ?></h2>
        <p style="color: #7f1d1d; margin-bottom: 15px;">
            <?php _e('Permanently delete your store and all associated data. This action cannot be undone.', 'dltpays'); ?>
        </p>
        <button type="button" id="delete-store-permanent" style="
            background: #1f2937;
            color: #ef4444;
            border: 2px solid #dc2626;
            padding: 12px 24px;
            font-size: 14px;
            font-weight: bold;
            cursor: pointer;
            border-radius: 6px;
            transition: all 0.2s;
        " onmouseover="this.style.background='#dc2626'; this.style.color='#fff';" onmouseout="this.style.background='#1f2937'; this.style.color='#ef4444';">
            <?php _e('Permanently Delete Store', 'dltpays'); ?>
        </button>
    </div>
    <?php endif; ?>
</div>

<script>
jQuery(document).ready(function($) {
    var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
    var adminNonce = '<?php echo wp_create_nonce('dltpays_admin_nonce'); ?>';
    
    // =========================================================================
    // PROMOTIONAL CODE
    // =========================================================================
    $('#apply-promo-code').on('click', function() {
        var code = $('#promo-code-input').val().trim().toUpperCase();
        var btn = $(this);
        var msgDiv = $('#promo-code-message');
        
        if (!code || code.length < 6) {
            msgDiv.html('<span style="color: #fecaca;">⚠ Please enter a valid promotional code</span>').show();
            return;
        }
        
        btn.prop('disabled', true).text('Validating...');
        msgDiv.hide();
        
        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: { action: 'dltpays_validate_promo_code', nonce: adminNonce, code: code },
            success: function(response) {
                if (response.success) {
                    msgDiv.html(
                        '<div style="padding: 12px; background: rgba(255,255,255,0.15); border-radius: 6px;">' +
                        '<span style="color: #fff; font-size: 16px;">✓ Code applied!</span><br>' +
                        '<span style="color: #d1fae5;">Referred by: <strong>' + response.data.store_name + '</strong></span><br>' +
                        '<span style="color: #fef08a; font-weight: bold;">🎉 You\'ll get 50% off platform fees for your first month!</span>' +
                        '</div>'
                    ).show();
                    btn.text('✓ Applied!').css('background', '#22c55e').css('color', '#fff');
                    setTimeout(function() {
                        msgDiv.append('<div style="margin-top: 12px; padding: 10px; background: rgba(0,0,0,0.2); border-radius: 4px; color: #fff;">👉 Now <strong>deactivate</strong> and <strong>reactivate</strong> the plugin to complete registration with your discount.</div>');
                    }, 1000);
                } else {
                    msgDiv.html('<span style="color: #fecaca;">✗ ' + (response.data || 'Invalid promotional code') + '</span>').show();
                    btn.prop('disabled', false).text('Apply Code');
                }
            },
            error: function() {
                msgDiv.html('<span style="color: #fecaca;">✗ Connection error. Please try again.</span>').show();
                btn.prop('disabled', false).text('Apply Code');
            }
        });
    });
    
    $('#promo-code-input').on('input', function() {
        $(this).val($(this).val().toUpperCase());
    });
    
    // =========================================================================
    // PAYOUT MODE TOGGLE
    // =========================================================================
    $('#dltpays_payout_mode').on('change', function() {
        if ($(this).val() === 'auto') {
            $('#auto-signing-section').show();
            $('#manual-mode-info').hide();
            $('#auto-mode-info').show();
            loadAutosignStatus();
        } else {
            $('#auto-signing-section').hide();
            $('#manual-mode-info').show();
            $('#auto-mode-info').hide();
        }
    });
    
    // =========================================================================
    // COMMISSION RATES
    // =========================================================================
    function updateTotalRate() {
        let total = 0;
        for (let i = 1; i <= 5; i++) {
            total += parseFloat($('#rate_l' + i).val()) || 0;
        }
        $('#total-rate').text(total.toFixed(1) + '%');
        $('#total-rate').css('color', total > 50 ? '#dc3545' : '#28a745');
    }
    
    $('[id^="rate_l"]').on('input', updateTotalRate);
    updateTotalRate();
    
    // =========================================================================
    // AUTO-SIGNING FUNCTIONS
    // =========================================================================
    function loadAutosignStatus() {
        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: { action: 'dltpays_get_autosign_settings', nonce: adminNonce },
            success: function(response) {
                if (response.success) {
                    var data = response.data;
                    
                    // Update platform signer address
                    if (data.platform_signer_address) {
                        var addr = data.platform_signer_address;
                        $('#platform-signer-address')
                            .text(addr.substring(0, 8) + '...' + addr.slice(-4))
                            .data('full-address', addr);
                    }
                    
                    // Update slider values
                    var maxSingle = data.auto_sign_max_single_payout || 100;
                    var dailyLimit = data.auto_sign_daily_limit || 1000;
                    
                    $('#autosign-max-single').val(maxSingle);
                    $('#max-single-value').text('$' + maxSingle.toLocaleString());
                    $('#autosign-daily-limit').val(dailyLimit);
                    $('#daily-limit-value').text('$' + dailyLimit.toLocaleString());
                    
                    // Show appropriate section based on status
                    if (data.auto_signing_enabled) {
                        $('#autosign-status').html('<span style="color: #4ade80;">✅ Active</span>');
                        $('#autosign-terms-section, #autosign-limits-section, #autosign-setup-section').hide();
                        $('#autosign-enabled-section').show();
                        $('#current-max-single').text('$' + maxSingle.toLocaleString());
                        $('#current-daily-limit').text('$' + dailyLimit.toLocaleString());
                    } else if (data.auto_sign_terms_accepted) {
                        $('#autosign-status').html('<span style="color: #fbbf24;">⚠️ Setup Required</span>');
                        $('#autosign-terms-section').hide();
                        $('#autosign-limits-section').show();
                        $('#autosign-setup-section').show();
                        $('#autosign-enabled-section').hide();
                    } else {
                        $('#autosign-status').html('<span style="color: #f87171;">❌ Not Configured</span>');
                        $('#autosign-terms-section').show();
                        $('#autosign-limits-section, #autosign-setup-section, #autosign-enabled-section').hide();
                    }
                } else {
                    $('#autosign-status').html('<span style="color: #f87171;">❌ Error loading</span>');
                }
            },
            error: function() {
                $('#autosign-status').html('<span style="color: #f87171;">❌ Connection error</span>');
            }
        });
    }
    
    // Load on page load if auto mode selected
    if ($('#dltpays_payout_mode').val() === 'auto') {
        loadAutosignStatus();
    }
    
    // Terms checkbox enables button
    $('#autosign-terms-checkbox').on('change', function() {
        var btn = $('#accept-autosign-terms');
        if ($(this).is(':checked')) {
            btn.prop('disabled', false).css({ opacity: 1, cursor: 'pointer' });
        } else {
            btn.prop('disabled', true).css({ opacity: 0.5, cursor: 'not-allowed' });
        }
    });
    
    // Accept terms
    $('#accept-autosign-terms').on('click', function() {
        var btn = $(this);
        btn.prop('disabled', true).text('Saving...');
        
        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: { action: 'dltpays_update_autosign_settings', nonce: adminNonce, auto_sign_terms_accepted: true },
            success: function(response) {
                if (response.success) {
                    $('#autosign-terms-section').hide();
                    $('#autosign-limits-section').show();
                    $('#autosign-status').html('<span style="color: #fbbf24;">⚠️ Configure Limits</span>');
                } else {
                    alert('Error: ' + (response.data || 'Failed to save'));
                    btn.prop('disabled', false).text('Accept & Continue →');
                }
            },
            error: function() {
                alert('Connection error');
                btn.prop('disabled', false).text('Accept & Continue →');
            }
        });
    });
    
    // Slider value updates
    $('#autosign-max-single').on('input', function() {
        $('#max-single-value').text('$' + parseInt($(this).val()).toLocaleString());
    });
    
    $('#autosign-daily-limit').on('input', function() {
        $('#daily-limit-value').text('$' + parseInt($(this).val()).toLocaleString());
    });
    
    // Save limits
    $('#save-autosign-limits').on('click', function() {
        var btn = $(this);
        btn.prop('disabled', true).text('Saving...');
        
        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: {
                action: 'dltpays_update_autosign_settings',
                nonce: adminNonce,
                auto_sign_max_single_payout: $('#autosign-max-single').val(),
                auto_sign_daily_limit: $('#autosign-daily-limit').val()
            },
            success: function(response) {
                if (response.success) {
                    $('#autosign-limits-section').hide();
                    $('#autosign-setup-section').show();
                    $('#autosign-status').html('<span style="color: #fbbf24;">⚠️ Wallet Setup Required</span>');
                } else {
                    alert('Error: ' + (response.data || 'Failed to save'));
                }
                btn.prop('disabled', false).text('💾 Save Limits & Continue →');
            },
            error: function() {
                alert('Connection error');
                btn.prop('disabled', false).text('💾 Save Limits & Continue →');
            }
        });
    });
    
    // Copy signer address
    $('#copy-signer-address').on('click', function() {
        var address = $('#platform-signer-address').data('full-address');
        var btn = $(this);
        
        if (navigator.clipboard && address) {
            navigator.clipboard.writeText(address).then(function() {
                btn.text('✓ Copied!');
                setTimeout(function() { btn.text('📋 Copy Address'); }, 2000);
            });
        }
    });
    
    // Setup with Crossmark (Auto-signing section)
    $('#setup-autosign-crossmark').on('click', async function() {
        var btn = $(this);
        
        if (typeof window.xrpl === 'undefined' || !window.xrpl.crossmark) {
            if (confirm('Crossmark wallet not detected!\n\nClick OK to download Crossmark.')) {
                window.open('https://crossmark.io', '_blank');
            }
            return;
        }
        
        btn.prop('disabled', true).text('Connecting...');
        
        try {
            var sdk = window.xrpl.crossmark;
            var signIn = await sdk.methods.signInAndWait();
            
            if (!signIn.response.data.address) {
                throw new Error('Connection cancelled');
            }
            
            var userWallet = signIn.response.data.address;
            btn.text('Adding signer...');
            
            var tx = await sdk.methods.signAndSubmitAndWait({
                TransactionType: 'SignerListSet',
                Account: userWallet,
                SignerQuorum: 1,
                SignerEntries: [{
                    SignerEntry: {
                        Account: $('#platform-signer-address').data('full-address'),
                        SignerWeight: 1
                    }
                }]
            });
            
            if (tx.response.data.meta.TransactionResult === 'tesSUCCESS') {
                alert('✅ Signer added! Click "Verify Setup" to enable auto-signing.');
            } else {
                throw new Error(tx.response.data.meta.TransactionResult);
            }
            
        } catch (err) {
            alert('❌ ' + (err.message || 'Crossmark error'));
        }
        btn.prop('disabled', false).text('🦊 Open Crossmark Setup');
    });
    
    // Verify auto-sign setup
    $('#verify-autosign-setup').on('click', function() {
        var btn = $(this);
        btn.prop('disabled', true).text('Verifying...');
        
        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: { action: 'dltpays_verify_autosign', nonce: adminNonce },
            success: function(response) {
                if (response.success && response.data.auto_signing_enabled) {
                    alert('✅ ' + (response.data.message || 'Auto-signing enabled!'));
                    location.reload();
                } else {
                    alert('❌ ' + (response.data.message || response.data || 'Verification failed. Make sure you added the signer in Crossmark.'));
                    btn.prop('disabled', false).text('✓ I\'ve Added the Signer - Verify Setup');
                }
            },
            error: function() {
                alert('Connection error');
                btn.prop('disabled', false).text('✓ I\'ve Added the Signer - Verify Setup');
            }
        });
    });
    
    // Edit limits
    $('#edit-autosign-limits').on('click', function() {
        $('#autosign-enabled-section').hide();
        $('#autosign-limits-section').show();
        $('#save-autosign-limits').text('💾 Save Limits');
    });
    
    // Revoke auto-signing
    $('#revoke-autosign').on('click', function() {
        if (!confirm('Are you sure you want to revoke auto-signing? You will need to manually approve each payout in Xaman.')) {
            return;
        }
        
        var btn = $(this);
        btn.prop('disabled', true).text('Revoking...');
        
        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: { action: 'dltpays_revoke_autosign', nonce: adminNonce },
            success: function(response) {
                alert(response.data.message || 'Auto-signing disabled. Remember to also remove the signer from your wallet.');
                loadAutosignStatus();
                btn.prop('disabled', false).text('Revoke Auto-Signing Permission');
            },
            error: function() {
                alert('Connection error');
                btn.prop('disabled', false).text('Revoke Auto-Signing Permission');
            }
        });
    });
    
    // =========================================================================
    // CONNECTION STATUS & WALLET HANDLERS
    // =========================================================================
    <?php if ($has_credentials): ?>
    $.ajax({
        url: ajaxurl,
        method: 'POST',
        data: { action: 'dltpays_check_connection', nonce: adminNonce },
        success: function(response) {
            if (response.success) {
                var data = response.data;
                
                $('#connection-status').html('<span style="color: #28a745;">✓ Connected</span>');
                
                if (data.store_referral_code) {
                    $('#store-referral-code').html('<span style="color: #4ade80;">' + data.store_referral_code + '</span>');
                } else {
                    $('#store-referral-code').html('<span style="color: #999;">Not available</span>');
                }
                
                var earnings = data.chainb_earned || 0;
                $('#chainb-earnings').text('$' + earnings.toFixed(2));
                
                if (data.xaman_connected && data.wallet_address) {
                    var walletType = data.push_enabled ? 'Xaman' : 'Crossmark';
                    var walletHtml = '<span style="color: #28a745;">✓ Connected via ' + walletType + '</span><br>' +
                        '<code style="font-size: 12px;">' + data.wallet_address.substring(0, 8) + '...' + data.wallet_address.slice(-4) + '</code>' +
                        '<br><span style="font-size: 11px; color: #666;">Mode: ' + (data.payout_mode === 'auto' ? '⚡ Auto' : '📱 Manual') + '</span>' +
                        '<br><button type="button" class="button" id="disconnect-wallet" style="margin-top: 8px; color: #dc3545;">Disconnect Wallet</button>';
                    
                    $('#wallet-status').html(walletHtml);
                    
                    // Grey out incompatible payout mode
                    if (data.payout_mode === 'auto') {
                        $('#dltpays_payout_mode option[value="manual"]').prop('disabled', true).text('Manual - Not available (Crossmark connected)');
                    } else if (data.push_enabled) {
                        $('#dltpays_payout_mode option[value="auto"]').prop('disabled', true).text('Auto - Not available (requires Crossmark)');
                    }
                } else {
                    $('#wallet-status').html(
                        '<span style="color: #dc3545;">✗ Not connected</span><br>' +
                        '<button type="button" class="button button-primary" id="connect-wallet" style="margin-top: 5px;">Connect Wallet</button>' +
                        
                        '<div id="wallet-choice-modal" style="display: none; margin-top: 15px; padding: 20px; background: #f8f9fa; border-radius: 8px; border: 1px solid #ddd;">' +
                            '<p style="margin: 0 0 15px 0; font-weight: bold; font-size: 15px;">Choose your wallet:</p>' +
                            
                            '<div id="xaman-option" style="padding: 15px; border: 2px solid #2563eb; border-radius: 8px; margin-bottom: 12px; cursor: pointer; background: #eff6ff;">' +
                                '<div style="display: flex; align-items: center; gap: 10px;">' +
                                    '<span style="font-size: 24px;">📱</span>' +
                                    '<div>' +
                                        '<strong style="color: #1e40af;">Xaman Mobile App</strong>' +
                                        '<span style="background: #2563eb; color: white; font-size: 10px; padding: 2px 6px; border-radius: 3px; margin-left: 8px;">RECOMMENDED</span>' +
                                        '<p style="margin: 4px 0 0 0; font-size: 12px; color: #555;">Manual payouts — approve each via push notification</p>' +
                                    '</div>' +
                                '</div>' +
                            '</div>' +
                            
                            '<div id="crossmark-option" style="padding: 15px; border: 1px solid #ddd; border-radius: 8px; cursor: pointer; background: #fff;">' +
                                '<div style="display: flex; align-items: center; gap: 10px;">' +
                                    '<span style="font-size: 24px;">🦊</span>' +
                                    '<div>' +
                                        '<strong>Crossmark Browser Extension</strong>' +
                                        '<p style="margin: 4px 0 0 0; font-size: 12px; color: #555;">Auto payouts — process automatically within limits</p>' +
                                    '</div>' +
                                '</div>' +
                            '</div>' +
                            
                            '<div id="crossmark-terms" style="display: none; margin-top: 15px; padding: 15px; background: #fef3c7; border-radius: 8px; border: 1px solid #f59e0b;">' +
                                '<h4 style="margin: 0 0 10px 0; color: #92400e;">⚠️ Auto-Payout Terms & Conditions</h4>' +
                                '<div style="font-size: 13px; color: #78350f; line-height: 1.5;">' +
                                    '<p style="margin: 0 0 10px 0;"><strong>By enabling auto-payouts, you authorize YesAllofUs to:</strong></p>' +
                                    '<ul style="margin: 0 0 10px 15px; padding: 0;">' +
                                        '<li>Automatically sign and send affiliate commission payments from your wallet</li>' +
                                        '<li>Process payments up to your configured limits without manual approval</li>' +
                                    '</ul>' +
                                    '<p style="margin: 0 0 10px 0;"><strong style="color: #dc2626;">🛡️ Security Recommendations:</strong></p>' +
                                    '<ul style="margin: 0 0 10px 15px; padding: 0;">' +
                                        '<li><strong>Keep only 1-2 days worth of expected commissions in this wallet</strong></li>' +
                                        '<li>Top up your wallet regularly rather than storing large balances</li>' +
                                        '<li>Set conservative limits to minimize risk</li>' +
                                    '</ul>' +
                                    '<p style="margin: 0; padding: 8px; background: #fde68a; border-radius: 4px;"><strong>You can revoke auto-signing permission at any time from your wallet settings.</strong></p>' +
                                '</div>' +
                                '<label style="display: flex; align-items: flex-start; margin-top: 12px; cursor: pointer;">' +
                                    '<input type="checkbox" id="crossmark-terms-checkbox" style="margin: 3px 10px 0 0; width: 18px; height: 18px;">' +
                                    '<span style="font-size: 13px; color: #78350f;">I have read and agree to the terms and conditions for auto-payouts</span>' +
                                '</label>' +
                                '<button type="button" id="connect-crossmark-btn" disabled style="margin-top: 12px; width: 100%; padding: 12px; background: #9ca3af; color: white; border: none; border-radius: 6px; font-weight: bold; cursor: not-allowed;">' +
                                    '🦊 Connect Crossmark' +
                                '</button>' +
                            '</div>' +
                            
                            '<div id="xaman-qr" style="margin-top: 15px; display: none;"></div>' +
                        '</div>'
                    );
                }
            } else {
                $('#connection-status').html('<span style="color: #dc3545;">✗ ' + (response.data || 'Connection failed') + '</span>');
                $('#wallet-status').html('<span style="color: #666;">-</span>');
            }
        },
        error: function() {
            $('#connection-status').html('<span style="color: #dc3545;">✗ Connection failed</span>');
            $('#wallet-status').html('<span style="color: #666;">-</span>');
        }
    });

    // Show wallet choice modal
    $(document).on('click', '#connect-wallet', function() {
        $('#wallet-choice-modal').slideToggle();
    });

    // Xaman option selected
    $(document).on('click', '#xaman-option', function() {
        $('#xaman-option').css('border-color', '#2563eb').css('background', '#eff6ff');
        $('#crossmark-option').css('border-color', '#ddd').css('background', '#fff');
        $('#crossmark-terms').hide();
        $('#connect-xaman-btn').remove();
        $('#xaman-qr').before('<button type="button" class="button button-primary" id="connect-xaman-btn" style="width: 100%; margin-top: 10px; padding: 10px;">📱 Connect Xaman</button>');
    });

    // Crossmark option selected
    $(document).on('click', '#crossmark-option', function() {
        $('#crossmark-option').css('border-color', '#f59e0b').css('background', '#fffbeb');
        $('#xaman-option').css('border-color', '#ddd').css('background', '#fff');
        $('#crossmark-terms').slideDown();
        $('#connect-xaman-btn').remove();
    });

    // Crossmark terms checkbox
    $(document).on('change', '#crossmark-terms-checkbox', function() {
        var btn = $('#connect-crossmark-btn');
        if ($(this).is(':checked')) {
            btn.prop('disabled', false).css('background', '#f59e0b').css('cursor', 'pointer');
        } else {
            btn.prop('disabled', true).css('background', '#9ca3af').css('cursor', 'not-allowed');
        }
    });

    // Connect with Crossmark (from wallet choice modal)
    $(document).on('click', '#connect-crossmark-btn', async function() {
        var btn = $(this);
        
        if (typeof window.xrpl === 'undefined' || !window.xrpl.crossmark) {
            if (confirm('Crossmark wallet not detected!\n\nClick OK to download Crossmark.')) {
                window.open('https://crossmark.io', '_blank');
            }
            return;
        }
        
        btn.prop('disabled', true).text('Connecting...');
        
        try {
            var sdk = window.xrpl.crossmark;
            var signIn = await sdk.methods.signInAndWait();
            
            if (!signIn.response.data.address) {
                throw new Error('Connection cancelled');
            }
            
            var userWallet = signIn.response.data.address;
            btn.text('Saving...');
            
            // Save wallet to backend
            var saveResult = await $.ajax({
                url: ajaxurl,
                method: 'POST',
                data: {
                    action: 'dltpays_save_crossmark_wallet',
                    nonce: adminNonce,
                    wallet_address: userWallet
                }
            });
            
            if (saveResult.success) {
                // Also accept auto-sign terms
                await $.ajax({
                    url: ajaxurl,
                    method: 'POST',
                    data: {
                        action: 'dltpays_update_autosign_settings',
                        nonce: adminNonce,
                        auto_sign_terms_accepted: true
                    }
                });
                
                alert('✅ Wallet connected!\n\nAddress: ' + userWallet.substring(0, 8) + '...' + userWallet.slice(-4) + '\n\nPayout mode set to Auto. Configure your limits in the Auto-Signing section below.');
                location.reload();
            } else {
                throw new Error(saveResult.data || 'Failed to save wallet');
            }
            
        } catch (err) {
            alert('❌ ' + (err.message || 'Crossmark error'));
            btn.prop('disabled', false).text('🦊 Connect Crossmark');
        }
    });

    // Connect with Xaman (from modal)
    $(document).on('click', '#connect-xaman-btn', function() {
        var btn = $(this);
        btn.prop('disabled', true).text('Connecting...');
        
        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: { action: 'dltpays_connect_xaman', nonce: adminNonce },
            success: function(response) {
                if (response.success) {
                    var data = response.data;
                    
                    $('#xaman-qr').html(
                        '<p><strong>Scan with Xaman app:</strong></p>' +
                        '<img src="' + data.qr_png + '" style="max-width: 200px; border: 1px solid #ddd; border-radius: 8px;">' +
                        '<p style="margin-top: 10px;"><a href="' + data.deep_link + '" class="button button-primary" target="_blank">Open Xaman App</a></p>' +
                        '<p style="font-size: 12px; color: #666;">Waiting for approval...</p>'
                    ).show();
                    
                    btn.text('Waiting for Xaman...');
                    
                    var pollCount = 0;
                    var pollInterval = setInterval(function() {
                        pollCount++;
                        if (pollCount > 60) {
                            clearInterval(pollInterval);
                            btn.text('Timeout - Try Again').prop('disabled', false);
                            return;
                        }
                        
                        $.ajax({
                            url: ajaxurl,
                            method: 'POST',
                            data: { action: 'dltpays_poll_xaman', nonce: adminNonce, connection_id: data.connection_id },
                            success: function(pollResponse) {
                                if (pollResponse.success && pollResponse.data.status === 'connected') {
                                    clearInterval(pollInterval);
                                    $('#xaman-qr').html('<p style="color: #28a745;"><strong>✓ Connected!</strong></p>');
                                    btn.text('Connected!');
                                    setTimeout(function() { location.reload(); }, 2000);
                                } else if (pollResponse.data && (pollResponse.data.status === 'expired' || pollResponse.data.status === 'cancelled')) {
                                    clearInterval(pollInterval);
                                    btn.text('Try Again').prop('disabled', false);
                                    $('#xaman-qr').html('<p style="color: #dc3545;">Connection ' + pollResponse.data.status + '</p>');
                                }
                            }
                        });
                    }, 5000);
                } else {
                    btn.text('Failed - Try Again').prop('disabled', false);
                    alert(response.data || 'Connection failed');
                }
            },
            error: function() {
                btn.text('Failed - Try Again').prop('disabled', false);
            }
        });
    });

    // Disconnect wallet
    $(document).on('click', '#disconnect-wallet', function() {
        if (!confirm('Disconnect your wallet? You will need to reconnect to process payouts.')) {
            return;
        }
        
        var btn = $(this);
        btn.prop('disabled', true).text('Disconnecting...');
        
        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: { action: 'dltpays_disconnect_xaman', nonce: adminNonce },
            success: function(response) {
                if (response.success) {
                    alert('Wallet disconnected.');
                    location.reload();
                } else {
                    alert('Failed: ' + (response.data || 'Unknown error'));
                    btn.prop('disabled', false).text('Disconnect Wallet');
                }
            },
            error: function() {
                alert('Connection error');
                btn.prop('disabled', false).text('Disconnect Wallet');
            }
        });
    });
    
    // Disconnect store
    $('#disconnect-store').on('click', function(e) {
        e.preventDefault();
        if (confirm('Are you sure you want to disconnect this store? You can reconnect later by reactivating the plugin.')) {
            $('<form method="post" action="options.php">' +
                '<?php echo wp_nonce_field('dltpays_settings-options', '_wpnonce', true, false); ?>' +
                '<input type="hidden" name="option_page" value="dltpays_settings">' +
                '<input type="hidden" name="action" value="update">' +
                '<input type="hidden" name="dltpays_store_id" value="">' +
                '<input type="hidden" name="dltpays_api_secret" value="">' +
              '</form>').appendTo('body').submit();
        }
    });
    
    // Permanently delete store
    $('#delete-store-permanent').on('click', function(e) {
        e.preventDefault();
        if (confirm('⚠️ WARNING: This will permanently delete your store, all affiliates, and all payout history. This cannot be undone.')) {
            var confirmation = prompt('Type "PERMANENTLY DELETE" to confirm:');
            if (confirmation === 'PERMANENTLY DELETE') {
                $.ajax({
                    url: ajaxurl,
                    method: 'POST',
                    data: { action: 'dltpays_delete_store', nonce: adminNonce, confirm: 'PERMANENTLY DELETE' },
                    success: function(response) {
                        if (response.success) {
                            alert('Store permanently deleted.');
                            location.reload();
                        } else {
                            alert('Failed: ' + (response.data || 'Unknown error'));
                        }
                    }
                });
            }
        }
    });
    <?php endif; ?>
    
    // Copy referral code
    $('#copy-referral-code').on('click', function() {
        var code = $('#store-referral-code').text().trim();
        var btn = $(this);
        
        if (code && code !== 'Loading...' && code !== 'Not available') {
            if (navigator.clipboard) {
                navigator.clipboard.writeText(code).then(function() {
                    btn.text('✓ Copied!').css('background', '#22c55e');
                    setTimeout(function() { btn.text('📋 Copy Code').css('background', '#4ade80'); }, 2000);
                });
            }
        }
    });
    
    // Save rates as JSON
    $('form').on('submit', function() {
        const rates = [];
        for (let i = 1; i <= 5; i++) {
            rates.push(parseFloat($('#rate_l' + i).val()) || 0);
        }
        if (!$('#dltpays_commission_rates').length) {
            $(this).append('<input type="hidden" name="dltpays_commission_rates" id="dltpays_commission_rates">');
        }
        $('#dltpays_commission_rates').val(JSON.stringify(rates));
    });
});
</script>