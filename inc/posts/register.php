<?php

/**
 * Example implementation for custom post type, taxonomy, and meta boxes
 * This demonstrates WordPress best practices for custom content types
 * 
 * EFFICIENCY OPTIMIZATIONS INCLUDED:
 * - Meta box rendering uses PHP templating instead of string concatenation
 * - Single get_post_meta() call for all fields to reduce database queries
 * - Batch processing of meta updates/deletes in save function
 * - Static caching system for repeated meta field access
 * - Optimized frontend display methods with sprintf/printf
 * - Enhanced JSON meta box with copy functionality and data summary
 * - Proper escaping and sanitization throughout
 * - Separated validation logic into dedicated methods
 * - Cache clearing after save operations for data consistency
 * 
 * @package MGBdev\Eifu_Docs_Class
 */

namespace MGBdev\Eifu_Docs_Class;


/**
 * Example implementation for custom post type, taxonomy, and meta boxes
 * This demonstrates WordPress best practices for custom content types
 * 
 * EFFICIENCY OPTIMIZATIONS INCLUDED:
 * - Meta box rendering uses PHP templating instead of string concatenation
 * - Single get_post_meta() call for all fields to reduce database queries
 * - Batch processing of meta updates/deletes in save function
 * - Static caching system for repeated meta field access
 * - Optimized frontend display methods with sprintf/printf
 * - Enhanced JSON meta box with copy functionality and data summary
 * - Proper escaping and sanitization throughout
 * - Separated validation logic into dedicated methods
 * - Cache clearing after save operations for data consistency
 * 
 * @package MGBdev\Eifu_Docs_Class
 */

/**
 * Class Eifu_Custom_Content
 * 
 * Handles registration of custom post type EIFUC_G_PT, custom taxonomy EIFUC_G_TX,
 * and related meta boxes with URL fields, checkboxes, and JSON storage.
 */
class IFU_Post_Register {
    
    /**
     * Meta field keys for the custom fields
     */
    const META_FIELD_PREFIX = '_eifu_';
	const URL_FIELDS = [
        'eng_usa_url' => 'English USA URL',
        'eng_ce_url' => 'English CE URL', 
        'base_translation_url' => 'Base Translation URL',
        'documentation_url' => 'Documentation URL'
    ];
    const CHECKBOX_FIELDS = [
        'usa_default' => 'Use English USA URL as default English file',
        'ce_and_usa' => 'Use both USA and CE URLs and files',
        'use_translations' => 'Use / enable the checked translations from below'
    ];
    const JSON_META_KEY = '_eifu_json_data';
    const SUPPORTED_LANGUAGES_META_KEY = '_eifu_supported_languages';
    const TERM_HIDE_CAT_META_KEY = 'hide_cat';
    
    /**
     * Initialize the class by hooking into WordPress
     */
    public function __construct() {
        add_action( 'init', [ $this, 'register_post_type' ] );
        add_action( 'init', [ $this, 'register_taxonomy' ] );
        add_action( 'add_meta_boxes', [ $this, 'add_meta_boxes' ] );
        add_action( 'save_post', [ $this, 'save_meta_fields' ] );
        
        // Add taxonomy term meta field hooks
        add_action( EIFUC_G_TX . '_add_form_fields', [ $this, 'add_term_meta_fields' ] );
        add_action( EIFUC_G_TX . '_edit_form_fields', [ $this, 'edit_term_meta_fields' ] );
        add_action( 'created_' . EIFUC_G_TX, [ $this, 'save_term_meta_fields' ] );
        add_action( 'edited_' . EIFUC_G_TX, [ $this, 'save_term_meta_fields' ] );
    }
    
