# IFU Documents Template Hierarchy

## Available Templates

### Current Setup
1. `archive-ifudoc.php` - Root archive page for all IFU documents
2. `taxonomy-toplevel.php` - Top-level (parent) category archive pages
3. `taxonomy-child.php` - Child category archive pages
4. `single-ifudoc.php` - Individual document pages

### Template Loading Priority

The `template_include` filter in `eifu-class.php` overrides WordPress's default template hierarchy. It routes taxonomy archives based on term depth:

1. **Taxonomy archive (parent = 0)** → `taxonomy-toplevel.php`
2. **Taxonomy archive (parent > 0)** → `taxonomy-child.php`
3. **Post type archive** → `archive-ifudoc.php`
4. **Single post** → `single-ifudoc.php`

## Example URLs and Templates

### Root Archive
- URL: `/ifu/`
- Template: `archive-ifudoc.php`
- Shows: All categories and documents in hierarchical structure

### Top-Level Category Archives
- URL: `/ifu-cat/medical-devices/`
- Template: `taxonomy-toplevel.php`
- Shows: Direct posts + child terms with their posts

### Child Category Archives
- URL: `/ifu-cat/medical-devices/surgical-tools/`
- Template: `taxonomy-child.php`
- Shows: Direct posts + any sub-child terms with their posts

### Single Documents
- URL: `/ifu/surgical-procedure-guide/`
- Template: `single-ifudoc.php`
- Shows: Individual document with download links (English USA, English CE, translated files)

## Template Filter Logic

The `template_include` filter in `eifu-class.php` handles template selection:

```php
add_filter('template_include', function($template) {
    // Handle taxonomy archives with conditional routing for hierarchy levels
    if (is_tax(EIFUC_G_TX)) {
        $current_term = get_queried_object();

        // Check if this is a top-level term (parent = 0) or child term (parent > 0)
        if ($current_term && $current_term->parent == 0) {
            // Top-level category template
            $plugin_template = EIFUC_G_DIR . 'tmpl/taxonomy-toplevel.php';
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        } else {
            // Child category template
            $plugin_template = EIFUC_G_DIR . 'tmpl/taxonomy-child.php';
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        }
    }
    // Handle post type archive (root archive page)
    elseif (is_post_type_archive(EIFUC_G_PT)) {
        $plugin_template = EIFUC_G_DIR . 'tmpl/archive-ifudoc.php';
        if (file_exists($plugin_template)) {
            return $plugin_template;
        }
    }
    // Handle single posts
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
Add conditional CSS in your taxonomy templates:
```php
$term_slug = $current_term->slug;
echo '<div class="ifu-category-archive category-' . esc_attr($term_slug) . '">';
```

### 2. Custom Archive Headers
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
