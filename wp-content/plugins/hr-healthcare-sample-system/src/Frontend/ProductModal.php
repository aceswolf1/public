<?php
/**
 * Product modal shell printed on tagged pages (wp_footer).
 *
 * @package HR_Healthcare\Sample_System
 */

declare(strict_types=1);

namespace HR_Healthcare\Sample_System\Frontend;

/**
 * Prints the §16 / contract.md modal DOM shell. JS mounts behavior into it.
 */
final class ProductModal {

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
	public function __construct( Assets $assets ) {
		$this->assets = $assets;
	}

	/**
	 * Register hooks.
	 */
	public function register(): void {
		add_action( 'wp_footer', array( $this, 'render' ), 20 );
	}

	/**
	 * Print the modal shell on tagged product pages only.
	 */
	public function render(): void {
		if ( null === $this->assets->page_slug() ) {
			return;
		}
		?>
		<div
			class="hrh-sample-modal"
			role="dialog"
			aria-modal="true"
			aria-labelledby="hrh-sample-modal-title"
			hidden
		>
			<div class="hrh-sample-modal__backdrop" data-close></div>
			<div class="hrh-sample-modal__dialog">
				<header class="hrh-sample-modal__header">
					<h2 id="hrh-sample-modal-title" class="hrh-sample-modal__title">
						<?php esc_html_e( 'Request a Sample', 'hr-healthcare-sample-system' ); ?>
					</h2>
					<button type="button" class="hrh-sample-modal__close" data-close aria-label="<?php esc_attr_e( 'Close', 'hr-healthcare-sample-system' ); ?>">
						&times;
					</button>
				</header>

				<ol class="hrh-sample-steps" aria-label="<?php esc_attr_e( 'Sample request steps', 'hr-healthcare-sample-system' ); ?>">
					<li class="hrh-sample-steps__item" data-step-indicator="select"><?php esc_html_e( 'Selection', 'hr-healthcare-sample-system' ); ?></li>
					<li class="hrh-sample-steps__item" data-step-indicator="review"><?php esc_html_e( 'Review', 'hr-healthcare-sample-system' ); ?></li>
					<li class="hrh-sample-steps__item" data-step-indicator="confirm"><?php esc_html_e( 'Confirmation', 'hr-healthcare-sample-system' ); ?></li>
				</ol>

				<div class="hrh-sample-modal__layout">
					<div class="hrh-sample-modal__media">
						<img src="" alt="" />
					</div>
					<div class="hrh-sample-modal__body">
						<section class="hrh-sample-step" data-step="select" aria-live="polite">
							<div class="hrh-sample-selector" data-slug=""></div>
						</section>

						<section class="hrh-sample-step" data-step="review" hidden>
							<dl class="hrh-sample-card__specs"></dl>
							<div class="hrh-sample-step__actions">
								<button type="button" class="hrh-sample-btn" data-action="back">
									<?php esc_html_e( 'Back', 'hr-healthcare-sample-system' ); ?>
								</button>
								<button type="button" class="hrh-sample-btn hrh-sample-btn--primary" data-action="add-to-cart">
									<?php esc_html_e( 'Add to Cart', 'hr-healthcare-sample-system' ); ?>
								</button>
							</div>
						</section>

						<section class="hrh-sample-step" data-step="confirm" hidden role="status">
							<div class="hrh-sample-success"></div>
							<p class="hrh-sample-success__msg">
								<?php esc_html_e( 'Sample Request has been Added to your Cart', 'hr-healthcare-sample-system' ); ?>
							</p>
							<div class="hrh-sample-step__actions">
								<button type="button" class="hrh-sample-btn" data-action="continue">
									<?php esc_html_e( 'Continue Browsing', 'hr-healthcare-sample-system' ); ?>
								</button>
								<button type="button" class="hrh-sample-btn hrh-sample-btn--primary" data-action="view-cart">
									<?php esc_html_e( 'View Cart', 'hr-healthcare-sample-system' ); ?>
								</button>
							</div>
						</section>
					</div>
				</div>

				<p class="hrh-sample-sr-only" role="status" aria-live="polite"></p>
			</div>
		</div>
		<?php
	}
}