    /**
     * Register the custom post type EIFUC_G_PT
     */
    public function register_post_type() {
        $labels = [
            'name'                  => _x( 'IFU Documents', 'Post type general name', 'eifu-class'),
            'singular_name'         => _x( 'IFU Document', 'Post type singular name', 'eifu-class'),
            'menu_name'             => _x( 'IFU Documents', 'Admin Menu text', 'eifu-class'),
            'name_admin_bar'        => _x( 'IFU Document', 'Add New on Toolbar', 'eifu-class'),
            'add_new'               => __( 'Add New', 'eifu-class'),
            'add_new_item'          => __( 'Add New IFU Document', 'eifu-class'),
            'new_item'              => __( 'New IFU Document', 'eifu-class'),
            'edit_item'             => __( 'Edit IFU Document', 'eifu-class'),
            'view_item'             => __( 'View IFU Document', 'eifu-class'),
            'all_items'             => __( 'All IFU Documents', 'eifu-class'),
            'search_items'          => __( 'Search IFU Documents', 'eifu-class'),
            'parent_item_colon'     => __( 'Parent IFU Documents:', 'eifu-class'),
            'not_found'             => __( 'No IFU Documents found.', 'eifu-class'),
            'not_found_in_trash'    => __( 'No IFU Documents found in Trash.', 'eifu-class'),
            'featured_image'        => _x( 'IFU Document Cover Image', 'Overrides the "Featured Image" phrase', 'eifu-class'),
            'set_featured_image'    => _x( 'Set cover image', 'Overrides the "Set featured image" phrase', 'eifu-class'),
            'remove_featured_image' => _x( 'Remove cover image', 'Overrides the "Remove featured image" phrase', 'eifu-class'),
            'use_featured_image'    => _x( 'Use as cover image', 'Overrides the "Use as featured image" phrase', 'eifu-class'),
            'archives'              => _x( 'IFU Document archives', 'The post type archive label used in nav menus', 'eifu-class'),
            'insert_into_item'      => _x( 'Insert into IFU Document', 'Overrides the "Insert into post" phrase', 'eifu-class'),
            'uploaded_to_this_item' => _x( 'Uploaded to this IFU Document', 'Overrides the "Uploaded to this post" phrase', 'eifu-class'),
            'filter_items_list'     => _x( 'Filter IFU Documents list', 'Screen reader text for the filter links', 'eifu-class'),
            'items_list_navigation' => _x( 'IFU Documents list navigation', 'Screen reader text for the pagination', 'eifu-class'),
            'items_list'            => _x( 'IFU Documents list', 'Screen reader text for the items list', 'eifu-class'),
        ];

        $args = [
            'labels'             => $labels,
            'public'             => true,
            'publicly_queryable' => true,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'show_in_rest'       => true, // Enable Gutenberg editor
            'query_var'          => true,
            'has_archive'        => 'ifu',
            'rewrite'            => array( 'slug' => 'document'),
            'capability_type'    => 'post',
            'hierarchical'       => false,
            'menu_position'      => null,
            'menu_icon'          => 'dashicons-admin-tools',
            'supports'           => [ 'title', 'editor', 'excerpt', ],
            'taxonomies'         => [ EIFUC_G_TX ],
        ];

        register_post_type( EIFUC_G_PT, $args );
    }
    
    /**
     * Register the custom taxonomy EIFUC_G_TX
     */
    public function register_taxonomy() {
        $labels = [
           'name'                       => _x( 'IFU Categories', 'Taxonomy General Name', 'eifu-class'),
            'singular_name'              => _x( 'IFU Category', 'Taxonomy Singular Name', 'eifu-class'),
            'menu_name'                  => __( 'IFU Categories', 'eifu-class'),
            'all_items'                  => __( 'All Categories', 'eifu-class'),
            'parent_item'                => __( 'Parent Category', 'eifu-class'),
            'parent_item_colon'          => __( 'Parent Category:', 'eifu-class'),
            'new_item_name'              => __( 'New Category Name', 'eifu-class'),
            'add_new_item'               => __( 'Add New Category', 'eifu-class'),
            'edit_item'                  => __( 'Edit Category', 'eifu-class'),
            'update_item'                => __( 'Update Category', 'eifu-class'),
            'view_item'                  => __( 'View Category', 'eifu-class'),
            'separate_items_with_commas' => __( 'Separate categories with commas', 'eifu-class'),
            'add_or_remove_items'        => __( 'Add or remove categories', 'eifu-class'),
            'choose_from_most_used'      => __( 'Choose from the most used', 'eifu-class'),
            'popular_items'              => __( 'Popular Categories', 'eifu-class'),
            'search_items'               => __( 'Search Categories', 'eifu-class'),
            'not_found'                  => __( 'Not Found', 'eifu-class'),
            'no_terms'                   => __( 'No categories', 'eifu-class'),
            'items_list'                 => __( 'Categories list', 'eifu-class'),
            'items_list_navigation'      => __( 'Categories list navigation', 'eifu-class'),
        ];

        $args = [
            'labels'                     => $labels,
            'hierarchical'               => true,
            'public'                     => true,
            'show_ui'                    => true,
            'show_admin_column'          => true,
            'show_in_nav_menus'          => true,
            'show_tagcloud'              => true,
            'show_in_rest'               => true, // Enable Gutenberg editor
            'rewrite'                    => [ 'slug' => 'ifu' ],
        ];

        register_taxonomy( EIFUC_G_TX, [ EIFUC_G_PT ], $args );
    }
    
