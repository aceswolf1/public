# Handoff: HR Healthcare Sample System (sample-cart)

**Date:** 2026-09-10 · **Status:** all 8 tasks complete, cross-worker seam validated, on disk (uncommitted).
**Plugin:** `wp-content/plugins/hr-healthcare-sample-system/` · Built to `.claude/sample_cart_implementation_guide.md`.

## What was built (by worker track)

### Backend — PHP (worker-1 → 1b → 1c)
- **Scaffold:** main plugin file (header, PHP 8.1, GPL-2.0+), `composer.json` (PSR-4 + PhpSpreadsheet ^2.0), `phpcs.xml`, `src/Plugin.php` singleton, `src/Support/Manifest.php` (manifest reader — validated against the real dist manifest, ok:true).
- **Data spine (`src/Data/`):** `Schema.php` (3 `$wpdb->prefix` tables via dbDelta), `Activation.php`, `Groups/Options/Skus.php`, `Resolver.php` (the ONE resolver), `Support/ImageMap.php` (cached slug→page_id + featured-image/placeholder fallback), `Importer.php` (PhpSpreadsheet → normalize → diff-sync → pre-commit diff → URL-less rejection).
- **Admin (`src/Admin/`):** `Metabox.php` (`_hrh_sample_group_tag`), `SettingsPage.php` (4 tabs: Import / Configuration [GF form dropdown + field scan, checkout page picker, cart cap] / Setup & Usage / Options viewer). `Support/Settings.php` helper.
- **REST (`src/Rest/`):** `Routes.php` + `GroupsController.php` (batched + single, resolved image_url) + `ResolveController.php`. Namespace `hrh-sample/v1`, nonce-protected.
- **Frontend PHP (`src/Frontend/`):** `Assets.php` (conditional enqueue + localize), `ProductModal.php` (wp_footer shell §16), `CartShortcodes.php` (both shortcodes + enqueue flag).
- **Integration (`src/Integration/`):** `GravityForms.php` — form-ID gate, `gform_validation` (cart non-empty + all lines resolve), `gform_pre_submission` (resolve via `Data\Resolver`, write readable+json by marker class, clear selections), entries-list summary column.
- **Polish:** `uninstall.php`, `languages/hr-healthcare-sample-system.pot`, `§10` edge guards, `composer install --no-dev` shippable vendor.

### Frontend — JS/SCSS (worker-2 → 2b)
- **Build:** `package.json`, `vite.config.js` (frontend/admin entries + manifest → `assets/dist/`), bundled placeholder image.
- **Components (`assets/src/js/`):** `PropertySelector.js`, `SpecBlock.js`, `LineItemCard.js` (reusable leaves), `resolver.js`, `a11y.js`, `api.js`, `dom.js`.
- **Machines:** `modal.js` (3-step Select→Review→Confirm, three-node indicator, Back preserves selection, dup+cap gates at Review, `.hrh-sample-open-modal` delegation, focus trap), `cart.js` (cookie state, render, count, remove, edit-in-place, checkout resolve, invalid self-heal), `admin.js`, `frontend.js` wiring.
- **SCSS:** `_tokens` (font:inherit + brand tokens), `_a11y`, `_ui`, `modal`, `cart`, `admin`. Distinct frontend vs admin CSS chunks. Contrast ≥4.5:1; inert background + scroll lock; reduced-motion.

## Integration validation (orchestrator, at convergence)
Seam checks PASS — the two tracks agree on every shared identifier:
- Localized object `HRH_SAMPLE_CONFIG`/`HRH_SAMPLE_BOOT` — emitted by `Frontend/Assets.php`, consumed by `frontend.js`/`modal.js`/`api.js`.
- REST namespace `hrh-sample/v1` — consistent PHP (`Rest/*`) ↔ JS (`api.js`).
- Cookie `hrh_sample_cart` — consistent.
- GF marker classes `hrh-sample-field-{selections,readable,json}` — consistent PHP ↔ documented setup.
- Manifest reader (PHP) resolves the real hashed dist (JS build) — verified ok:true.

## Verified vs. NOT verified
**Verified (in dev):** importer against the real cleaned sheet (`.claude/product_import_sheet.xlsx`) → **26 groups / 206 options / 239 SKUs**, URL-less group correctly **skipped** (rows 132–141, listed by product), **idempotent** re-run (added:0/updated:0/removed:0/unchanged:239), resolve matches guide example (`ecovue-hv 20g/packet → SKU 380NW, HCPCS A4559, 5/bx`). Modal 3-step + dup/cap gates smoke-passed. Build produces distinct frontend/admin bundles. Lint was clean before the no-dev vendor strip.

