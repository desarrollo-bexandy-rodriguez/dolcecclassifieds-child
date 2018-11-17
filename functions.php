<?php
/********************* Include Parent Stylesheet **************************************/
function dolceclassifieds_scripts_styles_child_theme() {
	global $wp_styles;
    $parent_style = 'css'; 
    wp_enqueue_style($parent_style, get_template_directory_uri().'/style.php');
    wp_enqueue_style( 'child-style',
        get_stylesheet_directory_uri() . '/style.css',
        array( $parent_style ),
        wp_get_theme()->get('Version')
    );
}
add_action( 'wp_enqueue_scripts', 'dolceclassifieds_scripts_styles_child_theme' );


/********************* Remove Parent Features **************************************/
function remove_parent_theme_features() {
    //remove_action('after_setup_theme', 'dolce_format_price', 10);
}

add_action( 'after_setup_theme', 'remove_parent_theme_features', 10 );


/********************* Include Child Functions Files **************************************/
function include_child_functions($value='')
{
   include 'child-language.php';
   include 'child-functions-payments.php';
   include 'child-functions-cron-jobs.php';
   include 'child-functions-validate-settings.php';
}

add_action( 'after_setup_theme', 'include_child_functions', $priority = 10, $accepted_args = 1 );


/********************* Include Child Styles and Scripts **************************************/
function my_enqueue_assets() {
	global $wp_query;
    wp_enqueue_script('jquery');
    wp_enqueue_script('jquery-ui-core');
    wp_enqueue_script('jquery-ui-datepicker');
    wp_enqueue_script('jquery-touch-punch');
    wp_enqueue_style('jquery-ui-css', 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.2/themes/smoothness/jquery-ui.css');
    wp_enqueue_style( 'parent-style', get_template_directory_uri().'/style.css' );
    wp_enqueue_script( 'ajax-pagination',  get_stylesheet_directory_uri() . '/js/ajax-pagination.js',array( 'jquery' ), '1.0', true );
    wp_enqueue_script( 'range-slider',  get_stylesheet_directory_uri() . '/js/range-slider.js',array( 'jquery-ui-slider' ), '1.0', true );
	wp_localize_script( 'ajax-pagination', 'ajaxpagination', array(
    	'ajaxurl' => admin_url( 'admin-ajax.php' ),
    	'query_vars' => json_encode( $wp_query->query )
    ));

    wp_enqueue_script( 'html5gallery',  get_stylesheet_directory_uri() . '/html5gallery/html5gallery.js',array( 'jquery' ), '1.6', true );
    wp_enqueue_script('child-post-new-ad', get_stylesheet_directory_uri().'/js/child-post-new-ad.js', array(), '', true );
        // files for the "Post your ad" page
    if(get_the_ID() == get_option('post_new_ad')) {
        
    }
    wp_enqueue_style('child-icon-font', get_stylesheet_directory_uri().'/icon-font/child-icon-font.css');

    wp_enqueue_style( 'jsImgSliderCss', get_stylesheet_directory_uri().'/assets/jsImgSlider/js-image-slider.css' );
    wp_enqueue_script( 'jsImgSliderJs',  get_stylesheet_directory_uri() . '/assets/jsImgSlider/js-image-slider.js' );
    wp_enqueue_script( 'mcVideoPluginJs',  get_stylesheet_directory_uri() . '/assets/jsImgSlider/mcVideoPlugin.js' );

    wp_enqueue_script( 'placeholders-traduction',  get_stylesheet_directory_uri() . '/js/placeholders-traduction.js');
}

add_action( 'wp_enqueue_scripts', 'my_enqueue_assets' );


/********************* Include Ajax Query Vars for pagination **************************************/
function my_ajax_pagination() {
    $query_vars = json_decode( stripslashes( $_POST['query_vars'] ), true );

    $query_vars['paged'] = $_POST['page'];


    $posts = new WP_Query( $query_vars );
    $GLOBALS['wp_query'] = $posts;

  
    if( ! $posts->have_posts() ) { 
        get_template_part( 'content', 'none' );
    }
    else {
        while ( $posts->have_posts() ) { 
            $posts->the_post();
            get_template_part( 'content', get_post_format() );
        }
    }

    die();
}
add_action( 'wp_ajax_nopriv_ajax_pagination', 'my_ajax_pagination' );
add_action( 'wp_ajax_ajax_pagination', 'my_ajax_pagination' );

/********************* Define Custom image size for **************************************/
function my_image_size_override() {
    return array( 825, 510 );
}

/********************* Include Ajax Vars for filters **************************************/
function my_ajax_filter() {

        get_template_part( 'content', 'none' );
        wp_head();


    die();
}
add_action( 'wp_ajax_nopriv_ajax_filter', 'my_ajax_filter' );
add_action( 'wp_ajax_ajax_filter', 'my_ajax_filter' );

/********************* Define Video options **************************************/
update_option('maximum_videos_to_upload', '2');
update_option('max_video_size', '10');

/********************* Override Parent Global Variables **************************************/
function override_parent_global_variables()
{
    global $payment_duration_types, $taxonomy_ad_url;
    $payment_duration_types = array(
            '6' => array('Minutes', 'Minute', 'C', 'minute'),
            '5' => array('Hours', 'Hour', 'H', 'hour')
        )+$payment_duration_types ;
    $taxonomy_ad_url = $taxonomy_ad_url;
}
add_action( 'after_setup_theme', 'override_parent_global_variables' ); 

/********************* Replace Parent Scripts with Child Scripts **************************************/
function child_dequeue_script() {
    wp_dequeue_script( 'dolcejs' );
    wp_dequeue_script( 'responsivejs' );
}

add_action( 'wp_print_scripts', 'child_dequeue_script', 100 );

function child_add_js_css() {
    wp_enqueue_script('child-responsivejs', get_stylesheet_directory_uri().'/js/child-responsive.js', array( 'jquery' ));
    wp_enqueue_script('child-dolcejs', get_stylesheet_directory_uri().'/js/child-dolceclassifieds.js', array( 'jquery' ));
    wp_localize_script('child-dolcejs', 'wpvars', array( 
        'wpthemeurl' => get_template_directory_uri(), 
        'wpchildthemeurl' => get_stylesheet_directory_uri() 
    ));
}

add_action('wp_enqueue_scripts', 'child_add_js_css');

/********************* Define custom whatsapp shortcode **************************************/
function child_shortcode_whatsapp_chat( $atts, $content = null ) {
    extract(shortcode_atts(array(
        'phone'      => '#',
        'blank'     => 'false'
    ), $atts));

    $blank_link = '';

    if ( $blank == 'true' )
        $blank_link = "target=\"_blank\"";

    $target='';

    if (wp_is_mobile() ) {
        $target='api';
    } else {
        $target='web';
    }

    $out = "<div class=\"child-whatsapp\"><a class=\"awhatsapp\" href=\"https://".$target.".whatsapp.com/send?phone=" .$phone. "\"" .$blank_link."><span>" .do_shortcode($content). "</span></a></div>";

    return $out;
}

add_shortcode('childwhatsapp', 'child_shortcode_whatsapp_chat');

