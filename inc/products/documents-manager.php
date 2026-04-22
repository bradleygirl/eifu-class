<?php
/**
 * Product Documents Relationship Manager
 * 
 * This class manages the relationship between WooCommerce products and custom IFU documents.
 * It provides a meta box interface for selecting which documents are associated with each product,
 * following WordPress and WooCommerce best practices for plugin development.
 * 
 * FEATURES INCLUDED:
 * - OOP design with proper initialization and hooks
 * - Security validation with nonces and capability checks
 * - Efficient database operations with caching
 * - Proper input sanitization and validation
 * - Helper methods for relationship management
 * - WooCommerce product meta box integration
 * - Batch processing for better performance
 * 
 * @package MGBdev\Eifu_Docs_Class
 */

namespace MGBdev\Eifu_Docs_Class;

/**
 * Class Product_Documents_Manager
 * 
 * Manages the relationship between WooCommerce products and IFU documents.
 * Provides meta box functionality for selecting and saving document associations.
 */
class Product_Documents_Manager {
    
    /**
     * Meta field constants
     */
    const META_KEY_PRODUCT_DOCUMENTS = '_product_documents';
    const META_KEY_JSON_RELATIONSHIPS = '_product_documents_json';
    const NONCE_ACTION = 'save_product_documents';
    const NONCE_FIELD = 'product_documents_nonce';
    
    /**
     * Cache for document queries and relationships
     */
    private static $documents_cache = null;
    private static $relationships_cache = [];
    
    /**
     * Initialize the class by hooking into WordPress and WooCommerce
     */
    public function __construct() {
        add_action( 'add_meta_boxes', [ $this, 'add_meta_boxes' ] );
        add_action( 'save_post', [ $this, 'save_product_documents' ] );
        add_action( 'wp_ajax_load_product_documents', [ $this, 'ajax_load_documents' ] );
    }
    
    /**
     * Add meta box to WooCommerce product edit screen
     */
    public function add_meta_boxes() {
        // Only add to product post type (WooCommerce)
        $screen = get_current_screen();
        if ( ! $screen || 'product' !== $screen->id ) {
            return;
        }
        
        add_meta_box(
            'product_documents_manager',
            __( 'Associated IFU Documents', 'eifu-class' ),
            [ $this, 'render_meta_box' ],
            'product',
            'normal',
            'high'
        );
    }
    
    /**
     * Render the product documents meta box
     * 
     * @param WP_Post $post The product post object
     */
    public function render_meta_box( $post ) {
        // Add nonce for security
        wp_nonce_field( self::NONCE_ACTION, self::NONCE_FIELD );
        
        // Get selected documents for this product
        $selected_documents = $this->get_product_documents( $post->ID );
        
        // Get available documents
        $available_documents = $this->get_available_documents();
        
        if ( empty( $available_documents ) ) {
            $this->render_no_documents_message();
            return;
        }
        
        $this->render_documents_selector( $available_documents, $selected_documents );
        $this->render_relationship_summary( $post->ID );
    }
    
    /**
     * Render message when no documents are available
     */
    private function render_no_documents_message() {
        ?>
        <div class="product-documents-meta-box">
            <p class="description">
                <?php 
                printf(
                    esc_html__( 'No IFU documents are available. Please create some %s first.', 'eifu-class' ),
                    '<a href="' . esc_url( admin_url( 'edit.php?post_type=' . EIFUC_G_PT ) ) . '">' . 
                    esc_html__( 'IFU documents', 'eifu-class' ) . '</a>'
                );
                ?>
            </p>
        </div>
        <?php
    }
    
