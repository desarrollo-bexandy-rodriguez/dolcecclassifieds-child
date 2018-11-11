<?php
function dolceclassifieds_scripts_styles_child_theme() {
		global $wp_styles;
			wp_enqueue_style( 'dolceclassifieds-parent', get_template_directory_uri() . '/style.css');
}
//add_action( 'wp_enqueue_scripts', 'dolceclassifieds_scripts_styles_child_theme' );
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

add_action( 'wp_ajax_nopriv_ajax_pagination', 'my_ajax_pagination' );
add_action( 'wp_ajax_ajax_pagination', 'my_ajax_pagination' );

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

function my_image_size_override() {
    return array( 825, 510 );
}

add_action( 'wp_ajax_nopriv_ajax_filter', 'my_ajax_filter' );
add_action( 'wp_ajax_ajax_filter', 'my_ajax_filter' );

function my_ajax_filter() {

        get_template_part( 'content', 'none' );
        wp_head();


    die();
}

update_option('maximum_videos_to_upload', '2');
update_option('max_video_size', '10');

function child_dequeue_script() {
    wp_dequeue_script( 'dolcejs' );
    wp_dequeue_script( 'responsivejs' );
}
add_action( 'wp_print_scripts', 'child_dequeue_script', 100 );

function child_add_js_css() {
    wp_enqueue_script('child-responsivejs', get_stylesheet_directory_uri().'/js/child-responsive.js', array( 'jquery' ));
    wp_enqueue_script('child-dolcejs', get_stylesheet_directory_uri().'/js/child-dolceclassifieds.js', array( 'jquery' ));
    wp_localize_script('child-dolcejs', 'wpvars', array( 'wpthemeurl' => get_template_directory_uri() ));
    wp_localize_script('child-dolcejs', 'wpvars', array( 'wpchildthemeurl' => get_stylesheet_directory_uri() ));
    wp_localize_script('dolcejs', 'wpvars', array( 'wpchildthemeurl' => get_stylesheet_directory_uri() ));
}
add_action('wp_enqueue_scripts', 'child_add_js_css');

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