    /**
     * Add meta boxes to the EIFUC_G_PT post type
     */
    public function add_meta_boxes() {
        // Main meta box for URL fields and checkboxes
        add_meta_box(
            'eifu_fields_meta_box',
            __( 'IFU Document Settings', 'eifu-class'),
            [ $this, 'render_fields_meta_box' ],
            EIFUC_G_PT,
            'normal',
            'high'
        );
        
        // JSON data meta box
        add_meta_box(
            'eifu_json_meta_box',
            __( 'JSON Data Storage', 'eifu-class'),
            [ $this, 'render_json_meta_box' ],
            EIFUC_G_PT,
            'normal',
            'default'
        );
        
        // Supported languages meta box
        add_meta_box(
            'eifu_languages_meta_box',
            __( 'Supported Languages', 'eifu-class' ),
            [ $this, 'render_languages_meta_box' ],
            EIFUC_G_PT,
            'normal',
            'default'
        );
    }
    
    /**
     * Render the main meta box with URL fields and checkboxes
     * 
     * @param WP_Post $post The post object
     */
    public function render_fields_meta_box( $post ) {
        // Add nonce for security
        wp_nonce_field( 'eifu_save_meta_fields', 'eifu_meta_nonce' );
        
        // Get all meta values at once for efficiency
        $meta_values = get_post_meta( $post->ID );
        ?>
        
        <table class="form-table">
            <tr><td colspan="2"><h3><?php esc_html_e( 'URL Fields', 'eifu-class'); ?></h3></td></tr>
            
            <?php foreach ( self::URL_FIELDS as $field_key => $field_label ) : ?>
                <?php 
                $meta_key = self::META_FIELD_PREFIX . $field_key;
                $value = isset( $meta_values[ $meta_key ][0] ) ? $meta_values[ $meta_key ][0] : '';
                ?>
                <tr>
                    <th scope="row">
                        <label for="<?php echo esc_attr( $meta_key ); ?>"><?php echo esc_html( $field_label ); ?></label>
                    </th>
                    <td>
                        <input type="url" 
                               id="<?php echo esc_attr( $meta_key ); ?>" 
                               name="<?php echo esc_attr( $meta_key ); ?>" 
                               value="<?php echo esc_attr( $value ); ?>" 
                               class="regular-text" />
                    </td>
                </tr>
            <?php endforeach; ?>
            
            <tr><td colspan="2"><h3><?php esc_html_e( 'Options', 'eifu-class'); ?></h3></td></tr>
            
            <?php foreach ( self::CHECKBOX_FIELDS as $field_key => $field_label ) : ?>
                <?php 
                $meta_key = self::META_FIELD_PREFIX . $field_key;
                $value = isset( $meta_values[ $meta_key ][0] ) ? $meta_values[ $meta_key ][0] : '';
                ?>
                <tr>
                    <th scope="row"><?php echo esc_html( $field_label ); ?></th>
                    <td>
                        <label for="<?php echo esc_attr( $meta_key ); ?>">
                            <input type="checkbox" 
                                   id="<?php echo esc_attr( $meta_key ); ?>" 
                                   name="<?php echo esc_attr( $meta_key ); ?>" 
                                   value="1" 
                                   <?php checked( $value, '1' ); ?> />
                            <?php echo esc_html( $field_label ); ?>
                        </label>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
        
        <?php
    }
    
