# Cross-Worker Contract — HR Healthcare Sample System

Frozen seams distilled from `/.claude/sample_cart_implementation_guide.md`.
**No new decisions.** Guide wins on any conflict. Worker-2 builds against this.

---

## 1. Identifiers (exact)

| Thing | Value |
|---|---|
| Plugin slug / text domain | `hr-healthcare-sample-system` |
| PHP namespace | `HR_Healthcare\Sample_System\` → `src/` |
| Localized JS object | `window.HRH_SAMPLE_CONFIG` |
| Cookie name | `hrh_sample_cart` |
| REST namespace | `hrh-sample/v1` |
| CSS / GF marker prefix | `hrh-sample-` |
| Root wrapper | `.hrh-sample-root` |
| Open-modal trigger (Elementor) | `.hrh-sample-open-modal` (event-delegated) |
| Shortcodes | `[hrh_sample_cart]`, `[hrh_sample_cart_summary]` |
| GF field markers | `hrh-sample-field-selections`, `hrh-sample-field-readable`, `hrh-sample-field-json` |

---

## 2. Localized object — `window.HRH_SAMPLE_CONFIG`

Printed via `wp_localize_script` on tagged product pages only. Shape = one group config (§2):

```json
{
  "slug": "ecovue-hv",
  "product_line": "EcoVue",
  "page_url": "https://…/ecovue-hv/",
  "image_url": "https://…/ecovue-hv.jpg",
  "properties": [
    {
      "key": "dimension_length",
      "label": "Dimension / Length",
      "selectable": true,
      "values": [ { "key": "20g", "label": "20 g" }, { "key": "250ml", "label": "250 ml" } ]
    },
    {
      "key": "viscosity",
      "label": "Viscosity",
      "selectable": false,
      "value": { "key": "high", "label": "High" }
    }
  ],
  "combinations": [
    {
      "options": { "dimension_length": "20g", "type": "safewrap_packet" },
      "sku": "380",
      "product_name": "EcoVue® HV Sterile Ultrasound Gel",
      "hcpcs": "A4559",
      "sample_amount": "5/bx"
    }
  ]
}
```

Rules:
- Selectable props → `values[]` (`{key,label}`). Fixed props → single `value` (`{key,label}`).
- Combinations mention **selectable keys only** + SKU display metadata (`sku`, `product_name`, `hcpcs`, `sample_amount`).
- `properties` = UI contract (order + selectable/fixed). `combinations` = resolver whitelist.
- `image_url` resolved live from the group's page featured image (placeholder fallback if missing). Never in the cookie.
- On non-product pages this object is absent; cart seeds its cache via REST.

---

## 3. Cookie — `hrh_sample_cart`

**Selections only. Never resolved data.**

```json
[
  { "slug": "ecovue-hv", "options": { "dimension_length": "20g", "type": "packet" } }
]
```

- No `qty`, no consent, no SKU/name/hcpcs/image.
- Line identity = resolved SKU of `{slug, options}`. Duplicate SKUs disallowed at add.
- Cap = settings default 10 (keeps under ~4 KB).
- Stale (no longer resolves) → flag "please re-select" at load / cart-open / submit; never silent-drop.

---

## 4. REST — `hrh-sample/v1` (nonce-protected)

| Method | Path | Purpose | Response |
|---|---|---|---|
| `GET` | `/groups?slugs=a,b,c` | Batched configs (cart open) | `{ "a": <groupConfig>, "b": <groupConfig>, … }` — same shape as §2 |
| `GET` | `/group/{slug}` | Single group config | `<groupConfig>` |
| `POST` | `/resolve` | `{slug, options}` → SKU | `{ "sku": "…", "valid": true, "product_name": "…", "hcpcs": "…", "sample_amount": "…" }` |

- Group configs include resolved `image_url`.
- Client may also resolve from cached/localized config; REST is the authority (cart + GF submit).
- Never trust a cookie-stored SKU on submit.

---

## 5. Vite manifest & bundle names

| Item | Value |
|---|---|
| Manifest path (relative to plugin root) | `assets/dist/.vite/manifest.json` |
| Logical entry names | `frontend`, `admin` |
| Source entries | `assets/src/js/frontend.js`, `assets/src/js/admin.js` |
| Out dir | `assets/dist` |

PHP `Support\Manifest` resolves logical → hashed file. Enqueues reference `frontend` / `admin` only — never literal filenames. Missing manifest → degrade gracefully (no enqueue, no fatal).

`assets/dist/` (built CSS/JS + manifest) is committed. `node_modules/` is not. PHP owns enqueue; worker-2 owns the Vite build that produces the manifest.

---

## 6. DOM structure & CSS / marker classes (§16)

All classes under the `hrh-sample-` prefix. Exact shells PHP prints / JS mounts into:

### Open trigger
- `.hrh-sample-open-modal` — Elementor-added; JS binds via `document` delegation (`e.target.closest(...)`).

### PropertySelector (reusable: modal step 1 + cart inline edit)
```
.hrh-sample-selector[data-slug]
  fieldset.hrh-sample-prop
    legend.hrh-sample-prop__label          ("Select " + label)
    .hrh-sample-prop__tiles[role=radiogroup]
      label.hrh-sample-tile
        input[type=radio].hrh-sample-sr-only[name=<prop.key>][value=<value.key>]
        span                              (value.label)
  p.hrh-sample-prop.hrh-sample-prop--fixed   (fixed props)
    span.hrh-sample-prop__label
    span                                  (value.label)
  .hrh-sample-selector__foot
    p.hrh-sample-selector__sku → span[data-sku-out]
    button.hrh-sample-btn.hrh-sample-btn--primary[data-action=to-review]
