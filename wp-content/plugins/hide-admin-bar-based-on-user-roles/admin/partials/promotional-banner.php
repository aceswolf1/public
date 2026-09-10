<?php
// Check if the banner should be hidden
$hide_banner = get_user_meta(get_current_user_id(), 'hab_hide_promo_banner', true);
$hide_until = get_user_meta(get_current_user_id(), 'hab_hide_promo_until', true);

// Show banner if not permanently hidden and not temporarily hidden or temporary period has expired
if ($hide_banner !== 'permanent' && (empty($hide_until) || time() > $hide_until)) :
?>
<div class="habur-pro-banner">
    <div class="habur-pro-banner-content">
        <div class="habur-banner-header" style="position: relative; margin-bottom: 8px;">
            <h2 style="font-size: 18px; color: #6610F2; margin-bottom: 8px; font-weight: 700; display: inline-block;">
                <?php esc_html_e('🚀 Hide Admin Bar Pro', 'hide-admin-bar-based-on-user-roles'); ?> 
                <span style="background: #FFD700; color: #000; padding: 2px 6px; border-radius: 3px; font-size: 12px; font-weight: 600; margin-left: 8px;">
                    <?php esc_html_e('Launch Offer: 30% OFF', 'hide-admin-bar-based-on-user-roles'); ?>
                </span>
            </h2>
            <div class="habur-dismiss-options" style="display: inline-block; margin-left: 10px; font-size: 11px; color: #6610F2;">
                <a href="#" class="habur-dismiss-now" data-dismiss="now" style="font-size: 11px;"><?php esc_html_e('Hide for now', 'hide-admin-bar-based-on-user-roles'); ?></a> | 
                <a href="#" class="habur-dismiss-30days" data-dismiss="30days" style="font-size: 11px;"><?php esc_html_e('Hide for 30 days', 'hide-admin-bar-based-on-user-roles'); ?></a> | 
                <a href="#" class="habur-dismiss-permanent" data-dismiss="permanent" style="font-size: 11px;"><?php esc_html_e('Never show again', 'hide-admin-bar-based-on-user-roles'); ?></a>
            </div>
        </div>
        
        <p style="font-size: 13px; margin-bottom: 8px;">
            <?php 
                esc_html_e('🚀 Over 600,000+ downloads — now go even further with Pro.', 'hide-admin-bar-based-on-user-roles');
            ?>
        </p>
        
        <div class="habur-cta-container">
            <a href="https://checkout.freemius.com/plugin/18739/plan/30986/" class="habur-upgrade-button" target="_blank">
                <?php esc_html_e('UPGRADE TO PRO NOW', 'hide-admin-bar-based-on-user-roles'); ?> <span class="dashicons dashicons-arrow-right-alt"></span>
            </a>
        </div>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    $('.habur-dismiss-now, .habur-dismiss-30days, .habur-dismiss-permanent').on('click', function(e) {
        e.preventDefault();
        
        var dismissType = $(this).data('dismiss');
        var bannerElement = $(this).closest('.habur-pro-banner');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'hab_dismiss_promotional_banner',
                dismiss_type: dismissType,
                nonce: '<?php echo wp_create_nonce("hab_dismiss_promo_nonce"); ?>'
            },
            success: function(response) {
                if (response.success) {
                    bannerElement.slideUp(300);
                }
            }
        });
    });
});
</script>
<?php endif; ?>

<style>
/* Pro Banner Styles */
.habur-pro-banner {
    background: linear-gradient(to right, #f8f9fa, #ffffff);
    border: 1px solid #e2e4e7;
    border-left: 3px solid #6610F2;
    border-radius: 6px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
    margin: 5px 0 10px;
    padding: 8px;
    width: 100%;
    max-width: 100%;
}

.habur-pro-banner-content {
    display: flex;
    flex-direction: column;
}

.habur-upgrade-button {
    display: inline-block;
    background-color: #6610F2;
    color: #fff;
    text-decoration: none;
    font-size: 12px;
    font-weight: 500;
    padding: 6px 14px;
    border-radius: 4px;
    margin-top: 5px;
    align-self: flex-start;
    transition: background-color 0.2s ease;
}

.habur-upgrade-button:hover {
    background-color: #5a0fc7;
    color: #fff;
}

</style>