    /**
     * Render the JSON data meta box
     * 
     * @param WP_Post $post The post object
     */
    public function render_json_meta_box( $post ) {
        $json_data = get_post_meta( $post->ID, self::JSON_META_KEY, true );
        ?>
        
        <div class="eifu-json-meta-box">
            <p><strong><?php esc_html_e( 'JSON Encoded Metadata', 'eifu-class'); ?></strong></p>
            <p><em><?php esc_html_e( 'This field automatically stores all the above metadata as JSON when the post is saved.', 'eifu-class'); ?></em></p>
            
            <?php if ( $json_data ) : ?>
                <textarea readonly rows="10" cols="50" style="width: 100%; font-family: monospace; font-size: 12px;"><?php echo esc_textarea( $json_data ); ?></textarea>
                
                <p>
                    <button type="button" class="button button-secondary" onclick="this.previousElementSibling.select(); document.execCommand('copy'); this.innerText='Copied!'; setTimeout(() => this.innerText='Copy JSON', 2000);">
                        <?php esc_html_e( 'Copy JSON', 'eifu-class'); ?>
                    </button>
                </p>
                
                <?php 
                $decoded_data = json_decode( $json_data, true );
                if ( $decoded_data && is_array( $decoded_data ) ) :
                ?>
                    <details style="margin-top: 15px;">
                        <summary style="cursor: pointer; font-weight: bold;"><?php esc_html_e( 'Data Summary', 'eifu-class'); ?></summary>
                        <ul style="margin: 10px 0; padding-left: 20px;">
                            <li><?php printf( esc_html__( 'Total fields: %d', 'eifu-class'), count( $decoded_data ) ); ?></li>
                            <li><?php printf( esc_html__( 'Last updated: %s', 'eifu-class'), isset( $decoded_data['last_updated'] ) ? esc_html( $decoded_data['last_updated'] ) : esc_html__( 'Unknown', 'eifu-class') ); ?></li>
                            <?php if ( isset( $decoded_data['categories'] ) && is_array( $decoded_data['categories'] ) && ! empty( $decoded_data['categories'] ) ) : ?>
                                <li><?php printf( esc_html__( 'Categories: %s', 'eifu-class'), esc_html( implode( ', ', $decoded_data['categories'] ) ) ); ?></li>
                            <?php endif; ?>
                        </ul>
                    </details>
                <?php endif; ?>
                
            <?php else : ?>
                <p><?php esc_html_e( 'No JSON data saved yet. Save the post to generate JSON data.', 'eifu-class'); ?></p>
            <?php endif; ?>
        </div>
        
        <?php
    }
    
    /**
     * Render the supported languages meta box with checkboxes
     * 
     * @param WP_Post $post The post object
     */
    public function render_languages_meta_box( $post ) {
        // Get supported languages from plugin settings
        $supported_languages = $this->get_plugin_supported_languages();
        
        if ( empty( $supported_languages ) ) {
            ?>
            <p><?php esc_html_e( 'No supported languages configured in plugin settings.', 'eifu-class' ); ?></p>
            <p><em><?php printf( 
                esc_html__( 'Please configure languages in %s to enable language selection.', 'eifu-class' ),
                '<a href="' . esc_url( admin_url( 'edit.php?post_type=' . EIFUC_G_PT . '&page=ifu-docs-settings' ) ) . '">' . esc_html__( 'Plugin Settings', 'eifu-class' ) . '</a>'
            ); ?></em></p>
            <?php
            return;
        }
        
        // Get currently selected languages for this post
        $selected_languages = $this->get_post_selected_languages( $post->ID );
        
        ?>
        <div class="eifu-languages-meta-box">
            <p><strong><?php esc_html_e( 'Select supported languages for this document:', 'eifu-class' ); ?></strong></p>
            
            <div class="eifu-language-checkboxes" style="max-height: 200px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; margin: 10px 0;">
                <?php foreach ( $supported_languages as $index => $language_data ) : ?>
                    <?php foreach ( $language_data as $code => $label ) : ?>
                        <?php
                        $checkbox_id = 'eifu_language_' . $index;
                        $checkbox_name = 'eifu_supported_languages[]';
                        $is_checked = isset( $selected_languages[ $code ] );
                        ?>
                        <label for="<?php echo esc_attr( $checkbox_id ); ?>" style="display: block; margin-bottom: 8px;">
                            <input type="checkbox" 
                                   id="<?php echo esc_attr( $checkbox_id ); ?>" 
                                   name="<?php echo esc_attr( $checkbox_name ); ?>" 
                                   value="<?php echo esc_attr( $code ); ?>" 
                                   <?php checked( $is_checked ); ?> />
                            <strong><?php echo esc_html( $code ); ?></strong> - <?php echo esc_html( $label ); ?>
                        </label>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </div>
            
            <p class="description">
                <?php esc_html_e( 'Selected languages will be saved as metadata for this document and can be used for filtering and display purposes.', 'eifu-class' ); ?>
            </p>
        </div>
        <?php
    }
    