**NOT verified (environment limits — expected per guide §20):**
- **Live WP activation / admin importer UI / REST-over-HTTP / browser modal** — the worker shells couldn't reach the Local-by-Flywheel MySQL. Static + direct-PHP verification only. **Recommend a manual pass inside Local.**
- **Gravity Forms end-to-end submit** — the checkout form doesn't exist in dev yet (§20). Integration is built to spec; verify once the form is created.

## Open items / confirm
- **SKU count 239 vs guide §20 estimate 278** — believed correct by design: `combo_json` keys on *selectable* props only, so rows differing only in fixed props/metadata collapse to one combination; plus the skipped URL-less group. **Confirm** the collapse is intended (spot-check a group whose raw rows > combinations) before treating 239 as final.
- **`composer lint` currently errors** (`phpcs not found`) because vendor is in shippable `--no-dev` state. Run `composer install` (restores dev deps) to re-lint; re-run `composer install --no-dev` before committing for deploy.
- **Plugin is untracked in git.** For WP Migrate deploy (§14): review, then commit the plugin dir **including `vendor/` and `assets/dist/`** (do NOT gitignore them — deliberate per §14), exclude `node_modules/`.

## Post-build fix — PHP platform mismatch (RESOLVED 2026-09-10)
Workers ran `composer install` under the system CLI **PHP 8.3**, so a transitive dep
(`maennchen/zipstream-php` 3.2.2, pulled by PhpSpreadsheet) resolved to a PHP-8.2+ version and
`vendor/composer/platform_check.php` was generated demanding **PHP ≥ 8.3.0** → **fatal on the
Local runtime (PHP 8.2.x)**. Fix applied: pinned `config.platform.php = "8.1"` in `composer.json`
(matches the plugin's declared *Requires PHP 8.1*), then `composer update` + `composer install
--no-dev`. zipstream downgraded to 3.1.1 (8.1-compatible); platform_check floor now `>= 80100`.
**Verified: platform_check passes under Local's PHP 8.2.27.** The platform pin is durable — any
future `composer install/update` on any build machine now resolves for 8.1+, so this won't recur.
Re-run `composer install --no-dev` before committing the shippable `vendor/`.

## Post-build fix — ES module enqueue (RESOLVED 2026-09-10)
Vite emits **ES modules** (the bundles use `import.meta.url`), but `wp_enqueue_script` renders a
**classic** `<script>` tag → browser error *"Cannot use 'import.meta' outside a module"* on the
frontend bundle (and would hit admin.js on the settings screen too). Fix: `Frontend/Assets.php`
now adds a `script_loader_tag` filter (`filter_module_type`) that marks the two plugin handles
(`hrh-sample-frontend`, `hrh-sample-admin`) as `type="module"`. Chosen over `wp_enqueue_script_module`
(WP 6.5+ only; plugin targets 6.0+) and over reformatting Vite to IIFE (multi-entry IIFE is
unsupported by Rollup without restructuring). The localized `-js-extra` data script stays classic,
so `HRH_SAMPLE_CONFIG`/`HRH_SAMPLE_BOOT` globals are set before the deferred module runs.
**PHP-only change — no asset rebuild needed.** Verified: filter output correct for single/double-quoted
and type-present tags; non-plugin handles untouched.

## Manual wiring the human must do (part of "done" per §20)
1. Put the cleaned sheet where the admin importer uploads it (dev copy is at `.claude/product_import_sheet.xlsx`); run **Settings → Import**.
2. Set each product page's **group tag** (metabox) — this is what activates the modal/cart on a page.
3. Add the Elementor open button with class **`hrh-sample-open-modal`** on product pages.
4. Place shortcodes: **`[hrh_sample_cart]`** (header mini-cart) and **`[hrh_sample_cart_summary]`** (checkout page).
5. Build the **Gravity Form** and add the 3 marker-class fields (`hrh-sample-field-selections` hidden, `-readable`, `-json` admin-only); select it + the checkout page in **Settings → Configuration**.

## Orchestration note — teammate model instability (action for future runs)
All workers ran on grok (`xai/grok-build-0.1`/`grok-4.5`) via `CLAUDE_CODE_TEAMMATE_COMMAND`. Four tool-schema latch incidents: workers finished + committed their work, then emitted spontaneous `shutdown_approved`/`shutdown_rejected` structured messages instead of a plain `COMPLETE` — one (worker-2b) latched into an unbreakable loop and had its pane killed. **Zero work was lost** in any incident (each finished before latching); every case was recovered by respawn. **Durable fix: point teammate spawns at a stronger tool-schema-adherent model.** See `orchestration-ctx/team-status-sample-cart.md` incident log.
