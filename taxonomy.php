<?php
if(!defined('error_reporting')) { define('error_reporting', '0'); }
ini_set( 'display_errors', error_reporting );
if(error_reporting == '1') { error_reporting( E_ALL ); }
if(isdolcetheme !== 1) { die(); }

global $taxonomy_location_url, $taxonomy_ad_category_url, $taxonomy_ad_url, $ads_per_page, $current_user_type;

$search_keyword = sanitize_text_field($_GET['s']);
$search_cat = (int)$_GET['c'];
$search_location = sanitize_text_field($_GET['ls']) ? sanitize_text_field($_GET['ls']) : sanitize_text_field($_GET['l']);
$cookie_lang = preg_replace("/([^a-zA-Z0-9])/", "", $_COOKIE['sitelang']);
$current_lang = $cookie_lang ? $cookie_lang : get_option('site_language');
$current_lang = $current_lang ? $current_lang : 'default';
if(get_term_by('slug', $search_location, $taxonomy_location_url)) {
	$search_location_term_data = get_term_by('slug', $search_location, $taxonomy_location_url);
} else {
	$search_location_term_data = get_term_by('name', $search_location, $taxonomy_location_url);
}
// ld = location distance
$search_distance = ($_GET['ld'] == "all") ? 'all' : (int)$_GET['ld'];

$sort_by = $_GET['sort'] ? (int)$_GET['sort'] : '1';

if(strlen($search_keyword) == "13") {
	global $ad_ids_length;
	$ad_id_prefix = parse_url(site_url());
	$ad_id_prefix = strtoupper(substr($ad_id_prefix['host'], 0, 3));
	if(substr($search_keyword, 0, 3) == $ad_id_prefix && is_numeric(substr($search_keyword, 3, $ad_ids_length))) {
		global $wpdb;
		$post_id = $wpdb->get_var("SELECT `post_id` FROM `".$wpdb->postmeta."` WHERE `meta_key` = 'ad_id' AND `meta_value` = ".substr($search_keyword, 3, $ad_ids_length)." LIMIT 1");
		if($post_id) {
			wp_redirect(get_permalink($post_id)); die();
		}
	}
}

