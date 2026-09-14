# Team Plan: sample-cart

**Feature:** HR Healthcare Sample System — a custom WordPress plugin adding a "request sample"
cart to an Elementor site. Full spec is the authoritative contract:
`.claude/sample_cart_implementation_guide.md` (§§1–20). Workers treat that guide as the
source of truth; this plan only sequences the work and defines worker boundaries.

**Slug:** `sample-cart` · **Plugin dir:** `wp-content/plugins/hr-healthcare-sample-system/`
· **Workers:** 2 · **Model:** harness-resolved (do not set)

---

## Existing Patterns Used

Greenfield plugin — no prior plugin code to extend. Conventions are dictated by the guide:
- PSR-4 namespaced `src/` (`HR_Healthcare\Sample_System\`), Composer autoload, **not** legacy `class-*.php`.
- Vite build (`assets/src/` → `assets/dist/` + `.vite/manifest.json`), vanilla JS ES modules, SCSS.
- WPCS-linted PHP (`phpcs.xml`), PHP 8.1+.
- Naming reference table (guide §"Naming reference") is mandatory for every identifier.
- Host env confirmed: PHP 8.3, Node 22, Composer 2.6; Gravity Forms plugin installed; ACF Pro
  installed but MUST NOT be used (guide §4).

## Worker Boundaries (clean file split — no shared files)

- **W1 — Backend (pt-backend-eng)** owns: the main plugin file, `composer.json`, `phpcs.xml`,
  and everything under `src/**` (Data, Admin, Rest, Integration, Support, and the Frontend PHP
  shells `Assets.php`/`ProductModal.php`/`CartShortcodes.php`). Also `uninstall.php`, `.pot`.
- **W2 — Frontend (pt-frontend-eng)** owns: `package.json`, `vite.config.js`, and everything
  under `assets/**` (all JS components + entries, all SCSS, the bundled placeholder image, and
  committed `assets/dist/`).

**The one seam:** W1's Frontend PHP renders the DOM shells (§16), localizes `HRH_SAMPLE_CONFIG`
(§2/§6), enqueues the `frontend`/`admin` bundles via the manifest, and exposes REST (§8). W2's JS
hydrates those shells and calls those endpoints. The seam is frozen by the guide (§2 config shape,
§6 cookie shape, §8 REST routes, §16 exact DOM + CSS classes) and pinned in
`orchestration-ctx/decisions/contract.md` (W1 writes it in Wave 1).

## Data / test input

User will provide the cleaned `product_import_sheet.xlsx` at
`wp-content/uploads/product_import_sheet.xlsx`. Until present, W1 verifies the importer with a
small hand-authored fixture covering: multi-value (selectable) prop, single-value (fixed) prop,
`label_{prop}` friendly label, and one URL-less group (must be reported skipped). Real-data
verification (26 groups / 278 SKUs) runs once the user drops the cleaned sheet.

---

## Wave plan & dependencies

### Wave 1 — Scaffold + Contract (parallel)
- **W1** [Phase 0 PHP]: main plugin file (header docblock), `composer.json` (PSR-4 +
  phpoffice/phpspreadsheet ^2.0 + WPCS dev deps), `phpcs.xml`, `composer install`,
  `src/Plugin.php` singleton boot on `plugins_loaded`, `src/Support/Manifest.php` reader.
  **First deliverable:** `orchestration-ctx/decisions/contract.md` pinning the seams
  (localized object name+shape, REST routes+payloads, cookie shape, DOM hooks + CSS/marker
  classes, `.vite/manifest.json` path). ~8 pts.
- **W2** [Phase 0 assets]: `package.json`, `vite.config.js` (frontend/admin inputs + manifest,
  outDir `assets/dist`), `assets/src/` skeleton (scss `_tokens`/`_a11y`, js `frontend.js`/
  `admin.js` stubs), bundle neutral placeholder product image, `npm run build` produces
  `assets/dist` + `.vite/manifest.json`. ~5 pts.

### Wave 2 — Data spine + Frontend leaf components (parallel)
- **W1** [Phase 1]: `Data/Schema.php` (3 prefixed tables, dbDelta), `Activation.php`,
  `Data/Groups|Options|Skus.php`, `Data/Resolver.php` (the ONE resolver), `Support/ImageMap.php`
  (cached slug→page_id, featured-image + placeholder fallback), `Data/Importer.php`
  (PhpSpreadsheet parse → normalize → diff-sync insert/update/delete by key → pre-commit diff →
  URL-less rejection listing each skipped group by row range). Verify against fixture. ~13 pts.
  *blockedBy W1-Wave1.*
- **W2** [Phase 4 leaves]: `components/PropertySelector.js` (tiles/radiogroup + locked specs +
  live resolve), `components/SpecBlock.js`, `components/LineItemCard.js`, `resolver.js`
  (client resolve + dead-combo disable), `a11y.js`, `api.js`, SCSS `modal.scss`/`cart.scss`
  (tokens, low-specificity, `.hrh-sample-root` scope, theme-font inherit). ~13 pts.
  *blockedBy W1-Wave1 (contract.md), W2-Wave1 (build pipeline).*

### Wave 3 — Admin + REST + Frontend PHP (W1); Modal + Cart machines (W2)
- **W1** [Phases 2+3]: `Admin/Metabox.php` (`_hrh_sample_group_tag`, choices from Groups),
  `Admin/SettingsPage.php` (Import / Configuration [form dropdown + field scan, page picker,
  cart cap] / Setup & Usage / Options viewer), `Rest/Routes.php`, `Rest/GroupsController.php`
  (batched + single, includes resolved `image_url`), `Rest/ResolveController.php`,
  `Frontend/Assets.php` (conditional register/enqueue via manifest, localize config on tagged
  pages), `Frontend/ProductModal.php` (wp_footer shell §16), `Frontend/CartShortcodes.php`
  (`[hrh_sample_cart]` + `[hrh_sample_cart_summary]`, enqueue flag). ~13 pts.
  *blockedBy W1-Wave2.*
- **W2** [Phases 4+5 machines]: `modal.js` (3-step machine, three-node indicator, Back preserves
  selection, commit+dup+cap at Review, `.hrh-sample-open-modal` delegation, focus trap),
  `cart.js` (cookie state, render LineItemCards, count, remove, edit-in-place, checkout-page
  resolve, invalid-line self-heal), `admin.js` (importer/settings UI), wire `frontend.js`. ~13 pts.
  *blockedBy W2-Wave2.*

### Wave 4 — Gravity Forms + Polish (parallel)
- **W1** [Phases 6+7 PHP]: `Integration/GravityForms.php` (form-id gate, `gform_validation`
  [cart non-empty + all lines resolve], `gform_pre_submission` [resolve → write readable+json by
  marker class, clear selections], entries-list summary column). PHP edge/empty guards (§10),
  `uninstall.php`, i18n `.pot`, `composer lint` clean, `composer install --no-dev` vendor tree
  committed. Document the 3 marker-class fields (form built manually later — §20). ~10 pts.
  *blockedBy W1-Wave3.*
- **W2** [Phase 7 frontend]: theme-font inheritance + token scoping verified against live theme
  (§18), full a11y pass both surfaces (§17: dialog roles, focus trap/restore, Esc, live-region,
  keyboard, reduced-motion), final `npm run build` + committed `assets/dist`. ~5 pts.
  *blockedBy W2-Wave3.*

## Integration & convergence
- W1 & W2 outputs meet only at the frozen seam (contract.md). QA the seam: does W2's JS read the
  exact localized shape W1 emits? Do REST payloads match? Do CSS/DOM hooks match §16?
- Final handoff (`development/sample-cart/06-handoff.md`): what each worker delivered, the manual
  wiring the human must do (Elementor trigger button, place 2 shortcodes, create GF + 3 marker
  fields, set page tags, provide cleaned sheet), run/test instructions, deferred items.

## Communication
All cross-worker questions route through team-lead. The contract.md is the only shared reference;
neither worker edits the other's files. Planned relay: if W1 changes any seam detail, team-lead
relays the delta to W2 (and vice versa).
