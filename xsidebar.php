<?php
if(!defined('error_reporting')) { define('error_reporting', '0'); }
ini_set( 'display_errors', error_reporting );
if(error_reporting == '1') { error_reporting( E_ALL ); }
if(isdolcetheme !== 1) { die(); }

global $taxonomy_location_url, $taxonomy_ad_category_url, $show_price_sort_sidebar, $show_empty_categories, $sidebar_subcategories_show_count, $sidebar_subcategories_limit, $sidebar_filters_limit, $sidebar_filters_show_post_count;

$search_keyword = sanitize_text_field($_GET['s']);
$search_cat = (int)$_GET['c'];
$search_location_name = sanitize_text_field($_GET['l']);
$search_location_slug = sanitize_text_field($_GET['ls']);
$search_location = $wp_query->query[$taxonomy_location_url] ? $wp_query->query[$taxonomy_location_url] : $search_location;
$current_location_term_data = get_term_by('slug', $search_location_slug, $taxonomy_location_url);


$search_distance = ($_GET['ld'] == "all") ? 'all' : (int)$_GET['ld'];
if(is_search()) {
	$search_category_data = get_term($search_cat, $taxonomy_ad_category_url);
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

$url_format = get_option('permalink_structure') ? '?' : '&amp;';
if($wp_query->queried_object) {
	$current_cat_url = get_term_link($wp_query->queried_object);
}

if(is_numeric($_GET['ps'])) {
	$price_start = $_GET['ps'];
}
if(is_numeric($_GET['pe'])) {
	$price_end = $_GET['pe'];
}

if($_GET['sort']) {
	$sort_by = $_GET['sort'];
}

$cookie_lang = preg_replace("/([^a-zA-Z0-9])/", "", $_COOKIE['sitelang']);
$current_lang = $cookie_lang ? $cookie_lang : get_option('site_language');
$current_lang = $current_lang ? $current_lang : 'default';
?>
<div class="sidebar">


</div> <!-- sidebar -->