function child_validate_settings_form($action, $form_data='') {
    $current_user = wp_get_current_user();

    if(!is_user_logged_in()) die(_d('You must be logged in!',418));

    $action = preg_replace("/([^a-zA-Z0-9-_])/", "", $action);
    if($form_data) {
        if($action == "edit-payment-settings") {
            $activate_payments = false;
            $activate_recurring = false;
            foreach ($form_data as $field) {
                if(strpos($field['name'], "[") === false) {
                    $form[$field['name']] = trim($field['value']);
                } else {
                    preg_match_all("/\[([^\]]*)\]/", $field['name'], $matches);
                    $user_type = preg_replace("/([^a-z])/", "", strtolower($matches[1][0]));
                    $plan_id = preg_replace("/([^a-z])/", "", strtolower($matches[1][1]));
                    $field_name = str_replace(array("[".$user_type."]", "[".$plan_id."]"), "", $field['name']);
                    $field_payment_type = str_replace("_", "", strrchr($field_name, '_'));
                    $new_field_name = str_replace("_".$field_payment_type, "", $field_name)."_data";
                    $value = substr(sanitize_text_field((int)$field['value']), 0 , 100);
                    $value = $value == "0" ? "" : $value;
                    if($plan_id) {
                        $form[$new_field_name][$user_type][$plan_id][$field_payment_type] = $value;
                    } else {
                        $form[$new_field_name][$user_type][$field_payment_type] = $value;
                    }

                    switch ($field_payment_type) {
                        case 'price':
                            if($value > "0") {
                                $activate_payments = true;
                            }
                            break;

                        case 'recurring':
                            if($value == "1") {
                                $activate_recurring = true;
                            }
                            break;
                    }
                }
            }
        } else {
            foreach ($form_data as $field) {
                if(strpos($field['name'], "[]") === false) {
                    $form[$field['name']] = trim($field['value']);
                } else {
                    $form[str_replace("[]", "", $field['name'])][] = trim($field['value']);
                }
            }
        }
        // extra formatting of the data for the payment settings admin page
    }

    if(defined('dolce_demo_theme') && in_array($action, array('edit-payment-settings', 'edit-private-messages-settings'))) {
        die(json_encode(array('status' => 'ok', 'form_ok' => _d('Settings saved',419))));
    }

    switch ($action) {
        case 'edit-site-settings':
            if(!current_user_can('level_10')) die(_d('This is only for admins!',420));

            foreach ($form as $key => $value) {
                switch ($key) {
                    case 'site_language':
                        update_option('site_language', preg_replace("/([^a-z])/", "", strtolower($value)));
                        break;

                    case 'site_title':
                        update_option('blogname', substr(sanitize_text_field($value), 0 , 200));
                        break;

                    case 'site_description':
                        update_option('blogdescription', substr(sanitize_text_field($value), 0 , 300));
                        break;

                    case 'site_language':
                        update_option('site_language', substr(preg_replace("/([^a-zA-Z0-9 _-])/", "", sanitize_text_field($value)), 0 , 100));
                        break;

                    case 'c_code':
                        foreach ($value as $key => $currency) {
                            $currency_codes[sanitize_text_field($form['key'][$key])] = array(
                                                                        sanitize_text_field($currency),
                                                                        sanitize_text_field($form['c_before'][$key]),
                                                                        sanitize_text_field($form['c_after'][$key])
                                                                    );
                        }
                        unset($currency_codes['last_key']);
                        if(count($currency_codes) > 0) {
                            update_option('currency_codes', $currency_codes);
                        } else {
                            $err = _d('You need to add at least one currency',421);
                            die(json_encode(array('status' => 'err', 'form_err' => $err)));
                        }
                        break;

                    case 'default_currency':
                        update_option('default_currency', substr(sanitize_text_field($value), 0 , 300));
                        break;

                    case 'allow_fb_login':
                        if(!defined('dolce_demo_theme')) {
                            update_option('allow_fb_login', (int)$value);
                        }
                        break;

                    case 'fb_app_id':
                        if(!defined('dolce_demo_theme')) {
                            update_option('fb_app_id', substr(sanitize_text_field($value), 0 , 200));
                        }
                        break;

                    case 'fb_app_secret':
                        if(!defined('dolce_demo_theme')) {
                            update_option('fb_app_secret', substr(sanitize_text_field($value), 0 , 200));
                        }
                        break;

                    case 'allow_g_login':
                        if(!defined('dolce_demo_theme')) {
                            update_option('allow_g_login', (int)$value);
                        }
                        break;

                    case 'g_client_id':
                        if(!defined('dolce_demo_theme')) {
                            update_option('g_client_id', substr(sanitize_text_field($value), 0 , 200));
                        }
                        break;

                    case 'g_client_secret':
                        if(!defined('dolce_demo_theme')) {
                            update_option('g_client_secret', substr(sanitize_text_field($value), 0 , 200));
                        }
                        break;

                    case 'google_maps_api':
                        if(!defined('dolce_demo_theme')) {
                            update_option('google_maps_api', substr(sanitize_text_field($value), 0 , 200));
                        }
                        break;

                    case 'show_empty_categories':
                        update_option('show_empty_categories', (int)$value);
                        break;

                    case 'show_sidebar_category_ad_count':
                        update_option('show_sidebar_category_ad_count', (int)$value);
                        break;

                    case 'sidebar_filters_show_post_count':
                        update_option('sidebar_filters_show_post_count', (int)$value);
                        break;

                    case 'show_price_sort_sidebar':
                        update_option('show_price_sort_sidebar', (int)$value);
                        break;

                    case 'phone_number_only_for_registered':
                        update_option('phone_number_only_for_registered', (int)$value);
                        break;

                    case 'show_header_language':
                        update_option('show_header_language', (int)$value);
                        break;

                    case 'tos_reg_page_id':
                        update_option('tos_reg_page_id', (int)$value);
                        break;

                    case 'tos_ad_page_id':
                        update_option('tos_ad_page_id', (int)$value);
                        break;
                } // switch between fields
            } // foreach $form
            die(json_encode(array('status' => 'ok', 'form_ok' => _d('Settings saved',419))));
            break; // edit site settings

        case 'edit-ad-settings':
            if(!current_user_can('level_10')) die(_d('This is only for admins!',420));
            global $wpdb;
            if($form['taxonomy_ad_url'] == $form['taxonomy_ad_category_url']) {
                $err['taxonomy_ad_url'] = _d('The ad url can\'t be the same as the category url',422);
            }
            if($form['taxonomy_ad_url'] == $form['taxonomy_location_url']) {
                $err['taxonomy_ad_url'] = _d('The ad url can\'t be the same as the location url',423);
            }
            if($form['taxonomy_ad_category_url'] == $form['taxonomy_location_url']) {
                $err['taxonomy_ad_category_url'] = _d('The category url can\'t be the same as the location url',424);
            }

            if($err) {
                die(json_encode(array('status' => 'err', 'fields_err' => $err)));
            }

            foreach ($form as $key => $value) {
                switch ($key) {
                    case 'taxonomy_ad_url':
                        $value = substr(sanitize_text_field($value), 0 , 200);
                        if(!$value) {
                            $err['taxonomy_ad_url'] = _d('This field can\'t be empty',425);
                        } else {
                            if(get_option('taxonomy_ad_url') && get_option('taxonomy_ad_url') != stripslashes($value)) {
                                $wpdb->query($wpdb->prepare("UPDATE $wpdb->posts SET `post_type` = '%s' WHERE `post_type` = '%s'", stripslashes($value), get_option('taxonomy_ad_url')));
                            }
                            update_option('taxonomy_ad_url', stripslashes($value));
                        }
                        break;

                    case 'taxonomy_ad_category_url':
                        $value = substr(sanitize_text_field($value), 0 , 200);
                        if(!$value) {
                            $err['taxonomy_ad_category_url'] = _d('This field can\'t be empty',425);
                        } else {
                            if(get_option('taxonomy_ad_category_url') && get_option('taxonomy_ad_category_url') != stripslashes($value)) {
                                $wpdb->query($wpdb->prepare("UPDATE $wpdb->term_taxonomy SET `taxonomy` = '%s' WHERE `taxonomy` = '%s'", stripslashes($value), get_option("taxonomy_ad_category_url")));
                                $wpdb->query($wpdb->prepare("UPDATE $wpdb->options SET `option_name` = '%s' WHERE `option_name` = '%s'", stripslashes($value)."_children", get_option("taxonomy_ad_category_url")."_children"));
                            }
                            update_option('taxonomy_ad_category_url', $value);
                        }
                        break;

                    case 'taxonomy_location_url':
                        $value = substr(sanitize_text_field($value), 0 , 200);
                        if(!$value) {
                            $err['taxonomy_location_url'] = _d('This field can\'t be empty',425);
                        } else {
                            if(get_option('taxonomy_location_url') && get_option('taxonomy_location_url') != stripslashes($value)) {
                                $wpdb->query($wpdb->prepare("UPDATE $wpdb->term_taxonomy SET `taxonomy` = '%s' WHERE `taxonomy` = '%s'", stripslashes($value), get_option("taxonomy_location_url")));
                                $wpdb->query($wpdb->prepare("UPDATE $wpdb->options SET `option_name` = '%s' WHERE `option_name` = '%s'", stripslashes($value)."_children", get_option("taxonomy_location_url")."_children"));
                            }
                            update_option('taxonomy_location_url', $value);
                        }
                        break;

                    case 'should_ads_expire':
                        update_option('should_ads_expire', (int)$value);
                        break;

                    case 'when_do_ads_expire':
                        update_option('when_do_ads_expire', (int)$value);
                        break;

                    case 'expired_ads_notice':
                        update_option('expired_ads_notice', (int)$value);
                        break;

                    case 'send_email_ad_expired':
                        update_option('send_email_ad_expired', (int)$value);
                        break;

                    case 'expired_ads_action':
                        update_option('expired_ads_action', (int)$value);
                        break;

                    case 'maximum_images_to_upload':
                        if($value < 1) {
                            $err['maximum_images_to_upload'] = _d('You need to allow at least one image',426);
                        } else {
                            update_option('maximum_images_to_upload', (int)$value);
                        }
                        break;

                    case 'max_image_size':
                        if($value < 1) {
                            $err['max_image_size'] = _d('The images need to be at least 1MB in size',427);
                        } else {
                            update_option('max_image_size', (int)$value);
                        }
                        break;

                    case 'manually_approve_ads':
                        update_option('manually_approve_ads', (int)$value);
                        break;

                    case 'ads_per_page':
                        $value = $value ? (int)$value : '12';
                        update_option('ads_per_page', $value);
                        break;

                    case 'loop_ad_design':
                        $value = $value ? (int)$value : '1';
                        update_option('loop_ad_design', $value);
                        break;

                    case 'ads_have_ids_personal':
                        update_option('ads_have_ids_personal', (int)$value);
                        break;

                    case 'ads_have_ids_business':
                        update_option('ads_have_ids_business', (int)$value);
                        break;
                } // switch between fields
            } // foreach $form
            if($err) {
                die(json_encode(array('status' => 'err', 'fields_err' => $err)));
            } else {
                update_option('flush_rewrite_rules', 'yes');
                die(json_encode(array('status' => 'ok', 'form_ok' => _d('Settings saved',419))));
            }
            break; // edit-ad-settings

        case 'edit-user-settings':
            if(defined('dolce_demo_theme')) die();
            if(!current_user_can('level_10')) die(_d('This is only for admins!',420));

            foreach ($form as $key => $value) {
                switch ($key) {
                    case 'activate_business_users':
                        update_option('activate_business_users', (int)$value);
                        break;
                } // switch between fields
            } // foreach $form
            if($err) {
                die(json_encode(array('status' => 'err', 'fields_err' => $err)));
            } else {
                die(json_encode(array('status' => 'ok', 'form_ok' => _d('Settings saved',419))));
            }
            break; // edit-user-settings

        case 'edit-email-settings':
            if(!current_user_can('level_10')) die('This is only for admins!');

            foreach ($form as $key => $value) {
                switch ($key) {
                    case 'email_settings_sitename':
                        $value = substr(sanitize_text_field($value), 0 , 200);
                        if(!$value) {
                            $err['email_settings_sitename'] = _d('This field can\'t be empty',425);
                        } else {
                            update_option('email_settings_sitename', stripslashes($value));
                        }
                        break;

                    case 'email_settings_siteemail':
                        $value = substr(sanitize_text_field($value), 0 , 200);
                        if(!$value) {
                            $err['email_settings_siteemail'] = _d('This field can\'t be empty',425);
                        } else {
                            update_option('email_settings_siteemail', stripslashes($value));
                        }
                        break;

                    case 'email_settings_emailsignature':
                        global $allowed_html_in_post_ad_textarea_field;
                        $value = wp_kses(wp_rel_nofollow(make_clickable(force_balance_tags($value))), $allowed_html_in_post_ad_textarea_field);
                        update_option('email_settings_emailsignature', stripslashes($value));
                        break;

                    case 'notifications_email':
                        $value = substr(sanitize_text_field($value), 0 , 200);
                        if(!$value) {
                            $err['notifications_email'] = _d('This field can\'t be empty',425);
                        } else {
                            update_option('notifications_email', stripslashes($value));
                        }
                        break;

                    case 'notifications_email_new_user':
                        update_option('notifications_email_new_user', (int)$value);
                        break;

                    case 'notifications_email_new_ad':
                        update_option('notifications_email_new_ad', (int)$value);
                        break;

                    case 'notifications_email_new_payment':
                        update_option('notifications_email_new_payment', (int)$value);
                        break;
                } // switch between fields
            } // foreach $form
            if($err) {
                die(json_encode(array('status' => 'err', 'fields_err' => $err)));
            } else {
                die(json_encode(array('status' => 'ok', 'form_ok' => _d('Settings saved',419))));
            }
            break; // edit-email-settings

        case 'edit-payment-settings':
            if(defined('dolce_demo_theme')) die();
            if(!current_user_can('level_10')) die('This is only for admins!');

            foreach ($form as $key => $value) {
                switch ($key) {
                    case 'payment_mycred':
                        update_option('payment_mycred', (int)$value);
                        break;
                    case 'payment_paypal':
                        update_option('payment_paypal', (int)$value);
                        break;

                    case 'payment_paypal_sandbox':
                        update_option('payment_paypal_sandbox', (int)$value);
                        break;

                    case 'payment_paypal_sandbox_address':
                        $value = substr(sanitize_text_field($value), 0 , 200);
                        update_option('payment_paypal_sandbox_address', stripslashes($value));
                        break;

                    case 'payment_paypal_address':
                        $value = substr(sanitize_text_field($value), 0 , 200);
                        update_option('payment_paypal_address', stripslashes($value));
                        break;

                    case 'payment_stripe':
                        update_option('payment_stripe', (int)$value);
                        break;

                    case 'payment_stripe_rememberme':
                        update_option('payment_stripe_rememberme', (int)$value);
                        break;

                    case 'payment_stripe_sandbox':
                        update_option('payment_stripe_sandbox', (int)$value);
                        break;

                    case 'payment_stripe_live_secret_key':
                        $value = substr(sanitize_text_field($value), 0 , 500);
                        update_option('payment_stripe_live_secret_key', stripslashes($value));
                        break;

                    case 'payment_stripe_live_publishable_key':
                        $value = substr(sanitize_text_field($value), 0 , 500);
                        update_option('payment_stripe_live_publishable_key', stripslashes($value));
                        break;

                    case 'payment_stripe_test_secret_key':
                        $value = substr(sanitize_text_field($value), 0 , 500);
                        update_option('payment_stripe_test_secret_key', stripslashes($value));
                        break;

                    case 'payment_stripe_test_publishable_key':
                        $value = substr(sanitize_text_field($value), 0 , 500);
                        update_option('payment_stripe_test_publishable_key', stripslashes($value));
                        break;

                    case 'payment_currency':
                        $value = strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $value), 0 , 3));
                        if(!$value) {
                            $err['payment_currency'] = _d('This field can\'t be empty',425);
                        } else {
                            update_option('payment_currency', $value);
                        }
                        break;

                    case 'payment_currency_symbol_before':
                        $value = substr(sanitize_text_field($value), 0 , 10);
                        update_option('payment_currency_symbol_before', stripslashes($value));
                        break;

                    case 'payment_currency_symbol_after':
                        $value = substr(sanitize_text_field($value), 0 , 10);
                        update_option('payment_currency_symbol_after', stripslashes($value));
                        break;

                    case 'payment_user_reg_data':
                        update_option('payment_user_reg_data', $value);
                        break;

                    case 'payment_paid_ads_data':
                        update_option('payment_paid_ads_data', $value);
                        break;

                    case 'payment_always_on_top_data':
                        update_option('payment_always_on_top_data', $value);
                        break;

                    case 'payment_highlighted_ad_data':
                        update_option('payment_highlighted_ad_data', $value);
                        break;

                    case 'payment_push_data':
                        $payment_push_time_hour = $value['time']['timehour'] < 10 ? "0".$value['time']['timehour'] : $value['time']['timehour'];
                        $payment_push_time_minutes = $value['time']['timeminutes'] < 10 ? "0".$value['time']['timeminutes'] : $value['time']['timeminutes'];
                        update_option('date_to_push_ads', date("Ymd".$payment_push_time_hour.$payment_push_time_minutes, current_time('timestamp')));
                        update_option('payment_push_data', $value);
                        break;
                } // switch between fields
            } // foreach $form

            // if PayPal is activated make sure the email address is present
            if(get_option('payment_paypal') == "1" && !get_option('payment_paypal_address')) {
                $err['payment_paypal_address'] = _d('You need to write your PayPal email address',431);
                update_option('payment_paypal', '2');
            }

            // if PayPal Sandbox is activated make sure the sandbox email address is present
            if(get_option('payment_paypal') == "1" && get_option('payment_paypal_sandbox') == "1" && !get_option('payment_paypal_sandbox_address')) {
                $err['payment_paypal_sandbox_address'] = _d('You need to write your PayPal Sandbox email address',432);
                update_option('payment_paypal_sandbox', '2');
            }

            // if STRIPE is activated make sure the LIVE secret key and the publishable key are inputed
            if(get_option('payment_stripe') == "1" && (!get_option('payment_stripe_live_secret_key') || !get_option('payment_stripe_live_publishable_key'))) {
                $err['payment_stripe_live_secret_key'] = _d('You need to write your LIVE keys',433);
                update_option('payment_stripe', '2');
            }

            // if STRIPE sandbox is activated then make sure the TEST secret key and the publishable key are inputed
            if(get_option('payment_stripe') == "1" && get_option('payment_stripe_sandbox') == "1" && (!get_option('payment_stripe_test_secret_key') || !get_option('payment_stripe_test_publishable_key'))) {
                $err['payment_stripe_test_secret_key'] = _d('You need to write your TEST keys',434);
                update_option('payment_stripe_sandbox', '2');
            }

            if((get_option('payment_paypal') == "1" || get_option('payment_stripe') == "1") && $activate_payments) {
                update_option('payment_mode_active', '1');
            } else {
                delete_option('payment_mode_active');
            }


            // create the subscription plans for the theme
            // if STRIPE is activated and at least one plan is a subscription
            if(get_option('payment_stripe') == "1" && $activate_recurring) {
                if(get_option('payment_stripe_sandbox')) {
                    $payment_stripe_secret_key = get_option('payment_stripe_test_secret_key');
                    $payment_stripe_publishable_key = get_option('payment_stripe_test_publishable_key');
                } else {
                    $payment_stripe_secret_key = get_option('payment_stripe_live_secret_key');
                    $payment_stripe_publishable_key = get_option('payment_stripe_live_publishable_key');
                }

                require_once(get_template_directory().'/APIs/stripe/stripe-php-4.13.0/init.php');
                \Stripe\Stripe::setApiKey($payment_stripe_secret_key);

                $site_url = parse_url(home_url());
                $host_domain = $site_url['host'];
                $payment_currency = strtolower(get_option('payment_currency'));
                $payment_data = get_all_payment_data();
                global $payment_duration_types;
                // ad posting fee
                $payment_types = array(
                        'user_reg' => _d('Registration fee',839),
                        'paid_ads' => _d('Ad posting fee',435),
                        'always_on_top' => _d('Always on top',240),
                        'highlighted_ad' => _d('Highlighted ad',436),
                        'push' => _d('Push to top',437)
                    );
                foreach ($payment_types as $payment_name => $title) {
                    foreach ($payment_data[$payment_name] as $user_type => $plan) {
                        if($plan['first']['price'] > 0 && $plan['first']['recurring'] == "1") {
                            $title = ($payment_name == "user_reg" && $user_type == "business") ? $title." - ".strtoupper(_d('Business',836)) : $title;
                            $stripe_plans[] = array(
                                    's_name' => $title,
                                    's_id' => $host_domain."_".$payment_name."_".$user_type,
                                    's_amount' => ($plan['first']['price'] * 100),
                                    's_duration' => $plan['first']['duration'],
                                    's_interval' => $payment_duration_types[$plan['first']['durationtype']]['3'],
                                    's_vars' => $plan['first']['price'].$payment_currency.$plan['first']['duration'].$plan['first']['durationtype'],
                                );
                        }
                    } // foreach ($payment_data[$payment_name] as $user_type => $plan)
                } // foreach ($payment_types as $payment_name => $title)

                if(count($stripe_plans) > 0)  {
                    foreach ($stripe_plans as $key => $array_plan) {
                        // RETRIEVE plan from STRIPE
                        unset($plan);
                        try { $plan = \Stripe\Plan::retrieve($array_plan['s_id']); } catch (Exception $e) {}

                        // if the plan already exists at STRIPE
                        if($plan) {
                            $create_plan = "no";
                            $stripe_plan = $plan->__toArray($recursive=true);
                            // if the plan from STRIPE has different payment variables then we delete the old plan and create a new one
                            if($stripe_plan['metadata']['vars'] != $payment_plan_vars) {
                                // DELETE plan from STRIPE
                                try { $plan->delete(); } catch (Exception $e) {}
                                $create_plan = "yes";
                            }
                        } else {
                            $create_plan = "yes";
                        }

                        if($create_plan == "yes") {
                            // CREATE plan on STRIPE
                            try {
                                \Stripe\Plan::create(array(
                                    "id" => $array_plan['s_id'],
                                    "amount" => $array_plan['s_amount'],
                                    "currency" => $payment_currency,
                                    "interval" => $array_plan['s_interval'], // day, week, month or year
                                    "interval_count" => $array_plan['s_duration'], // Maximum of one year interval allowed (1 year, 12 months, or 52 weeks)
                                    "name" => $array_plan['s_name'],
                                    "metadata" => array('vars' => $array_plan['s_vars']),
                                ));
                            } catch (Exception $e) {}
                        } // if($create_plan == "yes") {
                    } // foreach $stripe_plans
                } // if(count($stripe_plans) > 0)  {
            } // if no error

            if($err) {
                die(json_encode(array('status' => 'err', 'fields_err' => $err)));
            } else {
                die(json_encode(array('status' => 'ok', 'form_ok' => _d('Settings saved',419))));
            }
            break; // edit-payment-settings

        case 'edit-private-messages-settings':
            if(defined('dolce_demo_theme')) die();
            if(!current_user_can('level_10')) die('This is only for admins!');

            foreach ($form as $key => $value) {
                switch ($key) {
                    case 'allow_private_messages':
                        update_option('allow_private_messages', (int)$value);
                        break;

                    case 'private_messages_send_email':
                        update_option('private_messages_send_email', (int)$value);
                        break;

                    case 'private_message_include_message':
                        update_option('private_message_include_message', (int)$value);
                        break;

                    case 'private_message_allow_images':
                        update_option('private_message_allow_images', (int)$value);
                        break;

                    case 'private_message_maximum_images_to_upload':
                        update_option('private_message_maximum_images_to_upload', (int)$value);
                        break;

                    case 'private_message_max_image_size':
                        update_option('private_message_max_image_size', (int)$value);
                        break;
                } // switch between fields
            } // foreach $form
            die(json_encode(array('status' => 'ok', 'form_ok' => _d('Settings saved',419))));
            break; // edit-private-messages-settings

        case 'edit-auto-class-settings':
            if(!current_user_can('level_10')) die(_d('This is only for admins!',420));

            foreach ($form as $key => $value) {
                switch ($key) {
                    case 'activate_business_users':
                        update_option('activate_business_users', (int)$value);
                        break;
                } // switch between fields
            } // foreach $form
            if($err) {
                die(json_encode(array('status' => 'err', 'fields_err' => $err)));
            } else {
                die(json_encode(array('status' => 'ok', 'form_ok' => _d('Settings saved',419))));
            }
            break; // edit-auto-class-settings

        case 'add-upgrade':
            if(!current_user_can('level_10')) die('This is only for admins!');

            foreach ($form as $key => $value) {
                switch ($key) {
                    case 'upgrade_id':
                        $upgrade_id = (int)$value;
                        break;

                    case 'upgrade_duration':
                        $upgrade_duration = (int)$value;
                        break;

                    case 'post_id':
                        $post_id = (int)$value;
                        break;
                } // switch between fields
            } // foreach $form

            /* $upgrade_id legend
            1 - Ad posting fee
            2 - Always on top
            3 - Highlighted ad
            4 - Push ad
            */
            switch ($upgrade_id) {
                case '1': // Ad posting fee
                    $post_meta_names = array('fee' => 'ad_posting_fee', 'expiration' => 'ad_posting_fee_expiration');
                    $update_ad['ID'] = $post_id;
                    $update_ad['post_status'] = 'publish';
                    wp_update_post($update_ad);
                    delete_post_meta($post_id, 'needs_payment');
                    delete_post_meta($post_id, 'needs_activation');
                    delete_post_meta($post_id, 'expired');
                    break;

                case '2': // Always on top
                    $post_meta_names = array('fee' => 'always_on_top', 'expiration' => 'always_on_top_expiration');
                    break;

                case '3': // Highlighted ad
                    $post_meta_names = array('fee' => 'highlighted_ad', 'expiration' => 'highlighted_ad_expiration');
                    break;

                case '4': // Push ad
                    $post_meta_names = array('fee' => 'push_ad', 'expiration' => 'push_ad_expiration');
                    break;
            }

            update_post_meta($post_id, $post_meta_names['fee'], '1');
            if($upgrade_duration == "0") {
                delete_post_meta($post_id, $post_meta_names['expiration']);
            } else {
                $expiration = get_post_meta($post_id, $post_meta_names['expiration'], true);
                if($expiration > current_time('timestamp')) {
                    $expiration = $expiration + ($upgrade_duration * 86400);
                } else {
                    $expiration = current_time('timestamp') + $upgrade_duration * 86400;
                }
                update_post_meta($post_id, $post_meta_names['expiration'], $expiration);
            }

            $msg = _d('purchased',460);
            if(get_post_meta($post_id, $post_meta_names['expiration'], true)) {
                $expiration_seconds = $expiration - current_time('timestamp');
                if($expiration_seconds > 0) {
                    // still active
                    $msg .= ' <span>-</span> '._d('expires in',462).' '.secondsToTime($expiration_seconds);
                }
            }

            die(json_encode(array('status' => 'ok', 'form_ok' => _d('Settings saved',419), 'msg' => $msg)));
            break; // add-upgrade

        case 'remove-upgrade':
            if(!current_user_can('level_10')) die('This is only for admins!');

            foreach ($form as $key => $value) {
                switch ($key) {
                    case 'upgrade_id':
                        $upgrade_id = (int)$value;
                        break;

                    case 'upgrade_duration':
                        $upgrade_duration = (int)$value;
                        break;

                    case 'post_id':
                        $post_id = (int)$value;
                        break;
                } // switch between fields
            } // foreach $form

            switch ($upgrade_id) {
                case '1': // Ad posting fee
                    $post_meta_names = array('fee' => 'ad_posting_fee', 'expiration' => 'ad_posting_fee_expiration');
                    break;

                case '2': // Always on top
                    $post_meta_names = array('fee' => 'always_on_top', 'expiration' => 'always_on_top_expiration');
                    break;

                case '3': // Highlighted ad
                    $post_meta_names = array('fee' => 'highlighted_ad', 'expiration' => 'highlighted_ad_expiration');
                    break;

                case '4': // Push ad
                    $post_meta_names = array('fee' => 'push_ad', 'expiration' => 'push_ad_expiration');
                    break;
            }
            delete_post_meta($post_id, $post_meta_names['fee']);
            delete_post_meta($post_id, $post_meta_names['expiration']);
            $payment_data = get_all_payment_data();
            $post_author_id = get_post_field('post_author', $post_id);
            $user_type = get_user_meta($post_author_id, 'user_type', true) ? get_user_meta($post_author_id, 'user_type', true) : "personal";
            if(get_option('payment_mode_active') && $payment_data['paid_ads'][$user_type]['first']['price']) {
                update_post_meta($post_id, 'needs_payment', "1");
                wp_update_post(array('ID' => $post_id, 'post_status' => 'private'));
            }

            die(json_encode(array('status' => 'ok', 'form_ok' => _d('Settings saved',419))));
            break; // remove-upgrade

        case 'cancel-stripe-subscription':
            foreach ($form as $key => $value) {
                switch ($key) {
                    case 'item_id':
                        $item_id = (int)$value;
                        if(!$item_id)
                            die(json_encode(array('status' => 'err', 'err_msg' => _d('We need the item ID!',438))));
                        break;

                    case 'post_id':
                        $post_id = (int)$value;
                        if(!$post_id) {
                            if(in_array($item_id, array("5", "6"))) {
                                die(json_encode(array('status' => 'err', 'err_msg' => _d('We need the user ID!',996))));
                            } else {
                                die(json_encode(array('status' => 'err', 'err_msg' => _d('We need the ad ID!',439))));
                            }
                        }
                        break;
                } // switch between fields
            } // foreach $form

            // does the ad/user exist?
            if(in_array($item_id, array("5", "6"))) {
                $post_data = get_user_by('ID', $post_id);
                if(!$post_data) die(json_encode(array('status' => 'err', 'err_msg' => _d('There is no user with this ID!',997))));
            } else {
                $post_data = get_post($post_id);
                if(!$post_data) die(json_encode(array('status' => 'err', 'err_msg' => _d('There is no ad with this ID!',440))));
            }

            // if the current user is not the ad author or an admin
            $user_id = in_array($item_id, array("5", "6")) ? $post_data->ID : $post_data->post_author;
            if($current_user->ID != $user_id && !current_user_can('level_10')) {
                die(json_encode(array('status' => 'err', 'err_msg' => _d('You are not allowed to cancel this subscription!',441))));
            }

            $author_stripe_id = get_the_author_meta('stripe_client_id', $user_id);
            if(!$author_stripe_id) {
                $err_text = in_array($item_id, array("5", "6")) ? _d('This user has no STRIPE client ID!',998) : _d('Ad author has no STRIPE client ID!',442);
                die(json_encode(array('status' => 'err', 'err_msg' => $err_text)));
            }

            $stripe_subscription_id = in_array($item_id, array("5", "6")) ? get_user_meta($post_id, 'stripe_subscription_id', true) : get_post_meta($post_id, 'stripe_subscription_id', true);
            if(!$stripe_subscription_id[$item_id]) {
                die(json_encode(array('status' => 'err', 'err_msg' => _d('There is no subscription id for this item id!',443))));
            }

            if(get_option('payment_stripe_sandbox') == "1") {
                // sandbox activated
                $stripe_secret_key = get_option('payment_stripe_test_secret_key');
                $stripe_publishable_key = get_option('payment_stripe_test_publishable_key');
            } else {
                // sandbox disabled
                $stripe_secret_key = get_option('payment_stripe_live_secret_key');
                $stripe_publishable_key = get_option('payment_stripe_live_publishable_key');
            }

            require_once(get_template_directory().'/APIs/stripe/stripe-php-4.13.0/init.php');
            \Stripe\Stripe::setApiKey($stripe_secret_key);

            try {
                $cu = \Stripe\Customer::retrieve($author_stripe_id);
                $cu->subscriptions->retrieve($stripe_subscription_id[$item_id])->cancel(array('at_period_end' => true));
            } catch (Exception $e) {
                die(json_encode(array('status' => 'err', 'err_msg' => _d('We couldn\'t cancel the subscription!',444))));
            }

            payment_canceled($item_id, $post_id, false);

            if(in_array($item_id, array("5", "6"))) {
                die(json_encode(array('status' => 'ok', 'msg' => _d('Subscription canceled',601))));
            }

            switch ($item_id) {
                case '1': // Ad posting fee
                    $expiration_meta = "ad_posting_fee_expiration";
                    break;

                case '2': // Always on top
                    $expiration_meta = "always_on_top_expiration";
                    break;

                case '3': // Highlighted ad
                    $expiration_meta = "highlighted_ad_expiration";
                    break;

                case '4': // Push ad
                    $expiration_meta = "push_ad_expiration";
                    break;
            }

            $msg = _d('Subscription canceled',601);
            if(get_post_meta($post_id, $expiration_meta, true)) {
                $expiration_seconds = get_post_meta($post_id, $expiration_meta, true) - current_time('timestamp');
                if($expiration_seconds > 0) {
                    // still active
                    $msg .= ' <span>-</span> '._d('expires in',462).' '.secondsToTime($expiration_seconds);
                }
            }

            die(json_encode(array('status' => 'ok', 'msg' => $msg)));
            break; // cancel-stripe-subscription

        case 'edit-account':
            if(current_user_can('level_10') && $form['userid']) {
                $userid = (int)$form['userid'];
                $current_user = get_user_by('id', $userid);
            } else {
                $userid = $current_user->ID;
            }

            foreach ($form as $key => $value) {
                switch ($key) {
                    case 'user_name':
                        if(!$value) {
                            $err[$key] = _d('You need to write your name. A nickname is also okay.',445);
                        } else {
                            $value = substr(sanitize_text_field($value), 0 , 100);
                        }
                        break;
                    
                    case 'user_email':
                        if(!$value) {
                            $err[$key] = _d('You need to write your email address',446);
                        } else {
                            if(!is_email($value)) {
                                $err[$key] = _d('Your email address is incorrect',447);
                            } elseif ($current_user->user_email != $value && email_exists($value) == true) {
                                $err[$key] = _d('There is already an account with this email address',448);
                            } else {
                                $value = sanitize_email($value);
                            }
                        }
                        break;
                    
                    case 'user_password':
                        if($value && (strlen($value) < 6 || strlen($value) > 100)) {
                            $err[$key] = _d('Your password must be between 6 and 100 characters',26);
                        }
                        break;
                    
                    // case 'user_phone':
                    //  if($value) {
                    //      $value = sanitize_text_field($value);
                    //  }
                    //  break;
                    
                    case 'user_url':
                        if($value) {
                            $value = esc_url($value, array('http','https'));
                        }
                        break;
                } // switch between fields
            } // foreach $form

            if(!$err) {
                $userdata = array(
                        'ID' => $userid,
                        'user_nicename' => $form['user_name'],
                        'display_name' => $form['user_name'],
                        'nickname' => $form['user_name'],
                        'user_email' => $form['user_email'],
                        'user_url' => $form['user_url']
                    );
                if($form['user_password']) {
                    $userdata['user_pass'] = $form['user_password'];
                }
                if(wp_update_user($userdata)) {
                    die(json_encode(array('status' => 'ok', 'form_ok' => _d('Settings saved',419))));
                } else {
                    $err .= _d('Something went wrong. Try saving again.',449)."<br />";
                    die(json_encode(array('status' => 'err', 'form_err' => $err)));
                }
            } else { // if !$err
                die(json_encode(array('status' => 'err', 'fields_err' => $err)));
            }
            break; // edit account

        case 'delete-account':
            $userid = (int)$form['userid'];
            $user = get_user_by('id', $userid);
            foreach ($user->roles as $key => $value) {
                if($value == "administrator") {
                    die(json_encode(array('status' => 'err', 'form_err' => _d('You can\'t delete an administrator account!',817))));
                }
            }
            // deleteme

            if(!current_user_can('level_10') && $current_user->ID != $userid) {
                die(json_encode(array('status' => 'err', 'form_err' => _d('You can\'t delete this account!',818))));
            }
            require_once(ABSPATH.'wp-admin/includes/user.php' );
            wp_delete_user($userid);
            wp_clear_auth_cookie();
            die(json_encode(array('status' => 'ok')));

            break; // add_demo_ads

        case 'add_demo_ads':
            echo create_demo_ads();
            break; // add_demo_ads

        case 'remove_demo_ads':
            echo create_demo_ads('delete');
            break; // add_demo_ads

        case 'ask-for-verification':
            if(get_user_meta($current_user->ID, 'ask_for_verification', true) != "yes") {
                update_user_meta($current_user->ID, 'ask_for_verification', "yes");
                dolce_email('', '', $body_vars='', '14');
            }
            echo "ok";
            break; // add_demo_ads

        case 'hide-welcoming-message':
            if(!current_user_can('level_10')) die('This is only for admins!');
            update_option('show_welcoming_message', 'no');
            break; // hide-welcoming-message

        case 'edit-form-field-names':
            if(!current_user_can('level_10')) die(_d('This is only for admins!',420));
            unset($form['action']);
            $form_builder_form_fields = get_option('form_builder_form_fields');
            foreach ($form as $name => $value) {
                foreach ($form_builder_form_fields['all'] as $key => $field) {
                    if($field['input_name'] == $name) {
                        if(!is_array($form_builder_form_fields['all'][$key]['name'])) $form_builder_form_fields['all'][$key]['name'] = array();
                        $form_builder_form_fields['all'][$key]['name']['default'] = substr(sanitize_text_field($value), 0 , 500);
                    }
                }
            } // foreach $form
            update_option('form_builder_form_fields', $form_builder_form_fields);
            die(json_encode(array('status' => 'ok', 'form_ok' => _d('Settings saved',419))));
            break; // edit site settings
    } //switch ($action) {
}


