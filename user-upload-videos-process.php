<?php
ini_set( 'display_errors', 0 );
require( '../../../wp-load.php' );

define('DONOTCACHEPAGE',1);
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

$current_user = wp_get_current_user();

if (empty($_FILES)) {
	die(json_encode(array('status' => 'err', 'message' => 'There are no files to upload')));
} else {
	$post_id = (int)$_POST['post_id'];
	$session_code = preg_replace('/[^0-9a-fA-F:., ]/', "", (isset($_SERVER["HTTP_CF_CONNECTING_IP"]) ? $_SERVER["HTTP_CF_CONNECTING_IP"] : getenv('REMOTE_ADDR')));

	//check for any error in the upload process
	if($_FILES['Filedata']['error'] != '0') {
		$error_codes = array(
			    '1' => _d('The uploaded file exceeds the upload_max_filesize directive in php.ini',181),
			    '2' => _d('The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form',182),
			    '3' => _d('The uploaded file was only partially uploaded',183),
			    '4' => _d('No file was uploaded',184),
			    '6' => _d('Missing a temporary folder',185),
			    '7' => _d('Failed to write file to disk',186),
			    '8' => _d('A PHP extension stopped the file upload',187)
		   );
		die(json_encode(array('status' => 'err', 'message' => $error_codes[$_FILES['Filedata']['error']])));
	}

	if(!$post_id && !$session_code) {
		die(json_encode(array('status' => 'err', 'message' => _d('There was an error when uploading this video. Please refresh the page.',1022))));
	}

	//Check the total image upload count
	if($post_id) {	
		unset($session_code);
		$videos = get_children( array('post_parent' => $post_id, 'post_status' => 'inherit', 'post_type' => 'attachment', 'post_mime_type' => 'video') );
	} elseif($session_code) {
		unset($post_id);
		global $wpdb;
		$videos = $wpdb->get_results($wpdb->prepare("SELECT post_id FROM $wpdb->postmeta AS a JOIN $wpdb->posts AS b ON `a`.`post_id` = `b`.`ID` WHERE `a`.`meta_key` = 'session_code' AND `a`.`meta_value` = %s AND `b`.`post_mime_type` IN ('video/mp4', 'video/webm', 'video/ogg', 'video/quicktime')", $session_code));
	}
	if (count($videos) >= get_option('maximum_videos_to_upload')) {
		die(json_encode(array('status' => 'err', 'message' => _d('You have uploaded the maximum number of allowed videos',1023))));
	}

	//Get the Size of the File
	$file_size = $_FILES['Filedata']['size'];
	$max_vid_size = get_option('max_video_size') * 1048576;

	//Make sure that file size is correct
	if ($file_size > $max_vid_size){
		die(json_encode(array('status' => 'err', 'message' => _d('Your video is bigger than %d MB',1024,get_option('max_video_size')))));
	}
	if ($file_size == "0"){
		die(json_encode(array('status' => 'err', 'message' => _d('Video size error',1025))));
	}

	//check file extension
	$finfo = finfo_open(FILEINFO_MIME_TYPE);
	$video_mime_type = finfo_file($finfo, $_FILES['Filedata']['tmp_name']);
	finfo_close($finfo);
	$video_extension = str_replace("video/", "", $video_mime_type);
	$allowed_extensions = array("mp4","webm","ogv","mov");
	
	if ((!in_array($video_extension, $allowed_extensions))) {
		die(json_encode(array('status' => 'err', 'message' => _d('This file doesn\'t seem to be an video',1026))));
	}

	require_once( ABSPATH . 'wp-admin/includes/image.php' );
	require_once( ABSPATH . 'wp-admin/includes/file.php' );
	require_once( ABSPATH . 'wp-admin/includes/media.php' );
	
	// Let WordPress handle the upload.
	$post_data = array(
			'comment_status' => 'closed',
			'ping_status' => 'closed',
			'post_author' => $current_user->ID,
		);
	$attachment_id = media_handle_upload('Filedata', $post_id, $post_data);
	if ( is_wp_error( $attachment_id ) ) {
		// There was an error uploading the image.
		die(json_encode(array('status' => 'err', 'message' => 'Error uploading video')));
	} else {
		// The image was uploaded successfully!
		add_post_meta($attachment_id, 'session_code', $session_code);
		$video_th_url = wp_get_attachment_url($attachment_id);
		die(json_encode(array('status' => 'ok', 'attachment_id' => $attachment_id, 'attachment_url'=> $video_th_url, 'attachment_mime_type' => $video_mime_type)));
	}
}
?>