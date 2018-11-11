<?php
if(!defined('error_reporting')) { define('error_reporting', '0'); }
ini_set( 'display_errors', error_reporting );
if(error_reporting == '1') { error_reporting( E_ALL ); }
if(isdolcetheme !== 1) { die(); }

/*
Template Name: Admin - Edit Payment Settings
Revision #: 00898738537356724431
*/

global $all_payment_currencies, $payment_duration_types;
$current_user = wp_get_current_user();
if (!current_user_can('level_10')) { wp_redirect(home_url()); die(); }

$payment_paypal = get_option('payment_paypal');
$payment_paypal_address = get_option('payment_paypal_address');
$payment_paypal_sandbox = get_option('payment_paypal_sandbox');
$payment_paypal_sandbox_address = get_option('payment_paypal_sandbox_address');

$payment_stripe = get_option('payment_stripe');
$payment_stripe_rememberme = get_option('payment_stripe_rememberme');
$payment_stripe_live_secret_key = get_option('payment_stripe_live_secret_key');
$payment_stripe_live_publishable_key = get_option('payment_stripe_live_publishable_key');
$payment_stripe_sandbox = get_option('payment_stripe_sandbox');
$payment_stripe_test_secret_key = get_option('payment_stripe_test_secret_key');
$payment_stripe_test_publishable_key = get_option('payment_stripe_test_publishable_key');

$payment_mycred = get_option('payment_mycred');

if(defined('dolce_demo_theme')) {
	$payment_stripe_live_secret_key = 'hidden in demo installation';
	$payment_stripe_live_publishable_key = 'hidden in demo installation';
	$payment_stripe_test_secret_key = 'hidden in demo installation';
	$payment_stripe_test_publishable_key = 'hidden in demo installation';
}

$payment_currency = get_option('payment_currency');
$payment_currency_symbol_before = get_option('payment_currency_symbol_before');
$payment_currency_symbol_after = get_option('payment_currency_symbol_after');

$payment_user_reg_data = get_option('payment_user_reg_data');
$payment_paid_ads_data = get_option('payment_paid_ads_data');
$payment_always_on_top_data = get_option('payment_always_on_top_data');
$payment_highlighted_ad_data = get_option('payment_highlighted_ad_data');
$payment_push_data = get_option('payment_push_data');

$user_types = array( 'personal' => _d('Private users',831) );
if(get_option('activate_business_users') == "1") {
	$user_types['business'] = _d('Business users',683);
	$extra_class_upgrade_content = " upgrade-content-more-userss";
} else {
	$user_types['personal'] = _d('All users',832);
	$extra_class_upgrade_content = " upgrade-content-single-user";
}
get_header();

if(!defined('private_messages_plugin')) {
	echo '<div class="clear20"></div>';
	echo '<div class="ok rad5">This page is not availabe in the free version of the theme. It\'s only available if you purchase a theme license.';
	echo '<div class="clear5"></div>';
	echo 'You can buy a theme license from our website here: <a href="https://dolceclassifieds.com/" style="color: $blue"><u><b>https://dolceclassifieds.com/</b></u></a></div>';
	echo '<div class="clear40"></div>';
	get_footer('no-sidebar');
	die();
}

?>

