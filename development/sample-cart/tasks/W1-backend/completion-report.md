# Completion Report — Task #7 (Wave 4: Gravity Forms + PHP polish)

**Worker:** worker-1c (pt-backend-eng)
**Date:** 2026-09-10
**Status:** COMPLETE

## Deliverables

### 1. `src/Integration/GravityForms.php`
Form-ID gated GF integration reusing `Data\Resolver` and `Settings::checkout_form_id()`:

| Hook | Behavior |
|---|---|
| `gform_validation` | No-op unless configured form. Blocks empty cart; re-resolves every `{slug,options}` against live tables; never trusts posted SKU. |
| `gform_pre_submission` | Resolves via `Data\Resolver`; writes readable + JSON into marker fields; blanks selections. |
| `gform_entry_list_columns` + `gform_entries_column_filter` | "Samples" column on the configured form; summary from stored JSON (e.g. `3 items · TruCath Duo +2`). |

Marker classes located by CSS class (not field ID):
- `hrh-sample-field-selections`
- `hrh-sample-field-readable`
- `hrh-sample-field-json`

### 2. PHP edge/empty guards (§10)
- **No tag** — `Assets::detect_page_tag` leaves `page_slug` null → no modal/cart UI (existing ProductModal/Assets behavior).
- **Removed group / empty tables** — tag present but `Groups::find_by_slug` null → UI suppressed; `error_log` notice when `WP_DEBUG`.
- **Dead cart line** — REST `resolve` already returns `valid:false`; GF `gform_validation` blocks submit with re-select message.
- **Empty cart at checkout** — GF validation message: "Your cart is empty…".
- **Fresh-migrated empty tables** — Activation/`Schema::create` already creates empty tables; frontend degrades (no group → no UI).

### 3. `uninstall.php`
Drops `{$wpdb->prefix}hrh_sample_{skus,options,groups}`, deletes options (`checkout_form_id`, `checkout_page_id`, `cart_cap`, `db_version`, `slug_page_map`), and clears `_hrh_sample_group_tag` postmeta. Included in `phpcs.xml`.

### 4. i18n `.pot`
`languages/hr-healthcare-sample-system.pot` generated via `wp i18n make-pot` (80 msgids). `Plugin::boot()` calls `load_plugin_textdomain`.

### 5. Service registration
`Plugin::register_services()` instantiates `( new GravityForms() )->register();` (always, not admin-only — frontend submit needs the hooks).

### 6. Marker-field documentation
- Expanded Setup & Usage tab in `SettingsPage` with the three required fields + purpose.
- Handoff doc: `tasks/W1-backend/gf-marker-fields.md`.

### 7. WPCS + shippable vendor (§14)
- `composer lint` **clean (exit 0)** on all PHP including `uninstall.php` + `GravityForms.php` (verified *before* pruning).
- `composer install --no-dev --optimize-autoloader` → vendor retains PhpSpreadsheet 2.4.7 + deps; WPCS/PHPCS removed. Autoload resolves `Integration\GravityForms`.

## Files touched

| Path | Action |
|---|---|
| `src/Integration/GravityForms.php` | **created** |
| `uninstall.php` | **created** |
| `languages/hr-healthcare-sample-system.pot` | **created** |
| `src/Plugin.php` | register GF + load_plugin_textdomain |
| `src/Frontend/Assets.php` | §10 removed-group / empty-tables guard |
| `src/Admin/SettingsPage.php` | expanded marker-field docs |
| `phpcs.xml` | include `uninstall.php` |
| `vendor/` | pruned to no-dev (shippable) |
| `tasks/W1-backend/gf-marker-fields.md` | **created** (docs) |

## Verification (verbatim)

```text
$ composer lint
> phpcs
........ 8 / 8 (100%)
Time: 421ms; Memory: 14MB
# exit 0

$ composer install --no-dev --optimize-autoloader
Package operations: 0 installs, 0 updates, 8 removals
  - Removing wp-coding-standards/wpcs (3.4.1)
  … (phpcs + phpcompatibility + dealerdirect)
Generating optimized autoload files

$ php -r 'require "vendor/autoload.php"; echo class_exists("HR_Healthcare\\Sample_System\\Integration\\GravityForms")?"GF_CLASS_OK\n":"MISS\n";'
GF_CLASS_OK

$ php -l src/Integration/GravityForms.php
No syntax errors detected in …/GravityForms.php

$ wp i18n make-pot . languages/hr-healthcare-sample-system.pot …
Success: POT file successfully generated.
$ rg -c '^msgid ' languages/hr-healthcare-sample-system.pot
80
$ rg -n "Your cart is empty|Samples" languages/hr-healthcare-sample-system.pot
323:msgid "Your cart is empty. Add at least one sample before submitting."
337:msgid "Samples"
```

### NOT-VERIFIED (with reason)
- **End-to-end GF submit** (empty-cart block + readable/json write + entries column): client's checkout form with the three marker fields does not exist in dev yet (guide §20). Built to spec; documented in `gf-marker-fields.md` + Setup & Usage tab. Manual verify after form is created.
- **WP-CLI plugin activation / live hook smoke**: Local DB not reachable from this shell (`Error establishing a database connection`) — same constraint noted by prior workers. PHP syntax + autoload verified offline.

## Deferred / manual (expected per §20)
1. Create/extend the client's GF checkout form with the three marker-class fields.
2. Select that form under Sample System → Configuration; confirm field scan.
3. Place shortcodes / Elementor trigger / page tags (human wiring).
