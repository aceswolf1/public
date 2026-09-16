/**
 * Product modal — 3-step machine (Product Selection → Review → Confirmation).
 * Guide §6 / §16 / §17.
 *
 * Selection is held in modal state across Select↔Review (not the cookie).
 * Commit (dup-SKU + cart-cap) fires only at Review's Add to Cart.
 */

import { announce, closeDialog, openDialog } from './a11y.js';
import { ensureGroups, seedCache, seedFromLocalized } from './api.js';
import {
	addSelection,
	canAddSelection,
	openCart,
	readSelections,
} from './cart.js';
import { el } from './dom.js';
import { fillSpecBlock } from './components/SpecBlock.js';
import { mountPropertySelector } from './components/PropertySelector.js';

const STEPS = [
	{ key: 'select', label: 'Product Selection' },
	{ key: 'review', label: 'Review' },
	{ key: 'confirm', label: 'Confirmation' },
];

/** @typedef {import('./resolver.js').GroupConfig} GroupConfig */
/** @typedef {import('./resolver.js').ResolveResult} ResolveResult */
/** @typedef {import('./components/SpecBlock.js').SpecData} SpecData */

/**
 * @typedef {{
 *   options: Record<string, string>,
 *   resolved: ResolveResult,
 *   spec: SpecData
 * }} SelectionState
 */

/**
 * @param {HTMLElement} modal
 * @returns {HTMLElement|null}
 */
function liveRegion(modal) {
	return modal.querySelector('.hrh-sample-sr-only[role="status"], [role="status"][aria-live]');
}

/**
 * Visible notice inside the Review step (e.g. "already in your cart").
 * @param {HTMLElement} modal
 * @returns {HTMLElement|null}
 */
function reviewNotice(modal) {
	return modal.querySelector('.hrh-sample-step[data-step="review"] .hrh-sample-step__notice');
}

/**
 * @param {HTMLElement} modal
 * @param {string} message Empty string clears + hides the notice.
 */
function setReviewNotice(modal, message) {
	const notice = reviewNotice(modal);
	if (!notice) {
		return;
	}
	notice.textContent = message;
	notice.hidden = message === '';
}

/**
 * Ensure the three-node step indicator exists and reflects `activeKey`.
 * @param {HTMLElement} modal
 * @param {string} activeKey
 */
function renderSteps(modal, activeKey) {
	let list = modal.querySelector('.hrh-sample-steps');
	if (!list) {
		list = el('ol', {
			className: 'hrh-sample-steps',
			'aria-label': 'Progress',
		});
		// Fallback insertion (the shell normally ships the <ol> already).
		const optionsBox =
			modal.querySelector('.hrh-sample-modal__options') ||
			modal.querySelector('.hrh-sample-modal__content');
		optionsBox?.prepend(list);
	}

	const activeIndex = STEPS.findIndex((step) => step.key === activeKey);
	const existing = Array.from(list.querySelectorAll('li'));

	// Prefer updating the PHP-printed three-node list in place (§16).
	if (existing.length === STEPS.length) {
		existing.forEach((li, index) => {
			const step = STEPS[index];
			li.classList.remove('is-active', 'is-complete');
			li.removeAttribute('aria-current');
			li.setAttribute('data-step', step.key);
			li.setAttribute('data-step-indicator', step.key);
			if (index === activeIndex) {
				li.classList.add('is-active');
				li.setAttribute('aria-current', 'step');
			} else if (index < activeIndex) {
				li.classList.add('is-complete');
			}
		});
		return;
	}

	list.replaceChildren(
		...STEPS.map((step, index) => {
			const li = el('li', {
				className: 'hrh-sample-steps__item',
				'data-step': step.key,
				'data-step-indicator': step.key,
				text: step.label,
			});
			if (index === activeIndex) {
				li.classList.add('is-active');
				li.setAttribute('aria-current', 'step');
			} else if (index < activeIndex) {
				li.classList.add('is-complete');
			}
			return li;
		})
	);
}

/**
 * Show one step panel; hide the others.
 * @param {HTMLElement} modal
 * @param {string} activeKey
 */
function showStep(modal, activeKey) {
	modal.querySelectorAll('.hrh-sample-step').forEach((section) => {
		const key = section.getAttribute('data-step');
		const isActive = key === activeKey;
		section.hidden = !isActive;
		if (isActive) {
			section.removeAttribute('hidden');
		}
	});
	renderSteps(modal, activeKey);
	modal.setAttribute('data-current-step', activeKey);
}

