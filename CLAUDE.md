# CLAUDE.md — eifu-class

Layer 2 (per-repo) rules. Read after the super CLAUDE.md at
`app/public/CLAUDE.md` (Layer 1, site-wide).

Strategic context in `.site-meta/docs/`:
- `triage.md` — Plugin 3 dispositions and Per-repo finding index
  for this repo.
- `refactor-strategy.md` Decision 1.3 — `eifu-class` is sole owner
  of the IFU subsystem post-remediation; zero IFU code in the
  child theme post dead-code sweep.
- `phases/04-custom-plugins.md` §3 — full inventory of file
  structure, registrations, hooks, capability checks, dependencies.
- `deployment-playbook.md` §3 (branching), §4 (versioning), §5.2
  (custom plugin release procedure), §6 Class 2 (default risk
  class for this repo), §7.2 (Class 2 testing protocol).

---

## Repo purpose

WordPress plugin owning the IFU (Instructions-for-Use) documents
subsystem. Registers CPT `ifu`, taxonomy `ifu-cat`, per-product
IFU association meta box, frontend product tab, and admin settings
page. ~24 production `ifu` posts are managed through it.

This is regulatory infrastructure — the operator's regulatory
department depends on IFU URL fields being accurate and the
product → IFU associations being persistent. See `README.md`
policy notes for the public-by-design, serialized-LIKE
reverse-lookup, and no-uninstall-handler decisions.

---

## Regulatory subsystem — IFU posture

This plugin is **sole owner** of the IFU subsystem
(refactor-strategy.md Decision 1.3). Zero IFU code lives in the
child theme post dead-code sweep (Dependency 3, Session 3).

**IFU PDFs are public by design** per the operator's regulatory
"available on demand" interpretation — no authentication, no rate
limiting, enumerable URLs are all acceptable. Documented in
`README.md` § "Public-by-design IFU access" and is a settled fact
in super CLAUDE.md (operational history #6). Any session that
proposes adding access control on IFU URLs has misread the policy
— surface back, don't implement.

The retired `bb_document` CPT and `eifu-cat` taxonomy in the child
theme are dead remnants of a prior IFU mechanism and are **not
this plugin's concern**. Their cleanup is Session 3 (dead-code
sweep). Do not touch theme-side IFU code in this repo's sessions.

---

## Session scope

Edits stay inside `wp-content/plugins/eifu-class/`.

A finding pointing outside this directory — even at related code
in the child theme (e.g., dead `bb_document` template references)
— gets surfaced as a written note and ends the session. Cross-repo
work is a sequence of single-repo sessions per super CLAUDE.md
hard rule #7.

`.site-meta/` reads (strategic docs) are expected. `.site-meta/`
writes are not in scope for this repo's sessions.

---

## Git workflow + release procedure

Branching, versioning, release mechanics: playbook §3, §4, §5.2.

**Three version surfaces in this repo must move together at every
release:**
- `eifu-class.php` — `Version:` header.
- `eifu-class.php` — `EIFUC_GLOBAL_VERSION` constant in the
  bootstrap. Currently unreferenced at runtime; keep in sync to
  avoid future confusion if it ever gets wired up.
- `CHANGELOG.md` — entry block (rename `[Unreleased]` to
  `[<x.y.z>] - <YYYY-MM-DD>` and start a fresh `[Unreleased]`
  above).

Plus the canonical git tag (`v<x.y.z>`) per playbook §4.

Default branch: commit to `main` directly for Class 1 / Class 2
work (this repo's typical class). Feature branches only for
Class 3+ — see "Risk class" below.

---

## Risk class for changes in this repo

**Default: Class 2 (Low)** per playbook §6.

Class up to 3 (Medium) when the change touches:
- CPT or taxonomy registration arguments
  (`IFU_Post_Register::register_post_type` /
  `IFU_Post_Register::register_taxonomy`) — affects URL routing,
  search indexing, admin UI for the whole subsystem.
- Template routing in the `template_include` filter (in
  `eifu-class.php`) — affects every IFU URL load.
- Capability or nonce checks in any `save_*` callback — affects
  who can edit IFU records.

Class up to 4 (High) only if a change affects the URL pattern of
`ifu` posts or `ifu-cat` terms — that interacts with cached
external links and search-engine index records. Coordinate with
the operator's regulatory department before shipping any URL-shape
change.

---

## Known quirks

1. **Product → IFU reverse lookup uses `LIKE '%serialized%'`.**
   `Product_Documents_Manager::get_document_products` and
   `get_relationship_stats` query `wp_postmeta` for products
   referencing a given IFU document ID using
   `LIKE '%' . esc_like( serialize( (string) $id ) ) . '%'`.
   Functional today; fragile if PHP's serialization format ever
   changes. Documented in `README.md` § "Product → IFU reverse
   lookup constraint". Do not "fix" by switching to a normalized
   table in this repo's sessions — that's a Class 4 architectural
   change requiring its own session.

2. **No uninstall handler.** `_eifu_*` post meta and
   `_product_documents` / `_product_documents_json` meta survive
   plugin deletion. Intentional for regulatory record persistence.
   Documented in `README.md` § "No uninstall handler". Do not add
   an `uninstall.php` in this repo's sessions without explicit
   operator + regulatory approval.

3. **Repository previously contained a nested duplicate plugin
   tree** at `eifu-class/eifu-class/` — swept in 1.0.0. If you see
   signs of it returning (a `wp-content/plugins/eifu-class/eifu-class/`
   directory reappearing during ZIP install or sync), that's a
   regression of the 1.0.0 cleanup and should be removed
   immediately. WordPress only loads the top-level tree; the
   nested duplicate was dead weight that misled tree navigation.

---

## Test approach

No automated tests. Manual verification per playbook §7.2 (Class 2
protocol). IFU surfaces to exercise on Local → staging → prod:

**Admin:**
1. IFU CPT list (`/wp-admin/edit.php?post_type=ifu`) — list
   renders; edit one IFU post → URL fields, language checklist,
   JSON summary meta box render correctly; save with no changes →
   reload → no data loss.
2. IFU taxonomy (`/wp-admin/edit-tags.php?taxonomy=ifu-cat&post_type=ifu`)
   — term hierarchy renders; "hide category" checkbox works.
3. Product → IFU association meta box (edit a product known to
   have IFU associations) — current associations render; search /
   add UI works.
4. Settings page (`/wp-admin/edit.php?post_type=ifu&page=ifu-docs-settings`)
   — renders without PHP errors; supported-languages textarea
   retains current JSON content.

**Frontend:**
5. IFU archive (`/ifu/`) — IFU posts list renders.
6. Single IFU — single template renders with URL fields.
7. Taxonomy archive (top-level term URL under `/ifu/`) —
   top-level template renders.
8. Taxonomy archive (child term URL under `/ifu/`) — child
   template renders.
9. Product page IFU tab (product known to have IFU associations)
   — "IFU Documents" tab present, associated documents render
   with URL fields.
10. Product page without IFU — IFU Documents tab is absent (no
    empty tab).

Historically regression-prone:
- Surfaces 5–8 (CPT and taxonomy template routing) — depends on
  the `template_include` filter resolving to the right template.
  Any change there needs all four template paths verified.
- Surface 9 (product → IFU reverse lookup) — the serialized-LIKE
  query. Use a product known to have an associated IFU.