function child_restore_default_language() {
    // add languages
    $words = array(
        '1' => 'The ad doesn\'t exist',
        '2' => 'You are not allowed to make modifications',
        '3' => 'The category you selected does not exist. Please select another one.',
        '4' => 'This field is mandatory',
        '5' => 'Your address is incomplete. Try to write it again.',
        '6' => 'Your ad was saved',
        '7' => 'We couldn\'t save your ad. Please try again.',
        '8' => 'only an admin can activate this ad',
        '9' => 'Please select a reason for the report',
        '10' => 'There was an error with the report form. Reload the page and try again',
        '11' => 'The ad id from the report form is not correct',
        '12' => 'Reported ad on',
        '13' => 'Hello admin',
        '14' => 'Someone has reported an ad from your website at',
        '15' => 'Ad name',
        '16' => 'Ad url',
        '17' => 'Report reason',
        '18' => 'Report message',
        '19' => 'Your report was sent',
        '20' => 'Please write your name',
        '21' => 'Please write your email address',
        '22' => 'Your email address seems to be incorrect',
        '23' => 'This email address already exists.',
        '24' => 'Recover your password?',
        '25' => 'Please write a password',
        '26' => 'Your password must be between 6 and 100 characters',
        '27' => 'Account created',
        '28' => 'Error. Please try again.',
        '29' => 'You can on only resend the email once every 10 seconds',
        '30' => 'Email sent',
        '31' => 'You have already validated your email address. Refresh the page.',
        '32' => 'Please make sure the information is correct',
        '33' => 'You are now logged in',
        '34' => 'Please write a message',
        '35' => 'Please write your name',
        '36' => 'We don\'t know where to send this message. Try reloading the page and sending your message again.',
        '37' => 'Your message was sent',
        '38' => 'You need to register or login first!',
        '39' => 'You are not logged in',
        '40' => 'You can\'t edit this',
        '41' => 'Post or image does not exist',
        '42' => 'Write your city and country (or your street address) and make sure the map marker is correctly positioned',
        '43' => 'Your address remains private. We\'ll never show your full address.',
        '44' => 'The category name can\'t be empty',
        '45' => 'The category url can\'t be empty',
        '46' => 'This category already exists',
        '47' => 'This url is already used on another category',
        '48' => 'Getting font session',
        '49' => 'FAIL',
        '50' => 'Generating font file',
        '51' => 'Font archive created',
        '52' => 'Font archive unzipped',
        '53' => 'Unzipping font archive',
        '54' => 'OK',
        '55' => 'Close',
        '56' => 'Toggle fullscreen',
        '57' => 'Zoom in/out',
        '58' => 'Previous',
        '59' => 'arrow left',
        '60' => 'Next',
        '61' => 'arrow right',
        '62' => 'My messages',
        '63' => 'Select all',
        '64' => 'Delete selected',
        '65' => 'No private messages yet',
        '66' => 'Send',
        '67' => 'Messages',
        '68' => 'Message',
        '69' => 'Add New Message',
        '70' => 'Edit Message',
        '71' => 'View Message',
        '72' => 'All Messages',
        '73' => 'Search Messages',
        '74' => 'Parent Message',
        '75' => 'No Messages found',
        '76' => 'No Messages found in Trash',
        '77' => 'Delete this conversation?',
        '78' => 'Yes',
        '79' => 'No',
        '80' => 'Field',
        '81' => 'Private messages settings',
        '82' => 'Go to help page',
        '83' => 'Since this is a demo installation you won\'t be able to change the options.',
        '84' => 'Allow users to use private messages',
        '85' => 'yes',
        '86' => 'no',
        '87' => 'If you don\'t enable private messages then all messages will be sent by email',
        '88' => 'Notify user by email when they receive a new message?',
        '89' => 'Include the private message in the email?',
        '90' => 'If you choose "NO" then the user will receive a link to the private messages inbox page and they will be able to read the message there.',
        '91' => 'Save settings',
        '92' => 'Saving',
        '93' => 'Saved',
        '94' => 'Error',
        '95' => 'expand',
        '96' => 'Are you sure?',
        '97' => 'minimize',
        '98' => 'type',
        '99' => 'values',
        '100' => 'Label text',
        '101' => 'The text that will be shown next to the form field, in the <b>Post your ad</b> page. This part usually describes what the form field is used for',
        '102' => 'Label size',
        '103' => 'Here you can choose the label size. You can choose a size in pixels or in percentages. We recommend that you always use percentages. That way the form will look good on all screen sizes.',
        '104' => 'Label class',
        '105' => 'If you added your own css class in the css file of the theme then you can use that css class here. The css class will only be applied to the label side of the form and only to this specific label, not all of them.',
        '106' => 'Label help icon text',
        '107' => 'You can add extra information to a label. For example maybe you want to describe how the user should fill in a particular form field. If you add text here then the label will have a question mark icon next to it. When someone moves their cursor on the question mark, the text will appear. It will look exactly like the tooltip that you are looking at now.',
        '108' => 'Label help note',
        '109' => 'If you want to add an extra description to the label and have that text be shown at all times, then add the text here instead of using the <b>help icon text</b> method.',
        '110' => 'Field type',
        '111' => 'The fields can be any one of these 5 types',
        '112' => 'This field is the most used field. The user can only write text on a single line.',
        '113' => 'This field will show a textarea field. The user can write text on multiple lines.',
        '114' => 'This field will show a drop down list. The user will only be able to select a single option from the drop down list.',
        '115' => 'This field will show a list of radio buttons. The user will only be able to select a single option from the list.',
        '116' => 'This field will show a list of checkboxes. The user can choose one or multiple options.',
        '117' => 'Field values',
        '118' => 'When the <b>Field type</b> is <b>Select</b>, <b>Radio</b> or <b>Checkbox</b> you have to choose your own options that will appear in the form. The user will only be able to choose from the list of options that you write here.<br />Only one option per line.',
        '119' => 'Field character limit',
        '120' => 'Field size',
        '121' => 'Here you can modify the size of the field. It\'s recommended that you use percentages so the field will be displayed correctly on all screen sizes.<br />You can only set the size of the field for <b>text</b> and <b>textarea</b> fields.',
        '122' => 'Field class',
        '123' => 'If you added your own css class in the css file of the theme then you can use that css class here.<br />The css class will only be applied to this specific field, not all fields from the form.',
        '124' => 'Field help icon text',
        '125' => 'You can add extra information to a field. For example maybe you want to describe how the user should fill in a particular form field. If you add text here then the field will have a question mark icon next to it. When someone moves their cursor on the question mark, the text will appear. It will look exactly like the tooltip that you are looking at now.',
        '126' => 'Field help note',
        '127' => 'If you want to add an extra description to the label and have that text be shown at all times, then add the text here instead of using the <b>help icon text</b> method.',
        '128' => 'Mandatory field?',
        '129' => 'Choose if this field should be mandatory or not.',
        '130' => 'Field group size',
        '131' => 'The <b>field group</b> is the box that the <b>label</b> and the <b>field</b> are both in. By default, the ad form, shows just one field on each line. But if you change the group size to 50%(half the size of the form) then you can fit two fields on the same line.',
        '132' => 'Add a div separator after the field?',
        '133' => 'The theme only shows a single field group on each line. After the field group there is a space. That space separates each field group in order for the fields to be easier to see for the users.<br />It is recommended that each field have a separator after it. But if you want to have more than one field group on the same line then you must disable the <b>div separator</b>.',
        '134' => 'Separator height',
        '135' => 'The default size for the <b>div separator</b> is <b>30px</b>. Here you can choose your custom height for the separator in case you want it to be larger or smaller than 30px.',
        '136' => 'Is this a sortable field?',
        '137' => 'In the category pages, in the sidebar, you can have sorting filters for the products in that category.',
        '138' => 'Read more about it here.',
        '139' => 'Use field for',
        '140' => 'The theme has special form fields that you can use in your site.',
        '141' => 'The information submitted in these special fields will be shown in different places of the ad page.',
        '142' => 'None',
        '143' => 'Ad page position',
        '144' => 'All data written in the form needs to be shown somewhere in the ad page.',
        '145' => 'You can choose where the data written in this field will be shown.',
        '146' => 'Under the price',
        '147' => 'Under the images',
        '148' => 'Show advanced settings',
        '149' => 'Hide advanced settings',
        '150' => 'Add new field in this group',
        '151' => 'Save',
        '152' => 'Saving',
        '153' => 'Other fields in this field group',
        '154' => 'expand all',
        '155' => 'minimize all',
        '156' => 'You are currently editing the form for the category',
        '157' => 'All categories',
        '158' => 'Use this form for all sub-categories',
        '159' => 'unless you create a separate form for a subcategory',
        '160' => 'You have no form fields created for this category.',
        '161' => 'You can start adding new form fields or simply import a list of form fields from another category.',
        '162' => 'Add new field',
        '163' => 'or import from',
        '164' => 'Choose category',
        '165' => 'All ads from',
        '166' => 'Newest first',
        '167' => 'Oldest first',
        '168' => 'Price Low to High',
        '169' => 'Price High to Low',
        '170' => 'Order by',
        '171' => 'There are no ads here at the moment',
        '172' => 'ads online',
        '173' => 'Member since',
        '174' => 'Seller\'s website',
        '175' => 'UserID',
        '176' => 'Email',
        '177' => 'Edit user',
        '178' => 'Edit in WordPress',
        '179' => 'Send message',
        '180' => 'There are no files to upload',
        '181' => 'The uploaded file exceeds the upload_max_filesize directive in php.ini',
        '182' => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form',
        '183' => 'The uploaded file was only partially uploaded',
        '184' => 'No file was uploaded',
        '185' => 'Missing a temporary folder',
        '186' => 'Failed to write file to disk',
        '187' => 'A PHP extension stopped the file upload',
        '188' => 'Your image is bigger than 5MB',
        '189' => 'Image size error',
        '190' => 'This file doesn\'t seem to be an image',
        '191' => 'Error uploading image',
        '192' => 'Payment settings',
        '193' => 'Choose the payment processors you want to use in your site',
        '194' => 'settings',
        '195' => 'close',
        '196' => 'Activate PayPal payments?',
        '197' => 'Your PayPal email address',
        '198' => 'Activate PayPal test mode?',
        '199' => 'Your PayPal sandbox email',
        '200' => 'Activate STRIPE payments?',
        '201' => 'STRIPE live secret key',
        '202' => 'STRIPE live publishable key',
        '203' => 'Show "remember me" field?',
        '204' => 'Activate STRIPE test mode?',
        '205' => 'STRIPE test secret key',
        '206' => 'STRIPE test publishable key',
        '207' => 'NOTE',
        '208' => 'You should add a',
        '209' => 'webhook',
        '210' => 'in your STRIPE account',
        '211' => 'That way refunds or chargebacks will be processed automatically by the theme',
        '212' => 'Step',
        '213' => 'Go to your Webhooks page from your account.',
        '214' => 'Add endpoint',
        '215' => 'Fill in the form like this',
        '216' => 'and',
        '217' => 'create two webhooks, with the same URL, one for live and one for test',
        '218' => 'URL',
        '219' => 'Mode',
        '220' => 'Live',
        '221' => 'Test',
        '222' => 'Select',
        '223' => 'Send me all events',
        '224' => 'Click the button',
        '225' => 'Create endpoint',
        '226' => 'Choose a currency for the payment options',
        '227' => 'Leaving a price empty will disable that upgrade',
        '228' => 'Payments currency code',
        '229' => 'examples',
        '230' => 'make sure the currency code is supported by all the payment processors that you activated',
        '231' => 'Payments currency symbol',
        '232' => 'add your currency symbol before or after the amount, depending on the standard for the currency',
        '233' => 'the symbol will be used next to the prices in your site. if no symbol is added then the 3 letter currency code will be used.',
        '234' => 'Posting an ad',
        '235' => 'This price is for normal ads. Not upgrades. If you don\'t write a price then ad posting is free.',
        '236' => 'Price',
        '237' => 'Paid ads will last for',
        '238' => 'Leaving this empty means the upgrade will never expire',
        '239' => 'Recurring payments?',
        '240' => 'Always on top',
        '241' => 'Featured ads',
        '242' => 'Ads will be displayed on top of normal ads and will have the "FEATURED" label next to them.',
        '243' => 'Upgrade will last for',
        '244' => 'Highlighted ads',
        '245' => 'These ads will be highlighted with a different color than normal ads',
        '246' => 'Push to top',
        '247' => 'Once a day the ad will be pushed to the top of other ads. It will look the same as if the ad was posted again.',
        '248' => 'Push each day for',
        '249' => 'Push ads each day at',
        '250' => 'hours',
        '251' => 'Mandatory fields',
        '252' => 'Language settings',
        '253' => 'Edit a language',
        '254' => 'All',
        '255' => 'Choose language',
        '256' => 'Add a new language',
        '257' => 'Language name',
        '258' => 'Add new language',
        '259' => 'There is no language with this name!',
        '260' => 'Edit site settings',
        '261' => 'Site language',
        '262' => 'Site title',
        '263' => 'Site description',
        '264' => 'Upload logo',
        '265' => 'Delete logo',
        '266' => 'Edit currency list',
        '267' => 'Symbol position',
        '268' => 'add new currency',
        '269' => 'Default currency',
        '270' => 'Allow Facebook login',
        '271' => 'Facebook app id',
        '272' => 'Facebook app secret',
        '273' => 'Allow Google+ login',
        '274' => 'Google+ client id',
        '275' => 'Google+ client secret',
        '276' => 'Google+ auth callback url',
        '277' => 'Make sure you set this exact callback url in your Google application',
        '278' => 'Show empty categories in sidebar',
        '279' => 'Show price limits in sidebar',
        '280' => 'Show phone number only to registered users?',
        '281' => 'It seems that the Private Messages plugin is not installed.',
        '282' => 'Contact us to upgrade your theme and activate this plugin',
        '283' => 'It seems that the Form Builder plugin is not installed.',
        '284' => 'Email Settings',
        '285' => 'Site users will receive emails with the following details',
        '286' => 'Sender name',
        '287' => 'Sender email',
        '288' => 'Email signature',
        '289' => 'This will be at the end of all emails',
        '290' => 'html allowed',
        '291' => 'Email notifications',
        '292' => 'Notifications email',
        '293' => 'New user registration',
        '294' => 'New ad posted',
        '295' => 'New payment',
        '296' => 'You need to regenerate the icon font file for your website.',
        '297' => 'Why do I need to do this?',
        '298' => 'This theme uses',
        '299' => 'icon fonts',
        '300' => 'to show the icons next to the category name',
        '301' => 'Icon fonts are just like text fonts but instead of letters they contain icons',
        '302' => 'Using an icon font, instead of icon images, cuts down on the file size a lot and it also allows the icons to be resized as much as you want without loosing quality',
        '303' => 'The icons will look the same way on a computer, laptop or phone, regardless of the screen size',
        '304' => 'The whole icon list for the theme has',
        '305' => 'icons',
        '306' => 'Including the whole icon library on each page load would add <b>2.3MB</b> to the page',
        '307' => 'To keep the theme as optimized as possible we have added the possibility to generate a separate font file that only has the icons that you are actually using',
        '308' => 'This noticeably cuts down the file size so the pages will load faster',
        '309' => 'After you\'ve done changes to the icon list click the save button to generate a new icon font file',
        '310' => 'You don\'t need to do this after each icon change',
        '311' => 'It\'s actually recommended that you only do this after you\'ve finished all the changes',
        '312' => 'So basically when you\'re ready to leave this page just click the save button',
        '313' => 'The theme uses the website',
        '314' => 'to generate the font files',
        '315' => 'That way you don\'t need to install anything else on your server to be able to have this feature',
        '316' => 'Save Settings',
        '317' => 'Add new category',
        '318' => 'Category name',
        '319' => 'Category url',
        '320' => 'Category description',
        '321' => 'Category parent',
        '322' => 'No subcategory',
        '323' => 'Category icon',
        '324' => 'Add category',
        '325' => 'Update category',
        '326' => 'Cancel edit',
        '327' => 'search for icon by name',
        '328' => 'Icon system provided by',
        '329' => 'Ad Settings',
        '330' => 'will be used like this',
        '331' => 'Ad category url',
        '332' => 'cars',
        '333' => 'Ad location url',
        '334' => 'Should ads expire?',
        '335' => 'When do ads expire?',
        '336' => 'after',
        '337' => 'days',
        '338' => '0 or empty means the ads never expire',
        '339' => 'Show a notice above expired ads?',
        '340' => 'Send an email after the ad has expired?',
        '341' => 'The email will let the user know what happened to the ad depending on what you choose in the next option',
        '342' => 'What happens to expired ads?',
        '343' => 'expired ads are checked once a day at 03:00 AM, server time',
        '344' => 'Hide the ad from the site and keep ad upgrades',
        '345' => 'ad owner will still see the ad',
        '346' => 'Hide the ad from the site but remove ad upgrades',
        '347' => 'Keep the ad on the site and delete ad upgrades',
        '348' => 'Delete the ad from the site',
        '349' => 'Ad stays the same',
        '350' => 'How many images can a user upload for an ad?',
        '351' => 'Image size limit',
        '352' => 'Manually approve each new ad?',
        '353' => 'Number of ads on each page',
        '354' => 'There was an error when uploading this image. Please refresh the page.',
        '355' => 'You have uploaded the maximum number of allowed images',
        '356' => 'Your image is bigger than %d MB',
        '357' => 'You\'re not allowed to see this page without an account. Sorry about that.',
        '358' => 'You can register or login here',
        '359' => 'minutes ago',
        '360' => 'hours ago',
        '361' => 'days ago',
        '362' => 'yesterday',
        '363' => 'now',
        '364' => 'Category',
        '365' => 'Categories',
        '366' => 'Search Categories',
        '367' => 'All Categories',
        '368' => 'Parent Category',
        '369' => 'Edit Category',
        '370' => 'Update Category',
        '371' => 'Add New Category',
        '372' => 'New Category Name',
        '373' => 'Ad Categories',
        '374' => 'Location',
        '375' => 'Search Locations',
        '376' => 'All Locations',
        '377' => 'Parent Location',
        '378' => 'Edit Location',
        '379' => 'Update Location',
        '380' => 'Add New Location',
        '381' => 'New Location Name',
        '382' => 'Ad Locations',
        '383' => 'Ads',
        '384' => 'Ad',
        '385' => 'Add New Ad',
        '386' => 'Edit Ad',
        '387' => 'View Ad',
        '388' => 'All Ads',
        '389' => 'Search Ads',
        '390' => 'Parent Ad',
        '391' => 'No ads found',
        '392' => 'No ads found in Trash',
        '393' => 'Payments',
        '394' => 'Payment',
        '395' => 'Add New Payment',
        '396' => 'Edit Payment',
        '397' => 'View Payment',
        '398' => 'All Payments',
        '399' => 'Search Payments',
        '400' => 'Parent Payment',
        '401' => 'No payments found',
        '402' => 'No payments found in Trash',
        '403' => 'Profile page message',
        '404' => 'Send a message to',
        '405' => 'Subject',
        '406' => 'html code will be removed',
        '407' => 'Name',
        '408' => 'Phone',
        '409' => 'Send Message',
        '410' => 'Sending',
        '411' => 'Message Sent',
        '412' => 'The ad does not exist',
        '413' => 'You are trying to send a message to a non existing user',
        '414' => 'We don\'t know where to send the message. Try refreshing the page.',
        '415' => 'You are sending messages too fast',
        '416' => 'To prevent spam please wait %d seconds before sending your next message',
        '417' => 'We couldn\'t send the message. Email is not working.',
        '418' => 'You must be logged in!',
        '419' => 'Settings saved',
        '420' => 'This is only for admins!',
        '421' => 'You need to add at least one currency',
        '422' => 'The ad url can\'t be the same as the category url',
        '423' => 'The ad url can\'t be the same as the location url',
        '424' => 'The category url can\'t be the same as the location url',
        '425' => 'This field can\'t be empty',
        '426' => 'You need to allow at least one image',
        '427' => 'The images need to be at least 1MB in size',
        '428' => 'This doesn\'t seem to be a correct number',
        '429' => 'The hour needs to be between 0 and 23',
        '430' => 'The minutes need to be between 0 and 59',
        '431' => 'You need to write your PayPal email address',
        '432' => 'You need to write your PayPal Sandbox email address',
        '433' => 'You need to write your LIVE keys',
        '434' => 'You need to write your TEST keys',
        '435' => 'Ad posting fee',
        '436' => 'Highlighted ad',
        '437' => 'Push to top',
        '438' => 'We need the item ID!',
        '439' => 'We need the ad ID!',
        '440' => 'There is no ad with this ID!',
        '441' => 'You are not allowed to cancel this subscription!',
        '442' => 'Ad author has no STRIPE client ID!',
        '443' => 'There is no subscription id for this item id!',
        '444' => 'We couldn\'t cancel the subscription!',
        '445' => 'You need to write your name. A nickname is also okay.',
        '446' => 'You need to write your email address',
        '447' => 'Your email address is incorrect',
        '448' => 'There is already an account with this email address',
        '449' => 'Something went wrong. Try saving again.',
        '450' => 'minutes',
        '451' => 'Never',
        '452' => 'day',
        '453' => 'week',
        '454' => 'weeks',
        '455' => 'month',
        '456' => 'months',
        '457' => 'year',
        '458' => 'Please pay the posting fee so we can show your ad in our website',
        '459' => 'Here you can purchase ad upgrades to get more views for your ad',
        '460' => 'purchased',
        '461' => 'renews in',
        '462' => 'expires in',
        '463' => 'expired',
        '464' => 'In order to display the ad in our website you will need to pay a posting fee',
        '465' => 'Expires in',
        '466' => 'Add upgrade',
        '467' => 'Removing',
        '468' => 'Removed',
        '469' => 'Remove',
        '470' => 'with',
        '471' => 'Extend this ad for',
        '472' => 'Pay',
        '473' => 'every',
        '474' => 'for',
        '475' => 'Your ad will be displayed on top of normal ads and will have the "FEATURED" label next to them',
        '476' => 'Your ad will be highlighted with a different color than normal ads',
        '477' => 'Once a day we\'ll change the posting time of your ad and move it at the top of the newly added ads. That way your ad will not get pushed down in the listings by newer ads.',
        '478' => 'Your comment has been posted but needs to be approved by an admin first',
        '479' => 'Change category',
        '480' => 'Main image',
        '481' => 'Too many images!',
        '482' => 'You can only upload',
        '483' => 'more images',
        '484' => 'Sorry, but you uploaded the maximum number of allowed images.',
        '485' => 'Your image is bigger than',
        '486' => 'images have been uploaded',
        '487' => 'Sorry, but you uploaded the maximum number of allowed images',
        '488' => 'Drag your images here to upload them or',
        '489' => 'Select from a folder',
        '490' => 'You can upload a maximum of %s images',
        '491' => 'You have %s images left',
        '492' => 'Drop your images here',
        '493' => 'Click an image to select it as the main image for the ad',
        '494' => 'Update ad',
        '495' => 'Submit ad',
        '496' => 'Go Back',
        '497' => 'The site owners have decided that all messages should be sent directly to your email address',
        '498' => 'Your ad is currently not visible in our website. You\'ll need to pay a fee to activate your ad.',
        '499' => 'Pay Now',
        '500' => 'We are reviewing your ad to make sure it\'s accurate and we\'ll activate it very soon.',
        '501' => 'In the meantime, your ad will not be publicly visible in our website but you can still edit your ad if you want.',
        '502' => 'We\'ll let you know when your ad has been approved and is visible in the website.',
        '503' => 'Edit ad',
        '504' => 'Edit Images',
        '505' => 'Pause Ad',
        '506' => 'Paused',
        '507' => 'Do you want to pause this ad?',
        '508' => 'You can reactivate the ad at any time.<br />The ad will not be visible to others while it\'s paused.',
        '509' => 'Your ad is paused!',
        '510' => 'Do you want to repost the ad?',
        '511' => 'Do you want to reactivate the ad?',
        '512' => 'Your ad is active again!',
        '513' => 'Expired',
        '514' => 'Do you want to delete this ad?',
        '515' => 'This action can\'t be undone',
        '516' => 'Delete Ad',
        '517' => 'Upgrade',
        '518' => 'Edit in WP',
        '519' => 'Prints',
        '520' => 'Phone clicks',
        '521' => 'Views',
        '522' => 'Back to the ad',
        '523' => 'Your ad was deleted.',
        '524' => 'Go to your',
        '525' => 'widgets page',
        '526' => 'to add content here.',
        '527' => 'price',
        '528' => 'Posted on',
        '529' => 'Sold by',
        '530' => 'Show phone number',
        '531' => 'Print',
        '532' => 'Report',
        '533' => 'This ad has no user ID.',
        '534' => 'This means the ad was posted by an unregistered user and will be deleted if the user does not register.',
        '535' => 'Report this ad',
        '536' => 'Reason',
        '537' => 'Fake ad',
        '538' => 'Spam ad',
        '539' => 'Copyright issue',
        '540' => 'Other',
        '541' => 'Send Report',
        '542' => 'Report Sent',
        '543' => 'Cancel',
        '544' => 'No pictures',
        '545' => 'The image could not be loaded',
        '546' => 'Choose Category',
        '547' => 'Write your ad',
        '548' => 'Confirmation',
        '549' => 'In what category would you like to place your ad?',
        '550' => 'Back',
        '551' => 'Post ad in',
        '552' => 'or choose a subcategory below',
        '553' => 'Your ad was saved!',
        '554' => 'Visit your ad here',
        '555' => 'redirecting in %s seconds ...',
        '556' => 'Your ad was saved but we need your email address so we can publish the ad on our website',
        '557' => 'Registering',
        '558' => 'Register',
        '559' => 'or connect with',
        '560' => 'Check your email',
        '561' => 'We sent a link to your email address.',
        '562' => 'Please click the link to validate your email address.',
        '563' => 'Resend email?',
        '564' => 'Edit account',
        '565' => 'Nickname',
        '566' => 'Password',
        '567' => 'Website',
        '568' => 'Upload avatar',
        '569' => 'Delete avatar',
        '570' => 'Search results',
        '571' => 'Latest ads',
        '572' => 'All ads in',
        '573' => 'Closest first',
        '574' => 'Closest last',
        '575' => 'We couldn\'t find any ads with these filters',
        '576' => 'We couldn\'t find any ads',
        '577' => 'There are no ads here at the moment.',
        '578' => 'Would you like to be the first to post an ad here?',
        '579' => 'Written by',
        '580' => 'Edit in WordPess',
        '581' => 'Tags',
        '582' => 'No Comments',
        '583' => 'Comment',
        '584' => 'Comments',
        '585' => 'Blog Categories',
        '586' => 'Back to',
        '587' => 'FEATURED',
        '588' => 'km away',
        '589' => 'We are removing the upgrade from the ad',
        '590' => 'Your ad',
        '591' => 'will not be visible in our website anymore.',
        '592' => 'Payment failed.',
        '593' => 'Product name',
        '594' => 'Product type',
        '595' => 'Payment for ad',
        '596' => 'Client email',
        '597' => 'Payment failed',
        '598' => 'Hi',
        '599' => 'We tried to bill your card for %s but the payment failed.',
        '600' => 'Payment refunded.',
        '601' => 'Subscription canceled',
        '602' => 'Your payment for %s has been refunded.',
        '603' => 'Payment dispute.',
        '604' => 'The ad has not been changed and the upgrade has not been removed.',
        '605' => 'It is up to you to decide what you want to do with the ad.',
        '606' => 'If the outcome of the chargeback is in favor of the client then the upgrade will be removed automatically.',
        '607' => 'If it\'s in your favor then no action will be performed.',
        '608' => 'Payment dispute',
        '609' => 'You won a payment dispute.',
        '610' => 'You won a payment dispute',
        '611' => 'We are removing the upgrade from your ad.',
        '612' => 'The ad will not be visible in our website anymore.',
        '613' => 'You lost a payment dispute.',
        '614' => 'You lost a payment dispute',
        '615' => 'Your payment dispute for %s has closed in your favor.',
        '616' => 'The upgrade will still be active until the expiration date.',
        '617' => 'The ad will still be visible until the expiration date.',
        '618' => 'Subscription canceled.',
        '619' => 'subscription',
        '620' => 'Your subscription for %s has been canceled.',
        '621' => 'No token was submitted',
        '622' => 'We don\'t know what ad you are paying for',
        '623' => 'Can\'t create the customer!',
        '624' => 'Can\'t process this credit card',
        '625' => 'Can\'t create subscription!',
        '626' => 'The card has been declined',
        '627' => 'ERROR',
        '628' => 'Got %s when processing IPN data.',
        '629' => 'Raw post data',
        '630' => 'Payment for',
        '631' => 'Payment error!',
        '632' => 'IPN data submitted is invalid.',
        '633' => 'Payment status',
        '634' => 'Payment is <b>pending</b>',
        '635' => 'PayPal reason',
        '636' => 'Payment notification!',
        '637' => 'Your payment for the site %s is currently pending.',
        '638' => 'We\'ll let you know when the payment has finished.',
        '639' => 'Payment was <b>refunded</b>.',
        '640' => 'Payment refunded!',
        '641' => 'Your payment for the site %s was refunded.',
        '642' => 'A payment was reversed.',
        '643' => 'Payment reversed!',
        '644' => 'Your payment for the site %s was reversed.',
        '645' => 'Payment status is not recognized',
        '646' => 'This payment has no custom field value.',
        '647' => 'The custom field is used to store the ad id. We need that in order to know where to apply the upgrade from the payment. The payment processing has been stopped in the theme.',
        '648' => 'The business email from the transaction data does not match the email from the payment settings page.',
        '649' => 'The price for %s from the transaction is not the same as the price you set in the site.',
        '650' => 'The currency from the transaction is not the same as the site\'s currency.',
        '651' => 'This transaction has been processed already',
        '652' => 'Your ad is active but it needs to be approved by an admin first.',
        '653' => 'Your payment was successful.',
        '654' => 'Payment price',
        '655' => 'Payment type',
        '656' => 'Payment received!',
        '657' => 'You have received a new payment.',
        '658' => 'Payment successful!',
        '659' => 'Posted in',
        '660' => 'Previous page',
        '661' => 'Next page',
        '662' => 'No articles found',
        '663' => 'You have successfully activated your account.',
        '664' => 'You can browse the site and post ads now.',
        '665' => 'This email has already been activated.',
        '666' => 'Place ads on the site',
        '667' => 'Contact other members',
        '668' => 'ads to pick from',
        '669' => 'Login',
        '670' => 'Logging in',
        '671' => 'Or login with',
        '672' => 'Or register with',
        '673' => 'We are refreshing the page...',
        '674' => 'Welcome to your Classified Ads website',
        '675' => 'It looks like this is the first time you are installing this theme. Here is a list of things you should do:',
        '676' => 'Read the documentation for the theme',
        '677' => 'Do it now',
        '678' => 'Create some categories',
        '679' => 'Go to the form builder and change the ad posting form.',
        '680' => 'The theme already created a form for you so this is not very important. Just do it if you want different form fields.',
        '681' => 'Visit the settings page if you want to change the site\'s settings',
        '682' => 'Visit the payment settings page if you want to charge for the ad posting',
        '683' => 'Business users',
        '684' => 'Hide this',
        '685' => 'Menu',
        '686' => 'My account',
        '687' => 'My profile',
        '688' => 'Log Out',
        '689' => 'Home',
        '690' => 'Post new ad',
        '691' => 'Where?',
        '692' => 'Search for',
        '693' => 'Search',
        '694' => 'Settings',
        '695' => 'Site Settings',
        '696' => 'Payment Settings',
        '697' => 'Language Settings',
        '698' => 'Documentation/Help',
        '699' => 'Private messages',
        '700' => 'Form builder',
        // '701' => 'Forms',
        '702' => 'Edit categories',
        '703' => 'WordPress Dashboard',
        '704' => 'WordPress',
        '705' => 'Credit Card',
        '706' => 'Processing payment!',
        '707' => 'please wait...',
        '708' => 'Subscribe for',
        '709' => 'Canceling',
        '710' => 'Cancel subscription',
        '711' => 'Canceling the subscription does not remove the upgrade. It will be removed at expiration date.',
        '712' => 'Your ad will not be visible in the site anymore but you can repost it anytime you want.',
        '713' => 'Your ad will not be visible in the site anymore but you can repost it anytime you want.<br />If the ad had any upgrades then those wore removed.',
        '714' => 'Your ad will still be visible in our website but if it has any upgrades then those will be removed.',
        '715' => 'Because of that, we had to remove the ad from our website.',
        '716' => 'Copyright',
        '717' => 'Be the first to comment here.',
        '718' => 'Your comment',
        '719' => 'Submit Comment',
        '720' => 'Cancel reply',
        '721' => 'Reply',
        '722' => 'January',
        '723' => 'February',
        '724' => 'March',
        '725' => 'April',
        '726' => 'May',
        '727' => 'June',
        '728' => 'July',
        '729' => 'August',
        '730' => 'September',
        '731' => 'October',
        '732' => 'November',
        '733' => 'December',
        '734' => 'Days',
        '735' => 'Day',
        '736' => 'Weeks',
        '737' => 'Week',
        '738' => 'Months',
        '739' => 'Month',
        '740' => 'Years',
        '741' => 'Year',
        '742' => 'Email validation link for',
        '743' => 'Please click the link below so we can activate your account',
        '744' => 'Your ad has been activated on',
        '745' => 'Your ad %s has been activated and can now be viewed by everyone on our website.',
        '746' => 'You can see your ad here',
        '747' => 'Your ad has expired on',
        '748' => 'An ad that you posted on our website has just expired.',
        '749' => 'If you would like to repost the ad please visit our website at',
        '750' => 'sent you a message',
        '751' => 'Expired ad',
        '752' => 'sent you a message regarding your ad that you posted on',
        '753' => 'You can send a message to %s by replying back to this email.',
        '754' => 'sent you a message from your profile page on',
        '755' => 'Profile page',
        '756' => 'To read the message and reply back please visit your inbox here',
        '757' => 'To read the message please visit your inbox here',
        '758' => 'replied back to your message on',
        '759' => 'Reply for',
        '760' => 'This language already exists',
        '761' => 'The language name can\'t be empty',
        '762' => 'Create demo ads & categories',
        '763' => 'Create Demo Ads',
        '764' => 'Delete Demo Ads',
        '765' => 'Creating',
        '766' => 'The demo ads have been created',
        '767' => 'The demo ads have been removed',
        '768' => 'this can take a few minutes, depending on your server\'s performance',
        '769' => '<b>NOTE</b>: Try not to post real ads in the demo categories. When you delete the demo data the demo categories will be deleted.',
        '770' => 'Your regular categories or ads will not be deleted.',
        '771' => 'Blog',
        '772' => 'Registration IP',
        '773' => 'Ad posted from IP',
        '774' => 'Report reason',
        '775' => 'Registered User',
        '776' => 'IP',
        '777' => 'or',
        '778' => 'Push ad',
        '779' => 'Name / Nickname',
        '780' => 'Already have an account?',
        '781' => 'Click to login.',
        '782' => 'From',
        '783' => 'Till',
        '784' => 'Post ad for',
        '785' => 'Post free ad',
        '786' => 'You have a new user registration on your website.',
        '787' => 'New user registration on',
        '788' => 'New ad posted on',
        '789' => 'Someone posted a new ad on your website.',
        '790' => 'Google Maps API key',
        '791' => 'You\'ll need get an API key for Google Maps if you want to use the address field in the ad posting form.',
        '792' => 'You can read instructions on how to get an API key from here:',
        '793' => 'Allow image upload in private messages?',
        '794' => 'How many images can a user upload in one private message?',
        '795' => 'images at a time',
        '796' => 'You can only upload images',
        '797' => 'Maximum %s images',
        '798' => 'images left',
        '799' => 'Upload images',
        '801' => 'Conversation for',
        '802' => 'Page not found',
        '803' => 'It seems that the page you were trying to reach does not exist anymore, or maybe it has just moved.',
        '804' => 'Delete',
        '805' => 'Do you really want to delete this language set?',
        '806' => 'Show language drop-down in header?',
        '807' => 'Geolocation is not supported by this browser.',
        '808' => 'You denied the request for Geolocation.',
        '809' => 'Location information is unavailable.',
        '810' => 'Find my location',
        '811' => 'Ad design',
        '812' => 'Thumbnails',
        '813' => 'Description',
        '814' => 'optional',
        '815' => 'Delete account',
        '816' => 'This action can\'t be reversed.',
        '817' => 'You can\'t delete an administrator account!',
        '818' => 'You can\'t delete this account!',
        '819' => 'Your account has been deleted',
        '820' => 'Activate business users?',
        '821' => 'If YES then all users will be asked to choose between a personal and a business user account.',
        '822' => 'Allow business users to change header color on their profile page?',
        '823' => 'Allow business users to upload their own header image on their profile page?',
        '824' => 'User registration',
        '825' => 'Please select what type of account you would like to use',
        '826' => 'on',
        '827' => 'Posted in',
        '828' => 'Redirecting to profile page...',
        '829' => 'Allow business users to add extra users to their business account?',
        '830' => 'Paid accounts will last for',
        '831' => 'Private users',
        '832' => 'All users',
        '833' => 'Choose your payment options',
        '834' => 'Only for business users',
        '835' => 'Personal',
        '836' => 'Business',
        '837' => 'after this continue with',
        '838' => 'Extra users',
        '839' => 'Registration fee',
        '840' => 'mandatory',
        '841' => 'Your ad is now posted on our website',
        '842' => 'Your ad upgrade for "Always on top" is now active.',
        '843' => 'Your ad upgrade for "Highlighted ad" is now active.',
        '844' => 'Your ad upgrade for "Push ad" is now active.',
        '845' => 'Import',
        '846' => 'Export',
        '847' => 'This will replace the current language with the new language that you are importing.',
        '848' => 'Copy the text below and import it as needed',
        '849' => 'Paste your language text below:',
        '850' => 'The text uploaded is not formated correctly. Make sure you copied the text correctly when you exported the language text.',
        '861' => 'Language imported successfully',
        '862' => 'You need to refresh the page to see the changes',
        '863' => 'Refresh page',
        '864' => 'Don\'t refresh',
        '865' => 'Terms of Service page for the registration page',
        '866' => 'No pages available',
        '867' => 'Go here to create a Terms of Service page',
        '868' => 'By logging in or registering you automatically agree to our',
        '869' => 'Reviews',
        '870' => 'Add review',
        '871' => 'Write a review for',
        '872' => 'Delivery',
        '873' => 'Responsiveness',
        '874' => 'Friendliness',
        '875' => 'Leave a message about this seller',
        '876' => 'characters left',
        '877' => 'Select your rating again',
        '878' => 'Please write a short review about the seller',
        '879' => 'No seller selected for the review. Try refreshing the page.',
        '880' => 'Review for',
        '881' => 'Review',
        '882' => 'Edit Review',
        '883' => 'View Review',
        '884' => 'All Reviews',
        '885' => 'Search Reviews',
        '886' => 'No reviews found',
        '887' => 'No reviews found in Trash',
        '888' => 'We couldn\'t save the review. Please try again.',
        '889' => 'Your review was posted successfully.',
        '890' => 'Your rating for %s is',
        '891' => '%s has %s reviews',
        '892' => 'You already posted a review for this seller',
        '893' => 'Terms of Service page for the ad posting page',
        '894' => 'My Reviews',
        '895' => 'See all reviews',
        '896' => 'Reply to review',
        '897' => 'Leave a reply to this review',
        '898' => 'from',
        '899' => 'replied to the review',
        '900' => 'read more',
        '901' => 'See the ads instead',
        '902' => 'Delete',
        '903' => 'Edit',
        '904' => 'Edit your review for',
        '905' => 'There was an error when trying to update the review. Try refreshing the page.',
        '906' => 'You are not allowed to edit this review',
        '907' => 'Update review',
        '908' => 'Are you sure you want to delete this review?',
        '909' => 'Are you sure you want to delete your reply?',
        '910' => 'Deleted!',
        '911' => 'Your review was deleted.',
        '912' => 'Your reply was deleted.',
        '913' => 'We are ',
        '914' => 'Edit your review',
        '915' => 'Verified',
        '916' => 'Add verified',
        '917' => 'Remove verified',
        '918' => 'Account links',
        '919' => 'Admin links',
        '920' => 'Business account',
        '921' => 'stars',
        '922' => 'Push time',
        '923' => 'Become verified',
        '924' => 'Get your account verified',
        '925' => 'Getting your account verified is very important.',
        '926' => 'People who visit your ads will trust you more if they know you passed our verification process.',
        '927' => 'If you would like to get a verified badge then click the button below.',
        '928' => 'We\'ll contact you as soon as possible to ask for more details about you.',
        '929' => 'Ask for verification',
        '930' => 'Request sent',
        '931' => 'Verification request on',
        '932' => 'Someone asked to become a verified user on your website.',
        '933' => 'This account asked to be verified!',
        '934' => 'Business account',
        '935' => 'Add business status',
        '936' => 'Remove business status',
        '937' => 'Free',
        '938' => 'to post ads',
        '939' => 'for featured ads',
        '940' => 'for highlighted ads',
        '941' => 'to push ads',
        '942' => 'User Settings',
        '943' => 'User Management',
        '944' => 'Ad Management',
        '945' => 'Show unique ad IDs in personal ads?',
        '946' => 'Show unique ad IDs in business ads?',
        '947' => 'business accounts are not enabled',
        '948' => 'AD ID',
        '949' => 'Search / AD ID',
        '950' => 'Pending',
        '951' => 'Mark ad as sold and delete upgrades',
        '952' => 'Paid',
        '953' => 'Sold',
        '954' => 'Highlighted',
        '955' => 'needs activation',
        '956' => 'needs payment',
        '957' => 'Rotate',
        '958' => 'Reset filters',
        '959' => 'I agree to the %s of this website',
        '960' => 'Read our',
        '961' => 'Please agree to our Terms of Service before posting your ad',
        '962' => 'Premium plugins',
        '963' => 'Premium',
        '964' => 'Auto classifieds',
        '965' => 'See all categories',
        '966' => 'It seems that the Auto Classifieds plugin is not installed.',
        '967' => 'Please agree to our Terms of Services',
        '968' => 'See %d more categories',
        '969' => 'private user with',
        '970' => 'See more',
        '971' => 'See less',
        '972' => 'See fewer categories',
        '973' => 'See %d more categories from %s',
        '974' => 'Show ad count next to categories, in sidebar',
        '975' => 'If you experience long page loads on pages with lots of sorting options in the sidebar then setting this to "NO" will reduce the page load',
        '976' => 'Show ad count next to the sortable fields, in sidebar',
        '977' => 'This is your current account type',
        '978' => 'Registration fee',
        '979' => 'Business registration fee',
        '980' => 'Extend your registration',
        '981' => 'Your account has been disabled and you won\'t be able to post ads on our website anymore.',
        '982' => 'Personal account registration fee',
        '983' => 'Business account registration fee',
        '984' => 'The user\'s account is still active and has not been changed in any way.',
        '985' => 'It is up to you to decide what you want to do with the account.',
        '986' => 'If the outcome of the chargeback is in favor of the user then the account will be disabled automatically.',
        '987' => 'Your account will still be active until your active subscription ends.',
        '988' => 'Your account is not active on our website.',
        '989' => 'Your business account is now active on our website.',
        '990' => 'Verified Business Account',
        '991' => 'Verified account',
        '992' => 'Manage subscription',
        '993' => 'Your current account subscription',
        '994' => 'Your account will expire on',
        '995' => 'Canceling the subscription does not remove the subscription right away. It will be removed at the expiration date.',
        '996' => 'We need the user ID!',
        '997' => 'There is no user with this ID!',
        '998' => 'This user has no STRIPE client ID!',
        '999' => 'Your account subscription will renew itself',
        '1000' => 'Add personal status',
        '1001' => 'Activate account',
        '1002' => 'Remove personal status',
        '1003' => 'Deactivate account',
        '1004' => 'Form field names',
        '1005' => 'Continue reading',
        '1006' => 'One per line',
        '1007' => 'Pushed',
        '1008' => 'Flag',
        '1009' => 'Base url',
        '1010' => 'Language locale',
        '1011' => 'Deselect all',
        '1012' => 'Your Facebook OAuth Redirect URI is',
        // Add Videos Issue
        '1013' => 'Drag your videos here to upload them or',
        '1014' => 'Sorry, but you uploaded the maximum number of allowed videos',
        '1015' => 'Too many videos!',
        '1016' => 'You can upload a maximum of %s videos',
        '1017' => 'You have %s videos left',
        '1018' => 'Drop your videos here',
        '1019' => 'Click an video to select it as the main video for the ad',
        '1020' => 'Main video',
        '1021' => 'Error uploading video',
        '1022' => 'There was an error when uploading this video. Please refresh the page.',
        '1023' => 'You have uploaded the maximum number of allowed videos',
        '1024' => 'Your video is bigger than %d MB',
        '1025' => 'Video size error',
        '1026' => 'This file doesn\'t seem to be an video',
        '1027' => 'more videos',
        '1028' => 'Your video is bigger than',
        '1029' => 'Edit Videos',
        '1030' => 'Activate MyCred payments?',

        // Auto Classifieds Language strings
        'ac1' => 'Activate',
        'ac2' => 'Cars category',
        'ac3' => 'Motorcycles category',
        'ac4' => 'Campers category',
        'ac5' => 'Trucks category',
        'ac6' => 'Tyres category',
        'ac7' => 'sub-categories',
        'ac8' => 'makes',
        'ac9' => 'models',
        'ac10' => 'form fields',
        'ac11' => 'Activated',
        'ac12' => 'All categories(makes) and custom form fields from this category will be deleted!',
        'ac13' => 'Make',
        'ac14' => 'Model',
        'ac15' => 'Write Make',
        'ac16' => 'Write Model',
        'ac17' => 'Please select or write a make and a model',
        'ac18' => 'Please select or write a model name',
        'ac19' => '<b>Note:</b><br />Activating or removing a category takes a really long time.<br />Depending on what category you activate and your server\'s performance it can take several minutes to add/delete all the makes from a category.<br /><br />If you get an error it means your server has run out of memory and couldn\'t complete the task.<br />Simply click the button again and the script will resume from where it left off.',
        'ac20' => 'km',
        'ac21' => 'kW',
        'ac22' => 'hp',
        'ac23' => 'All makes',
        'ac24' => 'All models',
        'ac25' => 'First registration',
        'ac26' => 'All years',
        'ac27' => 'Any price',
        'ac28' => 'Mileage',
        'ac29' => 'Any city',
        'ac30' => 'Auto classifieds categories',
    );
    $languages = array(
            'original' => array(
                    'id' => 'original',
                    'name' => 'Default - English',
                    'words' => $words
                ),
            'english' => array(
                    'id' => 'english',
                    'name' => 'English',
                    'flag' => 'us',
                    // 'url' => 'en',
                    // 'locale' => 'en_US',
                    'words' => $words
                )
        );

    if(get_option('dolce_languages')) {
        $current_languages = get_option('dolce_languages');
        $current_languages['original'] = $languages['original'];
        update_option('dolce_languages', $current_languages);
    } else {
        update_option('dolce_languages', $languages);
    }
}