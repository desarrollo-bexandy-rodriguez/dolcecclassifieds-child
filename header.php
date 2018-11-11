<?php
if(!defined('error_reporting')) { define('error_reporting', '0'); }
ini_set( 'display_errors', error_reporting );
if(error_reporting == '1') { error_reporting( E_ALL ); }
if(isdolcetheme !== 1) { die(); }

global $taxonomy_ad_category_url, $show_empty_categories, $taxonomy_location_url, $allow_social_login, $allow_fb_login, $allow_tw_login, $allow_g_login, $login_err, $fb_id, $current_user_type;
$current_user = wp_get_current_user();

if(is_user_logged_in() && get_option('activate_business_users') == "1" && !get_user_meta($current_user->ID, 'user_type', true) && !current_user_can('level_10') && $wp_query->query['page_section'] != "change_account_type") {
	if(get_user_meta($current_user->ID, 'email_key', true)) {
		if($_GET['email_key'] && !is_author()) {
			$email_key = substr(preg_replace("/([^a-zA-Z0-9])/", "", $_GET['email_key']), 0, 200);
			$parse_url = parse_url(get_author_posts_url($current_user->ID).'change-account-type/');
			$parse_url['query'] = $parse_url['query'] ? $parse_url['query'].'&email_key='.$email_key : 'email_key='.$email_key;
			$redirect_to_url = $parse_url['scheme'].'://'.$parse_url['host'].''.$parse_url['path'].'?'.$parse_url['query'];
			wp_redirect($redirect_to_url); die();
		}
	} else {
		wp_redirect(get_author_posts_url($current_user->ID).'change-account-type/'); die();
	}
}

if($_GET['email_key']) {
	$email_key = substr(preg_replace("/([^a-zA-Z0-9])/", "", $_GET['email_key']), 0, 200);
	$email_key_user_id = $wpdb->get_var($wpdb->prepare("SELECT `user_id` FROM `".$wpdb->usermeta."` WHERE `meta_key` = 'email_key' AND `meta_value` = %s LIMIT 1", $email_key));
	if($email_key_user_id) {
		if($redirect_to && $redirect_to->post_author == $email_key_user_id) {
			// activate the ad that the user posted before registering
			$post_status = "publish";
			if(get_post_meta($redirect_to->ID, 'needs_activation', true) || get_post_meta($redirect_to->ID, 'needs_payment', true)) {
				$post_status = "private";
			}

			wp_update_post(array('ID' => $redirect_to->ID, 'post_status' => $post_status));
			delete_post_meta($redirect_to->ID, 'cookie_key');

			if($post_status == "publish" && get_option('notifications_email_new_ad') == "1") {
				dolce_email('', '', array($redirect_to->post_title, get_permalink($redirect_to->ID)), '13');
			}

			if($wp_query->query['page_section'] != "change_account_type") {
				delete_user_meta($email_key_user_id, 'redirect_to');
				$redirect_to_url = get_post_permalink($redirect_to->ID);
				$parse_url = parse_url($redirect_to_url);
				$parse_url['query'] = $parse_url['query'] ? $parse_url['query'].'&email_key='.$email_key : 'email_key='.$email_key;
				$redirect_to_url = $parse_url['scheme'].'://'.$parse_url['host'].''.$parse_url['path'].'?'.$parse_url['query'];
				wp_redirect($redirect_to_url); die();
			}
		}
	}
}

if(get_user_meta($current_user->ID, 'redirect_to', true)) {
	if(get_option('activate_business_users') == "1") {
		if(get_user_meta($current_user->ID, 'user_type', true)) {
			$redirect_to = get_user_meta($current_user->ID, 'redirect_to', true);
			$redirect_to_url = get_post_permalink($redirect_to);
			delete_user_meta($current_user->ID, 'redirect_to');
			wp_redirect($redirect_to_url); die();
		} else {
			// stay on the page. you should be on the change-user-type page.
		}
	} else {
		$redirect_to = get_user_meta($current_user->ID, 'redirect_to', true);
		$redirect_to_url = get_post_permalink($redirect_to);
		delete_user_meta($current_user->ID, 'redirect_to');
		wp_redirect($redirect_to_url); die();
	}
}

upgrade_dolce_theme();
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, height=device-height, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
	<link rel="profile" href="http://gmpg.org/xfn/11">
	<link rel="pingback" href="<?php bloginfo( 'pingback_url' ); ?>">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php
if(defined('dolce_demo_theme') && function_exists('demo_theme_options')) demo_theme_options();

license_check();
if($email_key) {
	if($email_key_user_id) {
		delete_user_meta($email_key_user_id, 'email_key');
		$email_key_msg = _d('You have successfully activated your account.',663)."<br />"._d('You can browse the site and post ads now.',664);

		if(get_option('notifications_email_new_user') == "1") {
			$email_key_name = get_the_author_meta('display_name', $email_key_user_id);
			$email_key_email = get_the_author_meta('user_email', $email_key_user_id);
			$email_key_profile = get_author_posts_url($email_key_user_id);
		}
		dolce_email('', '', array($email_key_name, $email_key_email, $email_key_profile), '12');
	} else {
		$email_key_msg = _d('This email has already been activated.',665);
	}
	echo '<div class="ok email-key-validation">'.$email_key_msg.'</div><div class="clear20 hide-is-mobile"></div>';
}
?>
<?php if($login_err) echo '<div class="err rad3">'.$login_err.'</div>'; ?>

