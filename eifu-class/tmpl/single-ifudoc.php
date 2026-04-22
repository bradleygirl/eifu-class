<?php
/**
 * Custom single post template for IFU Documents
 */

get_header(); 

global $post;
setup_postdata($post);

?>

<div id="primary" class="content-area">
	<main id="main" class="site-main" role="main">
<div class="ifu-doc-wrapper" >
    <header class="ifu-doc-header">
        <h1 id="doc-title"><?php echo esc_html__($post->post_title, 'eifu-class'); ?></h1>
        <!--<h3><?php echo esc_html__('This is a custom template for the single IFU post type.', 'eifu-class'); ?></h3>-->
    </header>
    <?php
    // Get the ID of the current post
    $post_id = get_the_ID();
    
    // Get the JSON meta data using the constant from IFU_Post_Register class
    $doc_json = get_post_meta( $post_id, \MGBdev\Eifu_Docs_Class\IFU_Post_Register::JSON_META_KEY, true );
    $doc_data = json_decode($doc_json, true);

    // Retrieve all post meta data for the current post
    $meta_data = get_post_meta( $post_id, '', false );
    
    /* Display document information */
    ?>
        <div class="downloads">
    <?php
   if (($doc_data['usa_default']) && isset($doc_data['eng_usa_url'])) {
    echo '<div class="download-english">';
    echo '<h3>English language file:</h3>';
    echo '<a
        id="usa"
        class="button ifudl active eng-1"
        href="#"
        data-url="'.$doc_data['eng_usa_url'].'"
        target="_blank" >
    Download PDF</a>'; 
    echo '</div>';
   }
   if(($doc_data['ce_and_usa']) && (isset($doc_data['eng_ce_url'])&& isset($doc_data['eng_usa_url']))) {
    echo '<div class="download-english">';
    echo '<h3>English language file:</h3>';
    echo '<h4>USA Customers</h4>';
    echo '<a
        id="usa"
        class="button ifudl active"
        href="#"
        data-url="'.$doc_data['eng_usa_url'].'"
        target="_blank" >
        Download PDF</a>'; 
    echo '<h4>CE Customers</h4>';
    echo '<a
        id="ce"
        class="button ifudl active"
        href="#"
        data-url="'.$doc_data['eng_ce_url'].'"
        target="_blank" >
        Download PDF</a>'; 
    echo '</div>';
   }
   if(($doc_data['use_translations']) && (isset($doc_data['base_translation_url']) && isset($doc_data['supported_languages']))) {
    echo '<div class="download-translation">';
    echo '<h3>Translated File:</h3>'; ?>
    <form action="" method="GET">
        <select name="language" id="langs">
            <option value="">Select Language</option>
<?php
    foreach($doc_data['supported_languages'] as $code => $lang) {
        echo '<option value="'.$code.'">'.$lang.'</option>';
    }
?>
        </select>
        <?php
    
    echo '<a 
        class="button ifudl" 
        href="#"
        data-url="'.$doc_data['base_translation_url'].'"
        target="_blank" 
        id="translation">Download PDF</a>'; ?>
    </form>
    <?php
   
    echo '</div>';
}
    ?>
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
              
        document.getElementById('langs').addEventListener('change', function() {
            const selectedLang = this.value;
            const translationLink = document.getElementById('translation');
            const baseUrl = '<?php echo esc_js($doc_data['base_translation_url']); ?>';
            
            if (selectedLang) {
                translationLink.setAttribute('data-url', baseUrl + '_' + selectedLang + '.pdf');
                translationLink.setAttribute('class', 'button ifudl active');
            } else {
                translationLink.setAttribute('data-url', baseUrl);
                translationLink.setAttribute('class', 'button ifudl');
            }
        });
      });
        const ifudl = document.querySelectorAll('.ifudl');
        ifudl.forEach(function(link) {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                if (this.classList.contains('active')) {
                    const url = this.getAttribute('data-url');
                    window.open(url, '_blank');
                }
                else {
                    alert('Please select a language for the translated file.');
                }
          });
        
    });
    </script>
    <style>
.ifu-doc-wrapper {
  
  .ifu-doc-header h1 {
    &:before {
      font-family: 'bbiconfont';
  content: '\e043';
  display: inline-block;
  margin-right: 1rem;
  color: hsl(207.3, 20.3%, 58.5%);
    }
  }
  
  .downloads {
    margin-bottom:4rem;
    display: grid;
	  grid-template-columns: 1fr;
    grid-gap: 2rem;
	     @media (min-width: 768px) {
        grid-template-columns: 1fr 1fr;
      }
    .download-english, .download-translation  {
      border: 1px solid transparent;
      border-top-color: rgba(0, 0, 0, 0.1);
      border-left-color: rgba(0, 0, 0, 0.1);
      box-shadow: 1px 2px 6px rgba(0, 0, 0, 0.28);
      display: grid;
      align-content: start;
      padding:1rem;
      border-radius:0.5rem;
      grid-template-columns: 1fr;
      grid-gap: 1rem;
      h3{
          color: hsl(207.3, 20.3%, 41.5%);
        font-weight: 500;
      }
      @media (min-width: 768px) {
          grid-template-columns: 1fr 1fr;
       }
      .button.ifudl {
        border-radius:0.5rem;border: 1px solid #ddd;
        background-color: var(--wp--preset--color--light-blue);
        text-align: center;
        color: hsl(207.3, 20.3%, 58.5%);
        &:before {
          font-family: "bbiconfont";
          content: '\e816';
          text-decoration: none;
          display: inline-block;
          margin-right: 0.5rem;
        }
        &:hover {
          border-color: hsl(207.3, 20.3%, 58.5%);
          color: hsl(207.3, 20.3%, 41.5%);
          box-shadow: 1px 1px 4px rgba(0, 0, 0, 0.28);
        }
      }
      .button.ifudl.active.eng-1 {
          grid-column:1/-1;
          margin-inline:20%;
        }
      h3, form {
        grid-column: 1 / -1;
        text-align:center;
      }
      form { 
        display: grid; 
        grid-gap: 1rem;
        grid-template-columns: 1fr;
        grid-gap: 1rem;
        @media (min-width: 768px) {
           grid-template-columns: 1fr 1fr;
        }
        .button.ifudl:hover {
          border-style: dashed;
          border-color: #bbb;
          color:#bbb;
        }
        .button.ifudl.active:hover {
          border: 1px solid hsl(207.3, 20.3%, 41.5%);
          color: hsl(207.3, 20.3%, 41.5%);
        }
 
      }
    }

}}
    </style>
    <?php



    // Display the JSON data
    /*echo '<h4>JSON Document Data:</h4>';
    echo '<pre>';
    print_r($doc_data);
    echo '</pre>';*/
    
    // Display the post meta data using var_dump()
   
    ?>

</div>

	</main><!-- #main -->
</div><!-- primary -->

<?php get_sidebar(); ?>
<?php get_footer(); ?> 