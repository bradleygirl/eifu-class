<?php
/**
 * Product Documents Tab Frontend Renderer
 * 
 * This class handles the frontend rendering of a new WooCommerce product tab 
 * that displays the documents associated with a product via the documents manager.
 * The tab only appears if there are related documents, and displays titles with 
 * links to the document posts.
 * 
 * FEATURES INCLUDED:
 * - OOP design with proper initialization and WordPress hooks
 * - Conditional tab display (only shows if documents exist)
 * - JSON data decoding and PHP array manipulation
 * - Document title and link rendering with proper escaping
 * - Integration with existing Product_Documents_Manager
 * - WooCommerce product tabs filter implementation
 * - Proper sanitization and validation
 * 
 * @package MGBdev\Eifu_Docs_Class
 */

namespace MGBdev\Eifu_Docs_Class;

/**
 * Class Product_Documents_Tab
 * 
 * Manages the frontend display of related documents in a WooCommerce product tab.
 * Uses the woocommerce_product_tabs filter to add a custom tab when documents exist.
 */
class Product_Documents_Tab {
    
    /**
     * Tab priority for ordering
     */
    const TAB_PRIORITY = 15;
    
    /**
     * Tab key identifier
     */
    const TAB_KEY = 'ifu_documents';
    
    /**
     * Cache for product documents to avoid repeated queries
     */
    private static $documents_cache = [];
    
    /**
     * Initialize the class by hooking into WooCommerce
     */
    public function __construct() {
        add_filter( 'woocommerce_product_tabs', [ $this, 'add_documents_tab' ], 10, 1 );
    }
    
    /**
     * Add the documents tab to WooCommerce product tabs
     * 
     * @param array $tabs Existing product tabs
     * @return array Modified tabs array
     */
    public function add_documents_tab( $tabs ) {
        global $product;
        
        // Verify we have a valid product
        if ( ! $product instanceof \WC_Product ) {
            return $tabs;
        }
        
        $product_id = $product->get_id();
        
        // Check if product has associated documents
        if ( ! $this->product_has_documents( $product_id ) ) {
            return $tabs;
        }
        
        // Add the documents tab
        $tabs[ self::TAB_KEY ] = [
            'title'    => __( 'IFU Documents', 'eifu-class' ),
            'priority' => self::TAB_PRIORITY,
            'callback' => [ $this, 'render_documents_tab_content' ],
        ];
        
        return $tabs;
    }
    
    /**
     * Render the content for the documents tab
     */
    public function render_documents_tab_content() {
        global $product;
        
        if ( ! $product instanceof \WC_Product ) {
            return;
        }
        
        $product_id = $product->get_id();
        $documents = $this->get_product_documents_data( $product_id );
        
        if ( empty( $documents ) ) {
            $this->render_no_documents_message();
            return;
        }
        
        $this->render_documents_list( $documents );
    }
    
    /**
     * Check if a product has associated documents
     * 
     * @param int $product_id The product ID
     * @return bool True if product has documents
     */
    private function product_has_documents( $product_id ) {
        // Use the existing Product_Documents_Manager method if available
        if ( class_exists( __NAMESPACE__ . '\\Product_Documents_Manager' ) ) {
            $manager = new Product_Documents_Manager();
            $document_ids = $manager->get_product_documents( $product_id );
            return ! empty( $document_ids );
        }
        
        // Fallback method
        $document_ids = get_post_meta( $product_id, '_product_documents', true );
        return is_array( $document_ids ) && ! empty( $document_ids );
    }
    
    /**
     * Get documents data for a product
     * 
     * @param int $product_id The product ID
     * @return array Array of document data
     */
    private function get_product_documents_data( $product_id ) {
        // Check cache first
        if ( isset( self::$documents_cache[ $product_id ] ) ) {
            return self::$documents_cache[ $product_id ];
        }
        
        $documents_data = [];
        
        // Get JSON relationship data first (if available)
        $json_data = get_post_meta( $product_id, '_product_documents_json', true );
        
        if ( ! empty( $json_data ) ) {
            $relationship_data = json_decode( $json_data, true );
            if ( is_array( $relationship_data ) && isset( $relationship_data['documents'] ) ) {
                foreach ( $relationship_data['documents'] as $doc_data ) {
                    if ( isset( $doc_data['id'] ) ) {
                        $documents_data[] = $this->prepare_document_data( $doc_data['id'], $doc_data );
                    }
                }
            }
        }
        
        // Fallback: Get documents by IDs if JSON data not available
        if ( empty( $documents_data ) ) {
            $document_ids = get_post_meta( $product_id, '_product_documents', true );
            
            if ( is_array( $document_ids ) && ! empty( $document_ids ) ) {
                foreach ( $document_ids as $doc_id ) {
                    $documents_data[] = $this->prepare_document_data( $doc_id );
                }
            }
        }
        
        // Cache the results
        self::$documents_cache[ $product_id ] = $documents_data;
        
        return $documents_data;
    }
    
