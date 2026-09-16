/**
 * Cart state machine — cookie selections + LineItemCard surfaces.
 * Guide §6 / §16 / §17. Cookie = selections only (`hrh_sample_cart`).
 */

import { announce, closeDialog, openDialog } from './a11y.js';
import {
	ensureGroups,
	getBoot,
	getCached,
	seedFromLocalized,
} from './api.js';
import { el } from './dom.js';
import { buildSpecData, resolve } from './resolver.js';
import { mountPropertySelector } from './components/PropertySelector.js';
import {
	getLineEditMount,
	renderLineItemCard,
	setLineEditOpen,
	updateLineItemCard,
} from './components/LineItemCard.js';

export const COOKIE_NAME = 'hrh_sample_cart';
export const CART_CHANGED_EVENT = 'hrh-sample:cart-changed';

/** @typedef {import('./resolver.js').GroupConfig} GroupConfig */
/** @typedef {{ slug: string, options: Record<string, string> }} CartSelection */

/** @type {WeakMap<HTMLElement, ReturnType<typeof mountPropertySelector>>} */
const editControllers = new WeakMap();

/**
 * @returns {CartSelection[]}
 */
export function readSelections() {
	if (typeof document === 'undefined') {
		return [];
	}
	const raw = document.cookie
		.split(';')
		.map((part) => part.trim())
		.find((part) => part.startsWith(`${COOKIE_NAME}=`));
	if (!raw) {
		return [];
	}
	try {
		const parsed = JSON.parse(decodeURIComponent(raw.slice(COOKIE_NAME.length + 1)));
		if (!Array.isArray(parsed)) {
			return [];
		}
		return parsed
			.filter((line) => line && typeof line.slug === 'string')
			.map((line) => ({
				slug: String(line.slug),
				options: { ...(line.options || {}) },
			}));
	} catch {
		return [];
	}
}

/**
 * @param {CartSelection[]} selections
 */
export function writeSelections(selections) {
	const safe = (selections || []).map((line) => ({
		slug: String(line.slug),
		options: { ...(line.options || {}) },
	}));
	const value = encodeURIComponent(JSON.stringify(safe));
	const maxAge = 60 * 60 * 24 * 30; // 30 days
	document.cookie = `${COOKIE_NAME}=${value}; path=/; max-age=${maxAge}; SameSite=Lax`;
	dispatchCartChanged(safe);
}

/**
 * @param {CartSelection[]} selections
 */
function dispatchCartChanged(selections) {
	if (typeof window === 'undefined') {
		return;
	}
	window.dispatchEvent(
		new CustomEvent(CART_CHANGED_EVENT, {
			detail: { selections, count: selections.length },
		})
	);
}

/**
 * @returns {number}
 */
export function getCartCount() {
	return readSelections().length;
}

/**
 * Resolve every selection against cached configs.
 * @param {CartSelection[]} [selections]
 * @returns {Array<{
 *   selection: CartSelection,
 *   config: GroupConfig|null,
 *   resolved: ReturnType<typeof resolve>,
 *   stale: boolean
 * }>}
 */
export function resolveSelections(selections = readSelections()) {
	return selections.map((selection) => {
		const config = getCached(selection.slug);
		const resolved = config
			? resolve(config, selection.options || {})
			: { valid: false, sku: null, product_name: null, hcpcs: null, sample_amount: null };
		return {
			selection,
			config,
			resolved,
			stale: !config || !resolved.valid,
		};
	});
}

/**
 * Duplicate-SKU + cart-cap gate used by modal Review "Add to Cart".
 * Requires configs for existing cookie lines to already be cached
 * (caller should `ensureGroups` first).
 *
 * @param {string} slug
 * @param {Record<string, string>} options
 * @param {GroupConfig} config
 * @returns {{ ok: true, resolved: ReturnType<typeof resolve> } | { ok: false, reason: 'invalid'|'duplicate'|'cap', sku?: string|null, cap?: number }}
 */
export function canAddSelection(slug, options, config) {
	const resolved = resolve(config, options || {});
	if (!resolved.valid || !resolved.sku) {
		return { ok: false, reason: 'invalid', sku: null };
	}

	const existing = resolveSelections();
	const duplicate = existing.find(
		(line) => !line.stale && line.resolved.sku === resolved.sku
	);
	if (duplicate) {
		return { ok: false, reason: 'duplicate', sku: resolved.sku };
	}

	const { cartCap } = getBoot();
	if (readSelections().length >= cartCap) {
		return { ok: false, reason: 'cap', cap: cartCap, sku: resolved.sku };
	}

	return { ok: true, resolved };
}

