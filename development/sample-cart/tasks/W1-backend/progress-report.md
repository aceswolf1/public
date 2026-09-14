# W1 Backend — Progress Report

## 2026-09-10

- Claimed Task #1 (`in_progress`).
- Read assignment + guide §§ Naming, 2, 6, 8, 14, 15, 16.
- Wrote `orchestration-ctx/decisions/contract.md` (cross-worker seams).
- Next: PHP scaffold (main file, composer.json, phpcs.xml, Plugin.php, Manifest.php) + `composer install` + lint.

### Resume (worker-1b)
- Replaced terminated worker; contract.md already present — did NOT rewrite.
- Marked Task #1 `in_progress`, owner `worker-1b`.
- Built PHP scaffold: main file, composer.json, phpcs.xml, `src/Plugin.php`, `src/Support/Manifest.php`.
- `composer install` OK (phpspreadsheet 2.4.7 + WPCS 3.4.1).
- `composer lint` clean (exit 0).
- Manifest `self_check` against worker-2 dist: `ok:true`.
- WP-CLI activation NOT-VERIFIED (DB connection error outside Local runtime).
- Completing Task #1 → claim #3 (data spine) next.
- Task #1 COMPLETE (WP activation later verified via Local PHP/socket).

### Task #3 — Data spine
- Built Schema/Activation/Groups/Options/Skus/Resolver/ImageMap/Importer.
- Hand fixture + real sheet verified (26 groups, 1 URL-less skip rows 132–141, ecovue-hv resolve OK, idempotent 239 SKUs).
- `composer lint` clean.
- Completing Task #3 → claim #5 next.

### Task #5 — Admin + REST + Frontend PHP shells
- Metabox, SettingsPage (4 tabs), REST Routes/Groups/Resolve, Assets/ProductModal/CartShortcodes, Settings helper.
- Verified: 3 REST routes, shortcodes, resolve+batched, cart shells, lint clean.
- Completing Task #5 → claim #7 next.

### Resume2 (worker-1c) — Task #7
- Replaced terminated worker at task boundary. Tasks #1/#3/#5 already on disk — NOT rebuilding.
- Claimed Task #7 `in_progress`, owner `worker-1c`.
- Read Resolver, SettingsPage, Settings, Plugin, guide §§7/10/11/14/19/20.
- Next: GravityForms.php + edge guards + uninstall.php + .pot + register service + lint + composer install --no-dev + marker-field docs.

### Task #7 — Gravity Forms + polish (COMPLETE)
- Built `src/Integration/GravityForms.php` (form-ID gate, validation, pre_submission, entries column).
- §10 edge guard in `Assets::detect_page_tag` (removed/unknown group).
- `uninstall.php` + `languages/*.pot` + Plugin registration + textdomain load.
- Marker-field docs in SettingsPage Setup tab + `gf-marker-fields.md`.
- `composer lint` clean (exit 0); `composer install --no-dev` shipped vendor (PhpSpreadsheet only).
- E2E GF submit NOT-VERIFIED (form absent in dev per §20); WP-CLI DB unreachable.
