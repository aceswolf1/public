/**
 * PropertySelector — reusable property-picking UI + live resolve.
 * Mounts in: modal Product Selection AND cart inline edit (§16).
 */

import { el } from '../dom.js';
import {
	availableValues,
	buildSpecData,
	resolve,
	selectableKeys,
} from '../resolver.js';

/**
 * @typedef {import('../resolver.js').GroupConfig} GroupConfig
 * @typedef {import('../resolver.js').ResolveResult} ResolveResult
 *
 * @typedef {{
 *   config: GroupConfig,
 *   initialOptions?: Record<string, string>,
 *   showAdvance?: boolean,
 *   advanceLabel?: string,
 *   namePrefix?: string,
 *   onChange?: (state: {
 *     options: Record<string, string>,
 *     resolved: ResolveResult,
 *     spec: ReturnType<typeof buildSpecData>
 *   }) => void,
 *   onAdvance?: (state: {
 *     options: Record<string, string>,
 *     resolved: ResolveResult,
 *     spec: ReturnType<typeof buildSpecData>
 *   }) => void,
 *   announce?: (message: string) => void
 * }} MountOptions
 */

let uid = 0;
function nextId(prefix) {
	uid += 1;
	return `hrh-sample-${prefix}-${uid}`;
}

/**
 * Mount a PropertySelector into `container` (replaced contents).
 * Returns a controller with getOptions / getResolved / setOptions / destroy.
 * @param {HTMLElement} container
 * @param {MountOptions} options
 */
export function mountPropertySelector(container, options) {
	if (!container || !options?.config) {
		throw new Error('PropertySelector requires container + config');
	}

	const {
		config,
		initialOptions = {},
		showAdvance = true,
		advanceLabel = 'Finish Selection',
		namePrefix = nextId('sel'),
		onChange = null,
		onAdvance = null,
		announce = null,
	} = options;

	/** @type {Record<string, string>} */
	let current = { ...initialOptions };

	const root = el('div', {
		className: 'hrh-sample-selector',
		'data-slug': config.slug || '',
	});

	const selectable = (config.properties || []).filter((prop) => prop.selectable);
	const fixed = (config.properties || []).filter((prop) => !prop.selectable);

	selectable.forEach((prop) => {
		const legendId = nextId('legend');
		const fieldset = el('fieldset', { className: 'hrh-sample-prop' }, [
			el('legend', {
				className: 'hrh-sample-prop__label',
				id: legendId,
				text: `Select ${prop.label}`,
			}),
		]);

		const tiles = el('div', {
			className: 'hrh-sample-prop__tiles',
			role: 'radiogroup',
			'aria-labelledby': legendId,
		});

		(prop.values || []).forEach((value) => {
			const inputId = nextId('tile');
			const input = el('input', {
				type: 'radio',
				id: inputId,
				name: `${namePrefix}__${prop.key}`,
				value: value.key,
				className: 'hrh-sample-sr-only',
			});
			if (current[prop.key] === value.key) {
				input.checked = true;
			}
			input.addEventListener('change', () => {
				if (!input.checked) {
					return;
				}
				current = { ...current, [prop.key]: value.key };
				sync();
			});

			const label = el('label', {
				className: 'hrh-sample-tile',
				for: inputId,
				'data-prop': prop.key,
				'data-value': value.key,
			}, [input, el('span', { text: value.label || value.key })]);

			tiles.append(label);
		});

		fieldset.append(tiles);
		root.append(fieldset);
	});

	fixed.forEach((prop) => {
		root.append(
			el('p', { className: 'hrh-sample-prop hrh-sample-prop--fixed' }, [
				el('span', { className: 'hrh-sample-prop__label', text: prop.label }),
				' ',
				el('span', { text: prop.value?.label || prop.value?.key || '' }),
			])
		);
	});

	const skuOut = el('span', { 'data-sku-out': '', text: '-' });
	const advanceBtn = el('button', {
		type: 'button',
		className: 'hrh-sample-btn hrh-sample-btn--primary',
		'data-action': 'to-review',
		disabled: true,
		text: advanceLabel,
	});
	if (!showAdvance) {
		advanceBtn.hidden = true;
	}
	advanceBtn.addEventListener('click', () => {
		const resolved = resolve(config, current);
		if (!resolved.valid) {
			return;
		}
		const state = {
			options: { ...current },
			resolved,
			spec: buildSpecData(config, current, resolved),
		};
		onAdvance?.(state);
	});

	root.append(
		el('div', { className: 'hrh-sample-selector__foot' }, [
			el('p', { className: 'hrh-sample-selector__sku' }, ['SKU: ', skuOut]),
			advanceBtn,
		])
	);

	container.replaceChildren(root);

	function sync() {
		const resolved = resolve(config, current);
		skuOut.textContent = resolved.valid && resolved.sku ? resolved.sku : '-';
		advanceBtn.disabled = !resolved.valid;

		// Disable dead-end tiles per selectable axis.
		selectable.forEach((prop) => {
			const available = availableValues(config, prop.key, current);
			root.querySelectorAll(`.hrh-sample-tile[data-prop="${prop.key}"]`).forEach((tile) => {
				const value = tile.getAttribute('data-value');
				const input = tile.querySelector('input[type="radio"]');
				const isAvailable = available.has(value);
				tile.classList.toggle('is-unavailable', !isAvailable);
				if (input) {
					input.disabled = !isAvailable;
					input.setAttribute('aria-disabled', isAvailable ? 'false' : 'true');
				}
			});
		});

		const state = {
			options: { ...current },
			resolved,
			spec: buildSpecData(config, current, resolved),
		};

		if (announce) {
			if (resolved.valid) {
				announce(`SKU resolved: ${resolved.sku}`);
			} else if (selectableKeys(config).every((key) => current[key])) {
				announce('Invalid combination. Please adjust your selection.');
			}
		}

		onChange?.(state);
	}

	// Initial sync (applies initial selection + availability).
	sync();

	return {
		root,
		getOptions: () => ({ ...current }),
		getResolved: () => resolve(config, current),
		/**
		 * @param {Record<string, string>} next
		 */
		setOptions(next = {}) {
			current = { ...next };
			selectable.forEach((prop) => {
				root.querySelectorAll(`input[name="${namePrefix}__${prop.key}"]`).forEach((input) => {
					input.checked = input.value === current[prop.key];
				});
			});
			sync();
		},
		destroy() {
			container.replaceChildren();
		},
	};
}
