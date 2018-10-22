<?php
if(!defined('error_reporting')) { define('error_reporting', '0'); }
ini_set( 'display_errors', error_reporting );
if(error_reporting == '1') { error_reporting( E_ALL ); }
if(isdolcetheme !== 1) { die(); }

define('DONOTCACHEPAGE',1);

/*
Template Name: Post new ad
*/

get_header();
?>

<div class="post-new-ad">
	<div class="ad-steps-tabs">
		<div class="col-33 l text-center ad-step-wrapper">
			<div class="ad-step ad-step1 active-ad-step" data-tab-step="1"><span class="for-desktop"><?=_d('Choose Category',546)?></span><span class="for-mobile hide"><?=_d('Step',212)?> 1</span></div>
		</div>
		<div class="col-33 l text-center ad-step-wrapper">
			<div class="ad-step ad-step2" data-tab-step="2"><span class="for-desktop"><?=_d('Write your ad',547)?></span><span class="for-mobile hide"><?=_d('Step',212)?> 2</span></div>
		</div>
		<div class="col-33 l text-center ad-step-wrapper">
			<div class="ad-step ad-step3" data-tab-step="3"><span class="for-desktop"><?=_d('Confirmation',548)?></span><span class="for-mobile hide"><?=_d('Step',212)?> 3</span></div>
		</div>
		<div class="moving-underline"></div>
		<div class="clear"></div>
	</div> <!-- ad-steps -->
	<div class="clear30"></div>

	<div class="steps">
		<div class="step step1 center hides" data-step="1">
			<h3 class="title text-center"><?=_d('In what category would you like to place your ad?',549)?></h3>
			<div class="clear30"></div>
			<?php
			$category_icons = get_option('category_icons');
			/* content structure of $c
				[term_id]				[count]
				[name]					[cat_ID]
				[slug]					[category_count]
				[term_group]			[category_description]
				[term_taxonomy_id]		[cat_name]
				[taxonomy]				[category_nicename]
				[description]			[category_parent]
				[parent]
			*/
			global $taxonomy_ad_category_url;
			$c = get_categories(array('taxonomy' => $taxonomy_ad_category_url, 'hide_empty' => 0));
			$main_cats = "";
			$auto_class_cats = get_option('auto_class_cats');
			foreach ($c as $cat) {
				$icon = $category_icons[$cat->term_id] ? 'cat-icon-'.$category_icons[$cat->term_id] : 'icon-arrow-right';
				$hide = ($cat->category_parent == '0') ? '' : ' hide';
				if(!in_array($cat->category_parent, $auto_class_cats)) {
					$main_cats .=  '<li class="'.$hide.'" data-id="'.$cat->cat_ID.'" data-parent="'.$cat->category_parent.'" data-nice-name="'.$cat->category_nicename.'"><a class="rad3" href="#'.$cat->category_nicename.'"><span class="icon '.$icon.'"></span>'.$cat->name.'</a></li>';
				}
			}
			if(count($c) > 0) { ?>
				<div class="back-one-cat l hide"><span class="icon icon-arrow-left"></span> <?=_d('Back',550)?></div>
				<div class="clear"></div>
				<div class="post-ad-in-this-cat rad3 hide"><?=_d('Post ad in',551)?> <span class="text"></span> <span class="icon icon-arrow-right"></span></div>
				<div class="clear"></div>
				<div class="or-choose-subcat text-center hide"><?=_d('or choose a subcategory below',552)?>:</div>
				<div class="clear"></div>
				<ul class="all-cats center"><?=$main_cats?></ul>
			<?php } ?>
				<div class="clear20"></div>
		</div> <!-- step1 -->

		<div class="step step2 step-post-form center hide" data-step="2">
			<?php include(get_stylesheet_directory().'/user-post-new-ad-form.php'); ?>
		</div> <!-- step2 -->

		<?php
		$posted_ad_cookie = $_COOKIE['posted_ad'] ? ' data-posted-ad-cookie="'.substr(preg_replace('/[^0-9a-z]/', "", $_COOKIE['posted_ad']), 0, 60).'"' : '';
		?>
		<div class="step step3 pc-70 center hide"<?=$posted_ad_cookie?> data-step="3">
			<?php if(is_user_logged_in()) { ?>
				<div class="ad-was-posted text-center">
					<span class="icon-ad-was-posted icon icon-checkmark"></span>
					<div class="clear20"></div>
					<div class="text-ad-was-posted"><?=_d('Your ad was saved!',553)?></div>
					<div class="clear20"></div>
					<a href="#" class="round-corners-button visit-ad rad25"><?=_d('Visit your ad here',554)?> <span class="icon icon-arrow-right"></span></a>
					<div class="clear20"></div>
					<div class="redirecting"><?=_d('redirecting in %s seconds ...',555,'<span>3</span>')?></div>
				</div> <!-- ad-was-posted -->
			<?php } else { // user is logged in ?>
				<div class="user-not-registered">
					<form action="" method="post" class="form-styling register-email">
						<div class="we-need-the-email"><span class="icon icon-cancel"></span> <?=_d('Your ad was saved but we need your email address so we can publish the ad on our website',556)?></div>
						<div class="clear20"></div>
						<div class="err-msg hide text-center"></div>
						<div class="form-input text-center">
							<input type="text" name="u_name" id="u_name" value="" placeholder="<?=_d('Name / Nickname',779)?>" class="input center col-70" />
							<div class="clear10"></div>
							<input type="text" name="u_email" id="u_email" value="" placeholder="<?=_d('Email',176)?>" class="input center col-70" />
							<div class="clear10"></div>
							<input type="password" name="u_pass" id="u_pass" value="" placeholder="<?=_d('Password',566)?>" class="input center col-70" />
							<div class="clear10"></div>
							<?php
							if(get_option('tos_ad_page_id') > 0) {
								$tos_page = get_page(get_option('tos_ad_page_id'));
							?>
								<div class="form-styling reg-tos-link">
									<div class="form-input no-selection">
										<label><input type="checkbox" id="reg_tos" name="tos" value="yes" class="" /></label>
										<span>
										<?=_d('I agree to the %s of this website',8688, '<a href="'.get_permalink(get_option('tos_ad_page_id')).'" target="_blank">'.$tos_page->post_title.'</a>')?>
										</span>
										<div class="clear"></div>
									</div>
								</div>
								<div class="clear20"></div>
							<?php } ?>
							<div class="submit-form round-corners-button rad25" data-saving="<?=_d('Registering',557)?>" data-error="<?=_d('Error',94)?>" data-default="<?=_d('Register',558)?>">
								<span class="text"><?=_d('Register',558)?></span>
								<span class="icon icon-for-default icon-arrow-right"></span>
								<svg version="1.1" class="icon icon-for-saving loader r hide" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 50 50" style="enable-background:new 0 0 50 50;" xml:space="preserve"><path fill="#000" d="M43.935,25.145c0-10.318-8.364-18.683-18.683-18.683c-10.318,0-18.683,8.365-18.683,18.683h4.068c0-8.071,6.543-14.615,14.615-14.615c8.072,0,14.615,6.543,14.615,14.615H43.935z"><animateTransform attributeType="xml" attributeName="transform" type="rotate" from="0 25 25" to="360 25 25" dur="0.6s" repeatCount="indefinite"/></path></svg>
								<span class="icon icon-for-error icon-cancel hide"></span>
							</div> <!-- submit-form -->
						</div> <!-- form-input -->
						<div class="clear20"></div>
						<div class="already-have-account text-center"><?=_d('Already have an account?',780)?> <span class="show-login-popup"><?=_d('Click to login.',781)?></span></div>
					</form> <div class="clear"></div>

					<?php
					if($allow_social_login == "1") {
					?>
					<div class="clear40"></div>
					<div class="or-connect-with text-center"><?=_d('or connect with',559)?></div>
					<div class="clear40"></div>

					<div class="connect-social text-center">
						<?php if($allow_fb_login == "1") { ?>
						<div class="col-33 connect-button-container">
							<a href="<?=home_url()?>/?action=fb-login" class="connect-button fb col-100 rad3"><span class="icon icon-social-f"></span> Facebook</a>
						</div>
						<?php } ?>
						<?php if($allow_g_login == "1") { ?>
						<div class="col-33 connect-button-container">
							<a href="<?=home_url()?>/?action=g-login" class="connect-button go col-100 rad3"><svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 48 48" class="connect-button-svg" height="22" width="22"><defs><path id="a2" d="M44.5 20H24v8.5h11.8C34.7 33.9 30.1 37 24 37c-7.2 0-13-5.8-13-13s5.8-13 13-13c3.1 0 5.9 1.1 8.1 2.9l6.4-6.4C34.6 4.1 29.6 2 24 2 11.8 2 2 11.8 2 24s9.8 22 22 22c11 0 21-8 21-22 0-1.3-.2-2.7-.5-4z"/></defs><clipPath id="b2"><use xlink:href="#a2" overflow="visible"/></clipPath><path clip-path="url(#b2)" fill="#FBBC05" d="M0 37V11l17 13z"/><path clip-path="url(#b2)" fill="#EA4335" d="M0 11l17 13 7-6.1L48 14V0H0z"/><path clip-path="url(#b2)" fill="#34A853" d="M0 37l30-23 7.9 1L48 0v48H0z"/><path clip-path="url(#b2)" fill="#4285F4" d="M48 48L17 24l-4-3 35-10z"/></svg> Google <span class="icon-on-hover icon icon-checkmark2 r hide"></span></a>
						</div>
						<?php } ?>
					</div> <!-- social-networks -->
					<?php } ?>
				</div> <!-- user-not-registered -->

				<div class="registration-over text-center hide">
					<div class="title"><?=_d('Check your email',560)?></div>
					<div class="clear10"></div>
					<div class="sub-title"><?=_d('We sent a link to your email address.',561)?></div>
					<div class="sub-title"><?=_d('Please click the link to validate your email address.',562)?></div>
					<div class="clear10"></div>
					<div class="envelope no-selection">
						<span class="icon icon-mail"><span class="inbox-message shadow">1</span></span>
					</div>
					<div class="clear10"></div>
					<div class="text-center no-selection"><span class="no-selection resend-email resend-email-active"><u><?=_d('Resend email?',563)?></u></span><span class="resend-email-msg hide"></span></div>
				</div> <!-- registration-over -->
			<?php } // user is not logged in ?>
			<div class="clear"></div>
		</div> <!-- step3 -->

		<div class="clear"></div>
	</div> <!-- steps -->
</div>

<?php get_footer('no-sidebar'); ?>