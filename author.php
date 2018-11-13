<?php
if(!defined('error_reporting')) { define('error_reporting', '0'); }
ini_set( 'display_errors', error_reporting );
if(error_reporting == '1') { error_reporting( E_ALL ); }
if(isdolcetheme !== 1) { die(); }

global $taxonomy_ad_category_url, $taxonomy_ad_url, $ads_per_page, $review_char_limit, $taxonomy_review_url;
$seller = get_query_var('author_name') ? get_user_by('slug', get_query_var('author_name')) : get_userdata(get_query_var('author'));
$seller = $seller->data;
$sort_by = $_GET['sort'] ? (int)$_GET['sort'] : '1';
$all_payment_data = get_all_payment_data();
/*
	$seller content:
    [ID]				[user_url]
    [user_login]		[user_registered]
    [user_pass]			[user_activation_key]
    [user_nicename]		[user_status]
    [user_email]		[display_name]
*/

if(get_query_var('page_section') == "verified" && (!is_user_logged_in() || $seller->ID != $current_user->ID || get_user_meta($seller->ID, 'verified', true) == "yes")) {
	wp_redirect(get_author_posts_url($seller->ID)); die();
}
if(get_query_var('page_section') == "change_account_type" && (!is_user_logged_in() || $seller->ID != $current_user->ID || get_option('activate_business_users') != "1" || get_user_meta($current_user->ID, 'user_type', true) == "business")) {
	wp_redirect(get_author_posts_url($seller->ID)); die();
}
if(get_query_var('page_section') == "manage_subscription" && (!is_user_logged_in() || $seller->ID != $current_user->ID || !get_user_meta($current_user->ID, 'user_reg_expiration', true))) {
	wp_redirect(get_author_posts_url($seller->ID)); die();
}

if($_POST['action'] == "mark_as_verified" && current_user_can('level_10')) {
	if(get_user_meta($seller->ID, 'verified', true)) {
		delete_user_meta($seller->ID, 'verified');
	} else {
		update_user_meta($seller->ID, 'verified', 'yes');
	}
	delete_user_meta($seller->ID, 'ask_for_verification');
	$url = get_author_posts_url($seller->ID);
	if(get_query_var('page_section') == "reviews") {
		$url = $url."reviews/";
	}
	wp_redirect($url); die();
}

if($_POST['action'] == "change_user_type" && current_user_can('level_10')) {
	$form_action = preg_replace("/([^a-zA-Z0-9_])/", "", $_POST['form_action']);

	switch ($form_action) {
		case 'add_business':
			update_user_meta($seller->ID, 'user_type', 'business');
			break;

		case 'remove_business':
			delete_user_meta($seller->ID, 'user_type');
			if(!$all_payment_data['user_reg']['personal']['first']['price']) {
				update_user_meta($seller->ID, 'user_type', 'personal');
			}
			break;

		case 'add_personal':
			update_user_meta($seller->ID, 'user_type', 'personal');
			break;

		case 'remove_personal':
			delete_user_meta($seller->ID, 'user_type');
			break;
	}

	$url = get_author_posts_url($seller->ID);
	if(get_query_var('page_section') == "reviews") {
		$url = $url."reviews/";
	}
	wp_redirect($url); die();
}

get_header();
$reviews_per_page = "5";
if(get_query_var('page_section') == "reviews") {
	$paged_reviews = get_query_var('paged') ? get_query_var('paged') : 1;
	$paged_reviews = $wp_query->query['page'] ? $wp_query->query['page'] : $paged_reviews;
} else {
	$paged_reviews = "1";
}
$seller_reviews_args = array(
		'post_type' => $taxonomy_review_url,
		'posts_per_page' => $reviews_per_page,
		'paged' => $paged_reviews,
		'meta_query' => array(
			array(
				'key' => 'review_for',
				'value' => $seller->ID,
				'compare' => '=',
				'type' => 'NUMERIC'
			)
		),
	);
$seller_reviews = new WP_Query($seller_reviews_args);
?>

<?php if(current_user_can('level_10') && get_user_meta($seller->ID, 'ask_for_verification', true) == "yes") { ?>
	<div class="ok rad3"><?=_d('This account asked to be verified!',933)?></div>
<?php } ?>

