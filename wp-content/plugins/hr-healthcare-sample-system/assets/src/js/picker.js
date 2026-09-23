/**
 * Inline section-swap picker — 3-step machine without a modal overlay.
 *
 * Elementor setup required:
 *   .hrh-sample-trigger-section  CSS class on the button section
 *   .hrh-sample-picker-section   CSS class on the section containing [hrh_sample_picker]
 *
 * JS adds/removes .is-open / .is-hidden on those sections to swap them.
 * No-ops silently when .hrh-sample-picker-section is absent (modal pages unaffected).
 */

import { announce } from './a11y.js';
import { ensureGroups, seedCache, seedFromLocalized } from './api.js';
import { addSelection, canAddSelection, openCart, readSelections } from './cart.js';
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

/** @param {HTMLElement} root */
function liveRegion(root) {
	return root.querySelector('.hrh-sample-sr-only[role="status"], [role="status"][aria-live]');
}

/** @param {HTMLElement} root */
function reviewNotice(root) {
	return root.querySelector('.hrh-sample-step[data-step="review"] .hrh-sample-step__notice');
}

/**
 * @param {HTMLElement} root
 * @param {string} message
 */
function setReviewNotice(root, message) {
	const notice = reviewNotice(root);
	if (!notice) return;
	notice.textContent = message;
	notice.hidden = message === '';
}

/**
 * @param {HTMLElement} root
 * @param {string} activeKey
 */