<?php if(!is_user_logged_in()) { ?>
<div class="login-box hide">
	<div class="close r"><span class="icon icon-cancel l"></span> <b><?=_d('Close',55)?></b></div>
	<div class="clear"></div>
	<div class="login-box-wrapper shadow">
		<div class="login-box-wrapper2<?=defined('auto_classifieds_plugin_active') ? ' auto-class-login-box-wrapper2' : ""?>">
			<div class="message col-40 l">
				<?php
				$image_url = wp_get_attachment_image_src(get_option('admin_site_logo_id'), 'full');
				if($image_url) {
					$image_url = $image_url[0];
					$title_content = '<img src="'.$image_url.'" alt="'.get_bloginfo('name').'" />';
				} else {
					$title_content = get_bloginfo('name');
				}
				?>
				<div class="title text-center"><?=$title_content?></div>
				<div class="description text-center"><?=stripcslashes(get_bloginfo('description'))?></div>
			</div> <!-- message -->
			<ul class="summary col-40">
				<li><span class="icon icon-arrow-right"></span> <?=_d('Place ads on the site',666)?></li>
				<li><span class="icon icon-arrow-right"></span> <?=_d('Contact other members',667)?></li>
				<li><span class="icon icon-arrow-right"></span> <b><?=count_posts()?></b> <?=_d('ads to pick from',668)?></li>
			</ul>
			<div class="form col-60 r">
				<form action="" method="post">
					<div class="tabs">
						<div class="tab active rad3 login-tab l"><?=_d('Login',669)?></div>
						<div class="l"><?=_d('or',777)?></div>
						<div class="tab rad3 register-tab l"><?=_d('Register',558)?></div>
						<div class="clear"></div>
					</div> <!-- tabs -->
					<div class="clear20"></div>
					<div class="clear40 hide-on-error"></div>

					<div class="err-msg col-100 err rad3 hide"></div>
					<input type="text" name="name" id="h_name" value="" class="input toggle hide col-100 rad3" placeholder="<?=_d('Name / Nickname',779)?>" />
					<input type="text" name="email" id="h_email" value="" class="input col-100 rad3" placeholder="<?=_d('Email',176)?>" />
					<input type="password" name="pass" id="h_pass" value="" class="input col-100 rad3" placeholder="<?=_d('Password',566)?>" />
					<div class="clear5"></div>
					<?php
					if(get_option('tos_reg_page_id') > 0) {
						$tos_page = get_page(get_option('tos_reg_page_id'));
						?>
						<div class="form-styling reg-tos-link toggle l hide">
						<div class="form-input no-selection">
							<label><input type="checkbox" id="reg_tos" name="tos" value="yes" class="" /></label>
							<span>
							<?=_d('I agree to the %s of this website',8688, '<a href="'.get_permalink(get_option('tos_reg_page_id')).'" target="_blank">'.$tos_page->post_title.'</a>')?>
							</span>
							<div class="clear"></div>
						</div>
						</div>
					<?php } ?>
					<div class="clear50 toggle"></div>
					<div class="clear30 toggle hide"></div>


					<div class="action-button-wrapper l">
						<div class="action-button login-button round-corners-button rad25 l toggle">
							<span class="text text-default"><?=_d('Login',669)?></span>
							<span class="text text-saving hide"><svg class="icon" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 50 50" width="25" height="25" style="enable-background:new 0 0 50 50;" xml:space="preserve"><path fill="#000" d="M43.935,25.145c0-10.318-8.364-18.683-18.683-18.683c-10.318,0-18.683,8.365-18.683,18.683h4.068c0-8.071,6.543-14.615,14.615-14.615c8.072,0,14.615,6.543,14.615,14.615H43.935z"><animateTransform attributeType="xml" attributeName="transform" type="rotate" from="0 25 25" to="360 25 25" dur="0.6s" repeatCount="indefinite" /></path></svg> <?=_d('Logging in',670)?></span>
							<span class="text text-error hide"><span class="icon icon-cancel"></span> <?=_d('Error',94)?></span>
						</div>
						<div class="action-button register-button round-corners-button rad25 l hide toggle">
							<span class="text text-default"><?=_d('Register',558)?></span>
							<span class="text text-saving hide"><svg class="icon" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 50 50" width="25" height="25" style="enable-background:new 0 0 50 50;" xml:space="preserve"><path fill="#000" d="M43.935,25.145c0-10.318-8.364-18.683-18.683-18.683c-10.318,0-18.683,8.365-18.683,18.683h4.068c0-8.071,6.543-14.615,14.615-14.615c8.072,0,14.615,6.543,14.615,14.615H43.935z"><animateTransform attributeType="xml" attributeName="transform" type="rotate" from="0 25 25" to="360 25 25" dur="0.6s" repeatCount="indefinite" /></path></svg> <?=_d('Registering',557)?></span>
							<span class="text text-error hide"><span class="icon icon-cancel"></span> <?=_d('Error',94)?></span>
						</div>
					</div> <!-- action-button-wrapper -->
					<?php
					if($allow_social_login == "1") {
						echo '<div class="login-social r">';
							if($allow_g_login == "1") {
								echo '<a href="'.home_url().'/?action=g-login" rel="nofollow" class="social social-g r" title="Google"><svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 45 48" width="38" height="38"><defs><path id="a" d="M44.5 20H24v8.5h11.8C34.7 33.9 30.1 37 24 37c-7.2 0-13-5.8-13-13s5.8-13 13-13c3.1 0 5.9 1.1 8.1 2.9l6.4-6.4C34.6 4.1 29.6 2 24 2 11.8 2 2 11.8 2 24s9.8 22 22 22c11 0 21-8 21-22 0-1.3-.2-2.7-.5-4z"/></defs><clipPath id="b"><use xlink:href="#a" overflow="visible"/></clipPath><path clip-path="url(#b)" fill="#FBBC05" d="M0 37V11l17 13z"/><path clip-path="url(#b)" fill="#EA4335" d="M0 11l17 13 7-6.1L48 14V0H0z"/><path clip-path="url(#b)" fill="#34A853" d="M0 37l30-23 7.9 1L48 0v48H0z"/><path clip-path="url(#b)" fill="#4285F4" d="M48 48L17 24l-4-3 35-10z"/></svg></a>';
							}
							if($allow_fb_login == "1") {
								echo '<a href="'.home_url().'/?action=fb-login" rel="nofollow" class="social social-f r" title="Facebook"><span class="icon icon-social-f-circle"></span></a>';
							}
						echo '<div class="or toggle r"><span>'._d('Or login with',671).'</span></div>';
						echo '<div class="or toggle hide r"><span>'._d('Or register with',672).'</span></div>';
						echo '</div>';
					}
					?>
					<div class="clear10"></div>
					<div class="text-center recove-password"><a href='<?=home_url()?>/wp-login.php?action=lostpassword'><?=_d('Recover your password?',24)?></a></div>
					<div class="clear"></div>
				</form>

				<div class="login-over hide">
					<div class="title text-center"><?=_d('You are now logged in',33)?></div>
					<div class="clear20"></div>
					<div class="text-center"><span class="icon-login-over icon-checkmark"></span></div>
					<div class="clear20"></div>
					<div class="sub-title text-center"><?=_d('We are refreshing the page...',673)?></div>
				</div> <!-- login-over -->
				<div class="registration-over text-center hide">
					<div class="title"><?=_d('Check your email',560)?></div>
					<div class="clear10"></div>
					<div class="sub-title"><?=_d('We sent a link to your email address.',561)?></div>
					<div class="sub-title"></div>
					<div class="clear10"></div>
					<div class="envelope no-selection">
						<span class="icon icon-mail"><span class="inbox-message shadow">1</span></span>
					</div>
					<div class="clear10"></div>
					<div class="text-center no-selection"><span class="no-selection resend-email resend-email-active"><u><?=_d('Resend email?',563)?></u></span><span class="resend-email-msg hide"></span></div>
				</div> <!-- registration-over -->
			</div> <!-- form -->
			<div class="clear"></div>
		</div> <!-- login-box-wrapper2 -->
			<div class="clear"></div>
	</div> <!-- login-box-wrapper -->
			<div class="clear"></div>
</div> <!-- login-box -->
<?php } ?>

