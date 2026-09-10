/**
 * Admin entry — importer / settings UI enhancements.
 * Guide §9. PHP owns the settings page markup; this hydrates progressive
 * enhancements (import confirm, field-scan feedback, options viewer expand).
 */

import '../scss/admin.scss';

/**
 * Confirm before submitting the import form (diff-sync is destructive).
 * @param {HTMLFormElement} form
 */
function bindImportConfirm(form) {
	form.addEventListener('submit', (event) => {
		const fileInput = form.querySelector('input[type="file"]');
		const hasFile = fileInput instanceof HTMLInputElement && fileInput.files && fileInput.files.length > 0;
		if (!hasFile) {
			return;
		}
		const ok = window.confirm(
			'Re-import will diff-sync groups, options, and SKUs against this sheet. Continue?'
		);
		if (!ok) {
			event.preventDefault();
		}
	});
}

/**
 * Toggle options-viewer group panels.
 * @param {HTMLElement} root
 */
function bindOptionsViewer(root) {
	root.addEventListener('click', (event) => {
		const target = /** @type {HTMLElement} */ (event.target);
		const toggle = target.closest('[data-action="toggle-group"]');
		if (!toggle) {
			return;
		}
		event.preventDefault();
		const controls = toggle.getAttribute('aria-controls');
		const panel = controls
			? document.getElementById(controls)
			: toggle.closest('.hrh-sample-admin__group')?.querySelector('.hrh-sample-admin__group-body');
		if (!panel) {
			return;
		}
		const open = panel.hasAttribute('hidden');
		if (open) {
			panel.removeAttribute('hidden');
		} else {
			panel.setAttribute('hidden', '');
		}
		toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
	});
}

/**
 * Live-announce field-scan results when the GF form dropdown changes
 * (PHP still does the authoritative scan on save; this is UX only).
 * @param {HTMLElement} root
 */
function bindFieldScanHint(root) {
	const select = root.querySelector('[name="hrh_sample_checkout_form"], #hrh_sample_checkout_form');
	const hint = root.querySelector('[data-hrh-field-scan]');
	if (!select || !hint) {
		return;
	}
	select.addEventListener('change', () => {
		hint.textContent =
			'Save settings to scan this form for the three marker-class fields (selections, readable, json).';
	});
}

/**
 * Cap input: keep within a sane range client-side (server re-validates).
 * @param {HTMLElement} root
 */
function bindCartCap(root) {
	const input = root.querySelector('[name="hrh_sample_cart_cap"], #hrh_sample_cart_cap');
	if (!(input instanceof HTMLInputElement)) {
		return;
	}
	input.addEventListener('change', () => {
		const value = Number(input.value);
		if (!Number.isFinite(value) || value < 1) {
			input.value = '1';
		} else if (value > 50) {
			input.value = '50';
		}
	});
}

/**
 * Boot admin progressive enhancements against the PHP-rendered settings page.
 */
export function bootAdmin() {
	if (typeof window === 'undefined' || typeof document === 'undefined') {
		return;
	}

	const root =
		document.querySelector('.hrh-sample-admin') ||
		document.getElementById('hrh-sample-settings') ||
		document.querySelector('.wrap.hrh-sample');

	if (root) {
		const importForm =
			root.querySelector('form[data-hrh-import]') ||
			root.querySelector('#hrh-sample-import-form') ||
			root.querySelector('form.hrh-sample-admin__import');
		if (importForm instanceof HTMLFormElement) {
			bindImportConfirm(importForm);
		}
		bindOptionsViewer(/** @type {HTMLElement} */ (root));
		bindFieldScanHint(/** @type {HTMLElement} */ (root));
		bindCartCap(/** @type {HTMLElement} */ (root));
	}

	window.HRH_SAMPLE_ADMIN_READY = true;
	window.HRH_SAMPLE_ADMIN = {
		version: '0.3.0-wave3',
		bootAdmin,
	};

	return window.HRH_SAMPLE_ADMIN;
}

bootAdmin();