/**
 * Commit a selection to the cookie (after canAddSelection).
 * @param {string} slug
 * @param {Record<string, string>} options
 */
export function addSelection(slug, options) {
	const next = [
		...readSelections(),
		{ slug: String(slug), options: { ...(options || {}) } },
	];
	writeSelections(next);
	return next;
}

/**
 * Remove the line at index (or matching slug+sku when provided).
 * @param {{ index?: number, slug?: string, sku?: string }} target
 */
export function removeSelection(target = {}) {
	const current = readSelections();
	let next = current;

	if (typeof target.index === 'number') {
		next = current.filter((_, idx) => idx !== target.index);
	} else if (target.slug) {
		const resolved = resolveSelections(current);
		next = current.filter((line, idx) => {
			if (line.slug !== target.slug) {
				return true;
			}
			if (!target.sku) {
				return false;
			}
			return resolved[idx]?.resolved?.sku !== target.sku;
		});
	}

	writeSelections(next);
	return next;
}

/**
 * Replace a line's options (edit-in-place save).
 * @param {number} index
 * @param {Record<string, string>} options
 */
export function updateSelection(index, options) {
	const current = readSelections();
	if (index < 0 || index >= current.length) {
		return current;
	}
	const next = current.map((line, idx) =>
		idx === index
			? { slug: line.slug, options: { ...(options || {}) } }
			: line
	);
	writeSelections(next);
	return next;
}

/**
 * @param {HTMLElement|null} root
 * @param {string} message
 */
function liveAnnounce(root, message) {
	const region = root?.querySelector('[role="status"][aria-live], .hrh-sample-sr-only[role="status"]');
	announce(region || null, message);
}

/**
 * Update every cart-count badge on the page.
 */
export function refreshCartCounts() {
	const count = getCartCount();
	document.querySelectorAll('.hrh-sample-cart-icon__count').forEach((badge) => {
		badge.textContent = String(count);
		badge.setAttribute('aria-label', `${count} item${count === 1 ? '' : 's'} in cart`);
		badge.hidden = count === 0;
	});
}

/**
 * @param {HTMLElement} list
 * @param {Array<ReturnType<typeof resolveSelections>[number]>} lines
 * @param {{ showActions?: boolean, autoEditStale?: boolean }} [opts]
 */
function renderLines(list, lines, opts = {}) {
	const showActions = opts.showActions !== false;
	const placeholder =
		(typeof window !== 'undefined' && window.HRH_SAMPLE_PLACEHOLDER_IMAGE) || '';

	const nodes = lines.map((line, index) => {
		const sku = line.resolved.sku || `pending-${index}`;
		const productName = line.resolved.product_name || line.config?.product_line || line.selection.slug;
		const imageUrl = line.config?.image_url || placeholder;
		const spec = line.config
			? buildSpecData(line.config, line.selection.options, line.resolved)
			: {
					product_name: productName,
					sku: line.resolved.sku || '',
					hcpcs: '',
					sample_amount: '',
					properties: [],
				};

		const li = renderLineItemCard({
			slug: line.selection.slug,
			sku,
			imageUrl,
			productName,
			spec,
			stale: line.stale,
			editId: `edit-${line.selection.slug}-${index}`,
			showActions,
		});
		li.setAttribute('data-index', String(index));
		return li;
	});

	list.replaceChildren(...nodes);

	if (opts.autoEditStale) {
		list.querySelectorAll('.hrh-sample-line.is-stale').forEach((li) => {
			openInlineEdit(/** @type {HTMLElement} */ (li), list);
		});
	}
}

/**
 * @param {HTMLElement} list
 * @returns {HTMLElement|null}
 */
function ensureStatusBanner(list) {
	const parent = list.parentElement;
	if (!parent) {
		return null;
	}
	let banner = parent.querySelector('.hrh-sample-cart__status');
	if (!banner) {
		banner = el('div', {
			className: 'hrh-sample-cart__status',
			role: 'status',
			hidden: true,
		});
		parent.insertBefore(banner, list);
	}
	return /** @type {HTMLElement} */ (banner);
}

/**
 * @param {HTMLElement} banner
 * @param {'loading'|'error'|'empty'|null} state
 * @param {{ onRetry?: () => void, message?: string }} [opts]
 */
