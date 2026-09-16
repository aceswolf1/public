<?php

/**
 * Product modal shell printed on tagged pages (wp_footer).
 *
 * @package HR_Healthcare\Sample_System
 */

declare(strict_types=1);

namespace HR_Healthcare\Sample_System\Frontend;

/**
 * Prints the modal DOM shell. JS mounts behavior into it.
 *
 * Structure (general layout — CSS authored separately):
 *   .hrh-sample-modal                     (main container / dialog root)
 *     .hrh-sample-modal__backdrop
 *     .hrh-sample-modal__media            (image container)
 *     .hrh-sample-modal__content          (content container)
 *       .hrh-sample-modal__title
 *       .hrh-sample-modal__options        (options container)
 *         .hrh-sample-steps               (step indicator)
 *         .hrh-sample-modal__body         (options list / step panels)
 *
 * Each step owns its own action row: the Selection step's SKU + "Finish
 * Selection" foot is rendered by PropertySelector inside .hrh-sample-selector;
 * Review and Confirmation have their own .hrh-sample-step__actions.
 */
final class ProductModal
{

	/**
	 * Assets service.
	 *
	 * @var Assets
	 */
	private Assets $assets;

	/**
	 * Constructor.
	 *
	 * @param Assets $assets Assets service (for page-tag detection).
	 */
	public function __construct(Assets $assets)
	{
		$this->assets = $assets;
	}

	/**
	 * Register hooks.
	 */
	public function register(): void
	{
		add_action('wp_footer', array($this, 'render'), 20);
	}

	/**
	 * Print the modal shell on tagged product pages only.
	 */
	public function render(): void
	{
		if (null === $this->assets->page_slug()) {
			return;
		}
?>
		<div
			class="hrh-sample-modal"
			role="dialog"
			aria-modal="true"
			aria-labelledby="hrh-sample-modal-title"
			hidden>
			<div class="hrh-sample-modal__backdrop" data-close></div>

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
						$hrh_product_title = get_the_title(get_queried_object_id());
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
									<svg width="142" height="142" viewBox="0 0 142 142" fill="none" xmlns="http://www.w3.org/2000/svg">
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
	}
}
