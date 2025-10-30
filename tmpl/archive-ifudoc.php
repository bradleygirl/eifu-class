<?php
/**
 * Custom archive template for IFU Documents
 */

get_header(); ?>
<div id="primary" class="content-area">
	<main id="main" class="site-main" role="main">

<div class="ifu-archive-wrapper">
    <header class="ifu-archive-header">
        <h1><?php echo esc_html__('IFU Documents', 'eifu-class'); ?></h1>
       <!-- <form class="ifu-archive-filter" method="get" action="">
            <input type="search" name="s" placeholder="<?php esc_attr_e('Search documents...', 'eifu-class'); ?>" value="<?php echo get_search_query(); ?>" />
            <?php
            $categories = get_terms(array(
                'taxonomy' => EIFUC_G_TX,
                'hide_empty' => false,
            ));
            if ($categories && !is_wp_error($categories)) {
                echo '<select name="document_category">';
                echo '<option value="">' . esc_html__('All Categories', 'eifu-class') . '</option>';
                foreach ($categories as $cat) {
                    $selected = (isset($_GET[EIFUC_G_TX]) && $_GET[EIFUC_G_TX] == $cat->slug) ? 'selected' : '';
                    echo '<option value="' . esc_attr($cat->slug) . '" ' . $selected . '>' . esc_html($cat->name) . '</option>';
                }
                echo '</select>';
            }
            ?>
            <button type="submit"><?php esc_html_e('Filter', 'eifu-class'); ?></button>
        </form>-->
    </header>

    <?php
    // Hierarchical taxonomy rendering for IFU Documents
    $top_terms = get_terms(array(
        'taxonomy' => EIFUC_G_TX,
        'parent' => 0,
        'hide_empty' => false,
    ));
    
    if ($top_terms && !is_wp_error($top_terms)) :
        foreach ($top_terms as $top_term) :
            if ( ! \MGBdev\Eifu_Docs_Class\IFU_Post_Register::get_term_hide_cat( $top_term->term_id ) ) {
            // Get layer 2 children
            $child_terms = get_terms(array(
                'taxonomy' => EIFUC_G_TX,
                'parent' => $top_term->term_id,
                'hide_empty' => false,
            ));
            // Only show parent if it has children with posts
            $has_child_with_posts = false;
            if ($child_terms && !is_wp_error($child_terms)) {
                foreach ($child_terms as $child_term) {
                    $child_query = new WP_Query(array(
                        'post_type' => EIFUC_G_PT,
                        'tax_query' => array(array(
                            'taxonomy' => EIFUC_G_TX,
                            'field' => 'term_id',
                            'terms' => $child_term->term_id,
                        )),
                        'posts_per_page' => -1,
                    ));
                    if ($child_query->have_posts()) {
                        $has_child_with_posts = true;
                        break;
                    }
                }
            }
            if (!$has_child_with_posts) continue;
            ?>
            <section class="ifu-category-group" aria-labelledby="cat-<?php echo esc_attr($top_term->term_id); ?>">
                <h2 id="cat-<?php echo esc_attr($top_term->term_id); ?>"><?php echo esc_html($top_term->name); ?></h2>
                <?php
                if ($child_terms && !is_wp_error($child_terms)) :
                    foreach ($child_terms as $child_term) :
                        $child_query = new WP_Query(array(
                            'post_type' => EIFUC_G_PT,
                            'tax_query' => array(array(
                                'taxonomy' => EIFUC_G_TX,
                                'field' => 'term_id',
                                'terms' => $child_term->term_id,
                            )),
                            'posts_per_page' => -1,
                        ));
                        if (!$child_query->have_posts()) continue;
                        ?>
                        <div class="ifu-category-child-group" aria-labelledby="child-<?php echo esc_attr($child_term->term_id); ?>">
                            <h3 id="child-<?php echo esc_attr($child_term->term_id); ?>"><?php echo esc_html($child_term->name); ?></h3>
                            <div class="ifu-documents-cards" role="list">
                                <?php while ($child_query->have_posts()) : $child_query->the_post(); ?>
                                    <div class="ifu-document-card" role="listitem">
                                        <h4 class="ifu-document-title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h4>
                                        <div class="ifu-document-excerpt"><?php the_excerpt(); ?></div>
                                        <div class="ifu-document-meta">
                                            <?php
                                            $file_size = get_post_meta(get_the_ID(), '_document_file_size', true);
                                            if ($file_size) {
                                                echo '<span class="ifu-file-size">' . esc_html($file_size) . '</span>';
                                            }
                                            // Download links for each language
                                            $eng_usa_checked = get_post_meta(get_the_ID(), '_document_eng_usa_checked', true);
                                            $eng_usa_url = get_post_meta(get_the_ID(), '_document_eng_usa_url', true);
                                            $eng_ce_checked = get_post_meta(get_the_ID(), '_document_eng_ce_checked', true);
                                            $eng_ce_url = get_post_meta(get_the_ID(), '_document_eng_ce_url', true);
                                            $base_url = get_post_meta(get_the_ID(), '_document_base_url', true);
                                            $checked_langs = get_post_meta(get_the_ID(), '_document_languages', true);
                                            if (!is_array($checked_langs)) $checked_langs = array();
                                            $langs = function_exists('MGBdev\\WC_Eifu_Docs\\eifu_get_supported_languages') ? \MGBdev\WC_Eifu_Docs\eifu_get_supported_languages() : array();
                                            echo '<ul class="ifu-download-list">';
                                            // English-USA
                                            if ($eng_usa_checked && $eng_usa_url) {
                                                $url = rtrim($eng_usa_url, '/') . '_ENG-USA.pdf';
                                                echo '<li><a href="' . esc_url($url) . '" target="_blank" class="ifu-download-link">' . esc_html__('English-USA', 'eifu-class') . '</a></li>';
                                            }
                                            // English-CE
                                            if ($eng_ce_checked && $eng_ce_url) {
                                                $url = rtrim($eng_ce_url, '/') . '_ENG-CE.pdf';
                                                echo '<li><a href="' . esc_url($url) . '" target="_blank" class="ifu-download-link">' . esc_html__('English-CE', 'eifu-class') . '</a></li>';
                                            }
                                            // Other languages
                                            if ($base_url && $checked_langs) {
                                                foreach ($checked_langs as $code) {
                                                    $label = isset($langs[$code]) ? $langs[$code] : $code;
                                                    $download_url = rtrim($base_url, '/') . '_' . $code . '.pdf';
                                                    echo '<li><a href="' . esc_url($download_url) . '" target="_blank" class="ifu-download-link">' . esc_html($label) . ' (' . esc_html($code) . ')</a></li>';
                                                }
                                            }
                                            echo '</ul>';
                                            ?>
                                        </div>
                                    </div>
                                <?php endwhile; wp_reset_postdata(); ?>
                            </div>
                        </div>
                    <?php endforeach;
                endif;
                ?>
            </section>
        <?php 
    }    
    endforeach;
    else : ?>
        <p><?php esc_html_e('No IFU Document categories found.', 'eifu-class'); ?></p>
    <?php endif; ?>
</div>
	</main><!-- #main -->
</div><!-- primary -->

<?php get_sidebar(); ?>
<style>
    .ifu-document-title {
  margin-left:1rem;
  &:before {
      font-family: 'bbiconfont';
  content: '\e043';
  display: inline-block;
  margin-right: 1rem;
  color: hsl(207.3, 20.3%, 58.5%);
}
</style>
<?php get_footer(); ?> 