if(is_numeric($_GET['ps'])) {
	$price_start = $_GET['ps'];
}
if(is_numeric($_GET['pe'])) {
	$price_end = $_GET['pe'];
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

get_header();

// global $sublanguage;
// pr($sublanguage->get_languages());
?>
<h1>Hola Soledad</h1>
<div class="items-loop">
	<div class="loop">
		<?php
		$form_builder_form_fields = get_option('form_builder_form_fields');
		$cat_id = is_search() ? $search_cat : $wp_query->queried_object->term_id;
		$cat_fields = $form_builder_form_fields['all'];

		$paged = get_query_var('paged') ? get_query_var('paged') : 1;
		$paged = $wp_query->query['page'] ? $wp_query->query['page'] : $paged;
		$args = array(
				'post_type' => $taxonomy_ad_url,
				'posts_per_page' => $ads_per_page,
				'paged' => $paged
			);

		// if category/taxonomy page
		if(in_array($wp_query->queried_object->taxonomy, array($taxonomy_ad_category_url, $taxonomy_location_url))) {
			$args['tax_query'][] = array('taxonomy' => $wp_query->queried_object->taxonomy, 'terms' => $wp_query->queried_object->term_id);
		}

		// filters
		if(is_array($url_filters)) {
			foreach ($url_filters as $key => $url_filter) {
				$args['meta_query']['relation'] = "AND";
				$url_filter = explode(":", $url_filter);
				if(is_array(explode(",", $url_filter[1]))) {
					$args['meta_query'][$url_filter[0]]['relation'] = "OR";
					foreach (explode(",", $url_filter[1]) as $url_filter_value) {
						$args['meta_query'][$url_filter[0]][] = array('key' => 'filter_field_name_'.$url_filter[0]."_".$url_filter_value, 'value' => '1', 'compare' => '=', 'type' => 'NUMERIC');
					}
				} else {
					$args['meta_query'][] = array('key' => 'filter_field_name_'.$url_filter[0]."_".$url_filter[1], 'value' => '1', 'compare' => '=', 'type' => 'NUMERIC');
				}
			}
		}

		// search keywords
		if($search_keyword) {
			$args['s']= sanitize_text_field($search_keyword);
		}

		// search category
		if($search_cat) {
			$args['tax_query'][] = array('taxonomy' => $taxonomy_ad_category_url, 'terms' => $search_cat);
		}
		
		if($search_location_term_data->term_id) {
			if($search_distance == "all") {
				$search_distance_sql = "999999";
				$args['tax_query'][] = array('taxonomy' => $taxonomy_location_url, 'terms' => $search_location_term_data->term_id);
			} else {
				$search_distance_sql = $search_distance;
			}
			$lat = get_tax_meta($search_location_term_data->term_id, 'gmaps_lat');
			$lng = get_tax_meta($search_location_term_data->term_id, 'gmaps_lng');
			// 3959 for miles
			$query = $wpdb->prepare("SELECT post_id, ( 6371 * acos( cos( radians('%s') ) * cos( radians( lat ) ) * cos( radians( lng ) - radians('%s') ) + sin( radians('%s') ) * sin( radians( lat ) ) ) ) AS distance FROM ".$wpdb->prefix."posts_location HAVING distance < '%s' ORDER BY distance", $lat, $lng, $lat, $search_distance_sql);
			$ad_rows = $wpdb->get_results($query);
			if(count($ad_rows) > 0) {
				foreach ($ad_rows as $key => $ad_row) {
					$ad_distance[$ad_row->post_id] = $ad_row->distance;
					$ad_id_list[] = $ad_row->post_id;
				}
				$args['post__in'] = $ad_id_list;
			} else {
				$args['post__in'] = array('0');
			}
		}

		if($_GET['aclatlng']) {
			$aclatlng = explode(",", $_GET['aclatlng']);
			if(count($aclatlng) == "2") {
				$lat = $aclatlng[0];
				$lng = $aclatlng[1];
				if(is_numeric($lat) && is_numeric($lng)) {
					if($search_distance == "all") {
						$search_distance_sql = "999999";
					} else {
						$search_distance_sql = $search_distance;
					}
					// 3959 for miles
					$query = $wpdb->prepare("SELECT post_id, ( 6371 * acos( cos( radians('%s') ) * cos( radians( lat ) ) * cos( radians( lng ) - radians('%s') ) + sin( radians('%s') ) * sin( radians( lat ) ) ) ) AS distance FROM ".$wpdb->prefix."posts_location HAVING distance < '%s' ORDER BY distance", $lat, $lng, $lat, $search_distance_sql);
					$ad_rows = $wpdb->get_results($query);
					if(count($ad_rows) > 0) {
						foreach ($ad_rows as $key => $ad_row) {
							$ad_distance[$ad_row->post_id] = $ad_row->distance;
							$ad_id_list[] = $ad_row->post_id;
						}
						$args['post__in'] = $ad_id_list;
						$sort_by = "5";
					} else {
						$args['post__in'] = array('0');
					}
				}
			}
		}

		if($price_start) {
			$args['meta_query'][] = array('key' => 'price_amount', 'value' => $price_start, 'compare' => '>=', 'type' => 'NUMERIC');
		}
		if($price_end) {
			$args['meta_query'][] = array('key' => 'price_amount', 'value' => $price_end, 'compare' => '<=', 'type' => 'NUMERIC');
		}

		if($_GET['acregistration'] || $_GET['acmileage']) {
			if($form_builder_form_fields[$cat_id]) {
				foreach ($form_builder_form_fields[$cat_id] as $key => $value) {
					if($value['input_purpose'] == "8") { // registration key
						$acregistration_filter_name = $value['input_name'];
					}
					if($value['input_purpose'] == "10") { // mileage key
						$acmileage_filter_name = $value['input_name'];
					}
					if($acregistration_filter_name && $acmileage_filter_name) break;
				}
			}
		}
		if($_GET['acregistration']) {
			$args['meta_query'][] = array('key' => $acregistration_filter_name, 'value' => (int)$_GET['acregistration'], 'compare' => '>=', 'type' => 'NUMERIC');
		}
		if($_GET['acmileage']) {
			$args['meta_query'][] = array('key' => $acmileage_filter_name, 'value' => (int)$_GET['acmileage'], 'compare' => '<=', 'type' => 'NUMERIC');
		}

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
			
			case '5': // distance closest
				$args['orderby'] = 'post__in';
				break;
			
			case '6': // distance desc
				$args['orderby'] = 'post__in';
				$args['post__in'] = array_reverse($args['post__in'], true);
				break;
		}

		// Featured ads args START
		$featured_args = $args;
		$featured_args['meta_query'][] = array('key' => 'always_on_top', 'value' => '1', 'compare' => '=', 'type' => 'NUMERIC');
		$featured_args['posts_per_page'] = '3';
		$featured_args['orderby'] = 'rand';
		unset($featured_args['order'], $featured_args['paged']);
		$featured = new WP_Query($featured_args);
		// Featured ads args END

		// Normal ads args START
		$args['meta_query'][] = array('key' => 'always_on_top', 'value' => '1', 'compare' => 'NOT EXISTS', 'type' => 'NUMERIC');
		$ads = new WP_Query($args);
		// Normal ads args END

		if($featured->have_posts() || $ads->have_posts()) { ?>
			<div class="loop-title-bar-container rad3">
				<div class="loop-title-bar rad5">
					<?php
					if(is_search()) {
						$title = _d('Search results',570);
					} elseif(get_the_id() == get_option('show_all_ads') || !$wp_query->queried_object->name) {
						$title = _d('Latest ads',571);
					} else {
						$title = '<span class="label">'._d('All ads in',572).' </span><span class="category">'.$wp_query->queried_object->name.'</span>';
					}
					?>
					<h3 class="l"><?=$title?></h3>
					<div class="sorting r">
						<?php
						if(is_tax()) {
							$link = $no_filter_link = get_term_link($wp_query->queried_object);
						}
						if(is_search()) {
							$link = $no_filter_link = home_url().'/?s='.$search_keyword.'&c='.$search_cat.'&l='.$search_location.'&ld='.$search_distance;
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
						<div class="fake-select fake-select-order-by rad25 no-selection r">
							<div class="first"><span class="text l"></span> <span class="icon icon-arrow-up hide"></span><span class="icon icon-arrow-down"></span></div>
							<div class="options rad5 shadow hide l">
								<a href="<?=$link_no_sort?>" data-value="1" class="option<?php if($sort_by == '1') { echo ' selected'; } ?>"><?=_d('Newest first',166)?></a>
								<a href="<?=$link?>2" data-value="2" class="option<?php if($sort_by == '2') { echo ' selected'; } ?>"><?=_d('Oldest first',167)?></a>
								<a href="<?=$link?>3" data-value="3" class="option<?php if($sort_by == '3') { echo ' selected'; } ?>"><?=_d('Price Low to High',168)?></a>
								<a href="<?=$link?>4" data-value="4" class="option<?php if($sort_by == '4') { echo ' selected'; } ?>"><?=_d('Price High to Low',169)?></a>
								<?php if($search_distance) { ?>
								<a href="<?=$link?>5" data-value="5" class="option<?php if($sort_by == '5') { echo ' selected'; } ?>"><?=_d('Closest first',573)?></a>
								<a href="<?=$link?>6" data-value="6" class="option<?php if($sort_by == '6') { echo ' selected'; } ?>"><?=_d('Closest last',574)?></a>
								<?php } ?>
							</div> <!-- options -->
							<input type="hidden" name="sorting_order_by" value="<?=$sort_by?>" />
						</div> <!-- fake-selector -->
						<div class="r"><?=_d('Order by',170)?>:</div>
					</div>
					<div class="clear"></div>
				</div> <!-- loop-title-bar -->
				<div class="clear"></div>
			</div> <!-- col-100 -->
			<?php
				if($form_builder_form_fields[$cat_id]) {
					$cat_fields = $form_builder_form_fields[$cat_id];
				} else {
					$parent = get_term($cat_id, $taxonomy_ad_category_url);
					while ($parent->parent > 0) {
						if($form_builder_form_fields[$parent->parent]) {
							$cat_fields = $form_builder_form_fields[$parent->parent];
							break;
						} else {
							$parent = get_term($parent->parent, $taxonomy_ad_category_url);
						}
					}
				}
				$url_format = get_option('permalink_structure') ? '?' : '&';
				$current_cat_url = get_term_link($wp_query->queried_object);

				if(count($filters) > 0) {
					echo '<div class="filters l">';
					echo '<a class="reset-filters rad3 l" href="'.$no_filter_link.'"><span class="icon icon-cancel"></span> <span class="name">'._d('Reset filters',958).'</span></a>';
					foreach ($filters as $key1 => $filter) {
						$filter_name = $cat_fields[$key1]['name'][$current_lang] ? $cat_fields[$key1]['name'][$current_lang] : $cat_fields[$key1]['name']['default'];
						$input_values = $cat_fields[$key1]['input_values'][$current_lang] ? $cat_fields[$key1]['input_values'][$current_lang] : $cat_fields[$key1]['input_values']['default'];
						foreach ($filter as $key2 => $value) {
							$filter_value[] = $input_values[$value - 1];
							$loop_filters[$cat_fields[$key1]['input_name']][] = $value;
						}
						$filter_value = implode(', ', $filter_value);
						if($filter_name && $filter_value) {
							echo '<div class="filter rad3 l"><span class="name">'.$filter_name.': </span><span class="value">'.$filter_value.'</span></div> <!-- filter -->';
						}
						unset($filter_value);
					}
					echo '</div> <!-- filters --> <div class="clear20"></div>';
				} // if(count($filters) > 0)
			?>
			<div class="clear10 hide-is-mobile"></div>
		<?php }

		if($featured->have_posts() || $ads->have_posts()) {
			echo '<div class="items-wrap">';
		}
		// Featured ads START
		if($featured->have_posts()) {
			while($featured->have_posts()) {
				$featured->the_post();
				set_query_var('distance', $ad_distance[get_the_ID()]);
				get_template_part('loop-items');
			}
		}
		// Featured ads END

		// Normal ads START
		if($ads->have_posts()) {
			while($ads->have_posts()) {
				$ads->the_post();
				set_query_var('distance', $ad_distance[get_the_ID()]);
				get_template_part('loop-items');
			}
		}
		// Normal ads END
		if($featured->have_posts() || $ads->have_posts()) {
			echo '</div> <!-- items-wrap -->';
		}

		if(!$featured->have_posts() && !$ads->have_posts()) {
			if($loop_filters) {
				echo '<div class="col-100 no-ads-message">'._d('We couldn\'t find any ads with these filters',575).'</div>';
			} else {
				if(is_search()) {
					echo '<div class="col-100 no-ads-message rad3">'._d('We couldn\'t find any ads',576).'</div>';
				} else {
					echo '<div class="col-100 no-ads-message">'._d('There are no ads here at the moment.',577).'<br />'._d('Would you like to be the first to post an ad here?',578).'</div>';
					echo '<div class="empty-category-postnew">';
					$postnew_url = $wp_query->queried_object->slug ? get_permalink(get_option('post_new_ad')).'#'.$wp_query->queried_object->slug : get_permalink(get_option('post_new_ad'));
					echo '<a href="'.$postnew_url.'" class="postnew-button rad3">';
						$payment_data = get_all_payment_data();
						if(get_option('payment_mode_active') && $payment_data['paid_ads'][$current_user_type]['first']['price']) {
							echo '<span class="text">'._d('Post ad for',784)." ".get_option('payment_currency_symbol_before').$payment_data['paid_ads'][$current_user_type]['first']['price'].get_option('payment_currency_symbol_after').'</span>';
						} else {
							echo '<span class="text">'._d('Post free ad',785).'</span>';
						}
					echo '<span class="icon icon-plus r"></span>';
					echo '</a>';
					echo '</div>';
				}
			}
		}

		echo '<div class="clear30 hide-is-mobile"></div>';
		$total = ceil($ads->found_posts / $ads_per_page);
		dolce_pagination($total, $paged);
		echo '<div class="clear"></div>';
		// Normal ads END

		wp_reset_postdata();
		?>
	</div> <!-- loop -->
</div> <!-- items-loop -->

<?php get_footer(); ?>