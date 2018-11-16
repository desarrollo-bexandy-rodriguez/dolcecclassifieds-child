<?php
if(!defined('error_reporting')) { define('error_reporting', '0'); }
ini_set( 'display_errors', error_reporting );
if(error_reporting == '1') { error_reporting( E_ALL ); }
if(isdolcetheme !== 1) { die(); }

/*
// if there are no cron job times then we create them.
// the cron times will be separated by 30 minutes to not load the server
if(!get_option('check_expired_no_author_ads')) update_option('check_expired_no_author_ads', time());
if(!get_option('check_expired_ads')) update_option('check_expired_ads', strtotime('+30 minutes'));
if(!get_option('check_expired_payments')) update_option('check_expired_payments', strtotime('+60 minutes'));
if(!get_option('check_expired_pm_images')) update_option('check_expired_pm_images', strtotime('+90 minutes'));


// Cron job for the ads that do not have an author. Ads where the user did not register
if(get_option('check_expired_no_author_ads') < time()) {
	update_option('check_expired_no_author_ads', strtotime('+3 hours'));
	check_expired_no_author_ads();
}
function check_expired_no_author_ads() {
	global $delete_expired_no_author_ads, $taxonomy_ad_url;
	$args = array(
			'post_type' => $taxonomy_ad_url,
			'post_status' => array('draft','private'),
			'posts_per_page' => '-1',
			'date_query' => array(
				array(
					'before'    => date('Y-m-d H:i:s', strtotime('-'.$delete_expired_no_author_ads.' day')),
					'inclusive' => true
				)
			),
			'meta_key' => 'cookie_key',
			'author' => '0'
		);
	$ads = new WP_Query($args);
	if($ads->have_posts()) {
		while($ads->have_posts()) {
			$ads->the_post();
			delete_ad(get_the_ID());
		}
	}
	wp_reset_postdata();
} // function chech_expired_no_author_ads()

// check all expired ads - just old ads
if(get_option('check_expired_ads') < time()) {
	update_option('check_expired_ads', strtotime('+3 hours'));
	check_expired_ads();
}
function check_expired_ads() {
	global $taxonomy_ad_url;
	if(get_option('should_ads_expire') == "1" && get_option('when_do_ads_expire') > 0 && get_option('expired_ads_action') != "5") {
		$args = array(
				'post_type' => $taxonomy_ad_url,
				'post_status' => array('publish', 'draft', 'private'),
				'posts_per_page' => '-1',
				'date_query' => array(
					array(
						'before'    => date('Y-m-d H:i:s', strtotime('-'.get_option('when_do_ads_expire').' day')),
						'inclusive' => true
					)
				),
				'meta_query' => array(
					array(
						'key' => 'expired',
						'compare' => 'NOT EXISTS',
						'value' => '1',
						'type' => 'NUMERIC'
					),
					array(
						'key' => 'needs_payment',
						'compare' => 'NOT EXISTS',
						'value' => '1',
						'type' => 'NUMERIC'
					),

					array(
						'key' => 'ad_posting_fee',
						'compare' => 'NOT EXISTS',
						'value' => '1',
						'type' => 'NUMERIC'
					),
					array(
						'key' => 'always_on_top',
						'compare' => 'NOT EXISTS',
						'value' => '1',
						'type' => 'NUMERIC'
					),
					array(
						'key' => 'highlighted_ad',
						'compare' => 'NOT EXISTS',
						'value' => '1',
						'type' => 'NUMERIC'
					),
					array(
						'key' => 'push_ad',
						'compare' => 'NOT EXISTS',
						'value' => '1',
						'type' => 'NUMERIC'
					)
				)
			);
		$ads = new WP_Query($args);
		if($ads->have_posts()) {
			while($ads->have_posts()) {
				$ads->the_post();
				$payment_data = get_all_payment_data(get_the_ID());

				// mark the ad as an expired ad
				update_post_meta(get_the_ID(), 'expired', '1');
				if($payment_data['paid_ads']['first']['price'] && get_option('payment_mode_active')) {
					update_post_meta(get_the_ID(), 'needs_payment', '1');
				}

				switch (get_option('expired_ads_action')) {
					// Hide the ad from the site and keep ad upgrades
					case '1':
						wp_update_post(array('ID' => get_the_ID(), 'post_status' => 'private'));
						$email_text = _d('Your ad will not be visible in the site anymore but you can repost it anytime you want.',712);
						break;

					// Hide the ad from the site but remove ad upgrades
					case '2':
						wp_update_post(array('ID' => get_the_ID(), 'post_status' => 'private'));

						delete_post_meta(get_the_ID(), 'ad_posting_fee');
						delete_post_meta(get_the_ID(), 'ad_posting_fee_expiration');
						delete_post_meta(get_the_ID(), 'ad_posting_fee_recurring');
						delete_post_meta(get_the_ID(), 'ad_posting_fee_recurring_period');

						delete_post_meta(get_the_ID(), 'always_on_top');
						delete_post_meta(get_the_ID(), 'always_on_top_expiration');
						delete_post_meta(get_the_ID(), 'always_on_top_recurring');
						delete_post_meta(get_the_ID(), 'always_on_top_recurring_period');

						delete_post_meta(get_the_ID(), 'highlighted_ad');
						delete_post_meta(get_the_ID(), 'highlighted_ad_expiration');
						delete_post_meta(get_the_ID(), 'highlighted_ad_recurring');
						delete_post_meta(get_the_ID(), 'highlighted_ad_recurring_period');

						delete_post_meta(get_the_ID(), 'push_ad');
						delete_post_meta(get_the_ID(), 'push_ad_expiration');
						delete_post_meta(get_the_ID(), 'push_ad_recurring');
						delete_post_meta(get_the_ID(), 'push_ad_recurring_period');

						delete_post_meta(get_the_ID(), 'stripe_subscription_id');

						$email_text = _d('Your ad will not be visible in the site anymore but you can repost it anytime you want.<br />If the ad had any upgrades then those wore removed.',713);
						break;

					// Keep the ad on the site and delete ad upgrades
					case '3':
						delete_post_meta(get_the_ID(), 'ad_posting_fee');
						delete_post_meta(get_the_ID(), 'ad_posting_fee_expiration');
						delete_post_meta(get_the_ID(), 'ad_posting_fee_recurring');
						delete_post_meta(get_the_ID(), 'ad_posting_fee_recurring_period');

						delete_post_meta(get_the_ID(), 'always_on_top');
						delete_post_meta(get_the_ID(), 'always_on_top_expiration');
						delete_post_meta(get_the_ID(), 'always_on_top_recurring');
						delete_post_meta(get_the_ID(), 'always_on_top_recurring_period');

						delete_post_meta(get_the_ID(), 'highlighted_ad');
						delete_post_meta(get_the_ID(), 'highlighted_ad_expiration');
						delete_post_meta(get_the_ID(), 'highlighted_ad_recurring');
						delete_post_meta(get_the_ID(), 'highlighted_ad_recurring_period');

						delete_post_meta(get_the_ID(), 'push_ad');
						delete_post_meta(get_the_ID(), 'push_ad_expiration');
						delete_post_meta(get_the_ID(), 'push_ad_recurring');
						delete_post_meta(get_the_ID(), 'push_ad_recurring_period');

						delete_post_meta(get_the_ID(), 'stripe_subscription_id');

						$email_text = _d('Your ad will still be visible in our website but if it has any upgrades then those will be removed.',714);
						break;

					// Delete the ad from the site
					case '4':
						delete_ad(get_the_ID());
						$email_text = _d('Because of that, we had to remove the ad from our website.',715);
						break;

					// Ads is marked as sold
					case '6':
						delete_post_meta(get_the_ID(), 'ad_posting_fee');
						delete_post_meta(get_the_ID(), 'ad_posting_fee_expiration');
						delete_post_meta(get_the_ID(), 'ad_posting_fee_recurring');
						delete_post_meta(get_the_ID(), 'ad_posting_fee_recurring_period');

						delete_post_meta(get_the_ID(), 'always_on_top');
						delete_post_meta(get_the_ID(), 'always_on_top_expiration');
						delete_post_meta(get_the_ID(), 'always_on_top_recurring');
						delete_post_meta(get_the_ID(), 'always_on_top_recurring_period');

						delete_post_meta(get_the_ID(), 'highlighted_ad');
						delete_post_meta(get_the_ID(), 'highlighted_ad_expiration');
						delete_post_meta(get_the_ID(), 'highlighted_ad_recurring');
						delete_post_meta(get_the_ID(), 'highlighted_ad_recurring_period');

						delete_post_meta(get_the_ID(), 'push_ad');
						delete_post_meta(get_the_ID(), 'push_ad_expiration');
						delete_post_meta(get_the_ID(), 'push_ad_recurring');
						delete_post_meta(get_the_ID(), 'push_ad_recurring_period');

						delete_post_meta(get_the_ID(), 'stripe_subscription_id');

						update_post_meta(get_the_ID(), 'sold', "sold");

						$email_text = _d('Your ad will still be visible in our website but if it has any upgrades then those will be removed.',714);
						break;

					// Ads stays the same
					case '5':
						// do nothing. sip on some coffee and relax
						break;
				} // switch

				// send email to the author
				if(get_option('send_email_ad_expired') == "1") {
					$title = get_post(get_the_ID());
					$body_vars = array(get_the_author_meta('display_name'), $email_text, home_url('/'), home_url('/'), $title->post_title);
					dolce_email(get_the_author_meta('user_email'), array(), $body_vars, '3');
				}
			} // while have_posts()
		} // if have_posts()
		wp_reset_postdata();
	} else {
		return false;
	}
} // function check_expired_ads()

// ads that have a payment expiration
if(get_option('check_expired_payments') < time()) {
	update_option('check_expired_payments', strtotime('+3 hours'));
	check_expired_payments();
}
function check_expired_payments() {
	global $taxonomy_ad_url;
	// expired "Posting an ad" payment START
	$args = array(
			'post_type' => $taxonomy_ad_url,
			'post_status' => array('publish', 'draft', 'private'),
			'posts_per_page' => '-1',
			'meta_query' => array(
				array(
					'key'     => 'ad_posting_fee',
					'value'   => '1',
					'compare' => '=',
					'type' => 'NUMERIC'
				),
				array(
					'key'     => 'ad_posting_fee_expiration',
					'compare' => 'EXISTS',
					'type' => 'NUMERIC'
				),
				array(
					'key'     => 'ad_posting_fee_expiration',
					'value'   => time(),
					'compare' => '<',
					'type' => 'NUMERIC'
				),
				array(
					'key'     => 'ad_posting_fee_recurring',
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
			delete_post_meta(get_the_ID(), 'ad_posting_fee');
			delete_post_meta(get_the_ID(), 'ad_posting_fee_expiration');
			$payment_data = get_all_payment_data(get_the_ID());
			if($payment_data['paid_ads']['first']['price'] && get_option('payment_mode_active')) {
				wp_update_post(array('ID' => get_the_ID(), 'post_status' => 'private'));
				update_post_meta(get_the_ID(), 'needs_payment', '1');
			}
		}
	}
	wp_reset_postdata();
	// expired "Posting an ad" payment END

	// expired "Always on top / Featured ads" payment START
	$args = array(
			'post_type' => $taxonomy_ad_url,
			'post_status' => array('publish', 'draft', 'private'),
			'posts_per_page' => '-1',
			'meta_query' => array(
				array(
					'key'     => 'always_on_top',
					'value'   => '1',
					'compare' => '=',
					'type' => 'NUMERIC'
				),
				array(
					'key'     => 'always_on_top_expiration',
					'compare' => 'EXISTS',
					'type' => 'NUMERIC'
				),
				array(
					'key'     => 'always_on_top_expiration',
					'value'   => time(),
					'compare' => '<',
					'type' => 'NUMERIC'
				),
				array(
					'key'     => 'always_on_top_recurring',
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
			delete_post_meta(get_the_ID(), 'always_on_top');
			delete_post_meta(get_the_ID(), 'always_on_top_expiration');
		}
	}
	wp_reset_postdata();
	// expired "Always on top / Featured ads" payment END

	// expired "Highlighted ads" payment START
	$args = array(
			'post_type' => $taxonomy_ad_url,
			'post_status' => array('publish', 'draft', 'private'),
			'posts_per_page' => '-1',
			'meta_query' => array(
				array(
					'key'     => 'highlighted_ad',
					'value'   => '1',
					'compare' => '=',
					'type' => 'NUMERIC'
				),
				array(
					'key'     => 'highlighted_ad_expiration',
					'compare' => 'EXISTS',
					'type' => 'NUMERIC'
				),
				array(
					'key'     => 'highlighted_ad_expiration',
					'value'   => time(),
					'compare' => '<',
					'type' => 'NUMERIC'
				),
				array(
					'key'     => 'highlighted_ad_recurring',
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
			delete_post_meta(get_the_ID(), 'highlighted_ad');
			delete_post_meta(get_the_ID(), 'highlighted_ad_expiration');
		}
	}
	wp_reset_postdata();
	// expired "Highlighted ads" payment END

	// expired "Push to top price" payment START
	$args = array(
			'post_type' => $taxonomy_ad_url,
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
} // function check_expired_payments()

// ads that need to be pushed to the top
$payment_push_data = get_option('payment_push_data');
$payment_push_time_hour = $payment_push_data['time']['timehour'] < 10 ? "0".$payment_push_data['time']['timehour'] : $payment_push_data['time']['timehour'];
$payment_push_time_minutes = $payment_push_data['time']['timeminutes'] < 10 ? "0".$payment_push_data['time']['timeminutes'] : $payment_push_data['time']['timeminutes'];
if(get_option('date_to_push_ads') < date("Ymd".$payment_push_time_hour.$payment_push_time_minutes, current_time('timestamp'))) {
	push_ads_each_day();
	$date_to_push_ads = date("Ymd".$payment_push_time_hour.$payment_push_time_minutes, strtotime('tomorrow'));
	update_option('date_to_push_ads', $date_to_push_ads);
}
function push_ads_each_day() {
	global $taxonomy_ad_url;
	$args = array(
			'post_type' => $taxonomy_ad_url,
			'post_status' => array('publish', 'draft', 'private'),
			'posts_per_page' => '-1',
			'meta_query' => array(
				array(
					'key'     => 'push_ad',
					'value'   => '1',
					'compare' => '=',
					'type' => 'NUMERIC'
				)
			)
		);
	$ads = new WP_Query($args);
	if($ads->have_posts()) {
		while($ads->have_posts()) { $ads->the_post();
			if(get_post_meta(get_the_ID(), 'last_pushed_date', true) < date("Ymd", current_time('timestamp'))) {
				wp_update_post(array('ID' => get_the_ID(), 'post_date' => date("Y-m-d H:i:s", current_time('timestamp'))));
				update_post_meta(get_the_ID(), 'last_pushed_date', date("Ymd", current_time('timestamp')));
			}
		}
	}
	wp_reset_postdata();
} // function push_ads_each_day()

// delete images that have not been attached to any pm reply
if(get_option('check_expired_pm_images') < time()) {
	update_option('check_expired_pm_images', strtotime('+3 hours'));
	check_expired_pm_images();
}
function check_expired_pm_images() {
	$photos_args = array(
		'author' => $user_id,
		'post_parent' => $post_id,
		'post_type'	 => "attachment",
		'post_status'	 => "any",
		'post_mime_type' => "image",
		'meta_key'		 => "private_message_temp_image",
		'meta_value'	 => "yes",
		'meta_compare'	 => '=',
		'fields'	 => 'ids',
		'date_query' => array(
				array(
					'before' => '2 days ago',
				),
			),
		);
	$photos = new WP_Query($photos_args);
	foreach ($photos->posts as $img_id) {
		wp_delete_attachment($img_id, true);
	}
} // function check_expired_pm_images()
*/


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
            } else {
                update_post_meta(get_the_ID(), 'push_ad_expiration', strtotime('+5 minutes') );
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
        $mycred->add_creds(
            'Recurring Payment',
            $author_id,
            $product_price * -1,
            $item_name
        );
    }
    return true;
} // function update_recurring_push_ad()