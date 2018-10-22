<?php
if(!defined('error_reporting')) { define('error_reporting', '0'); }
ini_set( 'display_errors', error_reporting );
if(error_reporting == '1') { error_reporting( E_ALL ); }
if(isdolcetheme !== 1) { die(); }

global $taxonomy_ad_url, $taxonomy_ad_category_url, $taxonomy_location_url, $months_translated, $report_ad_reasons;
$current_user = wp_get_current_user();

$cookie_lang = preg_replace("/([^a-zA-Z0-9])/", "", $_COOKIE['sitelang']);
$current_lang = $cookie_lang ? $cookie_lang : get_option('site_language');
$current_lang = $current_lang ? $current_lang : 'default';

while (have_posts()) : the_post();

// only count views if the user is not the author or the admin
if(get_the_author_meta(ID) != $current_user->ID && !current_user_can('level_10')) {
	update_post_meta(get_the_ID(), 'views', (get_post_meta(get_the_ID(), 'views', true) + 1));
}

$category_data = wp_get_post_terms(get_the_ID(), $taxonomy_ad_category_url);
$form_builder_form_fields = get_option('form_builder_form_fields');
$category_fields = get_post_meta(get_the_ID(), 'category_fields', true);

while (!$category_fields && $cat->parent > 0) {
	$category_fields = $form_builder_form_fields[$cat->parent];
	$category_fields_temp = array_values($category_fields);
	if(!$category_fields_temp[0]['use_form_for_subcats'] || $category_fields_temp[0]['use_form_for_subcats'] == "2") {
		unset($category_fields);
	}
	$cat = get_term($cat->parent, $taxonomy_ad_category_url);
}

if(!$category_fields) {
	$category_fields = $form_builder_form_fields['all'];
}

// getting the field values for the fields that have a certain purpose
foreach ($category_fields as $key => $field) {
	// '1' => 'Title',
	// '2' => 'Description',
	// '3' => 'Price amount',
	// '4' => 'Price currency',
	// '5' => 'Phone number',
	// '6' => 'Location address',

	// phone number
	if($field['input_purpose'] == "5") {
		$phone_number = str_replace(" ", "", get_post_meta(get_the_ID(), $field['input_name'], true));
	}
}

get_header(); ?>


