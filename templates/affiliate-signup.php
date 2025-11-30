<?php
/**
 * Template: Affiliate Signup Form
 */
if (!defined('ABSPATH')) exit;

// Get referral code from multiple sources (URL param takes priority)
$ref_code = '';
if (isset($_GET['ref']) && !empty($_GET['ref'])) {
    $ref_code = sanitize_text_field($_GET['ref']);
} elseif (isset($_COOKIE['dltpays_ref']) && !empty($_COOKIE['dltpays_ref'])) {
    $ref_code = sanitize_text_field($_COOKIE['dltpays_ref']);
} elseif (function_exists('WC') && WC()->session) {
    $ref_code = WC()->session->get('dltpays_ref') ?: '';
}

$rates = json_decode(get_option('dltpays_commission_rates', '[25,5,3,2,1]'), true) ?: [25,5,3,2,1];
$store_name = get_bloginfo('name');
?>
<style>
.dltpays-signup-container {
    max-width: 480px;
    margin: 0 auto;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}
.dltpays-signup-card {
    background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
    border-radius: 20px;
    padding: 40px;
    color: #fff;
    box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);
}
.dltpays-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: rgba(16, 185, 129, 0.15);
    border: 1px solid rgba(16, 185, 129, 0.3);
    color: #10b981;
    font-size: 12px;
    font-weight: 600;
    padding: 6px 12px;
    border-radius: 50px;
    margin-bottom: 20px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.dltpays-badge svg {
    width: 14px;
    height: 14px;
}
.dltpays-signup-card h2 {
    font-size: 28px;
    font-weight: 700;
    margin: 0 0 8px 0;
    color: #fff;
}
.dltpays-signup-card .subtitle {
    color: #94a3b8;
    font-size: 16px;
    margin: 0 0 32px 0;
    line-height: 1.5;
}
.dltpays-commission-highlight {
    display: flex;
    align-items: center;
    gap: 16px;
    background: rgba(255,255,255,0.05);
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 28px;
}
.dltpays-commission-percent {
    font-size: 48px;
    font-weight: 800;
    background: linear-gradient(135deg, #10b981 0%, #34d399 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    line-height: 1;
}
.dltpays-commission-text {
    font-size: 14px;
    color: #cbd5e1;
    line-height: 1.4;
}
.dltpays-commission-text strong {
    display: block;
    color: #fff;
    font-size: 16px;
    margin-bottom: 2px;
}
.dltpays-referred-badge {
    display: flex;
    align-items: center;
    gap: 8px;
    background: rgba(59, 130, 246, 0.15);
    border: 1px solid rgba(59, 130, 246, 0.3);
    color: #60a5fa;
    font-size: 13px;
    padding: 10px 14px;
    border-radius: 10px;
    margin-bottom: 24px;
}
.dltpays-referred-badge svg {
    width: 16px;
    height: 16px;
    flex-shrink: 0;
}
.dltpays-form-group {
    margin-bottom: 20px;
}
.dltpays-form-group label {
    display: block;
    font-size: 13px;
    font-weight: 600;
    color: #e2e8f0;
    margin-bottom: 8px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.dltpays-form-group input[type="text"] {
    width: 100%;
    padding: 14px 16px;
    font-size: 15px;
    background: rgba(255,255,255,0.07);
    border: 2px solid rgba(255,255,255,0.1);
    border-radius: 10px;
    color: #fff;
    transition: all 0.2s;
    box-sizing: border-box;
}
.dltpays-form-group input[type="text"]:focus {
    outline: none;
    border-color: #10b981;
    background: rgba(255,255,255,0.1);
}
.dltpays-form-group input[type="text"]::placeholder {
    color: #64748b;
}
.dltpays-form-group .hint {
    font-size: 12px;
    color: #64748b;
    margin-top: 8px;
    display: flex;
    align-items: center;
    gap: 6px;
}
.dltpays-form-group .hint svg {
    width: 14px;
    height: 14px;
    color: #10b981;
}
.dltpays-checkbox-group {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    margin-bottom: 24px;
}
.dltpays-checkbox-group input[type="checkbox"] {
    width: 18px;
    height: 18px;
    margin-top: 2px;
    accent-color: #10b981;
    flex-shrink: 0;
}
.dltpays-checkbox-group label {
    font-size: 14px;
    color: #94a3b8;
    cursor: pointer;
    line-height: 1.4;
}
.dltpays-checkbox-group a {
    color: #10b981;
    text-decoration: none;
}
.dltpays-checkbox-group a:hover {
    text-decoration: underline;
}
.dltpays-submit-btn {
    width: 100%;
    padding: 16px 24px;
    font-size: 16px;
    font-weight: 600;
    color: #fff;
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    border: none;
    border-radius: 10px;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}
.dltpays-submit-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 20px -10px rgba(16, 185, 129, 0.5);
}
.dltpays-submit-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
}
.dltpays-submit-btn svg {
    width: 18px;
    height: 18px;
}
.dltpays-trustline-note {
    display: flex;
    align-items: center;
    gap: 10px;
    background: rgba(251, 191, 36, 0.1);
    border: 1px solid rgba(251, 191, 36, 0.2);
    border-radius: 10px;
    padding: 14px;
    margin-top: 20px;
    font-size: 13px;
    color: #fbbf24;
}
.dltpays-trustline-note svg {
    width: 18px;
    height: 18px;
    flex-shrink: 0;
}
.dltpays-trustline-note a {
    color: #fbbf24;
    font-weight: 600;
}
.dltpays-message {
    padding: 14px;
    border-radius: 10px;
    margin-top: 16px;
    font-size: 14px;
    display: none;
}
.dltpays-message.error {
    background: rgba(239, 68, 68, 0.15);
    border: 1px solid rgba(239, 68, 68, 0.3);
    color: #fca5a5;
}
.dltpays-message.success {
    background: rgba(16, 185, 129, 0.15);
    border: 1px solid rgba(16, 185, 129, 0.3);
    color: #6ee7b7;
}
.dltpays-success-card {
    text-align: center;
    display: none;
}
.dltpays-success-icon {
    width: 80px;
    height: 80px;
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 24px;
}
.dltpays-success-icon svg {
    width: 40px;
    height: 40px;
    color: #fff;
}
.dltpays-success-card h3 {
    font-size: 24px;
    font-weight: 700;
    color: #fff;
    margin: 0 0 8px 0;
}
.dltpays-success-card .success-subtitle {
    color: #94a3b8;
    margin: 0 0 32px 0;
}
.dltpays-link-box {
    background: rgba(255,255,255,0.05);
    border-radius: 10px;
    padding: 4px;
    display: flex;
    gap: 4px;
    margin-bottom: 24px;
}
.dltpays-link-box input {
    flex: 1;
    padding: 12px 14px;
    font-size: 13px;
    background: transparent;
    border: none;
    color: #fff;
    font-family: monospace;
}
.dltpays-link-box input:focus {
    outline: none;
}
.dltpays-copy-btn {
    padding: 12px 20px;
    font-size: 14px;
    font-weight: 600;
    color: #0f172a;
    background: #10b981;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    gap: 6px;
}
.dltpays-copy-btn:hover {
    background: #34d399;
}
.dltpays-copy-btn.copied {
    background: #6ee7b7;
}
.dltpays-copy-btn svg {
    width: 16px;
    height: 16px;
}
.dltpays-dashboard-link {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    color: #10b981;
    font-weight: 600;
    text-decoration: none;
    font-size: 15px;
}
.dltpays-dashboard-link:hover {
    color: #34d399;
}
.dltpays-dashboard-link svg {
    width: 18px;
    height: 18px;
}
</style>

