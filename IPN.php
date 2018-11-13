<?php
require( '../../../wp-load.php' );

if(!in_array($_GET['processor'], array('paypal', 'stripe', 'stripe_webhook', 'mycred'))) {
	die('Processor not recognized!');
}

if($_GET['processor'] == "stripe_webhook") {
	$input = @file_get_contents("php://input");
	$event = json_decode($input); // outputs an Object

	if($event->livemode === false) {
		// sandbox activated
		$stripe_secret_key = get_option('payment_stripe_test_secret_key');
		$stripe_publishable_key = get_option('payment_stripe_test_publishable_key');
	} else {
		// sandbox disabled - LIVE
		$stripe_secret_key = get_option('payment_stripe_live_secret_key');
		$stripe_publishable_key = get_option('payment_stripe_live_publishable_key');
	}

	require_once(get_template_directory().'/APIs/stripe/stripe-php-4.13.0/init.php');
	\Stripe\Stripe::setApiKey($stripe_secret_key);

	try {
		// Verify the event by fetching it from Stripe
		\Stripe\Event::retrieve($event->id);
	} catch (Exception $e) {
		die('Event not from STRIPE!');
	}

	switch ($event->type) {
		case 'invoice.payment_succeeded': // Occurs whenever an invoice attempts to be paid, and the payment succeeds.
			// only use this event for subscription payments, but not for the first payment
			if($event->data->object->lines->data->type == "subscription") {
				$post_id = $event->data->object->lines->data->metadata->post_id;
				$product_id = $event->data->object->lines->data->metadata->product_id;
				switch ($product_id) {
					case '1': // ad posting fee
						$payment_expiration_meta = "ad_posting_fee_expiration";
						$payment_recurring_period_meta = "ad_posting_fee_recurring_period";
						break;
					
					case '2': // Always on top
						$payment_expiration_meta = "always_on_top_expiration";
						$payment_recurring_period_meta = "always_on_top_recurring_period";
						break;
					
					case '3': // Highlighted ads
						$payment_expiration_meta = "highlighted_ad_expiration";
						$payment_recurring_period_meta = "highlighted_ad_recurring_period";
						break;
					
					case '4': // Push to top
						$payment_expiration_meta = "push_ad_expiration";
						$payment_recurring_period_meta = "push_ad_recurring_period";
						break;

					case '5': // Registration fee PERSONAL
						$payment_expiration_meta = "user_reg_expiration";
						$payment_recurring_period_meta = "user_reg_recurring_period";
						break;

					case '6': // Registration fee BUSINESS
						$payment_expiration_meta = "user_reg_expiration";
						$payment_recurring_period_meta = "user_reg_recurring_period";
						break;
				} // switch

				if(in_array($product_id, array("5", "6"))) {
					// if yes then that means this isn't the first payment so we only renew the expiration time
					if(get_user_meta($post_id, $payment_recurring_period_meta, true)) {
						update_user_meta($post_id, $payment_expiration_meta, strtotime("+".get_user_meta($post_id, $payment_recurring_period_meta, true)));
					}
				} else {
					// if yes then that means this isn't the first payment so we only renew the expiration time
					if(get_post_meta($post_id, $payment_recurring_period_meta, true)) {
						update_post_meta($post_id, $payment_expiration_meta, strtotime("+".get_post_meta($post_id, $payment_recurring_period_meta, true)));
					}
				}
			} // $event->data->object->lines->data->type == "subscription"
			break; // invoice.payment_succeeded

		case 'invoice.payment_failed':
			// if the payment is not a subscription we process the failed payment and remove the upgrades
			// subscriptions cancellation is managed with 'customer.subscription.deleted'
			// we also check that this is the last attempt at charging the card (depending on the STRIPE settings it can have 2-3 retries)
			if(!$event->data->object->next_payment_attempt) {
				$post_id = $event->data->object->lines->data->metadata->post_id;
				$product_id = $event->data->object->lines->data->metadata->product_id;
				if(in_array($product_id, array("5", "6"))) {
					$user_to_process = get_user_by('ID', $post_id);
					$payer_email = $user_to_process->user_email;
					$payer_name = $user_to_process->display_name;

					$email_text = _d('Your account has been disabled and you won\'t be able to post ads on our website anymore.',981);
				} else {
					$ad_to_process = get_post($post_id);
					$payer_email = get_the_author_meta('user_email', $ad_to_process->post_author);
					$payer_name = get_the_author_meta('display_name', $ad_to_process->post_author);

					$email_text = _d('We are removing the upgrade from the ad',589).' "'.$ad_to_process->post_title.'".';
				}

				switch ($product_id) {
					case '1': // ad posting fee
						$item_name = _d('Ad posting fee',435);
						$email_text = _d('Your ad',590).' "'.$ad_to_process->post_title.'" '._d('will not be visible in our website anymore.',591);
						break;
					
					case '2': // Always on top
						$item_name = _d('Upgrade',517)." - "._d('Always on top',240);
						break;
					
					case '3': // Highlighted ads
						$item_name = _d('Upgrade',517)." - "._d('Highlighted ad',436);
						break;
					
					case '4': // Push to top
						$item_name = _d('Upgrade',517)." - "._d('Push to top',437);
						break;

					case '5': // Registration fee PERSONAL
						$item_name = _d('Personal account registration fee',982);
						break;

					case '6': // Registration fee BUSINESS
						$item_name = _d('Business account registration fee',983);
						break;
				} // switch

				// send email to admin
				$body  = _d('Payment failed.',592)."<br /><br />";
				$body .= _d('Product name',593).": ".$item_name."<br />";
				$body .= _d('Product type',594).": ".$event->data->object->lines->data->type."<br />";
				if(in_array($product_id, array("5", "6"))) {
					$body .= _d('Profile page',755).": ".get_author_posts_url($post_id)."<br />";
				} else {
					$body .= _d('Payment for ad',595).": ".$ad_to_process->post_title."<br />";
					$body .= _d('Ad url',16).": ".get_post_permalink($post_id)."<br />";
				}
				$body .= _d('Client email',596).": ".$payer_email."<br />";
				dolce_email(get_option('notifications_email'), _d('Payment failed',597)." - ".home_url(), $body);

				// send email to customer
				$body  = _d('Hi',598)." ".$payer_name.",<br /><br />";
				$body .= _d('We tried to bill your card for %s but the payment failed.',599,'"'.$item_name.'"')."<br />";
				$body .= $email_text."<br /><br />";
				if(in_array($product_id, array("5", "6"))) {
					$body .= _d('Profile page',755).": ".get_author_posts_url($post_id)."<br />";
				} else {
					$body .= _d('Payment for ad',595).": ".$ad_to_process->post_title."<br />";
					$body .= _d('Ad url',16).": ".get_post_permalink($post_id);
				}
				dolce_email($payer_email, _d('Payment failed',597)." - ".home_url(), $body);

				payment_canceled($product_id, $post_id, false);
			}
			break; // invoice.payment_failed

		case 'charge.refunded':
			$post_id = $event->data->object->metadata->post_id;
			$product_id = $event->data->object->metadata->product_id;
			if(in_array($product_id, array("5", "6"))) {
				$user_to_process = get_user_by('ID', $post_id);
				$payer_email = $user_to_process->user_email;
				$payer_name = $user_to_process->display_name;
			} else {
				$ad_to_process = get_post($post_id);
				$payer_email = get_the_author_meta('user_email', $ad_to_process->post_author);
				$payer_name = get_the_author_meta('display_name', $ad_to_process->post_author);
			}

			switch ($product_id) {
				case '1': // ad posting fee
					$item_name = _d('Ad posting fee',435);
					break;
				
				case '2': // Always on top
					$item_name = _d('Upgrade',517)." - "._d('Always on top',240);
					break;
				
				case '3': // Highlighted ads
					$item_name = _d('Upgrade',517)." - "._d('Highlighted ad',436);
					break;
				
				case '4': // Push to top
					$item_name = _d('Upgrade',517)." - "._d('Push to top',437);
					break;

				case '5': // Registration fee PERSONAL
					$item_name = _d('Personal account registration fee',982);
					break;

				case '6': // Registration fee BUSINESS
					$item_name = _d('Business account registration fee',983);
					break;
			} // switch

			// send email to admin
			$body  = _d('Payment refunded.',600)."<br /><br />";
			$body .= _d('Product name',593).": ".$item_name."<br />";
			if(in_array($product_id, array("5", "6"))) {
				$body .= _d('Profile page',755).": ".get_author_posts_url($post_id)."<br />";
			} else {
				$body .= _d('Ad name',15).": ".$ad_to_process->post_title."<br />";
				$body .= _d('Ad url',16).": ".get_post_permalink($post_id)."<br />";
			}
			$body .= _d('Client email',596).": ".$payer_email;
			dolce_email(get_option('notifications_email'), _d('Subscription canceled',601)." - ".home_url(), $body);

			// send email to customer
			$body  = _d('Hi',598)." ".$payer_name.",<br /><br />";
			$body .= _d('Your payment for %s has been refunded.',602,'"'.$item_name.'"').'<br />';
			if(in_array($product_id, array("5", "6"))) {
				$body .= _d('Profile page',755).": ".get_author_posts_url($post_id)."<br />";
			} else {
				$body .= _d('Payment for ad',595).": ".$ad_to_process->post_title."<br />";
				$body .= _d('Ad url',16).": ".get_post_permalink($post_id);
			}
			dolce_email($payer_email, _d('Subscription canceled',601)." - ".home_url(), $body);

			payment_canceled($product_id, $post_id);
			break; // charge.refunded

		case 'charge.dispute.created':
			$post_id = $event->data->object->metadata->post_id;
			$product_id = $event->data->object->metadata->product_id;
			if(in_array($product_id, array("5", "6"))) {
				$user_to_process = get_user_by('ID', $post_id);
				$payer_email = $user_to_process->user_email;
			} else {
				$ad_to_process = get_post($post_id);
				$payer_email = get_the_author_meta('user_email', $ad_to_process->post_author);
			}

			switch ($product_id) {
				case '1': // ad posting fee
					$item_name = _d('Ad posting fee',435);
					break;
				
				case '2': // Always on top
					$item_name = _d('Upgrade',517)." - "._d('Always on top',240);
					break;
				
				case '3': // Highlighted ads
					$item_name = _d('Upgrade',517)." - "._d('Highlighted ad',436);
					break;
				
				case '4': // Push to top
					$item_name = _d('Upgrade',517)." - "._d('Push to top',437);
					break;

				case '5': // Registration fee PERSONAL
					$item_name = _d('Personal account registration fee',982);
					break;

				case '6': // Registration fee BUSINESS
					$item_name = _d('Business account registration fee',983);
					break;
			} // switch

			// send email to admin
			$body  = _d('Payment dispute.',603)."<br /><br />";
			$body .= _d('Product name',593).": ".$item_name."<br />";
			if(in_array($product_id, array("5", "6"))) {
				$body .= _d('Profile page',755).": ".get_author_posts_url($post_id)."<br />";
				$body .= _d('Client email',596).": ".$payer_email."<br /><br />";
				$body .= _d('The user\'s account is still active and has not been changed in any way.',984)." "._d('It is up to you to decide what you want to do with the account.',985)."<br />";
				$body .= _d('If the outcome of the chargeback is in favor of the user then the account will be disabled automatically.',986)."<br />";
			} else {
				$body .= _d('Ad name',15).": ".$ad_to_process->post_title."<br />";
				$body .= _d('Ad url',16).": ".get_post_permalink($post_id)."<br />";
				$body .= _d('Client email',596).": ".$payer_email."<br /><br />";
				$body .= _d('The ad has not been changed and the upgrade has not been removed.',604)." "._d('It is up to you to decide what you want to do with the ad.',605)."<br />";
				$body .= _d('If the outcome of the chargeback is in favor of the client then the upgrade will be removed automatically.',606)."<br />";
			}
			$body .= _d('If it\'s in your favor then no action will be performed.',607);
			dolce_email(get_option('notifications_email'), _d('Payment dispute',608)." - ".home_url(), $body);
			break; // charge.dispute.created

		case 'charge.dispute.funds_reinstated': // Occurs when funds are reinstated to your account after a dispute is won
			$post_id = $event->data->object->metadata->post_id;
			$product_id = $event->data->object->metadata->product_id;
			if(in_array($product_id, array("5", "6"))) {
				$user_to_process = get_user_by('ID', $post_id);
				$payer_email = $user_to_process->user_email;
			} else {
				$ad_to_process = get_post($post_id);
				$payer_email = get_the_author_meta('user_email', $ad_to_process->post_author);
			}

			switch ($product_id) {
				case '1': // ad posting fee
					$item_name = _d('Ad posting fee',435);
					break;
				
				case '2': // Always on top
					$item_name = _d('Upgrade',517)." - "._d('Always on top',240);
					break;
				
				case '3': // Highlighted ads
					$item_name = _d('Upgrade',517)." - "._d('Highlighted ad',436);
					break;
				
				case '4': // Push to top
					$item_name = _d('Upgrade',517)." - "._d('Push to top',437);
					break;

				case '5': // Registration fee PERSONAL
					$item_name = _d('Personal account registration fee',982);
					break;

				case '6': // Registration fee BUSINESS
					$item_name = _d('Business account registration fee',983);
					break;
			} // switch

			// send email to admin
			$body  = _d('You won a payment dispute.',609)."<br /><br />";
			$body .= _d('Product name',593).": ".$item_name."<br />";
			if(in_array($product_id, array("5", "6"))) {
				$body .= _d('Profile page',755).": ".get_author_posts_url($post_id)."<br />";
			} else {
				$body .= _d('Ad name',15).": ".$ad_to_process->post_title."<br />";
				$body .= _d('Ad url',16).": ".get_post_permalink($post_id)."<br />";
			}
			$body .= _d('Client email',596).": ".$payer_email;
			dolce_email(get_option('notifications_email'), _d('You won a payment dispute',610)." - ".home_url(), $body);
			break; // charge.dispute.funds_reinstated

		case 'charge.dispute.funds_withdrawn': // Occurs when funds are removed from your account due to a dispute
			$post_id = $event->data->object->metadata->post_id;
			$product_id = $event->data->object->metadata->product_id;
			if(in_array($product_id, array("5", "6"))) {
				$user_to_process = get_user_by('ID', $post_id);
				$payer_email = $user_to_process->user_email;
				$payer_name = $user_to_process->display_name;
				$email_text = _d('Your account has been disabled and you won\'t be able to post ads on our website anymore.',981);
			} else {
				$ad_to_process = get_post($post_id);
				$payer_email = get_the_author_meta('user_email', $ad_to_process->post_author);
				$payer_name = get_the_author_meta('display_name', $ad_to_process->post_author);
				$email_text = _d('We are removing the upgrade from your ad.',611);
			}

			switch ($product_id) {
				case '1': // ad posting fee
					$email_text = _d('The ad will not be visible in our website anymore.',612);
					$item_name = _d('Ad posting fee',435);
					break;
				
				case '2': // Always on top
					$item_name = _d('Upgrade',517)." - "._d('Always on top',240);
					break;
				
				case '3': // Highlighted ads
					$item_name = _d('Upgrade',517)." - "._d('Highlighted ad',436);
					break;
				
				case '4': // Push to top
					$item_name = _d('Upgrade',517)." - "._d('Push to top',437);
					break;

				case '5': // Registration fee PERSONAL
					$item_name = _d('Personal account registration fee',982);
					break;

				case '6': // Registration fee BUSINESS
					$item_name = _d('Business account registration fee',983);
					break;
			} // switch

			// send email to admin
			$body  = _d('You lost a payment dispute.',613)."<br /><br />";
			$body .= _d('Product name',593).": ".$item_name."<br />";
			if(in_array($product_id, array("5", "6"))) {
				$body .= _d('Profile page',755).": ".get_author_posts_url($post_id)."<br />";
			} else {
				$body .= _d('Ad name',15).": ".$ad_to_process->post_title."<br />";
				$body .= _d('Ad url',16).": ".get_post_permalink($post_id)."<br />";
			}
			$body .= _d('Client email',596).": ".$payer_email;
			dolce_email(get_option('notifications_email'), _d('You lost a payment dispute',614)." - ".home_url(), $body);

			// send email to customer
			$body  = _d('Hi',598)." ".$payer_name.",<br /><br />";
			$body .= _d('Your payment dispute for %s has closed in your favor.',615,'"'.$item_name.'"').'<br />';
			$body .= $email_text.'<br />';
			if(in_array($product_id, array("5", "6"))) {
				$body .= _d('Profile page',755).": ".get_author_posts_url($post_id)."<br />";
			} else {
				$body .= _d('Ad name',15).": ".$ad_to_process->post_title."<br />";
				$body .= _d('Ad url',16).": ".get_post_permalink($post_id);
			}
			dolce_email($payer_email, _d('Subscription canceled',601)." - ".home_url(), $body);

			payment_canceled($product_id, $post_id);
			break; // charge.dispute.funds_withdrawn

		case 'customer.subscription.deleted':
			$post_id = $event->data->object->metadata->post_id;
			$product_id = $event->data->object->metadata->product_id;
			$cancel_at_period_end = $event->data->object->cancel_at_period_end;
			if(in_array($product_id, array("5", "6"))) {
				$user_to_process = get_user_by('ID' ,$post_id);
				$payer_email = $user_to_process->user_email;
				$payer_name = $user_to_process->display_name;
				$email_text = _d('Your account will still be active until your subscription ends.',987);
			} else {
				$ad_to_process = get_post($post_id);
				$payer_email = get_the_author_meta('user_email', $ad_to_process->post_author);
				$payer_name = get_the_author_meta('display_name', $ad_to_process->post_author);
				$email_text = _d('The upgrade will still be active until the expiration date.',616);
			}

			switch ($product_id) {
				case '1': // ad posting fee
					$item_name = _d('Ad posting fee',435);
					$email_text = _d('The ad will still be visible until the expiration date.',617);
					break;
				
				case '2': // Always on top
					$item_name = _d('Upgrade',517)." - "._d('Always on top',240);
					break;
				
				case '3': // Highlighted ads
					$item_name = _d('Upgrade',517)." - "._d('Highlighted ad',436);
					break;
				
				case '4': // Push to top
					$item_name = _d('Upgrade',517)." - "._d('Push to top',437);
					break;

				case '5': // Registration fee PERSONAL
					$item_name = _d('Personal account registration fee',982);
					break;

				case '6': // Registration fee BUSINESS
					$item_name = _d('Business account registration fee',983);
					break;
			} // switch

			// send email to admin
			$body  = _d('Subscription canceled.',618)."<br /><br />";
			$body .= _d('Product name',593).": ".$item_name."<br />";
			$body .= _d('Product type',594).": "._d('subscription',619)."<br />";
			if(in_array($product_id, array("5", "6"))) {
				$body .= _d('Profile page',755).": ".get_author_posts_url($post_id)."<br />";
			} else {
				$body .= _d('Ad name',15).": ".$ad_to_process->post_title."<br />";
				$body .= _d('Ad url',16).": ".get_post_permalink($post_id)."<br />";
			}
			$body .= _d('Client email',596).": ".$payer_email;
			if($cancel_at_period_end) {
				$body .= "<br /><br /><b>".$email_text."</b>";
			}
			dolce_email(get_option('notifications_email'), _d('Subscription canceled',601)." - ".home_url(), $body);

			// send email to customer
			$body  = "_d('Hi',598) ".$payer_name.",<br /><br />";
			$body .= _d('Your subscription for %s has been canceled.',620,'"'.$item_name.'"').'<br />';
			if(in_array($product_id, array("5", "6"))) {
				$body .= _d('Profile page',755).": ".get_author_posts_url($post_id)."<br />";
			} else {
				$body .= _d('Ad name',15).": ".$ad_to_process->post_title."<br />";
				$body .= _d('Ad url',16).": ".get_post_permalink($post_id);
			}
			if($cancel_at_period_end) {
				$body .= "<br /><br /><b>".$email_text."</b>";
			}
			dolce_email($payer_email, _d('Subscription canceled',601)." - ".home_url(), $body);

			if($cancel_at_period_end) {
				payment_canceled($product_id, $post_id, false);
			} else {
				payment_canceled($product_id, $post_id);
			}
			break; // customer.subscription.deleted

		case 'ping':
			// this is STRIPE calling
			break; // ping
	}

	http_response_code(200); die();
} // stripe_webhook

