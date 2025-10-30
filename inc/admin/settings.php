<?php
/**
 * Admin Settings Page for IFU Documents Plugin
 * 
 * This class implements a comprehensive settings page using WordPress best practices:
 * - Singleton pattern for global variables and instance management
 * - WordPress Settings API for proper option handling
 * - Nonce verification for security
 * - Proper sanitization and validation
 * - Object-oriented approach with separation of concerns
 * 
 * @package MGBdev\Eifu_Docs_Class
 */

namespace MGBdev\Eifu_Docs_Class;

/**
 * Class IFU_Admin_Settings
 * 
 * Handles the admin settings page for the IFU Documents plugin.
 * Uses singleton pattern to ensure only one instance exists.
 * 
 * Language Data Structure:
 * - Input: CODE:Label format (e.g., "ESP:Spanish")
 * - Storage: JSON-encoded 2D array: [{0: {"ESP": "Spanish"}}, {1: {"FRA": "French"}}]
 * - Access: Multiple helper methods for codes, labels, and combined data
 */
class IFU_Admin_Settings {
    
    /**
     * Singleton instance
     * 
     * @var IFU_Admin_Settings|null
     */
    private static $instance = null;
    
    /**
     * Plugin option name prefix
     * 
     * @var string
     */
    private static $option_prefix = 'ifu_docs_';
    
    /**
     * Settings page slug
     * 
     * @var string
     */
    private static $page_slug = 'ifu-docs-settings';
    
    /**
     * Settings group name
     * 
     * @var string
     */
    private static $settings_group = 'ifu_docs_settings_group';
    
    /**
     * Settings section name
     * 
     * @var string
     */
    private static $settings_section = 'ifu_docs_display_section';
    
    /**
     * Plugin settings fields configuration
     * 
     * @var array
     */
    private $settings_fields = [
        'supported_languages' => [
            'title' => 'Supported Non-English Languages',
            'type' => 'textarea',
            'description' => 'Enter one non-English language per line in the format CODE:Label (e.g., ESP:Spanish)',
            'default' => '',
            'placeholder' => "ESP:Spanish\nFRA:French\nDEU:German",
            'sanitize_callback' => 'sanitize_languages_field'
        ]
    ];
    
    /**
     * Private constructor to prevent direct instantiation
     */
    private function __construct() {
        $this->init_hooks();
    }
    