    /**
     * Get supported languages from plugin settings
     * 
     * @return array Array of supported languages data
     */
    private function get_plugin_supported_languages() {
        // Check if the settings class exists and is available
        if ( ! class_exists( 'MGBdev\\Eifu_Docs_Class\\IFU_Admin_Settings' ) ) {
            return [];
        }
        
        return IFU_Admin_Settings::get_supported_languages_data();
    }
    
    /**
     * Get selected languages for a specific post
     * 
     * @param int $post_id The post ID
     * @return array Array of selected languages (code => label)
     */
    private function get_post_selected_languages( $post_id ) {
        $languages_json = get_post_meta( $post_id, self::SUPPORTED_LANGUAGES_META_KEY, true );
        
        if ( empty( $languages_json ) ) {
            return [];
        }
        
        $decoded_languages = json_decode( $languages_json, true );
        return is_array( $decoded_languages ) ? $decoded_languages : [];
    }
    
    /**
     * Save meta fields when post is saved
     * 
     * @param int $post_id The post ID
     */
    public function save_meta_fields( $post_id ) {
        // Performance check: early returns for invalid saves
        if ( $this->should_skip_save( $post_id ) ) {
            return;
        }
        
        $meta_data = [];
        $meta_updates = [];
        $meta_deletes = [];
        
        // Process URL fields efficiently
        foreach ( self::URL_FIELDS as $field_key => $field_label ) {
            $meta_key = self::META_FIELD_PREFIX . $field_key;
            $value = isset( $_POST[ $meta_key ] ) ? esc_url_raw( trim( $_POST[ $meta_key ] ) ) : '';
            
            if ( ! empty( $value ) ) {
                $meta_updates[ $meta_key ] = $value;
                $meta_data[ $field_key ] = $value;
            } else {
                $meta_deletes[] = $meta_key;
            }
        }
        
        // Process checkbox fields efficiently
        foreach ( self::CHECKBOX_FIELDS as $field_key => $field_label ) {
            $meta_key = self::META_FIELD_PREFIX . $field_key;
            $is_checked = isset( $_POST[ $meta_key ] ) && $_POST[ $meta_key ] === '1';
            
            if ( $is_checked ) {
                $meta_updates[ $meta_key ] = '1';
                $meta_data[ $field_key ] = true;
            } else {
                $meta_deletes[] = $meta_key;
                $meta_data[ $field_key ] = false;
            }
        }
        
        // Process supported languages efficiently
        $this->process_supported_languages( $post_id, $meta_data );
        
        // Batch update/delete meta fields for better performance
        foreach ( $meta_updates as $key => $value ) {
            update_post_meta( $post_id, $key, $value );
        }
        
        foreach ( $meta_deletes as $key ) {
            delete_post_meta( $post_id, $key );
        }
        
        // Build comprehensive JSON data
        $meta_data = array_merge( $meta_data, [
            'post_id' => $post_id,
            'post_title' => get_the_title( $post_id ),
            'post_status' => get_post_status( $post_id ),
            'last_updated' => current_time( 'mysql' ),
            'categories' => $this->get_post_categories( $post_id ),
        ] );
        
        // Encode and save JSON data
        $json_data = wp_json_encode( $meta_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE );
        update_post_meta( $post_id, self::JSON_META_KEY, $json_data );
        
        // Clear cache after save to ensure fresh data on next load
        self::clear_meta_cache( $post_id );
    }
    
    /**
     * Determine if the save operation should be skipped
     * 
     * @param int $post_id The post ID
     * @return bool True if save should be skipped
     */
    private function should_skip_save( $post_id ) {
        // Check if this is an autosave
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return true;
        }
        
        // Check if this is the correct post type
        if ( get_post_type( $post_id ) !== EIFUC_G_PT ) {
            return true;
        }
        
        // Check the nonce
        if ( ! isset( $_POST['eifu_meta_nonce'] ) || ! wp_verify_nonce( $_POST['eifu_meta_nonce'], 'eifu_save_meta_fields' ) ) {
            return true;
        }
        
        // Check user permissions
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Get post categories efficiently
     * 
     * @param int $post_id The post ID
     * @return array Array of category names
     */
    private function get_post_categories( $post_id ) {
        $terms = get_the_terms( $post_id, EIFUC_G_TX );
        return ( $terms && ! is_wp_error( $terms ) ) ? wp_list_pluck( $terms, 'name' ) : [];
    }
    