    /**
     * Render the documents selector interface
     * 
     * @param array $available_documents Available documents
     * @param array $selected_documents Currently selected document IDs
     */
    private function render_documents_selector( $available_documents, $selected_documents ) {
        ?>
        <div class="product-documents-meta-box">
            <table class="form-table">
                <tr>
                    <td colspan="2">
                        <p><strong><?php esc_html_e( 'Select IFU documents to associate with this product:', 'eifu-class' ); ?></strong></p>
                        <p class="description">
                            <?php esc_html_e( 'Selected documents will be linked to this product and can be displayed on the frontend or used for filtering.', 'eifu-class' ); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <td colspan="2">
                        <div class="product-documents-selector" style="max-height: 300px; overflow-y: auto; border: 1px solid #ddd; padding: 15px; background: #f9f9f9;">
                            <div class="documents-list">
                                <?php foreach ( $available_documents as $document ) : ?>
                                    <?php
                                    $is_selected = in_array( $document->ID, $selected_documents, true );
                                    $checkbox_id = 'product_document_' . $document->ID;
                                    ?>
                                    <div class="document-item" style="margin-bottom: 12px; padding: 8px; background: white; border-radius: 3px;">
                                        <label for="<?php echo esc_attr( $checkbox_id ); ?>" style="display: flex; align-items: center; cursor: pointer;">
                                            <input type="checkbox" 
                                                   id="<?php echo esc_attr( $checkbox_id ); ?>" 
                                                   name="product_documents[]" 
                                                   value="<?php echo esc_attr( $document->ID ); ?>" 
                                                   <?php checked( $is_selected ); ?> 
                                                   style="margin-right: 10px;" />
                                            <div class="document-info">
                                                <strong><?php echo esc_html( $document->post_title ); ?></strong>
                                                <?php if ( ! empty( $document->post_excerpt ) ) : ?>
                                                    <br><span class="description"><?php echo esc_html( wp_trim_words( $document->post_excerpt, 15 ) ); ?></span>
                                                <?php endif; ?>
                                                <br><small class="meta-info">
                                                    <?php 
                                                    printf( 
                                                        esc_html__( 'ID: %d | Last modified: %s', 'eifu-class' ),
                                                        $document->ID,
                                                        esc_html( get_the_modified_date( 'M j, Y', $document ) )
                                                    ); 
                                                    ?>
                                                </small>
                                            </div>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <div class="bulk-actions" style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #ddd;">
                                <button type="button" class="button button-secondary" onclick="jQuery('.product-documents-selector input[type=checkbox]').prop('checked', true);">
                                    <?php esc_html_e( 'Select All', 'eifu-class' ); ?>
                                </button>
                                <button type="button" class="button button-secondary" onclick="jQuery('.product-documents-selector input[type=checkbox]').prop('checked', false);" style="margin-left: 10px;">
                                    <?php esc_html_e( 'Deselect All', 'eifu-class' ); ?>
                                </button>
                            </div>
                        </div>
                    </td>
                </tr>
            </table>
        </div>
        <?php
    }
    
    /**
     * Render relationship summary and JSON data
     * 
     * @param int $product_id The product ID
     */
    private function render_relationship_summary( $product_id ) {
        $json_data = get_post_meta( $product_id, self::META_KEY_JSON_RELATIONSHIPS, true );
        
        if ( ! empty( $json_data ) ) {
            ?>
            <div class="product-documents-summary" style="margin-top: 20px; padding: 15px; background: #f0f0f1; border-radius: 3px;">
                <h4><?php esc_html_e( 'Relationship Data Summary', 'eifu-class' ); ?></h4>
                <textarea readonly rows="8" style="width: 100%; font-family: monospace; font-size: 12px;"><?php echo esc_textarea( $json_data ); ?></textarea>
                <p>
                    <button type="button" class="button button-secondary" onclick="this.previousElementSibling.select(); document.execCommand('copy'); this.innerText='<?php esc_attr_e( 'Copied!', 'eifu-class' ); ?>'; setTimeout(() => this.innerText='<?php esc_attr_e( 'Copy JSON', 'eifu-class' ); ?>', 2000);">
                        <?php esc_html_e( 'Copy JSON', 'eifu-class' ); ?>
                    </button>
                </p>
            </div>
            <?php
        }
    }
    
