# IFU Documents for WooCommerce Products

Custom post type, taxonomy, and product-association meta box for
managing Instructions-for-Use (IFU) documents on the BNB Surgical
WooCommerce site. Provides:

- CPT `ifu` for IFU documents with English-USA, English-CE, and
  base-translation URL fields, a supported-languages checklist,
  and a JSON summary meta box.
- Hierarchical taxonomy `ifu-cat` for IFU categorization, with
  per-term `hide_cat` toggle.
- Per-product meta box associating one or more IFU documents to
  each WooCommerce product.
- Frontend product tab "IFU Documents" surfacing associated IFU
  documents on product pages that have any.
- Templates for CPT archive, single, and taxonomy hierarchy
  (top-level vs. child term) under `tmpl/` — see
  `tmpl/README-template-hierarchy.md` for routing details.

This is the live IFU subsystem; ~24 production `ifu` posts are
managed through it.

---

## Policy notes

### Public-by-design IFU access

IFU PDFs are intentionally globally accessible. The regulatory
"available on demand" interpretation applies: no authentication,
no rate limiting, and enumerable URLs are all acceptable. This
is a deliberate policy decision, not an oversight. Any future
change to access control on IFU URLs would require regulatory
review.

### Product → IFU reverse lookup constraint

The product → IFU reverse lookup in
`Product_Documents_Manager::get_document_products` and
`get_relationship_stats` queries `wp_postmeta` with
`LIKE '%' . $wpdb->esc_like( serialize( (string) $document_id ) ) . '%'`.

This is fragile: it relies on the exact PHP `serialize()` format
of a stringified document ID. WordPress core has not changed
PHP's serialization format in 15+ years and it is not a public
contract; the technique is functional today.

If the lookup ever stops returning matches (after a WP major
upgrade or an internal storage shape change), the replacement is
a proper relationship table or a normalized index meta key.
Documented as a known constraint, not a defect.

### No uninstall handler

Uninstalling this plugin does **not** remove `_eifu_*` post meta,
the `ifu_docs_all_settings` option, the `hide_cat` term meta on
`ifu-cat` terms, or `_product_documents` / `_product_documents_json`
meta on WooCommerce products. This is intentional — IFU
associations and the URL fields they reference are regulatory
records and should survive plugin churn.

To fully remove all plugin-owned data, manually delete via WP-CLI
or direct database query:

- `wp_options` row where `option_name = 'ifu_docs_all_settings'`.
- `wp_postmeta` rows where `meta_key LIKE '_eifu_%'` (on `ifu`
  posts).
- `wp_postmeta` rows where `meta_key IN ( '_product_documents',
  '_product_documents_json' )` (on WooCommerce `product` posts).
- `wp_termmeta` rows where `meta_key = 'hide_cat'` (on `ifu-cat`
  terms).

---

## Version

**1.0.0** — first formally released version. See `CHANGELOG.md`
for release history.

## License

GPL — see `LICENSE`.