// https://stripe.com/docs/api?PHPSESSID=bmgqkp3wn8mo6659e47yq6806o9eq4z73gq171a0
if($_GET['processor'] == "stripe") {
	$token = $_POST['vars']['token'];
	if(!$token)
		die(json_encode(array('status' => 'err', 'msg' => _d('No token was submitted',621))));

	$ip = preg_replace('/[^0-9a-fA-F:., ]/', '', $_POST['vars']['ip']);
	$email = $_POST['vars']['email'];
	$product_id = $_POST['vars']['item_nr'];
	$post_id = $_POST['vars']['post_id'];
	if(in_array($product_id, array("5", "6"))) {
		$ad_to_process = get_user_by('ID', $post_id);
	} else {
		$ad_to_process = get_post($post_id);
		$author_user_type_data = get_user_by('ID', $ad_to_process->post_author);
		$author_user_type = get_user_meta($author_user_type_data->ID, 'user_type', true);
	}
	if(!$ad_to_process)
		die(json_encode(array('status' => 'err', 'msg' => _d('We don\'t know what ad you are paying for',622))));

	if(in_array($product_id, array("5", "6"))) {
		$author_email = $ad_to_process->user_email;
		$stripe_client_id = get_the_author_meta('stripe_client_id', $ad_to_process->ID);
	} else {
		$author_id = $ad_to_process->post_author;
		$author_email = get_the_author_meta('user_email', $ad_to_process->post_author);
		$stripe_client_id = get_the_author_meta('stripe_client_id', $ad_to_process->post_author);
	}
	$site_url = parse_url(home_url());
	$host_domain = $site_url['host'];
	$payment_currency = strtolower( get_option('payment_currency'));
	if(in_array($product_id, array("5", "6"))) {
		$payment_data = get_all_payment_data();
	} else {
		$payment_data = get_all_payment_data($post_id);
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

	switch ($product_id) {
		case '1':
			if($payment_data['paid_ads']['first']['recurring'] == "1") {
				$stripe_plan = $host_domain."_"."posting_fee_".$author_user_type;
			}
			$item_name = _d('Ad posting fee',435);
			$product_price = $payment_data['paid_ads']['first']['price'] * 100;
			break;

		case '2':
			if($payment_data['always_on_top']['first']['recurring'] == "1") {
				$stripe_plan = $host_domain."_"."always_on_top_".$author_user_type;
			}
			$item_name = _d('Upgrade',517)." - "._d('Always on top',240);
			$product_price = $payment_data['always_on_top']['first']['price'] * 100;
			break;

		case '3':
			if($payment_data['highlighted_ad']['first']['recurring'] == "1") {
				$stripe_plan = $host_domain."_"."highlighted_ad_".$author_user_type;
			}
			$item_name = _d('Upgrade',517)." - "._d('Highlighted ads',244);
			$product_price = $payment_data['highlighted_ad']['first']['price'] * 100;
			break;

		case '4':
			if($payment_data['push']['first']['recurring'] == "1") {
				$stripe_plan = $host_domain."_"."push_to_top_".$author_user_type;
			}
			$item_name = _d('Upgrade',517)." - "._d('Push to top',437);
			$product_price = $payment_data['push']['first']['price'] * 100;
			break;

		case '5':
			if($payment_data['user_reg']['personal']['first']['recurring'] == "1") {
				$stripe_plan = $host_domain."_"."user_reg_personal";
			}
			$item_name = _d('Personal account registration fee',982);
			$product_price = $payment_data['user_reg']['personal']['first']['price'] * 100;
			break;

		case '6':
			if($payment_data['user_reg']['business']['first']['recurring'] == "1") {
				$stripe_plan = $host_domain."_"."user_reg_business";
			}
			$item_name = _d('Business account registration fee',983);
			$product_price = $payment_data['user_reg']['business']['first']['price'] * 100;
			break;
	}

	require_once(get_template_directory().'/APIs/stripe/stripe-php-4.13.0/init.php');
	\Stripe\Stripe::setApiKey($stripe_secret_key);

	// RETRIEVE client from STRIPE
	$create_customer = "yes";
	if($stripe_client_id) {
		try {
			$customer = \Stripe\Customer::retrieve($stripe_client_id);
		} catch (Exception $e) { }
		if($customer) {
			$create_customer = "no";
			$customer_array = $customer->__toArray($recursive=true);
			if($customer_array['deleted'] == "1") {
				$create_customer = "yes";
				if(in_array($product_id, array("5", "6"))) {
					delete_user_meta($ad_to_process->ID, 'stripe_client_id');
				} else {
					delete_user_meta($ad_to_process->post_author, 'stripe_client_id');
				}
			}
		} else {
			$create_customer = "yes";
			if(in_array($product_id, array("5", "6"))) {
				delete_user_meta($ad_to_process->ID, 'stripe_client_id');
			} else {
				delete_user_meta($ad_to_process->post_author, 'stripe_client_id');
			}
		}
	}
	if($create_customer == "yes") {
		try {
			if(in_array($product_id, array("5", "6"))) {
				$get_author_posts_url = get_author_posts_url($ad_to_process->ID);
				$user_id = $ad_to_process->ID;
			} else {
				$get_author_posts_url = get_author_posts_url($ad_to_process->post_author);
				$user_id = $ad_to_process->post_author;
			}
			// CREATE customer on STRIPE
			$customer = \Stripe\Customer::create(array(
				"source" => $token,
				"email" => $author_email,
				"metadata" => array(
						'user_id' => $user_id,
						'profile_url' => $get_author_posts_url,
						'ip' => $ip
					)
			));
		} catch (Exception $e) {
			die(json_encode(array('status' => 'err', 'msg' => _d('Can\'t create the customer!',623))));
		}

		$customer_array = $customer->__toArray($recursive=true);
		$stripe_client_id = $customer_array['id'];
		if(in_array($product_id, array("5", "6"))) {
			update_user_meta($ad_to_process->ID, 'stripe_client_id', $customer_array['id']);
		} else {
			update_user_meta($ad_to_process->post_author, 'stripe_client_id', $customer_array['id']);
		}
	} elseif($create_customer == "no") {
		// update the payment source for existing customer
		try {
			$customer->source = $token;
			$customer->save();
		} catch (Exception $e) {
			$err = $e->jsonBody['error']['message'];
			die(json_encode(array('status' => 'err', 'msg' => _d('Can\'t process this credit card',624).'<br />'.$err)));
		}

		try {
			$customer = \Stripe\Customer::retrieve($stripe_client_id);
		} catch (Exception $e) {}
	} // elseif($create_customer == "no")

	if($stripe_plan) { // pay for a subscription
		try {
			if(in_array($product_id, array("5", "6"))) {
				$subscription_id_data = get_user_meta($post_id, 'stripe_subscription_id', true);
				if($subscription_id_data && $subscription_id_data[$product_id]) {
					$subscription_id = $subscription_id_data[$product_id];
				}

				$plan_id_data = get_user_meta($post_id, 'stripe_subscription_plan', true);
				if($plan_id_data && $plan_id_data[$product_id]) {
					$current_plan_id = $plan_id_data[$product_id];
				}

				$post_url = get_author_posts_url($post_id);
				$metadata = array(
								'product_id' => $product_id, 
								'user_id' => $post_id, 
								'profile_url' => $post_url, 
								'ip' => $ip
							);
			} else {
				$subscription_id_data = get_post_meta($post_id, 'stripe_subscription_id', true);
				if($subscription_id_data && $subscription_id_data[$product_id]) {
					$subscription_id = $subscription_id_data[$product_id];
				}

				$plan_id_data = get_post_meta($post_id, 'stripe_subscription_plan', true);
				if($plan_id_data && $plan_id_data[$product_id]) {
					$current_plan_id = $plan_id_data[$product_id];
				}

				$post_url = get_post_permalink($post_id);
				$metadata = array(
								'product_id' => $product_id, 
								'post_id' => $post_id, 
								'post_url' => $post_url, 
								'ip' => $ip
							);
			}

			if($subscription_id && $stripe_plan == $current_plan_id) { // if the subscription has not expired yet then we only reactivate it
				$subscription = \Stripe\Subscription::retrieve($subscription_id);
				$subscription->plan = $stripe_plan;
				$subscription->save();
			} else { // if the subscription has already expired or it's beginning for the first time then we create one
				$subscription = $customer->subscriptions->create(array("plan" => $stripe_plan, 'metadata' => $metadata));
			}
		} catch (Exception $e) {
			die(json_encode(array('status' => 'err', 'msg' => _d('Can\'t create subscription!',625))));
		}
		$payment = $subscription->__toArray($recursive=true);
		$stripe_subscription_renewal = $payment['current_period_end'];
		$stripe_subscription_id = $payment['id'];
	} else {
		// one time charge
		$customer_array = $customer->__toArray($recursive=true);
		try {
			if(in_array($product_id, array("5", "6"))) {
				$post_url = get_author_posts_url($post_id);
			} else {
				$post_url = get_post_permalink($post_id);
			}
			$payment = \Stripe\Charge::create(array(
				"amount" => $product_price,
				"currency" => $payment_currency,
				"customer" => $customer_array['id'],
				"description" => $item_name,
				'metadata' => array('product_id' => $product_id, 'post_id' => $post_id, 'post_url' => $post_url, 'ip' => $ip),
				'statement_descriptor' => substr($host_domain, 0, 22),
				'receipt_email' => $author_email
			));
		} catch(\Stripe\Error\Card $e) {
			$err = $e->jsonBody['error']['message'];
			die(json_encode(array('status' => 'err', 'msg' => _d('The card has been declined',626).'<br />'.$err)));
		}

		$payment = $payment->__toArray($recursive=true);
	} // one time charge
	$product_price = $product_price / 100;

	if(in_array($product_id, array("5", "6"))) {
		$first_name_data = get_user_by('ID', $post_id);
		$first_name = $first_name_data->display_name;
	} else {
		$post_id_data = get_post($post_id);
		$first_name_data = get_user_by('ID', $post_id_data->post_author);
		$first_name = $first_name_data->display_name;
	}
} // STRIPE


// https://developer.paypal.com/docs/classic/ipn/ht_ipn/
if($_GET['processor'] == "paypal") {
	// STEP 1: read POST data
	// Reading POSTed data directly from $_POST causes serialization issues with array data in the POST.
	// Instead, read raw POST data from the input stream.
	$raw_post_data = file_get_contents('php://input');
	if(!$raw_post_data) die('No post data');
	$raw_post_array = explode('&', $raw_post_data);
	$payment = array();
	foreach ($raw_post_array as $keyval) {
		$keyval = explode ('=', $keyval);
		if (count($keyval) == 2)
			$payment[$keyval[0]] = urldecode($keyval[1]);
	}
	// read the IPN message sent from PayPal and prepend 'cmd=_notify-validate'
	$req = 'cmd=_notify-validate';
	if(function_exists('get_magic_quotes_gpc')) {
		$get_magic_quotes_exists = true;
	}
	foreach ($payment as $key => $value) {
		if($get_magic_quotes_exists == true && get_magic_quotes_gpc() == 1) {
			$value = urlencode(stripslashes($value));
		} else {
			$value = urlencode($value);
		}
		$req .= "&$key=$value";
		$raw_data_to_email .= "$key = ".urldecode($value)."<br />";
	}

	// STEP 2: POST IPN data back to PayPal to validate
	$sandbox = get_option("payment_paypal_sandbox") == "1" ? "sandbox." : "";
	$ch = curl_init('https://www.'.$sandbox.'paypal.com/cgi-bin/webscr');
	curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $req);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
	curl_setopt($ch, CURLOPT_FORBID_REUSE, 1);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Connection: Close'));

	// In wamp-like environments that do not come bundled with root authority certificates,
	// please download 'cacert.pem' from "http://curl.haxx.se/docs/caextract.html" and set
	// the directory path of the certificate as shown below:
	// curl_setopt($ch, CURLOPT_CAINFO, dirname(__FILE__) . '/cacert.pem');
	if(!($res = curl_exec($ch))) {
		$body = "<b>"._d('ERROR',627).":</b> "._d('Got %s when processing IPN data.',628,'<b>"'.curl_error($ch).'"</b>')."<br /><br />"._d('Raw post data',629).":<br />".$raw_data_to_email;
		dolce_email(get_option('notifications_email'), _d('Payment error!',631), $body);
		curl_close($ch);
		exit;
	}
	curl_close($ch);

	if (strcmp($res, "INVALID") == 0) {
		// IPN invalid, log for manual investigation
		$body  = "<b>"._d('ERROR',627).":</b> "._d('IPN data submitted is invalid.',632)."<br /><br />";
		$body .= _d('Raw post data',629).":<br />".$raw_data_to_email;
		dolce_email(get_option('notifications_email'), _d('Payment error!',631), $body);
		die();
	} else if(strcmp($res, "VERIFIED") == 0) {
		// STEP 3: The IPN is verified, process it

		// assign posted variables to local variables
		$item_name = $_POST['item_name'];
		$product_id = substr((int)$_POST['item_number'], 0, 2);
		$payment_status = preg_replace("/([^a-zA-Z-_])/", "", $_POST['payment_status']);
		$payment_amount = $_POST['mc_gross'];
		$payment_currency = $_POST['mc_currency'];
		$txn_id = $_POST['txn_id'];
		$txn_type = $_POST['txn_type'];
		$post_id = (int)$_POST['custom'];
		$business = $_POST['business'];
		$payer_email = $_POST['payer_email'];
		$first_name = $_POST['first_name'];

		if(in_array($product_id, array("5", "6"))) {
			$payment_data = get_all_payment_data();
		} else {
			$payment_data = get_all_payment_data($post_id);
		}

		if(get_option("payment_paypal_sandbox") == "1" && $payment_status == "pending" ) {
			$payment_status = "Completed";
		}
		// check whether the payment_status is Completed
		if ($payment_status == "Completed") {
			// The payment has been completed, and the funds have been added successfully to your account balance.
		} elseif(in_array($payment_status, array("Canceled_Reversal", "Denied", "Expired", "Failed", "Voided"))) {
			// Canceled_Reversal: A reversal has been canceled. For example, you won a dispute with the customer, and the funds for the transaction that was reversed have been returned to you.
			// Denied: You denied the payment. This happens only if the payment was previously pending because of possible reasons described for the pending_reason variable or the Fraud_Management_Filters_x variable.
			// Expired: This authorization has expired and cannot be captured.
			// Failed: The payment has failed. This happens only if the payment was made from your customer's bank account.
			// Voided: This authorization has been voided.
			if(in_array($product_id, array("5", "6"))) {
				$permalink = get_author_posts_url($post_id);
			} else {
				$permalink = get_post_permalink($post_id);
			}
			$body  = "<b>"._d('ERROR',627).":</b> "._d('Payment status',633).": <b>".$payment_status."</b><br /><br />";
			$body .= _d('Payment for',630).": ".$permalink."<br /><br />";
			$body .= _d('Raw post data',629).":<br />".$raw_data_to_email;
			dolce_email(get_option('notifications_email'), _d('Payment error!',631), $body);
			die();
		} elseif ($payment_status == "Created") {
			//A German ELV payment is made using Express Checkout.
		} elseif ($payment_status == "Pending") {
			//The payment is pending. See pending_reason for more information.

			// Send email to admin
			if(in_array($product_id, array("5", "6"))) {
				$permalink = get_author_posts_url($post_id);
			} else {
				$permalink = get_post_permalink($post_id);
			}
			$pending_reason = $_POST['pending_reason'];
			$body  = _d('Payment is <b>pending</b>',634).".<br /><br />";
			$body .= _d('PayPal reason',635).": ".$pending_reason."<br />";
			$body .= _d('Payment for',630).": ".$permalink."<br /><br />";
			$body .= _d('Raw post data',629).":<br />".$raw_data_to_email;
			dolce_email(get_option('notifications_email'), _d('Payment notification!',636), $body);

			// Send email to buyer
			$body  = _d('Hi',598)." ".$first_name.",<br /><br />";
			$body .= _d('Your payment for the site %s is currently pending.',637,home_url())."<br />";
			$body .= _d('We\'ll let you know when the payment has finished.',638);
			dolce_email($payer_email, _d('Payment notification!',636), $body);
			die();
		} elseif ($payment_status == "Refunded") {
			//You refunded the payment.

			// Send email to admin
			if(in_array($product_id, array("5", "6"))) {
				$permalink = get_author_posts_url($post_id);
			} else {
				$permalink = get_post_permalink($post_id);
			}
			$body  = _d('Payment was <b>refunded</b>.',639)."<br /><br />";
			$body .= _d('Payment for',630).": ".$permalink."<br /><br />";
			$body .= _d('Raw post data',629).":<br />".$raw_data_to_email;
			dolce_email(get_option('notifications_email'), _d('Payment refunded!',640), $body);

			// Send email to buyer
			$body = "Hi ".$first_name.",<br /><br />"._d('Your payment for the site %s was refunded.',641,home_url());
			dolce_email($payer_email, _d('Payment refunded!',640), $body);

			payment_canceled($product_id, $post_id); die();
		} elseif ($payment_status == "Reversed") {
			//A payment was reversed due to a chargeback or other type of reversal. The funds have been removed from your account balance and returned to the buyer. The reason for the reversal is specified in the ReasonCode element.

			// Send email to admin
			if(in_array($product_id, array("5", "6"))) {
				$permalink = get_author_posts_url($post_id);
			} else {
				$permalink = get_post_permalink($post_id);
			}
			$body  = _d('A payment was reversed.',642)."<br /><br />";
			$body .= _d('Payment for',630).": ".$permalink."<br /><br />";
			$body .= _d('Raw post data',629).":<br />".$raw_data_to_email;
			dolce_email(get_option('notifications_email'), _d('Payment reversed!',643), $body);

			// Send email to buyer
			$body = _d('Hi',598)." ".$first_name.",<br /><br />"._d('Your payment for the site %s was reversed.',644,home_url());
			dolce_email($payer_email, _d('Payment reversed!',643), $body);

			payment_canceled($product_id, $post_id); die();
		} elseif ($payment_status == "Processed") {
			//A payment has been accepted.
		} else {
			if(in_array($product_id, array("5", "6"))) {
				$permalink = get_author_posts_url($post_id);
			} else {
				$permalink = get_post_permalink($post_id);
			}
			$body  = "<b>"._d('ERROR',627).". </b>"._d('Payment status is not recognized',645)." : ".$payment_status."<br /><br />";
			$body .= _d('Payment for',630).": ".$permalink."<br /><br />";
			$body .= _d('Raw post data',629).":<br />".$raw_data_to_email;
			dolce_email(get_option('notifications_email'), _d('Payment error!',631), $body);
			die();
		}

		// check the transaction type
		if ($txn_type == "subscr_signup") {
			// subscription sign-up.
			// PayPal sends "subscr_signup" and "subscr_payment" one after another so we only use "subscr_payment"
			// this notification does not have "payment_status" attached so we don't add any other settings to the ad based solely on this
			die();
		} elseif ($txn_type == "subscr_cancel") {
			//subscription cancellation.
			payment_canceled($product_id, $post_id, false); die();
		} elseif ($txn_type == "subscr_modify") {
			//subscription modification.
			die();
		} elseif ($txn_type == "subscr_failed") {
			//subscription payment failure.
			die();
		} elseif ($txn_type == "subscr_payment") {
			//subscription payment.
			switch ($product_id) {
				case '1': // ad posting fee
					$payment_expiration_meta = "ad_posting_fee_expiration";
					$payment_recurring_period_meta = "ad_posting_fee_recurring_period";
					break;
				
				case '2': // Always on top
					$payment_expiration_meta = "always_on_top_expiration";
					$payment_recurring_period_meta = "always_on_top_recurring_period";
					break;
				
				case '3': // Highlighted ads
					$payment_expiration_meta = "highlighted_ad_expiration";
					$payment_recurring_period_meta = "highlighted_ad_recurring_period";
					break;
				
				case '4': // Push to top
					$payment_expiration_meta = "push_ad_expiration";
					$payment_recurring_period_meta = "push_ad_recurring_period";
					break;

				case '5': // Registration fee PERSONAL
					$payment_expiration_meta = "user_reg_expiration";
					$payment_recurring_period_meta = "user_reg_recurring_period";
					break;

				case '6': // Registration fee BUSINESS
					$payment_expiration_meta = "user_reg_expiration";
					$payment_recurring_period_meta = "user_reg_recurring_period";
					break;
			} // switch


			if(in_array($product_id, array("5", "6"))) {
				// if yes then that means this isn't the first "subscr_payment" payment so we only renew the expiration time from the user account
				if(get_user_meta($post_id, $payment_recurring_period_meta, true)) {
					update_user_meta($post_id, $payment_expiration_meta, strtotime("+".get_user_meta($post_id, $payment_recurring_period_meta, true)));
				}
			} else {
				// if yes then that means this isn't the first "subscr_payment" payment so we only renew the expiration time and stop the script
				if(get_post_meta($post_id, $payment_recurring_period_meta, true)) {
					update_post_meta($post_id, $payment_expiration_meta, strtotime("+".get_post_meta($post_id, $payment_recurring_period_meta, true)));
					die();
				}
			}
		} elseif ($txn_type == "subscr_eot") {
			//subscription's end of term.
			payment_canceled($product_id, $post_id, false); die();
		}


		// check that the custom field has a value and the value is from an ad
		// otherwise we don't have where to place the upgrade
		if(in_array($product_id, array("5", "6"))) {
			$ad_to_process = get_user_by('ID', $post_id);
		} else {
			$ad_to_process = get_post($post_id);
		}
		if(!$ad_to_process) {
			$body  = "<b>"._d('ERROR',627).":</b> _d('This payment has no custom field value.',646)<br />";
			$body .= _d('The custom field is used to store the ad id. We need that in order to know where to apply the upgrade from the payment. The payment processing has been stopped in the theme.',647)."<br /><br />";
			$body .= _d('Raw post data',629).":<br />".$raw_data_to_email;
			dolce_email(get_option('notifications_email'), _d('Payment error!',631), $body);
			die();
		}

		// check that business is your Primary PayPal email
		$business_from_site = (get_option("payment_paypal_sandbox") == "1") ? get_option('payment_paypal_sandbox_address') : get_option('payment_paypal_address');
		if($business != $business_from_site) {
			if(in_array($product_id, array("5", "6"))) {
				$permalink = get_author_posts_url($post_id);
			} else {
				$permalink = get_post_permalink($post_id);
			}
			$body = "<b>"._d('ERROR',627).":</b> "._d('The business email from the transaction data does not match the email from the payment settings page.',648)."<br /><br />";
			$body .= _d('Payment for',630).": ".$permalink."<br /><br />";
			$body .= _d('Raw post data',629).":<br />".$raw_data_to_email;
			dolce_email(get_option('notifications_email'), _d('Payment error!',631), $body);
			die();
		}

		// check that payment_amount is correct
		switch ($product_id) {
			case '1': // ad posting fee
				$product_price = number_format($payment_data['paid_ads']['first']['price'], 2, '.', '');
				break;
			
			case '2': // Always on top
				$product_price = number_format($payment_data['always_on_top']['first']['price'], 2, '.', '');
				break;
			
			case '3': // Highlighted ads
				$product_price = number_format($payment_data['highlighted_ad']['first']['price'], 2, '.', '');
				break;
			
			case '4': // Push to top
				$product_price = number_format($payment_data['push']['first']['price'], 2, '.', '');
				break;

			case '5': // Registration fee PERSONAL
				$product_price = number_format($payment_data['user_reg']['personal']['first']['price'], 2, '.', '');
				break;

			case '6': // Registration fee BUSINESS
				$product_price = number_format($payment_data['user_reg']['business']['first']['price'], 2, '.', '');
				break;
		}
		if($product_price != $payment_amount) {
			if(in_array($product_id, array("5", "6"))) {
				$permalink = get_author_posts_url($post_id);
			} else {
				$permalink = get_post_permalink($post_id);
			}
			$body  = "<b>"._d('ERROR',627).":</b> "._d('The price for %s from the transaction is not the same as the price you set in the site.',649,'"<b>'.$item_name.'</b>"')."<br /><br />";
			$body .= _d('Payment for',630).": ".$permalink."<br /><br />";
			$body .= _d('Raw post data',629).":<br />".$raw_data_to_email;
			dolce_email(get_option('notifications_email'), _d('Payment error!',631), $body);
			die();
		}

		// check that payment_currency is correct
		if($payment_currency != get_option('payment_currency')) {
			if(in_array($product_id, array("5", "6"))) {
				$permalink = get_author_posts_url($post_id);
			} else {
				$permalink = get_post_permalink($post_id);
			}
			$body  = "<b>"._d('ERROR',627).":</b> _d('The currency from the transaction is not the same as the site\'s currency.',650)<br /><br />";
			$body .= _d('Payment for',630).": ".$permalink."<br /><br />";
			$body .= _d('Raw post data',629).":<br />".$raw_data_to_email;
			dolce_email(get_option('notifications_email'), _d('Payment error!',631), $body);
			die();
		}

		// check that txn_id has not been previously processed
		$payments_args = array(
			'post_type' => 'payment', 'posts_per_page' => "1", 'paged' => "1",
			'meta_query' => array(
					array( 'key' => 'txn_id', 'value' => $txn_id, 'compare' => '=' ),
					array( 'key' => 'processor', 'value' => 'paypal', 'compare' => '=' )
				)
		);
		$payments = new WP_Query( $payments_args );
		if($payments->found_posts > 0) {
			// This transaction has been processed already.
			die(_d('This transaction has been processed already',651));
		}
	} // if(strcmp($res, "VERIFIED") == 0) {
} // PayPal


