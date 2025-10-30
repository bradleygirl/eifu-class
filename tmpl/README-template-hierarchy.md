# IFU Documents Template Hierarchy

## Available Templates

### Current Setup
1. `archive-ifudoc.php` - Root archive page for all IFU documents
2. `taxonomy-ifucat.php` - Category archive pages 
3. `single-ifudoc.php` - Individual document pages

### Additional Template Options

You can create more specific templates for finer control:

#### Category-Specific Templates
- `taxonomy-ifu-cat-medical-devices.php` - Only for "medical-devices" category
- `taxonomy-ifu-cat-{slug}.php` - For any specific category slug

#### Template Loading Priority (WordPress follows this order):
1. `taxonomy-{taxonomy}-{term}.php` - Most specific (e.g., `taxonomy-ifu-cat-medical-devices.php`)
2. `taxonomy-{taxonomy}.php` - For all terms in taxonomy (e.g., `taxonomy-ifu-cat.php`)
3. `taxonomy.php` - For all taxonomies
4. `archive.php` - General archive template
5. `index.php` - Fallback

## Example URLs and Templates

### Root Archive
- URL: `/ifu/`
- Template: `archive-ifudoc.php`
- Shows: All categories and documents in hierarchical structure

### Category Archives
- URL: `/ifu/medical-devices/`
- Template: `taxonomy-ifucat.php` (or `taxonomy-ifu-cat-medical-devices.php` if it exists)
- Shows: Documents in "medical-devices" category + subcategories

### Subcategory Archives  
- URL: `/ifu/medical-devices/surgical-tools/`
- Template: `taxonomy-ifucat.php`
- Shows: Documents in "surgical-tools" subcategory + breadcrumbs

### Single Documents
- URL: `/document/surgical-procedure-guide/`
- Template: `single-ifudoc.php`
- Shows: Individual document with all metadata

## Template Filter Logic

The `template_include` filter in `eifu-class.php` handles template selection:

```php
add_filter('template_include', function($template) {
    // 1. Check for taxonomy archives first (most specific)
    if (is_tax(EIFUC_G_TX)) {
        $plugin_template = EIFUC_G_DIR . 'tmpl/taxonomy-ifucat.php';
        if (file_exists($plugin_template)) {
            return $plugin_template;
        }
    }
    // 2. Check for post type archive (root archive)
    elseif (is_post_type_archive(EIFUC_G_PT)) {
        $plugin_template = EIFUC_G_DIR . 'tmpl/archive-ifudoc.php';
        if (file_exists($plugin_template)) {
            return $plugin_template;
        }
    }
    // 3. Check for single posts
    elseif (is_singular(EIFUC_G_PT)) {
        $plugin_template = EIFUC_G_DIR . 'tmpl/single-ifudoc.php';
        if (file_exists($plugin_template)) {
            return $plugin_template;
        }
    }
    return $template;
});
```

## Customization Tips

### 1. Category-Specific Styling
Add conditional CSS in your taxonomy template:
```php
$term_slug = $current_term->slug;
echo '<div class="ifu-category-archive category-' . esc_attr($term_slug) . '">';
```

### 2. Different Layouts per Category
```php
// In taxonomy-ifucat.php
$term_slug = get_queried_object()->slug;

switch ($term_slug) {
    case 'medical-devices':
        // Show grid layout
        get_template_part('partials/documents-grid');
        break;
    case 'manuals':
        // Show list layout  
        get_template_part('partials/documents-list');
        break;
    default:
        // Default layout
        get_template_part('partials/documents-default');
}
```

### 3. Custom Archive Headers
```php
// Different headers based on category level
$current_term = get_queried_object();
if ($current_term->parent == 0) {
    // Top-level category header
    echo '<h1 class="category-title-parent">' . $current_term->name . '</h1>';
} else {
    // Child category header with parent info
    $parent = get_term($current_term->parent);
    echo '<h1 class="category-title-child">' . $parent->name . ' &gt; ' . $current_term->name . '</h1>';
}
```