```

### Product modal (3 steps) — `wp_footer` on tagged pages
```
.hrh-sample-modal[role=dialog][aria-modal=true][aria-labelledby=hrh-sample-modal-title][hidden]
  .hrh-sample-modal__backdrop[data-close]
  .hrh-sample-modal__dialog
    header.hrh-sample-modal__header
      h2#hrh-sample-modal-title.hrh-sample-modal__title
      button.hrh-sample-modal__close[data-close]
    ol.hrh-sample-steps                           (3 nodes: Selection → Review → Confirmation)
    .hrh-sample-modal__layout
      .hrh-sample-modal__media > img
      .hrh-sample-modal__body
        section.hrh-sample-step[data-step=select][aria-live=polite]
          .hrh-sample-selector
        section.hrh-sample-step[data-step=review][hidden]
          dl.hrh-sample-card__specs               (SpecBlock)
          .hrh-sample-step__actions
            button.hrh-sample-btn[data-action=back]
            button.hrh-sample-btn.hrh-sample-btn--primary[data-action=add-to-cart]
        section.hrh-sample-step[data-step=confirm][hidden][role=status]
          .hrh-sample-success
          p.hrh-sample-success__msg
          .hrh-sample-step__actions
            button.hrh-sample-btn[data-action=continue]
            button.hrh-sample-btn.hrh-sample-btn--primary[data-action=view-cart]
    p.hrh-sample-sr-only[role=status][aria-live=polite]
```

### Cart icon + dialog — `[hrh_sample_cart]` (icon in place; dialog on `wp_footer`)
```
button.hrh-sample-cart-icon[aria-haspopup=dialog][aria-controls=hrh-sample-cart]
  span.hrh-sample-cart-icon__count

#hrh-sample-cart.hrh-sample-cart[role=dialog][aria-modal=true][aria-labelledby=hrh-sample-cart-title][hidden]
  .hrh-sample-cart__backdrop[data-close]
  .hrh-sample-cart__dialog
    header.hrh-sample-cart__header
      h2#hrh-sample-cart-title
      button.hrh-sample-cart__close[data-close]
    ul.hrh-sample-cart__list                  (LineItemCards)
    footer.hrh-sample-cart__footer
      button.hrh-sample-btn[data-action=continue]
      button.hrh-sample-btn.hrh-sample-btn--primary[data-action=checkout]   ("Proceed to Checkout")
    p.hrh-sample-sr-only[role=status][aria-live=polite]
```

### LineItemCard (cart dialog + `[hrh_sample_cart_summary]`)
```
li.hrh-sample-line[data-slug][data-sku]
  article.hrh-sample-card
    img.hrh-sample-card__img
    dl.hrh-sample-card__specs                 (SpecBlock shared partial)
      div > dt / dd                           (Product Name, SKU#, HCPCS, selected props, Quantity)
    .hrh-sample-card__actions
      button.hrh-sample-btn[data-action=edit][aria-expanded][aria-controls]
      button.hrh-sample-btn[data-action=cancel]
  .hrh-sample-line__edit[hidden]              (inline PropertySelector expander)
    .hrh-sample-selector
    .hrh-sample-line__edit-actions
      button.hrh-sample-btn[data-action=edit-cancel]
      button.hrh-sample-btn.hrh-sample-btn--primary[data-action=edit-save]
```

### Cart summary — `[hrh_sample_cart_summary]`
Same `ul.hrh-sample-cart__list` of LineItemCards, inline (no dialog wrapper / backdrop / focus-trap).

### Shared utilities
- `.hrh-sample-btn` / `.hrh-sample-btn--primary`
- `.hrh-sample-sr-only`
- SpecBlock partial class: `dl.hrh-sample-card__specs`

---

## 7. Ownership boundary

| Owner | Paths |
|---|---|
| Worker-1 (backend) | Main plugin file, `composer.json`, `phpcs.xml`, `uninstall.php`, `languages/`, **everything under `src/**`** (incl. Frontend PHP shells that render DOM / enqueue / localize) |
| Worker-2 (frontend) | `assets/**`, `package.json`, `vite.config.js` |

PHP prints the modal/cart shells and localizes `HRH_SAMPLE_CONFIG`. JS mounts into those shells and owns behavior/state.