// MyCred
if($_GET['processor'] == "mycred") {

	$product_id = $_POST['vars']['item_nr'];
	$post_id = $_POST['vars']['post_id'];
	if(in_array($product_id, array("5", "6"))) {
		$ad_to_process = get_user_by('ID', $post_id);
	} else {
		$ad_to_process = get_post($post_id);
		$author_user_type_data = get_user_by('ID', $ad_to_process->post_author);
	}
	if(!$ad_to_process)
		die(json_encode(array('status' => 'err', 'msg' => _d('We don\'t know what ad you are paying for',622))));

	if(in_array($product_id, array("5", "6"))) {
		$author_email = $ad_to_process->user_email;
	} else {
		$author_id = $ad_to_process->post_author;
		$author_email = get_the_author_meta('user_email', $ad_to_process->post_author);
	}

	if(!in_array($product_id, array("5", "6")) && $author_id != get_current_user_id())
		die(json_encode(array('status' => 'err', 'msg' => 'You have to be the author of the ad for perform the pay')));

	$payment_currency = strtolower( get_option('payment_currency'));
	if(in_array($product_id, array("5", "6"))) {
		$payment_data = get_all_payment_data();
	} else {
		$payment_data = get_all_payment_data($post_id);
	}

	switch ($product_id) {
		case '1':
			if($payment_data['paid_ads']['first']['recurring'] == "1") {
				$mycred_recurring = true;
			}
			$item_name = _d('Ad posting fee',435);
			$product_price = $payment_data['paid_ads']['first']['price'];
			break;

		case '2':
			if($payment_data['always_on_top']['first']['recurring'] == "1") {
				$mycred_recurring = true;
			}
			$item_name = _d('Upgrade',517)." - "._d('Always on top',240);
			$product_price = $payment_data['always_on_top']['first']['price'];
			break;

		case '3':
			if($payment_data['highlighted_ad']['first']['recurring'] == "1") {
				$mycred_recurring = true;
			}
			$item_name = _d('Upgrade',517)." - "._d('Highlighted ads',244);
			$product_price = $payment_data['highlighted_ad']['first']['price'];
			break;

		case '4':
			if($payment_data['push']['first']['recurring'] == "1") {
				$mycred_recurring = true;
			}
			$item_name = _d('Upgrade',517)." - "._d('Push to top',437);
			$product_price = $payment_data['push']['first']['price'];
			break;

		case '5':
			if($payment_data['user_reg']['personal']['first']['recurring'] == "1") {
				$mycred_recurring = true;
			}
			$item_name = _d('Personal account registration fee',982);
			$product_price = $payment_data['user_reg']['personal']['first']['price'];
			break;

		case '6':
			if($payment_data['user_reg']['business']['first']['recurring'] == "1") {
				$mycred_recurring = true;
			}
			$item_name = _d('Business account registration fee',983);
			$product_price = $payment_data['user_reg']['business']['first']['price'];
			break;
	}


	// RETRIEVE client from STRIPE
	$mycred = mycred();
	// Make sure user is not excluded
	if ( ! $mycred->exclude_user( $author_id ) ) {
		// get users balance
		$balance = $mycred->get_users_balance( $author_id );
		// error if the user is not the author
		if($product_price >  $balance)
			die(json_encode(array('status' => 'err', 'msg' => 'You dont have enought credits')));
		
		// Adjust balance with a log entry
		$mycred->add_creds(
			'reference',
			$author_id,
			$product_price * -1,
			$item_name
		);
	}


	if(in_array($product_id, array("5", "6"))) {
		$first_name_data = get_user_by('ID', $post_id);
		$first_name = $first_name_data->display_name;
	} else {
		$post_id_data = get_post($post_id);
		$first_name_data = get_user_by('ID', $post_id_data->post_author);
		$first_name = $first_name_data->display_name;
	}
} // MyCred

