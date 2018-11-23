<?php
ini_set( 'display_errors', 0 );
require( '../../../../wp-load.php' );

define('DONOTCACHEPAGE',1);
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

if (!is_user_logged_in()) { die(); }

global $taxonomy_review_url, $review_char_limit;
$current_user = wp_get_current_user();

if($_POST['action'] == 'save_review') {
	$form_data = $_POST['form_data'];
	$err = $form = array();
	foreach ($form_data as $field) {
		$name = $field['name'];
		$value = $field['value'];

		switch ($name) {
			case 'seller_id':
					if((int)$value > 0 && get_userdata((int)$value)) {
						$form[$name] = (int)$value;
					}
				break;

			case 'delivery':
					if((int)$value > 0 && (int)$value <= 5) {
						$form[$name] = (int)$value;
					}
				break;

			case 'responsiveness':
					if((int)$value > 0 && (int)$value <= 5) {
						$form[$name] = (int)$value;
					}
				break;

			case 'friendliness':
					if((int)$value > 0 && (int)$value <= 5) {
						$form[$name] = (int)$value;
					}
				break;

			case 'review_text':
					$value = substr(strip_tags(wp_kses(trim($value), array())), 0, $review_char_limit);
					if(strlen($value) > 0) {
						$form[$name] = $value;
					}
				break;
		}
	}

	if(!$form['seller_id']) $err['seller_id'] = _d('No seller selected for the review. Try refreshing the page.',879);
	if(!$form['delivery']) $err['delivery'] = _d('Select your rating again',877);
	if(!$form['responsiveness']) $err['responsiveness'] = _d('Select your rating again',877);
	if(!$form['friendliness']) $err['friendliness'] = _d('Select your rating again',877);
	//if(!$form['review_text']) $err['review_text'] = _d('Please write a short review about the seller',878);

	if(count($err) > 0) {
		die(json_encode(array('status' => 'err', 'fields_err' => $err)));
	}

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
				'value' => $form['seller_id'],
				'compare' => '=',
				'type' => 'NUMERIC'
			)
		),
		'posts_per_page' => '1',
		'fields' => 'ids'
	);
	$user_has_review = new WP_Query($user_has_review_args);
	if($user_has_review->found_posts > 0) {
		die(json_encode(array('status' => 'err', 'form_err' => _d('You already posted a review for this seller',892))));
	}

	$seller_data = get_userdata($form['seller_id']);
	$review_args = array(
		'post_content'   => $form['review_text'], // The full text of the post
		'post_title'     => _d('Review for',880)." ".$seller_data->display_name, // The title of your post
		'post_status'    => "publish", // Post status
		'post_type'      => $taxonomy_review_url, // Taxonomy name
		'post_author'    => $current_user->id, // The user ID number of the author
		'ping_status'    => 'closed', // Pingbacks or trackbacks allowed
		'post_excerpt'   => '', // Post excerpt
		'comment_status' => 'closed' // If comments are open
	);
	$review_id = wp_insert_post($review_args);

	if($review_id > 0) {
		$review_rating = round(($form['delivery'] + $form['responsiveness'] + $form['friendliness']) / 3, 1);

		update_post_meta($review_id, 'review_for', $form['seller_id']);
		update_post_meta($review_id, 'review_from', $current_user->id);
		update_post_meta($review_id, 'delivery', $form['delivery']);
		update_post_meta($review_id, 'responsiveness', $form['responsiveness']);
		update_post_meta($review_id, 'friendliness', $form['friendliness']);
		update_post_meta($review_id, 'rating', $review_rating);

		$total_user_rating = calculate_user_review_rating($form['seller_id']);

		die(json_encode(array('status' => 'ok', 'rating' => $review_rating, 'user_rating' => $total_user_rating, 'review_id' => $review_id)));
	} else {
		die(json_encode(array('status' => 'err', 'form_err' => _d('We couldn\'t save the review. Please try again.',888))));
	}
}

