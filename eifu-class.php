<?php
/**
 * Plugin Name:     IFU Documents for WooCommerce Products
 * Plugin URI:      n/a
 * Description:     Adds custom post type IFU with its own taxonomy, to attach an IFU doc to each relevant WooCommerce product.
 * Author:          MGB dev
 * Author URI:      YOUR SITE HERE
 * Text Domain:     eifu-class
 * Domain Path:     /languages
 * Version:         0.1.0
 *
 * @package         MGBdev\Eifu_Docs_Class
 */


namespace MGBdev\Eifu_Docs_Class;

// Exit if accessed directly
if ( !defined( 'ABSPATH' )) exit;

define(constant_name: 'EIFUC_GLOBAl_VERSION', value: '1.0.0');
define(constant_name: 'EIFUC_GLOBAl_NAME', value: 'ifuc-global');
define(constant_name: 'EIFUC_GLOBAl_ABSPATH', value: __DIR__);
define(constant_name: 'EIFUC_GLOBAl_BASE_NAME', value: plugin_basename(__FILE__));
define(constant_name: 'EIFUC_G_DIR', value: plugin_dir_path(__FILE__));
define(constant_name: 'EIFUC_G_URL', value: plugin_dir_url(__FILE__));
//define(constant_name: ''eifu-class'', value:'eifu-class'); // GLOBAL TEXT DOMAIN
define(constant_name: 'EIFUC_G_PT', value: 'ifu'); // GLOBAL POST TYPE
define(constant_name: 'EIFUC_G_TX', value: 'ifu-cat'); // GLOBAL POST taxonomy


add_action('before_woocommerce_init', function () {
    // Check if the FeaturesUtil class exists in the \Automattic\WooCommerce\Utilities namespace.
    if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
        // Declare compatibility with custom order tables using the FeaturesUtil class.
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
    }
});

function ifu_class_maybe_load_woocommerce_features() {
    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', function() {
            echo '<div class="notice notice-error"><p>';
            echo __('EIFUC Product Documents requires WooCommerce to be installed and active.', 'eifu-class');
            echo '</p></div>';
        });
        return;
    }
    // Load additional WooCommerce-specific files
    
	require_once (EIFUC_G_DIR . 'inc/posts/register.php');
	new IFU_Post_Register(); // Initialize the class
    // require_once (EIFUC_G_DIR . 'inc/frontend-integration.php');
    require_once (EIFUC_G_DIR . 'inc/admin/settings.php');
	require_once (EIFUC_G_DIR . 'inc/products/documents-manager.php');
	new Product_Documents_Manager(); // Initialize the class
	require_once (EIFUC_G_DIR . 'inc/products/documents-tab.php');
	
}
add_action('plugins_loaded', __NAMESPACE__ . '\\ifu_class_maybe_load_woocommerce_features', 20);

// Force use of plugin templates for IFU Documents
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