// Prepare the variables, based on the product id, so we can apply the upgrade later in the code
switch ($product_id) {
	case '1': // ad posting fee
		$payment_product_custom_meta_marker = "ad_posting_fee";
		$payment_recurring = $payment_data['paid_ads']['first']['recurring'];
		$payment_duration = $payment_data['paid_ads']['first']['duration'];
		$payment_duration_type = $payment_data['paid_ads']['first']['durationtype'];
		$payment_recurring_meta = "ad_posting_fee_recurring";
		$payment_recurring_period_meta = "ad_posting_fee_recurring_period";
		$payment_expiration_meta = "ad_posting_fee_expiration";
		$email_message = _d("Your ad is now posted on our website.",841);

		// activate and publish the ad if the admin has not specified that they want to approve it manually
		if(get_post_meta($post_id, 'needs_activation', true)) {
			$email_message = _d('Your ad is active but it needs to be approved by an admin first.',652);
		} else {
			$update_ad['ID'] = $post_id;
			$update_ad['post_status'] = 'publish';
			wp_update_post($update_ad);
		}

		delete_post_meta($post_id, 'needs_payment'); // remove marker to know that the ad does not need payment anymore
		break;
	
	case '2': // Always on top
		$payment_product_custom_meta_marker = "always_on_top";
		$payment_recurring = $payment_data['always_on_top']['first']['recurring'];
		$payment_duration = $payment_data['always_on_top']['first']['duration'];
		$payment_duration_type = $payment_data['always_on_top']['first']['durationtype'];
		$payment_recurring_meta = "always_on_top_recurring";
		$payment_recurring_period_meta = "always_on_top_recurring_period";
		$payment_expiration_meta = "always_on_top_expiration";
		$email_message = _d("Your ad upgrade for \"Always on top\" is now active.",842);
		break;
	
	case '3': // Highlighted ads
		$payment_product_custom_meta_marker = "highlighted_ad";
		$payment_recurring = $payment_data['highlighted_ad']['first']['recurring'];
		$payment_duration = $payment_data['highlighted_ad']['first']['duration'];
		$payment_duration_type = $payment_data['highlighted_ad']['first']['durationtype'];
		$payment_recurring_meta = "highlighted_ad_recurring";
		$payment_recurring_period_meta = "highlighted_ad_recurring_period";
		$payment_expiration_meta = "highlighted_ad_expiration";
		$email_message = _d("Your ad upgrade for \"Highlighted ad\" is now active.",843);
		break;
	
	case '4': // Push to top
		$payment_product_custom_meta_marker = "push_ad";
		$payment_recurring = $payment_data['push']['first']['recurring'];
		$payment_duration = $payment_data['push']['first']['duration'];
		$payment_duration_type = $payment_data['push']['first']['durationtype'];
		$payment_recurring_meta = "push_ad_recurring";
		$payment_recurring_period_meta = "push_ad_recurring_period";
		$payment_expiration_meta = "push_ad_expiration";
		$email_message = _d("Your ad upgrade for \"Push ad\" is now active.",844);
		break;

	case '5': // Registration fee PERSONAL
		$payment_product_custom_meta_marker = "user_reg_personal";
		$payment_recurring = $payment_data['user_reg']['personal']['first']['recurring'];
		$payment_duration = $payment_data['user_reg']['personal']['first']['duration'];
		$payment_duration_type = $payment_data['user_reg']['personal']['first']['durationtype'];
		$payment_recurring_meta = "user_reg_recurring";
		$payment_recurring_period_meta = "user_reg_recurring_period";
		$payment_expiration_meta = "user_reg_expiration";
		$email_message = _d("Your account is not active on our website.",988);
		update_user_meta($post_id, 'user_type', 'personal');
		break;

	case '6': // Registration fee BUSINESS
		$payment_product_custom_meta_marker = "user_reg_business";
		$payment_recurring = $payment_data['user_reg']['business']['first']['recurring'];
		$payment_duration = $payment_data['user_reg']['business']['first']['duration'];
		$payment_duration_type = $payment_data['user_reg']['business']['first']['durationtype'];
		$payment_recurring_meta = "user_reg_recurring";
		$payment_recurring_period_meta = "user_reg_recurring_period";
		$payment_expiration_meta = "user_reg_expiration";
		$email_message = _d("Your business account is now active on our website.",989);
		update_user_meta($post_id, 'user_type', 'business');
		break;
}

