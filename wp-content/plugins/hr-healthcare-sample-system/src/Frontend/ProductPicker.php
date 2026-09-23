<?php

/**
 * [hrh_sample_picker] shortcode — inline section-swap picker.
 *
 * @package HR_Healthcare\Sample_System
 */

declare(strict_types=1);

namespace HR_Healthcare\Sample_System\Frontend;

use HR_Healthcare\Sample_System\Support\ImageMap;

/**
 * Renders the 3-step picker HTML inline (no modal overlay).
 * Designed to live inside an Elementor section classed
 * `hrh-sample-picker-section` that starts hidden; the trigger button section
 * carries `hrh-sample-trigger-section`. JS in picker.js swaps them.
 *
 * Usage: [hrh_sample_picker]
 * Override: [hrh_sample_picker group="my-slug"]
 */
final class ProductPicker
{

	/**
	 * Assets service.
	 *
	 * @var Assets
	 */
	private Assets $assets;

	/**
	 * @param Assets $assets Assets service.
	 */
	public function __construct(Assets $assets)
	{
		$this->assets = $assets;
	}

	/**
	 * Register shortcode.
	 */
	public function register(): void
	{
		add_shortcode('hrh_sample_picker', array($this, 'render'));
	}

	/**
	 * Render the inline picker HTML.
	 *
	 * @param array<string, string>|string $atts Shortcode attributes.
	 * @return string HTML or empty string when no group can be resolved.
	 */
	public function render($atts = array()): string
	{
		$atts = shortcode_atts(array('group' => ''), $atts, 'hrh_sample_picker');
		$slug = sanitize_title((string) $atts['group']);

		// Fall back to the page's _hrh_sample_group_tag meta (same source as modal).
		if ('' === $slug) {
			$post_id = get_queried_object_id();
			if ($post_id > 0) {
				$meta = get_post_meta($post_id, ImageMap::META_KEY, true);
				$slug = is_string($meta) ? sanitize_title($meta) : '';
			}
		}

		if ('' === $slug) {
			return '';
		}

		$this->assets->enqueue_frontend();

		// Localize only when the wp-action detection didn't already do it
		// (e.g. shortcode is on a page that lacks the meta but uses the attribute).
		if (null === $this->assets->page_slug()) {
			$this->assets->localize_page_config($slug);
		}

		$hrh_product_title = get_the_title(get_queried_object_id());

		ob_start();
?>
		<div class="hrh-sample-picker hrh-sample-root hrh-sample-modal" data-slug="<?php echo esc_attr($slug); ?>">
			<div class="hrh-sample-modal__dialog">
				<button type="button" class="hrh-sample-modal__close" data-close aria-label="<?php esc_attr_e('Close', 'hr-healthcare-sample-system'); ?>">
					&times;
				</button>

				<div class="hrh-sample-modal__media">
					<img src="" alt="" />
				</div>

				<div class="hrh-sample-modal__content">
					<h2 id="hrh-sample-modal-title" class="hrh-sample-modal__title">
						<?php
						echo esc_html(
							'' !== $hrh_product_title
								? $hrh_product_title
								: __('Request a Sample', 'hr-healthcare-sample-system')
						);
						?>
					</h2>

					<div class="hrh-sample-modal__options">
						<ol class="hrh-sample-steps" aria-label="<?php esc_attr_e('Sample request steps', 'hr-healthcare-sample-system'); ?>">
							<li class="hrh-sample-steps__item" data-step-indicator="select"><?php esc_html_e('Selection', 'hr-healthcare-sample-system'); ?></li>
							<li class="hrh-sample-steps__item" data-step-indicator="review"><?php esc_html_e('Review', 'hr-healthcare-sample-system'); ?></li>
							<li class="hrh-sample-steps__item" data-step-indicator="confirm"><?php esc_html_e('Confirmation', 'hr-healthcare-sample-system'); ?></li>
						</ol>

						<div class="hrh-sample-modal__body">
							<section class="hrh-sample-step hrh-sample-step__select" data-step="select" aria-live="polite">
								<div class="hrh-sample-selector" data-slug=""></div>
							</section>

							<section class="hrh-sample-step hrh-sample-step__review" data-step="review" hidden>
								<p class="hrh-sample-step__notice" hidden></p>
								<dl class="hrh-sample-card__specs"></dl>
								<div class="hrh-sample-step__actions">
									<button type="button" class="hrh-sample-btn" data-action="back">
										<?php esc_html_e('Back', 'hr-healthcare-sample-system'); ?>
									</button>
									<button type="button" class="hrh-sample-btn hrh-sample-btn--primary" data-action="add-to-cart">
										<?php esc_html_e('Add to Cart', 'hr-healthcare-sample-system'); ?>
									</button>
								</div>
							</section>

							<section class="hrh-sample-step hrh-sample-step__confirmation" data-step="confirm" hidden role="status">
								<div class="hrh-sample-success">
									<svg width="142" height="142" viewBox="0 0 142 142" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
										<circle cx="71" cy="71" r="71" fill="#00B189" />
										<path d="M34.2319 74.9844L56.2926 96.9908L107.768 45.6426" stroke="white" stroke-width="10" />
									</svg>
								</div>
								<p class="hrh-sample-success__msg">
									<?php esc_html_e('Sample Request has been Added to your Cart', 'hr-healthcare-sample-system'); ?>
								</p>
								<div class="hrh-sample-step__actions">
									<button type="button" class="hrh-sample-btn" data-action="continue">
										<?php esc_html_e('Continue Browsing', 'hr-healthcare-sample-system'); ?>
									</button>
									<button type="button" class="hrh-sample-btn hrh-sample-btn--primary" data-action="view-cart">
										<?php esc_html_e('View Cart', 'hr-healthcare-sample-system'); ?>
									</button>
								</div>
							</section>
						</div>
					</div>
				</div>
			</div>

			<p class="hrh-sample-sr-only" role="status" aria-live="polite"></p>
		</div>
<?php
		return (string) ob_get_clean();
	}
}
