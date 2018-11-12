<?php
if(!defined('error_reporting')) { define('error_reporting', '0'); }
ini_set( 'display_errors', error_reporting );
if(error_reporting == '1') { error_reporting( E_ALL ); }
if(isdolcetheme !== 1) { die(); }

function child_generate_payment_buttons($product_id, $post_id="") {
	if(get_option('payment_paypal') == "1") {
		generate_paypal_payment_button($product_id, $post_id);
	}
	if(get_option('payment_stripe') == "1") {
		generate_stripe_payment_button($product_id, $post_id);
	}
}

function child_generate_paypal_payment_button($product_id, $post_id="") {
	global $payment_duration_types;
	$amount_type = "amount";
	$cmd = "_xclick";
	if(in_array($product_id, array("5", "6"))) { // user registration
		$return = $post_id ? get_author_posts_url($post_id) : '';
	} else {
		$return = $post_id ? get_post_permalink($post_id) : '';
	}
	$notify_url = get_template_directory_uri().'/IPN.php?processor=paypal';
	$custom = $post_id;
	$type = "hidden";
	if(in_array($product_id, array("5", "6"))) { // user registration
		$payment_data = get_all_payment_data();
	} else {
		$payment_data = get_all_payment_data($post_id);
	}

	switch ($product_id) {
		case '1': // posting fee
			$item_name = _d('Ad posting fee',435);
			$amount = $payment_data['paid_ads']['first']['price'];
			$payment_paid_ads_duration_type = $payment_data['paid_ads']['first']['durationtype'];
			if($payment_data['paid_ads']['first']['recurring'] == "1") {
				if(get_post_meta($post_id, 'ad_posting_fee_expiration', true)) {
					$ad_posting_fee_expiration = get_post_meta($post_id, 'ad_posting_fee_expiration', true);
					$ad_posting_fee_expiration_timestamp = $ad_posting_fee_expiration - current_time('timestamp');
					$p1 = round($ad_posting_fee_expiration_timestamp / (60 * 60 * 24));
					$t1 = "d";
					if($p1 > 90) {
						$p1 = floor($ad_posting_fee_expiration_timestamp / (60 * 60 * 24 * 7));
						$t1 = "w";
						$p2 = round(($ad_posting_fee_expiration_timestamp - $p1 * 60 * 60 * 24 * 7) / (60 * 60 * 24));
						if($p2 > 0) {
							$form_subscription_fields .= '<input type="'.$type.'" name="a2" value="0" />'; // trial period price
							$form_subscription_fields .= '<input type="'.$type.'" name="p2" value="'.$p2.'" />'; // Subscription duration
							$form_subscription_fields .= '<input type="'.$type.'" name="t2" value="d" />'; // Regular subscription units of duration
						}
					}
					$form_subscription_fields .= '<input type="'.$type.'" name="a1" value="0" />'; // trial period price
					$form_subscription_fields .= '<input type="'.$type.'" name="p1" value="'.$p1.'" />'; // Subscription duration
					$form_subscription_fields .= '<input type="'.$type.'" name="t1" value="'.$t1.'" />'; // Regular subscription units of duration
				}
				$cmd = "_xclick-subscriptions";
				$amount_type = "a3"; // Regular subscription price
				$form_subscription_fields .= '<input type="'.$type.'" name="p3" value="'.$payment_data['paid_ads']['first']['duration'].'" />'; // Subscription duration
				$form_subscription_fields .= '<input type="'.$type.'" name="t3" value="'.$payment_duration_types[$payment_paid_ads_duration_type]['2'].'" />'; // Regular subscription units of duration
				$form_subscription_fields .= '<input type="'.$type.'" name="src" value="1" />'; // Recurring payments (yes or no)
				$form_subscription_fields .= '<input type="'.$type.'" name="modify" value="1" />'; // allows subscribers to sign up for new subscriptions and modify their current subscriptions
			}
			break;
		
		case '2': // Always on top
			$item_name = _d('Upgrade',517)." - "._d('Always on top',240);
			$amount = $payment_data['always_on_top']['first']['price'];
			$payment_always_on_top_duration_type = $payment_data['always_on_top']['first']['durationtype'];
			if($payment_data['always_on_top']['first']['recurring'] == "1") {
				if(get_post_meta($post_id, 'always_on_top_expiration', true)) {
					$always_on_top_expiration = get_post_meta($post_id, 'always_on_top_expiration', true);
					$always_on_top_expiration_timestamp = $always_on_top_expiration - current_time('timestamp');
					$p1 = round($always_on_top_expiration_timestamp / (60 * 60 * 24));
					$t1 = "d";
					if($p1 > 90) {
						$p1 = floor($always_on_top_expiration_timestamp / (60 * 60 * 24 * 7));
						$t1 = "w";
						$p2 = round(($always_on_top_expiration_timestamp - $p1 * 60 * 60 * 24 * 7) / (60 * 60 * 24));
						if($p2 > 0) {
							$form_subscription_fields .= '<input type="'.$type.'" name="a2" value="0" />'; // trial period price
							$form_subscription_fields .= '<input type="'.$type.'" name="p2" value="'.$p2.'" />'; // Subscription duration
							$form_subscription_fields .= '<input type="'.$type.'" name="t2" value="d" />'; // Regular subscription units of duration
						}
					}
					$form_subscription_fields .= '<input type="'.$type.'" name="a1" value="0" />'; // trial period price
					$form_subscription_fields .= '<input type="'.$type.'" name="p1" value="'.$p1.'" />'; // Subscription duration
					$form_subscription_fields .= '<input type="'.$type.'" name="t1" value="'.$t1.'" />'; // Regular subscription units of duration
				}
				$cmd = "_xclick-subscriptions";
				$amount_type = "a3"; // Regular subscription price
				$form_subscription_fields .= '<input type="'.$type.'" name="p3" value="'.$payment_data['always_on_top']['first']['duration'].'" />'; // Subscription duration
				$form_subscription_fields .= '<input type="'.$type.'" name="t3" value="'.$payment_duration_types[$payment_always_on_top_duration_type]['2'].'" />'; // Regular subscription units of duration
				$form_subscription_fields .= '<input type="'.$type.'" name="src" value="1" />'; // Recurring payments (yes or no)
				$form_subscription_fields .= '<input type="'.$type.'" name="modify" value="1" />'; // allows subscribers to sign up for new subscriptions and modify their current subscriptions
			}
			break;
		
		case '3': // Highlighted ads
			$item_name = _d('Upgrade',517)." - "._d('Highlighted ads',244);
			$amount = $payment_data['highlighted_ad']['first']['price'];
			$payment_highlighted_ad_duration_type = $payment_data['highlighted_ad']['first']['durationtype'];
			if($payment_data['highlighted_ad']['first']['recurring'] == "1") {
				if(get_post_meta($post_id, 'highlighted_ad_expiration', true)) {
					$highlighted_ad_expiration = get_post_meta($post_id, 'highlighted_ad_expiration', true);
					$highlighted_ad_expiration_timestamp = $highlighted_ad_expiration - current_time('timestamp');
					$p1 = round($highlighted_ad_expiration_timestamp / (60 * 60 * 24));
					$t1 = "d";
					if($p1 > 90) {
						$p1 = floor($highlighted_ad_expiration_timestamp / (60 * 60 * 24 * 7));
						$t1 = "w";
						$p2 = round(($highlighted_ad_expiration_timestamp - $p1 * 60 * 60 * 24 * 7) / (60 * 60 * 24));
						if($p2 > 0) {
							$form_subscription_fields .= '<input type="'.$type.'" name="a2" value="0" />'; // trial period price
							$form_subscription_fields .= '<input type="'.$type.'" name="p2" value="'.$p2.'" />'; // Subscription duration
							$form_subscription_fields .= '<input type="'.$type.'" name="t2" value="d" />'; // Regular subscription units of duration
						}
					}
					$form_subscription_fields .= '<input type="'.$type.'" name="a1" value="0" />'; // trial period price
					$form_subscription_fields .= '<input type="'.$type.'" name="p1" value="'.$p1.'" />'; // Subscription duration
					$form_subscription_fields .= '<input type="'.$type.'" name="t1" value="'.$t1.'" />'; // Regular subscription units of duration
				}
				$cmd = "_xclick-subscriptions";
				$amount_type = "a3"; // Regular subscription price
				$form_subscription_fields .= '<input type="'.$type.'" name="p3" value="'.$payment_data['highlighted_ad']['first']['duration'].'" />'; // Subscription duration
				$form_subscription_fields .= '<input type="'.$type.'" name="t3" value="'.$payment_duration_types[$payment_highlighted_ad_duration_type]['2'].'" />'; // Regular subscription units of duration
				$form_subscription_fields .= '<input type="'.$type.'" name="src" value="1" />'; // Recurring payments (yes or no)
				$form_subscription_fields .= '<input type="'.$type.'" name="modify" value="1" />'; // allows subscribers to sign up for new subscriptions and modify their current subscriptions
			}
			break;
		
		case '4': // Push to top
			$item_name = _d('Upgrade',517)." - "._d('Push to top',437);
			$amount = $payment_data['push']['first']['price'];
			$payment_push_duration_type = $payment_data['push']['first']['durationtype'];
			if($payment_data['push']['first']['recurring'] == "1") {
				if(get_post_meta($post_id, 'push_ad_expiration', true)) {
					$push_ad_expiration = get_post_meta($post_id, 'push_ad_expiration', true);
					$push_ad_expiration_timestamp = $push_ad_expiration - current_time('timestamp');
					$p1 = round($push_ad_expiration_timestamp / (60 * 60 * 24));
					$t1 = "d";
					if($p1 > 90) {
						$p1 = floor($push_ad_expiration_timestamp / (60 * 60 * 24 * 7));
						$t1 = "w";
						$p2 = round(($push_ad_expiration_timestamp - $p1 * 60 * 60 * 24 * 7) / (60 * 60 * 24));
						if($p2 > 0) {
							$form_subscription_fields .= '<input type="'.$type.'" name="a2" value="0" />'; // trial period price
							$form_subscription_fields .= '<input type="'.$type.'" name="p2" value="'.$p2.'" />'; // Subscription duration
							$form_subscription_fields .= '<input type="'.$type.'" name="t2" value="d" />'; // Regular subscription units of duration
						}
					}
					$form_subscription_fields .= '<input type="'.$type.'" name="a1" value="0" />'; // trial period price
					$form_subscription_fields .= '<input type="'.$type.'" name="p1" value="'.$p1.'" />'; // Subscription duration
					$form_subscription_fields .= '<input type="'.$type.'" name="t1" value="'.$t1.'" />'; // Regular subscription units of duration
				}
				$cmd = "_xclick-subscriptions";
				$amount_type = "a3"; // Regular subscription price
				$form_subscription_fields .= '<input type="'.$type.'" name="p3" value="'.$payment_data['push']['first']['duration'].'" />'; // Subscription duration
				$form_subscription_fields .= '<input type="'.$type.'" name="t3" value="'.$payment_duration_types[$payment_push_duration_type]['2'].'" />'; // Regular subscription units of duration
				$form_subscription_fields .= '<input type="'.$type.'" name="src" value="1" />'; // Recurring payments (yes or no)
				$form_subscription_fields .= '<input type="'.$type.'" name="modify" value="1" />'; // allows subscribers to sign up for new subscriptions and modify their current subscriptions
			}
			break;

		case '5': // User registration PERSONAL
			$item_name = _d('Registration fee',978);
			$amount = $payment_data['user_reg']['personal']['first']['price'];
			$payment_user_reg_duration_type = $payment_data['user_reg']['personal']['first']['durationtype'];
			if($payment_data['user_reg']['personal']['first']['recurring'] == "1") {
				if(get_user_meta($post_id, 'user_reg_expiration', true) && get_user_meta($post_id, 'user_type', true) == "personal") {
					$user_reg_expiration = get_user_meta($post_id, 'user_reg_expiration', true);
					$user_reg_expiration_timestamp = $user_reg_expiration - current_time('timestamp');
					$p1 = round($user_reg_expiration_timestamp / (60 * 60 * 24));
					$t1 = "d";
					if($p1 > 90) {
						$p1 = floor($user_reg_expiration_timestamp / (60 * 60 * 24 * 7));
						$t1 = "w";
						$p2 = round(($user_reg_expiration_timestamp - $p1 * 60 * 60 * 24 * 7) / (60 * 60 * 24));
						if($p2 > 0) {
							$form_subscription_fields .= '<input type="'.$type.'" name="a2" value="0" />'; // trial period price
							$form_subscription_fields .= '<input type="'.$type.'" name="p2" value="'.$p2.'" />'; // Subscription duration
							$form_subscription_fields .= '<input type="'.$type.'" name="t2" value="d" />'; // Regular subscription units of duration
						}
						// $user_reg_expiration_timestamp
					}
					$form_subscription_fields .= '<input type="'.$type.'" name="a1" value="0" />'; // trial period price
					$form_subscription_fields .= '<input type="'.$type.'" name="p1" value="'.$p1.'" />'; // Subscription duration
					$form_subscription_fields .= '<input type="'.$type.'" name="t1" value="'.$t1.'" />'; // Regular subscription units of duration
				}
				$cmd = "_xclick-subscriptions";
				$amount_type = "a3"; // Regular subscription price
				$form_subscription_fields .= '<input type="'.$type.'" name="p3" value="'.$payment_data['user_reg']['personal']['first']['duration'].'" />'; // Subscription duration
				$form_subscription_fields .= '<input type="'.$type.'" name="t3" value="'.$payment_duration_types[$payment_user_reg_duration_type]['2'].'" />'; // Regular subscription units of duration
				$form_subscription_fields .= '<input type="'.$type.'" name="src" value="1" />'; // Recurring payments (yes or no)
				$form_subscription_fields .= '<input type="'.$type.'" name="modify" value="1" />'; // allows subscribers to sign up for new subscriptions and modify their current subscriptions
			}
			break;

		case '6': // User registration BUSINESS
			$item_name = _d('Business registration fee',979);
			$amount = $payment_data['user_reg']['business']['first']['price'];
			$payment_user_reg_duration_type = $payment_data['user_reg']['business']['first']['durationtype'];
			if($payment_data['user_reg']['business']['first']['recurring'] == "1") {
				if(get_user_meta($post_id, 'user_reg_expiration', true) && get_user_meta($post_id, 'user_type', true) == "business") {
					$user_reg_expiration = get_user_meta($post_id, 'user_reg_expiration', true);
					$user_reg_expiration_timestamp = $user_reg_expiration - current_time('timestamp');
					$p1 = round($user_reg_expiration_timestamp / (60 * 60 * 24));
					$t1 = "d";
					if($p1 > 90) {
						$p1 = floor($user_reg_expiration_timestamp / (60 * 60 * 24 * 7));
						$t1 = "w";
						$p2 = round(($user_reg_expiration_timestamp - $p1 * 60 * 60 * 24 * 7) / (60 * 60 * 24));
						if($p2 > 0) {
							$form_subscription_fields .= '<input type="'.$type.'" name="a2" value="0" />'; // trial period price
							$form_subscription_fields .= '<input type="'.$type.'" name="p2" value="'.$p2.'" />'; // Subscription duration
							$form_subscription_fields .= '<input type="'.$type.'" name="t2" value="d" />'; // Regular subscription units of duration
						}
						// $user_reg_expiration_timestamp
					}
					$form_subscription_fields .= '<input type="'.$type.'" name="a1" value="0" />'; // trial period price
					$form_subscription_fields .= '<input type="'.$type.'" name="p1" value="'.$p1.'" />'; // Subscription duration
					$form_subscription_fields .= '<input type="'.$type.'" name="t1" value="'.$t1.'" />'; // Regular subscription units of duration
				}
				$cmd = "_xclick-subscriptions";
				$amount_type = "a3"; // Regular subscription price
				$form_subscription_fields .= '<input type="'.$type.'" name="p3" value="'.$payment_data['user_reg']['business']['first']['duration'].'" />'; // Subscription duration
				$form_subscription_fields .= '<input type="'.$type.'" name="t3" value="'.$payment_duration_types[$payment_user_reg_duration_type]['2'].'" />'; // Regular subscription units of duration
				$form_subscription_fields .= '<input type="'.$type.'" name="src" value="1" />'; // Recurring payments (yes or no)
				$form_subscription_fields .= '<input type="'.$type.'" name="modify" value="1" />'; // allows subscribers to sign up for new subscriptions and modify their current subscriptions
			}
			break;
	}

	if(get_option("payment_paypal_sandbox") == "1") {
		$form_url = "https://www.sandbox.paypal.com/cgi-bin/webscr";
		$business = get_option('payment_paypal_sandbox_address');
	} else {
		$form_url = "https://www.paypal.com/cgi-bin/webscr";
		$business = get_option('payment_paypal_address');
	}
	$form  = '<form action="'.$form_url.'" method="post" target="target_'.$product_id.'" class="paypal-form">';
	$form .= '<input type="'.$type.'" name="cmd" value="'.$cmd.'" />';
	$form .= '<input type="'.$type.'" name="notify_url" value="'.$notify_url.'" />';
	$form .= '<input type="'.$type.'" name="currency_code" value="'.get_option('payment_currency').'" />';
	// $form .= '<input type="'.$type.'" name="lc" value="US" />';
	$form .= '<input type="'.$type.'" name="business" value="'.$business.'" />';
	$form .= '<input type="'.$type.'" name="return" value="'.$return.'" />';
	$form .= '<input type="'.$type.'" name="cancel_return" value="'.$return.'" />';
	$form .= '<input type="'.$type.'" name="custom" value="'.$custom.'" />';
	$form .= '<input type="hidden" name="no_note" value="1" />';
	$form .= '<input type="hidden" name="no_shipping" value="1" />';

	$form .= '<input type="'.$type.'" name="item_name" value="'.$item_name.'" />';
	$form .= '<input type="'.$type.'" name="item_number" value="'.$product_id.'" />';
	$form .= '<input type="'.$type.'" name="'.$amount_type.'" value="'.$amount.'" />';
	$form .= $form_subscription_fields;
	$form .= '<button type="submit" class="pay-button pay-button-paypal round-corners-button rad25"><span class="icon icon-paypal"></span> PayPal</button>';
	$form .= '</form>';
	echo $form;
}
function child_generate_stripe_payment_button($product_id, $post_id="") {
	$current_user = wp_get_current_user();
	if(in_array($product_id, array("5", "6"))) {
		$user_email = $current_user->user_email;
		$payment_data = get_all_payment_data();
	} else {
		$ad_to_process = get_post($post_id);
		$user_email = get_the_author_meta('user_email', $ad_to_process->post_author);
		$payment_data = get_all_payment_data($post_id);
	}

	$payment_currency = get_option('payment_currency');
	$payment_stripe_rememberme = get_option('payment_stripe_rememberme');
	$payment_stripe_sandbox = get_option('payment_stripe_sandbox');
	if(get_option('payment_stripe_sandbox') == "1") {
		// sandbox activated
		$stripe_secret_key = get_option('payment_stripe_test_secret_key');
		$stripe_publishable_key = get_option('payment_stripe_test_publishable_key');
	} else {
		// sandbox disabled
		$stripe_secret_key = get_option('payment_stripe_live_secret_key');
		$stripe_publishable_key = get_option('payment_stripe_live_publishable_key');
	}

	switch ($product_id) {
		case '1': // posting fee
			$item_name = _d('Ad posting fee',435);
			$amount = $payment_data['paid_ads']['first']['price'] * 100;
			$payment_recurring = $payment_data['paid_ads']['first']['recurring'];
			break;
		
		case '2': // Always on top
			$item_name = _d('Upgrade',517)." - "._d('Always on top',240);
			$amount = $payment_data['always_on_top']['first']['price'] * 100;
			$payment_recurring = $payment_data['always_on_top']['first']['recurring'];
			break;
		
		case '3': // Highlighted ads
			$item_name = _d('Upgrade',517)." - "._d('Highlighted ads',244);
			$amount = $payment_data['highlighted_ad']['first']['price'] * 100;
			$payment_recurring = $payment_data['highlighted_ad']['first']['recurring'];
			break;
		
		case '4': // Push to top
			$item_name = _d('Upgrade',517)." - "._d('Push to top',437);
			$amount = $payment_data['push']['first']['price'] * 100;
			$payment_recurring = $payment_data['push']['first']['recurring'];
			break;

		case '5': // Registration fee PERSONAL
			$item_name = _d('Registration fee',978);
			$amount = $payment_data['user_reg']['personal']['first']['price'] * 100;
			$payment_recurring = $payment_data['user_reg']['personal']['first']['recurring'];
			break;

		case '6': // Registration fee BUSINESS
			$item_name = _d('Business registration fee',979);
			$amount = $payment_data['user_reg']['business']['first']['price'] * 100;
			$payment_recurring = $payment_data['user_reg']['business']['first']['recurring'];
			break;

	}
	?>

	<button id="pay_button_<?=$product_id?>" class="pay-button pay-button-credit-card round-corners-button rad25"><span class="icon icon-credit-card"></span> <?=_d('Credit Card',705)?></button>

	<script type="text/javascript">
	jQuery(document).ready(function($) {
		var handler<?=$product_id?> = StripeCheckout.configure({
			key: '<?=$stripe_publishable_key?>',
			image: '<?=get_template_directory_uri()?>/img/lock.png',
			token: function(token) {
				if(!$('#overlay_for_<?=$product_id?>').length) {
					$('body').append('<div class="overlay hide" id="overlay_for_<?=$product_id?>"></div>');
				}
				if(!$('#message_for_<?=$product_id?>').length) {
					$('body').append('<div class="stripe-payment-processing-message-container hide" id="message_for_<?=$product_id?>"><div class="close r"><span class="icon icon-cancel"></span> <?=addslashes(_d('close',195))?></div><div class="clear5"></div><div class="stripe-payment-processing-message rad5 shadow text-center"><span class="text"><?=addslashes(_d('Processing payment!',706))?></span><div class="clear30"></div><img class="icon loader" src="<?=get_template_directory_uri()?>/plugins/private-messages/loader.svg" alt="" /><span class="icon icon-for-err icon-cancel hide no-selection"></span><span class="icon icon-for-ok icon-checkmark hide no-selection"></span><div class="wait"><?=addslashes(_d('please wait...',707))?></div></div></div>');
				}
				$('#overlay_for_<?=$product_id?>').css({
					width: $('body').outerWidth(),
					height: $('body').outerHeight(),
					opacity: '0.9'
				}).fadeIn('200');

				$('#message_for_<?=$product_id?>').show();
				var top = (($(window).outerHeight() - $('#message_for_<?=$product_id?>').outerHeight()) / 2);
				var left = (($(window).outerWidth() - $('#message_for_<?=$product_id?>').outerWidth()) / 2);
				$('#message_for_<?=$product_id?>').css({'top': top, 'left': left});

				var vars = {
						'token': token.id,
						'ip': token.client_ip,
						'email': token.email,
						'item_nr': <?=$product_id?>,
						'post_id': <?=$post_id?>
					};
				$.ajax({
					type: "POST",
					url: wpvars.wpthemeurl+'/IPN.php?processor=stripe',
					data: { vars: vars },
					cache: false,
					timeout: 30000, // in milliseconds
					success: function(raw_paydata) {
						var is_err = false;
						var is_json = true;
						try {
							var paydata = JSON.parse(raw_paydata);
						} catch(err) {
							is_json = false;
						}
						if(is_json && paydata != null) {
							if(paydata.status == 'ok') {
								$('#message_for_<?=$product_id?> .stripe-payment-processing-message .text').html(paydata.msg);
								$('#message_for_<?=$product_id?> .stripe-payment-processing-message .wait').html('Refreshing the page in 2 seconds...');
								$('#message_for_<?=$product_id?> .stripe-payment-processing-message .icon').hide();
								$('#message_for_<?=$product_id?> .stripe-payment-processing-message .icon-for-ok').fadeIn('fast');
								setTimeout(function() {
									<?php if(in_array($product_id, array("5", "6"))) { ?>
										location.reload();
									<?php } else { ?>
										window.location = '<?=get_post_permalink($post_id)?>';
									<?php } ?>
								}, 2000);
							} else {
								is_err = true;
							}//if error
						}

						if(is_err || !is_json || !raw_paydata) {
							$('#message_for_<?=$product_id?> .stripe-payment-processing-message .text').text('<?=_d('Payment error!',631)?>');
							if(is_json) {
								if(paydata.msg) {
									$('#message_for_<?=$product_id?> .stripe-payment-processing-message .text').append('<br />'+paydata.msg);
								}
							}
							$('#message_for_<?=$product_id?> .stripe-payment-processing-message').find('.icon, .wait').hide();
							$('#message_for_<?=$product_id?> .stripe-payment-processing-message .icon-for-err').fadeIn('fast');
						}
					},
					error: function(request, status, err) {
						$('#message_for_<?=$product_id?> .stripe-payment-processing-message .text').text('<?=_d('Payment error!',631)?>');
						$('#message_for_<?=$product_id?> .stripe-payment-processing-message').find('.icon, .wait').hide();
						$('#message_for_<?=$product_id?> .stripe-payment-processing-message .icon-for-err').fadeIn('fast');
					}
				});
			}
		});

		$('#pay_button_<?=$product_id?>').on('click', function(e) {
			e.preventDefault();
			// Open Checkout with further options
			handler<?=$product_id?>.open({
				name: '<?=addslashes($item_name)?>',
				// description: '2 widgets',
				currency: "<?=strtolower($payment_currency)?>",
				<?php
					if($user_email)
						echo "email: '".$user_email."',"."\n";

					if($payment_stripe_rememberme == "2")
						echo "allowRememberMe: false,"."\n";

					if($payment_recurring == "1") {
						echo "panelLabel: '"._d('Subscribe for',708)." {{amount}}',"."\n";
					} else {
						echo "panelLabel: '"._d('Pay',472)." {{amount}}',"."\n";
					}
				?>
				amount: <?=$amount?>
			});
		});

		// Close Checkout on page navigation
		$(window).on('popstate', function() {
			handler<?=$product_id?>.close();
		});

		$('body').on('click', '#message_for_<?=$product_id?> .close', function(event) {
			$('#message_for_<?=$product_id?>, #overlay_for_<?=$product_id?>').fadeOut('fast', function() {
				$(this).remove();
			});
		});

		$(window).on('resize', function(){
			if($('#overlay_for_<?=$product_id?>').is(':visible')) {
				$('#overlay_for_<?=$product_id?>').css({
					width: $('body').outerWidth(),
					height: $('body').outerHeight()
				});
			}

			if($('#message_for_<?=$product_id?>').is(':visible')) {
				var top = (($(window).outerHeight() - $('#message_for_<?=$product_id?>').outerHeight()) / 2);
				var left = (($(window).outerWidth() - $('#message_for_<?=$product_id?>').outerWidth()) / 2);
				$('#message_for_<?=$product_id?>').css({'top': top, 'left': left});
			}
		});
	});
	</script>
	<?php
}

