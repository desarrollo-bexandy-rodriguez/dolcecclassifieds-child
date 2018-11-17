<?php
if(!defined('error_reporting')) { define('error_reporting', '0'); }
ini_set( 'display_errors', error_reporting );
if(error_reporting == '1') { error_reporting( E_ALL ); }
if(isdolcetheme !== 1) { die(); }

/********************* Save settings including MyCred Payment **************************************/
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

            if((get_option('payment_paypal') == "1" || get_option('payment_stripe') == "1" || get_option('payment_mycred') == "1") && $activate_payments) {
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

        case 'cancel-mycred-subscription':
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


            $mycred_subscription_id = get_post_meta($post_id, 'push_ad_recurring', true);
            if(!$mycred_subscription_id) {
                die(json_encode(array('status' => 'err', 'err_msg' => _d('There is no subscription id for this item id!',443))));
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

            delete_post_meta($post_id, 'push_ad');
            delete_post_meta($post_id, 'push_ad_expiration');
            delete_post_meta($post_id, 'push_ad_recurring');
            delete_post_meta($post_id, 'push_ad_recurring_period');

            die(json_encode(array('status' => 'ok', 'msg' => $msg)));
            break; // cancel-mycred-subscription

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