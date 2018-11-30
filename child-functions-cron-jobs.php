<?php
if(!defined('error_reporting')) { define('error_reporting', '0'); }
ini_set( 'display_errors', error_reporting );
if(error_reporting == '1') { error_reporting( E_ALL ); }
if(isdolcetheme !== 1) { die(); }

if(!get_option('check_expired_push_ads')) update_option('check_expired_push_ads', strtotime('+1 minutes'));
if(!get_option('check_expired_recurring_push_ads')) update_option('check_expired_recurring_push_ads', strtotime('+2 minutes'));
// revisar ads push cada 5 minutos
if(get_option('check_expired_push_ads') < time()) {
    update_option('check_expired_push_ads', strtotime('+5 minutes'));
    check_expired_push_ads();
}

function check_expired_push_ads() {
    global $taxonomy_ad_url;

    // expired "Push to top price" payment START
    $args = array(
            'post_type' => 'item',
            'post_status' => array('publish', 'draft', 'private'),
            'posts_per_page' => '-1',
            'meta_query' => array(
                array(
                    'key'     => 'push_ad',
                    'value'   => '1',
                    'compare' => '=',
                    'type' => 'NUMERIC'
                ),
                array(
                    'key'     => 'push_ad_expiration',
                    'compare' => 'EXISTS',
                    'type' => 'NUMERIC'
                ),
                array(
                    'key'     => 'push_ad_expiration',
                    'value'   => time(),
                    'compare' => '<',
                    'type' => 'NUMERIC'
                ),
                array(
                    'key'     => 'push_ad_recurring',
                    'value'   => '1',
                    'compare' => 'NOT EXISTS',
                    'type' => 'NUMERIC'
                ),
            )
        );
    $ads = new WP_Query($args);
    if($ads->have_posts()) {
        while($ads->have_posts()) {
            $ads->the_post();
            delete_post_meta(get_the_ID(), 'push_ad');
            delete_post_meta(get_the_ID(), 'push_ad_expiration');
        }
    }
    wp_reset_postdata();
    // expired "Push to top price" payment END
}  // function check_expired_push_ads()

// revisar ads push cada 5 minutos
if(get_option('check_expired_recurring_push_ads') < time()) {
    update_option('check_expired_recurring_push_ads', strtotime('+1 minutes'));
    check_expired_recurring_push_ads();
}

function check_expired_recurring_push_ads() {
    global $taxonomy_ad_url;

    // expired "Push to top price" payment START
    $args = array(
            'post_type' => 'item',
            'post_status' => array('publish', 'draft', 'private'),
            'posts_per_page' => '-1',
            'meta_query' => array(
                array(
                    'key'     => 'push_ad',
                    'value'   => '1',
                    'compare' => '=',
                    'type' => 'NUMERIC'
                ),
                array(
                    'key'     => 'push_ad_expiration',
                    'compare' => 'EXISTS',
                    'type' => 'NUMERIC'
                ),
                array(
                    'key'     => 'push_ad_expiration',
                    'value'   => time(),
                    'compare' => '<',
                    'type' => 'NUMERIC'
                ),
                array(
                    'key'     => 'push_ad_recurring',
                    'value'   => '1',
                    'compare' => '=',
                    'type' => 'NUMERIC'
                ),
            )
        );
    $ads = new WP_Query($args);
    if($ads->have_posts()) {
        while($ads->have_posts()) {
            $ads->the_post();
            
            if ( !update_recurring_push_ad(get_the_ID()) ) {
                delete_post_meta(get_the_ID(), 'push_ad');
                delete_post_meta(get_the_ID(), 'push_ad_expiration');
                delete_post_meta(get_the_ID(), 'push_ad_recurring');
                delete_post_meta(get_the_ID(), 'push_ad_recurring_period');
            } else {
				wp_update_post(array('ID' => get_the_ID(), 'post_date' => date("Y-m-d H:i:s", current_time('timestamp'))));
				update_post_meta(get_the_ID(), 'last_pushed_date', date("Y-m-d H:i:s", current_time('timestamp')));
				$push_ad_recurring_period = get_post_meta(get_the_ID(), 'push_ad_recurring_period', true);
                update_post_meta(get_the_ID(), 'push_ad_expiration', strtotime("+$push_ad_recurring_period") );
                delete_post_meta(get_the_ID(), 'needs_payment');
            }
            
            
        }
    }
    wp_reset_postdata();
    // expired "Push to top price" payment END
}  // function check_expired_recurring_push_ads()

function update_recurring_push_ad($post_id = null) {
    $ad_to_process = get_post($post_id);
    $author_id = $ad_to_process->post_author;
    $payment_data = child_get_all_payment_data($post_id);
    $item_name = "Upgrade - Push to top";
    $product_price = $payment_data['push']['first']['price'];
    // RETRIEVE client from STRIPE
    $mycred = mycred();
    // Make sure user is not excluded
    if ( ! $mycred->exclude_user( $author_id ) ) {
        // get users balance
        $balance = $mycred->get_users_balance( $author_id );
        // error if user not have founds
        if($product_price >  $balance)
            return false;

        // Adjust balance with a log entry
        //$mycred->add_creds( 'Recurring Payment', $author_id, $product_price * -1, $item_name );
        $amount  = $product_price * -1;
        $mycred->update_users_balance( $author_id, $amount );
    }
    return true;
} // function update_recurring_push_ad()