function renderSteps(root, activeKey) {
	const list = root.querySelector('.hrh-sample-steps');
	if (!list) return;

	const activeIndex = STEPS.findIndex((step) => step.key === activeKey);
	const existing = Array.from(list.querySelectorAll('li'));

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
 * Populate the .hrh-sample-picker__media img from the group config.
 * @param {HTMLElement} root
 * @param {GroupConfig} config
 */
function setPickerImage(root, config) {
	const img = root.querySelector('.hrh-sample-modal__media img');
	if (!img) return;
	const placeholder =
		(typeof window !== 'undefined' && window.HRH_SAMPLE_PLACEHOLDER_IMAGE) || '';
	const src = config.image_url || placeholder;
	if (src) img.setAttribute('src', src);
	img.setAttribute('alt', config.product_line || config.slug || 'Product');
}

/**
 * @param {HTMLElement} root
 * @param {string} activeKey
 */
function showStep(root, activeKey) {
	root.querySelectorAll('.hrh-sample-step').forEach((section) => {
		const isActive = section.getAttribute('data-step') === activeKey;
		section.hidden = !isActive;
		if (isActive) section.removeAttribute('hidden');
	});
	renderSteps(root, activeKey);
	root.setAttribute('data-current-step', activeKey);
}

/**
 * Create a step-machine controller bound to one .hrh-sample-picker element.
 * @param {HTMLElement} root
 */
function createPickerController(root) {
	/** @type {GroupConfig|null} */
	let config = null;
	/** @type {SelectionState|null} */
	let selection = null;
	/** @type {ReturnType<typeof mountPropertySelector>|null} */
	let selectorHandle = null;
	/** @type {string} */
	let step = 'select';

	const announce_ = (/** @type {string} */ msg) => announce(liveRegion(root), msg);

	function destroySelector() {
		selectorHandle?.destroy?.();
		selectorHandle = null;
	}

	function mountSelector() {
		if (!config) return;
		const mount =
			root.querySelector('.hrh-sample-step[data-step="select"] .hrh-sample-selector') ||
			root.querySelector('.hrh-sample-step[data-step="select"]');
		if (!mount) return;

		destroySelector();
		selectorHandle = mountPropertySelector(/** @type {HTMLElement} */ (mount), {
			config,
			initialOptions: selection?.options || {},
			showAdvance: true,
			advanceLabel: 'Finish Selection',
			announce: announce_,
			onChange: (state) => {
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
		showStep(root, 'select');
		mountSelector();
		announce_('Product Selection');
		const focusTarget =
			root.querySelector('.hrh-sample-step[data-step="select"] input:not([disabled])') ||
			root.querySelector('.hrh-sample-step[data-step="select"] button');
		if (focusTarget && typeof focusTarget.focus === 'function') {
			focusTarget.focus();
		}
	}

	function goToReview() {
		if (!selection?.resolved?.valid) {
			announce_('Finish selecting a valid combination first.');
			return;
		}
		step = 'review';
		showStep(root, 'review');
		setReviewNotice(root, '');

		let dl = root.querySelector('.hrh-sample-step[data-step="review"] .hrh-sample-card__specs');
		const reviewSection = root.querySelector('.hrh-sample-step[data-step="review"]');
		if (!dl && reviewSection) {
			dl = el('dl', { className: 'hrh-sample-card__specs' });
			const actions = reviewSection.querySelector('.hrh-sample-step__actions');
			if (actions) reviewSection.insertBefore(dl, actions);
			else reviewSection.prepend(dl);
		}
		if (dl) fillSpecBlock(/** @type {HTMLElement} */ (dl), selection.spec);

		announce_('Review your sample request');
		const backBtn = root.querySelector('.hrh-sample-step[data-step="review"] [data-action="back"]');
		if (backBtn && typeof backBtn.focus === 'function') backBtn.focus();
	}

	function goToConfirm() {
		step = 'confirm';
		showStep(root, 'confirm');
		announce_('Sample request has been added to your cart');
		const focusTarget =
			root.querySelector('.hrh-sample-step[data-step="confirm"] [data-action="view-cart"]') ||
			root.querySelector('.hrh-sample-step[data-step="confirm"] [data-action="continue"]') ||
			root.querySelector('.hrh-sample-step[data-step="confirm"]');
		if (focusTarget && typeof focusTarget.focus === 'function') focusTarget.focus();
	}

	async function commitToCart() {
		if (!config || !selection?.resolved?.valid) {
			announce_('Nothing to add — finish your selection first.');
			return;
		}

		try {
			const slugs = readSelections().map((line) => line.slug);
			if (slugs.length) await ensureGroups(slugs);
		} catch {
			announce_('Unable to verify cart contents. Please try again.');
			return;
		}

		const gate = canAddSelection(config.slug, selection.options, config);
		if (!gate.ok) {
			const message =
				gate.reason === 'duplicate'
					? 'This sample is already in your cart.'
					: gate.reason === 'cap'
						? `You've reached the cart limit of ${gate.cap} samples.`
						: 'This combination is unavailable. Please go back and adjust.';
			setReviewNotice(root, message);
			announce_(message);
			return;
		}

		setReviewNotice(root, '');
		addSelection(config.slug, selection.options);
		goToConfirm();
	}

	function open(nextConfig) {
		config = nextConfig;
		selection = null;
		seedCache(config);
		setPickerImage(root, config);
		goToSelect();
	}

	function close() {
		destroySelector();
		selection = null;
		step = 'select';
		showStep(root, 'select');
	}

	function onAction(action) {
		if (action === 'back') { goToSelect(); return; }
		if (action === 'add-to-cart') { commitToCart(); }
	}

	return { open, close, onAction, getStep: () => step };
}

/** @type {WeakMap<HTMLElement, ReturnType<typeof createPickerController>>} */
const controllers = new WeakMap();

/** @param {HTMLElement} root */
function getController(root) {
	let c = controllers.get(root);
	if (!c) {
		c = createPickerController(root);
		controllers.set(root, c);
	}
	return c;
}

/**
 * Wire section-swap behavior for pages using [hrh_sample_picker].
 * Returns true when a picker section was found and wired (caller can skip initModal).
 *
 * @returns {boolean}
 */
export function initPicker() {
	const pickerSection = document.querySelector('.hrh-sample-picker-section');
	const triggerSection = document.querySelector('.hrh-sample-trigger-section');
	if (!pickerSection) return false;

	const picker = pickerSection.querySelector('.hrh-sample-picker');
	if (!picker) return false;

	seedFromLocalized();

	/** @param {Element|null} triggerEl */
	function openPicker(triggerEl) {
		const config = typeof window !== 'undefined' ? window.HRH_SAMPLE_CONFIG : null;
		if (!config?.slug) {
			announce(
				liveRegion(/** @type {HTMLElement} */ (picker)),
				'Sample options are not available on this page.'
			);
			return;
		}
		pickerSection.classList.add('is-open');
		triggerSection?.classList.add('is-hidden');
		getController(/** @type {HTMLElement} */ (picker)).open(config);
		pickerSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
	}

	function closePicker() {
		pickerSection.classList.remove('is-open');
		triggerSection?.classList.remove('is-hidden');
		getController(/** @type {HTMLElement} */ (picker)).close();
	}

	document.addEventListener('click', (event) => {
		const target = /** @type {HTMLElement} */ (event.target);

		const trigger = target.closest('.hrh-sample-open-modal');
		if (trigger) {
			event.preventDefault();
			openPicker(trigger);
			return;
		}

		const closeEl = target.closest('.hrh-sample-picker [data-close]');
		if (closeEl) {
			event.preventDefault();
			closePicker();
			return;
		}

		const actionEl = target.closest('.hrh-sample-picker [data-action]');
		if (!actionEl) return;
		if (actionEl.getAttribute('data-action') === 'to-review') return;

		event.preventDefault();
		const action = actionEl.getAttribute('data-action') || '';

		if (action === 'continue') {
			closePicker();
			return;
		}
		if (action === 'view-cart') {
			closePicker();
			const cartEl =
				document.getElementById('hrh-sample-cart') ||
				document.querySelector('.hrh-sample-cart');
			if (cartEl) openCart(/** @type {HTMLElement} */ (cartEl));
			return;
		}

		getController(/** @type {HTMLElement} */ (picker)).onAction(action);
	});

	return true;
}