    /**
     * Process supported languages from form submission
     * 
     * @param int $post_id The post ID
     * @param array &$meta_data Reference to meta data array for JSON storage
     */
    private function process_supported_languages( $post_id, &$meta_data ) {
        $submitted_languages = isset( $_POST['eifu_supported_languages'] ) && is_array( $_POST['eifu_supported_languages'] ) 
            ? $_POST['eifu_supported_languages'] 
            : [];
        
        // Sanitize submitted language codes
        $submitted_languages = array_map( 'sanitize_text_field', $submitted_languages );
        $submitted_languages = array_filter( $submitted_languages, 'strlen' ); // Remove empty values
        
        if ( empty( $submitted_languages ) ) {
            // No languages selected - remove the meta field
            delete_post_meta( $post_id, self::SUPPORTED_LANGUAGES_META_KEY );
            $meta_data['supported_languages'] = [];
            return;
        }
        
        // Get available languages from plugin settings to validate against
        $available_languages = $this->get_plugin_supported_languages();
        $valid_languages = [];
        
        // Build valid languages array - only include codes that exist in settings
        foreach ( $available_languages as $language_data ) {
            foreach ( $language_data as $code => $label ) {
                if ( in_array( $code, $submitted_languages, true ) ) {
                    $valid_languages[ $code ] = $label;
                }
            }
        }
        
        if ( ! empty( $valid_languages ) ) {
            // Encode valid languages as JSON and save
            $languages_json = wp_json_encode( $valid_languages, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE );
            update_post_meta( $post_id, self::SUPPORTED_LANGUAGES_META_KEY, $languages_json );
            $meta_data['supported_languages'] = $valid_languages;
        } else {
            // No valid languages found - remove the meta field
            delete_post_meta( $post_id, self::SUPPORTED_LANGUAGES_META_KEY );
            $meta_data['supported_languages'] = [];
        }
    }
    
    /**
     * Static cache for meta values to avoid duplicate database queries
     */
    private static $meta_cache = [];
    
    /**
     * Get meta field value with caching
     * 
     * @param int    $post_id The post ID
     * @param string $field_key The field key (without prefix)
     * @param mixed  $default Default value if not found
     * @return mixed The meta value
     */
    public static function get_meta_field( $post_id, $field_key, $default = '' ) {
        // Check cache first
        $cache_key = $post_id . '_' . $field_key;
        if ( isset( self::$meta_cache[ $cache_key ] ) ) {
            return self::$meta_cache[ $cache_key ];
        }
        
        $meta_key = self::META_FIELD_PREFIX . $field_key;
        $value = get_post_meta( $post_id, $meta_key, true ) ?: $default;
        
        // Cache the result
        self::$meta_cache[ $cache_key ] = $value;
        
        return $value;
    }
    
    /**
     * Get all meta fields for a post efficiently
     * 
     * @param int $post_id The post ID
     * @return array Associative array of field_key => value
     */
    public static function get_all_meta_fields( $post_id ) {
        $meta_values = get_post_meta( $post_id );
        $result = [];
        
        foreach ( array_merge( self::URL_FIELDS, self::CHECKBOX_FIELDS ) as $field_key => $field_label ) {
            $meta_key = self::META_FIELD_PREFIX . $field_key;
            $result[ $field_key ] = isset( $meta_values[ $meta_key ][0] ) ? $meta_values[ $meta_key ][0] : '';
        }
        
        return $result;
    }
    
    /**
     * Get JSON data for a post with caching
     * 
     * @param int $post_id The post ID
     * @return array|false The decoded JSON data or false if not found
     */
    public static function get_json_data( $post_id ) {
        $cache_key = $post_id . '_json';
        if ( isset( self::$meta_cache[ $cache_key ] ) ) {
            return self::$meta_cache[ $cache_key ];
        }
        
        $json_data = get_post_meta( $post_id, self::JSON_META_KEY, true );
        $decoded_data = $json_data ? json_decode( $json_data, true ) : false;
        
        // Cache the result
        self::$meta_cache[ $cache_key ] = $decoded_data;
        
        return $decoded_data;
    }
    
