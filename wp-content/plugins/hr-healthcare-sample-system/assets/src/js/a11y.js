/**
 * Accessibility helpers — focus trap, restore-focus, live announcements, inert.
 * Guide §17.
 */

/** @type {null | { container: HTMLElement, previouslyFocused: Element|null, onKeyDown: (e: KeyboardEvent) => void, inerted: HTMLElement[] }} */
let activeTrap = null;

/**
 * Focusable selector used by the trap.
 */
const FOCUSABLE =
	'a[href], button:not([disabled]), textarea:not([disabled]), input:not([disabled]):not([type="hidden"]), select:not([disabled]), [tabindex]:not([tabindex="-1"])';

/**
 * @param {HTMLElement} container
 * @returns {HTMLElement[]}
 */
export function getFocusable(container) {
	return Array.from(container.querySelectorAll(FOCUSABLE)).filter(
		(node) =>
			!node.hasAttribute('disabled') &&
			node.getAttribute('aria-hidden') !== 'true' &&
			!node.closest('[hidden], [inert]')
	);
}

/**
 * Mark page content outside the dialog as inert (fallback: aria-hidden).
 * @param {HTMLElement} dialog
 * @returns {HTMLElement[]}
 */
function inertBackground(dialog) {
	const inerted = [];
	Array.from(document.body.children).forEach((child) => {
		if (!(child instanceof HTMLElement)) {
			return;
		}
		if (child === dialog || child.contains(dialog) || dialog.contains(child)) {
			return;
		}
		if (child.hasAttribute('data-hrh-sample-keep')) {
			return;
		}
		if ('inert' in child) {
			child.inert = true;
		} else {
			child.setAttribute('aria-hidden', 'true');
			child.setAttribute('data-hrh-inert-fallback', 'true');
		}
		inerted.push(child);
	});
	return inerted;
}

/**
 * @param {HTMLElement[]} inerted
 */
function restoreBackground(inerted) {
	(inerted || []).forEach((child) => {
		if ('inert' in child) {
			child.inert = false;
		}
		if (child.getAttribute('data-hrh-inert-fallback') === 'true') {
			child.removeAttribute('aria-hidden');
			child.removeAttribute('data-hrh-inert-fallback');
		}
	});
}

/**
 * Trap Tab focus inside `container`. Call `releaseFocusTrap` to undo.
 * @param {HTMLElement} container
 * @param {{ initialFocus?: HTMLElement|null }} [opts]
 */
export function trapFocus(container, opts = {}) {
	releaseFocusTrap();

	const previouslyFocused = document.activeElement;
	const onKeyDown = (event) => {
		if (event.key !== 'Tab') {
			return;
		}
		const focusable = getFocusable(container);
		if (focusable.length === 0) {
			event.preventDefault();
			return;
		}
		const first = focusable[0];
		const last = focusable[focusable.length - 1];
		if (event.shiftKey && document.activeElement === first) {
			event.preventDefault();
			last.focus();
		} else if (!event.shiftKey && document.activeElement === last) {
			event.preventDefault();
			first.focus();
		}
	};

	container.addEventListener('keydown', onKeyDown);
	activeTrap = { container, previouslyFocused, onKeyDown, inerted: [] };

	const initial =
		opts.initialFocus ||
		container.querySelector('[data-initial-focus]') ||
		getFocusable(container)[0] ||
		container;
	if (initial && typeof initial.focus === 'function') {
		initial.focus();
	}
}

/**
 * Release the active focus trap (does not restore focus — use restoreFocus).
 */
export function releaseFocusTrap() {
	if (!activeTrap) {
		return;
	}
	activeTrap.container.removeEventListener('keydown', activeTrap.onKeyDown);
	restoreBackground(activeTrap.inerted || []);
	activeTrap = null;
}

/**
 * Restore focus to a previously focused element (or the trap's remembered trigger).
 * @param {Element|null} [el]
 */
export function restoreFocus(el = null) {
	const target = el || activeTrap?.previouslyFocused || null;
	releaseFocusTrap();
	if (target && typeof target.focus === 'function') {
		target.focus();
	}
}

/**
 * Announce a message into an aria-live region.
 * Clears then sets text so identical consecutive messages still fire.
 * @param {HTMLElement|null} liveRegion
 * @param {string} message
 */
export function announce(liveRegion, message) {
	if (!liveRegion) {
		return;
	}
	liveRegion.textContent = '';
	// Force a DOM mutation so polite live regions re-announce identical text.
	requestAnimationFrame(() => {
		liveRegion.textContent = message;
	});
}

/**
 * Open a dialog-like element: remove hidden, set aria, trap focus, inert background.
 * @param {HTMLElement} dialog
 * @param {{ trigger?: Element|null, initialFocus?: HTMLElement|null }} [opts]
 */
export function openDialog(dialog, opts = {}) {
	if (!dialog) {
		return;
	}
	// Ensure token/a11y scope even when PHP omitted .hrh-sample-root on the shell.
	dialog.classList.add('hrh-sample-root');
	dialog.hidden = false;
	dialog.setAttribute('aria-hidden', 'false');
	document.documentElement.classList.add('hrh-sample-dialog-open');
	document.body.classList.add('hrh-sample-dialog-open');

	const inerted = inertBackground(dialog);
	trapFocus(dialog, { initialFocus: opts.initialFocus });
	if (activeTrap) {
		activeTrap.inerted = inerted;
	}
	// Stash trigger for closeDialog convenience.
	dialog._hrhTrigger = opts.trigger || document.activeElement;
}

/**
 * Close a dialog-like element and restore focus to its trigger.
 * @param {HTMLElement} dialog
 * @param {{ restoreTo?: Element|null }} [opts]
 */
export function closeDialog(dialog, opts = {}) {
	if (!dialog) {
		return;
	}
	dialog.hidden = true;
	dialog.setAttribute('aria-hidden', 'true');
	document.documentElement.classList.remove('hrh-sample-dialog-open');
	document.body.classList.remove('hrh-sample-dialog-open');
	const restoreTo = opts.restoreTo || dialog._hrhTrigger || null;
	restoreFocus(restoreTo);
	dialog._hrhTrigger = null;
}
