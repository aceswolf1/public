/**
 * LineItemCard — cart line: image + SpecBlock + actions + inline edit shell.
 * Mounts in: cart dialog AND [hrh_sample_cart_summary] (§16).
 */

import { el } from '../dom.js';
import { fillSpecBlock, renderSpecBlock } from './SpecBlock.js';

/**
 * @typedef {import('./SpecBlock.js').SpecData} SpecData
 *
 * @typedef {{
 *   slug: string,
 *   sku: string,
 *   imageUrl?: string,
 *   productName?: string,
 *   spec: SpecData,
 *   stale?: boolean,
 *   editId?: string,
 *   showActions?: boolean
 * }} LineItemData
 */

/**
 * @param {LineItemData} data
 * @returns {HTMLLIElement}
 */
export function renderLineItemCard(data) {
	const editId = data.editId || `edit-${data.slug}-${data.sku}`.replace(/[^a-zA-Z0-9_-]/g, '-');
	const alt = data.productName || data.spec?.product_name || '';
	const showActions = data.showActions !== false;

	const img = el('img', {
		className: 'hrh-sample-card__img',
		src: data.imageUrl || '',
		alt,
	});

	const specs = renderSpecBlock(data.spec || {});

	const actions = showActions
		? el('div', { className: 'hrh-sample-card__actions' }, [
				el('button', {
					type: 'button',
					className: 'hrh-sample-btn',
					'data-action': 'edit',
					'aria-expanded': 'false',
					'aria-controls': editId,
					text: 'Edit Request',
				}),
				el('button', {
					type: 'button',
					className: 'hrh-sample-btn',
					'data-action': 'cancel',
					text: 'Cancel Request',
				}),
			])
		: null;

	const article = el('article', { className: 'hrh-sample-card' }, [
		img,
		specs,
		actions,
	].filter(Boolean));

	const edit = el('div', {
		id: editId,
		className: 'hrh-sample-line__edit',
		hidden: true,
	}, [
		el('div', { className: 'hrh-sample-selector' }),
		el('div', { className: 'hrh-sample-line__edit-actions' }, [
			el('button', {
				type: 'button',
				className: 'hrh-sample-btn',
				'data-action': 'edit-cancel',
				text: 'Cancel',
			}),
			el('button', {
				type: 'button',
				className: 'hrh-sample-btn hrh-sample-btn--primary',
				'data-action': 'edit-save',
				text: 'Update',
			}),
		]),
	]);

	const li = el('li', {
		className: 'hrh-sample-line',
		'data-slug': data.slug,
		'data-sku': data.sku,
	}, [article, edit]);

	if (data.stale) {
		li.classList.add('is-stale');
		li.setAttribute('data-stale', 'true');
		article.prepend(
			el('p', {
				className: 'hrh-sample-line__stale',
				role: 'status',
				text: 'Please re-select this item — the previous combination is no longer available.',
			})
		);
	}

	return /** @type {HTMLLIElement} */ (li);
}

/**
 * Update the SpecBlock + image/sku attrs on an existing card.
 * @param {HTMLElement} li
 * @param {Partial<LineItemData>} data
 */
export function updateLineItemCard(li, data = {}) {
	if (!li) {
		return;
	}
	if (data.slug) {
		li.setAttribute('data-slug', data.slug);
	}
	if (data.sku) {
		li.setAttribute('data-sku', data.sku);
	}
	const img = li.querySelector('.hrh-sample-card__img');
	if (img && data.imageUrl) {
		img.setAttribute('src', data.imageUrl);
	}
	if (img && (data.productName || data.spec?.product_name)) {
		img.setAttribute('alt', data.productName || data.spec.product_name);
	}
	const dl = li.querySelector('.hrh-sample-card__specs');
	if (dl && data.spec) {
		fillSpecBlock(dl, data.spec);
	}
	if (typeof data.stale === 'boolean') {
		li.classList.toggle('is-stale', data.stale);
		if (data.stale) {
			li.setAttribute('data-stale', 'true');
		} else {
			li.removeAttribute('data-stale');
		}
	}
}

/**
 * Find the PropertySelector mount point inside a line's edit expander.
 * @param {HTMLElement} li
 * @returns {HTMLElement|null}
 */
export function getLineEditMount(li) {
	return li?.querySelector('.hrh-sample-line__edit .hrh-sample-selector') || null;
}

/**
 * Toggle the inline edit expander.
 * @param {HTMLElement} li
 * @param {boolean} open
 */
export function setLineEditOpen(li, open) {
	const edit = li?.querySelector('.hrh-sample-line__edit');
	const btn = li?.querySelector('[data-action="edit"]');
	if (!edit) {
		return;
	}
	edit.hidden = !open;
	if (btn) {
		btn.setAttribute('aria-expanded', open ? 'true' : 'false');
	}
}