<header<?=defined('auto_classifieds_plugin_active') ? ' class="auto-class-header-bg"' : ""?>>
	<div class="header-content">
		<?php
		if(get_option('show_welcoming_message') != "no" && current_user_can('level_10')) {
			echo '<div class="welcoming-message-wrapper rad5"><div class="welcoming-message rad5">
					<div class="center col-80" style="text-align: left">
						<div class="title">'._d('Welcome to your Classified Ads website',674).'</div>
						'._d('It looks like this is the first time you are installing this theme. Here is a list of things you should do:',675).'
						<div class="clear10"></div>
						<span class="icon icon-arrow-right"></span> '._d('Read the documentation for the theme',676).' <a href="'.get_permalink(get_option('admin_documentation')).'">'._d('Do it now',677).'</a>
						<div class="clear10"></div>
						<span class="icon icon-arrow-right"></span> '._d('Create some categories',678).' <a href="'.get_permalink(get_option('admin_edit_categories')).'">'._d('Do it now',677).'</a>
						<div class="clear10"></div>
						<span class="icon icon-arrow-right"></span> '._d('Go to the form builder and change the ad posting form.',679).'<br />'._d('The theme already created a form for you so this is not very important. Just do it if you want different form fields.',680).' <a href="'.get_permalink(get_option('admin_form_builder')).'">'._d('Do it now',677).'</a>
						<div class="clear10"></div>
						<span class="icon icon-arrow-right"></span> '._d('Visit the settings page if you want to change the site\'s settings',681).' <a href="'.get_permalink(get_option('admin_site_settings')).'">'._d('Do it now',677).'</a>
						<div class="clear10"></div>
						<span class="icon icon-arrow-right"></span> '._d('Visit the payment settings page if you want to charge for the ad posting',682).' <a href="'.get_permalink(get_option('admin_payment_settings')).'">'._d('Do it now',677).'</a>
					</div>
					<div class="buttons">
						<div class="hide-welcoming-message round-corners-button rad25"><span class="icon icon-arrow-up"></span> '._d('Hide this',684).'</div>
					</div>
				</div> <!-- welcoming-message -->
				<div class="clear20"></div>
			</div> <!-- welcoming-message-wrapper -->';
		}
		?>
		<nav class="r">
			<div class="nav-button-mobile round-corners-button rad25 l hide"><span class="icon icon-menu l"></span> <span class="r"><?=_d('Menu',685)?></span></div>

			<?php
			if(get_option('show_header_language') == "1") {
				$languages = get_option('dolce_languages');
				$cookie_lang = preg_replace("/([^a-zA-Z0-9])/", "", $_COOKIE['sitelang']);
				$site_language = $languages[$cookie_lang] ? $cookie_lang : get_option('site_language');
				unset($languages['original']);
				$main_flag = $languages[$site_language]['flag'] ? '<img class="flag rad3" src="'.get_template_directory_uri().'/img/flags/'.$languages[$site_language]['flag'].'.png" /> ' : "";
				$main_lang_name = $languages[$site_language]['flag'] ? "" : $languages[$site_language]['name'];
				?>
				<div class="fake-select fake-select-header-language-chooser rad25 no-selection r">
					<div class="first"><span class="text l"><?=$main_flag.$main_lang_name?></span> <span class="icon icon-arrow-up hide"></span><span class="icon icon-arrow-down"></span></div>
					<div class="options rad7 hide">
					<?php
					foreach ($languages as $key => $l) {
						$flag = $l['flag'] ? '<img class="flag rad3" src="'.get_template_directory_uri().'/img/flags/'.$l['flag'].'.png" /> ' : "";
						$lang_name = $l['flag'] ? '<span class="lang-label">'.$l['name'].'</span>' : $l['name'];
						echo '<div data-value="'.$key.'" class="option">'.$flag.$lang_name.'</div>';
					}
					?>
					</div> <!-- options -->
					<input type="hidden" name="header_language" value="<?=$site_language?>" />
				</div>
			<?php } ?>

			<?php if(is_user_logged_in()) { ?>
				<div class="user-menu r">
					<div class="user-menu-content l">
						<div class="avatar l">
							<?php
							if(get_option('allow_private_messages') == "1" && defined('private_messages_plugin')) {
								$notification = get_pm_conversations_notifications($current_user->ID);
								if($notification < "1" || get_the_ID() == get_option('user_private_messages')) {
									$notification_class = " hide";
								}
								echo '<div class="pm-notification rad25'.$notification_class.'">'.$notification.'</div>';
							}

							$avatar_id = get_user_meta($current_user->ID, 'avatar_id', true);
							$avatar_url_social = get_user_meta($current_user->ID, 'avatar_url', true);
							if($avatar_url_social) {
								$avatar_url = $avatar_url_social;
							} else {
								$avatar_url = wp_get_attachment_image_src($avatar_id, 'avatar');
								$avatar_url = $avatar_url[0];
							}
							if(!$avatar_url) {
								$avatar_url = get_template_directory_uri().'/img/no-avatar.png';
							}
							echo '<img src="'.$avatar_url.'" width="60" height="60" class="rad50" />';
							echo is_user_verified($current_user->ID);
							?>
						</div>
						<span class="user-info vcenter l">
							<span class="text"><?=_d('My account',686)?> <span class="icon icon-arrow-down"></span></span>
							<span class="clear"></span>
							<span class="name"><?=$current_user->display_name?></span>
						</span>
						<span class="clear"></span>
					</div> <!-- user-menu-content -->
					<ul class="menu-links rad5 hide">
						<li class="first-link"><a href="<?=get_author_posts_url($current_user->ID)?>"><span class="icon icon-tags"></span> <?=_d('My profile',687)?></a></li>
						<li><a href="<?=get_author_posts_url($current_user->ID)?>reviews/"><span class="icon icon-star"></span> <?=_d('My Reviews',894)?></a></li>
						<?php if(get_option('allow_private_messages') == "1" && defined('private_messages_plugin')) { ?>
						<li>
							<a href="<?=get_permalink(get_option('user_private_messages')); ?>" title="">
							<span class="icon icon-chat"></span> <?=_d('Messages',67)?>
							<?php
							$notification = get_pm_conversations_notifications($current_user->ID);
							if($notification < "1" || get_the_ID() == get_option('user_private_messages')) {
								$notification_class = " hide";
							}
							echo '<span class="pm-notification rad25'.$notification_class.'">'.$notification.'</span>';
							?>
							</a>
						</li>
						<?php } ?>
						<?php if(get_user_meta($current_user->ID, 'verified', true) != "yes" && !current_user_can('level_10')) { ?>
						<li><a href="<?=get_author_posts_url($current_user->ID)?>verified/"><span><span class="icon icon-verified"></span> <?=_d('Become verified',923)?></span></a></li>
						<?php } ?>
						<?php if(get_user_meta($current_user->ID, 'user_type', true) != "business" && get_option('activate_business_users') == "1" && !current_user_can('level_10')) { ?>
						<li><a href="<?=get_author_posts_url($current_user->ID)?>change-account-type/"><span><span class="icon icon-business"></span> <?=_d('Business account',934)?></span></a></li>
						<?php } ?>
						<li><a href="<?=get_permalink(get_option('user_edit_account'));?>"><span class="icon icon-profile"></span> <?=_d('Edit account',564)?></a></li>
						<?php
							$current_url = home_url('/');
						?>
						<li><a href="<?=wp_logout_url($current_url)?>"><span class="icon icon-log-out"></span> <?=_d('Log Out',688)?></a></li>
					</ul>
				</div>
				<?php } else { ?>
				<div class="register-login r">
					<span class="show-login-popup rad3 r"><span class="icon icon-user"></span> <?=_d('Register',558)?> / <?=_d('Login',669)?></span>
				</div> <!-- register-login -->
			<?php } ?>

			<?php
			if(has_nav_menu("header-menu")) {
				$menu_args = array(
					'theme_location'  => 'header-menu',
					'container'       => 'ul',
					'container_class' => 'main-nav',
					'container_id'    => '',
					'menu_class'      => 'main-nav',
					'menu_id'         => '',
					'echo'            => true,
					'fallback_cb'     => false,
					'before'          => '',
					'after'           => '',
					'link_before'     => '',
					'link_after'      => '',
					'items_wrap'      => '<ul class="%2$s l">%3$s</ul>',
					'depth'           => 0,
					'walker'          => ''
				);
				wp_nav_menu($menu_args);
			} else { ?>
				<ul class="main-nav l">
					<?php if(!is_front_page()) { ?><li><a href="<?=home_url('/')?>" title="<?php bloginfo('name'); ?>"><?=_d('Home',689)?></a></li><?php } ?>
					<li><a href="<?=get_permalink(get_option('post_new_ad'))?>" title="<?=_d('Post new ad',690)?>"><?=_d('Post new ad',690)?></a></li>
					<li><a href="<?=get_permalink(get_option('dolce_blog_id'))?>" title="<?=_d('Blog',771)?>"><?=_d('Blog',771)?></a></li>
				</ul> <!-- main-nav -->
			<?php }	?>

			<div class="clear10"></div>
		</nav>

		<div class="logo l">
			<?php
			$logo_id = get_option('admin_site_logo_id');
			$image_url = wp_get_attachment_image_src($logo_id, 'full');
			if($image_url) {
				$image_url = $image_url[0];
				$h1_text = '<img src="'.$image_url.'" alt="'.get_bloginfo('name').'" />';
			} else {
				$h1_text = get_bloginfo('name');
			}
			?>
			<h1><a href="<?=home_url('/')?>" title="<?php bloginfo('name'); ?>"><?=$h1_text?></a></h1>
			<h2><?=stripcslashes(get_bloginfo('description'))?></h2>
			<div class="clear"></div>
		</div> <!-- logo -->


		<div>
		<?php 
			if (class_exists( 'myCRED_Core' )) {
				$point_type = 'mytype';
				$mycred     = mycred( $point_type );
				// Make sure user is not excluded
				if ( ! $mycred->exclude_user( $current_user->ID ) ) {
					// get users balance
					$balance = $mycred->get_users_balance( $current_user->ID );
					// adjust a users balance
					echo 'My Balance: '.$balance;
				}
			}		 
		?>
		</div> 

		<?php if ( is_active_sidebar( 'widget-header' ) ) { ?>
			<div class="clear10"></div>
			<div class="header-widget">
				<?php dynamic_sidebar( 'widget-header' ); ?>
			</div>
		<?php } elseif(current_user_can('level_10')) { ?>
			<div class="clear10"></div>
			<div class="header-widget text-center">
				<?=_d('Go to your',524)?> <a href="<?php echo admin_url('widgets.php'); ?>"><?=_d('widgets page',525)?></a> <?=_d('to add content here.',526)?>
			</div> <!-- widgetbox -->
		<?php } ?>
		<div class="clear"></div>
	</div> <!-- header-content -->
	<div class="clear"></div>

	<?php if(defined('auto_classifieds_plugin_active')) { ?>
		<?php
		global $taxonomy_ad_url;
		$search_cat = (int)$_GET['c'];
		$featured_args = array(
				'post_type' => $taxonomy_ad_url,
				'posts_per_page' => '3'
			);
		// if category/taxonomy page
		if(in_array($wp_query->queried_object->taxonomy, array($taxonomy_ad_category_url, $taxonomy_location_url))) {
			$featured_args['tax_query'][] = array('taxonomy' => $wp_query->queried_object->taxonomy, 'terms' => $wp_query->queried_object->term_id);
		}
		// search category
		if($search_cat) {
			$featured_args['tax_query'][] = array('taxonomy' => $taxonomy_ad_category_url, 'terms' => $search_cat);
		}
		$featured_args['meta_query'][] = array('key' => 'always_on_top', 'value' => '1', 'compare' => '=', 'type' => 'NUMERIC');
		$featured_args['posts_per_page'] = '3';
		$featured_args['orderby'] = 'rand';
		$featured = new WP_Query($featured_args);
		?>

		<div class="all-auto-class-header<?=!$featured->have_posts() ? " no-featured-ads" : ""?>">
			<?php
			if($featured->have_posts()) {
				echo '<div class="ac-header-featured-ads-wrapper l">';
				echo '<div class="ac-header-featured-ads">';
				echo '<h4>'._d('Featured ads',241).'</h4>';
				while($featured->have_posts()) {
					$featured->the_post();
					set_query_var('loop_ad_design', "1");
					get_template_part('loop-items');
				}
				echo '</div></div>';
			}
			wp_reset_postdata();
			?>

			<div class="auto-class-header-search-wrapper1 l text-center">
				<div class="auto-class-header-search-wrapper2">
					<?php auto_class_search_widget(); ?>
					<div class="clear"></div>
				</div> <!-- auto-class-header-search-wrapper1 -->
			</div> <!-- auto-class-header-search-wrapper2 -->
			<div class="clear"></div>
		</div> <!-- all-auto-class-header -->
		<div class="clear20"></div>
	<?php } ?>

	<?php
	// prepare search data

	// if "ads-from" taxonomy page
	if(is_tax($taxonomy_location_url)) {
		$current_location_data = $wp_query->queried_object;
		$location_text = $current_location_data->name;
		$_GET['l'] = implode(", ", $location_text);
		$_GET['ls'] = implode(", ", $current_location_data->slug);
	}

	// if category page
	if(is_tax($taxonomy_ad_category_url)) {
		$_GET['c'] = $wp_query->queried_object->term_id;
	}

	// getting the current filter from the url
	if($_GET['filter']) {
		$filters = process_url_filters($_GET['filter']);
		foreach ($filters as $key => $filter) {
			foreach ($filter as $k => $f) {
				$url_filter_values[] = $f;
			}
			$url_filters[] = $key.':'.implode(',', $url_filter_values);
			unset($url_filter_values);
		}
	}

	$search_keyword = sanitize_text_field($_GET['s']);
	$search_cat = (int)$_GET['c'];
	if($_GET['l']) {
		$search_location_name = sanitize_text_field($_GET['l']);
		$search_location_slug = sanitize_text_field($_GET['ls']);
	} else if($wp_query->queried_object->taxonomy == $taxonomy_location_url) {
		$search_location_name = $wp_query->queried_object->name;
		$search_location_slug = $wp_query->queried_object->slug;
	}

	if($search_location_slug && !$_GET['ld']) {
		$search_location_term_data = get_term_by('slug', $search_location_slug, $taxonomy_location_url);
		$sub_locations_args = array(
				'show_count'         => 0,
				'hide_empty'         => 0,
				'parent'             => $search_location_term_data->term_id,
				'pad_counts'         => 0,
				'taxonomy'           => $taxonomy_location_url
			);
		$sub_locations = get_categories($sub_locations_args);
	}
	$search_distance = ($_GET['ld'] == "all") ? 'all' : ((int)$_GET['ld'] > 150 ? '150' : (int)$_GET['ld']);

	if(is_numeric($_GET['ps'])) {
		$price_start = $_GET['ps'];
	}
	if(is_numeric($_GET['pe'])) {
		$price_end = $_GET['pe'];
	}

	if($_GET['sort']) {
		$sort_by = $_GET['sort'];
	}
	?>
	<div class="nav2">
		<div class="nav2-container">
			<a href="<?=get_permalink(get_option('post_new_ad'))?>" class="postnew-button rad3 r">
				<?php
				$payment_data = get_all_payment_data();
				if(get_option('payment_mode_active') && $payment_data['paid_ads'][$current_user_type]['first']['price']) {
					echo '<span class="text">'._d('Post ad for',784)." ".get_option('payment_currency_symbol_before').$payment_data['paid_ads'][$current_user_type]['first']['price'].get_option('payment_currency_symbol_after').'</span>';
				} else {
					echo '<span class="text">'._d('Post free ad',785).'</span>';
				}
				?>
				<span class="icon icon-plus r"></span>
			</a>
			<div class="clear-mobile1 clear20 hide"></div>

			<form action="<?=home_url('/')?>" method="get" id="header_search" class="header-search l">
				<div class="keyword-box-wrapper l">
					<?php
						$placeholder = _d('Search for',692);
						if(get_option('ads_have_ids_personal') == "1" || get_option('ads_have_ids_business') == "1") {
							$placeholder = _d('Search or AD ID',949);
						}
					?>
					<input type="text" class="input keyword rad3 l" name="s" id="s" value="<?php if($_GET['s']) { echo sanitize_text_field($_GET['s']); } ?>" placeholder="<?=$placeholder?>" />
					<div class="fake-select fake-select-header-category-chooser rad3 no-selection l">
						<div class="first"><span class="text l"></span> <span class="icon icon-arrow-up hide"></span><span class="icon icon-arrow-down"></span></div>
						<div class="options rad5 shadow hide">
							<div data-value="all" class="option<?php if($_GET['c'] == 'all') { echo ' selected'; } ?>"><?=_d('All categories',157)?></div>
							<?php
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
							//show full list of categories
							$all_cats = get_categories(array('taxonomy' => $taxonomy_ad_category_url, 'hide_empty' => $show_empty_categories));
							if(count($all_cats) > 0) {
								foreach($all_cats as $key => $cat) {
									if($cat->category_parent == '0') {
										$main_cats[] = $cat;
										unset($all_cats[$key]);
									}
								}//foreach $all_cats as $cat

								function show_cats_dropdown($all_cats, $main_cats) {
									$auto_class_cats = get_option('auto_class_cats');
									foreach ($main_cats as $key => $cat) {
										$selected = ($_GET['c'] == $category_id) ? " selected" : "";
										echo '<div data-value="'.$cat->term_id.'" class="option'.$selected.'" data-cat-parent="'.$cat->category_parent.'"><span class="icon icon-level-down"></span> '.$cat->name.'</div>';

										foreach($all_cats as $key => $subcat) {
											if($subcat->category_parent == $cat->term_id && !in_array($subcat->category_parent, $auto_class_cats)) {
												$sub_cats[] = $subcat;
												unset($all_cats[$key]);
											}
										}//foreach $c as $cat
										echo '<div class="sub-cat" data-subcats-for-cat="'.$cat->term_id.'">';
											if(count($sub_cats) > 0) {
												show_cats_dropdown($all_cats, $sub_cats);
												unset($sub_cats);
											}
										echo '</div>';
									}
								}//function show_cats

								show_cats_dropdown($all_cats, $main_cats);
								unset($all_cats, $main_cats);
							}//if count($c) > 0
							?>
						</div> <!-- options -->
						<input type="hidden" name="c" value="<?php if($_GET['c'] > 0 && $_GET['c'] != 'all') { echo (int)$_GET['c']; } ?>" />
					</div> <!-- fake-selector -->
				</div> <!-- keyword-box-wrapper -->
				<div class="clear-mobile2 hide"></div>
				<div class="location-box-wrapper l">
					<div class="location-box l">
						<input type="text" class="input location rad3 l" name="l" id="location" value="<?=$search_location_name?>" placeholder="<?=_d('Where?',691)?>" autocomplete="off" />
						<input type="hidden" class="location_slug" name="ls" id="location_slug" value="<?=$search_location_slug?>" />
						<div class="fake-select fake-select-distance rad3 no-selection l">
							<div class="first"><span class="text l"></span> <span class="icon icon-arrow-up hide"></span><span class="icon icon-arrow-down"></span></div>
							<div class="options rad5 shadow hide">
								<div data-value="all" class="option<?php if($search_distance == 'all') { echo ' selected'; } ?>"><?=_d('All',254)?></div>
								<div data-value="5" class="option<?php if($search_distance == '5') { echo ' selected'; } ?>">+ 5KM</div>
								<div data-value="10" class="option<?php if($search_distance == '10') { echo ' selected'; } ?>">+ 10KM</div>
								<div data-value="20" class="option<?php if($search_distance == '20') { echo ' selected'; } ?>">+ 20KM</div>
								<div data-value="30" class="option<?php if($search_distance == '30') { echo ' selected'; } ?>">+ 30KM</div>
								<div data-value="50" class="option<?php if($search_distance == '50') { echo ' selected'; } ?>">+ 50KM</div>
								<div data-value="100" class="option<?php if($search_distance == '100') { echo ' selected'; } ?>">+ 100KM</div>
								<div data-value="150" class="option<?php if($search_distance == "150") { echo ' selected'; } ?>">+ 150KM</div>
								<div data-value="300" class="option<?php if($search_distance == "300") { echo ' selected'; } ?>">+ 300KM</div>
							</div> <!-- options -->
							<input type="hidden" name="ld" value="<?php if($search_distance && $search_distance != 'all') { echo (int)$search_distance; } ?>" />
						</div> <!-- fake-selector -->
					</div> <!-- location-box -->
					<div class="location-autocomplete shadow rad3 hide"></div> <!-- location-autocomplete -->
				</div> <!-- location-box-wrapper -->

				<div class="clear-mobile2 hide"></div>

				<div class="precios-box-wrapper l">
					<div class="precios-box l">
						<p>
							<label for="amount">Price range:</label>
							<input type="text" id="amount" readonly style="border:0; color:#f6931f; font-weight:bold;">
						</p>						
						<div id="slider-range"></div>
						<input type="hidden" class="precios_slug" name="ps" id="precios_start" value="" />
						<input type="hidden" class="precios_slug" name="pe" id="precios_end" value="" />
					</div> <!-- precios-box -->
					<div class="precios-autocomplete shadow rad3 hide"></div> <!-- precios-autocomplete -->
				</div> <!-- precios-box-wrapper -->


				<?php
				if(count($url_filters) > 0) {
					echo '<input type="hidden" name="filter" value="'.implode("|", $url_filters).'" />';
				}
				if($price_start) {
					echo '<input type="hidden" name="ps" value="'.$price_start.'" />';
				}
				if($price_end) {
					echo '<input type="hidden" name="pe" value="'.$price_end.'" />';
				}
				if($sort_by) {
					echo '<input type="hidden" name="sort" value="'.$sort_by.'" />';
				}
				?>
				<button type="submit" form="header_search" value="<?=_d('Search',693)?>" class="search-button rad3"><span class="icon icon-search"></span> <span class="text"><?=_d('Search',693)?></span></button>
			</form>
			<div class="clear"></div>
		</div> <!-- nav2-container -->
	</div> <!-- nav2 -->
