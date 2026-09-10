<?php
// Check if the review banner should be hidden
$hide_review_banner = get_user_meta(get_current_user_id(), 'hab_hide_review_banner', true);
$hide_review_until = get_user_meta(get_current_user_id(), 'hab_hide_review_until', true);

// Get the plugin installation date
$plugin_install_date = get_option('hab_plugin_install_date');
if (!$plugin_install_date) {
    // If no install date is set, set it to now
    $plugin_install_date = time();
    update_option('hab_plugin_install_date', $plugin_install_date);
}

// Calculate days since installation
$days_since_install = floor((time() - $plugin_install_date) / DAY_IN_SECONDS);

// Show banner if:
// 1. Not permanently hidden
// 2. Not temporarily hidden or temporary period has expired
// 3. At least 7 days have passed since installation
if ($hide_review_banner !== 'permanent' && 
    (empty($hide_review_until) || time() > $hide_review_until) &&
    $days_since_install >= 7) :
?>
<div class="habur-review-banner">
    <div class="habur-review-banner-content">
        <div class="habur-banner-header" style="position: relative; margin-bottom: 8px;">
            <h2 style="font-size: 18px; color: #6610F2; margin-bottom: 8px; font-weight: 700; display: inline-block;">
                <?php esc_html_e('⭐ Enjoying Hide Admin Bar Based On User Roles?', 'hide-admin-bar-based-on-user-roles'); ?>
            </h2>
            <div class="habur-review-dismiss-options" style="display: inline-block; margin-left: 10px; font-size: 11px; color: #6610F2;">
                <a href="#" class="habur-review-dismiss-now" data-dismiss="now" style="font-size: 11px;"><?php esc_html_e('Hide for now', 'hide-admin-bar-based-on-user-roles'); ?></a> | 
                <a href="#" class="habur-review-dismiss-30days" data-dismiss="30days" style="font-size: 11px;"><?php esc_html_e('Hide for 30 days', 'hide-admin-bar-based-on-user-roles'); ?></a> | 
                <a href="#" class="habur-review-dismiss-permanent" data-dismiss="permanent" style="font-size: 11px;"><?php esc_html_e('Never show again', 'hide-admin-bar-based-on-user-roles'); ?></a>
            </div>
        </div>
        
        <p style="font-size: 13px; margin-bottom: 8px;">
            <?php esc_html_e('If you find this plugin helpful, please consider leaving a review. Your feedback helps us improve and helps other users discover the plugin.', 'hide-admin-bar-based-on-user-roles'); ?>
        </p>
        
        <div class="habur-cta-container">
            <a href="https://wordpress.org/support/plugin/hide-admin-bar-based-on-user-roles/reviews/#new-post" class="habur-review-button" target="_blank">
                <?php esc_html_e('LEAVE A REVIEW', 'hide-admin-bar-based-on-user-roles'); ?> <span class="dashicons dashicons-star-filled"></span>
            </a>
        </div>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    $('.habur-review-dismiss-now, .habur-review-dismiss-30days, .habur-review-dismiss-permanent').on('click', function(e) {
        e.preventDefault();
        
        var reviewDismissType = $(this).data('dismiss');
        var reviewBannerElement = $(this).closest('.habur-review-banner');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'hab_dismiss_review_banner',
                dismiss_type: reviewDismissType,
                nonce: '<?php echo wp_create_nonce("hab_dismiss_review_nonce"); ?>'
            },
            success: function(response) {
                if (response.success) {
                    reviewBannerElement.slideUp(300);
                }
            }
        });
    });
});
</script>

<style>
/* Review Banner Styles */
.habur-review-banner {
    background: linear-gradient(to right, #f8f9fa, #ffffff);
    border: 1px solid #e2e4e7;
    border-left: 3px solid #FFD700;
    border-radius: 6px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
    margin: 5px 0 10px;
    padding: 8px;
    width: 100%;
    max-width: 100%;
}

.habur-review-banner-content {
    display: flex;
    flex-direction: column;
}

.habur-review-button {
    display: inline-block;
    background-color: #FFD700;
    color: #000;
    text-decoration: none;
    font-size: 12px;
    font-weight: 500;
    padding: 6px 14px;
    border-radius: 4px;
    margin-top: 5px;
    align-self: flex-start;
    transition: background-color 0.2s ease;
}

.habur-review-button:hover {
    background-color: #FFC000;
    color: #000;
}
</style>
<?php endif; ?> 