// Apply the upgrade from the payment
if(in_array($product_id, array("5", "6"))) {
	update_user_meta($post_id, $payment_product_custom_meta_marker, '1'); // add marker to know that the upgrade was paid
	if($stripe_plan) {
		$current_subscription_plans = get_user_meta($post_id, 'stripe_subscription_plan', true);
		if(count($current_subscription_plans) == "0") {
			update_user_meta($post_id, 'stripe_subscription_plan', array($product_id => $stripe_plan));
		} else {
			$current_subscription_plans[$product_id] = $stripe_plan;
			update_user_meta($post_id, 'stripe_subscription_plan', $current_subscription_plans);
		}
	}
} else {
	update_post_meta($post_id, $payment_product_custom_meta_marker, '1'); // add marker to know that the upgrade was paid
	if($stripe_plan) {
		$current_subscription_plans = get_post_meta($post_id, 'stripe_subscription_plan', true);
		if(count($current_subscription_plans) == "0") {
			update_post_meta($post_id, 'stripe_subscription_plan', array($product_id => $stripe_plan));
		} else {
			$current_subscription_plans[$product_id] = $stripe_plan;
			update_post_meta($post_id, 'stripe_subscription_plan', $current_subscription_plans);
		}
	}
}
if($payment_duration) { // if the upgrade needs to expire
	global $payment_duration_types;
	$time_period = str_replace(array("D", "W", "M", "Y"), array("days", "weeks", "months", "years"), $payment_duration.' '.$payment_duration_types[$payment_duration_type]['2']);

	$expires_in = strtotime("+$time_period");
	// if there is leftover expiration time in the post then we add it to the current expiration time
	$available_expiration_time = get_post_meta($post_id, $payment_expiration_meta, true);
	if($available_expiration_time > current_time('timestamp')) {
		$expires_in = $expires_in + ($available_expiration_time - current_time('timestamp'));
	}
	if($stripe_subscription_renewal) { // if stripe was used then we use the renewal unix time from stripe
		$expires_in = $stripe_subscription_renewal;
	}
	if(in_array($product_id, array("5", "6"))) {
		update_user_meta($post_id, $payment_expiration_meta, $expires_in);
	} else {
		update_post_meta($post_id, $payment_expiration_meta, $expires_in);
	}

	if($payment_recurring == "1" ) { // if recurring payment
		if(in_array($product_id, array("5", "6"))) {
			update_user_meta($post_id, $payment_recurring_meta, '1'); // add marker to know that this payment is a recurring payment
			update_user_meta($post_id, $payment_recurring_period_meta, $time_period); // save the current payment plan's time duration so we can use it for future payments
		} else {
			update_post_meta($post_id, $payment_recurring_meta, '1'); // add marker to know that this payment is a recurring payment
			update_post_meta($post_id, $payment_recurring_period_meta, $time_period); // save the current payment plan's time duration so we can use it for future payments
		}
	}
} // if($payment_duration) { // if payment should expire