<div class="dltpays-signup-container">
    <div class="dltpays-signup-card">
        <div class="dltpays-form-state">
            <div class="dltpays-badge">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/>
                </svg>
                Instant Payouts
            </div>
            
            <h2>Become an Affiliate</h2>
            <p class="subtitle">Promote <?php echo esc_html($store_name); ?> and earn commissions paid instantly to your wallet.</p>
            
            <div class="dltpays-commission-highlight">
                <div class="dltpays-commission-percent"><?php echo $rates[0]; ?>%</div>
                <div class="dltpays-commission-text">
                    <strong>Commission Rate</strong>
                    Paid in RLUSD on every sale
                </div>
            </div>
            <p style="font-size: 13px; color: #64748b; margin: -16px 0 28px 0;">
                Plus earn on 5 levels when you recruit affiliates
            </p>
            
            <?php if ($ref_code): ?>
            <div class="dltpays-referred-badge">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                    <circle cx="9" cy="7" r="4"/>
                    <path d="M22 21v-2a4 4 0 0 0-3-3.87"/>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                </svg>
                Referred by: <strong><?php echo esc_html($ref_code); ?></strong>
            </div>
            <?php endif; ?>
            
            <form id="dltpays-signup" method="post">
                <div class="dltpays-form-group">
                    <label for="dltpays-wallet">Your XRPL Wallet</label>
                    <input 
                        type="text" 
                        id="dltpays-wallet" 
                        name="wallet_address" 
                        placeholder="rXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX" 
                        required 
                        pattern="^r[1-9A-HJ-NP-Za-km-z]{25,34}$"
                        autocomplete="off"
                    >
                    <div class="hint">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                        </svg>
                        Works with Xaman, Crossmark, or any XRPL wallet
                    </div>
                </div>
                
                <input type="hidden" id="dltpays-currency" name="currency" value="RLUSD">
                <!-- THE FIX: This hidden field sends the parent referral code -->
                <input type="hidden" name="referral_code" id="dltpays-parent-ref" value="<?php echo esc_attr($ref_code); ?>">
                
                <div class="dltpays-checkbox-group">
                    <input type="checkbox" id="dltpays-terms" name="terms" required>
                    <label for="dltpays-terms">
                        I agree to the <a href="https://yesallofus.com/affiliate-terms" target="_blank">Affiliate Terms</a> and have an RLUSD trustline
                    </label>
                </div>
                
                <button type="submit" class="dltpays-submit-btn">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/>
                    </svg>
                    Start Earning
                </button>
                
                <div class="dltpays-trustline-note">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/>
                        <line x1="12" y1="8" x2="12" y2="12"/>
                        <line x1="12" y1="16" x2="12.01" y2="16"/>
                    </svg>
                    <span>RLUSD requires a trustline. <a href="https://yesallofus.com/trustline" target="_blank">Setup guide →</a></span>
                </div>
                
                <div class="dltpays-message"></div>
            </form>
        </div>
        
        <div class="dltpays-success-card">
            <div class="dltpays-success-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <path d="M20 6L9 17l-5-5"/>
                </svg>
            </div>
            <h3>Successful!</h3>
            <p class="success-subtitle">You're now on your journey to earning passive income.</p>
            
            <div class="dltpays-link-box">
                <input type="text" id="dltpays-ref-link" readonly>
                <button type="button" class="dltpays-copy-btn" onclick="dltpaysCopyLink(this)">
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
            
            <a href="<?php echo home_url('/affiliate-dashboard'); ?>" class="dltpays-dashboard-link">
                View Dashboard
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M5 12h14M12 5l7 7-7 7"/>
                </svg>
            </a>
        </div>
    </div>
</div>

<script>
function dltpaysCopyLink(btn) {
    const input = document.getElementById('dltpays-ref-link');
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