function setBannerState(banner, state, opts = {}) {
	if (!banner) {
		return;
	}
	if (!state) {
		banner.hidden = true;
		banner.replaceChildren();
		return;
	}

	banner.hidden = false;
	if (state === 'loading') {
		banner.replaceChildren(el('p', { text: 'Loading cart…' }));
		return;
	}
	if (state === 'empty') {
		banner.replaceChildren(el('p', { text: 'Your sample cart is empty.' }));
		return;
	}
	if (state === 'error') {
		const retry = el('button', {
			type: 'button',
			className: 'hrh-sample-btn hrh-sample-btn--primary',
			text: 'Retry',
		});
		retry.addEventListener('click', () => opts.onRetry?.());
		banner.replaceChildren(
			el('p', {
				text: opts.message || 'We could not load your cart. Please try again.',
			}),
			retry
		);
	}
}

/**
 * Load configs for cookie slugs, resolve, render into a list.
 * Never paints "empty" on network error — shows retry instead.
 *
 * @param {HTMLElement} list
 * @param {{ showActions?: boolean, autoEditStale?: boolean, root?: HTMLElement|null }} [opts]
 */
export async function renderCartList(list, opts = {}) {
	if (!list) {
		return;
	}

	const banner = ensureStatusBanner(list);
	const selections = readSelections();

	if (selections.length === 0) {
		list.replaceChildren();
		setBannerState(banner, 'empty');
		return;
	}

	setBannerState(banner, 'loading');

	const slugs = selections.map((line) => line.slug);
	try {
		await ensureGroups(slugs);
	} catch {
		// Keep any previously rendered lines; never claim the cart is empty.
		setBannerState(banner, 'error', {
			message: 'We could not load product details for your cart.',
			onRetry: () => {
				renderCartList(list, opts);
			},
		});
		liveAnnounce(opts.root || list.closest('.hrh-sample-cart, .hrh-sample-root'), 'Cart failed to load. Retry available.');
		return;
	}

	setBannerState(banner, null);
	const lines = resolveSelections(selections);
	renderLines(list, lines, {
		showActions: opts.showActions,
		autoEditStale: opts.autoEditStale !== false,
	});

	const staleCount = lines.filter((line) => line.stale).length;
	if (staleCount > 0) {
		liveAnnounce(
			opts.root || list.closest('.hrh-sample-cart, .hrh-sample-root'),
			`${staleCount} item${staleCount === 1 ? '' : 's'} need${staleCount === 1 ? 's' : ''} re-selection.`
		);
	}
}

/**
 * @param {HTMLElement} li
 * @param {HTMLElement} list
 */
function openInlineEdit(li, list) {
	const index = Number(li.getAttribute('data-index'));
	const selections = readSelections();
	const selection = selections[index];
	if (!selection) {
		return;
	}

	const config = getCached(selection.slug);
	const mount = getLineEditMount(li);
	if (!config || !mount) {
		return;
	}

	const existing = editControllers.get(li);
	existing?.destroy?.();

	const controller = mountPropertySelector(mount, {
		config,
		initialOptions: selection.options,
		showAdvance: false,
		namePrefix: `edit-${selection.slug}-${index}`,
		announce: (message) => liveAnnounce(list.closest('.hrh-sample-cart, .hrh-sample-root'), message),
	});
	editControllers.set(li, controller);
	setLineEditOpen(li, true);
}

/**
 * @param {HTMLElement} li
 */
function closeInlineEdit(li) {
	const controller = editControllers.get(li);
	controller?.destroy?.();
	editControllers.delete(li);
	setLineEditOpen(li, false);
}

/**
 * @param {HTMLElement} li
 * @param {HTMLElement} list
 * @param {HTMLElement|null} root
 */
function saveInlineEdit(li, list, root) {
	const index = Number(li.getAttribute('data-index'));
	const controller = editControllers.get(li);
	if (!controller) {
		return;
	}

	const options = controller.getOptions();
	const resolved = controller.getResolved();
	if (!resolved.valid) {
		liveAnnounce(root, 'Invalid combination. Please adjust your selection.');
		return;
	}

	// Block saving into a duplicate SKU of another line.
	const selections = readSelections();
	const slug = selections[index]?.slug;
	const config = slug ? getCached(slug) : null;
	if (!config) {
		liveAnnounce(root, 'Unable to update this item right now.');
		return;
	}

	const others = resolveSelections(selections).filter((_, idx) => idx !== index);
	if (others.some((line) => !line.stale && line.resolved.sku === resolved.sku)) {
		liveAnnounce(root, `SKU ${resolved.sku} is already in your cart.`);
		return;
	}

	updateSelection(index, options);
	closeInlineEdit(li);
	renderCartList(list, { root, autoEditStale: true });
	liveAnnounce(root, 'Item updated.');
}