if($stripe_subscription_id) {
	if(in_array($product_id, array("5", "6"))) {
		$current_subscription_ids = get_user_meta($post_id, 'stripe_subscription_id', true);
		$current_subscription_ids[$product_id] = $stripe_subscription_id;
		update_user_meta($post_id, 'stripe_subscription_id', $current_subscription_ids);
	} else {
		$current_subscription_ids = get_post_meta($post_id, 'stripe_subscription_id', true);
		$current_subscription_ids[$product_id] = $stripe_subscription_id;
		update_post_meta($post_id, 'stripe_subscription_id', $current_subscription_ids);
	}
}



// save the payment in the site
$payment_args = array(
	'post_status'    => 'publish', // Post status
	'post_type'      => 'payment', // Taxonomy name
	'ping_status'    => 'closed', // Pingbacks or trackbacks allowed
	'comment_status' => 'closed' // If comments are open
);
if(in_array($product_id, array("5", "6"))) {
	$payment_args['post_title'] = $item_name.' - '.$ad_to_process->user_email; // The title of your post
	$payment_args['post_name'] = $item_name.' - '.$ad_to_process->user_login; // The slug for your post
} else {
	$payment_args['post_title'] = $item_name.' - '.$ad_to_process->post_title; // The title of your post
	$payment_args['post_name'] = $ad_to_process->post_title; // The slug for your post
}
$payment_id = wp_insert_post($payment_args);
update_post_meta($payment_id, 'product_id', $product_id);
update_post_meta($payment_id, 'product_name', $item_name);
update_post_meta($payment_id, 'product_price', $product_price." ".$payment_currency);
if(in_array($product_id, array("5", "6"))) {
	update_post_meta($payment_id, 'user_id', $post_id);
} else {
	update_post_meta($payment_id, 'post_id', $post_id);
}
update_post_meta($payment_id, 'processor', $_GET['processor']);
foreach ($payment as $key => $value) {
	if(is_bool($value)) {
		$value = $value ? "true" : "false";
	}
	update_post_meta($payment_id, $key, $value);
}