    /**
     * Get singleton instance
     * 
     * @return IFU_Admin_Settings
     */
    public static function get_instance() {
        if ( self::$instance === null ) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Prevent cloning of the instance
     */
    private function __clone() {}
    
    /**
     * Prevent unserialization of the instance
     */
    public function __wakeup() {}
    
    /**
     * Initialize WordPress hooks
     */
    public function init_hooks() {
        add_action( 'admin_menu', [ $this, 'add_admin_menu' ] );
        add_action( 'admin_init', [ $this, 'init_settings' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_scripts' ] );
    }
    
    /**
     * Add admin menu page
     */
    public function add_admin_menu() {
        add_submenu_page(
            'edit.php?post_type=ifu',  // Parent slug (under IFU Items menu)
            __( 'IFU Document Settings', 'eifu-class' ),
            __( 'Settings', 'eifu-class' ),
            'manage_options',
            self::$page_slug,
            [ $this, 'render_settings_page' ]
        );
    }
    
    /**
     * Initialize settings using WordPress Settings API
     */
    public function init_settings() {
        // Register settings group
        register_setting(
            self::$settings_group,
            self::get_option_name( 'all_settings' ),
            [
                'sanitize_callback' => [ $this, 'sanitize_settings' ],
                'default' => $this->get_default_settings()
            ]
        );
        
        // Add settings section
        add_settings_section(
            self::$settings_section,
            __( 'Configure how product documents are displayed and managed.', 'eifu-class' ),
            [ $this, 'render_section_description' ],
            self::$page_slug
        );
        
        // Add individual settings fields
        foreach ( $this->settings_fields as $field_key => $field_config ) {
            add_settings_field(
                $field_key,
                $field_config['title'],
                [ $this, 'render_field' ],
                self::$page_slug,
                self::$settings_section,
                [
                    'field_key' => $field_key,
                    'field_config' => $field_config,
                    'label_for' => $field_key
                ]
            );
        }
    }
    
    /**
     * Enqueue admin scripts and styles
     * 
     * @param string $hook_suffix Current admin page hook suffix
     */
    public function enqueue_admin_scripts( $hook_suffix ) {
        // Only load on our settings page
        if ( strpos( $hook_suffix, self::$page_slug ) === false ) {
            return;
        }
        
        // Add inline CSS for better styling
        $custom_css = '
        .ifu-settings-section {
            background: #fff;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            padding: 20px;
            margin: 20px 0;
        }
        .ifu-settings-field {
            margin-bottom: 20px;
        }
        .ifu-settings-field textarea {
            width: 100%;
            max-width: 600px;
            min-height: 100px;
            font-family: "Consolas", "Monaco", "Lucida Console", monospace;
            line-height: 1.4;
        }
        .ifu-settings-field textarea[id="supported_languages"] {
            min-height: 120px;
            background: #fafafa;
            border: 2px solid #e0e0e0;
        }
        .ifu-settings-field textarea[id="supported_languages"]:focus {
            background: #fff;
            border-color: #2271b1;
            box-shadow: 0 0 0 1px #2271b1;
        }
        .ifu-settings-description {
            color: #666;
            font-style: italic;
            margin-top: 5px;
        }
        .ifu-language-format-help {
            background: #f0f6fc;
            border: 1px solid #c6d9e5;
            border-radius: 4px;
            padding: 8px 12px;
            margin-top: 8px;
            font-size: 12px;
            color: #2c3e50;
        }
        .ifu-language-format-help code {
            background: #e3f2fd;
            padding: 2px 4px;
            border-radius: 2px;
            font-family: "Consolas", "Monaco", monospace;
            color: #1565c0;
        }
        .ifu-settings-notice {
            background: #e7f3ff;
            border-left: 4px solid #2271b1;
            padding: 12px;
            margin: 15px 0;
        }
        ';
        wp_add_inline_style( 'wp-admin', $custom_css );
    }
    
    /**
     * Render the settings page
     */
    public function render_settings_page() {
        // Check user capabilities
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'You do not have sufficient permissions to access this page.', 'eifu-class' ) );
        }
        
        // Handle form submission messages
        $message = '';
        if ( isset( $_GET['settings-updated'] ) && $_GET['settings-updated'] === 'true' ) {
            $message = '<div class="notice notice-success is-dismissible"><p>' . 
                      __( 'Settings saved successfully!', 'eifu-class' ) . 
                      '</p></div>';
        }
        
        ?>
        <div class="wrap">
            <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
            
            <?php echo $message; ?>
            
            <div class="ifu-settings-notice">
                <p><strong><?php esc_html_e( 'About these settings:', 'eifu-class' ); ?></strong></p>
                <p><?php esc_html_e( 'These settings control how your IFU (Instructions For Use) documents are displayed and managed across your website. Changes here will affect all IFU document posts.', 'eifu-class' ); ?></p>
            </div>
            
            <form method="post" action="options.php" class="ifu-settings-form">
                <?php
                // Security fields
                settings_fields( self::$settings_group );
                
                // Render settings sections
                do_settings_sections( self::$page_slug );
                
                // Submit button
                submit_button( __( 'Save Settings', 'eifu-class' ), 'primary', 'submit', true, [
                    'id' => 'ifu-settings-submit'
                ]);
                ?>
            </form>
            
            <div class="ifu-settings-section">
                <h3><?php esc_html_e( 'Current Settings Summary', 'eifu-class' ); ?></h3>
                <?php $this->render_settings_summary(); ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render section description
     */
    public function render_section_description() {
        echo '<p>' . esc_html__( 'Configure the display and management options for your product documents.', 'eifu-class' ) . '</p>';
    }
    
    /**
     * Render individual settings field
     * 
     * @param array $args Field arguments
     */
    public function render_field( $args ) {
        $field_key = $args['field_key'];
        $field_config = $args['field_config'];
        $option_value = $this->get_option( $field_key );
        
        // Generate unique field ID and name
        $field_id = esc_attr( $field_key );
        $field_name = esc_attr( self::get_option_name( 'all_settings' ) . '[' . $field_key . ']' );
        
        echo '<div class="ifu-settings-field">';
        
        switch ( $field_config['type'] ) {
            case 'textarea':
                $placeholder = isset( $field_config['placeholder'] ) ? $field_config['placeholder'] : '';
                
                // Special handling for supported_languages field - convert JSON back to CODE:Label format
                if ( $field_key === 'supported_languages' ) {
                    $display_value = $this->convert_json_to_display_format( $option_value );
                } else {
                    $display_value = $option_value;
                }
                
                printf(
                    '<textarea id="%s" name="%s" rows="6" cols="50" placeholder="%s">%s</textarea>',
                    $field_id,
                    $field_name,
                    esc_attr( $placeholder ),
                    esc_textarea( $display_value )
                );
                break;
                
            case 'text':
                printf(
                    '<input type="text" id="%s" name="%s" value="%s" class="regular-text" />',
                    $field_id,
                    $field_name,
                    esc_attr( $option_value )
                );
                break;
                
            case 'checkbox':
                printf(
                    '<label for="%s"><input type="checkbox" id="%s" name="%s" value="1" %s /> %s</label>',
                    $field_id,
                    $field_id,
                    $field_name,
                    checked( $option_value, '1', false ),
                    esc_html( $field_config['title'] )
                );
                break;
        }
        
        // Add field description if available
        if ( ! empty( $field_config['description'] ) ) {
            printf(
                '<p class="ifu-settings-description">%s</p>',
                esc_html( $field_config['description'] )
            );
        }
        
        // Add special help for supported_languages field
        if ( $field_key === 'supported_languages' ) {
            echo '<div class="ifu-language-format-help">';
            echo '<strong>' . esc_html__( 'Format Guide:', 'eifu-class' ) . '</strong><br>';
            echo esc_html__( 'Each language should be on a separate line in the format: ', 'eifu-class' );
            echo '<code>CODE:Label</code><br>';
            echo '<strong>' . esc_html__( 'Examples:', 'eifu-class' ) . '</strong><br>';
            echo '<code>ESP:Spanish</code><br>';
            echo '<code>FRA:French</code><br>';
            echo '<code>DEU:German</code><br>';
            echo '<code>ITA:Italian</code>';
            echo '</div>';
        }
        
        echo '</div>';
    }
    
    /**
     * Sanitize settings data
     * 
     * @param array $input Raw input data
     * @return array Sanitized data
     */
    public function sanitize_settings( $input ) {
        $sanitized = [];
        
        if ( ! is_array( $input ) ) {
            return $sanitized;
        }
        
        foreach ( $this->settings_fields as $field_key => $field_config ) {
            if ( isset( $input[ $field_key ] ) ) {
                $value = $input[ $field_key ];
                
                // Apply specific sanitization based on field type and custom callback
                if ( isset( $field_config['sanitize_callback'] ) && method_exists( $this, $field_config['sanitize_callback'] ) ) {
                    $sanitized[ $field_key ] = $this->{$field_config['sanitize_callback']}( $value );
                } else {
                    switch ( $field_config['type'] ) {
                        case 'textarea':
                            $sanitized[ $field_key ] = sanitize_textarea_field( $value );
                            break;
                            
                        case 'text':
                            $sanitized[ $field_key ] = sanitize_text_field( $value );
                            break;
                            
                        case 'checkbox':
                            $sanitized[ $field_key ] = $value === '1' ? '1' : '0';
                            break;
                            
                        default:
                            $sanitized[ $field_key ] = sanitize_text_field( $value );
                    }
                }
            } else {
                // Set default value if field not provided
                $sanitized[ $field_key ] = $field_config['default'] ?? '';
            }
        }
        
        return $sanitized;
    }
    
    /**
     * Sanitize languages field with CODE:Label format
     * Converts input to structured 2D array and encodes as JSON
     * 
     * @param string $input Raw textarea input
     * @return string JSON-encoded structured data
     */
    private function sanitize_languages_field( $input ) {
        // Sanitize the raw input first
        $clean_input = sanitize_textarea_field( $input );
        
        if ( empty( $clean_input ) ) {
            return wp_json_encode( [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE );
        }
        
        // Split by newlines and process each line
        $lines = preg_split( '/[\r\n]+/', $clean_input );
        $structured_data = [];
        $index = 0;
        
        foreach ( $lines as $line ) {
            $line = trim( $line );
            
            // Skip empty lines
            if ( empty( $line ) ) {
                continue;
            }
            
            // Parse CODE:Label format
            if ( strpos( $line, ':' ) !== false ) {
                $parts = explode( ':', $line, 2 ); // Limit to 2 parts in case label contains colons
                $code = trim( $parts[0] );
                $label = trim( $parts[1] );
                
                // Validate 3-letter code format (letters and numbers allowed)
                if ( preg_match( '/^[A-Z0-9]{2,4}$/i', $code ) && ! empty( $label ) ) {
                    $structured_data[ $index ] = [
                        strtoupper( sanitize_text_field( $code ) ) => sanitize_text_field( $label )
                    ];
                    $index++;
                }
            }
        }
        
        // Encode as JSON with pretty printing for readability
        return wp_json_encode( $structured_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE );
    }
    
    /**
     * Convert JSON-encoded languages back to CODE:Label display format
     * 
     * @param string $json_value JSON-encoded languages data
     * @return string CODE:Label format for textarea display
     */
    private function convert_json_to_display_format( $json_value ) {
        if ( empty( $json_value ) ) {
            return '';
        }
        
        $decoded_data = json_decode( $json_value, true );
        if ( ! is_array( $decoded_data ) ) {
            return '';
        }
        
        $display_lines = [];
        
        foreach ( $decoded_data as $language_item ) {
            if ( is_array( $language_item ) ) {
                foreach ( $language_item as $code => $label ) {
                    $display_lines[] = sprintf( '%s:%s', $code, $label );
                }
            }
        }
        
        return implode( "\n", $display_lines );
    }
    
    /**
     * Get default settings
     * 
     * @return array Default settings values
     */
    private function get_default_settings() {
        $defaults = [];
        foreach ( $this->settings_fields as $field_key => $field_config ) {
            $defaults[ $field_key ] = $field_config['default'] ?? '';
        }
        return $defaults;
    }
    
    /**
     * Get option name with prefix
     * 
     * @param string $option_key Option key
     * @return string Full option name
     */
    public static function get_option_name( $option_key ) {
        return self::$option_prefix . $option_key;
    }
    
    /**
     * Get option value
     * 
     * @param string $option_key Option key
     * @param mixed $default Default value
     * @return mixed Option value
     */
    public function get_option( $option_key, $default = '' ) {
        $all_settings = get_option( self::get_option_name( 'all_settings' ), [] );
        return $all_settings[ $option_key ] ?? $default;
    }
    
    /**
     * Update option value
     * 
     * @param string $option_key Option key
     * @param mixed $value Option value
     * @return bool True if successful
     */
    public function update_option( $option_key, $value ) {
        $all_settings = get_option( self::get_option_name( 'all_settings' ), [] );
        $all_settings[ $option_key ] = $value;
        return update_option( self::get_option_name( 'all_settings' ), $all_settings );
    }
    
    /**
     * Get all options
     * 
     * @return array All option values
     */
    public function get_all_options() {
        return get_option( self::get_option_name( 'all_settings' ), $this->get_default_settings() );
    }
    
    /**
     * Render settings summary
     */
    private function render_settings_summary() {
        $all_options = $this->get_all_options();
        
        echo '<table class="form-table">';
        foreach ( $this->settings_fields as $field_key => $field_config ) {
            $value = $all_options[ $field_key ] ?? $field_config['default'];
            
            // Special handling for supported_languages field
            if ( $field_key === 'supported_languages' ) {
                $display_value = $this->format_languages_summary( $value );
            } else {
                $display_value = ! empty( $value ) ? esc_html( wp_trim_words( $value, 10 ) ) : '<em>' . esc_html__( 'Not set', 'eifu-class' ) . '</em>';
            }
            
            printf(
                '<tr><th scope="row">%s</th><td>%s</td></tr>',
                esc_html( $field_config['title'] ),
                $display_value
            );
        }
        echo '</table>';
    }
    
    /**
     * Format languages summary for display
     * 
     * @param string $json_value JSON-encoded languages data
     * @return string Formatted summary
     */
    private function format_languages_summary( $json_value ) {
        if ( empty( $json_value ) ) {
            return '<em>' . esc_html__( 'Not set', 'eifu-class' ) . '</em>';
        }
        
        $decoded_data = json_decode( $json_value, true );
        if ( ! is_array( $decoded_data ) || empty( $decoded_data ) ) {
            return '<em>' . esc_html__( 'No valid languages configured', 'eifu-class' ) . '</em>';
        }
        
        $count = count( $decoded_data );
        $formatted_languages = self::get_formatted_supported_languages( 'both', ', ' );
        
        return sprintf(
            '<strong>%d</strong> %s: %s',
            $count,
            _n( 'language', 'languages', $count, 'eifu-class' ),
            esc_html( $formatted_languages )
        );
    }
    
    /**
     * Static method to get supported languages structured data (for use in other classes)
     * 
     * @return array Decoded array of structured language data
     */
    public static function get_supported_languages_data() {
        $instance = self::get_instance();
        $languages_json = $instance->get_option( 'supported_languages', '' );
        
        if ( empty( $languages_json ) ) {
            return [];
        }
        
        $decoded_data = json_decode( $languages_json, true );
        return is_array( $decoded_data ) ? $decoded_data : [];
    }
    
    /**
     * Static method to get supported languages as simple array of codes
     * 
     * @return array Array of language codes
     */
    public static function get_supported_language_codes() {
        $structured_data = self::get_supported_languages_data();
        $codes = [];
        
        foreach ( $structured_data as $language_item ) {
            if ( is_array( $language_item ) ) {
                $codes = array_merge( $codes, array_keys( $language_item ) );
            }
        }
        
        return array_unique( $codes );
    }
    
    /**
     * Static method to get supported languages as simple array of labels
     * 
     * @return array Array of language labels
     */
    public static function get_supported_language_labels() {
        $structured_data = self::get_supported_languages_data();
        $labels = [];
        
        foreach ( $structured_data as $language_item ) {
            if ( is_array( $language_item ) ) {
                $labels = array_merge( $labels, array_values( $language_item ) );
            }
        }
        
        return array_unique( $labels );
    }
    
    /**
     * Static method to get language label by code
     * 
     * @param string $code Language code (e.g., 'ESP')
     * @return string|false Language label or false if not found
     */
    public static function get_language_label_by_code( $code ) {
        $structured_data = self::get_supported_languages_data();
        $code = strtoupper( trim( $code ) );
        
        foreach ( $structured_data as $language_item ) {
            if ( is_array( $language_item ) && isset( $language_item[ $code ] ) ) {
                return $language_item[ $code ];
            }
        }
        
        return false;
    }
    
    /**
     * Static method to check if a language code is supported
     * 
     * @param string $code Language code to check (e.g., 'ESP')
     * @return bool True if supported
     */
    public static function is_language_code_supported( $code ) {
        $supported_codes = self::get_supported_language_codes();
        return in_array( strtoupper( trim( $code ) ), $supported_codes, true );
    }
    
    /**
     * Static method to check if a language label is supported
     * 
     * @param string $label Language label to check (e.g., 'Spanish')
     * @return bool True if supported
     */
    public static function is_language_label_supported( $label ) {
        $supported_labels = self::get_supported_language_labels();
        return in_array( trim( $label ), $supported_labels, true );
    }
    
    /**
     * Helper method to get formatted supported languages for display
     * 
     * @param string $format Display format: 'codes', 'labels', 'both' (default: 'labels')
     * @param string $separator Separator between languages (default: ', ')
     * @return string Formatted languages string
     */
    public static function get_formatted_supported_languages( $format = 'labels', $separator = ', ' ) {
        $structured_data = self::get_supported_languages_data();
        
        if ( empty( $structured_data ) ) {
            return __( 'None specified', 'eifu-class' );
        }
        
        $formatted_items = [];
        
        foreach ( $structured_data as $language_item ) {
            if ( is_array( $language_item ) ) {
                foreach ( $language_item as $code => $label ) {
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
            }
        }
        
        return ! empty( $formatted_items ) ? implode( $separator, $formatted_items ) : __( 'None specified', 'eifu-class' );
    }
    
    /**
     * Helper method to get structured languages as associative array (code => label)
     * 
     * @return array Associative array of code => label pairs
     */
    public static function get_languages_as_options() {
        $structured_data = self::get_supported_languages_data();
        $options = [];
        
        foreach ( $structured_data as $language_item ) {
            if ( is_array( $language_item ) ) {
                $options = array_merge( $options, $language_item );
            }
        }
        
        return $options;
    }
}

// Initialize the settings page (singleton pattern ensures only one instance)
IFU_Admin_Settings::get_instance();

/*
 * Example Usage for Other Classes:
 * 
 * // Get all language data as structured array
 * $languages = IFU_Admin_Settings::get_supported_languages_data();
 * 
 * // Get just the language codes
 * $codes = IFU_Admin_Settings::get_supported_language_codes(); // ['ESP', 'FRA', 'DEU']
 * 
 * // Get just the language labels  
 * $labels = IFU_Admin_Settings::get_supported_language_labels(); // ['Spanish', 'French', 'German']
 * 
 * // Check if a language code is supported
 * if ( IFU_Admin_Settings::is_language_code_supported( 'ESP' ) ) {
 *     // Do something for Spanish
 * }
 * 
 * // Get language name by code
 * $language_name = IFU_Admin_Settings::get_language_label_by_code( 'ESP' ); // 'Spanish'
 * 
 * // Get formatted string for display
 * $display = IFU_Admin_Settings::get_formatted_supported_languages( 'both' ); // 'Spanish (ESP), French (FRA)'
 * 
 * // Get as options array for dropdowns
 * $options = IFU_Admin_Settings::get_languages_as_options(); // ['ESP' => 'Spanish', 'FRA' => 'French']
 */