    /**
     * Helper method to display URL fields in frontend (optimized)
     * 
     * @param int $post_id The post ID
     */
    public static function display_url_fields( $post_id ) {
        $meta_fields = self::get_all_meta_fields( $post_id );
        $output = [];
        
        foreach ( self::URL_FIELDS as $field_key => $field_label ) {
            $value = $meta_fields[ $field_key ] ?? '';
            if ( ! empty( $value ) ) {
                $output[] = sprintf(
                    '<p><strong>%s:</strong> <a href="%s" target="_blank" rel="noopener">%s</a></p>',
                    esc_html( $field_label ),
                    esc_url( $value ),
                    esc_html( $value )
                );
            }
        }
        
        if ( ! empty( $output ) ) {
            echo '<div class="eifu-url-fields">' . implode( '', $output ) . '</div>';
        }
    }
    
    /**
     * Helper method to display checkbox fields in frontend (optimized)
     * 
     * @param int $post_id The post ID
     */
    public static function display_checkbox_fields( $post_id ) {
        $meta_fields = self::get_all_meta_fields( $post_id );
        $active_options = [];
        
        foreach ( self::CHECKBOX_FIELDS as $field_key => $field_label ) {
            if ( ! empty( $meta_fields[ $field_key ] ) ) {
                $active_options[] = $field_label;
            }
        }
        
        if ( ! empty( $active_options ) ) {
            printf(
                '<div class="eifu-checkbox-fields"><p><strong>%s</strong> %s</p></div>',
                esc_html__( 'Features:', 'eifu-class'),
                esc_html( implode( ', ', $active_options ) )
            );
        }
    }
    
    /**
     * Clear meta cache for a specific post (useful after save operations)
     * 
     * @param int $post_id The post ID
     */
    public static function clear_meta_cache( $post_id ) {
        foreach ( self::$meta_cache as $key => $value ) {
            if ( strpos( $key, $post_id . '_' ) === 0 ) {
                unset( self::$meta_cache[ $key ] );
            }
        }
    }
    
    /**
     * Get supported languages for a specific post
     * 
     * @param int $post_id The post ID
     * @return array Array of supported languages (code => label)
     */
    public static function get_post_supported_languages( $post_id ) {
        $languages_json = get_post_meta( $post_id, self::SUPPORTED_LANGUAGES_META_KEY, true );
        
        if ( empty( $languages_json ) ) {
            return [];
        }
        
        $decoded_languages = json_decode( $languages_json, true );
        return is_array( $decoded_languages ) ? $decoded_languages : [];
    }
    
    /**
     * Check if a post supports a specific language
     * 
     * @param int $post_id The post ID
     * @param string $language_code The language code to check
     * @return bool True if language is supported
     */
    public static function post_supports_language( $post_id, $language_code ) {
        $supported_languages = self::get_post_supported_languages( $post_id );
        return isset( $supported_languages[ strtoupper( trim( $language_code ) ) ] );
    }
    
    /**
     * Get formatted list of supported languages for a post
     * 
     * @param int $post_id The post ID
     * @param string $format Display format: 'codes', 'labels', 'both' (default: 'labels')
     * @param string $separator Separator between languages (default: ', ')
     * @return string Formatted languages string
     */
    public static function get_post_languages_formatted( $post_id, $format = 'labels', $separator = ', ' ) {
        $supported_languages = self::get_post_supported_languages( $post_id );
        
        if ( empty( $supported_languages ) ) {
            return '';
        }
        
        $formatted_items = [];
        
        foreach ( $supported_languages as $code => $label ) {
            switch ( $format ) {
                case 'codes':
                    $formatted_items[] = $code;
                    break;
                case 'both':
                    $formatted_items[] = sprintf( '%s (%s)', $label, $code );
                    break;
                case 'labels':
                default:
                    $formatted_items[] = $label;
                    break;
            }
        }
        
        return implode( $separator, $formatted_items );
    }
    
    /**
     * Display supported languages for a post in frontend
     * 
     * @param int $post_id The post ID
     * @param string $format Display format: 'codes', 'labels', 'both' (default: 'labels')
     */
    public static function display_post_supported_languages( $post_id, $format = 'labels' ) {
        $languages_formatted = self::get_post_languages_formatted( $post_id, $format );
        
        if ( ! empty( $languages_formatted ) ) {
            printf(
                '<div class="eifu-supported-languages"><p><strong>%s:</strong> %s</p></div>',
                esc_html__( 'Available Languages', 'eifu-class' ),
                esc_html( $languages_formatted )
            );
        }
    }
    
