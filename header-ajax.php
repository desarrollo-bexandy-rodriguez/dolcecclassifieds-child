<?php
if(!defined('error_reporting')) { define('error_reporting', '0'); }
ini_set( 'display_errors', error_reporting );
if(error_reporting == '1') { error_reporting( E_ALL ); }
if(isdolcetheme !== 1) { die(); }

global $taxonomy_ad_category_url, $show_empty_categories, $taxonomy_location_url, $allow_social_login, $allow_fb_login, $allow_tw_login, $allow_g_login, $login_err, $fb_id, $current_user_type;
$current_user = wp_get_current_user();
?>

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