    /**
     * Prepare document data for rendering
     * 
     * @param int   $document_id The document post ID
     * @param array $cached_data Optional cached data from JSON
     * @return array|null Document data or null if document doesn't exist
     */
    private function prepare_document_data( $document_id, $cached_data = [] ) {
        // Validate document ID
        $document_id = absint( $document_id );
        if ( ! $document_id ) {
            return null;
        }
        
        // Get the post object
        $document = get_post( $document_id );
        
        // Verify it's a valid published document
        if ( ! $document || 
             $document->post_type !== EIFUC_G_PT || 
             $document->post_status !== 'publish' ) {
            return null;
        }
        
        // Prepare document data
        $document_data = [
            'id'          => $document_id,
            'title'       => $document->post_title,
            'url'         => get_permalink( $document ),
            'excerpt'     => $document->post_excerpt,
            'modified'    => $document->post_modified,
        ];
        
        // Add cached data if available
        if ( ! empty( $cached_data ) ) {
            $document_data = array_merge( $document_data, $cached_data );
        }
        
        return $document_data;
    }
    
    /**
     * Render the list of documents
     * 
     * @param array $documents Array of document data
     */
    private function render_documents_list( $documents ) {
        ?>
            <h2>IFU Documents</h2>
        <div class="ifu-documents-tab-content">
            <div class="ifu-documents-grid">
                <?php foreach ( $documents as $document ) : ?>
                    <?php if ( $document && isset( $document['title'], $document['url'] ) ) : ?>
                        
                        <div class="ifu-document-item">
                            <div class="ifu-document-header">
                                <h4 class="ifu-document-title">
                                    <a href="<?php echo esc_url( $document['url'] ); ?>" class="ifu-document-link" ><?php echo esc_html( $document['title'] ); ?></a>
                                </h4>
                            </div>
                            
                            <?php if ( ! empty( $document['excerpt'] ) ) : ?>
                                <div class="ifu-document-excerpt">
                                    <p><?php echo esc_html( wp_trim_words( $document['excerpt'], 20 ) ); ?></p>
                                </div>
                            <?php endif; ?>
                            
                            <div class="ifu-document-actions">
                                <a href="<?php echo esc_url( $document['url'] ); ?>" 
                                   class="button ifu-document-button" >
                                    <?php esc_html_e( 'View / Download', 'eifu-class' ); ?>
                                </a>
                            </div>
                            

                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
        
        <style>
                       .ifu-documents-tab-content {
                margin: 20px 0;
            }
            
            .ifu-documents-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
                gap: 20px;
                margin-bottom: 20px;
            }
            
            .ifu-document-item {
                border: 1px solid #e1e1e1;
                border-radius: 5px;
                padding: 20px;
                background: #f9f9f9;
                transition: box-shadow 0.3s ease;
                display:flex;
                flex-wrap:wrap;justify-content: center;
            }
            
            .ifu-document-item:hover {
                box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            }
            .ifu-document-header {

                flex-basis: 100%;
            }
            .ifu-document-title {
                margin: 0 0 10px 0;
                font-size: 18px;
                line-height: 1.4;
                color: #333;
                text-align: center;
                &:before {
                    font-family: 'bbiconfont';
                    content: '\e043';
                    display: inline-block;
                    margin-right: 0.5rem;
                    color: hsl(207.3, 20.3%, 58.5%);
}
            }
            
            .ifu-document-excerpt {
                margin-bottom: 15px;
            }
            
            .ifu-document-excerpt p {
                margin: 0;
                color: #666;
                font-size: 14px;
                line-height: 1.5;
            }
            
            .ifu-document-actions {
                margin-bottom: 10px;
            }
            
            .ifu-document-button {
                display: inline-block;
                padding: 8px 16px;
                background-color: var(--wp--preset--color--light-blue);
                border: 1px solid #ccc;
                color: hsl(207.3, 20.3%, 58.5%);
                text-decoration: none;
                border-radius: 3px;
                font-size: 14px;
                font-weight: 500;
                transition: background-color 0.3s ease;
            }
            
            .ifu-document-button:hover {
                border-color: hsl(207.3, 20.3%, 58.5%);
                color: hsl(207.3, 20.3%, 58.5%);
                
                background-color: var(--wp--preset--color--light-blue);
            }
            
            .ifu-document-meta {
                border-top: 1px solid #e1e1e1;
                padding-top: 8px;
                margin-top: 15px;
            }
        </style>
        <?php
    }
    
    /**
     * Render message when no documents are available
     */
    private function render_no_documents_message() {
        ?>
        <div class="ifu-documents-tab-content">
            <p class="ifu-no-documents">
                <?php esc_html_e( 'No documents are currently attached for this product.', 'eifu-class' ); ?>
            </p>
        </div>
        <style>
            .ifu-no-documents {
                text-align: center;
                color: #666;
                font-style: italic;
                margin: 20px 0;
                padding: 20px;
                background: #f9f9f9;
                border-radius: 5px;
            }
        </style>
        <?php
    }
    
    /**
     * Clear cache for a specific product
     * 
     * @param int $product_id The product ID
     */
    public static function clear_cache( $product_id ) {
        unset( self::$documents_cache[ $product_id ] );
    }
    
    /**
     * Clear all cached data
     */
    public static function clear_all_cache() {
        self::$documents_cache = [];
    }
    
    /**
     * Get tab configuration for external use
     * 
     * @return array Tab configuration
     */
    public static function get_tab_config() {
        return [
            'key'      => self::TAB_KEY,
            'title'    => __( 'IFU Documents', 'eifu-class' ),
            'priority' => self::TAB_PRIORITY,
        ];
    }
}

// Initialize the class if not in admin context
if ( ! is_admin() ) {
    new Product_Documents_Tab();
}
