/**
 * REST client — hrh-sample/v1
 * Contract §4. Nonce-protected.
 * Wave 3: session cache, seed-from-localized, batched ensure + retry helpers.
 *
 * Boot seam (pending Wave-3 Assets.php):
 *   window.HRH_SAMPLE_BOOT = { restUrl, nonce, cartCap?, checkoutUrl? }
 * falls back to HRH_SAMPLE_CONFIG.restUrl/nonce if present, then wpApiSettings.
 */

/**
 * @typedef {{ restUrl: string, nonce: string, cartCap?: number, checkoutUrl?: string|null }} ApiBoot
 * @typedef {import('./resolver.js').GroupConfig} GroupConfig
 */

/** @type {Map<string, GroupConfig>} */
const cache = new Map();

/**
 * @returns {ApiBoot}
 */
export function getBoot() {
	const boot = (typeof window !== 'undefined' && window.HRH_SAMPLE_BOOT) || {};
	const config = (typeof window !== 'undefined' && window.HRH_SAMPLE_CONFIG) || {};
	const wpApi = (typeof window !== 'undefined' && window.wpApiSettings) || {};

	const restUrl =
		boot.restUrl ||
		config.restUrl ||
		(wpApi.root ? `${String(wpApi.root).replace(/\/?$/, '/') }hrh-sample/v1` : '/wp-json/hrh-sample/v1');

	// Assets.php localizes `restNonce`; accept `nonce` as an alias for resilience.
	const nonce = boot.nonce || boot.restNonce || config.nonce || wpApi.nonce || '';

	return {
		restUrl: String(restUrl).replace(/\/?$/, ''),
		nonce: String(nonce),
		cartCap: Number(boot.cartCap) > 0 ? Number(boot.cartCap) : 10,
		checkoutUrl: boot.checkoutUrl || null,
	};
}

/**
 * @param {string} path
 * @param {RequestInit} [init]
 */
async function request(path, init = {}) {
	const { restUrl, nonce } = getBoot();
	const url = `${restUrl}${path.startsWith('/') ? path : `/${path}`}`;

	const headers = new Headers(init.headers || {});
	if (nonce) {
		headers.set('X-WP-Nonce', nonce);
	}
	if (init.body && !headers.has('Content-Type')) {
		headers.set('Content-Type', 'application/json');
	}

	const response = await fetch(url, {
		credentials: 'same-origin',
		...init,
		headers,
	});

	if (!response.ok) {
		const text = await response.text().catch(() => '');
		const error = new Error(`HRH Sample API ${response.status}: ${text || response.statusText}`);
		error.status = response.status;
		throw error;
	}

	if (response.status === 204) {
		return null;
	}

	return response.json();
}

/**
 * Batched group configs — cart open.
 * GET /groups?slugs=a,b,c → { a: <groupConfig>, … }
 * @param {string[]} slugs
 * @returns {Promise<Record<string, GroupConfig>>}
 */
export function fetchGroups(slugs = []) {
	const unique = [...new Set(slugs.filter(Boolean))];
	if (unique.length === 0) {
		return Promise.resolve({});
	}
	const query = encodeURIComponent(unique.join(','));
	return request(`/groups?slugs=${query}`);
}

/**
 * Single group config.
 * GET /group/{slug}
 * @param {string} slug
 * @returns {Promise<GroupConfig>}
 */
export function fetchGroup(slug) {
	if (!slug) {
		return Promise.reject(new Error('slug required'));
	}
	return request(`/group/${encodeURIComponent(slug)}`);
}

/**
 * Server resolve — authority for cart + GF.
 * POST /resolve  { slug, options }
 * @param {string} slug
 * @param {Record<string, string>} options
 * @returns {Promise<{ sku: string, valid: boolean, product_name: string, hcpcs: string, sample_amount: string }>}
 */
export function resolveRemote(slug, options = {}) {
	if (!slug) {
		return Promise.reject(new Error('slug required'));
	}
	return request('/resolve', {
		method: 'POST',
		body: JSON.stringify({ slug, options }),
	});
}

/**
 * Seed the page-session cache from a localized group config (product pages).
 * @param {GroupConfig|null|undefined} config
 */
export function seedCache(config) {
	if (config?.slug) {
		cache.set(String(config.slug), config);
	}
}

/**
 * @param {string} slug
 * @returns {GroupConfig|null}
 */
export function getCached(slug) {
	if (!slug) {
		return null;
	}
	return cache.get(String(slug)) || null;
}

/**
 * Snapshot of every cached config.
 * @returns {Record<string, GroupConfig>}
 */
export function getCacheSnapshot() {
	return Object.fromEntries(cache.entries());
}

/**
 * Clear the session cache (tests / forced refresh).
 */
export function clearCache() {
	cache.clear();
}

/**
 * Ensure the given slugs are in the session cache. Fetches only missing ones
 * in one batched REST call. Never invents empty configs on network failure —
 * callers must handle the thrown error and show a retry UI.
 *
 * @param {string[]} slugs
 * @param {{ onStatus?: (status: 'loading'|'ready'|'error', error?: Error) => void }} [opts]
 * @returns {Promise<Record<string, GroupConfig|null>>}
 */
export async function ensureGroups(slugs = [], opts = {}) {
	const unique = [...new Set((slugs || []).filter(Boolean).map(String))];
	const missing = unique.filter((slug) => !cache.has(slug));

	if (missing.length === 0) {
		opts.onStatus?.('ready');
		return Object.fromEntries(unique.map((slug) => [slug, cache.get(slug) || null]));
	}

	opts.onStatus?.('loading');
	try {
		const result = await fetchGroups(missing);
		Object.entries(result || {}).forEach(([slug, config]) => {
			if (config) {
				cache.set(String(slug), config);
			}
		});
		opts.onStatus?.('ready');
		return Object.fromEntries(unique.map((slug) => [slug, cache.get(slug) || null]));
	} catch (error) {
		opts.onStatus?.('error', error);
		throw error;
	}
}

/**
 * Seed from localized config if present (safe to call on every boot).
 */
export function seedFromLocalized() {
	if (typeof window === 'undefined') {
		return;
	}
	const config = window.HRH_SAMPLE_CONFIG;
	if (config?.slug) {
		seedCache(config);
	}
}
