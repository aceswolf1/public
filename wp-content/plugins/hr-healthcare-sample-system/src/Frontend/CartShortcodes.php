<?php

/**
 * Cart shortcodes + footer cart dialog shell.
 *
 * @package HR_Healthcare\Sample_System
 */

declare(strict_types=1);

namespace HR_Healthcare\Sample_System\Frontend;

/**
 * `[hrh_sample_cart]` and `[hrh_sample_cart_summary]`. Sets an enqueue flag
 * and prints the cart dialog once on `wp_footer`.
 */
final class CartShortcodes
{

	/**
	 * Assets service.
	 *
	 * @var Assets
	 */
	private Assets $assets;

	/**
	 * Whether any cart shortcode rendered this request.
	 *
	 * @var bool
	 */
	private bool $cart_present = false;

	/**
	 * Whether the footer cart dialog has been printed.
	 *
	 * @var bool
	 */
	private bool $dialog_printed = false;

	/**
	 * Constructor.
	 *
	 * @param Assets $assets Assets service.
	 */
	public function __construct(Assets $assets)
	{
		$this->assets = $assets;
	}

	/**
	 * Register shortcodes + footer hook.
	 */
	public function register(): void
	{
		add_shortcode('hrh_sample_cart', array($this, 'render_cart_icon'));
		add_shortcode('hrh_sample_cart_summary', array($this, 'render_cart_summary'));
		add_action('wp_footer', array($this, 'render_cart_dialog'), 25);
	}

	/**
	 * `[hrh_sample_cart]` — icon + count in place; dialog via footer.
	 *
	 * @param array<string, string>|string $atts Shortcode atts.
	 */
	public function render_cart_icon($atts = array()): string
	{
		unset($atts);
		$this->flag_and_enqueue();

		ob_start();
?>
		<button
			type="button"
			class="hrh-sample-cart-icon"
			aria-haspopup="dialog"
			aria-controls="hrh-sample-cart"
			aria-label="<?php esc_attr_e('Open sample cart', 'hr-healthcare-sample-system'); ?>">
			<svg width="25" height="24" viewBox="0 0 25 24" fill="none" xmlns="http://www.w3.org/2000/svg">
				<path d="M1 1H5.16667L7.95833 14.9479C8.05359 15.4275 8.31449 15.8583 8.69536 16.1649C9.07623 16.4715 9.55281 16.6344 10.0417 16.625H20.1667C20.6555 16.6344 21.1321 16.4715 21.513 16.1649C21.8938 15.8583 22.1547 15.4275 22.25 14.9479L23.9167 6.20833H6.20833M10.375 21.8333C10.375 22.4086 9.90863 22.875 9.33333 22.875C8.75804 22.875 8.29167 22.4086 8.29167 21.8333C8.29167 21.258 8.75804 20.7917 9.33333 20.7917C9.90863 20.7917 10.375 21.258 10.375 21.8333ZM21.8333 21.8333C21.8333 22.4086 21.367 22.875 20.7917 22.875C20.2164 22.875 19.75 22.4086 19.75 21.8333C19.75 21.258 20.2164 20.7917 20.7917 20.7917C21.367 20.7917 21.8333 21.258 21.8333 21.8333Z" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
			</svg>

			<span class="hrh-sample-cart-icon__count" data-cart-count>0</span>
		</button>
	<?php
		return (string) ob_get_clean();
	}

	/**
	 * `[hrh_sample_cart_summary]` — inline order review list.
	 *
	 * @param array<string, string>|string $atts Shortcode atts.
	 */
	public function render_cart_summary($atts = array()): string
	{
		unset($atts);
		$this->flag_and_enqueue();

		ob_start();
	?>
		<div class="hrh-sample-root hrh-sample-cart-summary">
			<ul class="hrh-sample-cart__list" data-cart-summary-list></ul>
			<p class="hrh-sample-sr-only" role="status" aria-live="polite"></p>
		</div>
	<?php
		return (string) ob_get_clean();
	}

	/**
	 * Print the cart dialog once when a cart shortcode was present.
	 */
	public function render_cart_dialog(): void
	{
		if (! $this->cart_present || $this->dialog_printed) {
			return;
		}

		$this->dialog_printed = true;
	?>
		<div
			id="hrh-sample-cart"
			class="hrh-sample-cart"
			role="dialog"
			aria-modal="true"
			aria-labelledby="hrh-sample-cart-title"
			hidden>
			<div class="hrh-sample-cart__backdrop" data-close></div>
			<div class="hrh-sample-cart__dialog">
				<header class="hrh-sample-cart__header">
					<h2 id="hrh-sample-cart-title">
						<?php esc_html_e('Your Sample Cart', 'hr-healthcare-sample-system'); ?>
					</h2>
					<button type="button" class="hrh-sample-cart__close" data-close aria-label="<?php esc_attr_e('Close', 'hr-healthcare-sample-system'); ?>">
						&times;
					</button>
				</header>

				<ul class="hrh-sample-cart__list" data-cart-list></ul>

				<footer class="hrh-sample-cart__footer">
					<button type="button" class="hrh-sample-btn" data-action="continue">
						<?php esc_html_e('Continue Browsing', 'hr-healthcare-sample-system'); ?>
					</button>
					<button type="button" class="hrh-sample-btn hrh-sample-btn--primary" data-action="checkout">
						<?php esc_html_e('Proceed to Checkout', 'hr-healthcare-sample-system'); ?>
					</button>
				</footer>

				<p class="hrh-sample-sr-only" role="status" aria-live="polite"></p>
			</div>
		</div>
<?php
	}

	/**
	 * Mark cart present and enqueue frontend assets (idempotent).
	 */
	private function flag_and_enqueue(): void
	{
		$this->cart_present = true;
		$this->assets->enqueue_frontend();
	}
}