function child_generate_stripe_cancel_subscription_button($item_id, $post_id) { ?>
	<script type="text/javascript">
		jQuery(document).ready(function($) {
			$('.cancel-subscription-button-stripe').on('click', function(event) {
				var button = $(this);
				var item_id = $(this).data('item-id');
				if(button.hasClass('cancel-subscription-button-noclick')) {
					return false;
				} else {
					$(this).addClass('cancel-subscription-button-noclick cancel-subscription-button-active');
				}

				button.find('.icon').hide();
				button.find('.text').text(button.data('saving')).parent().find('.icon-for-saving').show();

				var form_data = [];
				form_data.push({name: 'item_id', value: item_id});
				form_data.push({name: 'post_id', value: '<?=$post_id?>'});
				$.ajax({
					type: "POST",
					url: wpvars.wpchildthemeurl+'/ajax/child-save-settings.php',
					data: { action: 'cancel-stripe-subscription', form_data: form_data },
					cache: false,
					timeout: 30000, // in milliseconds
					success: function(raw_data) {
						var is_err = false;
						var is_json = true;
						try {
							var data = JSON.parse(raw_data);
						} catch(err) {
							is_json = false;
						}
						if(is_json && data != null) {
							if(data.status == 'ok') {
								<?php if(in_array($item_id, array("5", "6"))) { ?>
									location.reload();
								<?php } else { ?>
									if(data.msg) {
										button.parents('.product').find('.purchased').fadeOut('fast', function() {
											$(this).html(data.msg).fadeIn('200');
										});
									}
								<?php } ?>

								button.find('.icon').hide();
								button.find('.text').text(button.data('saved')).parent().find('.icon-for-saved').show();
								<?php if(!in_array($item_id, array("5", "6"))) { ?>
								setTimeout(function() {
									button.parent().fadeOut('fast');
								}, 3000);
								<?php } ?>
							} else {
								is_err = true;
							}//if error
						}

						if(is_err || !is_json || !raw_data) {
							button.find('.icon').hide();
							button.removeClass('cancel-subscription-button-active').find('.text').text(button.data('error')).parent().find('.icon-for-error').show();
							setTimeout(function() {
								button.find('.icon').hide();
								button.removeClass('cancel-subscription-button-noclick').find('.text').text(button.data('default')).parent().find('.icon-for-default').show();
							}, 3000);
						}
					},
					error: function(request, status, err) {
						button.find('.icon').hide();
						button.find('.text').text(button.data('error')).parent().find('.icon-for-error').show();
						setTimeout(function() {
							button.find('.icon').hide();
							button.removeClass('cancel-subscription-button-active').find('.text').text(button.data('default')).parent().find('.icon-for-default').show();
						}, 3000);
					}
				});
			});
		});
	</script>
	<div class="col-100 text-center">
		<div class="cancel-subscription-button cancel-subscription-button-stripe round-corners-button rad25" data-item-id="<?=$item_id?>" data-saving="<?=_d('Canceling',709)?>" data-saved="<?=_d('Subscription canceled',601)?>" data-default="<?=_d('Cancel subscription',710)?>" data-error="<?=_d('Error',94)?>">
			<span class="text"><?=_d('Cancel subscription',710)?></span>
			<span class="icon icon-for-default icon-arrow-right"></span>
			<svg version="1.1" class="icon icon-for-saving loader r hide" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 50 50" style="enable-background:new 0 0 50 50;" xml:space="preserve"><path fill="#000" d="M43.935,25.145c0-10.318-8.364-18.683-18.683-18.683c-10.318,0-18.683,8.365-18.683,18.683h4.068c0-8.071,6.543-14.615,14.615-14.615c8.072,0,14.615,6.543,14.615,14.615H43.935z"><animateTransform attributeType="xml" attributeName="transform" type="rotate" from="0 25 25" to="360 25 25" dur="0.6s" repeatCount="indefinite"/></path></svg>
			<span class="icon icon-for-saved icon-checkmark hide"></span>
			<span class="icon icon-for-error icon-cancel hide"></span>
		</div> <!-- cancel-subscription-button -->
		<div class="clear5"></div>
		<?php
		if(in_array($item_id, array("5", "6"))) {
			$button_info_text = _d('Canceling the subscription does not remove the subscription right away. It will be removed at the expiration date.',995);
		} else {
			$button_info_text = _d('Canceling the subscription does not remove the upgrade. It will be removed at expiration date.',711);
		}
		?>
		<div class="cancel-subscription-button-description"><?=$button_info_text?></div>
	</div>
	<?php
}
function child_payment_canceled($item_number, $post_id, $remove_extra_time=true) {
	// remove the STRIPE subscription id if there is one
	$current_subscription_ids = in_array($item_number, array("5", "6")) ? get_user_meta($post_id, 'stripe_subscription_id', true) : get_post_meta($post_id, 'stripe_subscription_id', true);
	if($current_subscription_ids && $current_subscription_ids[$item_number] && $remove_extra_time) {
		unset($current_subscription_ids[$item_number]); // remove the stripe subscription id
		if(count($current_subscription_ids) > 0) {
			if(in_array($item_number, array("5", "6"))) {
				update_user_meta($post_id, 'stripe_subscription_id', $current_subscription_ids);
			} else {
				update_post_meta($post_id, 'stripe_subscription_id', $current_subscription_ids);
			}
		} else {
			if(in_array($item_number, array("5", "6"))) {
				delete_user_meta($post_id, 'stripe_subscription_id');
			} else {
				delete_post_meta($post_id, 'stripe_subscription_id');
			}
		}
	}

	$current_subscription_plans_ids = in_array($item_number, array("5", "6")) ? get_user_meta($post_id, 'stripe_subscription_plan', true) : get_post_meta($post_id, 'stripe_subscription_plan', true);
	if($current_subscription_plans_ids && $current_subscription_plans_ids[$item_number] && $remove_extra_time) {
		unset($current_subscription_plans_ids[$item_number]); // remove the stripe subscription id
		if(count($current_subscription_plans_ids) > 0) {
			if(in_array($item_number, array("5", "6"))) {
				update_user_meta($post_id, 'stripe_subscription_plan', $current_subscription_plans_ids);
			} else {
				update_post_meta($post_id, 'stripe_subscription_plan', $current_subscription_plans_ids);
			}
		} else {
			if(in_array($item_number, array("5", "6"))) {
				delete_user_meta($post_id, 'stripe_subscription_plan');
			} else {
				delete_post_meta($post_id, 'stripe_subscription_plan');
			}
		}
	}

	$payment_data = in_array($item_number, array("5", "6")) ? get_all_payment_data() : get_all_payment_data($post_id);

	switch ($item_number) {
		case '1': // ad posting fee
			delete_post_meta($post_id, 'ad_posting_fee_recurring');
			delete_post_meta($post_id, 'ad_posting_fee_recurring_period');
			if($remove_extra_time) {
				delete_post_meta($post_id, 'ad_posting_fee');
				delete_post_meta($post_id, 'ad_posting_fee_expiration');
			}
			if($payment_data['paid_ads']['first']['price'] && get_option('payment_mode_active') && $remove_extra_time) {
				wp_update_post(array('ID' => $post_id, 'post_status' => 'private'));
				update_post_meta($post_id, 'needs_payment', '1');
			}
			break;
		
		case '2': // Always on top
			delete_post_meta($post_id, 'always_on_top_recurring');
			delete_post_meta($post_id, 'always_on_top_recurring_period');
			if($remove_extra_time) {
				delete_post_meta($post_id, 'always_on_top');
				delete_post_meta($post_id, 'always_on_top_expiration');
			}
			break;
		
		case '3': // Highlighted ads
			delete_post_meta($post_id, 'highlighted_ad_recurring');
			delete_post_meta($post_id, 'highlighted_ad_recurring_period');
			if($remove_extra_time) {
				delete_post_meta($post_id, 'highlighted_ad');
				delete_post_meta($post_id, 'highlighted_ad_expiration');
			}
			break;
		
		case '4': // Push to top
			delete_post_meta($post_id, 'push_ad_recurring');
			delete_post_meta($post_id, 'push_ad_recurring_period');
			if($remove_extra_time) {
				delete_post_meta($post_id, 'push_ad');
				delete_post_meta($post_id, 'push_ad_expiration');
			}
			break;

		case '5': // Registration fee PERSONAL
			delete_user_meta($post_id, 'user_reg_recurring');
			delete_user_meta($post_id, 'user_reg_recurring_period');
			if($remove_extra_time) {
				delete_user_meta($post_id, 'user_type');
				delete_user_meta($post_id, 'user_reg_personal');
				delete_user_meta($post_id, 'user_reg_expiration');
			}
			break;

		case '6': // Registration fee BUSINESS
			delete_user_meta($post_id, 'user_reg_recurring');
			delete_user_meta($post_id, 'user_reg_recurring_period');
			if($remove_extra_time) {
				delete_user_meta($post_id, 'user_reg_business');
				delete_user_meta($post_id, 'user_reg_expiration');
				delete_user_meta($post_id, 'user_type');
			}
			break;
	} // switch
} // function payment_canceled