/**
 * Keep the left-side product image fixed across steps.
 * @param {HTMLElement} modal
 * @param {GroupConfig} config
 */
function setModalImage(modal, config) {
	const img = modal.querySelector('.hrh-sample-modal__media img');
	if (!img) {
		return;
	}
	const placeholder =
		(typeof window !== 'undefined' && window.HRH_SAMPLE_PLACEHOLDER_IMAGE) || '';
	const src = config.image_url || placeholder;
	if (src) {
		img.setAttribute('src', src);
	}
	const alt = config.product_line || config.slug || 'Product';
	img.setAttribute('alt', alt);
}

/**
 * @param {HTMLElement} modal
 * @param {string} message
 */
function announceModal(modal, message) {
	announce(liveRegion(modal), message);
}

/**
 * Create a controller bound to one modal shell element.
 * @param {HTMLElement} modal
 */
function createModalController(modal) {
	/** @type {GroupConfig|null} */
	let config = null;
	/** @type {SelectionState|null} */
	let selection = null;
	/** @type {ReturnType<typeof mountPropertySelector>|null} */
	let selector = null;
	/** @type {string} */
	let step = 'select';

	function destroySelector() {
		selector?.destroy?.();
		selector = null;
	}

	function mountSelector() {
		if (!config) {
			return;
		}
		const mount =
			modal.querySelector('.hrh-sample-step[data-step="select"] .hrh-sample-selector') ||
			modal.querySelector('.hrh-sample-step[data-step="select"]');
		if (!mount) {
			return;
		}

		destroySelector();
		selector = mountPropertySelector(/** @type {HTMLElement} */ (mount), {
			config,
			initialOptions: selection?.options || {},
			showAdvance: true,
			advanceLabel: 'Finish Selection',
			announce: (message) => announceModal(modal, message),
			onChange: (state) => {
				// Keep modal-held selection in sync while on Select (not cookie).
				if (step === 'select') {
					selection = {
						options: { ...state.options },
						resolved: state.resolved,
						spec: state.spec,
					};
				}
			},
			onAdvance: (state) => {
				selection = {
					options: { ...state.options },
					resolved: state.resolved,
					spec: state.spec,
				};
				goToReview();
			},
		});
	}

	function goToSelect() {
		step = 'select';
		showStep(modal, 'select');
		mountSelector();
		announceModal(modal, 'Product Selection');
		const focusTarget =
			modal.querySelector('.hrh-sample-step[data-step="select"] input:not([disabled])') ||
			modal.querySelector('.hrh-sample-step[data-step="select"] button');
		if (focusTarget && typeof focusTarget.focus === 'function') {
			focusTarget.focus();
		}
	}

	function goToReview() {
		if (!selection?.resolved?.valid) {
			announceModal(modal, 'Finish selecting a valid combination first.');
			return;
		}
		step = 'review';
		showStep(modal, 'review');
		setReviewNotice(modal, ''); // fresh review — clear any prior block message.

		// Title stays the product name (server-rendered); step is shown by the indicator.
		let dl = modal.querySelector('.hrh-sample-step[data-step="review"] .hrh-sample-card__specs');
		const reviewSection = modal.querySelector('.hrh-sample-step[data-step="review"]');
		if (!dl && reviewSection) {
			dl = el('dl', { className: 'hrh-sample-card__specs' });
			const actions = reviewSection.querySelector('.hrh-sample-step__actions');
			if (actions) {
				reviewSection.insertBefore(dl, actions);
			} else {
				reviewSection.prepend(dl);
			}
		}
		if (dl) {
			fillSpecBlock(/** @type {HTMLElement} */ (dl), selection.spec);
		}

		announceModal(modal, 'Review your sample request');
		const backBtn = modal.querySelector('.hrh-sample-step[data-step="review"] [data-action="back"]');
		if (backBtn && typeof backBtn.focus === 'function') {
			backBtn.focus();
		}
	}

	function goToConfirm() {
		step = 'confirm';
		showStep(modal, 'confirm');

		announceModal(modal, 'Sample request has been added to your cart');
		const focusTarget =
			modal.querySelector('.hrh-sample-step[data-step="confirm"] [data-action="view-cart"]') ||
			modal.querySelector('.hrh-sample-step[data-step="confirm"] [data-action="continue"]') ||
			modal.querySelector('.hrh-sample-step[data-step="confirm"]');
		if (focusTarget && typeof focusTarget.focus === 'function') {
			focusTarget.focus();
		}
	}

	async function commitToCart() {
		if (!config || !selection?.resolved?.valid) {
			announceModal(modal, 'Nothing to add — finish your selection first.');
			return;
		}

		// Ensure other cart lines are cached so duplicate-SKU can be checked.
		try {
			const slugs = readSelections().map((line) => line.slug);
			if (slugs.length) {
				await ensureGroups(slugs);
			}
		} catch {
			announceModal(modal, 'Unable to verify cart contents. Please try again.');
			return;
		}

		const gate = canAddSelection(config.slug, selection.options, config);
		if (!gate.ok) {
			let message;
			if (gate.reason === 'duplicate') {
				message = 'This sample is already in your cart.';
			} else if (gate.reason === 'cap') {
				message = `You've reached the cart limit of ${gate.cap} samples.`;
			} else {
				message = 'This combination is unavailable. Please go back and adjust.';
			}
			setReviewNotice(modal, message); // visible feedback on the Review step.
			announceModal(modal, message); // + screen-reader announcement.
			return;
		}

		setReviewNotice(modal, '');
		addSelection(config.slug, selection.options);
		goToConfirm();
	}

	/**
	 * Open the modal for the given (or localized) group config.
	 * @param {GroupConfig} nextConfig
	 * @param {{ trigger?: Element|null }} [opts]
	 */
	function open(nextConfig, opts = {}) {
		config = nextConfig;
		selection = null;
		seedCache(config);
		setModalImage(modal, config);
		openDialog(modal, {
			trigger: opts.trigger || null,
			initialFocus: modal.querySelector('.hrh-sample-modal__close'),
		});
		goToSelect();
	}

	function close() {
		destroySelector();
		selection = null;
		step = 'select';
		closeDialog(modal);
	}

	function onAction(action) {
		if (action === 'back') {
			// Preserve selection in modal state; remount tiles from it.
			goToSelect();
			return;
		}
		if (action === 'add-to-cart') {
			commitToCart();
			return;
		}
		if (action === 'continue') {
			close();
			return;
		}
		if (action === 'view-cart') {
			close();
			const cart = document.getElementById('hrh-sample-cart') || document.querySelector('.hrh-sample-cart');
			if (cart) {
				openCart(/** @type {HTMLElement} */ (cart));
			}
		}
	}

	return { open, close, onAction, getStep: () => step };
}

