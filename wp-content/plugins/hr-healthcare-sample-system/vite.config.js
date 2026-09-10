import { defineConfig } from 'vite';
import { resolve } from 'node:path';

/**
 * Vite build for the WordPress plugin.
 * Guide §14: two logical entries (frontend / admin), hashed filenames,
 * manifest at assets/dist/.vite/manifest.json for Support\Manifest.
 */
export default defineConfig({
	base: './',
	build: {
		manifest: true,
		outDir: 'assets/dist',
		emptyOutDir: true,
		// Keep the placeholder PNG as a real hashed file (not a data-URI) so PHP
		// ImageMap and <img src> can both reference a stable plugin-relative URL.
		assetsInlineLimit: 0,
		rollupOptions: {
			input: {
				frontend: resolve(__dirname, 'assets/src/js/frontend.js'),
				admin: resolve(__dirname, 'assets/src/js/admin.js'),
			},
		},
	},
});