if($_POST['action'] == 'update_review' || $_POST['action'] == 'update_review2') {
	$form_data = $_POST['form_data'];
	$err = $form = array();
	foreach ($form_data as $field) {
		$name = $field['name'];
		$value = $field['value'];

		switch ($name) {
			case 'review_id':
					if((int)$value > 0) $form[$name] = (int)$value;
				break;

			case 'delivery':
					if((int)$value > 0 && (int)$value <= 5) {
						$form[$name] = (int)$value;
					}
				break;

			case 'responsiveness':
					if((int)$value > 0 && (int)$value <= 5) {
						$form[$name] = (int)$value;
					}
				break;

			case 'friendliness':
					if((int)$value > 0 && (int)$value <= 5) {
						$form[$name] = (int)$value;
					}
				break;

			case 'review_text':
					$value = substr(strip_tags(wp_kses(trim($value), array())), 0, $review_char_limit);
					if(strlen($value) > 0) {
						$form[$name] = $value;
					}
				break;
		}
	}

	if(!$form['review_id']) $err['review_id'] = _d('There was an error when trying to update the review. Try refreshing the page.',879);
	if(!$form['delivery']) $err['delivery'] = _d('Select your rating again',877);
	if(!$form['responsiveness']) $err['responsiveness'] = _d('Select your rating again',877);
	if(!$form['friendliness']) $err['friendliness'] = _d('Select your rating again',877);
	//if(!$form['review_text']) $err['review_text'] = _d('Please write a short review about the seller',878);

	if(count($err) > 0) {
		die(json_encode(array('status' => 'err', 'fields_err' => $err)));
	}

	$review_data = get_post($form['review_id']);
	if(!$review_data) {
		die(json_encode(array('status' => 'err', 'form-err-msg' => _d('There was an error when trying to update the review. Try refreshing the page.',879))));
	}
	if($review_data->post_author != $current_user->ID && !current_user_can('level_10')) {
		die(json_encode(array('status' => 'err', 'form-err-msg' => _d('You are not allowed to edit this review',906))));
	}

	$review_args = array(
		'ID'     => $form['review_id'], // The id of the review
		'post_content'   => $form['review_text'], // The full text of the post
	);
	if(wp_update_post($review_args) > 0) {
		$review_rating = round(($form['delivery'] + $form['responsiveness'] + $form['friendliness']) / 3, 1);

		update_post_meta($form['review_id'], 'delivery', $form['delivery']);
		update_post_meta($form['review_id'], 'responsiveness', $form['responsiveness']);
		update_post_meta($form['review_id'], 'friendliness', $form['friendliness']);
		update_post_meta($form['review_id'], 'rating', $review_rating);

		$total_user_rating = calculate_user_review_rating($review_data->post_author);

		die(json_encode(array('status' => 'ok', 'rating' => $review_rating, 'user_rating' => $total_user_rating)));
	} else {
		die(json_encode(array('status' => 'err', 'form_err' => _d('We couldn\'t save the review. Please try again.',888))));
	}
}

if($_POST['action'] == 'reply_to_review') { // seller replies to review
	$review_text = substr(strip_tags(wp_kses(trim($_POST['review_text']), array())), 0, $review_char_limit);
	$review_id = (int)$_POST['review_id'];
	if(strlen($review_text) < 1 || $review_id < 1 || get_post_meta($review_id, 'review_for', true) != $current_user->ID) {
		die(json_encode(array('status' => 'err')));
	}

	$commentdata = array(
		'comment_post_ID' => $review_id,
		'comment_author' => $current_user->display_name,
		'comment_author_email' => $current_user->user_email,
		'comment_author_url' => '',
		'comment_content' => $review_text,
		'comment_type' => '',
		'comment_parent' => 0,
		'user_id' => $current_user->ID,
		'comment_author_IP' => preg_replace('/[^0-9a-fA-F:., ]/', '', $_SERVER['REMOTE_ADDR']),
		'comment_date' => current_time('mysql'),
		'comment_date_gmt' => current_time('mysql', 1),
		'comment_approved' => 1
	);
	$commentdata = apply_filters('preprocess_comment', $commentdata);
	$comment_id = wp_insert_comment($commentdata);
	if($comment_id > 0) {
		die(json_encode(array('status' => 'ok', 'review_id' => $comment_id)));
	} else {
		die(json_encode(array('status' => 'err', 'reason' => _d('We couldn\'t save the review. Please try again.',888))));
	}
}

if($_POST['action'] == 'update_seller_review') { // seller updates reply to review
	$review_text = substr(strip_tags(wp_kses(trim($_POST['review_text']), array())), 0, $review_char_limit);
	$review_id = (int)$_POST['review_id'];
	$review_id_data = get_comment($review_id);
	if(!$review_id_data || strlen($review_text) < 1 || $review_id < 1 || ($review_id_data->user_id != $current_user->ID && !current_user_can('level_10'))) {
		die(json_encode(array('status' => 'err')));
	}

	$commentdata = array(
		'comment_ID' => $review_id,
		'comment_content' => $review_text
	);
	$commentdata = apply_filters('preprocess_comment', $commentdata);
	$comment_id = wp_update_comment($commentdata);

	if($comment_id > 0) {
		die(json_encode(array('status' => 'ok')));
	} else {
		die(json_encode(array('status' => 'err', 'reason' => _d('We couldn\'t save the review. Please try again.',888))));
	}
}


if($_GET['action'] == 'delete_review') { // buyer deletes their review
	$review_id = (int)$_GET['review_id'];
	$review_data = get_post($review_id);
	calculate_user_review_rating(get_post_meta($review_id, 'review_for', true));
	if($review_data->post_author == $current_user->ID || current_user_can('level_10')) { wp_delete_post($review_id); }
	die('ok');
}

if($_GET['action'] == 'delete_reply') { // seller deletes their review reply
	$review_id = (int)$_GET['review_id'];
	$review_id_data = get_comment($review_id);
	if($review_id_data->user_id == $current_user->ID || current_user_can('level_10')) { wp_delete_comment($review_id); }
	die('ok');
}
?>