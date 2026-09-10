/**
 * SpecBlock — shared <dl.hrh-sample-card__specs> partial.
 * Used by LineItemCard AND the modal Review step (§16).
 */

import { el, escapeHtml } from '../dom.js';

/**
 * @typedef {{ label: string, value: string }} SpecProp
 * @typedef {{
 *   product_name?: string,
 *   sku?: string,
 *   hcpcs?: string,
 *   sample_amount?: string,
 *   properties?: SpecProp[],
 *   quantity_label?: string
 * }} SpecData
 */

/**
 * Build a single dt/dd row wrapped in a div.
 * @param {string} term
 * @param {string} description
 * @returns {HTMLElement}
 */
function row(term, description) {
	return el('div', {}, [
		el('dt', { text: term }),
		el('dd', { text: description || '—' }),
	]);
}

/**
 * Render a fresh SpecBlock <dl>.
 * @param {SpecData} data
 * @returns {HTMLDListElement}
 */
export function renderSpecBlock(data = {}) {
	const dl = el('dl', { className: 'hrh-sample-card__specs' });
	fillSpecBlock(dl, data);
	return /** @type {HTMLDListElement} */ (dl);
}

/**
 * Update an existing SpecBlock in place (immutable data → new children).
 * @param {HTMLElement} dl
 * @param {SpecData} data
 */
export function fillSpecBlock(dl, data = {}) {
	if (!dl) {
		return;
	}

	const children = [
		row('Product Name', data.product_name || ''),
		row('SKU#', data.sku || ''),
		row('HCPCS Code', data.hcpcs || ''),
	];

	(data.properties || []).forEach((prop) => {
		if (!prop?.label) {
			return;
		}
		children.push(row(prop.label, prop.value || ''));
	});

	const quantity = data.quantity_label || data.sample_amount || '';
	children.push(row('Quantity', quantity));

	dl.replaceChildren(...children);
}

/**
 * Convenience: HTML string (for rare PHP-less mounts). Prefer renderSpecBlock.
 * @param {SpecData} data
 * @returns {string}
 */
export function specBlockHtml(data = {}) {
	const props = (data.properties || [])
		.map(
			(prop) =>
				`<div><dt>${escapeHtml(prop.label)}</dt><dd>${escapeHtml(prop.value || '')}</dd></div>`
		)
		.join('');
	const quantity = escapeHtml(data.quantity_label || data.sample_amount || '');
	return [
		'<dl class="hrh-sample-card__specs">',
		`<div><dt>Product Name</dt><dd>${escapeHtml(data.product_name || '')}</dd></div>`,
		`<div><dt>SKU#</dt><dd>${escapeHtml(data.sku || '')}</dd></div>`,
		`<div><dt>HCPCS Code</dt><dd>${escapeHtml(data.hcpcs || '')}</dd></div>`,
		props,
		`<div><dt>Quantity</dt><dd>${quantity || '—'}</dd></div>`,
		'</dl>',
	].join('');
}