/**
 * Wire list-level edit/cancel actions (dialog + summary share this).
 * @param {HTMLElement} list
 * @param {{ root?: HTMLElement|null }} [opts]
 */
export function bindCartListActions(list, opts = {}) {
	if (!list || list.dataset.hrhBound === '1') {
		return;
	}
	list.dataset.hrhBound = '1';

	list.addEventListener('click', (event) => {
		const target = /** @type {HTMLElement} */ (event.target);
		const actionEl = target.closest('[data-action]');
		if (!actionEl || !list.contains(actionEl)) {
			return;
		}
		const li = /** @type {HTMLElement|null} */ (actionEl.closest('.hrh-sample-line'));
		if (!li) {
			return;
		}

		const action = actionEl.getAttribute('data-action');
		const root = opts.root || list.closest('.hrh-sample-cart, .hrh-sample-root');

		if (action === 'edit') {
			event.preventDefault();
			openInlineEdit(li, list);
			return;
		}
		if (action === 'edit-cancel') {
			event.preventDefault();
			closeInlineEdit(li);
			return;
		}
		if (action === 'edit-save') {
			event.preventDefault();
			saveInlineEdit(li, list, root);
			return;
		}
		if (action === 'cancel') {
			event.preventDefault();
			const index = Number(li.getAttribute('data-index'));
			removeSelection({ index });
			renderCartList(list, { root, autoEditStale: true });
			liveAnnounce(root, 'Item removed.');
		}
	});
}

/**
 * Sync the Proceed-to-Checkout button from boot.checkoutUrl.
 * Disabled when unset/trashed (PHP localizes null/empty).
 * @param {HTMLElement|null} dialog
 */
export function syncCheckoutButton(dialog) {
	const btn = dialog?.querySelector('[data-action="checkout"]');
	if (!btn) {
		return;
	}
	const { checkoutUrl } = getBoot();
	if (checkoutUrl) {
		btn.removeAttribute('disabled');
		btn.setAttribute('data-href', checkoutUrl);
		btn.removeAttribute('aria-disabled');
		btn.removeAttribute('title');
	} else {
		btn.setAttribute('disabled', '');
		btn.setAttribute('aria-disabled', 'true');
		btn.removeAttribute('data-href');
		btn.setAttribute('title', 'Checkout page is not configured.');
	}
}

/**
 * Open the cart dialog, fetch configs, render.
 * @param {HTMLElement} dialog
 * @param {{ trigger?: Element|null }} [opts]
 */
export async function openCart(dialog, opts = {}) {
	if (!dialog) {
		return;
	}

	seedFromLocalized();
	syncCheckoutButton(dialog);
	openDialog(dialog, { trigger: opts.trigger || null });

	const list = dialog.querySelector('.hrh-sample-cart__list');
	if (list) {
		bindCartListActions(/** @type {HTMLElement} */ (list), { root: dialog });
		await renderCartList(/** @type {HTMLElement} */ (list), {
			root: dialog,
			autoEditStale: true,
		});
	}
}

/**
 * @param {HTMLElement} dialog
 */
export function closeCart(dialog) {
	closeDialog(dialog);
}

/**
 * Hydrate every `[hrh_sample_cart_summary]` list on the page.
 */
export async function hydrateSummaries() {
	seedFromLocalized();
	const lists = document.querySelectorAll(
		'.hrh-sample-cart-summary .hrh-sample-cart__list, [data-hrh-sample-summary] .hrh-sample-cart__list'
	);
	// Also accept bare summary lists marked by the shortcode wrapper.
	const extra = document.querySelectorAll('.hrh-sample-summary .hrh-sample-cart__list');
	const all = new Set([
		...Array.from(lists),
		...Array.from(extra),
		...Array.from(document.querySelectorAll('ul.hrh-sample-cart__list')).filter(
			(node) => !node.closest('.hrh-sample-cart')
		),
	]);

	await Promise.all(
		Array.from(all).map(async (list) => {
			bindCartListActions(/** @type {HTMLElement} */ (list), {
				root: list.closest('.hrh-sample-root') || list.parentElement,
			});
			await renderCartList(/** @type {HTMLElement} */ (list), {
				root: list.closest('.hrh-sample-root') || list.parentElement,
				autoEditStale: true,
			});
		})
	);
}

/**
 * Document-level cart wiring: icon open, dialog actions, Esc, count refresh.
 */