    /**
     * Add meta fields to the "Add New Term" form
     * 
     * @param string $taxonomy The taxonomy slug
     */
    public function add_term_meta_fields( $taxonomy ) {
        wp_nonce_field( 'eifu_term_meta_nonce', 'eifu_term_meta_nonce' );
        ?>
        <div class="form-field">
            <label for="<?php echo esc_attr( self::TERM_HIDE_CAT_META_KEY ); ?>">
                <?php esc_html_e( 'Hide Category', 'eifu-class' ); ?>
            </label>
            <label for="<?php echo esc_attr( self::TERM_HIDE_CAT_META_KEY ); ?>">
                <input type="checkbox" 
                       id="<?php echo esc_attr( self::TERM_HIDE_CAT_META_KEY ); ?>" 
                       name="<?php echo esc_attr( self::TERM_HIDE_CAT_META_KEY ); ?>" 
                       value="1" />
                <?php esc_html_e( 'Hide this category from frontend display', 'eifu-class' ); ?>
            </label>
            <p class="description">
                <?php esc_html_e( 'When checked, this category will be hidden from public display areas.', 'eifu-class' ); ?>
            </p>
        </div>
        <?php
    }
    
    /**
     * Add meta fields to the "Edit Term" form
     * 
     * @param WP_Term $term The term object
     */
    public function edit_term_meta_fields( $term ) {
        $hide_cat = get_term_meta( $term->term_id, self::TERM_HIDE_CAT_META_KEY, true );
        wp_nonce_field( 'eifu_term_meta_nonce', 'eifu_term_meta_nonce' );
        ?>
        <tr class="form-field">
            <th scope="row">
                <label for="<?php echo esc_attr( self::TERM_HIDE_CAT_META_KEY ); ?>">
                    <?php esc_html_e( 'Hide Category', 'eifu-class' ); ?>
                </label>
            </th>
            <td>
                <label for="<?php echo esc_attr( self::TERM_HIDE_CAT_META_KEY ); ?>">
                    <input type="checkbox" 
                           id="<?php echo esc_attr( self::TERM_HIDE_CAT_META_KEY ); ?>" 
                           name="<?php echo esc_attr( self::TERM_HIDE_CAT_META_KEY ); ?>" 
                           value="1" 
                           <?php checked( $hide_cat, '1' ); ?> />
                    <?php esc_html_e( 'Hide this category from frontend display', 'eifu-class' ); ?>
                </label>
                <p class="description">
                    <?php esc_html_e( 'When checked, this category will be hidden from public display areas.', 'eifu-class' ); ?>
                </p>
            </td>
        </tr>
        <?php
    }
    
    /**
     * Save term meta fields when term is created or edited
     * 
     * @param int $term_id The term ID
     */
    public function save_term_meta_fields( $term_id ) {
        // Check nonce for security
        if ( ! isset( $_POST['eifu_term_meta_nonce'] ) || 
             ! wp_verify_nonce( $_POST['eifu_term_meta_nonce'], 'eifu_term_meta_nonce' ) ) {
            return;
        }
        
        // Check user permissions
        if ( ! current_user_can( 'manage_categories' ) ) {
            return;
        }
        
        // Process the hide_cat checkbox
        $hide_cat = isset( $_POST[ self::TERM_HIDE_CAT_META_KEY ] ) && $_POST[ self::TERM_HIDE_CAT_META_KEY ] === '1';
        
        if ( $hide_cat ) {
            update_term_meta( $term_id, self::TERM_HIDE_CAT_META_KEY, '1' );
        } else {
            delete_term_meta( $term_id, self::TERM_HIDE_CAT_META_KEY );
        }
    }
    
    /**
     * Check if a term should be hidden
     * 
     * @param int $term_id The term ID
     * @return bool True if term should be hidden
     */
    public static function is_term_hidden( $term_id ) {
        $hide_cat = get_term_meta( $term_id, self::TERM_HIDE_CAT_META_KEY, true );
        return $hide_cat === '1';
    }
    
    /**
     * Get the hide_cat meta value for a term
     * 
     * @param int $term_id The term ID
     * @return bool True if term is set to be hidden
     */
    public static function get_term_hide_cat( $term_id ) {
        return self::is_term_hidden( $term_id );
    }
}