</header>

<?php
$extra_class = is_user_logged_in() && get_user_meta($current_user->ID, 'email_key', true) && !current_user_can('level_10') ? " content-nosidebar" : "";
?>
<div class="all">
	<div class="content-wrapper">
		<div class="content<?=$extra_class?>">
			<?php if (current_user_can('level_10')) { ?>
			<div class="admin-menu rad5 gray-gradient">
				<ul class="l">
					<li class="top first-link">
						<div class="link top-a"><span class="icon icon-settings"></span> <span class="text text-short"><?=_d('Settings',694)?></span></div>
						<ul class="sub-menu shadow rad5 hide">
							<li class="sub"><a href="<?=get_permalink(get_option('admin_site_settings'))?>"><span class="icon icon-settings"></span><?=_d('Site Settings',695)?></a></li>
							<li class="sub"><a href="<?=get_permalink(get_option('admin_user_settings'))?>"><span class="icon icon-user"></span><?=_d('User Settings',942)?></a></li>
							<li class="sub"><a href="<?=get_permalink(get_option('admin_ad_settings'))?>"><span class="icon icon-tag"></span><?=_d('Ad Settings',329)?></a></li>
							<li class="sub"><a href="<?=get_permalink(get_option('admin_ad_management'))?>"><span class="icon icon-tag icon-tag-ad-management rad50"></span><?=_d('Ad Management',944)?></a></li>
							<li class="sub"><a href="<?php echo get_permalink(get_option('admin_edit_categories'))?>"><span class="icon icon-categories"></span><?=_d('Edit categories',702)?></a>
							</li>
							<li class="sub"><a href="<?=get_permalink(get_option('admin_email_settings'))?>"><span class="icon icon-mail"></span><?=_d('Email settings',284)?></a></li>
							<li class="sub"><a href="<?=get_permalink(get_option('admin_payment_settings'))?>"><span class="icon icon-money"></span><?=_d('Payment Settings',696)?></a></li>
							<li class="sub"><a href="<?=get_permalink(get_option('admin_language_settings'))?>"><span class="icon icon-earth"></span><?=_d('Language Settings',697)?></a></li>
							<li class="sub"><a href="<?=get_permalink(get_option('admin_create_demo_ads'))?>"><span class="icon icon-wand"></span><?=_d('Create Demo Ads',763)?></a></li>
							<li class="sub"><a href="<?=get_permalink(get_option('admin_documentation'))?>"><span class="icon icon-help"></span><?=_d('Documentation/Help',698)?></a></li>
						</ul>
					</li>
					<li class="top">
						<div class="link link-premium top-a"><span class="icon icon-star"></span> <span class="text"><?=_d('Premium plugins',962)?></span><span class="text-short hide"><?=_d('Premium',963)?></span></div>
						<ul class="sub-menu shadow rad5 hide">
							<li class="sub"><a href="<?=get_permalink(get_option('admin_private_messages'))?>"><span class="icon icon-chat"></span><?=_d('Private messages',699)?></a>
							</li>
							<li class="sub"><a href="<?=get_permalink(get_option('admin_form_builder'))?>"><span class="icon icon-form-builder"></span><?=_d('Form builder',700)?></a>
							<li class="sub"><a href="<?=get_permalink(get_option('admin_auto_classifieds'))?>"><span class="icon icon-car"></span><?=_d('Auto classifieds',964)?></a>
							</li>
						</ul>
					</li>
					<li class="top">
						<a class="top-a" href="<?php echo admin_url(); ?>"><span class="icon icon-wordpress"></span> <span class="text"><?=_d('WordPress Dashboard',703)?></span><span class="text-short hide"><?=_d('WordPress',704)?></span></a>
					</li>
				</ul>
				<div class="clear"></div>
			</div> <!-- admin-menu -->
			<div class="clear20"></div>
			<?php } ?>

			<?php if(is_user_logged_in() && get_user_meta($current_user->ID, 'email_key', true) && !current_user_can('level_10')) { ?>
				<div class="registration-over text-center">
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
				<div class="clear30"></div>
				<?php get_footer('no-sidebar'); ?>
			<?php die(); } ?>