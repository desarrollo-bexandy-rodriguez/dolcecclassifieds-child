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
    if(get_option('payment_mycred') == "1") {
        child_generate_mycred_payment_button($product_id, $post_id);
    }
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

    $show_cancel_mycred_subscription_button = false;
    if($post_data && get_post_meta($post_id, 'push_ad_recurring', true)) {
        $show_cancel_mycred_subscription_button = true;
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

    $duration_push_list = array(
            '0' => 'Just One Time',
            '5' => 'Every 5 minutes',
            '15' => 'Every 15 minutes',
            '30' => 'Every 30 minutes',
            '60' => 'Every hour',
            '120' => 'Every 2 hours',
            '180' => 'Every 3 hours',
            '240' => 'Every 4 hours',
            '360' => 'Every 6 hours',
            '1440' => 'Every 1 '._d('day',452),
            '2880' => 'Every 2 '._d('days',337),
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
            <div class="message-top" style="font-size: 1.5em"><h4><?= 'Here you can buy the self renews to facilitate that your ad is always updated, you will have more visibility and many more calls' ?>.</h4></div>
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
                            <div class="generated-payment-buttons"><?php child_generate_payment_buttons('1', $post_id); ?></div>
                            <?php generate_mycred_balance_buttons(); ?>
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
                                <div class="generated-payment-buttons"><?php child_generate_payment_buttons('2', $post_id); ?></div>
                                <?php generate_mycred_balance_buttons(); ?>
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
                                <div class="generated-payment-buttons"><?php child_generate_payment_buttons('3', $post_id); ?></div>
                                <?php generate_mycred_balance_buttons(); ?>
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

                        <h4><?= 'Self-renew'?>
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
                                    echo ' <span>-</span> <span class="expired">'.'stopped'.'</span>';
                                }
                            }
                            echo '</span>';
                        } else {
                            echo '<span class="purchased hide"></span>';
                        }
                        ?>
                        </h4>
                        <p><?= 'For each renewal '.$payment_data['push']['first']['price'].' credits will be deducted'?></p>
                        <?php if(current_user_can('level_10')) { ?>
                            <div class="admin-upgrade-options">
                                <div class="clear5"></div>
                                <form action="" method="post" class="expires-in l">
                                    <input type="hidden" name="upgrade_id" value="4" />
                                    <div class="l"><?= 'Renew in' ?>:</div>
                                    <div class="fake-select fake-select-time rad3 no-selection l">
                                        <div class="first"><span class="text l"></span> <span class="icon icon-arrow-up hide"></span><span class="icon icon-arrow-down"></span></div>
                                        <div class="options rad5 shadow hide">
                                            <?php
                                            foreach ($duration_push_list as $key => $value) {
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

                                    <div class="remove-upgrade-button round-corners-button rad25 l<?php if(!get_post_meta($post_id, 'push_ad', true)) echo " hide"; ?>" data-saving="<?= 'Sopping' ?>" data-saved="<?= 'Stopped' ?>" data-default="<?= 'Stop' ?>">
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
                            <div class="admin-upgrade-options">
                                <div class="clear5"></div>
                                <form action="" method="post" class="expires-in l">
                                    <input type="hidden" name="upgrade_id" value="4" />
                                    <div class="l">Renew itself in:</div>
                                    <div class="fake-select fake-select-time push_ads_recurring rad3 no-selection l">
                                        <div class="first"><span class="text l"></span> <span class="icon icon-arrow-up hide"></span><span class="icon icon-arrow-down"></span></div>
                                        <div class="options rad5 shadow hide">
                                            <?php
                                            foreach ($duration_push_list as $key => $value) {

                                                echo '<div data-value="'.$key.'" class="option">'.$value.'</div>';
                                            }
                                            ?>
                                        </div> <!-- options -->
                                        <input type="hidden" name="upgrade_duration" value="0" />
                                    </div> <!-- fake-selector -->
                                </form> <!-- expires-in -->
                            </div>


                            <div class="payment-buttons col-100 text-center">
                                <div class="generated-payment-buttons"><?php child_generate_payment_buttons('4', $post_id); ?></div>
                                <?php generate_mycred_balance_buttons(); ?>
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

                        if($show_cancel_mycred_subscription_button) {
                            child_generate_mycred_cancel_subscription_button('4', $post_id);
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

	$price = '<span class="value">'.get_option('payment_currency_symbol_before').$payment_data['price'].' '.get_option('payment_currency_symbol_after').'</span>';

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

function child_generate_mycred_payment_button($product_id, $post_id="") {
    $current_user = wp_get_current_user();
    if(in_array($product_id, array("5", "6"))) {
        $user_email = $current_user->user_email;
        $payment_data = get_all_payment_data();
    } else {
        $ad_to_process = get_post($post_id);
        $user_email = get_the_author_meta('user_email', $ad_to_process->post_author);
        $payment_data = get_all_payment_data($post_id);
    }

    switch ($product_id) {
        case '1': // posting fee
            $item_name = _d('Ad posting fee',435);
            $amount = $payment_data['paid_ads']['first']['price'];
            $payment_recurring = $payment_data['paid_ads']['first']['recurring'];
            break;
        
        case '2': // Always on top
            $item_name = _d('Upgrade',517)." - "._d('Always on top',240);
            $amount = $payment_data['always_on_top']['first']['price'];
            $payment_recurring = $payment_data['always_on_top']['first']['recurring'];
            break;
        
        case '3': // Highlighted ads
            $item_name = _d('Upgrade',517)." - "._d('Highlighted ads',244);
            $amount = $payment_data['highlighted_ad']['first']['price'];
            $payment_recurring = $payment_data['highlighted_ad']['first']['recurring'];
            break;
        
        case '4': // Push to top
            $item_name = _d('Upgrade',517)." - "._d('Push to top',437);
            $amount = $payment_data['push']['first']['price'] * 100;
            $payment_recurring = $payment_data['push']['first']['recurring'];
            break;

        case '5': // Registration fee PERSONAL
            $item_name = _d('Registration fee',978);
            $amount = $payment_data['user_reg']['personal']['first']['price'];
            $payment_recurring = $payment_data['user_reg']['personal']['first']['recurring'];
            break;

        case '6': // Registration fee BUSINESS
            $item_name = _d('Business registration fee',979);
            $amount = $payment_data['user_reg']['business']['first']['price'];
            $payment_recurring = $payment_data['user_reg']['business']['first']['recurring'];
            break;

    }
    ?>

    <button id="pay_button_<?=$product_id?>" class="pay-button pay-button-mycred round-corners-button rad25">Activate self renewal</button>

    <script type="text/javascript">
    jQuery(document).ready(function($) {

        $('#pay_button_<?=$product_id?>').on('click', function(e) {
            e.preventDefault();
            // Open Checkout with further options
            if(!$('#overlay_for_<?=$product_id?>').length) {
				$('body').append('<div class="overlay hide" id="overlay_for_<?=$product_id?>"></div>');
			}

            if(!$('#message_for_<?=$product_id?>').length) {
				$('body').append('<div class="stripe-payment-processing-message-container hide" id="message_for_<?=$product_id?>"><div class="close r"><span class="icon icon-cancel"></span> <?=addslashes(_d('close',195))?></div><div class="clear5"></div><div class="stripe-payment-processing-message rad5 shadow text-center"><span class="text"><?=addslashes(_d('Processing payment!',706))?></span><div class="clear30"></div><img class="icon loader" src="<?=get_template_directory_uri()?>/plugins/private-messages/loader.svg" alt="" /><span class="icon icon-for-err icon-cancel hide no-selection"></span><span class="icon icon-for-ok icon-checkmark hide no-selection"></span><div class="wait"><?=addslashes(_d('please wait...',707))?></div></div></div>');
			}

            $('#message_for_<?=$product_id?>').show();
				var top = (($(window).outerHeight() - $('#message_for_<?=$product_id?>').outerHeight()) / 2);
				var left = (($(window).outerWidth() - $('#message_for_<?=$product_id?>').outerWidth()) / 2);
				$('#message_for_<?=$product_id?>').css({'top': top, 'left': left});
            
            var vars = {
                'item_nr': <?=$product_id?>,
                'post_id': <?=$post_id?>,
                'duration_push_ad': $('.push_ads_recurring input[name="upgrade_duration"]').val()
            };
            
            $.ajax({
                type: "POST",
                url: wpvars.wpchildthemeurl+'/IPN.php?processor=mycred',
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

function child_generate_payment_option_form($payment_name, $form_payment_data="", $user_type) {
	global $payment_duration_types;
	$payment_currency = get_option('payment_currency');
	$payment_data = get_option('payment_'.$payment_name.'_data');
	if (class_exists( 'myCRED_Core' )) {
		$payment_mycred = get_option('payment_mycred');
		if ($payment_mycred == '1' && $payment_name == 'user_reg') {
			return 'Option not enabled to MyCred Gateway Payment';
		}
	}

	foreach (array("first") as $plan_id) {
		if($plan_id == "second" && !$payment_data[$user_type][$plan_id]['price'] && !$payment_data[$user_type][$plan_id]['duration']) {
			$extra_class_div = " hide";
		}
		if($plan_id == "first" && ($payment_data[$user_type]['second']['price'] || $payment_data[$user_type]['second']['duration'])) {
			$extra_style_button = ' style="display: none"';
		}
	?>
		<div class="<?=$plan_id?>-payment-plan<?=$extra_class_div?>">
			<div class="form-label">
				<label class="label" for="payment_<?=$payment_name?>_price_<?=$user_type?>_<?=$plan_id?>"><?=_d('Price',236)?></label>
			</div> <!-- form-label -->
			<div class="form-input">
				<div class="err-msg hide"></div>
				<input type="text" 
					name="payment_<?=$payment_name?>_price[<?=$user_type?>][<?=$plan_id?>]" 
					value="<?=$payment_data[$user_type][$plan_id]['price']?>" 
					id="payment_<?=$payment_name?>_price_<?=$user_type?>_<?=$plan_id?>" 
					class="input text-center" 
					size="10" 
				/> 
				<span class="payments-currency"><?=$payment_currency?></span>
			</div> <!-- form-input --> <div class="formseparator"></div>

			<div class="form-label">
				<label class="label" for="payment_<?=$payment_name?>_duration_<?=$user_type?>_<?=$plan_id?>">
				<?php
					if($payment_name[$user_type] == "push") {
						_de('Push each day for',248);
					} else {
						_de('Payment will last for',237);
					}
				?>
				</label>
			</div> <!-- form-label -->
			<div class="form-input">
				<div class="err-msg hide"></div>
				<input type="text" 
					name="payment_<?=$payment_name?>_duration[<?=$user_type?>][<?=$plan_id?>]" 
					value="<?=$payment_data[$user_type][$plan_id]['duration']?>" 
					id="payment_<?=$payment_name?>_duration_<?=$user_type?>_<?=$plan_id?>" 
					class="input text-center l" 
					size="10" 
				/>
				<div class="fake-select equal-input fake-select-duration rad3 no-selection l">
					<div class="first"><span class="text l"></span> <span class="icon icon-arrow-up hide"></span><span class="icon icon-arrow-down"></span></div>
					<div class="options rad5 shadow hide">
						<?php
						foreach ($payment_duration_types as $key => $value) {
							$selected = ($payment_data[$user_type][$plan_id]['durationtype'] == $key) ? ' selected' : '';
							echo '<div data-value="'.$key.'" class="option'.$selected.'">'.$value['0'].'</div>';
						}
						?>
					</div> <!-- options -->
					<input type="hidden" name="payment_<?=$payment_name?>_durationtype[<?=$user_type?>][<?=$plan_id?>]" value="<?=$payment_data[$user_type][$plan_id]['durationtype']?>" />
				</div> <!-- fake-selector -->
				<div class="help"><b>!</b> <?=_d('Leaving this empty means the upgrade will never expire',238)?></div>
			</div> <!-- form-input --> <div class="formseparator"></div>

			<div class="form-label">
				<label class="label" for="payment_<?=$payment_name?>_recurring_<?=$user_type?>_<?=$plan_id?>"><?=_d('Recurring payments?',239)?></label>
			</div> <!-- form-label -->
			<div class="form-input">
				<div class="err-msg hide"></div>
				<div class="toggle rad25 l">
					<div data-value="1" class="toggle-text toggle-yes l<?php if($payment_data[$user_type][$plan_id]['recurring'] != "1") { echo ' hide'; } ?>"><?=_d('yes',85)?></div>
					<div class="pin l">&nbsp;</div>
					<div data-value="2" class="toggle-text toggle-no r<?php if($payment_data[$user_type][$plan_id]['recurring'] != "2" && $payment_data[$user_type][$plan_id]['recurring']) { echo ' hide'; } ?>"><?=_d('no',86)?></div>
					<input type="hidden" class="input" maxlength="1" name="payment_<?=$payment_name?>_recurring[<?=$user_type?>][<?=$plan_id?>]" value="<?=$payment_data[$user_type][$plan_id]['recurring']?>" />
				</div> <!-- toggle -->
			</div> <!-- form-input --> <div class="clear"></div>

		</div> <!-- <?=$plan_id?>-payment-plan -->
	<?php
	} // foreach ($form_payment_data[$user_type] as $key => $payment_data) {
} // function generate_payment_option_form($input_name)

function generate_mycred_balance_buttons() {
    if (class_exists('myCRED_Core')) {
        $mycred=mycred();
        if (!$mycred->exclude_user(get_current_user_id()) ) {
            $balance = $mycred->get_users_balance( get_current_user_id() );
            $atts = array(
                'gateway' => 'paypal-standard',
                'amount'  => '100',
                'class' => 'pay-button pay-button-paypal round-corners-button rad25'
            );
        }
    } ?>
    <div class="mycred-balance">
        <h3>My Credits</h3>
        <h4>Credits Available</h4>
        <p><?= $balance ?> credits</p>
        <h4>Buy Credits</h4>
        <form id="mycred-balance" oninput="result.value=parseInt(amount.value)*parseInt(tasa.value)">
            <label for="amount">Amount: </label>
            <input type="number" name="amount" id="amount" value="10" min="10"> Credits
            <input type="hidden" name="tasa" id="tasa" value="0.5">
            <p>
                = <output name="result" id="result" for="amount tasa">0</output>
            </p>
            
        </form>
        <p>Pay with:
        <?php echo do_shortcode('[mycred_buy gateway="paypal-standard" amount=""]<img src="'.get_stylesheet_directory_uri().'/icon-font/paypal.png"><img src="'.get_stylesheet_directory_uri().'/icon-font/paypal-name.png">[/mycred_buy]'); ?>
        <br>
        <?php echo do_shortcode('[mycred_buy gateway="paypal-standard" amount=""]<img src="'.get_stylesheet_directory_uri().'/icon-font/visa.png"><img src="'.get_stylesheet_directory_uri().'/icon-font/mastercard.png"><img src="'.get_stylesheet_directory_uri().'/icon-font/american-express.png">[/mycred_buy]'); ?>
        </p>
        
    </div>
    <script type="text/javascript">
        jQuery(document).ready(function($) {
            $('form#mycred-balance input#amount').on('change', function(event) {
                var input = $(this);
                var val = input.val();
                var exchange = $('input#tasa').val();
                
                var result = val * exchange;
                $('output#result').text(result+' €');

                $('div.mycred-balance').find('a').each(function() {
                   var url = $( this ).attr('href');
                   var updurl = url.replace(/(amount=).*/,'amount=' + val );
                   $( this ).attr('href', updurl);
                });                
        });
    });
    </script>
<?php // function generate_mycred_balance_buttons()
}

function child_generate_mycred_cancel_subscription_button($item_id, $post_id) { ?>
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
                    data: { action: 'cancel-mycred-subscription', form_data: form_data },
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
                                setTimeout(function() {
                                    <?php if(in_array($product_id, array("5", "6"))) { ?>
                                        location.reload();
                                    <?php } else { ?>
                                        window.location = '<?=get_post_permalink($post_id)?>';
                                    <?php } ?>
                                }, 4000);
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
        <div class="cancel-subscription-button cancel-subscription-button-stripe round-corners-button rad25" data-item-id="<?=$item_id?>" data-saving="<?= 'Stopping' ?>" data-saved="<?= 'Self-renew stopped' ?>" data-default="<?= 'Stop self-renew' ?>" data-error="<?=_d('Error',94)?>">
            <span class="text"><?= 'Stop/Modify self-renew' ?></span>
            <span class="icon icon-for-default icon-arrow-right"></span>
            <svg version="1.1" class="icon icon-for-saving loader r hide" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 50 50" style="enable-background:new 0 0 50 50;" xml:space="preserve"><path fill="#000" d="M43.935,25.145c0-10.318-8.364-18.683-18.683-18.683c-10.318,0-18.683,8.365-18.683,18.683h4.068c0-8.071,6.543-14.615,14.615-14.615c8.072,0,14.615,6.543,14.615,14.615H43.935z"><animateTransform attributeType="xml" attributeName="transform" type="rotate" from="0 25 25" to="360 25 25" dur="0.6s" repeatCount="indefinite"/></path></svg>
            <span class="icon icon-for-saved icon-checkmark hide"></span>
            <span class="icon icon-for-error icon-cancel hide"></span>
        </div> <!-- cancel-subscription-button -->
        <?php generate_mycred_balance_buttons(); ?>
        <div class="clear5"></div>
        <?php
        if(in_array($item_id, array("5", "6"))) {
            $button_info_text = 'If you stop the self-renew it will not consume the credits, you can activate it when you need it at the moment that is most comfortable for you';
        } else {
            $button_info_text = 'If you stop the self-renew it will not consume the credits, you can activate it when you need it at the moment that is most comfortable for you';
        }
        ?>
        <div class="cancel-subscription-button-description"><?=$button_info_text?></div>
    </div>
    <?php
}

