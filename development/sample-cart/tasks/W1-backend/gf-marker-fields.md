# Gravity Forms — required marker-class fields

The plugin does **not** create or modify the client's checkout form. A human adds
these three fields to the existing GF form, then selects that form under
**Sample System → Configuration**. Field binding is by CSS class (Appearance →
Custom CSS Class), not numeric field ID, so rebuilds/reorders are safe.

| CSS class | Field type | Who writes it | Purpose |
|---|---|---|---|
| `hrh-sample-field-selections` | Hidden | Frontend (before submit); cleared by `gform_pre_submission` | Untrusted raw selections `[{slug, options}, …]` |
| `hrh-sample-field-readable` | Hidden or admin-only | `gform_pre_submission` via `Data\Resolver` | Human-readable fulfillment summary (dashboard interface) |
| `hrh-sample-field-json` | Hidden or admin-only | `gform_pre_submission` via `Data\Resolver` | Durable resolved JSON snapshot + entries-list column source |

## Hook order (form-ID gated)
1. `gform_validation` — cart non-empty; every `{slug,options}` still resolves against live tables.
2. `gform_pre_submission` — resolve → write readable + JSON → blank selections.
3. GF creates the entry.

Never trust a posted SKU. The server resolves every line.

## Manual verification (form does not exist in dev yet — §20)
1. Add the three fields with the classes above.
2. Select the form in Configuration; confirm the scan reports all three present.
3. Submit with an empty cart → blocked ("Your cart is empty…").
4. Submit with a valid cart → entry shows readable summary; Samples column shows a short triage string; selections field is blank on the entry.
