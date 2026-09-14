/**
 * Client-side resolver — mirrors Data\Resolver.
 * {slug, options} => sku from a cached/localized group config.
 * Also computes which tile values remain reachable for dead-combo disabling.
 */

/**
 * @typedef {{ key: string, label: string }} ValueRef
 * @typedef {{
 *   key: string,
 *   label: string,
 *   selectable: boolean,
 *   values?: ValueRef[],
 *   value?: ValueRef
 * }} Property
 * @typedef {{
 *   options: Record<string, string>,
 *   sku: string,
 *   product_name: string,
 *   hcpcs: string,
 *   sample_amount: string
 * }} Combination
 * @typedef {{
 *   slug: string,
 *   product_line?: string,
 *   page_url?: string,
 *   image_url?: string,
 *   properties: Property[],
 *   combinations: Combination[]
 * }} GroupConfig
 * @typedef {{
 *   valid: boolean,
 *   sku: string|null,
 *   product_name: string|null,
 *   hcpcs: string|null,
 *   sample_amount: string|null
 * }} ResolveResult
 */

/**
 * Selectable property keys in config order.
 * @param {GroupConfig} config
 * @returns {string[]}
 */
export function selectableKeys(config) {
	return (config?.properties ?? [])
		.filter((prop) => prop && prop.selectable)
		.map((prop) => prop.key);
}

/**
 * True when every selectable property has a non-empty selection.
 * @param {GroupConfig} config
 * @param {Record<string, string>} options
 */
export function isComplete(config, options = {}) {
	const keys = selectableKeys(config);
	return keys.length > 0 && keys.every((key) => Boolean(options[key]));
}

/**
 * Does a combination match the (possibly partial) selection?
 * Partial selections only constrain keys that are present.
 * @param {Combination} combo
 * @param {Record<string, string>} options
 */
function matches(combo, options) {
	return Object.entries(options).every(([key, value]) => {
		if (!value) {
			return true;
		}
		return combo.options?.[key] === value;
	});
}

/**
 * Resolve a full selection against the combination whitelist.
 * @param {GroupConfig} config
 * @param {Record<string, string>} options
 * @returns {ResolveResult}
 */
export function resolve(config, options = {}) {
	const empty = {
		valid: false,
		sku: null,
		product_name: null,
		hcpcs: null,
		sample_amount: null,
	};

	if (!config || !Array.isArray(config.combinations)) {
		return empty;
	}

	if (!isComplete(config, options)) {
		return empty;
	}

	const hit = config.combinations.find((combo) => matches(combo, options));
	if (!hit) {
		return empty;
	}

	return {
		valid: true,
		sku: hit.sku ?? null,
		product_name: hit.product_name ?? null,
		hcpcs: hit.hcpcs ?? null,
		sample_amount: hit.sample_amount ?? null,
	};
}

/**
 * Combinations still reachable given the current (partial) selection.
 * @param {GroupConfig} config
 * @param {Record<string, string>} options
 * @returns {Combination[]}
 */
export function reachableCombinations(config, options = {}) {
	if (!config || !Array.isArray(config.combinations)) {
		return [];
	}
	return config.combinations.filter((combo) => matches(combo, options));
}

/**
 * Value keys for `propKey` that appear in at least one reachable combination
 * when the other selected options are held fixed.
 * Used to disable dead-end tiles.
 * @param {GroupConfig} config
 * @param {string} propKey
 * @param {Record<string, string>} currentOptions
 * @returns {Set<string>}
 */
export function availableValues(config, propKey, currentOptions = {}) {
	const withoutSelf = { ...currentOptions };
	delete withoutSelf[propKey];

	const reachable = reachableCombinations(config, withoutSelf);
	const values = new Set();
	reachable.forEach((combo) => {
		const value = combo.options?.[propKey];
		if (value) {
			values.add(value);
		}
	});
	return values;
}

/**
 * Smart auto-switch: apply a pick of `value` on `axis`, keeping as many of the
 * user's other selections as stay valid and silently dropping ones that no
 * longer fit. Guarantees the returned selection matches >=1 combination
 * (assuming `value` exists in the config, which rendered tiles always do), so
 * the user is never trapped: picking any tile is always a legal move.
 *
 * @param {GroupConfig} config
 * @param {Record<string, string>} current Existing selection.
 * @param {string} axis Property key just picked.
 * @param {string} value Value key just picked.
 * @returns {Record<string, string>} Reconciled selection (new object).
 */
export function reconcileSelection(config, current = {}, axis, value) {
	let next = { [axis]: value };

	// Preserve prior picks on other axes when they remain reachable together
	// with the new pick; drop the ones that conflict (config order = priority).
	selectableKeys(config).forEach((key) => {
		if (key === axis) {
			return;
		}
		const prev = current[key];
		if (!prev) {
			return;
		}
		const candidate = { ...next, [key]: prev };
		if (reachableCombinations(config, candidate).length > 0) {
			next = candidate;
		}
	});

	return next;
}

/**
 * Friendly label lookup for a property value key.
 * @param {GroupConfig} config
 * @param {string} propKey
 * @param {string} valueKey
 * @returns {string}
 */
export function valueLabel(config, propKey, valueKey) {
	const prop = (config?.properties ?? []).find((item) => item.key === propKey);
	if (!prop) {
		return valueKey;
	}
	if (prop.selectable && Array.isArray(prop.values)) {
		const hit = prop.values.find((item) => item.key === valueKey);
		return hit?.label ?? valueKey;
	}
	if (!prop.selectable && prop.value?.key === valueKey) {
		return prop.value.label ?? valueKey;
	}
	return valueKey;
}

/**
 * Build SpecBlock rows from a resolved selection + config.
 * @param {GroupConfig} config
 * @param {Record<string, string>} options
 * @param {ResolveResult} resolved
 * @returns {{ product_name: string, sku: string, hcpcs: string, sample_amount: string, properties: {label:string,value:string}[] }}
 */
export function buildSpecData(config, options, resolved) {
	const properties = (config?.properties ?? [])
		.filter((prop) => prop.selectable)
		.map((prop) => ({
			label: prop.label,
			value: valueLabel(config, prop.key, options[prop.key]),
		}))
		.filter((row) => row.value);

	// Fixed props also show on the review card.
	(config?.properties ?? [])
		.filter((prop) => !prop.selectable && prop.value)
		.forEach((prop) => {
			properties.push({
				label: prop.label,
				value: prop.value.label ?? prop.value.key,
			});
		});

	return {
		product_name: resolved?.product_name ?? '',
		sku: resolved?.sku ?? '',
		hcpcs: resolved?.hcpcs ?? '',
		sample_amount: resolved?.sample_amount ?? '',
		properties,
	};
}
