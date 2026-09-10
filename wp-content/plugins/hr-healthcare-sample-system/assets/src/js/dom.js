/**
 * Tiny DOM helpers — escape + element factory.
 * Keeps leaf components free of XSS when rendering config labels.
 */

/**
 * @param {unknown} value
 * @returns {string}
 */
export function escapeHtml(value) {
	return String(value ?? '')
		.replace(/&/g, '&amp;')
		.replace(/</g, '&lt;')
		.replace(/>/g, '&gt;')
		.replace(/"/g, '&quot;')
		.replace(/'/g, '&#39;');
}

/**
 * @param {string} tag
 * @param {Record<string, string|boolean|null|undefined>} [attrs]
 * @param {(Node|string)[]} [children]
 * @returns {HTMLElement}
 */
export function el(tag, attrs = {}, children = []) {
	const node = document.createElement(tag);
	Object.entries(attrs).forEach(([key, val]) => {
		if (val === null || val === undefined || val === false) {
			return;
		}
		if (key === 'className') {
			node.className = String(val);
			return;
		}
		if (key === 'text') {
			node.textContent = String(val);
			return;
		}
		if (key === 'html') {
			node.innerHTML = String(val);
			return;
		}
		if (key.startsWith('on') && typeof val === 'function') {
			node.addEventListener(key.slice(2).toLowerCase(), val);
			return;
		}
		if (val === true) {
			node.setAttribute(key, '');
			return;
		}
		node.setAttribute(key, String(val));
	});
	children.forEach((child) => {
		if (child === null || child === undefined) {
			return;
		}
		node.append(typeof child === 'string' ? document.createTextNode(child) : child);
	});
	return node;
}