<div class="author-page<?=in_array(get_query_var('page_section'), array("change_account_type", "manage_subscription")) ? " change-account-type-page" : ""?>">
	<div class="user-items-wrapper">
		<div class="user-items-wrapper2">
			<?php if(get_query_var('page_section') == "reviews") { ?>
				<div class="author-reviews-section rad5">
					<div class="author-reviews-section-title">
						<h3 class="l"><?=_d('%s has %s reviews',891,array('<span class="blue">'.$seller->display_name.is_user_verified($seller->ID).'</span><span class="mobile-line-break"></span>', '<span class="blue">'.$seller_reviews->found_posts.'</span>'))?><span class="last-word">&#8204;</span></h3>
						<a class="back-to-the-ads round-corners-button rad25 r" href="<?=get_author_posts_url($seller->ID)?>"><?=_d('See the ads instead',901)?></a>
					</div>
					<div class="clear"></div>

					<?php if($seller_reviews->have_posts()) {
						while($seller_reviews->have_posts()) {
							$seller_reviews->the_post();
							$review_for_user_data = $review_for_user_data ? $review_for_user_data : get_userdata(get_post_meta(get_the_ID(), 'review_for', true));
							$user_avatar = get_user_avatar(get_the_author_meta('ID'));
							$user_avatar = '<img src="'.$user_avatar.'" alt="'.get_the_author_meta('display_name').'" class="review-author-avatar rad50" />';

							$seller_avatar = get_user_avatar(get_post_meta(get_the_ID(), 'review_for', true));
							$seller_avatar = '<img src="'.$seller_avatar.'" class="review-author-avatar rad50" />';

							$comments = get_comments('post_id='.get_the_ID());
						?>
						<div class="review col-100 rad5">
							<div class="review-meta-info">
								<?=get_user_rating('', 'l', get_post_meta(get_the_ID(), 'rating', true))?>
								<span class="from l">&nbsp;<?=_d('from',898)?>&nbsp;</span>
								<a class="review-author l" href="<?=get_author_posts_url(get_the_author_meta('ID'))?>"><?=$user_avatar.get_the_author_meta('display_name').is_user_verified(get_the_author_meta('ID'))?></a>
								<div class="review-date l">&nbsp;<?=date_time_ago(get_the_time('U'))?></div>
								<?php if(is_user_logged_in() && get_the_author_meta('ID') == $current_user->ID || current_user_can('level_10')) { ?>
									<div class="delete-review rad25 r no-selection" data-swal-title="<?=_d('Are you sure?',96)?>" data-swal-text="<?=_d('Are you sure you want to delete this review?',908)?>" data-swal-button="<?=_d('Delete',902)?>" data-swal-cancel="<?=_d('Cancel',543)?>" data-swal-ok-title="<?=_d('Deleted!',910)?>" data-swal-ok-text="<?=_d('Your review was deleted.',911)?><br /><?=_d('We are refreshing the page...',673)?>" data-review-id="<?=get_the_ID()?>"><span class="icon icon-delete"></span> <?=_d('Delete',902)?></div>
									<div class="edit-review rad25 r no-selection"><span class="icon icon-edit"></span> <?=_d('Edit',903)?></div>
								<?php } ?>
								<div class="clear20"></div>
							</div> <!-- review-meta-info -->
							<div class="review-text-wrapper">
								<?php show_detailed_review_box("", "r", get_post_meta(get_the_ID(), 'delivery', true), get_post_meta(get_the_ID(), 'responsiveness', true), get_post_meta(get_the_ID(), 'friendliness', true)); ?>
								<div class="review-text"><?=nl2br(stripslashes(get_the_content()))?></div>
								<div class="clear"></div>
							</div> <!-- review-text-wrapper -->

							<?php if(is_user_logged_in() && get_the_author_meta('ID') == $current_user->ID || current_user_can('level_10')) { ?>
								<div class="edit-review-form add-user-review-form text-center form-styling hide">
									<div class="close rad25 r no-selection"><span class="icon icon-cancel"></span> <?=_d('close',195)?></div>
									<h3 class="text-center"><span class="text"><?=_d('Edit your review for',904)?></span> <span class="seller-name"><?=$seller->display_name.is_user_verified($seller->ID)?></span></h3>
									<div class="err-msg form-err-msg hide"></div>
									<div class="err-msg err-msg-review_id hide"></div>
									<input type="hidden" name="review_id" value="<?=get_the_ID()?>" />
									<input type="hidden" name="action" value="update_review" />
									<?php
									$labels = array("delivery" => _d('Delivery',872), "responsiveness" => _d('Responsiveness',873), "friendliness" => _d('Friendliness',874));
									$label_values = array("delivery" => get_post_meta(get_the_ID(), 'delivery', true), "responsiveness" => get_post_meta(get_the_ID(), 'responsiveness', true), "friendliness" => get_post_meta(get_the_ID(), 'friendliness', true));
									foreach ($labels as $key => $label) { ?>
										<div class="label"><?=$label?></div>
										<div class="err-msg err-msg-<?=$key?> hide"></div>
										<div class="stars-wrapper">
											<?php for ($i=1; $i <= 5; $i++) { ?><span class="star icon-star star-disabled"></span><?php } ?>
											<input type="hidden" name="<?=$key?>" value="<?=$label_values[$key]?>" class="star-input" />
										</div> <!-- stars-wrapper -->
									<?php } ?>
									<div class="clear"></div>
									<div class="err-msg err-msg-review_text hide"></div>
									<textarea name="review_text" class="review-textarea rad5" placeholder="<?=_d('Leave a message about this seller',875)?>"><?=stripslashes(get_the_content())?></textarea>
									<div class="review-text-char-limit text-center" data-char-limit="<?=$review_char_limit?>"><span class="limit"><?=$review_char_limit?></span> <?=_d('characters left',876)?></div>

									<div class="buttons text-center">
										<button class="button submit-button submit-button-default round-corners-button rad25" name="submit">
											<span class="icon for-default icon-update hide"></span>
											<svg class="icon for-loading loader hide" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 50 50" style="enable-background:new 0 0 50 50;" xml:space="preserve"><path fill="#000" d="M43.935,25.145c0-10.318-8.364-18.683-18.683-18.683c-10.318,0-18.683,8.365-18.683,18.683h4.068c0-8.071,6.543-14.615,14.615-14.615c8.072,0,14.615,6.543,14.615,14.615H43.935z"><animateTransform attributeType="xml" attributeName="transform" type="rotate" from="0 25 25" to="360 25 25" dur="0.6s" repeatCount="indefinite" /></path></svg>
											<span class="icon for-done icon-checkmark hide"></span>
											<span class="icon for-err icon-cancel hide"></span>

											<span class="button-text text-default hide"><?=_d('Update review',870)?></span>
											<span class="button-text text-loading hide"><?=_d('Saving',92)?></span>
											<span class="button-text text-done hide"><?=_d('Saved',93)?></span>
											<span class="button-text text-err hide"><?=_d('Error',94)?></span>
										</button>
									</div> <!-- buttons -->
								</div> <!-- add-user-review-form -->
							<?php } ?>

							<?php if(count($comments) > 0) { ?>
								<?php foreach ($comments as $comment) { ?>
								<div class="seller-reply rad5 col-90 r">
									<a class="review-author l" href="<?=get_author_posts_url(get_post_meta(get_the_ID(), 'review_for', true))?>"><?=$seller_avatar.$review_for_user_data->display_name.is_user_verified($review_for_user_data->ID)?></a>
									<div class="from l"><?=_d('replied to the review',899)?></div>
									<?php if(is_user_logged_in() && $comment->user_id == $current_user->ID || current_user_can('level_10')) { ?>
										<div class="delete-review rad25 r no-selection" data-swal-title="<?=_d('Are you sure?',96)?>" data-swal-text="<?=_d('Are you sure you want to delete your reply?',909)?>" data-swal-button="<?=_d('Delete',902)?>" data-swal-cancel="<?=_d('Cancel',543)?>" data-swal-ok-title="<?=_d('Deleted!',910)?>" data-swal-ok-text="<?=_d('Your reply was deleted.',912)?><br /><?=_d('We are refreshing the page...',673)?>" data-review-reply-id="<?=$comment->comment_ID?>"><span class="icon icon-delete"></span> <?=_d('Delete',902)?></div>
										<div class="edit-review rad25 r no-selection"><span class="icon icon-edit"></span> <?=_d('Edit',903)?></div>
									<?php } ?>
									<div class="review-date r"><?=date_time_ago(strtotime($comment->comment_date))?></div>
									<div class="clear10"></div>
									<div class="seller-reply-text"><?=nl2br(stripslashes($comment->comment_content))?></div>
									<?php if(is_user_logged_in() && $comment->user_id == $current_user->ID || current_user_can('level_10')) { ?>
										<div class="reply seller-reply-form-update form-styling hide">
											<div class="reply-area">
												<textarea class="reply-textarea rad5 col-100" name="reply_text" placeholder="<?=_d('Leave a reply to this review',897)?>"><?=stripslashes($comment->comment_content)?></textarea>
												<div class="review-text-char-limit text-center" data-char-limit="<?=$review_char_limit?>"><span class="limit"><?=$review_char_limit?></span> <?=_d('characters left',876)?></div>

												<div class="buttons text-center">
													<button class="button submit-button submit-button-default round-corners-button rad25 no-selection" name="submit" data-review-id="<?=$comment->comment_ID?>">
														<span class="icon for-default icon-update hide"></span>
														<svg class="icon for-loading loader hide" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 50 50" style="enable-background:new 0 0 50 50;" xml:space="preserve"><path fill="#000" d="M43.935,25.145c0-10.318-8.364-18.683-18.683-18.683c-10.318,0-18.683,8.365-18.683,18.683h4.068c0-8.071,6.543-14.615,14.615-14.615c8.072,0,14.615,6.543,14.615,14.615H43.935z"><animateTransform attributeType="xml" attributeName="transform" type="rotate" from="0 25 25" to="360 25 25" dur="0.6s" repeatCount="indefinite" /></path></svg>
														<span class="icon for-done icon-checkmark hide"></span>
														<span class="icon for-err icon-cancel hide"></span>

														<span class="button-text text-default hide"><?=_d('Update review',907)?></span>
														<span class="button-text text-loading hide"><?=_d('Saving',92)?></span>
														<span class="button-text text-done hide"><?=_d('Saved',93)?></span>
														<span class="button-text text-err hide"><?=_d('Error',94)?></span>
													</button>
													<div class="cancel-reply round-corners-button rad25 no-selection"><?=_d('Cancel',543)?></div>
												</div> <!-- buttons -->
												<div class="clear"></div>
											</div> <!-- reply-area -->
											<div class="clear"></div>
										</div> <!-- reply -->
									<?php } ?>
								</div> <!-- seller-reply -->
								<?php } ?>
							<?php } ?>

							<?php if(is_user_logged_in() && get_post_meta(get_the_ID(), 'review_for', true) == $current_user->ID && count($comments) == "0") { ?>
								<div class="reply seller-reply-form form-styling">
									<div class="action-button reply-button l rad25 no-selection"><span class="icon icon-edit"></span> <?=_d('Reply to review',896)?></div>
									<div class="action-button close-button r rad25 hide no-selection"><span class="icon icon-cancel"></span> <?=_d('Close',55)?></div>
									<div class="clear"></div>
									<div class="reply-area hide">
										<textarea class="reply-textarea rad5 col-100" name="reply_text" placeholder="<?=_d('Leave a reply to this review',897)?>"></textarea>
										<div class="review-text-char-limit text-center" data-char-limit="<?=$review_char_limit?>"><span class="limit"><?=$review_char_limit?></span> <?=_d('characters left',876)?></div>

										<div class="buttons text-center">
											<button class="submit-button submit-button-default round-corners-button rad25 no-selection" name="submit" data-review-id="<?=get_the_ID()?>">
												<span class="icon for-default icon-plus2 hide"></span>
												<svg class="icon for-loading loader hide" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 50 50" style="enable-background:new 0 0 50 50;" xml:space="preserve"><path fill="#000" d="M43.935,25.145c0-10.318-8.364-18.683-18.683-18.683c-10.318,0-18.683,8.365-18.683,18.683h4.068c0-8.071,6.543-14.615,14.615-14.615c8.072,0,14.615,6.543,14.615,14.615H43.935z"><animateTransform attributeType="xml" attributeName="transform" type="rotate" from="0 25 25" to="360 25 25" dur="0.6s" repeatCount="indefinite" /></path></svg>
												<span class="icon for-done icon-checkmark hide"></span>
												<span class="icon for-err icon-cancel hide"></span>

												<span class="button-text text-default hide"><?=_d('Add review',870)?></span>
												<span class="button-text text-loading hide"><?=_d('Saving',92)?></span>
												<span class="button-text text-done hide"><?=_d('Saved',93)?></span>
												<span class="button-text text-err hide"><?=_d('Error',94)?></span>
											</button>
										</div> <!-- buttons -->
										<div class="clear"></div>
									</div> <!-- reply-area -->
									<div class="clear"></div>
								</div> <!-- reply -->

								<?php if(count($comments) == "0") { // placeholder html to place the seller reply after submit ?>
									<div class="seller-reply rad5 col-90 r hide">
										<a class="review-author l" href="<?=get_author_posts_url(get_post_meta(get_the_ID(), 'review_for', true))?>"><?=$seller_avatar.$current_user->display_name.is_user_verified($current_user->ID)?></a>
										<div class="from l"><?=_d('replied to the review',899)?></div>
										<div class="review-date r"><?=_d('now',363)?></div>
										<div class="clear10"></div>
										<div class="seller-reply-text"></div>
									</div> <!-- seller-reply -->
								<?php } ?>
							<?php } ?>

							<div class="clear"></div>
						</div> <!-- review -->
					<?php } // while posts
					echo '<div class="clear30 hide-is-mobile"></div>';
					$total = ceil($seller_reviews->found_posts / $reviews_per_page);
					dolce_pagination($total, $paged_reviews);
					echo '<div class="clear"></div>';
					} // if reviews ?>
				</div> <!-- author-reviews-section -->
			<?php } elseif(in_array(get_query_var('page_section'), array("change_account_type", "manage_subscription"))) {
				$payment_user_reg_data = get_option('payment_user_reg_data');
				$payment_paid_ads_data = get_option('payment_paid_ads_data');
				?>
				<div class="choose-user-type-plans text-center<?=get_query_var('page_section') == "manage_subscription" ? " choose-user-type-plans-manage-subscription" : ""?>" data-profile-url="<?=get_author_posts_url($current_user->ID)?>">
					<?php
					if(get_query_var('page_section') == "manage_subscription") {
						echo '<div class="title">'._d('Your current account subscription',993).'</div>';
					} else {
						echo '<div class="title">'._d('Please select what type of account you would like to use',825).'</div>';
					}
					?>
					<div class="clear40"></div>

					<script type="text/javascript">
						jQuery(document).ready(function($) {
							$('.column-button').on('click', function(event) {
								if($(this).hasClass('button-is-processing') || $(this).hasClass('disabled')) {
									return false;
								} else {
									$(this).addClass('button-is-processing');
									$('.column-button').addClass('disabled');
								}

								var user_type = $(this).data('user-type');
								var button = $(this);

								button.find('.text').text(button.data('saving'));
								button.find('.icon').hide().parent().find('.icon-for-saving').show();

								$.ajax({
									type: "POST",
									url: wpvars.wpthemeurl+'/ajax/register-user.php',
									data: { action: 'user_type', user_type: user_type},
									cache: false,
									timeout: 3000, // in milliseconds
									success: function(data) {
										if(data == "ok") {
											button.find('.text').text(button.data('saved'));
											button.find('.icon').hide().parent().find('.icon-for-saved').show();
											button.addClass('button-is-done');
											$('.user-type-column').not(button.parents('.user-type-column')).fadeOut('100', function(){
												$('.user-type-column').addClass('user-type-column-selected');
											});
											$('.user-type-column').find('.return-to-homepage').show();
											setTimeout(function() {
												window.location.href = $('.choose-user-type-plans').data('profile-url');
											}, 3000);
										} else {
											button.find('.text').text(button.data('error'));
											button.find('.icon').hide().parent().find('.icon-for-error').show();
											setTimeout(function() {
												button.find('.text').text(button.data('default'));
												button.find('.icon').hide();
												$('.column-button').removeClass('button-is-processing disabled');
											}, 2000);
										}
									},
									error: function(request, status, err) {
										button.find('.text').text(button.data('error'));
										button.find('.icon').hide().parent().find('.icon-for-error').show();
										setTimeout(function() {
											button.find('.text').text(button.data('default'));
											button.find('.icon').hide();
											$('.column-button').removeClass('button-is-processing disabled');
										}, 2000);
									}
								});
							});
						});
					</script>

					<?php
					if(get_query_var('page_section') == "manage_subscription" && get_user_meta($current_user->ID, 'user_type', true) == "personal" || get_query_var('page_section') == "change_account_type" && (!get_user_meta($current_user->ID, 'user_type', true) || get_user_meta($current_user->ID, 'user_type', true) == "personal")) {
					?>
					<div class="user-type-column user-type-column-personal<?=get_query_var('page_section') == "change_account_type" ? " l" : ""?>">
						<div class="user-type-column-inner shadow rad25">
							<div class="column-title"><?=_d('Personal',835)?></div>
							<div class="column-icon r"><img src="<?=get_template_directory_uri()?>/img/user-type-personal.png" alt="" /></div>
							<div class="column-benefits l">
								<div class="column-price">
									<div class="column-price-top-bar"></div>
									<div class="column-price-value"><?=dolce_format_price('user_reg', 'personal', _d('Free',937))?></div>
									<div class="column-price-bottom-bar"></div>
								</div> <!-- column-price -->
								<ul class="columns-user-benefits">
									<li><span class="icon icon-checkmark"></span> <span class="price"><?=dolce_format_price('paid_ads', 'personal', _d('Free',937))?></span> <?=_d('to post ads',938)?></li>
									<?php if(dolce_format_price('always_on_top', 'personal')) { ?>
									<li><span class="icon icon-checkmark"></span> <span class="price"><?=dolce_format_price('always_on_top', 'personal')?></span> <?=_d('for featured ads',939)?></li>
									<?php } ?>
									<?php if(dolce_format_price('highlighted_ad', 'personal')) { ?>
									<li><span class="icon icon-checkmark"></span> <span class="price"><?=dolce_format_price('highlighted_ad', 'personal')?></span> <?=_d('for highlighted ads',940)?></li>
									<?php } ?>
									<?php if(dolce_format_price('push', 'personal')) { ?>
									<li><span class="icon icon-checkmark"></span> <span class="price"><?=dolce_format_price('push', 'personal')?></span> <?=_d('to push ads',941)?></li>
									<?php } ?>
								</ul>
							</div> <!-- l -->
							<div class="clear30"></div>
							<?php
							if(get_user_meta($current_user->ID, 'user_type', true) == "personal" && get_query_var('page_section') != "manage_subscription") {
								echo '<div class="current-user-type rad17">'._d('This is your current account type',977).'</div>';
							} else {
							?>
							<div class="column-button-wrapper text-center">
								<?php if($all_payment_data['user_reg']['personal']['first']['price']) { ?>
									<div class="payment-buttons col-100 text-center">
										<?php $pre_text = get_user_meta($current_user->ID, 'user_reg_fee', true) ? _d('Extend your registration',980) : _d('Pay',472); ?>
										<div class="payment-buttons-text">
											<span class="icon icon-lock"></span>&nbsp;&nbsp; <?=$pre_text?> 
											<?=dolce_format_price('user_reg', 'personal')?> 
											<?=_d('with',470)?>
										</div>
										<div class="generated-payment-buttons"><?php generate_payment_buttons('5', $current_user->ID); ?></div>
									</div>
								<?php } else { ?>
									<div class="column-button round-corners-button rad25" data-saving="<?=_d('Saving',92)?>" data-saved="<?=_d('Saved',93)?>" data-error="<?=_d('Error',94)?>" data-default="<?=_d('Select',222)?>" data-user-type="personal">
										<span class="text"><?=_d('Select',222)?></span>
										<span class="icon icon-for-saved icon-checkmark hide"></span>
										<svg version="1.1" class="icon icon-for-saving loader r hide" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 50 50" style="enable-background:new 0 0 50 50;" xml:space="preserve"><path fill="#000" d="M43.935,25.145c0-10.318-8.364-18.683-18.683-18.683c-10.318,0-18.683,8.365-18.683,18.683h4.068c0-8.071,6.543-14.615,14.615-14.615c8.072,0,14.615,6.543,14.615,14.615H43.935z"><animateTransform attributeType="xml" attributeName="transform" type="rotate" from="0 25 25" to="360 25 25" dur="0.6s" repeatCount="indefinite"/></path></svg>
										<span class="icon icon-for-error icon-cancel hide"></span>
									</div> <!-- submit-form -->
								<?php } ?>

								<div class="return-to-homepage hide"><?=_d('Redirecting to profile page...',828)?> <svg version="1.1" class="loader" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 50 50" style="enable-background:new 0 0 50 50;" xml:space="preserve"><path fill="#000" d="M43.935,25.145c0-10.318-8.364-18.683-18.683-18.683c-10.318,0-18.683,8.365-18.683,18.683h4.068c0-8.071,6.543-14.615,14.615-14.615c8.072,0,14.615,6.543,14.615,14.615H43.935z"><animateTransform attributeType="xml" attributeName="transform" type="rotate" from="0 25 25" to="360 25 25" dur="0.6s" repeatCount="indefinite"/></path></svg></div>
							</div> <!-- column-button -->
							<?php } ?>
						</div> <!-- user-type-column-inner -->
					</div> <!-- user-type-column -->
					<?php } ?>

					<?php
					if(get_query_var('page_section') == "manage_subscription" && get_user_meta($current_user->ID, 'user_type', true) == "business" || get_query_var('page_section') == "change_account_type" && (!get_user_meta($current_user->ID, 'user_type', true) || get_user_meta($current_user->ID, 'user_type', true) == "personal")) {
					?>
					<div class="user-type-column user-type-column-business<?=get_query_var('page_section') == "change_account_type" ? " r" : ""?>">
						<div class="user-type-column-inner shadow rad25">
							<div class="column-title"><?=_d('Business',836)?></div>
							<div class="column-icon r"><img src="<?=get_template_directory_uri()?>/img/user-type-business.png" alt="" /></div>
							<div class="column-benefits l">
								<div class="column-price">
									<div class="column-price-top-bar"></div>
									<div class="column-price-value"><?=dolce_format_price('user_reg', 'business', _d('Free',937))?></div>
									<div class="column-price-bottom-bar"></div>
								</div> <!-- column-price -->
								<ul class="columns-user-benefits">
									<li><span class="icon icon-checkmark"></span> <span class="price"><?=dolce_format_price('paid_ads', 'business', _d('Free',937))?></span> <?=_d('to post ads',938)?></li>
									<?php if(dolce_format_price('always_on_top', 'business')) { ?>
									<li<?=(!dolce_format_price('always_on_top', 'personal') ? ' class="extra"' : '')?>><span class="icon icon-checkmark"></span> <span class="price"><?=dolce_format_price('always_on_top', 'business')?></span> <?=_d('for featured ads',939)?></li>
									<?php } ?>
									<?php if(dolce_format_price('highlighted_ad', 'business')) { ?>
									<li<?=(!dolce_format_price('highlighted_ad', 'personal') ? ' class="extra"' : '')?>><span class="icon icon-checkmark"></span> <span class="price"><?=dolce_format_price('highlighted_ad', 'business')?></span> <?=_d('for highlighted ads',940)?></li>
									<?php } ?>
									<?php if(dolce_format_price('push', 'business')) { ?>
									<li<?=(!dolce_format_price('push', 'personal') ? ' class="extra"' : '')?>><span class="icon icon-checkmark"></span> <span class="price"><?=dolce_format_price('push', 'business')?></span> <?=_d('to push ads',941)?></li>
									<?php } ?>
								</ul>
							</div> <!-- l -->
							<div class="clear30"></div>
							<?php
							if(get_user_meta($current_user->ID, 'user_type', true) == "business" && get_query_var('page_section') != "manage_subscription") {
								echo '<div class="current-user-type rad17">'._d('This is your current account type',977).'</div>';
							} else {
							?>
							<div class="column-button-wrapper text-center">
								<?php if(get_query_var('page_section') == "manage_subscription") { ?>
									<?php if($all_payment_data['user_reg']['business']['first']['price']) { ?>
										<div class="payment-buttons col-100 text-center">
											<?php
											$currentTime = new DateTime();
											$currentTime = DateTime::createFromFormat('U', get_user_meta($current_user->ID, 'user_reg_expiration', true));
											$renewal = " ".$currentTime->format('j')." ".$months_translated[$currentTime->format('n')]." ".$currentTime->format('Y');
											// $renewal .= " ".$currentTime->format('H:i:s');
											if(get_user_meta($current_user->ID, 'user_reg_recurring', true) == "1") {
											?>
												<div class="current-user-type rad17"><?=_d('Your account subscription will renew itself on',999).$renewal?></div>
												<?php
												if(get_user_meta($current_user->ID, 'stripe_client_id', true)) {
													generate_stripe_cancel_subscription_button('6', $current_user->ID);
												}
												?>
											<?php
											} else {
											?>
												<?php $pre_text = $all_payment_data['user_reg']['business']['first']['recurring'] ? _d('Subscribe for',708) : _d('Extend your registration',980); ?>
												<div class="payment-buttons-text">
													<span class="icon icon-lock"></span>&nbsp;&nbsp; <?=$pre_text?> 
													<?=dolce_format_price('user_reg', 'business')?> 
													<?=_d('with',470)?>
												</div>
												<div class="generated-payment-buttons"><?php generate_payment_buttons('6', $current_user->ID); ?></div>
												<div class="clear20"></div>
												<div class="current-user-type rad17"><?=_d('Your account will expire on',994).$renewal?></div>
											<?php } ?>
										</div>
									<?php } else { ?>
										<div class="column-button round-corners-button rad25" data-saving="<?=_d('Saving',92)?>" data-saved="<?=_d('Saved',93)?>" data-error="<?=_d('Error',94)?>" data-default="<?=_d('Select',222)?>" data-user-type="business">
											<span class="text"><?=_d('Select',222)?></span>
											<span class="icon icon-for-saved icon-checkmark hide"></span>
											<svg version="1.1" class="icon icon-for-saving loader r hide" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 50 50" style="enable-background:new 0 0 50 50;" xml:space="preserve"><path fill="#000" d="M43.935,25.145c0-10.318-8.364-18.683-18.683-18.683c-10.318,0-18.683,8.365-18.683,18.683h4.068c0-8.071,6.543-14.615,14.615-14.615c8.072,0,14.615,6.543,14.615,14.615H43.935z"><animateTransform attributeType="xml" attributeName="transform" type="rotate" from="0 25 25" to="360 25 25" dur="0.6s" repeatCount="indefinite"/></path></svg>
											<span class="icon icon-for-error icon-cancel hide"></span>
										</div> <!-- submit-form -->
									<?php } // if($all_payment_data['user_reg']['business']['first']['price']) ?>
								<?php } else { ?>
									<?php if($all_payment_data['user_reg']['business']['first']['price']) { ?>
										<div class="payment-buttons col-100 text-center">
											<?php $pre_text = get_user_meta($current_user->ID, 'user_reg_fee', true) ? _d('Extend your registration',980) : _d('Pay',472); ?>
											<div class="payment-buttons-text">
												<span class="icon icon-lock"></span>&nbsp;&nbsp; <?=$pre_text?> 
												<?=dolce_format_price('user_reg', 'business')?> 
												<?=_d('with',470)?>
											</div>
											<div class="generated-payment-buttons"><?php generate_payment_buttons('6', $current_user->ID); ?></div>
										</div>
									<?php } else { ?>
										<div class="column-button round-corners-button rad25" data-saving="<?=_d('Saving',92)?>" data-saved="<?=_d('Saved',93)?>" data-error="<?=_d('Error',94)?>" data-default="<?=_d('Select',222)?>" data-user-type="business">
											<span class="text"><?=_d('Select',222)?></span>
											<span class="icon icon-for-saved icon-checkmark hide"></span>
											<svg version="1.1" class="icon icon-for-saving loader r hide" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 50 50" style="enable-background:new 0 0 50 50;" xml:space="preserve"><path fill="#000" d="M43.935,25.145c0-10.318-8.364-18.683-18.683-18.683c-10.318,0-18.683,8.365-18.683,18.683h4.068c0-8.071,6.543-14.615,14.615-14.615c8.072,0,14.615,6.543,14.615,14.615H43.935z"><animateTransform attributeType="xml" attributeName="transform" type="rotate" from="0 25 25" to="360 25 25" dur="0.6s" repeatCount="indefinite"/></path></svg>
											<span class="icon icon-for-error icon-cancel hide"></span>
										</div> <!-- submit-form -->
									<?php } // if($all_payment_data['user_reg']['business']['first']['price']) ?>
								<?php } // if(get_query_var('page_section') == "manage_subscription") ?>

								<div class="return-to-homepage hide"><?=_d('Redirecting to profile page...',828)?> <svg version="1.1" class="loader" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 50 50" style="enable-background:new 0 0 50 50;" xml:space="preserve"><path fill="#000" d="M43.935,25.145c0-10.318-8.364-18.683-18.683-18.683c-10.318,0-18.683,8.365-18.683,18.683h4.068c0-8.071,6.543-14.615,14.615-14.615c8.072,0,14.615,6.543,14.615,14.615H43.935z"><animateTransform attributeType="xml" attributeName="transform" type="rotate" from="0 25 25" to="360 25 25" dur="0.6s" repeatCount="indefinite"/></path></svg></div>
							</div> <!-- column-button -->
							<?php } ?>
						</div> <!-- user-type-column-inner -->
					</div> <!-- user-type-column -->
					<?php } ?>

					<div class="clear"></div>
				</div> <!-- choose-user-type-plans -->
				<div class="clear30"></div>
				</div></div></div>
				<?php get_footer('no-sidebar'); die(); ?>
			<?php } elseif(get_query_var('page_section') == "verified") { ?>
				<div class="get-verified-section text-center">
					<div class="title"><?=_d('Get your account verified',924)?></div>
					<div class="verified-icon"><span class="icon icon-verified"></span></div>
					<div class="get-verified-desc">
						<div class="get-verified-desc-user"><b><?=$current_user->display_name?></b> <span class="arrow icon-arrow-right2"></span> <b><?=$current_user->display_name?> <span class="icon icon-verified"></span></b></div>
						<?=_d('Getting your account verified is very important.',925)?><br /><?=_d('People who visit your ads will trust you more if they know you passed our verification process.',926)?><br /><br />
						<?=_d('If you would like to get a verified badge then click the button below.',927)?><br /><?=_d('We\'ll contact you as soon as possible to ask for more details about you.',928)?>
					</div>
					<div class="form-styling">
						<div class="buttons text-center">
							<button class="button submit-button submit-button-<?=get_user_meta($current_user->ID, 'ask_for_verification', true) == "yes" ? "done" : "default"?> round-corners-button rad25" name="submit">
								<svg class="icon for-loading loader hide" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 50 50" style="enable-background:new 0 0 50 50;" xml:space="preserve"><path fill="#000" d="M43.935,25.145c0-10.318-8.364-18.683-18.683-18.683c-10.318,0-18.683,8.365-18.683,18.683h4.068c0-8.071,6.543-14.615,14.615-14.615c8.072,0,14.615,6.543,14.615,14.615H43.935z"><animateTransform attributeType="xml" attributeName="transform" type="rotate" from="0 25 25" to="360 25 25" dur="0.6s" repeatCount="indefinite" /></path></svg>
								<span class="icon for-done icon-checkmark hide"></span>
								<span class="icon for-err icon-cancel hide"></span>

								<span class="button-text text-default hide"><?=_d('Ask for verification',929)?></span>
								<span class="button-text text-loading hide"><?=_d('Ask for verification',929)?></span>
								<span class="button-text text-done hide"><?=_d('Request sent',930)?></span>
								<span class="button-text text-err hide"><?=_d('Error',94)?></span>
								<span class="icon for-default icon-verified hide"></span>
							</button>
						</div> <!-- buttons -->
					</div> <!-- form-styling -->
				</div> <!-- get-verified-section -->
			<?php } else { // author page ?>
				<div class="user-items">
					<div class="loop-title-bar col-100 rad5">
						<h3 class="l"><span class="text"><?=_d('All ads from',165)?></span> <span class="category"><?=$seller->display_name.is_user_verified($seller->ID)?></span></h3>
						<div class="sorting r">
							<?php
							if(is_tax()) {
								$link = get_term_link($wp_query->queried_object);
							}
							if(is_search()) {
								$link = home_url().'/?s='.$search_keyword.'&c='.$search_cat.'&l='.$search_location.'&ld='.$search_distance;
							}
							if($filters) {
								$parse_url = parse_url($link);
								$parse_url['query'] = $parse_url['query'] ? $parse_url['query'].'&filter='.implode('|', $url_filters) : 'filter='.implode('|', $url_filters);
								$link = $parse_url['scheme'].'://'.$parse_url['host'].''.$parse_url['path'].'?'.$parse_url['query'];
							}
							if($price_start || $price_end) {
								$parse_url = parse_url($link);
								$parse_url['query'] = $parse_url['query'] ? 'ps='.$price_start.'&pe='.$price_end.'&'.$parse_url['query'] : 'ps='.$price_start.'&pe='.$price_end;
								$link = $parse_url['scheme'].'://'.$parse_url['host'].''.$parse_url['path'].'?'.$parse_url['query'];
							}
							$parse_url = parse_url($link);
							$link_no_sort = $link ? $link : "/";
							$link = $parse_url['query'] ? $link."&sort=" : $link."?sort=" ;
							?>
							<div class="fake-select fake-select-order-by rad25 r">
								<div class="first"><span class="text l"></span> <span class="icon icon-arrow-up hide"></span><span class="icon icon-arrow-down"></span></div>
								<div class="options rad5 shadow hide l">
									<a href="<?=$link_no_sort?>" data-value="1" class="option<?php if($sort_by == '1') { echo ' selected'; } ?>"><?=_d('Newest first',166)?></a>
									<a href="<?=$link?>2" data-value="2" class="option<?php if($sort_by == '2') { echo ' selected'; } ?>"><?=_d('Oldest first',167)?></a>
									<a href="<?=$link?>3" data-value="3" class="option<?php if($sort_by == '3') { echo ' selected'; } ?>"><?=_d('Price Low to High',168)?></a>
									<a href="<?=$link?>4" data-value="4" class="option<?php if($sort_by == '4') { echo ' selected'; } ?>"><?=_d('Price High to Low',169)?></a>
								</div> <!-- options -->
								<input type="hidden" name="sorting_order_by" value="<?=$sort_by?>" />
							</div> <!-- fake-selector -->
							<div class="r"><?=_d('Order by',170)?>:</div>
						</div>
						<div class="clear"></div>
					</div> <!-- loop-title-bar -->
					<div class="clear10 hide-is-mobile"></div>
					<div class="clear"></div>

					<div class="loop">
						<?php
						$paged = $wp_query->query['page'] ? (int)$wp_query->query['page'] : 1;
						$args = array(
								'post_type' => $taxonomy_ad_url,
								'posts_per_page' => $ads_per_page,
								'author' => $seller->ID,
								'paged' => $paged
							);

						switch ($sort_by) {
							case '1': // date asc
								$args['order'] = 'DESC';
								$args['orderby'] = 'date';
								break;
							
							case '2': // date desc
								$args['order'] = 'ASC';
								$args['orderby'] = 'date';
								break;
							
							case '3': // price asc
								$args['order'] = 'ASC';
								$args['orderby'] = 'meta_value_num';
								$args['meta_key'] = 'price_amount';
								break;
							
							case '4': // price desc
								$args['order'] = 'DESC';
								$args['orderby'] = 'meta_value_num';
								$args['meta_key'] = 'price_amount';
								break;

							default:
								break;
						}

						$ads = new WP_Query( $args );
						if ( $ads->have_posts() ) {
							while ( $ads->have_posts() ) {
								$ads->the_post();
								get_template_part('loop-items');
							}
						} else {
							echo '<div class="col-100">'._d('There are no ads here at the moment',171).'</div>';
						}
						echo '<div class="clear30 hide-is-mobile"></div>';
						$total = ceil($ads->found_posts / $ads_per_page);
						dolce_pagination($total, $paged);
						echo '<div class="clear"></div>';

						wp_reset_postdata();
						?>
					</div> <!-- loop -->
					<div class="clear20 hide-is-mobile"></div>
					<div class="clear"></div>
				</div> <!-- user-items -->
			<?php } // author page end ?>

			<?php if(is_user_logged_in() && $seller->ID != $current_user->ID) { ?>
			<div class="add-user-review rad5 hide">
				<div class="close rad25 r no-selection"><span class="icon icon-cancel l"></span> <?=_d('close',195)?></div>
				<div class="clear"></div>
				<?php
				$user_has_review_args = array(
					'post_type' => $taxonomy_review_url,
					'meta_query' => array(
						array(
							'key' => 'review_from',
							'value' => $current_user->ID,
							'compare' => '=',
							'type' => 'NUMERIC'
						),
						array(
							'key' => 'review_for',
							'value' => $seller->ID,
							'compare' => '=',
							'type' => 'NUMERIC'
						)
					),
					'posts_per_page' => '1',
					'fields' => 'ids'
				);
				$user_has_review = new WP_Query($user_has_review_args);
				if(!$user_has_review->posts[0]) $user_has_review->posts[0] = "-1";
				$class = $user_has_review->found_posts == "0" ? "" : " hide";
				?>
				<div class="add-user-review-form text-center form-styling<?=$class?>">
					<h3 class="text-center"><span class="text"><?=_d('Write a review for',871)?></span> <span class="seller-name"><?=$seller->display_name.is_user_verified($seller->ID)?></span></h3>
					<div class="err-msg form-err-msg hide"></div>
					<div class="err-msg err-msg-seller_id hide"></div>
					<input type="hidden" name="seller_id" value="<?=$seller->ID?>" />
					<input type="hidden" name="action" value="<?=($user_has_review->posts[0] > 0 ? "update_review2" : "save_review")?>" />
					<?=($user_has_review->posts[0] > 0 ? '<input type="hidden" name="review_id" value="'.$user_has_review->posts[0].'" />' : '')?>
					<?php
					$labels = array("delivery" => _d('Delivery',872), "responsiveness" => _d('Responsiveness',873), "friendliness" => _d('Friendliness',874));
					foreach ($labels as $key => $label) { ?>
						<div class="label"><?=$label?></div>
						<div class="err-msg err-msg-<?=$key?> hide"></div>
						<div class="stars-wrapper">
							<?php for ($i=1; $i <= 5; $i++) { ?><span class="star icon-star star-disabled"></span><?php } ?>
							<input type="hidden" name="<?=$key?>" value="<?=get_post_meta($user_has_review->posts[0], $key, true)?>" class="star-input" />
						</div> <!-- stars-wrapper -->
					<?php } ?>
					<div class="clear"></div>
					<div class="err-msg err-msg-review_text hide"></div>
					<textarea name="review_text" class="review-textarea rad5" placeholder="<?=_d('Leave a message about this seller',875)?>"><?=get_post_field('post_content', $user_has_review->posts[0])?></textarea>
					<div class="review-text-char-limit text-center" data-char-limit="<?=$review_char_limit?>"><span class="limit"><?=$review_char_limit?></span> <?=_d('characters left',876)?></div>

					<div class="buttons text-center">
						<button class="button submit-button submit-button-default round-corners-button rad25" name="submit">
							<span class="icon for-default icon-plus2 hide"></span>
							<svg class="icon for-loading loader hide" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 50 50" style="enable-background:new 0 0 50 50;" xml:space="preserve"><path fill="#000" d="M43.935,25.145c0-10.318-8.364-18.683-18.683-18.683c-10.318,0-18.683,8.365-18.683,18.683h4.068c0-8.071,6.543-14.615,14.615-14.615c8.072,0,14.615,6.543,14.615,14.615H43.935z"><animateTransform attributeType="xml" attributeName="transform" type="rotate" from="0 25 25" to="360 25 25" dur="0.6s" repeatCount="indefinite" /></path></svg>
							<span class="icon for-done icon-checkmark hide"></span>
							<span class="icon for-err icon-cancel hide"></span>

							<span class="button-text text-default hide"><?=_d('Add review',870)?></span>
							<span class="button-text text-loading hide"><?=_d('Saving',92)?></span>
							<span class="button-text text-done hide"><?=_d('Saved',93)?></span>
							<span class="button-text text-err hide"><?=_d('Error',94)?></span>
						</button>
					</div> <!-- buttons -->
				</div> <!-- add-user-review-form -->

				<div class="review-posted-successfully text-center<?php if($user_has_review->found_posts == "0") { echo " hide"; } ?>">
					<div class="icon-for-alert rad50"><span class="icon icon-checkmark"></span></div>
					<div class="clear"></div>
					<div class="message">
						<p><?=_d('Your review was posted successfully.',889)?></p>
						<p><?=_d('Your rating for %s is',890,'<b class="seller-name">'.$seller->display_name.is_user_verified($seller->ID).'</b>')?> <?=get_user_rating('', '', get_post_meta($user_has_review->posts[0], 'rating', true))?>
						<div class="edit-your-review rad25"><span class="icon icon-edit"></span> <?=_d('Edit your review',914)?></div>
					</div>
				</div>
			</div> <!-- add-user-review -->
			<?php } ?>
		</div> <!-- user-items-wrapper2 -->
	</div> <!-- user-items-wrapper -->

	<div class="seller-and-reviews">
	<div class="seller-and-reviews-inner-wrapper rad5">
		<?php
		if(get_user_meta($seller->ID, 'user_type', true) == "business" || get_user_meta($seller->ID, 'verified', true)) {
			if(get_user_meta($seller->ID, 'user_type', true) == "business") {
				$class = " business";
				$text = _d('Business account',920);
				if(get_user_meta($seller->ID, 'verified', true)) {
					$text = _d('Verified Business Account',990);
				}		
			} else {
				$class = " verified";
				if(get_user_meta($seller->ID, 'verified', true)) {
					$text = _d('Verified account',991);
				}
			}
			echo '<div class="seller-and-reviews-status'.$class.' rad5">'.is_business_account($seller->ID).is_user_verified($seller->ID)." <span class='text'>".$text.'</span></div>';
		}
		?>
		<?php if(is_user_logged_in() && $seller->ID == $current_user->ID) { ?>
			<div class="user-account-links rad5">
				<h3><span class="icon icon-settings"></span> <?=_d('Account links',918)?><span class="icon arrow icon-arrow-down r"></span><span class="icon arrow icon-arrow-up hide r"></span></h3>
				<ul class="user-account-links-inner l">
					<li><a class="auto-font-size" href="<?=get_author_posts_url($seller->ID)?>reviews/"><span><span class="icon icon-star"></span> <?=_d('My Reviews',894)?></span></a></li>
					<?php if(get_option('allow_private_messages') == "1" && defined('private_messages_plugin')) { ?>
					<li>
						<a class="auto-font-size" href="<?=get_permalink(get_option('user_private_messages')); ?>" title="<?=_d('Messages',67)?>">
						<span class="icon icon-chat"></span> <?=_d('Messages',67)?>
						<span class="notifications-menu notifications rad25<?=$notification_class?>"><?=$notification?></span>
						</a>
					</li>
					<?php } ?>
					<?php if(get_user_meta($seller->ID, 'verified', true) != "yes" && !current_user_can('level_10')) { ?>
					<li><a class="auto-font-size" href="<?=get_author_posts_url($seller->ID)?>verified/"><span><span class="icon icon-verified"></span> <?=_d('Become verified',923)?></span></a></li>
					<?php } ?>
					<?php if(get_user_meta($seller->ID, 'user_type', true) != "business" && get_option('activate_business_users') == "1" && !current_user_can('level_10')) { ?>
					<li><a class="auto-font-size" href="<?=get_author_posts_url($seller->ID)?>change-account-type/"><span><span class="icon icon-business"></span> <?=_d('Business account',934)?></span></a></li>
					<?php } ?>
					<li><a class="auto-font-size" href="<?=get_permalink(get_option('user_edit_account'))?>"><span class="icon icon-profile"></span> <?=_d('Edit account',564)?></a></li>
					<?php if(get_user_meta($seller->ID, 'user_reg_expiration', true)) { ?>
					<li><a class="auto-font-size" href="<?=get_author_posts_url($seller->ID)?>manage-subscription/"><span class="icon icon-money"></span> <?=_d('Manage subscription',992)?></a></li>
					<?php } ?>
					<li><a class="auto-font-size" href="<?=wp_logout_url(home_url('/'))?>"><span class="icon icon-log-out"></span> <?=_d('Log Out',688)?></a></li>
				</ul>
				<div class="clear"></div>
			</div> <!-- user-account-links -->
			<div class="clear10"></div>
		<?php } ?>
		<?php if(current_user_can('level_10')) { ?>
			<div class="user-account-links rad5">
				<h3><span class="icon icon-settings"></span> <?=_d('Admin links',919)?><span class="icon arrow icon-arrow-down r"></span><span class="icon arrow icon-arrow-up hide r"></span></h3>
				<div class="user-account-links-inner">
					<ul class="l">
						<li><a class="auto-font-size" href="<?=home_url('/edit-account/?userid='.$seller->ID)?>"><span class="icon icon-profile"></span> <?=_d('Edit user',177)?></a></li>
						<li>
							<?php
								$button_text = _d('Add verified',916);
								if(get_user_meta($seller->ID, 'verified', true)) {
									$button_text = _d('Remove verified',917);
									$verified_class = " mark-as-verified-button-red";
								}
							?>
							<form action="" method="post" class="mark-as-verified-form">
								<button name="action" value="mark_as_verified" class="mark-as-verified-button auto-font-size<?=$verified_class?>"><span class="icon icon-verified"></span> <?=$button_text?></button>
							</form>
						</li>
						<?php if(get_option('activate_business_users') == "1") { ?>
						<li>
							<?php
								$button_action = 'add_business';
								$button_text = _d('Add business status',935);
								if(get_user_meta($seller->ID, 'user_type', true) == "business") {
									$button_action = 'remove_business';
									$button_text = _d('Remove business status',936);
									$user_type_class = " mark-as-verified-button-red";
								}
							?>
							<form action="" method="post" class="mark-as-verified-form">
								<input type="hidden" name="form_action" value="<?=$button_action?>" />
								<button name="action" value="change_user_type" class="mark-as-verified-button auto-font-size<?=$user_type_class?>"><span class="icon icon-business"></span> <?=$button_text?></button>
							</form>
						</li>
						<?php
						}

						if($all_payment_data['user_reg']['personal']['first']['price']) { ?>
						<li>
							<?php
								$button_action = 'add_personal';
								$button_text = get_option('activate_business_users') == "1" ? _d('Add personal status',935) : _d('Activate account',935);
								if(get_user_meta($seller->ID, 'user_type', true) == "personal") {
									$button_action = 'remove_personal';
									$button_text = get_option('activate_business_users') == "1" ? _d('Remove personal status',1002) : _d('Deactivate account',1003);
									$user_type_class = " mark-as-verified-button-red";
								}
							?>
							<form action="" method="post" class="mark-as-verified-form">
								<input type="hidden" name="form_action" value="<?=$button_action?>" />
								<button name="action" value="change_user_type" class="mark-as-verified-button auto-font-size<?=$user_type_class?>"><span class="icon icon-user"></span> <?=$button_text?></button>
							</form>
						</li>
						<?php } ?>
						<li><a class="auto-font-size" href="<?=get_edit_user_link($seller->ID)?>"><span class="icon icon-profile"></span> <?=_d('Edit in WordPress',178)?></a></li>
					</ul>
					<div class="clear"></div>
					<div class="admin-user-info rad3">
						<?=_d('UserID',175)?>: <?=$seller->ID?><br />
						<?=_d('Email',176)?>: <?=get_the_author_meta('user_email', $seller->ID)?>
					</div>
				</div> <!-- user-account-links-inner -->
			</div> <!-- user-account-links -->
			<div class="clear20"></div>
		<?php } // if admin ?>

		<div class="seller-info rad5">
			<div class="seller">
				<?php
				if(!is_user_logged_in()) {
					$send_message_class = " show-login-popup";
				} else {
					$send_message_class = $seller->ID == $current_user->ID ? " send-message-disabled" : " send-message-popup";
				}
				?>
				<div class="user-rating-wrapper">
					<?=get_user_rating($seller->ID,"large")?>
					<div class="total-reviews"><b><?=$seller_reviews->found_posts?></b> <?=strtolower(_d('reviews',869))?></div>
				</div><a href="<?=get_author_posts_url($seller->ID)?>" class="user-avatar-wrapper"><img src="<?=get_user_avatar($seller->ID)?>" alt="<?=$seller->display_name?>" class="avatar rad50" /></a><div class="send-message-wrapper"><div class="send-message rad50<?=$send_message_class?>" title="<?=_d('Send message',179)?>"><span class="icon icon-envelope"></span></div></div>
				<?php if(is_user_logged_in() && $seller->ID != $current_user->ID) { private_message_form($seller->ID); } ?>

				<div class="clear"></div>
				<div class="seller-details">
					<a href="<?=get_author_posts_url($seller->ID)?>" class="seller-name"><?=$seller->display_name.is_business_account($seller->ID).is_user_verified($seller->ID)?></a>
					<div class="clear"></div>
					<?php
					if(get_option('activate_business_users') == "1" && get_user_meta($seller->ID, 'user_type', true) == "personal") {
						echo '<span class="member-since">'._d('private user with',969).' </span>';
					}
					?>
					<span class="member-since"><b><?php echo count_user_posts($seller->ID, $taxonomy_ad_url); ?></b> <?=_d('ads online',172)?></span>
					<div class="clear"></div>
					<span class="member-since"><?=_d('Member since',173)." ".$months_translated[date('n', strtotime($seller->user_registered))]." ".date('Y', strtotime($seller->user_registered))?></span>
					<?php
					if($seller->user_url) {
						echo '<div><a class="website" href="'.$seller->user_url.'" rel="nofollow" target="_blank"><span class="icon icon-external-link"></span> '._d('Seller\'s website',174).'</a></div>';
					}
					?>
					<div class="clear"></div>
					<?php generate_mycred_balance_buttons(); ?>
				</div> <!-- seller-details -->

				<div class="clear10"></div>
				<?php show_detailed_review_box($seller->ID); ?>
				<div class="clear20"></div>
				<?php
					if(!is_user_logged_in()) {
						$add_review_class = " show-login-popup";
					} else {
						$add_review_class = $seller->ID == $current_user->ID ? " add-review-disabled" : " add-review-popup";
					}
				?>
				<div class="add-review rad25 big-button<?=$add_review_class?>"><span class="icon icon-plus2"></span> <?=_d('Add review',870)?></div>

				<div class="clear10"></div>
				<div class="clear"></div>
			</div>
			<div class="clear"></div>
		</div> <!-- seller-info -->

		<?php if($seller_reviews->found_posts > 0 && get_query_var('page_section') != "reviews") { ?>
		<div class="latest-reviews rad5">
			<h4><?=_d('%s has %s reviews',891,array('<span class="blue">'.$seller->display_name.is_user_verified($seller->ID).'</span>', '<span class="blue">'.$seller_reviews->found_posts.'</span>'))?></h4>
			<?php
				$truncated_seller_reviews = array_slice($seller_reviews->posts, 0, 3);
				foreach ($truncated_seller_reviews as $review) {
					$review_author = get_user_by('id', $review->post_author);
					$user_avatar = get_user_avatar($review->post_author, true);
					if($user_avatar) {
						$user_avatar = '<img src="'.$user_avatar.'" alt="'.$review_author->display_name.'" class="review-author-avatar rad50" />';
					}
					$limit = 100;
					$review_text = stripslashes($review->post_content);
					$review_text_1 = substr($review_text, 0, $limit);
					$review_text_2 = substr($review_text, $limit, 10000);
					if($review_text_2) {
						$review_text_2 = '<span class="read-more-dots">... </span><span class="read-more-link rad25">'._d('read more',900).'</span><span class="read-more-text hide">'.$review_text_2.'</span>';
					}
				?>
				<div class="review rad5">
					<?=get_user_rating('', 'l', get_post_meta($review->ID, 'rating', true))?>
					<span class="from l"><?=_d('from',898)?></span>
					<a class="review-author l" href="<?=get_author_posts_url($review->post_author)?>"><?=$user_avatar.$review_author->display_name.is_user_verified($review_author->ID)?></a>
					<div class="review-date r">&nbsp;<?=date_time_ago(strtotime($review->post_date))?></div>
					<div class="clear5"></div>
					<div class="review-text"><?=$review_text_1.$review_text_2?></div>
					<div class="clear"></div>
				</div>
			<?php } ?>
			<div class="clear"></div>
			<a class="see-all-reviews rad25 text-center" href="<?=get_author_posts_url($seller->ID)?>reviews/"><span class="icon icon-star"></span> <?=_d('See all reviews',895)?></a>
			<div class="clear"></div>
		</div> <!-- latest-reviews -->
		<?php } ?>
	</div> <!-- seller-and-reviews-inner-wrapper -->
	</div> <!-- seller-and-reviews -->

	<div class="clear"></div>
</div> <!-- author-page -->


<?php get_footer('no-sidebar'); ?>