/** @type {WeakMap<HTMLElement, ReturnType<typeof createModalController>>} */
const controllers = new WeakMap();

/**
 * @param {HTMLElement} modal
 */
function getController(modal) {
	let controller = controllers.get(modal);
	if (!controller) {
		controller = createModalController(modal);
		controllers.set(modal, controller);
	}
	return controller;
}

/**
 * Document-level modal wiring: open trigger delegation, actions, Esc.
 */
export function initModal() {
	seedFromLocalized();

	document.addEventListener('click', (event) => {
		const target = /** @type {HTMLElement} */ (event.target);

		const trigger = target.closest('.hrh-sample-open-modal');
		if (trigger) {
			event.preventDefault();
			const modal = document.querySelector('.hrh-sample-modal');
			const config = typeof window !== 'undefined' ? window.HRH_SAMPLE_CONFIG : null;
			if (!modal) {
				return;
			}
			if (!config?.slug) {
				announce(liveRegion(/** @type {HTMLElement} */ (modal)), 'Sample options are not available on this page.');
				return;
			}
			getController(/** @type {HTMLElement} */ (modal)).open(config, { trigger });
			return;
		}

		const closeEl = target.closest('.hrh-sample-modal [data-close]');
		if (closeEl) {
			event.preventDefault();
			const modal = closeEl.closest('.hrh-sample-modal');
			if (modal) {
				getController(/** @type {HTMLElement} */ (modal)).close();
			}
			return;
		}

		const actionEl = target.closest('.hrh-sample-modal [data-action]');
		if (!actionEl) {
			return;
		}
		// PropertySelector's to-review is handled by its own listener.
		if (actionEl.getAttribute('data-action') === 'to-review') {
			return;
		}
		const modal = actionEl.closest('.hrh-sample-modal');
		if (!modal) {
			return;
		}
		event.preventDefault();
		getController(/** @type {HTMLElement} */ (modal)).onAction(actionEl.getAttribute('data-action') || '');
	});

	document.addEventListener('keydown', (event) => {
		if (event.key !== 'Escape') {
			return;
		}
		const modal = document.querySelector('.hrh-sample-modal:not([hidden])');
		if (modal) {
			getController(/** @type {HTMLElement} */ (modal)).close();
		}
	});
}

export { createModalController, STEPS };