/**
 * Populate the Gravity Forms hidden "selections" field on the checkout page.
 *
 * The field carries the marker class `hrh-sample-field-selections` (on the GF
 * field container or the input itself). We write the cart cookie's selections
 * JSON into it so the GF submit hook can validate + resolve server-side. Only
 * this field is frontend-populated; `-readable` / `-json` are written by the
 * server hook at submit. No-ops on any page without the field.
 */
export function initCheckout() {
	if (typeof document === 'undefined') {
		return;
	}

	const MARKER = 'hrh-sample-field-selections';

	const findInput = () => {
		const onInput = document.querySelector(
			`input.${MARKER}, textarea.${MARKER}, select.${MARKER}`
		);
		if (onInput) {
			return onInput;
		}
		const holder = document.querySelector(`.${MARKER}`);
		if (!holder) {
			return null;
		}
		return holder.matches('input, textarea, select')
			? holder
			: holder.querySelector('input, textarea, select');
	};

	const populate = () => {
		const input = findInput();
		if (!input) {
			return;
		}
		input.value = JSON.stringify(readSelections());
	};

	// Nothing to do if this page has no checkout selections field.
	if (!findInput()) {
		return;
	}

	populate();
	window.addEventListener(CART_CHANGED_EVENT, populate);

	// Re-sync immediately before submit (capture phase runs before GF's own
	// handler, covering both standard and GF-AJAX submissions).
	document.addEventListener(
		'submit',
		(event) => {
			const form = /** @type {HTMLElement} */ (event.target);
			if (form && typeof form.querySelector === 'function' && form.querySelector(`.${MARKER}`)) {
				populate();
			}
		},
		true
	);

	// On a successful AJAX confirmation, empty the cart + refresh the UI live
	// (the server also clears the cookie; this updates the header count without
	// a reload). Standard (non-AJAX) submits are covered by the server clear.
	if (window.jQuery) {
		window.jQuery(document).on('gform_confirmation_loaded', () => {
			writeSelections([]);
		});
	}
}

export function initCart() {
	seedFromLocalized();
	refreshCartCounts();

	window.addEventListener(CART_CHANGED_EVENT, () => {
		refreshCartCounts();
		// Re-render open dialog + summaries so surfaces stay in sync.
		const openDialogEl = document.querySelector('.hrh-sample-cart:not([hidden])');
		if (openDialogEl) {
			const list = openDialogEl.querySelector('.hrh-sample-cart__list');
			if (list) {
				renderCartList(/** @type {HTMLElement} */ (list), {
					root: /** @type {HTMLElement} */ (openDialogEl),
					autoEditStale: true,
				});
			}
		}
		hydrateSummaries();
	});

	document.addEventListener('click', (event) => {
		const target = /** @type {HTMLElement} */ (event.target);

		const icon = target.closest('.hrh-sample-cart-icon');
		if (icon) {
			event.preventDefault();
			const controls = icon.getAttribute('aria-controls') || 'hrh-sample-cart';
			const dialog = document.getElementById(controls) || document.querySelector('.hrh-sample-cart');
			if (dialog) {
				openCart(/** @type {HTMLElement} */ (dialog), { trigger: icon });
			}
			return;
		}

		const closeEl = target.closest('.hrh-sample-cart [data-close]');
		if (closeEl) {
			event.preventDefault();
			const dialog = closeEl.closest('.hrh-sample-cart');
			if (dialog) {
				closeCart(/** @type {HTMLElement} */ (dialog));
			}
			return;
		}

		const actionEl = target.closest('.hrh-sample-cart [data-action]');
		if (!actionEl) {
			return;
		}
		const dialog = actionEl.closest('.hrh-sample-cart');
		if (!dialog) {
			return;
		}
		const action = actionEl.getAttribute('data-action');

		if (action === 'continue') {
			event.preventDefault();
			closeCart(/** @type {HTMLElement} */ (dialog));
			return;
		}
		if (action === 'checkout') {
			event.preventDefault();
			const href = actionEl.getAttribute('data-href');
			if (!href || actionEl.hasAttribute('disabled')) {
				liveAnnounce(/** @type {HTMLElement} */ (dialog), 'Checkout page is not configured.');
				return;
			}
			window.location.assign(href);
		}
	});

	document.addEventListener('keydown', (event) => {
		if (event.key !== 'Escape') {
			return;
		}
		const dialog = document.querySelector('.hrh-sample-cart:not([hidden])');
		if (dialog) {
			closeCart(/** @type {HTMLElement} */ (dialog));
		}
	});

	// Initial summary hydrate (checkout page) + self-heal stale lines at load.
	hydrateSummaries();
}