function child_get_all_payment_data($post_id="") {
	$all_data = array(
			'user_reg' => get_option('payment_user_reg_data'),
			'paid_ads' => get_option('payment_paid_ads_data'),
			'always_on_top' => get_option('payment_always_on_top_data'),
			'highlighted_ad' => get_option('payment_highlighted_ad_data'),
			'push' => get_option('payment_push_data')
		);

	if($post_id) {
		$post_data = get_post($post_id);
		$user_type = get_user_meta($post_data->post_author, 'user_type', true) ? get_user_meta($post_data->post_author, 'user_type', true) : "personal";

		foreach ($all_data as $payment_name => $user_types) {
			$all_data[$payment_name] = $all_data[$payment_name][$user_type];
		}
	}
	return $all_data;
}

function child_ad_needs_payment_html($post_id="") {
	global $payment_duration_types;
	$post_data = get_post($post_id);
	$post_author = $post_data->post_author;
	$author_user_type = get_user_meta($post_author, 'user_type', true) ? get_user_meta($post_author, 'user_type', true) : "personal";
	// if there is a subscription id in the post meta then we need to show a "cancel subscription" button for the user
	if($post_data && get_post_meta($post_id, 'stripe_subscription_id', true) && get_the_author_meta('stripe_client_id', $post_data->post_author)) {
		$author_stripe_id = get_the_author_meta('stripe_client_id', $post_data->post_author);
		$stripe_subscription_id = get_post_meta($post_id, 'stripe_subscription_id', true);
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
			$customer = \Stripe\Customer::retrieve($author_stripe_id);
		} catch (Exception $e) { }
		if($customer) {
			$show_cancel_subscription_button = true;
		}
	}

	$duration_list = array(
			'0' => _d('Never',451),
			'1' => '1 '._d('day',452),
			'2' => '2 '._d('days',337),
			'3' => '3 '._d('days',337),
			'7' => '1 '._d('week',453),
			'14' => '2 '._d('weeks',454),
			'21' => '3 '._d('weeks',454),
			'30' => '1 '._d('month',455),
			'60' => '2 '._d('months',456),
			'90' => '3 '._d('months',456),
			'180' => '6 '._d('months',456),
			'365' => '1 '._d('year',457),
		);

	$payment_data = get_all_payment_data($post_id);
	?>
	<?php if(current_user_can('level_10')) { ?>
	<script type="text/javascript">
		jQuery(document).ready(function($) {
			$('.payment-products .admin-upgrade-options .add-upgrade-button').on('click', function(event) {
				event.preventDefault();
				if($(this).hasClass('button-working')) {
					return false;
				} else {
					$(this).addClass('button-working')
				}
				var button = $(this);
				var form = $(this).parent();
				var form_data = form.serializeArray();
				var form_data_upgrade_id = form.find('input[name="upgrade_id"]').val();
				form_data.push({name: 'post_id', value: '<?=get_the_ID()?>'});
				button.find('.text').text(button.data('saving'));
				button.find('.icon').hide();
				button.find('.icon-for-saving').show();

				$.post(wpvars.wpchildthemeurl+'/ajax/child-save-settings.php', { action: 'add-upgrade', form_data: form_data }, function(raw_data, textStatus, xhr) {
					if(form_data_upgrade_id == "1") {
						$('.edit-ad-menu .pause').removeClass('paused').find('.text').text($('.edit-ad-menu .pause').data('default'));
						$('.item-page .entry .ad-is-paused').fadeOut(250);
					}

					button.find('.text').text(button.data('saved'));
					button.find('.icon').hide();
					button.find('.icon-for-saved').show();
					form.find('input[name="upgrade_duration"]').val('0');
					form.find('.fake-select .options .option[data-value="0"]').trigger('click');
					form.find('.remove-upgrade-button').fadeIn('200');
					var data = JSON.parse(raw_data);
					if(data.msg) {
						button.parents('.product').find('.purchased').html(data.msg).fadeIn('200');
					}
					setTimeout(function() {
						button.find('.text').text(button.data('default'));
						button.find('.icon').hide();
						button.find('.icon-for-default').show();
						button.removeClass('button-working');
					}, 3000);
				});
			}); // add upgrade

			$('.payment-products .admin-upgrade-options .remove-upgrade-button').on('click', function(event) {
				event.preventDefault();
				if($(this).hasClass('button-working')) {
					return false;
				} else {
					$(this).addClass('button-working')
				}
				var button = $(this);
				var form = $(this).parent();
				var form_data = form.serializeArray();
				var form_data_upgrade_id = form.find('input[name="upgrade_id"]').val();
				form_data.push({name: 'post_id', value: '<?=get_the_ID()?>'});
				button.find('.text').text(button.data('saving'));
				button.find('.icon').hide();
				button.find('.icon-for-saving').show();

				$.post(wpvars.wpchildthemeurl+'/ajax/child-save-settings.php', { action: 'remove-upgrade', form_data: form_data }, function(raw_data, textStatus, xhr) {
					<?php if($payment_data['paid_ads']['first']['price']) { ?>
					if(form_data_upgrade_id == "1") {
						$('.edit-ad-menu .pause').addClass('paused').find('.text').text($('.edit-ad-menu .pause').data('paused'));
						$('.item-page .entry .ad-is-paused').fadeOut(250);
					}
					<?php } ?>

					button.find('.text').text(button.data('saved'));
					button.find('.icon').hide();
					button.find('.icon-for-saved').show();
					form.find('input[name="upgrade_duration"]').val('0');
					form.find('.fake-select .options .option[data-value="0"]').trigger('click');
					button.parents('.product').find('.purchased').fadeOut('200');
					setTimeout(function() {
						button.fadeOut('200', function(){
							button.find('.text').text(button.data('default'));
							button.find('.icon').hide();
							button.find('.icon-for-default').show();
							button.removeClass('button-working');
						});
					}, 3000);
				});
			}); // remove upgrade
		});
	</script>
	<?php } // if admin ?>
	<div class="ad-needs-payment-section col-100 text-center">
		<?php if(!$post_id || (get_post_status($post_id) == 'private' && get_post_meta($post_id, 'needs_payment', true))) { ?>
			<div style="font-size: 1.5em"><?=_d('Please pay the posting fee so we can show your ad in our website',458)?>.</div>
		<?php } else { ?>
			<div style="font-size: 1.5em"><?=_d('Here you can purchase ad upgrades to get more views for your ad',459)?>.</div>
		<?php } ?>
		<div class="clear10"></div>

		<div class="payment-products col-80 center">
			<?php
			if($payment_data['paid_ads']['first']['price'] || current_user_can('level_10')) { ?>
				<div class="product rad5">
					<?php if($payment_data['paid_ads']['first']['price']) { ?>
						<div class="price r"><?=dolce_format_price('paid_ads', $author_user_type)?></div>
					<?php } // if $payment_data['paid_ads']['first']['price'] ?>
					<h4><?=_d('Ad posting fee',435)?><?php if(!$post_id || (get_post_status($post_id) == 'private' && get_post_meta($post_id, 'needs_payment', true) == "1")) { echo '<span class="mandatory">'._d('mandatory',840).'</span>'; } ?>
					<?php
					if(get_post_meta($post_id, 'ad_posting_fee', true) == "1") {
						echo '<span class="purchased">'._d('purchased',460);
						if(get_post_meta($post_id, 'ad_posting_fee_expiration', true)) {
							$expiration_time = get_post_meta($post_id, 'ad_posting_fee_expiration', true);
							$expiration_seconds = $expiration_time - current_time('timestamp');
							if($expiration_seconds > 0) {
								// still active
								$text = get_post_meta($post_id, 'always_on_top_recurring', true) ? _d('renews in',461) : _d('expires in',462);
								echo ' <span>-</span> '.$text.' '.secondsToTime($expiration_seconds);
							} elseif(!get_post_meta($post_id, 'always_on_top_recurring', true)) {
								echo ' <span>-</span> <span class="expired">'._d('expired',463).'</span>';
							}
						}
						echo '</span>';
					} else {
						echo '<span class="purchased hide"></span>';
					}
					?>
					</h4>
					<p><?=_d('In order to display the ad in our website you will need to pay a posting fee',464)?></p>
					<?php if(current_user_can('level_10')) { ?>
						<div class="admin-upgrade-options">
							<div class="clear5"></div>
							<form action="" method="post" class="expires-in l">
								<input type="hidden" name="upgrade_id" value="1" />
								<div class="l"><?=_d('Expires in',465)?>:</div>
								<div class="fake-select fake-select-time rad3 no-selection l">
									<div class="first"><span class="text l"></span> <span class="icon icon-arrow-up hide"></span><span class="icon icon-arrow-down"></span></div>
									<div class="options rad5 shadow hide">
										<?php
										foreach ($duration_list as $key => $value) {
											echo '<div data-value="'.$key.'" class="option">'.$value.'</div>';
										}
										?>
									</div> <!-- options -->
									<input type="hidden" name="upgrade_duration" value="0" />
								</div> <!-- fake-selector -->
								<div class="add-upgrade-button round-corners-button rad25 l" data-saving="<?=_d('Saving',92)?>" data-saved="<?=_d('Saved',93)?>" data-default="<?=_d('Add upgrade',466)?>">
									<span class="text"><?=_d('Add upgrade',466)?></span>
									<span class="icon icon-for-default icon-arrow-right"></span>
									<span class="icon icon-for-saved icon-checkmark hide"></span>
									<svg version="1.1" class="icon icon-for-saving loader hide" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 50 50" style="enable-background:new 0 0 50 50;" xml:space="preserve"><path fill="#000" d="M43.935,25.145c0-10.318-8.364-18.683-18.683-18.683c-10.318,0-18.683,8.365-18.683,18.683h4.068c0-8.071,6.543-14.615,14.615-14.615c8.072,0,14.615,6.543,14.615,14.615H43.935z"><animateTransform attributeType="xml" attributeName="transform" type="rotate" from="0 25 25" to="360 25 25" dur="0.6s" repeatCount="indefinite"/></path></svg>
								</div> <!-- add-upgrade-button -->

								<div class="remove-upgrade-button round-corners-button rad25 l<?php if(!get_post_meta($post_id, 'ad_posting_fee', true)) echo " hide"; ?>" data-saving="<?=_d('Removing',467)?>" data-saved="<?=_d('Removed',468)?>" data-default="<?=_d('Remove',469)?>">
									<span class="text"><?=_d('Remove',469)?></span>
									<span class="icon icon-for-default icon-cancel"></span>
									<span class="icon icon-for-saved icon-checkmark hide"></span>
									<svg version="1.1" class="icon icon-for-saving loader hide" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 50 50" style="enable-background:new 0 0 50 50;" xml:space="preserve"><path fill="#000" d="M43.935,25.145c0-10.318-8.364-18.683-18.683-18.683c-10.318,0-18.683,8.365-18.683,18.683h4.068c0-8.071,6.543-14.615,14.615-14.615c8.072,0,14.615,6.543,14.615,14.615H43.935z"><animateTransform attributeType="xml" attributeName="transform" type="rotate" from="0 25 25" to="360 25 25" dur="0.6s" repeatCount="indefinite"/></path></svg>
								</div> <!-- remove-upgrade-button -->
							</form> <!-- expires-in -->
						</div>
					<?php } else {
							if(get_post_meta($post_id, 'ad_posting_fee_recurring', true) || (get_post_meta($post_id, 'ad_posting_fee', true) && !get_post_meta($post_id, 'ad_posting_fee_expiration', true))) { } else {
						?>
						<div class="clear20"></div>
						<div class="payment-buttons col-100 text-center">
							<?php $pre_text = get_post_meta($post_id, 'ad_posting_fee', true) ? _d('Extend this ad for',471) : _d('Pay',472); ?>
							<div class="payment-buttons-text"><span class="icon icon-lock"></span>&nbsp;&nbsp; <?=$pre_text?> <?=get_option('payment_currency_symbol_before')?><?=$payment_data['paid_ads']['first']['price']?><?=get_option('payment_currency_symbol_after')?> <?=_d('with',470)?></div>
							<div class="generated-payment-buttons"><?php generate_payment_buttons('1', $post_id); ?></div>
						</div>
					<?php }
					}

					if($stripe_subscription_id['1'] && $show_cancel_subscription_button) {
						try {
							$subscription = $customer->subscriptions->retrieve($stripe_subscription_id['1']);
						} catch (Exception $e) { }
						if($subscription['status'] == "active") {
							generate_stripe_cancel_subscription_button('1', $post_id);
						}
					}
					?>
					<div class="clear"></div>
				</div> <!-- product -->
			<?php } // if post-ad-price ?>

			<?php if(!$post_id || (get_post_status($post_id) == 'private' && get_post_meta($post_id, 'needs_payment', true))) { } else { ?>
				<?php if($payment_data['always_on_top']['first']['price'] || current_user_can('level_10')) { ?>
					<div class="product rad5">
						<?php if($payment_data['always_on_top']['first']['price']) { ?>
							<div class="price r"><?=dolce_format_price('always_on_top', $author_user_type)?></div>
						<?php } // if $payment_data['always_on_top']['first']['price'] ?>
						<h4><?=_d('Always on top',240)?> / <?=ucfirst(strtolower(_d('Featured',587)))?>
						<?php
						if(get_post_meta($post_id, 'always_on_top', true) == "1") {
							echo '<span class="purchased">'._d('purchased',460);
							if(get_post_meta($post_id, 'always_on_top_expiration', true)) {
								$expiration_time = get_post_meta($post_id, 'always_on_top_expiration', true);
								$expiration_seconds = $expiration_time - current_time('timestamp');
								if($expiration_seconds > 0) {
									// still active
									$text = get_post_meta($post_id, 'always_on_top_recurring', true) ? _d('renews in',461) : _d('expires in',462);
									echo ' <span>-</span> '.$text.' '.secondsToTime($expiration_seconds);
								} elseif(!get_post_meta($post_id, 'always_on_top_recurring', true)) {
									echo ' <span>-</span> <span class="expired">'._d('expired',463).'</span>';
								}
							}
							echo '</span>';
						} else {
							echo '<span class="purchased hide"></span>';
						}
						?>
						</h4>
						<p><?=_d('Your ad will be displayed on top of normal ads and will have the "FEATURED" label next to them',475)?></p>
						<?php if(current_user_can('level_10')) { ?>
							<div class="admin-upgrade-options">
								<div class="clear5"></div>
								<form action="" method="post" class="expires-in l">
									<input type="hidden" name="upgrade_id" value="2" />
									<div class="l"><?=_d('Expires in',465)?>:</div>
									<div class="fake-select fake-select-time rad3 no-selection l">
										<div class="first"><span class="text l"></span> <span class="icon icon-arrow-up hide"></span><span class="icon icon-arrow-down"></span></div>
										<div class="options rad5 shadow hide">
											<?php
											foreach ($duration_list as $key => $value) {
												echo '<div data-value="'.$key.'" class="option">'.$value.'</div>';
											}
											?>
										</div> <!-- options -->
										<input type="hidden" name="upgrade_duration" value="0" />
									</div> <!-- fake-selector -->
									<div class="add-upgrade-button round-corners-button rad25 l" data-saving="<?=_d('Saving',92)?>" data-saved="<?=_d('Saved',93)?>" data-default="<?=_d('Add upgrade',466)?>">
										<span class="text"><?=_d('Add upgrade',466)?></span>
										<span class="icon icon-for-default icon-arrow-right"></span>
										<span class="icon icon-for-saved icon-checkmark hide"></span>
										<svg version="1.1" class="icon icon-for-saving loader hide" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 50 50" style="enable-background:new 0 0 50 50;" xml:space="preserve"><path fill="#000" d="M43.935,25.145c0-10.318-8.364-18.683-18.683-18.683c-10.318,0-18.683,8.365-18.683,18.683h4.068c0-8.071,6.543-14.615,14.615-14.615c8.072,0,14.615,6.543,14.615,14.615H43.935z"><animateTransform attributeType="xml" attributeName="transform" type="rotate" from="0 25 25" to="360 25 25" dur="0.6s" repeatCount="indefinite"/></path></svg>
									</div> <!-- add-upgrade-button -->

									<div class="remove-upgrade-button round-corners-button rad25 l<?php if(!get_post_meta($post_id, 'always_on_top', true)) echo " hide"; ?>" data-saving="<?=_d('Removing',467)?>" data-saved="<?=_d('Removed',468)?>" data-default="<?=_d('Remove',469)?>">
										<span class="text"><?=_d('Remove',469)?></span>
										<span class="icon icon-for-default icon-cancel"></span>
										<span class="icon icon-for-saved icon-checkmark hide"></span>
										<svg version="1.1" class="icon icon-for-saving loader hide" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 50 50" style="enable-background:new 0 0 50 50;" xml:space="preserve"><path fill="#000" d="M43.935,25.145c0-10.318-8.364-18.683-18.683-18.683c-10.318,0-18.683,8.365-18.683,18.683h4.068c0-8.071,6.543-14.615,14.615-14.615c8.072,0,14.615,6.543,14.615,14.615H43.935z"><animateTransform attributeType="xml" attributeName="transform" type="rotate" from="0 25 25" to="360 25 25" dur="0.6s" repeatCount="indefinite"/></path></svg>
									</div> <!-- remove-upgrade-button -->
								</form> <!-- expires-in -->
							</div>
							<div class="clear"></div>
						<?php } else {
							if(get_post_meta($post_id, 'always_on_top_recurring', true) || (get_post_meta($post_id, 'always_on_top', true) && !get_post_meta($post_id, 'always_on_top_expiration', true))) { } else {
						?>
							<div class="clear20"></div>
							<div class="payment-buttons col-100 text-center">
								<?php $pre_text = get_post_meta($post_id, 'always_on_top', true) ? _d('Extend this ad for',471) : _d('Pay',472); ?>
								<div class="payment-buttons-text"><span class="icon icon-lock"></span>&nbsp;&nbsp; <?=$pre_text?> <?=get_option('payment_currency_symbol_before')?><?=$payment_data['always_on_top']['first']['price']?><?=get_option('payment_currency_symbol_after')?> <?=_d('with',470)?></div>
								<div class="generated-payment-buttons"><?php generate_payment_buttons('2', $post_id); ?></div>
							</div>
						<?php }
						}

						if($stripe_subscription_id['2'] && $show_cancel_subscription_button) {
							try {
								$subscription = $customer->subscriptions->retrieve($stripe_subscription_id['2']);
							} catch (Exception $e) { }
							if($subscription['status'] == "active") {
								generate_stripe_cancel_subscription_button('2', $post_id);
							}
						}
						?>
						<div class="clear"></div>
					</div> <!-- product -->
				<?php } // if always on top ?>

				<?php if($payment_data['highlighted_ad']['first']['price'] || current_user_can('level_10')) { ?>
					<div class="product rad5">
						<?php if($payment_data['highlighted_ad']['first']['price']) { ?>
							<div class="price r"><?=dolce_format_price('highlighted_ad', $author_user_type)?></div>
						<?php } // if $payment_data['highlighted_ad']['first']['price'] ?>
						<h4><?=_d('Highlighted ad',436)?>
						<?php
						if(get_post_meta($post_id, 'highlighted_ad', true) == "1") {
							echo '<span class="purchased">'._d('purchased',460);
							if(get_post_meta($post_id, 'highlighted_ad_expiration', true)) {
								$expiration_time = get_post_meta($post_id, 'highlighted_ad_expiration', true);
								$expiration_seconds = $expiration_time - current_time('timestamp');
								if($expiration_seconds > 0) {
									// still active
									$text = get_post_meta($post_id, 'highlighted_ad_recurring', true) ? _d('renews in',461) : _d('expires in',462);
									echo ' <span>-</span> '.$text.' '.secondsToTime($expiration_seconds);
								} elseif(!get_post_meta($post_id, 'highlighted_ad_recurring', true)) {
									echo ' <span>-</span> <span class="expired">'._d('expired',463).'</span>';
								}
							}
							echo '</span>';
						} else {
							echo '<span class="purchased hide"></span>';
						}
						?>
						</h4>
						<p><?=_d('Your ad will be highlighted with a different color than normal ads',476)?></p>
						<?php if(current_user_can('level_10')) { ?>
							<div class="admin-upgrade-options">
								<div class="clear5"></div>
								<form action="" method="post" class="expires-in l">
									<input type="hidden" name="upgrade_id" value="3" />
									<div class="l"><?=_d('Expires in',465)?>:</div>
									<div class="fake-select fake-select-time rad3 no-selection l">
										<div class="first"><span class="text l"></span> <span class="icon icon-arrow-up hide"></span><span class="icon icon-arrow-down"></span></div>
										<div class="options rad5 shadow hide">
											<?php
											foreach ($duration_list as $key => $value) {
												echo '<div data-value="'.$key.'" class="option">'.$value.'</div>';
											}
											?>
										</div> <!-- options -->
										<input type="hidden" name="upgrade_duration" value="0" />
									</div> <!-- fake-selector -->
									<div class="add-upgrade-button round-corners-button rad25 l" data-saving="<?=_d('Saving',92)?>" data-saved="<?=_d('Saved',93)?>" data-default="<?=_d('Add upgrade',466)?>">
										<span class="text"><?=_d('Add upgrade',466)?></span>
										<span class="icon icon-for-default icon-arrow-right"></span>
										<span class="icon icon-for-saved icon-checkmark hide"></span>
										<svg version="1.1" class="icon icon-for-saving loader hide" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 50 50" style="enable-background:new 0 0 50 50;" xml:space="preserve"><path fill="#000" d="M43.935,25.145c0-10.318-8.364-18.683-18.683-18.683c-10.318,0-18.683,8.365-18.683,18.683h4.068c0-8.071,6.543-14.615,14.615-14.615c8.072,0,14.615,6.543,14.615,14.615H43.935z"><animateTransform attributeType="xml" attributeName="transform" type="rotate" from="0 25 25" to="360 25 25" dur="0.6s" repeatCount="indefinite"/></path></svg>
									</div> <!-- add-upgrade-button -->

									<div class="remove-upgrade-button round-corners-button rad25 l<?php if(!get_post_meta($post_id, 'highlighted_ad', true)) echo " hide"; ?>" data-saving="<?=_d('Removing',467)?>" data-saved="<?=_d('Removed',468)?>" data-default="<?=_d('Remove',469)?>">
										<span class="text"><?=_d('Remove',469)?></span>
										<span class="icon icon-for-default icon-cancel"></span>
										<span class="icon icon-for-saved icon-checkmark hide"></span>
										<svg version="1.1" class="icon icon-for-saving loader hide" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 50 50" style="enable-background:new 0 0 50 50;" xml:space="preserve"><path fill="#000" d="M43.935,25.145c0-10.318-8.364-18.683-18.683-18.683c-10.318,0-18.683,8.365-18.683,18.683h4.068c0-8.071,6.543-14.615,14.615-14.615c8.072,0,14.615,6.543,14.615,14.615H43.935z"><animateTransform attributeType="xml" attributeName="transform" type="rotate" from="0 25 25" to="360 25 25" dur="0.6s" repeatCount="indefinite"/></path></svg>
									</div> <!-- remove-upgrade-button -->
								</form> <!-- expires-in -->
							</div>
						<?php } else {
							if(get_post_meta($post_id, 'highlighted_ad_recurring', true) || (get_post_meta($post_id, 'highlighted_ad', true) && !get_post_meta($post_id, 'highlighted_ad_expiration', true))) { } else {
						?>
							<div class="clear20"></div>
							<div class="payment-buttons col-100 text-center">
								<?php $pre_text = get_post_meta($post_id, 'highlighted_ad', true) ? _d('Extend this ad for',471) : _d('Pay',472); ?>
								<div class="payment-buttons-text"><span class="icon icon-lock"></span>&nbsp;&nbsp; <?=$pre_text?> <?=get_option('payment_currency_symbol_before')?><?=$payment_data['highlighted_ad']['first']['price']?><?=get_option('payment_currency_symbol_after')?> <?=_d('with',470)?></div>
								<div class="generated-payment-buttons"><?php generate_payment_buttons('3', $post_id); ?></div>
							</div>
						<?php }
						}

						if($stripe_subscription_id['3'] && $show_cancel_subscription_button) {
							try {
								$subscription = $customer->subscriptions->retrieve($stripe_subscription_id['3']);
							} catch (Exception $e) { }
							if($subscription['status'] == "active") {
								generate_stripe_cancel_subscription_button('3', $post_id);
							}
						}
						?>
						<div class="clear"></div>
					</div> <!-- product -->
				<?php } // if highlighted ad ?>

				<?php if($payment_data['push']['first']['price'] || current_user_can('level_10')) { ?>
					<div class="product rad5">
						<?php if($payment_data['push']['first']['price']) { ?>
						<div class="price r"><?=dolce_format_price('push', $author_user_type)?></div>
						<?php } // if $payment_data['push']['first']['price'] ?>
						<h4><?=_d('Push ad',778)?>
						<?php
						if(get_post_meta($post_id, 'push_ad', true) == "1") {
							echo '<span class="purchased">'._d('purchased',460);
							if(get_post_meta($post_id, 'push_ad_expiration', true)) {
								$expiration_time = get_post_meta($post_id, 'push_ad_expiration', true);
								$expiration_seconds = $expiration_time - current_time('timestamp');
								if($expiration_seconds > 0) {
									// still active
									$text = get_post_meta($post_id, 'push_ad_recurring', true) ? _d('renews in',461) : _d('expires in',462);
									echo ' <span>-</span> '.$text.' '.secondsToTime($expiration_seconds);
								} elseif(!get_post_meta($post_id, 'push_ad_recurring', true)) {
									echo ' <span>-</span> <span class="expired">'._d('expired',463).'</span>';
								}
							}
							echo '</span>';
						} else {
							echo '<span class="purchased hide"></span>';
						}
						?>
						</h4>
						<p><?=_d('Once a day we\'ll change the posting time of your ad and move it at the top of the newly added ads. That way your ad will not get pushed down in the listings by newer ads.',477)?></p>
						<?php if(current_user_can('level_10')) { ?>
							<div class="admin-upgrade-options">
								<div class="clear5"></div>
								<form action="" method="post" class="expires-in l">
									<input type="hidden" name="upgrade_id" value="4" />
									<div class="l"><?=_d('Expires in',465)?>:</div>
									<div class="fake-select fake-select-time rad3 no-selection l">
										<div class="first"><span class="text l"></span> <span class="icon icon-arrow-up hide"></span><span class="icon icon-arrow-down"></span></div>
										<div class="options rad5 shadow hide">
											<?php
											foreach ($duration_list as $key => $value) {
												echo '<div data-value="'.$key.'" class="option">'.$value.'</div>';
											}
											?>
										</div> <!-- options -->
										<input type="hidden" name="upgrade_duration" value="0" />
									</div> <!-- fake-selector -->
									<div class="add-upgrade-button round-corners-button rad25 l" data-saving="<?=_d('Saving',92)?>" data-saved="<?=_d('Saved',93)?>" data-default="<?=_d('Add upgrade',466)?>">
										<span class="text"><?=_d('Add upgrade',466)?></span>
										<span class="icon icon-for-default icon-arrow-right"></span>
										<span class="icon icon-for-saved icon-checkmark hide"></span>
										<svg version="1.1" class="icon icon-for-saving loader hide" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 50 50" style="enable-background:new 0 0 50 50;" xml:space="preserve"><path fill="#000" d="M43.935,25.145c0-10.318-8.364-18.683-18.683-18.683c-10.318,0-18.683,8.365-18.683,18.683h4.068c0-8.071,6.543-14.615,14.615-14.615c8.072,0,14.615,6.543,14.615,14.615H43.935z"><animateTransform attributeType="xml" attributeName="transform" type="rotate" from="0 25 25" to="360 25 25" dur="0.6s" repeatCount="indefinite"/></path></svg>
									</div> <!-- add-upgrade-button -->

									<div class="remove-upgrade-button round-corners-button rad25 l<?php if(!get_post_meta($post_id, 'push_ad', true)) echo " hide"; ?>" data-saving="<?=_d('Removing',467)?>" data-saved="<?=_d('Removed',468)?>" data-default="<?=_d('Remove',469)?>">
										<span class="text"><?=_d('Remove',469)?></span>
										<span class="icon icon-for-default icon-cancel"></span>
										<span class="icon icon-for-saved icon-checkmark hide"></span>
										<svg version="1.1" class="icon icon-for-saving loader hide" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 50 50" style="enable-background:new 0 0 50 50;" xml:space="preserve"><path fill="#000" d="M43.935,25.145c0-10.318-8.364-18.683-18.683-18.683c-10.318,0-18.683,8.365-18.683,18.683h4.068c0-8.071,6.543-14.615,14.615-14.615c8.072,0,14.615,6.543,14.615,14.615H43.935z"><animateTransform attributeType="xml" attributeName="transform" type="rotate" from="0 25 25" to="360 25 25" dur="0.6s" repeatCount="indefinite"/></path></svg>
									</div> <!-- remove-upgrade-button -->
								</form> <!-- expires-in -->
							</div>
						<?php } else {
							if(get_post_meta($post_id, 'push_ad_recurring', true) || (get_post_meta($post_id, 'push_ad', true) && !get_post_meta($post_id, 'push_ad_expiration', true))) { } else {
						?>
							<div class="clear20"></div>
							<div class="payment-buttons col-100 text-center">
								<?php $pre_text = get_post_meta($post_id, 'push_ad', true) ? _d('Extend this ad for',471) : _d('Pay',472); ?>
								<div class="payment-buttons-text"><span class="icon icon-lock"></span>&nbsp;&nbsp; <?=$pre_text?> <?=get_option('payment_currency_symbol_before')?><?=$payment_data['push']['first']['price']?><?=get_option('payment_currency_symbol_after')?> <?=_d('with',470)?></div>
								<div class="generated-payment-buttons"><?php generate_payment_buttons('4', $post_id); ?></div>
							</div>
						<?php }
						}

						if($stripe_subscription_id['4'] && $show_cancel_subscription_button) {
							try {
								$subscription = $customer->subscriptions->retrieve($stripe_subscription_id['4']);
							} catch (Exception $e) { }
							if($subscription['status'] == "active") {
								generate_stripe_cancel_subscription_button('4', $post_id);
							}
						}
						?>
						<div class="clear"></div>
					</div> <!-- product -->
				<?php } // if push price ?>
			<?php } // if the ad needs payment first ?>
			<div class="clear"></div>
		</div> <!-- payment-products -->
		<div class="clear"></div>
	</div> <!-- ad-needs-payment -->
<?php
} // function ad_needs_payment_html()

function child_dolce_format_price($payment_type, $user_type, $default_label="") {
	global $payment_duration_types;
	$payment_data = get_all_payment_data();
	$payment_data = $payment_data[$payment_type][$user_type]['first'];

	if(!$payment_data['price']) return $default_label;

	$price = '<span class="value">'.get_option('payment_currency_symbol_before').$payment_data['price'].get_option('payment_currency_symbol_after').'</span>';

	if($payment_data['duration']) {
		$duration = $payment_data['duration'];
		$recurring = $payment_data['recurring'];
		$duration_type = $payment_data['durationtype'];
		$duration_label = $duration > "1" ? $payment_duration_types[$duration_type]['0'] : $payment_duration_types[$duration_type]['1'];
		if($recurring == "1") {
			if($duration == "1") {
				$price .= '<span class="duration duration1">/'.strtolower($duration_label).'</span>';
			} else {
				$price .= '<span class="duration duration2"> '._d('every',473)." ".$duration." ".strtolower($duration_label).' </span>';
			}
		} else {
			$price .= '<span class="duration duration2"> '._d('for',474)." ".$duration." ".strtolower($duration_label).' </span>';
		}
	}
	return $price;
}
?>