<div class="settings-page payment-settings col-70 center">
	<h3 class="title l"><span class="icon icon-money"></span> <?=_d('Payment settings',192)?></h3>
	<div class="help-tooltip l">
		<a href="<?=get_permalink(get_option('admin_documentation'))?>#payment-settings">
			<span class="icon icon-help"></span>
			<div class="tooltip-text shadow rad5 hide"><?=_d('Go to help page',82)?></div>
		</a>
	</div>
	<div class="clear"></div>
	<?php
	if(defined('dolce_demo_theme')) {
		echo '<div class="ok rad3">'._d('Since this is a demo installation you won\'t be able to change the options.',83).'</div>';
	}
	?>
	<div class="page-content">
		<form action="" method="post" class="form-styling settings-form post-form">
			<input type="hidden" name="action" value="edit-payment-settings" />

			<div class="form-msg form-err-msg err rad5 hide"></div>
			<div class="form-msg form-ok-msg ok rad5 hide"></div>
			<div class="clear40"></div>

			<script type="text/javascript">
				jQuery(document).ready(function($) {
					$('.payment-options .payment-tab').on('click', function(event) {
						$('.payment-tab-active').removeClass('payment-tab-active');
						$(this).addClass('payment-tab-active');
						$('.tabs-content .tab').slideUp('fast');
						$('.tabs-content #'+$(this).data('tab')).slideDown('fast');
					});
					$('.close').on('click', function(event) {
						$('.payment-tab-active').removeClass('payment-tab-active');
						$(this).parent().slideUp('fast');
					});
					$('input[name="payment_paypal"], input[name="payment_stripe"], input[name="payment_mycred"]').on('change', function(event) {
						var id = $(this).parents('.tab').attr('id');
						var icon = $('.payment-options .payment-tab[data-tab="'+id+'"] .icon');
						if($(this).val() == "1") { icon.show(); } else { icon.hide(); }
					});
					$('input[name="payment_currency"]').on('input', function(event) {
						var text = $(this).val().replace(/[^a-zA-Z]/g, '').toUpperCase();
						$(this).val(text);
						$('.payments-currency').text(text);
					});

					// activating/deactivating paypal payments
					$('input[name="payment_paypal"]').on('change', function(event) {
						var val = $(this).val();
						var div = $('.payment_paypal-container');
						if(val == "1" && !div.is(':visible')) {
							div.slideDown('fast');
						} else if(val == "2" && div.is(':visible')) {
							div.slideUp('fast');
						}
					});

					// activating/deactivating paypal sandbox
					$('input[name="payment_paypal_sandbox"]').on('change', function(event) {
						var val = $(this).val();
						var div = $('.payment_paypal_sandbox_address-container');
						if(val == "1" && !div.is(':visible')) {
							div.slideDown('fast');
						} else if(val == "2" && div.is(':visible')) {
							div.slideUp('fast');
						}
					});

					// activating/deactivating STRIPE payments
					$('input[name="payment_stripe"]').on('change', function(event) {
						var val = $(this).val();
						var div = $('.payment_stripe-container');
						if(val == "1" && !div.is(':visible')) {
							div.slideDown('fast');
						} else if(val == "2" && div.is(':visible')) {
							div.slideUp('fast');
						}
					});

					// activating/deactivating STRIPE sandbox
					$('input[name="payment_stripe_sandbox"]').on('change', function(event) {
						var val = $(this).val();
						var div = $('.payment_stripe_test_key-container');
						if(val == "1" && !div.is(':visible')) {
							div.slideDown('fast');
						} else if(val == "2" && div.is(':visible')) {
							div.slideUp('fast');
						}
					});

					// activating/deactivating MyCred payments
					$('input[name="payment_mycred"]').on('change', function(event) {
						var val = $(this).val();
						var div = $('.payment_mycred-container');
						if(val == "1" && !div.is(':visible')) {
							div.slideDown('fast');
						} else if(val == "2" && div.is(':visible')) {
							div.slideUp('fast');
						}
					});

					$('input[name="payment_mycred"]').on('change', function(event) {
						var id = $(this).parents('.tab').attr('id');
						var icon = $('.payment-options .payment-tab[data-tab="'+id+'"] .icon');
						if($(this).val() == "1") { 
							icon.show(); 
							$('.payment-options .payment-tab[data-tab="paypal"] .icon').hide(); 
							$('.payment-options .payment-tab[data-tab="stripe"] .icon').hide(); 
							$('input[name="payment_paypal"]').val(2);
							$('input[name="payment_stripe"]').val(2);
							$('input[name="payment_currency"]').val('Crédito');
							$('input[name="payment_currency_symbol_before"]').val('');
							$('input[name="payment_currency_symbol_after"]').val('Cre');
							$('input[name="payment_currency"]').attr("readonly","readonly");
							$('input[name="payment_currency_symbol_before"]').attr("readonly","readonly");
							$('input[name="payment_currency_symbol_after"]').attr("readonly","readonly");
						} else { 
							icon.hide(); 
							$('input[name="payment_currency"]').val('');
							$('input[name="payment_currency_symbol_before"]').val('');
							$('input[name="payment_currency_symbol_after"]').val('');
							$('input[name="payment_currency"]').removeAttr("readonly");
							$('input[name="payment_currency_symbol_before"]').removeAttr("readonly");
							$('input[name="payment_currency_symbol_after"]').removeAttr("readonly");
						}
					});


					$('.payment-settings .upgrade .upgrade-content .upgrade .upgrade-title').on('click', function(event) {
						$(this).parents('.upgrade-content').toggleClass('upgrade-content-more-users');
					});

					$('.upgrade .upgrade-content .upgrade').on('click', '.add_extra_payment_plan', function(event) {
						$(this).hide().closest('.upgrade-content').find('.second-payment-plan').show();
					});
					$('.upgrade .upgrade-content .upgrade').on('click', '.remove_extra_payment_plan', function(event) {
						var upgrade_content_div = $(this).closest('.upgrade-content')
						upgrade_content_div.find('.second-payment-plan').hide();
						upgrade_content_div.find('.second-payment-plan input').val('');
						upgrade_content_div.find('.first-payment-plan .add_extra_payment_plan').show();
						check_all_fake_selects();
						check_toggle_state(upgrade_content_div);
					});
				});
			</script>

			<h4><?=_d('Choose the payment processors you want to use in your site',193)?></h4>
			<div class="clear20"></div>

			<div class="payment-options text-center l rad5">
				<div class="payment-tab round-corners-button rad25 l" data-tab="paypal">PayPal <span class="icon icon-circle<?php if($payment_paypal != "1") { echo " hide"; } ?>"></span></div>
				<div class="payment-tab round-corners-button rad25 l" data-tab="stripe">Stripe <span class="icon icon-circle<?php if($payment_stripe != "1") { echo " hide"; } ?>"></span></div>
				<?php if (class_exists( 'myCRED_Core' )) { ?>
				<div class="payment-tab round-corners-button rad25 l" data-tab="mycred">MyCred <span class="icon icon-circle<?php if($payment_mycred != "1") { echo " hide"; } ?>"></span></div>
				<?php } ?>
				<div class="clear"></div>
			</div>
			<div class="clear20"></div>

			<div class="tabs-content">
				<div class="tab col-100 rad5 hide" id="paypal">
					<h4 class="rad25 l">PayPal <?=_d('settings',194)?></h4>
					<div class="close rad25 r"><span class="icon icon-cancel"></span> <?=_d('close',195)?></div>
					<div class="clear30"></div>

					<div class="form-label">
						<label class="label" for="payment_paypal"><?=_d('Activate PayPal payments?',196)?></label>
					</div> <!-- form-label -->
					<div class="form-input">
						<div class="err-msg hide"></div>
						<div class="toggle rad25 l">
							<div data-value="1" class="toggle-text toggle-yes l<?php if($payment_paypal != "1") { echo ' hide'; } ?>"><?=_d('yes',85)?></div>
							<div class="pin l">&nbsp;</div>
							<div data-value="2" class="toggle-text toggle-no r<?php if($payment_paypal != "2" && $payment_paypal) { echo ' hide'; } ?>"><?=_d('no',86)?></div>
							<input type="hidden" class="input" maxlength="1" name="payment_paypal" value="<?=$payment_paypal?>" />
						</div> <!-- toggle -->
					</div> <!-- form-input --> <div class="formseparator"></div>

					<div class="payment_paypal-container<?php if($payment_paypal != "1") { echo ' hide'; } ?>">
						<div class="form-label">
							<label class="label" for="payment_paypal_address"><?=_d('Your PayPal email address',197)?> <span class="mandatory icon icon-asterisk"></span></label>
						</div> <!-- form-label -->
						<div class="form-input">
							<div class="err-msg hide"></div>
							<input type="text" name="payment_paypal_address" maxlength="200" value="<?=$payment_paypal_address?>" id="payment_paypal_address" class="input col-100" />
						</div> <!-- form-input --> <div class="formseparator"></div>

						<div class="form-label">
							<label class="label" for="payment_paypal_sandbox"><?=_d('Activate PayPal test mode?',198)?></label>
						</div> <!-- form-label -->
						<div class="form-input">
							<div class="err-msg hide"></div>
							<div class="toggle rad25 l">
								<div data-value="1" class="toggle-text toggle-yes l<?php if($payment_paypal_sandbox != "1") { echo ' hide'; } ?>"><?=_d('yes',85)?></div>
								<div class="pin l">&nbsp;</div>
								<div data-value="2" class="toggle-text toggle-no r<?php if($payment_paypal_sandbox != "2" && $payment_paypal_sandbox) { echo ' hide'; } ?>"><?=_d('no',86)?></div>
								<input type="hidden" class="input" maxlength="1" name="payment_paypal_sandbox" value="<?=$payment_paypal_sandbox?>" />
							</div> <!-- toggle -->
						</div> <!-- form-input --> <div class="formseparator"></div>

						<div class="payment_paypal_sandbox_address-container<?php if($payment_paypal_sandbox != "1") { echo ' hide'; } ?>">
							<div class="form-label">
								<label class="label" for="payment_paypal_sandbox_address"><?=_d('Your PayPal sandbox email',199)?> <span class="mandatory icon icon-asterisk"></span></label>
							</div> <!-- form-label -->
							<div class="form-input">
								<div class="err-msg hide"></div>
								<input type="text" name="payment_paypal_sandbox_address" maxlength="200" value="<?=$payment_paypal_sandbox_address?>" id="payment_paypal_sandbox_address" class="input col-100" />
							</div> <!-- form-input --> <div class="formseparator"></div>
						</div> <!-- payment_paypal_sandbox_address-container -->
					</div> <!-- payment_paypal-container -->
				</div> <!-- paypal -->

				<div class="tab col-100 rad5 hide" id="stripe">
					<h4 class="rad25 l">STRIPE <?=_d('settings',194)?></h4>
					<div class="close rad25 r"><span class="icon icon-cancel"></span> <?=_d('close',195)?></div>
					<div class="clear30"></div>

					<div class="form-label">
						<label class="label" for="payment_stripe"><?=_d('Activate STRIPE payments?',200)?></label>
					</div> <!-- form-label -->
					<div class="form-input">
						<div class="err-msg hide"></div>
						<div class="toggle rad25 l">
							<div data-value="1" class="toggle-text toggle-yes l<?php if($payment_stripe != "1") { echo ' hide'; } ?>"><?=_d('yes',85)?></div>
							<div class="pin l">&nbsp;</div>
							<div data-value="2" class="toggle-text toggle-no r<?php if($payment_stripe != "2" && $payment_stripe) { echo ' hide'; } ?>"><?=_d('no',86)?></div>
							<input type="hidden" class="input" maxlength="1" name="payment_stripe" value="<?=$payment_stripe?>" />
						</div> <!-- toggle -->
					</div> <!-- form-input --> <div class="formseparator"></div>

					<div class="payment_stripe-container<?php if($payment_stripe != "1") { echo ' hide'; } ?>">
						<div class="form-label">
							<label class="label" for="payment_stripe_live_secret_key"><?=_d('STRIPE live secret key',201)?> <span class="mandatory icon icon-asterisk"></span></label>
						</div> <!-- form-label -->
						<div class="form-input">
							<div class="err-msg hide"></div>
							<input type="text" name="payment_stripe_live_secret_key" value="<?=$payment_stripe_live_secret_key?>" id="payment_stripe_live_secret_key" class="input col-100" />
						</div> <!-- form-input --> <div class="formseparator"></div>

						<div class="form-label">
							<label class="label" for="payment_stripe_live_publishable_key"><?=_d('STRIPE live publishable key',202)?> <span class="mandatory icon icon-asterisk"></span></label>
						</div> <!-- form-label -->
						<div class="form-input">
							<div class="err-msg hide"></div>
							<input type="text" name="payment_stripe_live_publishable_key" value="<?=$payment_stripe_live_publishable_key?>" id="payment_stripe_live_publishable_key" class="input col-100" />
						</div> <!-- form-input --> <div class="formseparator"></div>

						<div class="form-label">
							<label class="label" for="payment_stripe_rememberme"><?=_d('Show "remember me" field?',203)?></label>
						</div> <!-- form-label -->
						<div class="form-input">
							<div class="err-msg hide"></div>
							<div class="toggle rad25 l">
								<div data-value="1" class="toggle-text toggle-yes l<?php if($payment_stripe_rememberme != "1") { echo ' hide'; } ?>"><?=_d('yes',85)?></div>
								<div class="pin l">&nbsp;</div>
								<div data-value="2" class="toggle-text toggle-no r<?php if($payment_stripe_rememberme != "2" && $payment_stripe_rememberme) { echo ' hide'; } ?>"><?=_d('no',86)?></div>
								<input type="hidden" class="input" maxlength="1" name="payment_stripe_rememberme" value="<?=$payment_stripe_rememberme?>" />
							</div> <!-- toggle -->
						</div> <!-- form-input --> <div class="formseparator"></div>

						<div class="form-label">
							<label class="label" for="payment_stripe_sandbox"><?=_d('Activate STRIPE test mode?',204)?></label>
						</div> <!-- form-label -->
						<div class="form-input">
							<div class="err-msg hide"></div>
							<div class="toggle rad25 l">
								<div data-value="1" class="toggle-text toggle-yes l<?php if($payment_stripe_sandbox != "1") { echo ' hide'; } ?>"><?=_d('yes',85)?></div>
								<div class="pin l">&nbsp;</div>
								<div data-value="2" class="toggle-text toggle-no r<?php if($payment_stripe_sandbox != "2" && $payment_stripe_sandbox) { echo ' hide'; } ?>"><?=_d('no',86)?></div>
								<input type="hidden" class="input" maxlength="1" name="payment_stripe_sandbox" value="<?=$payment_stripe_sandbox?>" />
							</div> <!-- toggle -->
						</div> <!-- form-input --> <div class="formseparator"></div>

						<div class="payment_stripe_test_key-container<?php if($payment_stripe_sandbox != "1") { echo ' hide'; } ?>">
							<div class="form-label">
								<label class="label" for="payment_stripe_test_secret_key"><?=_d('STRIPE test secret key',205)?> <span class="mandatory icon icon-asterisk"></span></label>
							</div> <!-- form-label -->
							<div class="form-input">
								<div class="err-msg hide"></div>
								<input type="text" name="payment_stripe_test_secret_key" value="<?=$payment_stripe_test_secret_key?>" id="payment_stripe_test_secret_key" class="input col-100" />
							</div> <!-- form-input --> <div class="formseparator"></div>

							<div class="form-label">
								<label class="label" for="payment_stripe_test_publishable_key"><?=_d('STRIPE test publishable key',206)?> <span class="mandatory icon icon-asterisk"></span></label>
							</div> <!-- form-label -->
							<div class="form-input">
								<div class="err-msg hide"></div>
								<input type="text" name="payment_stripe_test_publishable_key" value="<?=$payment_stripe_test_publishable_key?>" id="payment_stripe_test_publishable_key" class="input col-100" />
							</div> <!-- form-input --> <div class="formseparator"></div>
						</div> <!-- payment_stripe_test_key-container -->

						<div class="tab-description rad5 col-100">
							<span class="red"><?=_d('NOTE',207)?>:</span><br />
							<?=_d('You should add a',208)?> <a href="https://stripe.com/docs/webhooks" target="_blank"><?=_d('webhook',209)?></a> <?=_d('in your STRIPE account',210)?>. <?=_d('That way refunds or chargebacks will be processed automatically by the theme',211)?>.<div class="clear10"></div>
							<b><?=_d('Step',212)?> 1:</b> <?=_d('Go to your Webhooks page from your account.',213)?> <a href="https://dashboard.stripe.com/account/webhooks" target="_blank">Click here.</a><br />
							<b><?=_d('Step',212)?> 2:</b> <?=_d('Click the button',224)?> "<u><?=_d('Add endpoint',214)?></u>"<br />
							<b><?=_d('Step',212)?> 3:</b> <?=_d('Fill in the form like this',215)?>:<br />
							&nbsp;&nbsp;&nbsp;<b><?=_d('URL',218)?>:</b> <input type="text" class="webhook-url rad3 col-90" value="<?=get_template_directory_uri()?>/IPN.php?processor=stripe_webhook" /><br />
							&nbsp;&nbsp;&nbsp;<b><?=_d('Mode',219)?>:</b> <u><?=_d('Live',220)?></u> <?=_d('and',216)?> <u><?=_d('Test',221)?></u> (<?=_d('create two webhooks, with the same URL, one for live and one for test',217)?>)<br />
							&nbsp;&nbsp;&nbsp;<b><?=_d('Select',222)?></b> "<u><?=_d('Send me all events',223)?></u>"<br />
							<b><?=_d('Step',212)?> 4:</b> <?=_d('Click the button',224)?> "<u><?=_d('Create endpoint',225)?></u>"
						</div> <!-- tab-description --> <div class="clear"></div>
					</div> <!-- payment_stripe_live-container -->
				</div> <!-- stripe -->

				<div class="tab col-100 rad5 hide" id="mycred">
					<h4 class="rad25 l">MyCred <?=_d('settings',194)?></h4>
					<div class="close rad25 r"><span class="icon icon-cancel"></span> <?=_d('close',195)?></div>
					<div class="clear30"></div>

					<div class="form-label">
						<label class="label" for="payment_mycred"><?=_d('Activate MyCred payments?',1030)?></label>
					</div> <!-- form-label -->
					<div class="form-input">
						<div class="err-msg hide"></div>
						<div class="toggle rad25 l">
							<div data-value="1" class="toggle-text toggle-yes l<?php if($payment_mycred != "1") { echo ' hide'; } ?>"><?=_d('yes',85)?></div>
							<div class="pin l">&nbsp;</div>
							<div data-value="2" class="toggle-text toggle-no r<?php if($payment_mycred != "2" && $payment_mycred) { echo ' hide'; } ?>"><?=_d('no',86)?></div>
							<input type="hidden" class="input" maxlength="1" name="payment_mycred" value="<?=$payment_mycred?>" />
						</div> <!-- toggle -->
					</div> <!-- form-input --> <div class="formseparator"></div>
				</div> <!-- mycred -->
			</div>
			<script type="text/javascript">
				jQuery(document).ready(function($) {
					$('.webhook-url').on('click', function(event) {
						$(this).select();
					});
					$('.webhook-url').on('input', function(event) {
						$(this).val('<?=get_template_directory_uri()?>/IPN.php?processor=stripe_webhook').select();
					});
				});
			</script>
			<div class="clear40"></div>

			<h4><?=_d('Choose a currency for the payment options',226)?></h4>
			<div class="clear5"></div>
			<div class="form-input col-100 nopadding">
				<div class="help"><b>!</b> <?=_d('Leaving a price empty will disable that upgrade',227)?></div>
			</div>
			<div class="clear40"></div>

			<div class="form-label">
				<label class="label" for="payment_currency"><?=_d('Payments currency code',228)?> <span class="mandatory icon icon-asterisk"></span></label>
			</div> <!-- form-label -->

			<div class="form-input">
				<div class="err-msg hide"></div>
				<input type="text" name="payment_currency" maxlength="3" value="<?=$payment_currency?>" id="payment_currency" class="input text-center" size="6" />
				<div class="help"><?=_d('examples',229)?>: USD, EUR, GBP</div>
				<div class="help"><b>!</b> <?=_d('make sure the currency code is supported by all the payment processors that you activated',230)?></div>
			</div> <!-- form-input --> <div class="formseparator"></div>

			<div class="form-label">
				<label class="label"><?=_d('Payments currency symbol',231)?></label>
			</div> <!-- form-label -->
			<div class="form-input">
				<div class="err-msg hide"></div>
				<input type="text" name="payment_currency_symbol_before" maxlength="10" value="<?=$payment_currency_symbol_before?>" id="payment_currency_symbol_before" class="input text-center" size="6" />
				 &nbsp;&nbsp;1000&nbsp;&nbsp; 
				<input type="text" name="payment_currency_symbol_after" maxlength="10" value="<?=$payment_currency_symbol_after?>" id="payment_currency_symbol_after" class="input text-center" size="6" />
				<div class="help"><?=_d('examples',229)?>: &#36;, &#8364;, &#163;</div>
				<div class="help"><b>!</b> <?=_d('add your currency symbol before or after the amount, depending on the standard for the currency',232)?></div>
				<div class="help"><b>!</b> <?=_d('the symbol will be used next to the prices in your site. if no symbol is added then the 3 letter currency code will be used.',233)?></div>
			</div> <!-- form-input --> <div class="formseparator"></div>
			
			<div class="clear30"></div>
			<h4><?=_d('Choose your payment options',833)?></h4>
			<div class="clear30"></div>
			<div class="upgrade col-100 rad5">
				<div class="upgrade-title rad3 l"><?=_d('User registration',824)?></div>
				<?php foreach ($user_types as $type => $type_name) { ?>
					<div class="clear20"></div>
					<div class="upgrade-content upgrade-content-<?=$type?><?=$extra_class_upgrade_content?>">
						<div class="upgrade col-100 rad5">
							<div class="upgrade-title rad3 l no-selection"><?=$type_name?><span class="icon icon-arrow-down"></span></div>
							<div class="upgrade-title-divider clear20"></div>
							<div class="upgrade-content">
								<?=generate_payment_option_form('user_reg', $payment_user_reg_data, $type)?>
							</div>
						</div>
					</div> <!-- upgrade-content -->
				<?php } ?>
			</div> <!-- upgrade -->

			<div class="clear30"></div>
			<div class="upgrade col-100 rad5">
				<div class="upgrade-title rad3 l"><?=_d('Posting an ad',234)?></div>
				<div class="clear10"></div>
				<?=_d('This price is for normal ads. Not upgrades. If you don\'t write a price then ad posting is free.',235)?>
				<div class="clear10"></div>
				<?php foreach ($user_types as $type => $type_name) { ?>
					<div class="clear20"></div>
					<div class="upgrade-content upgrade-content-<?=$type?><?=$extra_class_upgrade_content?>">
						<div class="upgrade col-100 rad5">
							<div class="upgrade-title rad3 l no-selection"><?=$type_name?><span class="icon icon-arrow-down"></span></div>
							<div class="upgrade-title-divider clear20"></div>
							<div class="upgrade-content">
								<?=generate_payment_option_form('paid_ads', $payment_paid_ads_data, $type)?>
							</div>
						</div>
					</div> <!-- upgrade-content -->
				<?php } ?>
			</div> <!-- upgrade -->

			<div class="clear50"></div>
			<div class="upgrade col-100 rad5">
				<div class="upgrade-title rad3 l"><?=_d('Always on top',240)?> / <?=_d('Featured ads',241)?></div>
				<div class="clear10"></div>
				<?=_d('Ads will be displayed on top of normal ads and will have the "FEATURED" label next to them.',242)?>
				<div class="clear10"></div>
				<?php foreach ($user_types as $type => $type_name) { ?>
					<div class="clear20"></div>
					<div class="upgrade-content upgrade-content-<?=$type?><?=$extra_class_upgrade_content?>">
						<div class="upgrade col-100 rad5">
							<div class="upgrade-title rad3 l no-selection"><?=$type_name?><span class="icon icon-arrow-down"></span></div>
							<div class="upgrade-title-divider clear20"></div>
							<div class="upgrade-content">
								<?=generate_payment_option_form('always_on_top', $payment_always_on_top_data, $type)?>
							</div>
						</div>
					</div> <!-- upgrade-content -->
				<?php } ?>
			</div> <!-- upgrade -->

			<div class="clear50"></div>
			<div class="upgrade col-100 rad5">
				<div class="upgrade-title rad3 l"><?=_d('Highlighted ads',244)?></div>
				<div class="clear10"></div>
				<?=_d('These ads will be highlighted with a different color than normal ads',245)?>
				<div class="clear10"></div>
				<?php foreach ($user_types as $type => $type_name) { ?>
					<div class="clear20"></div>
					<div class="upgrade-content upgrade-content-<?=$type?><?=$extra_class_upgrade_content?>">
						<div class="upgrade col-100 rad5">
							<div class="upgrade-title rad3 l no-selection"><?=$type_name?><span class="icon icon-arrow-down"></span></div>
							<div class="upgrade-title-divider clear20"></div>
							<div class="upgrade-content">
								<?=generate_payment_option_form('highlighted_ad', $payment_highlighted_ad_data, $type)?>
							</div>
						</div>
					</div> <!-- upgrade-content -->
				<?php } ?>
			</div> <!-- upgrade -->

			<div class="clear50"></div>
			<div class="upgrade col-100 rad5">
				<div class="upgrade-title rad3 l"><?=_d('Push to top',246)?></div>
				<div class="clear10"></div>
				<?=_d('Once a day the ad will be pushed to the top of other ads. It will look the same as if the ad was posted again.',247)?>
				<div class="clear10"></div>

				<div class="upgrade-content upgrade-content-<?=$type?>">
					<div class="upgrade col-100 rad5">
						<div class="upgrade-title rad3 l no-selection"><?=_d('Push time',922)?><span class="icon icon-arrow-down"></span></div>
						<div class="upgrade-title-divider clear20"></div>
						<div class="upgrade-content">
							<div class="form-label">
								<label class="label"><?=_d('Push ads each day at',249)?></label>
							</div> <!-- form-label -->
							<div class="form-input">
								<div class="err-msg hide"></div>
								<div class="fake-select equal-input fake-select-time rad3 no-selection l">
									<div class="first"><span class="text l"></span> <span class="icon icon-arrow-up hide"></span><span class="icon icon-arrow-down"></span></div>
									<div class="options rad5 shadow hide">
										<?php
										for ($i=0; $i <= 23; $i++) { 
											if($i < 10) {
												$j = "0".$i;
											} else {
												$j = $i;
											}
											$selected = ($payment_push_data['time']['timehour'] == $i) ? ' selected' : '';
											echo '<div data-value="'.$i.'" class="option'.$selected.'">'.$j.'</div>';
										}
										?>
									</div> <!-- options -->
									<input type="hidden" name="payment_push_timehour[time]" value="<?=$payment_push_data['time']['timehour']?>" />
								</div> <!-- fake-selector -->
								<div class="l" style="line-height: 2.5em">&nbsp;&nbsp;:&nbsp;&nbsp;</div>
								<div class="fake-select equal-input fake-select-time rad3 no-selection l">
									<div class="first"><span class="text l"></span> <span class="icon icon-arrow-up hide"></span><span class="icon icon-arrow-down"></span></div>
									<div class="options rad5 shadow hide">
										<?php
										for ($i=0; $i < 56; $i++) { 
											if($i % 5 == 0) {
												if($i < 10) {
													$j = "0".$i;
												} else {
													$j = $i;
												}
												$selected = ($payment_push_data['time']['timeminutes'] == $i) ? ' selected' : '';
												echo '<div data-value="'.$i.'" class="option">'.$j.'</div>';
											}
										}
										?>
									</div> <!-- options -->
									<input type="hidden" name="payment_push_timeminutes[time]" value="<?=$payment_push_data['time']['timeminutes']?>" />
								</div> <!-- fake-selector -->
								<div class="l" style="line-height: 2.5em">&nbsp;&nbsp;<?=_d('hours',250)?></div>
							</div> <!-- form-input --> <div class="clear"></div>
						</div> <!-- upgrade-content -->
					</div> <!-- upgrade -->
				</div> <!-- upgrade-content -->

				<div class="clear10"></div>
				<?php foreach ($user_types as $type => $type_name) { ?>
					<div class="clear20"></div>
					<div class="upgrade-content upgrade-content-<?=$type?><?=$extra_class_upgrade_content?>">
						<div class="upgrade col-100 rad5">
							<div class="upgrade-title rad3 l no-selection"><?=$type_name?><span class="icon icon-arrow-down"></span></div>
							<div class="upgrade-title-divider clear20"></div>
							<div class="upgrade-content">
								<?=generate_payment_option_form('push', $payment_push_data, $type)?>
							</div>
						</div>
					</div> <!-- upgrade-content -->
				<?php } ?>
			</div> <!-- upgrade -->

			<div class="clear20"></div>
			<div class="buttons text-center">
				<div class="submit-message">
					<span class="icon icon-asterisk"></span> <?=_d('Mandatory fields',251)?>
				</div>
				<div class="clear20"></div>
				<button class="button submit-button submit-button-default round-corners-button rad25" name="submit">
					<span class="icon for-default icon-arrow-right hide"></span>
					<svg class="icon for-loading loader hide" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 50 50" style="enable-background:new 0 0 50 50;" xml:space="preserve"><path fill="#000" d="M43.935,25.145c0-10.318-8.364-18.683-18.683-18.683c-10.318,0-18.683,8.365-18.683,18.683h4.068c0-8.071,6.543-14.615,14.615-14.615c8.072,0,14.615,6.543,14.615,14.615H43.935z"><animateTransform attributeType="xml" attributeName="transform" type="rotate" from="0 25 25" to="360 25 25" dur="0.6s" repeatCount="indefinite" /></path></svg>
					<span class="icon for-done icon-checkmark hide"></span>
					<span class="icon for-err icon-cancel hide"></span>

					<span class="button-text text-default hide"><?=_d('Save settings',91)?></span>
					<span class="button-text text-loading hide"><?=_d('Saving',92)?></span>
					<span class="button-text text-done hide"><?=_d('Saved',93)?></span>
					<span class="button-text text-err hide"><?=_d('Error',94)?></span>
				</button>
			</div>
		</form>
	</div>
</div> <!-- page -->


<?php get_footer('no-sidebar'); ?>