<div class="item-page" data-post-id="<?=get_the_ID()?>">
	<?php if((get_the_author_meta(ID) == $current_user->ID || current_user_can('level_10')) && is_user_logged_in()) { ?>
		<?php
			if(get_post_status(get_the_ID()) == 'private' && !current_user_can('level_10')) {
				if(get_post_meta(get_the_ID(), 'needs_payment', true)) {
		?>
				<div class="needs-payment text-center rad5 hides">
					<span class="needs-payment-icon icon icon-warning vcenter2 l"></span>
					<div class="needs-payment-text text-center">
						<?=_d('Your ad is currently not visible in our website. You\'ll need to pay a fee to activate your ad.',498)?><br />
					</div>
					<div class="link round-corners-button rad25"><?=_d('Pay Now',499)?> <span class="icon icon-arrow-right"></span></div>
				</div> <!-- needs-payment -->
		<?php
				} elseif(get_post_meta(get_the_ID(), 'needs_activation', true)) {
		?>
				<div class="needs-activation text-center rad5">
					<span class="needs-activation-icon icon icon-warning vcenter2 l"></span>
					<div class="needs-activation-text">
						<?=_d('We are reviewing your ad to make sure it\'s accurate and we\'ll activate it very soon.',500)?><br />
						<?=_d('In the meantime, your ad will not be publicly visible in our website but you can still edit your ad if you want.',501)?><br />
						<?=_d('We\'ll let you know when your ad has been approved and is visible in the website.',502)?>
					</div>
				</div> <!-- needs-activation -->
		<?php
				}
			}
		?>
		
		<div class="edit-ad-menu rad5 gray-gradient">
			<ul class="l edit-ad-buttons">
				<?php
				$li_class = current_user_can('level_10') ? 'for-admin' : 'for-user';
				?>
				<li class="rad3 edit <?=$li_class?>"><span class="icon icon-edit"></span><span class="text"><?=_d('Edit ad',503)?></span></li>
				<li class="rad3 edit-images <?=$li_class?>"><span class="icon icon-pictures"></span><span class="text"><?=_d('Edit Images',504)?></span></li>
				<li class="rad3 edit-videos <?=$li_class?>"><span class="child-icon child-icon-video"></span><span class="text"><?=_d('Edit Videos',1029)?></span></li>
				<li class="rad3 pause <?=$li_class?><?php if(get_post_status(get_the_ID()) == 'private' || get_post_meta(get_the_ID(), 'expired')) { echo " paused"; if((get_post_meta(get_the_ID(), 'needs_activation', true) == '1' || get_post_meta(get_the_ID(), 'needs_payment', true) == '1') && !current_user_can('level_10')) { echo " paused-blocked"; } } ?>"
						data-default="<?=_d('Pause Ad',505)?>" data-paused="<?=_d('Paused',506)?>"

						data-swal-title-default="<?=_d('Do you want to pause this ad?',507)?>"
						data-swal-text-default="<?=_d('You can reactivate the ad at any time.<br />The ad will not be visible to others while it\'s paused.',508)?>"
						data-swal-button-default="<?=_d('Yes',78)?>"
						data-swal-button-cancel="<?=_d('Cancel',543)?>"
						data-swal-confirmation-default="<?=_d('Your ad is paused!',509)?>"

					<?php if(get_post_meta(get_the_ID(), 'expired')) { ?>
						data-swal-title-paused="<?=_d('Do you want to repost the ad?',510)?>"
					<?php } else { ?>
						data-swal-title-paused="<?=_d('Do you want to reactivate the ad?',511)?>"
					<?php } ?>
						data-swal-button-paused="<?=_d('Yes',78)?>"
						data-swal-confirmation-paused="<?=_d('Your ad is active again!',512)?>"

				><span class="icon icon-pause"></span><span class="text"><?php
					if(get_post_meta(get_the_ID(), 'expired')) {
						_de('Expired',513);
					} else {
						if(get_post_status(get_the_ID()) == 'private') {
							if(get_post_meta(get_the_ID(), 'needs_activation', true)) {
								_de('Pending',950);
							} else {
								_de('Paused',506);
							}
						} else {
							_de('Pause Ad',505);
						}
					}
				?></span></li>
				<li class="rad3 delete <?=$li_class?>"
					 data-swal-title="<?=_d('Do you want to delete this ad?',514)?>"
					 data-swal-text="<?=_d('This action can\'t be undone',515)?>"
					 data-swal-button="<?=_d('Yes',78)?>"
					 data-swal-cancel="<?=_d('Cancel',543)?>"
					 cancelButtonText: button.data('swal-button-cancel'),
				><span class="icon icon-delete"></span><span class="text"><?=_d('Delete Ad',516)?></span></li>
				<?php if(get_option('payment_mode_active') || current_user_can('level_10')) { ?>
				<li class="rad3 green-gradient upgrade <?=$li_class?><?php if(get_post_meta(get_the_ID(), 'needs_payment', true) == "1") { echo " active"; } ?>"><span class="icon icon-star"></span><span class="text"><?=_d('Upgrade',517)?></span></li>
				<?php } // if (payment_mode_active) ?>
				<?php
				if(current_user_can('level_10')) {
					edit_post_link('<span class="icon icon-edit"></span><span class="text">'._d('Edit in WP',518).'</span>', '<li class="rad3 '.$li_class.'">', '</li>', get_the_ID() );
				}
				?>
			</ul>
			<?php
			$print_stats = get_post_meta(get_the_ID(), 'print_stats', true) ? get_post_meta(get_the_ID(), 'print_stats', true) : 0;
			$phone_clicks = get_post_meta(get_the_ID(), 'phone_number_views', true ) ? get_post_meta(get_the_ID(), 'phone_number_views', true ) : 0;
			$ad_messages = count_ad_messages(get_the_ID());
			$ad_views = get_post_meta(get_the_ID(), 'views', true);
			?>
			<div class="stats r">
				<div class="one-stat rad3"><span class="value"><?=$print_stats?></span><span class="text"><?=_d('Prints',519)?></span></div>
				<div class="one-stat rad3"><span class="value"><?=$phone_clicks?></span><span class="text"><?=_d('Phone clicks',520)?></span></div>
				<div class="one-stat rad3"><span class="value"><?=$ad_messages?></span><span class="text"><?=_d('Messages',67)?></span></div>
				<div class="one-stat rad3"><span class="value"><?=$ad_views?></span><span class="text"><?=_d('Views',521)?></span></div>
				<div class="clear"></div>
			</div> <!-- stats -->
			<div class="clear"></div>
		</div> <!-- edit-ad-menu -->
		<div class="clear20 hide-is-mobile"></div><div class="clear"></div>

		<?php if(get_option('payment_mode_active') || current_user_can('level_10')) { ?>
		<div class="page-section buy-upgrades<?php if(get_post_meta(get_the_ID(), 'needs_payment', true) != "1") { echo " hide"; } ?>">
			<div class="page-section-close round-corners-button rad17 l"><span class="icon icon-arrow-left"></span> <?=_d('Back to the ad',522)?></div>
			<div class="clear"></div>
			<?php ad_needs_payment_html(get_the_ID()); ?>
			<div class="clear40"></div>
		</div>
		<?php } ?>

		<!-- edit entry form -->
		<div class="page-section edit-entry hide">
			<div class="page-section-close round-corners-button rad17 l"><span class="icon icon-arrow-left"></span> <?=_d('Back to the ad',522)?></div>
			<div class="clear"></div>
			<div class="edit-entry-content pc-70 center">
				<?php
				$post_id_to_edit = get_the_ID();
				$category_id = get_post_meta($post_id_to_edit, 'cat_id', true);
				if(!$category_id) {
					$terms = wp_get_post_terms($post_id_to_edit, $taxonomy_ad_category_url);
					$category_id = $terms[0]->term_id;
				}
				include(get_stylesheet_directory().'/user-post-new-ad-form.php');
				?>
			</div>
		</div>

		<!-- add deletion confirmation -->
		<div class="entry-deleted text-center hide">
			<span class="icon icon-delete"></span>
			<div class="clear20"></div>
			<span class="text"><?=_d('Your ad was deleted.',523)?></span>
		</div>
	<?php } ?>

		<div class="page-section entry<?php if(get_post_meta(get_the_ID(), 'needs_payment', true) == "1") { echo " hide"; } ?>" data-post-id="<?php the_ID(); ?>">
		<?php if ( is_active_sidebar( 'ad-page-above-ad' ) ) { ?>
			<div class="ad-page-above-ad">
				<?php dynamic_sidebar( 'ad-page-above-ad' ); ?>
			</div>
			<div class="clear10"></div>
		<?php } elseif(current_user_can('level_10')) { ?>
			<div class="ad-page-above-ad">
				<?=_d('Go to your',524)?> <a href="<?=admin_url('widgets.php')?>"><?=_d('widgets page',525)?></a> <?=_d('to add content here.',526)?>
			</div> <!-- widgetbox -->
			<div class="clear10"></div>
		<?php } ?>

		<?php
		if(!is_wp_error(get_term_link($category_data[0]))) { ?>
		<div class="breadcrumbs">
			<div class="link l"><?=_d('Category',364)?>: </div>
			<?php
			$all_parents = array();
			foreach ($category_data as $one_cat) {
				$all_parents[] = $one_cat->parent;
			}
			foreach ($category_data as $one_cat) {
				if(!in_array($one_cat->term_id, $all_parents)) {
					$last_child = $one_cat;
					break;
				}
			}

			$child = get_term($last_child->term_id, $taxonomy_ad_category_url);
			while($child->parent > 0) {
				$links[] = '<a href="'.get_term_link($child).'" title="'.$child->name.'">'.$child->name.'</a>';
				$child = get_term($child->parent, $taxonomy_ad_category_url);
			}
			$links[] = '<a href="'.get_term_link($child).'" title="'.$child->name.'">'.$child->name.'</a>';
			$links = array_reverse($links);
			$i = "1";
			foreach ($links as $key => $link) {
				$icon = (count($links) != $i) ? '&nbsp; &#187; ' : '';
				echo '<div class="link l">'.$link.$icon.'</div>';
				$i++;
			}
			?>
			<div class="clear"></div>
		</div>
		<div class="clear10"></div>
		<?php } // breadcrumbs ?>

		<?php if(is_user_logged_in() && (get_the_author_meta(ID) == $current_user->ID || current_user_can('level_10'))) { ?>
			<?php if(get_post_meta(get_the_ID(), 'expired')) { ?>
				<div class="ad-is-paused rad17 l no-selection"><span class="text l"><?=_d('Expired',513)?></span> <span class="icon icon-pause"></span></div>
			<?php } else { ?>
				<div class="ad-is-paused rad17 l no-selection<?php if(get_post_status(get_the_ID()) != 'private') { echo " hide"; } ?>"><span class="text l"><?=get_post_meta(get_the_ID(), 'needs_activation', true) ? _de('Pending',950) : _de('Paused',506)?></span> <span class="icon icon-pause"></span></div>
			<?php } ?>

			<?php
			if(get_post_meta(get_the_ID(), 'always_on_top', true))
				$labels['2'] = strtolower(_d('always on top',240));

			if(get_post_meta(get_the_ID(), 'highlighted_ad', true))
				$labels['3'] = strtolower(_d('highlighted ad',436));

			if(get_post_meta(get_the_ID(), 'push_ad', true))
				$labels['4'] = strtolower(_d('push to top',437));

			if(count($labels) > 0) {
				echo '<div class="labels">';
				foreach ($labels as $key => $label) {
					echo '<div class="label label'.$key.' rad3 l">'.strtolower($label).'</div>';
				}
				echo '<div class="clear10"></div>';
				echo '</div> <!-- labels -->';
			}
		} elseif(get_option('expired_ads_notice') == "1" && get_post_meta(get_the_ID(), 'expired')) {
			echo '<div class="ad-is-paused no-selection errr rad3 l">'._d('Expired',513).' &nbsp;</div>';
		}
		?>

		<h3><?php the_title(); ?></h3>
		<div class="clear"></div>
		<div class="clear30 hide-is-mobile"></div>

		<?php
		$photos = get_children( array('post_parent' => get_the_ID(), 'post_status' => 'inherit', 'post_type' => 'attachment', 'post_mime_type' => array('image','video'), 'order' => 'ASC', 'orderby' => 'menu_order ID') );
		//reset the keys of the returned array so we can retrieve the first image from the array
		$photos = array_values($photos);
		$videos = get_children( array('post_parent' => get_the_ID(), 'post_status' => 'inherit', 'post_type' => 'attachment', 'post_mime_type' => 'video', 'order' => 'ASC', 'orderby' => 'menu_order ID') );
		//reset the keys of the returned array so we can retrieve the first image from the array
		$videos = array_values($videos);
		?>

		<div class="item-details-wrapper">
			<div class="item-details">
				<div class="item-details2">
					<div class="item-details3">
						<div class="item-conditions">
							<?php
							// Tyres
							$auto_class_extra_specifications_tyres = array();
							foreach ($category_fields as $field) {
								if($field['input_purpose'] == "15") { // Tyre for
									// Passenger car
									$tyre_for_options = $field['input_values'][$current_lang] ? $field['input_values'][$current_lang] : $field['input_values']['default'];
									reset($tyre_for_options);
									$first_option = key($tyre_for_options);
									if($first_option == get_post_meta(get_the_ID(), $field['input_name'], true) - 1) {
										$for_key = "1";
										$for_value = "P";
									}

									// Motorcycle
									$second_option_array = array_slice($tyre_for_options, 1, 1, true);
									reset($second_option_array);
									$second_option = key($second_option_array);
									if($second_option == get_post_meta(get_the_ID(), $field['input_name'], true) - 1) {
										$for_key = "6";
										$for_value = " &nbsp;M/C";
									}

									// Light truck
									$fourth_option_array = array_slice($tyre_for_options, 3, 1, true);
									reset($fourth_option_array);
									$fourth_option = key($fourth_option_array);
									if($fourth_option == get_post_meta(get_the_ID(), $field['input_name'], true) - 1) {
										$for_key = "7";
										$for_value = " &nbsp;LT";
									}

									// Temporary
									end($tyre_for_options);
									$last_option = key($tyre_for_options);
									if($last_option == get_post_meta(get_the_ID(), $field['input_name'], true) - 1) {
										$for_key = "10";
										$for_value = " &nbsp;T";
									}

									if($for_key && $for_value) {
										$auto_class_extra_specifications_tyres[$for_key] = $for_value;
									}
								}
								if($field['input_purpose'] == "16") { // Section width
									$arr = $field['input_values'][$current_lang] ? $field['input_values'][$current_lang] : $field['input_values']['default'];
									$section_value = $arr[get_post_meta(get_the_ID(), $field['input_name'], true) - 1];
									$auto_class_extra_specifications_tyres['2'] = preg_replace("/([^0-9])/", "", $section_value);
								}
								if($field['input_purpose'] == "17") { // Tyre aspect ratio
									$arr = $field['input_values'][$current_lang] ? $field['input_values'][$current_lang] : $field['input_values']['default'];
									$ratio_value = $arr[get_post_meta(get_the_ID(), $field['input_name'], true) - 1];
									$auto_class_extra_specifications_tyres['3'] = "/".$ratio_value;
								}
								if($field['input_purpose'] == "19") { // Tyre construction
									$tyre_construction_options = $field['input_values'][$current_lang] ? $field['input_values'][$current_lang] : $field['input_values']['default'];
									reset($tyre_construction_options);
									$first_option = key($tyre_construction_options);
									if($first_option == get_post_meta(get_the_ID(), $field['input_name'], true) - 1) {
										$auto_class_extra_specifications_tyres['4'] = " &nbsp;R";
									}
								}
								if($field['input_purpose'] == "18") { // Rim Diameter
									$arr = $field['input_values'][$current_lang] ? $field['input_values'][$current_lang] : $field['input_values']['default'];
									$diameter_value = $arr[get_post_meta(get_the_ID(), $field['input_name'], true) - 1];
									$auto_class_extra_specifications_tyres['5'] = preg_replace("/([^0-9])/", "", $diameter_value);
								}
								if($field['input_purpose'] == "20") { // Load index
									$auto_class_extra_specifications_tyres['8'] = " &nbsp;".get_post_meta(get_the_ID(), $field['input_name'], true);
								}
								if($field['input_purpose'] == "21") { // Speed rating
									$arr = $field['input_values'][$current_lang] ? $field['input_values'][$current_lang] : $field['input_values']['default'];
									$speed_value = explode(" ", $arr[get_post_meta(get_the_ID(), $field['input_name'], true) - 1]);
									$auto_class_extra_specifications_tyres['9'] = $speed_value[0];
								}
							}
							$auto_class_extra_specifications_tyres = array_filter($auto_class_extra_specifications_tyres);
							ksort($auto_class_extra_specifications_tyres);

							if(count($auto_class_extra_specifications_tyres) > 0) { ?>
								<div class="auto-class-extra-specifications auto-class-extra-specifications-tyres pc-100 l nopadding">
									<span class="icon iconac-tyre"></span><span class="value"><?=implode($auto_class_extra_specifications_tyres)?></span>
								</div>
								<?php
								echo '<div class="clear10"></div>';
							}
							// Tyres

							if(get_post_meta(get_the_ID(), 'make', true)) {
								$make = get_term(get_post_meta(get_the_ID(), 'make', true), $taxonomy_ad_category_url);
								if(get_post_meta(get_the_ID(), 'model', true)) {
									$model = get_term(get_post_meta(get_the_ID(), 'model', true), $taxonomy_ad_category_url);
								}
								if($make) {
								?>
								<div class="clear"></div>
								<div class="condition pc-50 l nopadding">
									<div class="label"><?=_d('Make',ac13)?> &nbsp;</div><div class="value"><b><?=$make->name?></b></div>
								</div>
								<?php if($model) { ?>
								<div class="condition pc-50 l nopadding">
									<div class="label"><?=_d('Model',ac14)?> &nbsp;</div><div class="value"><b><?=$model->name?></b></div>
								</div>
								<?php } ?>
								<div class="clear"></div>
								<?php
								}
							}

							$price = format_price(get_the_ID());
							if($price) { ?>
								<div class="price pc-50 l nopadding">
									<div class="label"><?=_d('price',527)?></div>
									<div class="value"><?=$price?></div>
								</div>
								<div class="condition pc-50 l nopadding">
									<div class="label"><?=_d('Views',521)?> &nbsp;</div><div class="value"><?=get_post_meta(get_the_ID(), 'views', true)?></div>
								</div>
								<div class="clear20"></div>
							<?php } // if $price ?>
							<?php
							$auto_class_extra_specifications_cars = array();
							foreach ($category_fields as $field) {
								$input_values = $field['input_values'][$current_lang] ? $field['input_values'][$current_lang] : $field['input_values']['default'];
								if($field['input_ad_page_position'] == '1' && $field['input_purpose'] == "0") {
									$field_post_meta = get_post_meta(get_the_ID(), $field['input_name'], true);
									if(is_array($field_post_meta) && is_array($input_values)) {
										$field_value = array();
										foreach ($field_post_meta as $key => $value) {
											$field_value[] = $input_values[$value - 1];
										}
										$field_value = implode(", ", $field_value);
									} else {
										$field_value = $input_values ? $input_values[$field_post_meta - 1] : $field_post_meta;
									}

									if($field_value) {
										$f_name = $field['name'][$current_lang] ? $field['name'][$current_lang] : $field['name']['default'];
										echo '<div class="condition pc-50 l nopadding"><div class="label">'.$f_name.'</div><div class="value">'.$field_value.'</div></div>';
									}
								}

								if($field['input_purpose'] == "8") { // year
									$auto_class_extra_specifications_cars['iconac-calendar'] = get_post_meta(get_the_ID(), $field['input_name'], true);
								}
								if($field['input_purpose'] == "9") { // fuel type
									$auto_class_extra_specifications_cars['iconac-gas'] = $input_values[get_post_meta(get_the_ID(), $field['input_name'], true) - 1];
								}
								if($field['input_purpose'] == "10") { // mileage
									$auto_class_extra_specifications_cars['iconac-mileage'] = get_post_meta(get_the_ID(), $field['input_name'], true) ? number_format(get_post_meta(get_the_ID(), $field['input_name'], true))." "._d('km',ac20) : "";
								}
								if($field['input_purpose'] == "11") { // power
									$power = get_post_meta(get_the_ID(), $field['input_name'], true);
									if($power) {
										$power_hp = '<span class="hp">'.number_format($power*1.3410220888).' '._d('hp',ac22).'</span class="hp">';
										$auto_class_extra_specifications_cars['iconac-power'] = $power." "._d('kW',ac21).$power_hp;
									}
								}
							} // for each field
							$auto_class_extra_specifications_cars = array_filter($auto_class_extra_specifications_cars);
							?>

							<div class="clear"></div>
							<div class="condition pc-50 l nopadding">
								<?php
								$date = get_the_time('j').' '.$months_translated[get_the_time('n')].' '.get_the_time('Y');
								?>
								<div class="label"><?=_d('Posted on',528)?></div><div class="value"><?=$date?>
								<?php if(date_time_ago(get_post_time())) {
									echo '<br /><small>( '.date_time_ago(get_post_time()).' )</small>';
								} ?>
								</div>
							</div>
							<?php
							$location_data = wp_get_post_terms(get_the_ID(), $taxonomy_location_url);
							if($location_data) {
								$location_data = $location_data[0];
								$location_links[] = '<a href="'.get_term_link($location_data).'" title="'.$location_data->description.'">'.$location_data->name.'</a>';
								while($location_data->parent > 0) {
									$location_data = get_term($location_data->parent, $taxonomy_location_url);
									$location_links[] = '<a href="'.get_term_link($location_data).'" title="'.$location_data->description.'">'.$location_data->name.'</a>';
								}
								if($location_links) {
									echo '<div class="condition pc-50 l nopadding">
										<div class="label">'._d('Location',374).'</div><div class="value"><span class="icon icon-map-marker"></span> '.implode(', ', $location_links).'</div>
									</div>
									<div class="clear"></div>';
								}
							} // if($location_data)
							?>
							<div class="clear"></div>

							<?php if(!$price) { ?>
							<div class="condition pc-50 l nopadding">
								<div class="label"><?=_d('Views',521)?> &nbsp;</div><div class="value"><?=get_post_meta(get_the_ID(), 'views', true)?></div>
							</div>
							<?php } ?>

							<?php
							if(count($auto_class_extra_specifications_cars) > 0) {
								foreach ($auto_class_extra_specifications_cars as $icon => $value) { ?>
								<div class="auto-class-extra-specifications pc-50 l nopadding">
									<span class="icon <?=$icon?>"></span><span class="value"><?=$value?></span>
								</div>
								<?php }
								echo '<div class="clear20"></div>';
							}
							?>


							<?php
							$user_type = get_the_author_meta('user_type') ? get_the_author_meta('user_type') : "personal";
							if(get_option('ads_have_ids_'.$user_type) == "1") { ?>
							<div class="clear"></div>
							<div class="condition ad-id pc-100 nopadding">
								<div class="label l"><?=_d('AD ID',948)?>:</div>
								<div class="value l"><?=get_ad_unique_id(get_the_ID())?></div>
							</div>
							<?php } ?>

							<div class="clear"></div>
						</div> <!-- item-conditions -->
										<!-- ----------------------AGREGAR VIDEOS 1 -------------------------------------- -->
				<div class="clear"></div>
						<div class="item-videos">
							<?php if($videos) { 
								foreach ($videos as $key => $video) {
									$video_id = $video->ID;
									$video_mime_type = $video->post_mime_type;
									$video_url = wp_get_attachment_url($video_id);
									echo'<video controls ><source src="'.$video_url.'" type="'.$video_mime_type.'"></video>';
								}
							}
							?>
						</div> <!-- item-videos -->
						<div class="clear"></div>
		<!-- ------------------------- FIN AGREGAR VIDEOS ------------------------------------- -->
						<div class="clear30 hide-is-mobile"></div>
						<div class="clear"></div>
					</div> <!-- item-details3 -->
				</div> <!-- item-details2 -->

				<?php if($photos) { ?>
				<div class="swiper-container hide">
					<div class="swiper-wrapper"></div>
					<div class="swiper-pagination"></div>
				</div> <!-- swiper-container -->
				<?php } ?>

				<div class="seller-and-report">
					<?php if(get_the_author_meta('ID')) { ?>
					<div class="seller-info">
						<div class="sold-by"><?=_d('Sold by',529)?></div>
						<a class="seller-link seller" href="<?=get_author_posts_url(get_the_author_meta('ID'))?>" title="<?php the_author(); ?>">
							<?php
							$avatar_id = get_user_meta(get_the_author_meta('ID'), 'avatar_id', true);
							$avatar_url_social = get_user_meta(get_the_author_meta('ID'), 'avatar_url', true);
							if($avatar_url_social) {
								$avatar_url = $avatar_url_social;
							} else {
								$avatar_url = wp_get_attachment_image_src($avatar_id, 'avatar');
								$avatar_url = $avatar_url[0];
							}
							if(!$avatar_url) {
								$avatar_url = get_stylesheet_directory_uri().'/img/no-avatar.png';
							}

							if($avatar_url) {
								echo '<span class="avatar rad50 l"><img src="'.$avatar_url.'" width="90" height="90" alt="'.get_the_author().'" class="l" /></span>';
							}
							$user_type_icon = get_user_meta(get_the_author_meta('ID'), 'user_type', true) == "personal" ? ' <span class="user-business-icon icon-business" title="'._d('Business account',920).'"></span>' : "";
							?>
							<span class="seller-name"><?php the_author(); ?><?=is_business_account(get_the_author_meta('ID'))?><?=is_user_verified(get_the_author_meta('ID'))?> <?=get_user_rating(get_the_author_meta('ID'))?></span><br />
							<span class="seller-details">
								<span class="items-online"><b><span class="icon icon-star"></span> <?=(int)get_user_meta(get_the_author_meta('ID'), 'total_reviews', true)?></b> <?=strtolower(_d('reviews',869))?></span><br />
								<span class="items-online"><b><span class="icon icon-tags"></span> <?=count_user_posts(get_the_author_meta('ID'), $taxonomy_ad_url)?></b> <?=_d('ads online',172)?></span><br />
								<span class="member-since">
									<?php
									$year = date('Y', strtotime(get_the_author_meta('user_registered')));
									$month = date('n', strtotime(get_the_author_meta('user_registered')));
									echo _d('Member since',173)." ".$months_translated[$month]." ".$year;
									?>
								</span>
							</span>
							<span class="clear"></span>
						</a> <!-- seller -->
						<?php
						// if(get_the_author_meta('user_url')) {
						// 	echo '<div class="clear10 hide-is-mobile"></div>';
						// 	echo '<div class="seller-website text-center"><a class="website" href="'.get_the_author_meta('user_url').'" rel="nofollow" target="_blank"><span class="icon icon-external-link"></span> '._d('Seller\'s website',174).'</a></div>';
						// }
						?>
						<?php if(get_the_author_meta('ID') > 0 && get_the_author_meta('ID') != $current_user->ID) { ?>
						<div class="clear20"></div>
						<div class="send-message rad25 big-button<?php if(!is_user_logged_in()) { echo ' show-login-popup'; } else { echo ' send-message-popup'; } ?>"><span class="icon icon-mail"></span><?=_d('Send message',179)?></div>
						<?php } ?>
						<?php if($phone_number) { ?>
						<div class="clear20 hide-is-mobile"></div>
						<div class="phone-number <?php if(!is_user_logged_in() && get_option('phone_number_only_for_registered') == "1") { echo ' show-login-popup'; } ?>">
							<div class="number">
								<span class="icon icon-phone l"></span>
								<?php
								if(is_user_logged_in()) {
									$phone_number_code = '<a href="tel:'.$phone_number.'" class="text">'.$phone_number.'</a>';
								} else {
									$phone_number_code = '<a class="text">'.substr($phone_number, 0, 4)." ";
									for ($i=0; $i < 7; $i++) { 
										$phone_number_code .= '<span class="icon icon-asterisk"></span>';
									}
									$phone_number_code .= '</a>';
								}
								echo $phone_number_code;
								?>
							</div>
							<?php if(!is_user_logged_in()) { ?>
							<div class="show-phone-number l"><small><u><?=_d('Show phone number',530)?></u></small></div>
							<?php } ?>
							<div class="clear"></div>
						</div>
						<?php } // if phone number ?>
						<div class="clear20"></div>
						<div class="save-print-report">
							<div class="option print l" ><span class="icon icon-print l"></span> <b><?=_d('Print',531)?></b><svg version="1.1" class="loader hide r" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 50 50" style="enable-background:new 0 0 50 50;" xml:space="preserve"><path fill="#000" d="M43.935,25.145c0-10.318-8.364-18.683-18.683-18.683c-10.318,0-18.683,8.365-18.683,18.683h4.068c0-8.071,6.543-14.615,14.615-14.615c8.072,0,14.615,6.543,14.615,14.615H43.935z"><animateTransform attributeType="xml" attributeName="transform" type="rotate" from="0 25 25" to="360 25 25" dur="0.6s" repeatCount="indefinite"/></path></svg></div>
							<div class="option report l<?php if(!is_user_logged_in()) { echo ' show-login-popup'; } else { echo ' send-report-popup'; } ?>"><span class="icon icon-warning l"></span> <b><?=_d('Report',532)?></b></div>
						</div>
						<?php if(current_user_can('level_10')) { ?>
							<div class="clear"></div>
							<div class="admin-show-user-info ok rad3" style="text-align: left; padding: 10px;">
								<?=_d('UserID',175)?>: <?=get_the_author_meta('ID')?><br />
								<?php
									if(defined('dolce_demo_theme') && $current_user->ID != "1") {
										$user_email = explode("@", get_the_author_meta('user_email'));
										$user_email = "xxxxxx@".$user_email['1'];
									} else {
										$user_email = get_the_author_meta('user_email');
									}
									echo _d('Email',176).": ".$user_email."<br />";

									if(get_the_author_meta('registration_ip')) {
										if(defined('dolce_demo_theme') && $current_user->ID != "1") {
											$registration_ip = preg_replace('/[0-9]+/', "x", get_the_author_meta('registration_ip'));
										} else {
											$registration_ip = get_the_author_meta('registration_ip');
										}
										echo _d('Registration IP',772).": ".$registration_ip."<br />";
									}

									if(get_post_meta(get_the_ID(), 'ad_posting_ip', true)) {
										if(defined('dolce_demo_theme') && $current_user->ID != "1") {
											$ad_posting_ip = preg_replace('/[0-9]+/', "x", get_post_meta(get_the_ID(), 'ad_posting_ip', true));
										} else {
											$ad_posting_ip = get_post_meta(get_the_ID(), 'ad_posting_ip', true);
										}
										echo _d('Ad posted from IP',773).": ".$ad_posting_ip."<br />";
									}
								?>
								<a href="<?=home_url()?>/edit-account/?userid=<?=get_the_author_meta('ID')?>"><?=_d('Edit user',177)?></a><br />
								<a href="<?=get_edit_user_link(get_the_author_meta('ID'))?>"><?=_d('Edit user in WordPress',178)?></a>
							</div>
						<?php } ?>
						<div class="clear"></div>
					</div> <!-- seller-info -->
					<?php } else { // if the ad has a user ID ?>
						<div class="err rad3">
							<?=_d('This ad has no user ID.',533)?>
							<div class="clear10"></div>
							<?=_d('This means the ad was posted by an unregistered user and will be deleted if the user does not register.',534)?>
						</div>
					<?php } ?>
				</div> <!-- seller-and-report -->

				<?php if(is_user_logged_in() && get_the_author_meta('ID') != $current_user->ID) { private_message_form(get_the_author_meta('ID'),get_the_ID()); } ?>
				<?php if(is_user_logged_in()) { ?>
				<div class="report-ad-popup shadow rad5 hide">
					<div class="title l"><?=_d('Report this ad',535)?></div>
					<div class="close round-corners-button rad25 r"><span class="icon icon-cancel"></span> <?=_d('close',195)?></div>
					<div class="clear20"></div>

					<form action="" method="post" id="report-ad-form" class="report-ad-form form-styling">
						<div class="form-msg form-err-msg err rad5 hide"></div>
						<div class="form-msg form-ok-msg ok rad5 hide"></div>
						<input type="hidden" name="report_ad_id" id="report_ad_id" value="<?=get_the_ID()?>" />
						<div class="clear10"></div>

						<div class="form-label">
							<label class="label"><?=_d('Reason',536)?> <span class="mandatory icon icon-asterisk"></span></label>
						</div> <!-- form-label -->
						<div class="form-input">
							<div class="err-msg hide"></div>
							<div class="fake-select fake-select-reason rad3 no-selection l">
								<div class="first"><span class="text l"></span> <span class="icon icon-arrow-up hide"></span><span class="icon icon-arrow-down"></span></div>
								<div class="options rad5 shadow hide">
									<?php
									foreach ($report_ad_reasons as $key => $reason) {
										echo '<div data-value="'.$key.'" class="option">'.$reason.'</div>';
									}
									?>
								</div> <!-- options -->
								<input type="hidden" name="reason" value="1" />
							</div> <!-- fake-selector -->
						</div> <!-- form-input --> <div class="formseparator"></div>

						<div class="form-label">
							<label class="label" for="message"><?=_d('Message',68)?></label>
						</div> <!-- form-label -->
						<div class="form-input">
							<div class="err-msg hide"></div>
							<textarea name="message" maxlength="2000" id="message" class="textarea col-100" rows="5"></textarea>
							<small class="help r"><b>!</b> <?=_d('html code will be removed',406)?></small>
						</div> <!-- form-input --> <div class="formseparator"></div>

						<div class="clear20"></div>
						<div class="buttons text-center">
							<div class="submit-message">
								<span class="icon icon-asterisk"></span> <?=_d('Mandatory fields',251)?>
							</div>
							<div class="clear20"></div>
							<button class="button submit-button submit-button-default send-report round-corners-button rad25" name="submit">
								<svg  class="icon for-loading loader hide" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 50 50" style="enable-background:new 0 0 50 50;" xml:space="preserve"><path fill="#000" d="M43.935,25.145c0-10.318-8.364-18.683-18.683-18.683c-10.318,0-18.683,8.365-18.683,18.683h4.068c0-8.071,6.543-14.615,14.615-14.615c8.072,0,14.615,6.543,14.615,14.615H43.935z"><animateTransform attributeType="xml" attributeName="transform" type="rotate" from="0 25 25" to="360 25 25" dur="0.6s" repeatCount="indefinite" /></path></svg>
								<span class="icon for-done icon-checkmark hide"></span>
								<span class="icon for-err icon-cancel hide"></span>

								<span class="button-text text-default hide"><?=_d('Send Report',541)?></span>
								<span class="button-text text-loading hide"><?=_d('Sending',410)?></span>
								<span class="button-text text-done hide"><?=_d('Report Sent',542)?></span>
								<span class="button-text text-err hide"><?=_d('Error',94)?></span>
							</button>
							<div class="button cancel-report round-corners-button rad25"><?=_d('Cancel',543)?></div>
						</div>
					</form> <!-- report-ad-form -->
				</div> <!-- report-ad-popup -->
				<?php } // if is_user_logged_in() then show report form ?>
			</div> <!-- item-details -->
		</div> <!-- item-details-wrapper -->

		<div class="item-images">
			<?php if($photos) { ?>
			<div class="selected-thumb-wrapper">
				<div class="selected-thumb">
					<?php
					$main_image_id = get_post_thumbnail_id(get_the_ID()) ? get_post_thumbnail_id(get_the_ID()) : $photos[0]->ID;
					$full_img_url = wp_get_attachment_image_src($main_image_id, 'full');
					foreach ($photos as $key => $photo) {
						if($photo->ID == $main_image_id) {
							$index = $key;
						}
					}
					$photo_html = wp_get_attachment_image_src($main_image_id, 'preview-thumb');
					echo '<a href="'.$full_img_url[0].'"><img data-index="'.$index.'" src="'.$photo_html[0].'" alt="" /></a>';
					?>
				</div> <!-- selected-thumb -->
			</div> <!-- selected-thumb-wrapper -->

			<div class="thumbs-gallery" id="scrollbar">
	            <div class="scrollbar hide"><div class="thumb rad17"></div></div>

	            <div class="viewport">
	                 <div class="overview">
						<?php
						foreach ($photos as $key => $photo) {
							$preview_thumb_url = wp_get_attachment_image_src( $photo->ID, 'preview-thumb' );
							$full_img_url = wp_get_attachment_image_src( $photo->ID, 'full' );
							$full_img_url_array[] = $full_img_url;
							$photo_html =  wp_get_attachment_image_src( $photo->ID, 'gallery-thumb' );
							$photo_html = '<img data-preview-th="'.$preview_thumb_url[0].'" data-full-img="'.$full_img_url[0].'" data-index="'.$key.'" src="'.$photo_html[0].'" alt="" class="gallery-thumb" />';
							if ($photo->post_mime_type == 'video/mp4'){
								$video_html =  wp_get_attachment_url( $photo->ID );
								$photo_html = '<img data-preview-th="'.$video_html.'" data-full-img="'.$video_html.'" data-index="'.$key.'" src="'.$video_html.'" alt="" class="gallery-thumb" />';
							}
							
							echo $photo_html;
						}
						?>
					</div> <!-- overview -->
				</div> <!-- viewport -->
			</div> <!-- thumbs-gallery -->
			<?php } else { ?>
			<div class="no-photos text-center">
				<span class="icon icon-pictures"></span>
				<span class="text"><?=_d('No pictures',544)?></span>
			</div> <!-- no-photos -->
			<?php } ?>
			<div class="clear"></div>
		</div> <!-- item-images -->



		<?php get_template_part('includes/image-lightbox-html-code'); ?>
		<script type="text/javascript">
			jQuery(document).ready(function($) {
				var pswpElement = document.querySelectorAll('.pswp')[0];

				// build array with images
				var items = [
				<?php
				foreach ($full_img_url_array as $key => $img) {
					echo "{ src: '".$img[0]."', w: ".$img[1].", h: ".$img[2]." },"."\n";
				}
				?>
				];

				var options = {
				    showHideOpacity: true,
				    bgOpacity: 0.85,
				    history: false,
				    errorMsg: "<div class='pswp__error-msg'><?=_d('The image could not be loaded',545)?></div>",
				    preload: [1,3]
				};

				// Initialize and open PhotoSwipe
				$('.selected-thumb, .thumbs-gallery').on('click', 'img', function(event) {
					event.preventDefault();
					var gallery = new PhotoSwipe( pswpElement, PhotoSwipeUI_Default, items, options);
					gallery.options.index = $(this).data('index');
					gallery.init();
				});
			});
		</script>

		<?php
		foreach ($category_fields as $field) {
			if($field['input_ad_page_position'] == '2' || $field['input_purpose'] >= "8") {
				$specification_class = "pc-20";
				$value_class = "";
				$field_post_meta = get_post_meta(get_the_ID(), $field['input_name'], true);
				if($field_post_meta) {
					if(is_array($field_post_meta)) {
						$field_value = array();
						$field_values = $field['input_values'][$current_lang] ? $field['input_values'][$current_lang] : $field['input_values']['default'];
						foreach ($field_post_meta as $key => $value) {
							$field_value[] = '<span class="bull">&bull;</span> '.$field_values[$value - 1].'';
						}
						if(count($field_value) > 10) {
							$specification_class = "pc-100";
							$value_class = " stretched-specification pc-100 nopadding";
							$field_value = implode("&nbsp;", $field_value).' <span class="bull">&bull;</span>';
						} else {
							$field_value = implode("<br />", $field_value);
						}
					} else {
						$current_lang = $field['input_values'][$current_lang] ? $current_lang : "default";
						$field_value = $field['input_values'][$current_lang] ? $field['input_values'][$current_lang][$field_post_meta - 1] : $field_post_meta;

						if($field['input_purpose'] == "14" && in_array($field_value, array("2","3","4"))) { // emission label
							$field_value = '<img class="auto-class-emission-label" viewBox="0 0 30 30" src="'.get_stylesheet_directory_uri().'/plugins/auto-classifieds/img/emission-label-'.$field_value.'.svg" alt="">';
						}

						if($field['input_purpose'] == "10") { // mileage
							$field_value = number_format($field_value)." "._d('km',ac20);
						}

						if($field['input_purpose'] == "11") { // power
							$power_hp = '<span class="hp">'.number_format($field_value*1.3410220888).' '._d('hp',ac22).'</span class="hp">';
							$field_value = $field_value." "._d('kW',ac21).$power_hp;
						}

						if($field['input_purpose'] == "12" || $field['input_purpose'] == "13") { // body color & interior color
							$field_value =  $field_value.'<span class="auto-class-color-box rad50" style="background: '.trim($field_value).'"></span>';
						}
					}
					if($field_value) {
						$f_name = $field['name'][$current_lang] ? $field['name'][$current_lang] : $field['name']['default'];
						$specification[] = '<div class="specification '.$specification_class.' l"><div class="label">'.$f_name.'</div><div class="value'.$value_class.'">'.$field_value.'</div></div>';
					}
					unset($field_value);
				} // if($field_post_meta)
			}
		}
		if($specification) {
			echo '<div class="clear"></div>
			<div class="item-specifications col-100 rad5">
				'.implode("", $specification).'
				<div class="clear"></div>
			</div>';
		}
		?>

		<div class="clear30"></div>

		<div class="item-description">
			<?php the_content(); ?>
		</div>

		<?php if ( is_active_sidebar( 'ad-page-under-ad' ) ) { ?>
			<div class="clear10"></div>
			<div class="ad-page-under-ad">
				<?php dynamic_sidebar( 'ad-page-under-ad' ); ?>
			</div>
			<div class="clear10"></div>
		<?php } elseif(current_user_can('level_10')) { ?>
			<div class="clear10"></div>
			<div class="ad-page-under-ad">
				<?=_d('Go to your',524)?> <a href="<?=admin_url('widgets.php')?>"><?=_d('widgets page',525)?></a> <?=_d('to add content here.',526)?>
			</div> <!-- widgetbox -->
			<div class="clear10"></div>
		<?php } ?>

		<div class="clear20"></div>
	</div> <!-- item -->
</div> <!-- item-page -->

<?php get_footer('no-sidebar'); endwhile; ?>