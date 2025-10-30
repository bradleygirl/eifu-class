<?php
/**
 * Custom archive template for IFU Documents
 */

get_header(); ?>
<div id="primary" class="content-area">
	<main id="main" class="site-main" role="main">
	<div class="ifu-archive-wrapper">
   

    <?php
    // Hierarchical taxonomy rendering for IFU Documents
    $current_term = get_queried_object();
    $current_term_id = $current_term->term_id;
    $current_term_name = $current_term->name;
    $current_term_description = $current_term->description;
    $current_term_slug = $current_term->slug;
    $current_term_count = $current_term->count;
    $current_term_children = get_terms(array(
        'taxonomy' => EIFUC_G_TX,
        'parent' => $current_term_id,
        'hide_empty' => false,
    ));
    $current_term_children_count = count($current_term_children);
    //echo $current_term_children_count;
?>
    <header class="ifu-archive-header">
        <h1><?php echo esc_html($current_term_name); ?></h1>

    </header>

<?php
    $direct_posts = new WP_Query(array(
        'post_type' => EIFUC_G_PT,
        'tax_query' => array(array(
            'taxonomy' => EIFUC_G_TX,
            'field' => 'term_id',
            'terms' => $current_term_id,
            'include_children' => false,
        )),
        'posts_per_page' => -1,
    ));
    if ($direct_posts->have_posts()) {
        $direct_has_posts = true;
    }
    else {
        $direct_has_posts = false;
    }
    if ($direct_has_posts) {
        // display direct posts
        ?>
        <section class="ifu-category-group" aria-labelledby="cat-<?php echo esc_attr($current_term_id); ?>">
          
            <div class="ifu-documents-cards" role="list">
                <?php while ($direct_posts->have_posts()) : $direct_posts->the_post(); ?>
                    <div class="ifu-document-card" role="listitem">
                        <h4 class="ifu-document-title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h4>
                    </div>
                <?php endwhile; wp_reset_postdata(); ?>
            </div>
        </section>
        <?php
    }

    if ( $current_term_children && !is_wp_error( $current_term_children)) :
        foreach ( $current_term_children as $child_term) :
            if ( ! \MGBdev\Eifu_Docs_Class\IFU_Post_Register::get_term_hide_cat( $child_term->term_id ) ) {
            // check if term has posts
            $child_id = $child_term->term_id;
            $child_term_permalink = get_term_link($child_term);
            $child_term_posts = new WP_Query(array(
                'post_type' => EIFUC_G_PT,
                'tax_query' => array(array(
                    'taxonomy' => EIFUC_G_TX,
                    'field' => 'term_id',
                    'terms' => $child_id,
                )),
                'posts_per_page' => -1,
            ));
            if ($child_term_posts->have_posts()) {
                $child_has_posts = true;
            }
            else {
                $child_has_posts = false;
            } 
            if (!$child_has_posts) continue;
            else {
                // display child terms with posts

                ?>
                <section class="ifu-category-group" aria-labelledby="cat-<?php echo esc_attr($child_id); ?>">
                <h2 id="cat-<?php echo esc_attr($child_id); ?>"><?php echo esc_html($child_term->name); ?></h2>
                <div class="ifu-documents-cards" role="list">
                    <?php while ($child_term_posts->have_posts()) : $child_term_posts->the_post(); ?>
                        <div class="ifu-document-card" role="listitem">
                            <h4 class="ifu-document-title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h4>
                        </div>
                    <?php endwhile; wp_reset_postdata(); ?>
                </div>
            </section>
                
                <?php
            } 
            // Get layer 2 children
 
            ?>

        <?php 
    }    
    endforeach;
    else : ?>
      
    <?php endif; ?>
		</div>
	</main><!-- #main -->
</div><!-- primary -->

<?php get_sidebar(); ?>
<style>
	.ifu-category-group {margin-bottom:4rem;}
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