    /**
     * Save product documents relationships
     * 
     * @param int $post_id The post ID being saved
     */
    public function save_product_documents( $post_id ) {
        // Skip if save should not proceed
        if ( $this->should_skip_save( $post_id ) ) {
            return;
        }
        
        // Process and validate document selections
        $selected_documents = $this->process_document_selections();
        
        // Save the relationships
        $this->save_document_relationships( $post_id, $selected_documents );
        
        // Generate and save JSON summary
        $this->save_relationship_json( $post_id, $selected_documents );
        
        // Clear caches
        $this->clear_relationship_cache( $post_id );
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
        if ( 'product' !== get_post_type( $post_id ) ) {
            return true;
        }
        
        // Check the nonce
        if ( ! isset( $_POST[ self::NONCE_FIELD ] ) || 
             ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ self::NONCE_FIELD ] ) ), self::NONCE_ACTION ) ) {
            return true;
        }
        
        // Check user permissions
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Process and validate document selections from form submission
     * 
     * @return array Validated document IDs
     */
    private function process_document_selections() {
        // Check the nonce
        if ( ! isset( $_POST[ self::NONCE_FIELD ] ) || 
             ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ self::NONCE_FIELD ] ) ), self::NONCE_ACTION ) ) {
            return ;
        }
        
        $selected_documents = isset( $_POST['product_documents'] ) && is_array( $_POST['product_documents'] ) 
            ? $_POST['product_documents'] 
            : [];
        
        // Sanitize and validate document IDs
        $selected_documents = array_map( 'absint', $selected_documents );
        $selected_documents = array_filter( $selected_documents, function( $id ) {
            return $id > 0 && get_post_type( $id ) === EIFUC_G_PT;
        });
        
        return array_unique( $selected_documents );
    }
    
    /**
     * Save document relationships to product meta
     * 
     * @param int   $product_id        The product ID
     * @param array $selected_documents Array of document IDs
     */
    private function save_document_relationships( $product_id, $selected_documents ) {
        if ( empty( $selected_documents ) ) {
            delete_post_meta( $product_id, self::META_KEY_PRODUCT_DOCUMENTS );
        } else {
            update_post_meta( $product_id, self::META_KEY_PRODUCT_DOCUMENTS, $selected_documents );
        }
    }
    
    /**
     * Generate and save JSON summary of relationships
     * 
     * @param int   $product_id        The product ID
     * @param array $selected_documents Array of document IDs
     */
    private function save_relationship_json( $product_id, $selected_documents ) {
        $product = wc_get_product( $product_id );
        
        if ( ! $product ) {
            return;
        }
        
        $relationship_data = [
            'product_id' => $product_id,
            'product_title' => $product->get_name(),
            'product_sku' => $product->get_sku(),
            'document_count' => count( $selected_documents ),
            'document_ids' => $selected_documents,
            'documents' => [],
            'last_updated' => current_time( 'mysql' ),
            'updated_by' => get_current_user_id(),
        ];
        
        // Add document details
        foreach ( $selected_documents as $doc_id ) {
            $document = get_post( $doc_id );
            if ( $document ) {
                $relationship_data['documents'][] = [
                    'id' => $doc_id,
                    'title' => $document->post_title,
                    'status' => $document->post_status,
                    'modified' => $document->post_modified,
                ];
            }
        }
        
        $json_data = wp_json_encode( $relationship_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE );
        
        if ( empty( $selected_documents ) ) {
            delete_post_meta( $product_id, self::META_KEY_JSON_RELATIONSHIPS );
        } else {
            update_post_meta( $product_id, self::META_KEY_JSON_RELATIONSHIPS, $json_data );
        }
    }
    
    /**
     * Get available IFU documents
     * 
     * @return array Array of document post objects
     */
    private function get_available_documents() {
        if ( null === self::$documents_cache ) {
            self::$documents_cache = get_posts([
                'post_type' => EIFUC_G_PT,
                'posts_per_page' => -1,
                'post_status' => 'publish',
                'orderby' => 'title',
                'order' => 'ASC',
                'meta_query' => [
                    'relation' => 'OR',
                    [
                        'key' => '_thumbnail_id',
                        'compare' => 'EXISTS'
                    ],
                    [
                        'key' => '_thumbnail_id',
                        'compare' => 'NOT EXISTS'
                    ]
                ]
            ]);
        }
        
        return self::$documents_cache;
    }
    
    /**
     * Get documents associated with a product
     * 
     * @param int $product_id The product ID
     * @return array Array of document IDs
     */
    public function get_product_documents( $product_id ) {
        $cache_key = 'product_' . $product_id;
        
        if ( ! isset( self::$relationships_cache[ $cache_key ] ) ) {
            $documents = get_post_meta( $product_id, self::META_KEY_PRODUCT_DOCUMENTS, true );
            self::$relationships_cache[ $cache_key ] = is_array( $documents ) ? $documents : [];
        }
        
        return self::$relationships_cache[ $cache_key ];
    }
    
    /**
     * Get products associated with a document
     * 
     * @param int $document_id The document ID
     * @return array Array of product IDs
     */
    public static function get_document_products( $document_id ) {
        global $wpdb;
        
        $cache_key = 'document_' . $document_id;
        
        if ( ! isset( self::$relationships_cache[ $cache_key ] ) ) {
            $query = $wpdb->prepare(
                "SELECT post_id FROM {$wpdb->postmeta} 
                 WHERE meta_key = %s 
                 AND meta_value LIKE %s",
                self::META_KEY_PRODUCT_DOCUMENTS,
                '%' . $wpdb->esc_like( serialize( (string) $document_id ) ) . '%'
            );
            
            $results = $wpdb->get_col( $query );
            self::$relationships_cache[ $cache_key ] = array_map( 'intval', $results );
        }
        
        return self::$relationships_cache[ $cache_key ];
    }
    
    /**
     * Check if a product has a specific document
     * 
     * @param int $product_id  The product ID
     * @param int $document_id The document ID
     * @return bool True if relationship exists
     */
    public static function product_has_document( $product_id, $document_id ) {
        $documents = ( new self() )->get_product_documents( $product_id );
        return in_array( (int) $document_id, $documents, true );
    }
    
    /**
     * Display associated documents for a product (for frontend use)
     * 
     * @param int    $product_id The product ID
     * @param string $format     Display format: 'list', 'grid', 'links'
     */
    public static function display_product_documents( $product_id, $format = 'list' ) {
        $manager = new self();
        $document_ids = $manager->get_product_documents( $product_id );
        
        if ( empty( $document_ids ) ) {
            return;
        }
        
        $documents = get_posts([
            'post__in' => $document_ids,
            'post_type' => EIFUC_G_PT,
            'post_status' => 'publish',
            'orderby' => 'post__in'
        ]);
        
        if ( empty( $documents ) ) {
            return;
        }
        
        switch ( $format ) {
            case 'grid':
                self::render_documents_grid( $documents );
                break;
            case 'links':
                self::render_documents_links( $documents );
                break;
            case 'list':
            default:
                self::render_documents_list( $documents );
                break;
        }
    }
    
    /**
     * Render documents as a list
     * 
     * @param array $documents Array of document post objects
     */
    private static function render_documents_list( $documents ) {
        echo '<div class="product-documents-list">';
        echo '<h4>' . esc_html__( 'Related Documents', 'eifu-class' ) . '</h4>';
        echo '<ul>';
        foreach ( $documents as $document ) {
            printf(
                '<li><a href="%s">%s</a></li>',
                esc_url( get_permalink( $document ) ),
                esc_html( $document->post_title )
            );
        }
        echo '</ul></div>';
    }
    
    /**
     * Render documents as links
     * 
     * @param array $documents Array of document post objects
     */
    private static function render_documents_links( $documents ) {
        $links = [];
        foreach ( $documents as $document ) {
            $links[] = sprintf(
                '<a href="%s">%s</a>',
                esc_url( get_permalink( $document ) ),
                esc_html( $document->post_title )
            );
        }
        
        printf(
            '<div class="product-documents-links"><strong>%s:</strong> %s</div>',
            esc_html__( 'Related Documents', 'eifu-class' ),
            implode( ', ', $links )
        );
    }
    
    /**
     * Render documents as a grid
     * 
     * @param array $documents Array of document post objects
     */
    private static function render_documents_grid( $documents ) {
        echo '<div class="product-documents-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">';
        echo '<h4 style="grid-column: 1 / -1;">' . esc_html__( 'Related Documents', 'eifu-class' ) . '</h4>';
        
        foreach ( $documents as $document ) {
            echo '<div class="document-card" style="border: 1px solid #ddd; padding: 15px; border-radius: 5px;">';
            
            if ( has_post_thumbnail( $document ) ) {
                printf(
                    '<div class="document-thumbnail">%s</div>',
                    get_the_post_thumbnail( $document, 'thumbnail' )
                );
            }
            
            printf(
                '<h5><a href="%s">%s</a></h5>',
                esc_url( get_permalink( $document ) ),
                esc_html( $document->post_title )
            );
            
            if ( $document->post_excerpt ) {
                printf( '<p>%s</p>', esc_html( wp_trim_words( $document->post_excerpt, 20 ) ) );
            }
            
            echo '</div>';
        }
        
        echo '</div>';
    }
    
    /**
     * Clear relationship cache for a specific product
     * 
     * @param int $product_id The product ID
     */
    private function clear_relationship_cache( $product_id ) {
        $cache_key = 'product_' . $product_id;
        unset( self::$relationships_cache[ $cache_key ] );
        
        // Clear documents cache as well to ensure fresh data
        self::$documents_cache = null;
    }
    
    /**
     * Get relationship statistics
     * 
     * @return array Statistics about relationships
     */
    public static function get_relationship_stats() {
        global $wpdb;
        
        $stats = [];
        
        // Total products with documents
        $stats['products_with_documents'] = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(DISTINCT post_id) FROM {$wpdb->postmeta} WHERE meta_key = %s",
                self::META_KEY_PRODUCT_DOCUMENTS
            )
        );
        
        // Total relationships
        $stats['total_relationships'] = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = %s",
                self::META_KEY_PRODUCT_DOCUMENTS
            )
        );
        
        return $stats;
    }
    
    /**
     * AJAX handler for loading documents dynamically
     */
    public function ajax_load_documents() {
        check_ajax_referer( 'load_product_documents', 'nonce' );
        
        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_die( -1, 403 );
        }
        
        $documents = $this->get_available_documents();
        
        wp_send_json_success([
            'documents' => array_map( function( $doc ) {
                return [
                    'id' => $doc->ID,
                    'title' => $doc->post_title,
                    'excerpt' => $doc->post_excerpt,
                    'modified' => get_the_modified_date( 'M j, Y', $doc ),
                ];
            }, $documents )
        ]);
    }
}