// Send email to buyer
$body  = "Hi ".$first_name.",<br /><br />"._d('Your payment was successful.',653)."<br />".$email_message."<br /><br />";
$body .= _d('Payment price',654).": ".$product_price." ".$payment_currency."<br />";
$body .= _d('Payment type',655).": ".$item_name."<br />";
if(in_array($product_id, array("5", "6"))) {
	$body .= _d('Payment for',630).": ".get_author_posts_url($post_id)."<br />";
} else {
	$body .= _d('Payment for',630).": ".get_post_permalink($post_id)."<br />";
}
dolce_email($payer_email, _d('Payment received!',656), $body);

// Send email to admin
$body  = _d('You have received a new payment.',657)."<br /><br />";
$body .= _d('Payment price',654).": ".$product_price." ".$payment_currency."<br />";
$body .= _d('Payment type',655).": ".$item_name."<br />";
if(in_array($product_id, array("5", "6"))) {
	$body .= _d('Payment for',630).": ".get_author_posts_url($post_id)."<br />";
} else {
	$body .= _d('Payment for',630).": ".get_post_permalink($post_id)."<br />";
}
$body .= "Payment details: ".get_post_permalink($payment_id);
dolce_email(get_option('notifications_email'), _d('Payment received!',656), $body);


http_response_code(200);
die(json_encode(array('status' => 'ok', 'msg' => _d('Payment successful!',658))));