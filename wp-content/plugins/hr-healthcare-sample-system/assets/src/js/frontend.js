/**
 * Frontend entry — modal + cart state machines.
 * Wave 3: wires modal.js + cart.js via document event delegation.
 * CSS: modal.scss + cart.scss only (admin.scss stays on admin.js).
 */

import '../scss/modal.scss';
import '../scss/cart.scss';

import placeholderUrl from '../images/product-placeholder.png';

import * as resolver from './resolver.js';
import * as a11y from './a11y.js';
import * as api from './api.js';
import * as cart from './cart.js';
import { initModal } from './modal.js';
import { initPicker } from './picker.js';
import { initCart, initCheckout } from './cart.js';
import { mountPropertySelector } from './components/PropertySelector.js';
import { renderSpecBlock, fillSpecBlock } from './components/SpecBlock.js';
import {
	renderLineItemCard,
	updateLineItemCard,
	getLineEditMount,
	setLineEditOpen,
} from './components/LineItemCard.js';

export const HRH_SAMPLE_PLACEHOLDER_IMAGE = placeholderUrl;

export {
	resolver,
	a11y,
	api,
	cart,
	initModal,
	initPicker,
	initCart,
	mountPropertySelector,
	renderSpecBlock,
	fillSpecBlock,
	renderLineItemCard,
	updateLineItemCard,
	getLineEditMount,
	setLineEditOpen,
};

/**
 * Boot frontend surfaces once the DOM is ready.
 * Seeds the config cache from localized HRH_SAMPLE_CONFIG (product pages),
 * then binds modal + cart document delegation.
 */
/**
 * Ensure PHP-printed shells carry the token/a11y surface class (§18).
 * ProductModal / cart dialog omit `.hrh-sample-root`; JS adds it so SCSS tokens apply.
 */
function ensureSurfaceRoots() {
	document
		.querySelectorAll('.hrh-sample-cart, .hrh-sample-cart-summary')
		.forEach((node) => {
			node.classList.add('hrh-sample-root');
		});
}

export function bootFrontend() {
	if (typeof window === 'undefined' || typeof document === 'undefined') {
		return null;
	}

	window.HRH_SAMPLE_PLACEHOLDER_IMAGE = HRH_SAMPLE_PLACEHOLDER_IMAGE;
	if (window.HRH_SAMPLE_BOOT?.placeholder) {
		window.HRH_SAMPLE_PLACEHOLDER_IMAGE = window.HRH_SAMPLE_BOOT.placeholder;
	}

	api.seedFromLocalized();

	const start = () => {
		ensureSurfaceRoots();
		initPicker();
		initCart();
		initCheckout();
	};

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', start, { once: true });
	} else {
		start();
	}

	const apiSurface = {
		version: '0.3.0-wave3',
		placeholder: HRH_SAMPLE_PLACEHOLDER_IMAGE,
		resolver,
		a11y,
		api,
		cart,
		initModal,
		initPicker,
		initCart,
		mountPropertySelector,
		renderSpecBlock,
		fillSpecBlock,
		renderLineItemCard,
		updateLineItemCard,
		getLineEditMount,
		setLineEditOpen,
	};

	window.HRH_SAMPLE_FRONTEND = apiSurface;
	return apiSurface;
}

bootFrontend();
