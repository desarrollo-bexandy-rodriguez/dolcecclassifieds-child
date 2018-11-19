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
    // code
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

add_action( 'after_setup_theme', 'include_child_functions');


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

/********************* Update Options Child Theme **************************************/
// flush permalinks after theme activation
function when_child_theme_is_activated() {
    global $theme_version;

    if(!get_option('is_theme_installed')) {
        create_theme_pages();
        update_option('is_theme_installed', 'yes');
        update_option('theme_version', $theme_version);

        // site settings page
        $currency_codes = array(
                // 3 letter code - currency name - symbol - symbol position 1=before, 2=after
                '1' => array('USD', '$', ''),
                '2' => array('EUR', '', '&euro;'),
                '3' => array('GBP', '', '&#163;')
            );
        update_option('currency_codes', $currency_codes);
        update_option('default_currency', '1');
        update_option('show_empty_categories', '0');
        update_option('show_sidebar_category_ad_count', '1');
        update_option('show_sidebar_filterd_ad_count', '2');
        update_option('show_price_sort_sidebar', '1');
        update_option('phone_number_only_for_registered', '1');
        update_option('show_header_language', '2');

        // user/business settings page
        update_option('activate_business_users', '2');

        // ad settings page
        update_option('taxonomy_ad_url', 'item');
        update_option('taxonomy_ad_category_url', 'ads');
        update_option('taxonomy_location_url', 'ads-from');
        update_option('should_ads_expire', '2');
        update_option('when_do_ads_expire', '0');
        update_option('maximum_images_to_upload', '20');
        update_option('max_image_size', '5');
        update_option('manually_approve_ads', '2');
        update_option('ads_per_page', '12');
        update_option('loop_ad_design', '1');

        // email settings page
        update_option('email_settings_sitename', get_bloginfo('name'));
        update_option('email_settings_siteemail', get_bloginfo('admin_email'));
        update_option('notifications_email', get_bloginfo('admin_email'));
        update_option('notifications_email_new_user', '1');
        update_option('notifications_email_new_ad', '1');
        update_option('notifications_email_new_payment', '1');

        // private messages settings page
        update_option('allow_private_messages', '1');
        update_option('private_messages_send_email', '1');
        update_option('private_message_include_message', '2');
        update_option('private_message_allow_images', '1');
        update_option('private_message_maximum_images_to_upload', '50');
        update_option('private_message_max_image_size', '5');

        // form builder - add default form fields
        $form_fields = array(
            'all' => array(
                '1' => array(
                    'input_name' => 'field_name_11',
                    'use_form_for_subcats' => '1',
                    'position' => '1',
                    'parent' => '0',
                    'name' => array('default' => 'Title'),
                    'label_size' => '34',
                    'label_size_unit' => '1',
                    'label_class' => '',
                    'label_help_text' => '',
                    'label_note' => '',
                    'input_type' => '1',
                    'input_values' => '',
                    'input_char_limit' => '500',
                    'input_size' => '100',
                    'input_size_unit' => '1',
                    'input_class' => '',
                    'input_help_text' => '',
                    'input_note' => '',
                    'mandatory' => '1',
                    'group_size' => '100',
                    'group_size_unit' => '1',
                    'separator_after' => '1',
                    'separator_height' => '20',
                    'sortable' => '2',
                    'input_purpose' => '1',
                    'input_ad_page_position' => '1',
                ),
                '2' => array(
                    'input_name' => 'field_name_12',
                    'use_form_for_subcats' => '1',
                    'position' => '2',
                    'parent' => '0',
                    'name' => array('default' => 'Description'),
                    'label_size' => '34',
                    'label_size_unit' => '1',
                    'label_class' => '',
                    'label_help_text' => '',
                    'label_note' => '',
                    'input_type' => '2',
                    'input_values' => '',
                    'input_char_limit' => '3000',
                    'input_size' => '100',
                    'input_size_unit' => '1',
                    'input_class' => '',
                    'input_help_text' => '',
                    'input_note' => '',
                    'mandatory' => '1',
                    'group_size' => '100',
                    'group_size_unit' => '1',
                    'separator_after' => '1',
                    'separator_height' => '20',
                    'sortable' => '2',
                    'input_purpose' => '2',
                    'input_ad_page_position' => '1',
                ),
                '3' => array(
                    'input_name' => 'field_name_13',
                    'use_form_for_subcats' => '1',
                    'position' => '3',
                    'parent' => '0',
                    'name' => array('default' => 'Price'),
                    'label_size' => '34',
                    'label_size_unit' => '1',
                    'label_class' => '',
                    'label_help_text' => '',
                    'label_note' => '',
                    'input_type' => '1',
                    'input_values' => '',
                    'input_char_limit' => '50',
                    'input_size' => '100',
                    'input_size_unit' => '1',
                    'input_class' => '',
                    'input_help_text' => '',
                    'input_note' => '',
                    'mandatory' => '1',
                    'group_size' => '100',
                    'group_size_unit' => '1',
                    'separator_after' => '1',
                    'separator_height' => '20',
                    'sortable' => '2',
                    'input_purpose' => '3',
                    'input_ad_page_position' => '1',
                ),
                '4' => array(
                    'input_name' => 'field_name_17',
                    'use_form_for_subcats' => '1',
                    'position' => '1',
                    'parent' => '3',
                    'input_type' => '1',
                    'input_values' => '',
                    'input_char_limit' => '',
                    'input_size' => '100',
                    'input_size_unit' => '1',
                    'input_class' => '',
                    'input_help_text' => '',
                    'input_note' => '',
                    'mandatory' => '1',
                    'group_size' => '100',
                    'group_size_unit' => '1',
                    'separator_after' => '1',
                    'separator_height' => '20',
                    'sortable' => '2',
                    'input_purpose' => '4',
                    'input_ad_page_position' => '1',
                ),
                '5' => array(
                    'input_name' => 'field_name_14',
                    'use_form_for_subcats' => '1',
                    'position' => '4',
                    'parent' => '0',
                    'name' => array('default' => 'Your address'),
                    'label_size' => '34',
                    'label_size_unit' => '1',
                    'label_class' => '',
                    'label_help_text' => '',
                    'label_note' => '',
                    'input_type' => '1',
                    'input_values' => '',
                    'input_char_limit' => '200',
                    'input_size' => '100',
                    'input_size_unit' => '1',
                    'input_class' => '',
                    'input_help_text' => '',
                    'input_note' => '',
                    'mandatory' => '1',
                    'group_size' => '100',
                    'group_size_unit' => '1',
                    'separator_after' => '1',
                    'separator_height' => '20',
                    'sortable' => '2',
                    'input_purpose' => '6',
                    'input_ad_page_position' => '1',
                ),
                '6' => array(
                    'input_name' => 'field_name_15',
                    'use_form_for_subcats' => '1',
                    'position' => '5',
                    'parent' => '0',
                    'name' => array('default' => 'Phone number'),
                    'label_size' => '34',
                    'label_size_unit' => '1',
                    'label_class' => '',
                    'label_help_text' => '',
                    'label_note' => '',
                    'input_type' => '1',
                    'input_values' => '',
                    'input_char_limit' => '',
                    'input_size' => '100',
                    'input_size_unit' => '1',
                    'input_class' => '',
                    'input_help_text' => '',
                    'input_note' => '',
                    'mandatory' => '2',
                    'group_size' => '100',
                    'group_size_unit' => '1',
                    'separator_after' => '1',
                    'separator_height' => '20',
                    'sortable' => '2',
                    'input_purpose' => '5',
                    'input_ad_page_position' => '1',
                ),
            ),
        );
        update_option('form_builder_form_fields', $form_fields);

        child_restore_default_language();
    }

    update_option('dolce_classifieds_version', $theme_version);

    flush_rewrite_rules();
} // function when_theme_is_activated()
remove_action( 'after_switch_theme', 'when_theme_is_activated' );
add_action( 'after_switch_theme', 'when_child_theme_is_activated', 20 );
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

