/**
 * DLTPays Frontend JavaScript
 */

(function($) {
    'use strict';
    
    // Affiliate signup form
    $('#dltpays-signup').on('submit', function(e) {
        e.preventDefault();
        
        const $form = $(this);
        const $submit = $form.find('.dltpays-submit');
        const $message = $form.find('.dltpays-message');
        
        // Validate wallet address
        const wallet = $form.find('[name="wallet_address"]').val().trim();
        if (!wallet.match(/^r[1-9A-HJ-NP-Za-km-z]{25,34}$/)) {
            showMessage($message, 'Please enter a valid XRPL wallet address', 'error');
            return;
        }
        
        $submit.prop('disabled', true).text('Registering...');
        $message.hide();
        
        $.ajax({
            url: dltpays.ajax_url,
            type: 'POST',
            data: {
    action: 'dltpays_register_affiliate',
    nonce: dltpays.nonce,
    wallet_address: wallet,
    currency: $form.find('[name="currency"]').val(),
    referral_code: $form.find('[name="referral_code"]').val()
},
            success: function(response) {
                if (response.success) {
                    $('.dltpays-form-state').hide();
                    $('.dltpays-success-card')
                        .show()
                        .find('#dltpays-ref-link')
                        .val(response.data.referral_link);
                } else {
                    showMessage($message, response.data.message, 'error');
                    $submit.prop('disabled', false).text('Start Earning');
                }
            },
            error: function() {
                showMessage($message, 'Something went wrong. Please try again.', 'error');
                $submit.prop('disabled', false).text('Start Earning');
            }
        });
    });
    
    // Currency change - show trustline notice for RLUSD
    $('#dltpays-currency').on('change', function() {
        const $notice = $('#dltpays-trustline-notice');
        if ($(this).val() === 'RLUSD') {
            $notice.slideDown();
        } else {
            $notice.slideUp();
        }
    });
    
    // Check trustline when wallet entered
    let trustlineCheckTimeout;
    $('[name="wallet_address"]').on('input', function() {
        const wallet = $(this).val().trim();
        const $notice = $('#dltpays-trustline-notice');
        
        clearTimeout(trustlineCheckTimeout);
        
        if (wallet.match(/^r[1-9A-HJ-NP-Za-km-z]{25,34}$/) && $('#dltpays-currency').val() === 'RLUSD') {
            trustlineCheckTimeout = setTimeout(function() {
                checkTrustline(wallet);
            }, 500);
        }
    });
    
    function checkTrustline(wallet) {
        // Optional: Check trustline via API
        // For now, just show the notice
        $('#dltpays-trustline-notice').show();
    }
    
    function showMessage($el, message, type) {
        $el.removeClass('error success')
           .addClass(type)
           .text(message)
           .slideDown();
    }
    
    // Copy referral link
    window.dltpaysCopyLink = function() {
        const input = document.getElementById('dltpays-ref-link');
        if (input) {
            input.select();
            input.setSelectionRange(0, 99999);
            
            if (navigator.clipboard) {
                navigator.clipboard.writeText(input.value);
            } else {
                document.execCommand('copy');
            }
            
            // Show feedback
            const btn = input.nextElementSibling;
            const originalText = btn.textContent;
            btn.textContent = 'Copied!';
            setTimeout(() => {
                btn.textContent = originalText;
            }, 2000);
        }
    };
    
    // Dashboard - copy link
    $(document).on('click', '[data-copy]', function() {
        const text = $(this).data('copy');
        navigator.clipboard.writeText(text).then(() => {
            const $btn = $(this);
            const originalText = $btn.text();
            $btn.text('Copied!');
            setTimeout(() => $btn.text(originalText), 2000);
        });
    });
    
})(jQuery);
