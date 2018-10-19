<?php
if(!defined('error_reporting')) { define('error_reporting', '0'); }
ini_set( 'display_errors', error_reporting );
if(error_reporting == '1') { error_reporting( E_ALL ); }
if(isdolcetheme !== 1) { die(); }

define('DONOTCACHEPAGE',1);

if($post_id_to_edit) {
	$photos = $wpdb->get_results($wpdb->prepare("SELECT ID FROM $wpdb->posts WHERE `post_parent` = %s AND `post_type` = 'attachment' AND `post_mime_type` IN ('image/png', 'image/jpg', 'image/jpeg', 'image/gif')", $post_id_to_edit));
	$videos = $wpdb->get_results($wpdb->prepare("SELECT ID FROM $wpdb->posts WHERE `post_parent` = %s AND `post_type` = 'attachment' AND `post_mime_type` IN ('video/mp4', 'video/webm', 'video/ogg', 'video/quicktime')", $post_id_to_edit));
} else {
	$session_code = preg_replace('/[^0-9a-fA-F:., ]/', "", (isset($_SERVER["HTTP_CF_CONNECTING_IP"]) ? $_SERVER["HTTP_CF_CONNECTING_IP"] : getenv('REMOTE_ADDR')));
	$photos = $wpdb->get_results($wpdb->prepare("SELECT post_id FROM $wpdb->postmeta AS a JOIN $wpdb->posts AS b ON `a`.`post_id` = `b`.`ID` WHERE `a`.`meta_key` = 'session_code' AND `a`.`meta_value` = %s AND `b`.`post_mime_type` IN ('image/png', 'image/jpg', 'image/jpeg', 'image/gif')", $session_code));
	$videos = $wpdb->get_results($wpdb->prepare("SELECT post_id FROM $wpdb->postmeta AS a JOIN $wpdb->posts AS b ON `a`.`post_id` = `b`.`ID` WHERE `a`.`meta_key` = 'session_code' AND `a`.`meta_value` = %s AND `b`.`post_mime_type` IN ('video/mp4', 'video/webm', 'video/ogg', 'video/quicktime')", $session_code));
}
$photos_left = get_option('maximum_images_to_upload') - count($photos);
$videos_left = get_option('maximum_videos_to_upload') - count($videos);
?>
<form action="" method="post" class="post-form form-styling" id="post-form" data-post-id="<?=$post_id_to_edit?>">
	<section class="fields">
		<div class="formerr-msg err hide rad3"></div>
		<div class="selected-category<?php if(!$category_id && !$post_id_to_edit) { echo " hide"; } ?>">
			<div class="form-label">
				<label class="label"><?=_d('Category',364)?></label>
			</div> <!-- form-label -->
			<div class="form-input">
				<?php if($category_id && $post_id_to_edit) { // if this form is loaded in the "edit ad section" ?>
				<div class="fake-select fake-select-category-chooser rad3 no-selection l" data-original-cat="<?=$category_id?>">
					<div class="first"><span class="text l"></span> <span class="icon icon-arrow-up hide"></span><span class="icon icon-arrow-down"></span></div>
					<div class="options rad5 hide">
						<div data-value="all" class="option<?php if($category_id == '0') { echo ' selected'; } ?>"><?=_d('All categories',157)?></div>
						<?php
						global $taxonomy_ad_category_url;
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
						$all_cats = get_categories(array('taxonomy' => $taxonomy_ad_category_url, 'hide_empty' => 0));
						if(count($all_cats) > 0) {
							foreach($all_cats as $key => $cat) {
								if($cat->category_parent == '0') {
									$main_cats[] = $cat;
									unset($all_cats[$key]);
								}
							}//foreach $all_cats as $cat

							function show_cats_dropdowns($all_cats, $main_cats) {
								$auto_class_cats = get_option('auto_class_cats');
								foreach ($main_cats as $key => $cat) {
									$selected = ($cat->term_id == $category_id) ? " selected" : "";
									echo '<div data-value="'.$cat->term_id.'" class="option'.$selected.'" data-cat-parent="'.$cat->category_parent.'"><span class="icon icon-level-down"></span> '.$cat->name.'</div>';

									foreach($all_cats as $key => $subcat) {
										if($subcat->category_parent == $cat->term_id && !in_array($subcat->category_parent, $auto_class_cats)) {
											$sub_cats[] = $subcat;
											unset($all_cats[$key]);
										}
									}//foreach $c as $cat
									echo '<div class="sub-cat" data-subcats-for-cat="'.$cat->term_id.'">';
										if(count($sub_cats) > 0) {
											show_cats_dropdowns($all_cats, $sub_cats);
											unset($sub_cats);
										}
									echo '</div>';
								}
							}//function show_cats

							show_cats_dropdowns($all_cats, $main_cats);
							unset($all_cats, $main_cats);
						}//if count($c) > 0
						?>
					</div> <!-- options -->
					<input type="hidden" name="cat_id" value="<?=$category_id?>" />
				</div> <!-- fake-selector -->
				<?php } else { // if this form is loaded in the "post new ad" section ?>
					<span class="selected-category-bold"></span><span class="change-category"><span class="icon icon-edit"></span> <u><?=_d('Change category',479)?></u></span>
					<input type="hidden" name="cat_id" id="cat_id" value="" />
				<?php } ?>
			</div> <!-- form-input --> <div class="formseparator"></div>
		</div> <!-- selected-category -->

		<div class="spinner-loader text-center hide"><img class="loader" src="<?=get_template_directory_uri()?>/plugins/form-builder/loader.svg" alt="" /></div>
		<div class="generated-form-fields"></div> <!-- generated-form-fields -->
	</section> <!-- fields -->

	<section class="images">
		<div class="clear20"></div>

		<script type="text/javascript">
			jQuery(document).ready(function($) {
				function formatFileSize(bytes) {
					if (typeof bytes !== 'number') { return ''; }
					if (bytes >= 1000000) { return (bytes / 1000000).toFixed(2) + ' MB'; }
					return (bytes / 1000).toFixed(2) + ' KB';
				}

				$('#upload_images').uploadifive({
					//options
					'auto'           : true,
					'dnd'            : true,
					'dropTarget'     : '.drag-images-overlay',
					'fileSizeLimit'  : '<?=get_option('max_image_size')?>MB',
					'fileType'       : ['image/png', 'image/jpg', 'image/jpeg', 'image/gif'],
					'formData'       : { 'post_id' : '<?=$post_id_to_edit?>', 'session_code' : '<?=$session_code?>' },
					'multi'          : true,
					'queueID'        : 'uploaded-images-queue',
					'queueSizeLimit' : '<?=get_option('maximum_images_to_upload')?>',
					'removeCompleted': true,
					'simUploadLimit' : '<?php if($photos_left > 10) { echo "10"; } else { echo $photos_left; } ?>',
					'uploadLimit'    : '20',
					'uploadScript'   : '<?=get_template_directory_uri()?>/user-upload-images-process.php',
					'overrideEvents' : ['onError'],
					//events
					'onAddQueueItem' : function(file) {
						$('.post-form .drag-images-overlay').hide();
						$('.post-form .uploaded-images-queue').append('<div id="'+file.name.replace(/[^a-z0-9]/gi,'')+'" class="one-img rad5"><div class="percentage l"></div><div class="close vcenter r"><span class="icon icon-cancel"></span></div><div class="name"><svg class="loader" height="20" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 50 50" style="enable-background:new 0 0 50 50;" xml:space="preserve"><path fill="#22a4e6" d="M43.935,25.145c0-10.318-8.364-18.683-18.683-18.683c-10.318,0-18.683,8.365-18.683,18.683h4.068c0-8.071,6.543-14.615,14.615-14.615c8.072,0,14.615,6.543,14.615,14.615H43.935z"><animateTransform attributeType="xml" attributeName="transform" type="rotate" from="0 25 25" to="360 25 25" dur="0.6s" repeatCount="indefinite" /></path></svg>'+file.name.replace(/[^a-z0-9\. _-]/gi,'')+'</div><div class="size">'+formatFileSize(file.size)+'</div><div class="message hide"></div></div>');
						if($('.post-form .photo-upload .images-have-been-uploaded .max-img').text() > "0") {
							var images_left = parseInt($('.post-form .photo-upload .no-images-uploaded .max-img').text()) - 1;
							$('.post-form .photo-upload .no-images-uploaded .max-img, .post-form .photo-upload .images-have-been-uploaded .max-img').text(images_left);
					        $('#upload_images').data('uploadifive').settings.queueSizeLimit = images_left;
					        $('.post-form .no-images-uploaded').hide();
					        $('.post-form .images-have-been-uploaded').show();
						}

						var form = $('.post-form');
						form.find('.submit-form').addClass('button-working');
						form.find('.err-msg, .form-msg').slideUp(200, function(){ $(this).hide().text(''); });
						form.find('.input-err').removeClass('input-err');
						form.find('.submit-form .text').text(form.find('.submit-form').data('saving'));
						form.find('.submit-form .icon').hide();
						form.find('.submit-form .icon-for-saving').show();
					},
					'onProgress'     : function (file, e) {
						if (e.lengthComputable) {
							var percent = Math.round((e.loaded / e.total) * 100);
							if(percent == "100") percent = "98";
							$('.post-form .uploaded-images-queue #'+file.name.replace(/[^a-z0-9]/gi,'')+' .percentage').text(percent+'%');
						}
					},
					'onUploadComplete' : function(file, raw_data) {
						var is_json = true;
						var err_msg;
						try {
							var data = JSON.parse(raw_data);
						} catch(err) {
							is_json = false;
						}
						if(is_json) {
							if(data.status == "ok") {
								//add the new image in the image preview
								$('<div class="one-img" data-attachment-id="'+data.attachment_id+'"><div class="main-image rad3 hide"><span class="icon icon-star"></span> <?=addslashes(_d('Main image',480))?></div><img src="'+data.attachment_url+'" class="preview-img rad3" alt="" /><div class="clear5"></div><div class="rotate rotate-left l rad50" title="<?=_d('Rotate',957)?>"><span class="icon icon-rotate"></span></div><div class="rotate rotate-right r rad50" title="<?=_d('Rotate',957)?>"><span class="icon icon-update"></span></div><div class="remove rad17"><span class="icon icon-delete"></span> <?=addslashes(_d('Remove',469))?></div></div>').hide().insertAfter('.post-form .uploaded-images .uploaded-images-message').fadeIn("slow");

								if($('.post-form .uploaded-images .one-img').length >= "1" && !$('.post-form input[name="main_image"]').val()) {
									$('.post-form .uploaded-images .one-img[data-attachment-id="'+data.attachment_id+'"]').find('.main-image').show().parent().find('.mark-as-main').hide();
									$('.post-form .uploaded-images .uploaded-images-message').slideDown('fast');
									$('.post-form input[name="main_image"]').val(data.attachment_id);

									var post_id = $('.post-form').data('post-id');
									if(post_id) {
										$.get(wpvars.wpthemeurl+'/ajax/mark-as-main-image.php?post_id=' + post_id + '&attachment_id=' + data.attachment_id);
									}
								}

								$('.post-form .uploaded-images-queue #'+file.name.replace(/[^a-z0-9]/gi,'')).remove();
							} else {
								err_msg = data.message;
							}
						} else {
							err_msg = "<?=_d('Error uploading image',191)?>";
						}

						if(err_msg) {
							$('.post-form .uploaded-images-queue #'+file.name.replace(/[^a-z0-9]/gi,'')+' .percentage').hide().parent().find('.message').text(err_msg).show();
							$('.post-form .uploaded-images-queue #'+file.name.replace(/[^a-z0-9]/gi,'')+' .loader').hide();
							var images_left = parseInt($('.post-form .photo-upload .no-images-uploaded .max-img').text()) + 1;
							$('.post-form .photo-upload .no-images-uploaded .max-img, .post-form .photo-upload .images-have-been-uploaded .max-img').text(images_left);
							$('#upload_images').data('uploadifive').settings.queueSizeLimit = images_left;
						}

						var form = $('.post-form');
						if(!form.find('.one-img .percentage:visible').length) {
							form.find('.submit-form .text').text(form.find('.submit-form').data('default'));
							form.find('.submit-form .icon').hide();
							form.find('.submit-form .icon-for-default').show();
							form.find('.submit-form').removeClass('button-working');
						}
					},
					'onError'        : function(errorType, file) {
						if(errorType == "QUEUE_LIMIT_EXCEEDED") {
							swal({
								title: "<?=_d('Too many images!',481)?>",
								text: "<?=_d('You can only upload',482)?> "+parseInt($('.post-form .photo-upload .no-images-uploaded .max-img').text())+" <?=_d('more images',483)?>.",
								type: "error",
								allowOutsideClick: 'true',
								confirmButtonColor: '#22a4e6',
							});
						} else if(errorType == "UPLOAD_LIMIT_EXCEEDED") {
							swal({
								title: "<?=_d('Too many images!',481)?>",
								text: "<?=_d('Sorry, but you uploaded the maximum number of allowed images.',484)?>",
								type: "error",
								allowOutsideClick: 'true',
								confirmButtonColor: '#22a4e6',
							});
						} else if(errorType == "FILE_SIZE_LIMIT_EXCEEDED") {
							$('.post-form .uploaded-images-queue #'+file.name.replace(/[^a-z0-9]/gi,'')+' .percentage').hide().parent().find('.message').text('<?=_d('Your image is bigger than',485)?> <?=get_option('max_image_size')?> MB').show();
							$('.post-form .uploaded-images-queue #'+file.name.replace(/[^a-z0-9]/gi,'')+' .loader').hide();
							var images_left = parseInt($('.post-form .photo-upload .no-images-uploaded .max-img').text()) + 1;
							$('.post-form .photo-upload .no-images-uploaded .max-img, .post-form .photo-upload .images-have-been-uploaded .max-img').text(images_left);
							$('#upload_images').data('uploadifive').settings.queueSizeLimit = images_left;
						} else if(errorType == "FORBIDDEN_FILE_TYPE") {
							$('.post-form .uploaded-images-queue #'+file.name.replace(/[^a-z0-9]/gi,'')+' .percentage').hide().parent().find('.message').text("<?=_d('This file doesn\'t seem to be an image',190)?>").show();
							$('.post-form .uploaded-images-queue #'+file.name.replace(/[^a-z0-9]/gi,'')+' .loader').hide();
							var images_left = parseInt($('.post-form .photo-upload .no-images-uploaded .max-img').text()) + 1;
							$('.post-form .photo-upload .no-images-uploaded .max-img, .post-form .photo-upload .images-have-been-uploaded .max-img').text(images_left);
							$('#upload_images').data('uploadifive').settings.queueSizeLimit = images_left;
						} else if(errorType == "404_FILE_NOT_FOUND") {
						} else {
							$('.post-form .uploaded-images-queue #'+file.name.replace(/[^a-z0-9]/gi,'')+' .percentage').hide().parent().find('.message').text(errorType).show();
							$('.post-form .uploaded-images-queue #'+file.name.replace(/[^a-z0-9]/gi,'')+' .loader').hide();
							var images_left = parseInt($('.post-form .photo-upload .no-images-uploaded .max-img').text()) + 1;
							$('.post-form .photo-upload .no-images-uploaded .max-img, .post-form .photo-upload .images-have-been-uploaded .max-img').text(images_left);
							$('#upload_images').data('uploadifive').settings.queueSizeLimit = images_left;							
						}

						var form = $('.post-form');
						if(!form.find('.one-img .percentage:visible').length) {
							form.find('.submit-form .text').text(form.find('.submit-form').data('default'));
							form.find('.submit-form .icon').hide();
							form.find('.submit-form .icon-for-default').show();
							form.find('.submit-form').removeClass('button-working');
						}
					},
					'onQueueComplete': function(data) {
						var form = $('.post-form');
						form.find('.submit-form .text').text(form.find('.submit-form').data('default'));
						form.find('.submit-form .icon').hide();
						form.find('.submit-form .icon-for-default').show();
						form.find('.submit-form').removeClass('button-working');
					}
				});

				$('.uploaded-images').on('click', '.rotate', function(event) {
					if($(this).parents('.one-img').find('.rotate').hasClass('processing')) {
						return false;
					} else {
						$(this).addClass('processing');
					}
					var angle = 0;

					var div = $(this);
					var img = div.parents('.one-img').find('.preview-img');
					var img_id = div.parents('.one-img').data('attachment-id');
					var degrees, rotation;
					if(div.hasClass('rotate-left')) {
						setInterval(function(){ angle -= 20; if(div.hasClass("processing")) { div.rotate(angle); } }, 50);
						degrees = parseInt(img.getRotateAngle()) - 90;
						rotation = "left";
					} else {
						setInterval(function(){ angle += 20; if(div.hasClass("processing")) { div.rotate(angle); } }, 50);
						degrees = parseInt(img.getRotateAngle()) + 90;
						rotation = "right";
					}
					img.addClass('rad50');
					img.animate({opacity: 0.5}, 500);
					img.rotate({angle: img.getRotateAngle(), animateTo: degrees, duration: 2500 });

					$.ajax({
						type: "GET",
						url: wpvars.wpthemeurl+'/user-upload-images-process.php',
						data: { img_id: img_id, rotation: rotation },
						cache: false,
						timeout: 20000, // in milliseconds
						success: function(data) {
							div.removeClass('processing').attr('style', '');
							img.animate({opacity: 1}, 250, function() {
								$(this).removeClass('rad50');
							});
						},
						error: function(request, status, err) {
							div.removeClass('processing').attr('style', '');
							img.animate({opacity: 1}, 250, function() {
								$(this).removeClass('rad50');
							});
						}
					});

				});
			});
		</script>
		<div class="upload-button-container hide"><input type="file" name="files" id="upload_images" multiple /></div>
		<div class="photo-upload rad17 text-center" data-error-title="<?=_d('Too many images!',481)?>" data-error-text="<?=_d('Sorry, but you uploaded the maximum number of allowed images',487)?>.">
			<span class="icon icon-pictures l vcenters"></span>
			<?=_d('Drag your images here to upload them or',488)?> <span class="manually-select-images"><u><?=_d('Select from a folder',489)?></u></span><br />
			<?php
			if($photos_left < get_option('maximum_images_to_upload')) {
				$first_message_class = ' hide';
				$second_message_class = '';
			} else {
				$first_message_class = '';
				$second_message_class = ' hide';
			}
			?>
			<span class="no-images-uploaded<?=$first_message_class?>"><?=_d('You can upload a maximum of %s images',490,'<span class="max-img rad5">'.$photos_left.'</span>')?></span>
			<span class="images-have-been-uploaded<?=$second_message_class?>"><?=_d('You have %s images left',491,'<span class="max-img rad5">'.$photos_left.'</span>')?></span>
			<div class="clear"></div>
		</div>
		<div class="drag-images-overlay text-center hide"><div class="vcenter"><span class="icon icon-pictures"></span> <?=_d('Drop your images here',492)?></div></div>

		<div id="uploaded-images-queue" class="hide"></div>
		<div class="uploaded-images-queue"></div> <!-- uploaded-images -->
		<div class="clear"></div>

		<div class="uploaded-images">
			<input type="hidden" name="main_image" value="<?=get_post_thumbnail_id($post_id_to_edit)?>" />
			<?php
			if(count($photos) > 0) {
				$show_main_image_message = true;
			}
			?>
			<div class="uploaded-images-message text-center<?php if(!$show_main_image_message) echo ' hide'; ?>"><?=_d('Click an image to select it as the main image for the ad',493)?><div class="clear5"></div></div>
			<?php
			foreach($photos as $key => $photo) {
				$attachment_id = $photo->post_id ? $photo->post_id : $photo->ID;
				$image_th_url = wp_get_attachment_image_src($attachment_id, 'upload-preview');
				echo '<div class="one-img" data-attachment-id="'.$attachment_id.'"><div class="main-image rad3 hide"><span class="icon icon-star"></span> '._d('Main image',480).'</div><img src="'.$image_th_url[0].'" class="preview-img rad3" alt="" /><div class="clear5"></div><div class="rotate rotate-left l rad50" title="'._d('Rotate',957).'"><span class="icon icon-rotate"></span></div><div class="rotate rotate-right r rad50" title="'._d('Rotate',957).'"><span class="icon icon-update"></span></div><div class="remove rad17"><span class="icon icon-delete"></span> '._d('Remove',469).'</div></div>';
			}
			?>
		</div> <!-- uploaded-images -->
	</section> <!-- images -->

	<!--- --------------------AGREGAR VIDEOS-------------------------- ------>
	<div>
		<h1>Prueba Child</h1>
	</div>

	<section class="videos">
		<div class="clear20"></div>

		<script type="text/javascript">
			jQuery(document).ready(function($) {
				function formatFileSize(bytes) {
					if (typeof bytes !== 'number') { return ''; }
					if (bytes >= 1000000) { return (bytes / 1000000).toFixed(2) + ' MB'; }
					return (bytes / 1000).toFixed(2) + ' KB';
				}

				$('#upload_videos').uploadifive({
					//options
					'auto'           : true,
					'dnd'            : true,
					'dropTarget'     : '.drag-videos-overlay',
					'fileSizeLimit'  : '<?=get_option('max_video_size')?>MB',
					'fileType'       : ['video/mp4', 'video/webm', 'video/ogg', 'video/quicktime'],
					'formData'       : { 'post_id' : '<?=$post_id_to_edit?>', 'session_code' : '<?=$session_code?>' },
					'multi'          : true,
					'queueID'        : 'uploaded-videos-queue',
					'queueSizeLimit' : '<?=get_option('maximum_videos_to_upload')?>',
					'removeCompleted': true,
					'simUploadLimit' : '<?php if($videos_left > 1) { echo "1"; } else { echo $videos_left; } ?>',
					'uploadLimit'    : '2',
					'uploadScript'   : '<?=get_stylesheet_directory_uri()?>/user-upload-videos-process.php',
					'overrideEvents' : ['onError'],
					//events
					'onAddQueueItem' : function(file) {
						$('.post-form .drag-videos-overlay').hide();
						$('.post-form .uploaded-videos-queue').append('<div id="'+file.name.replace(/[^a-z0-9]/gi,'')+'" class="one-vid rad5"><div class="percentage l"></div><div class="close vcenter r"><span class="icon icon-cancel"></span></div><div class="name"><svg class="loader" height="20" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 50 50" style="enable-background:new 0 0 50 50;" xml:space="preserve"><path fill="#22a4e6" d="M43.935,25.145c0-10.318-8.364-18.683-18.683-18.683c-10.318,0-18.683,8.365-18.683,18.683h4.068c0-8.071,6.543-14.615,14.615-14.615c8.072,0,14.615,6.543,14.615,14.615H43.935z"><animateTransform attributeType="xml" attributeName="transform" type="rotate" from="0 25 25" to="360 25 25" dur="0.6s" repeatCount="indefinite" /></path></svg>'+file.name.replace(/[^a-z0-9\. _-]/gi,'')+'</div><div class="size">'+formatFileSize(file.size)+'</div><div class="message hide"></div></div>');
						if($('.post-form .video-upload .videos-have-been-uploaded .max-vid').text() > "0") {
							var videos_left = parseInt($('.post-form .video-upload .no-videos-uploaded .max-vid').text()) - 1;
							$('.post-form .video-upload .no-videos-uploaded .max-vid, .post-form .video-upload .videos-have-been-uploaded .max-vid').text(videos_left);
					        $('#upload_videos').data('uploadifive').settings.queueSizeLimit = videos_left;
					        $('.post-form .no-videos-uploaded').hide();
					        $('.post-form .videos-have-been-uploaded').show();
						}

						var form = $('.post-form');
						form.find('.submit-form').addClass('button-working');
						form.find('.err-msg, .form-msg').slideUp(200, function(){ $(this).hide().text(''); });
						form.find('.input-err').removeClass('input-err');
						form.find('.submit-form .text').text(form.find('.submit-form').data('saving'));
						form.find('.submit-form .icon').hide();
						form.find('.submit-form .icon-for-saving').show();
					},
					'onProgress'     : function (file, e) {
						if (e.lengthComputable) {
							var percent = Math.round((e.loaded / e.total) * 100);
							if(percent == "100") percent = "98";
							$('.post-form .uploaded-videos-queue #'+file.name.replace(/[^a-z0-9]/gi,'')+' .percentage').text(percent+'%');
						}
					},
					'onUploadComplete' : function(file, raw_data) {
						var is_json = true;
						var err_msg;
						try {
							var data = JSON.parse(raw_data);
						} catch(err) {
							is_json = false;
						}
						if(is_json) {
							if(data.status == "ok") {
								//add the new image in the image preview
								$('<div class="one-vid" data-attachment-id="'+data.attachment_id+'"><video controls class="preview-vid rad3"><source src="'+data.attachment_url+'" type="'+data.attachment_mime_type+'" /></video><div class="clear5"></div><div class="remove rad17"><span class="icon icon-delete"></span> '._d('Remove',469).'</div></div>').hide().insertAfter('.post-form .uploaded-videos .uploaded-videos-message').fadeIn("slow");

								if($('.post-form .uploaded-videos .one-vid').length >= "1" && !$('.post-form input[name="main_video"]').val()) {
									$('.post-form .uploaded-videos .one-vid[data-attachment-id="'+data.attachment_id+'"]').find('.main-video').show().parent().find('.mark-as-main').hide();
									$('.post-form .uploaded-videos .uploaded-videos-message').slideDown('fast');
									$('.post-form input[name="main_video"]').val(data.attachment_id);

									var post_id = $('.post-form').data('post-id');
									if(post_id) {
										$.get(wpvars.wpthemeurl+'/ajax/mark-as-main-image.php?post_id=' + post_id + '&attachment_id=' + data.attachment_id);
									}
								}

								$('.post-form .uploaded-videos-queue #'+file.name.replace(/[^a-z0-9]/gi,'')).remove();
							} else {
								err_msg = data.message;
							}
						} else {
							err_msg = "<?=_d('Error uploading video',1021)?>";
						}

						if(err_msg) {
							$('.post-form .uploaded-videos-queue #'+file.name.replace(/[^a-z0-9]/gi,'')+' .percentage').hide().parent().find('.message').text(err_msg).show();
							$('.post-form .uploaded-videos-queue #'+file.name.replace(/[^a-z0-9]/gi,'')+' .loader').hide();
							var videos_left = parseInt($('.post-form .video-upload .no-videos-uploaded .max-vid').text()) + 1;
							$('.post-form .video-upload .no-videos-uploaded .max-vid, .post-form .video-upload .videos-have-been-uploaded .max-vid').text(videos_left);
							$('#upload_videos').data('uploadifive').settings.queueSizeLimit = videos_left;
						}

						var form = $('.post-form');
						if(!form.find('.one-vid .percentage:visible').length) {
							form.find('.submit-form .text').text(form.find('.submit-form').data('default'));
							form.find('.submit-form .icon').hide();
							form.find('.submit-form .icon-for-default').show();
							form.find('.submit-form').removeClass('button-working');
						}
					},
					'onError'        : function(errorType, file) {
						if(errorType == "QUEUE_LIMIT_EXCEEDED") {
							swal({
								title: "<?=_d('Too many videos!',1015)?>",
								text: "<?=_d('You can only upload',482)?> "+parseInt($('.post-form .video-upload .no-videos-uploaded .max-vid').text())+" <?=_d('more videos',1027)?>.",
								type: "error",
								allowOutsideClick: 'true',
								confirmButtonColor: '#22a4e6',
							});
						} else if(errorType == "UPLOAD_LIMIT_EXCEEDED") {
							swal({
								title: "<?=_d('Too many images!',1015)?>",
								text: "<?=_d('Sorry, but you uploaded the maximum number of allowed videos.',1014)?>",
								type: "error",
								allowOutsideClick: 'true',
								confirmButtonColor: '#22a4e6',
							});
						} else if(errorType == "FILE_SIZE_LIMIT_EXCEEDED") {
							$('.post-form .uploaded-videos-queue #'+file.name.replace(/[^a-z0-9]/gi,'')+' .percentage').hide().parent().find('.message').text('<?=_d('Your video is bigger than',1028)?> <?=get_option('max_video_size')?> MB').show();
							$('.post-form .uploaded-videos-queue #'+file.name.replace(/[^a-z0-9]/gi,'')+' .loader').hide();
							var videos_left = parseInt($('.post-form .video-upload .no-videos-uploaded .max-vid').text()) + 1;
							$('.post-form .video-upload .no-videos-uploaded .max-vid, .post-form .video-upload .videos-have-been-uploaded .max-vid').text(videos_left);
							$('#upload_videos').data('uploadifive').settings.queueSizeLimit = videos_left;
						} else if(errorType == "FORBIDDEN_FILE_TYPE") {
							$('.post-form .uploaded-videos-queue #'+file.name.replace(/[^a-z0-9]/gi,'')+' .percentage').hide().parent().find('.message').text("<?=_d('This file doesn\'t seem to be an video',1026)?>").show();
							$('.post-form .uploaded-videos-queue #'+file.name.replace(/[^a-z0-9]/gi,'')+' .loader').hide();
							var videos_left = parseInt($('.post-form .video-upload .no-videos-uploaded .max-vid').text()) + 1;
							$('.post-form .video-upload .no-videos-uploaded .max-vid, .post-form .video-upload .videos-have-been-uploaded .max-vid').text(videos_left);
							$('#upload_videos').data('uploadifive').settings.queueSizeLimit = videos_left;
						} else if(errorType == "404_FILE_NOT_FOUND") {
						} else {
							$('.post-form .uploaded-videos-queue #'+file.name.replace(/[^a-z0-9]/gi,'')+' .percentage').hide().parent().find('.message').text(errorType).show();
							$('.post-form .uploaded-videos-queue #'+file.name.replace(/[^a-z0-9]/gi,'')+' .loader').hide();
							var videos_left = parseInt($('.post-form .video-upload .no-videos-uploaded .max-vid').text()) + 1;
							$('.post-form .video-upload .no-videos-uploaded .max-vid, .post-form .video-upload .videos-have-been-uploaded .max-vid').text(videos_left);
							$('#upload_videos').data('uploadifive').settings.queueSizeLimit = videos_left;							
						}

						var form = $('.post-form');
						if(!form.find('.one-img .percentage:visible').length) {
							form.find('.submit-form .text').text(form.find('.submit-form').data('default'));
							form.find('.submit-form .icon').hide();
							form.find('.submit-form .icon-for-default').show();
							form.find('.submit-form').removeClass('button-working');
						}
					},
					'onQueueComplete': function(data) {
						var form = $('.post-form');
						form.find('.submit-form .text').text(form.find('.submit-form').data('default'));
						form.find('.submit-form .icon').hide();
						form.find('.submit-form .icon-for-default').show();
						form.find('.submit-form').removeClass('button-working');
					}
				});
			});
		</script>
		<div class="upload-video-button-container hide"><input type="file" name="files" id="upload_videos" multiple /></div>
		<div class="video-upload rad17 text-center" data-error-title="<?=_d('Too many videos!',1015)?>" data-error-text="<?=_d('Sorry, but you uploaded the maximum number of allowed videos',1014)?>.">
			<span class="child-icon child-icon-video l vcenters"></span>
			<?=_d('Drag your videos here to upload them or',1013)?> <span class="manually-select-videos"><u><?=_d('Select from a folder',489)?></u></span><br />
			<?php
			if($videos_left < get_option('maximum_videos_to_upload')) {
				$first_message_class = ' hide';
				$second_message_class = '';
			} else {
				$first_message_class = '';
				$second_message_class = ' hide';
			}
			?>
			<span class="no-videos-uploaded<?=$first_message_class?>"><?=_d('You can upload a maximum of %s videos',1016,'<span class="max-vid rad5">'.$videos_left.'</span>')?></span>
			<span class="videos-have-been-uploaded<?=$second_message_class?>"><?=_d('You have %s videos left',1017,'<span class="max-img rad5">'.$videos_left.'</span>')?></span>
			<div class="clear"></div>
		</div>
		<div class="drag-videos-overlay text-center hide"><div class="vcenter"><span class="child-icon child-icon-video"></span> <?=_d('Drop your videos here',1018)?></div></div>

		<div id="uploaded-videos-queue" class="hide"></div>
		<div class="uploaded-videos-queue"></div> <!-- uploaded-videos -->
		<div class="clear"></div>

		<div class="uploaded-videos">
			<input type="hidden" name="main_video" value="<?=get_post_thumbnail_id($post_id_to_edit)?>" />
			<?php
			if(count($videos) > 0) {
				$show_main_video_message = true;
			}
			?>
			<div class="uploaded-videos-message text-center<?php if(!$show_main_video_message) echo ' hide'; ?>"><?=_d('Click an video to select it as the main video for the ad',1019)?><div class="clear5"></div></div>
			<?php
			foreach($videos as $key => $video) {
				$attachment_id = $video->post_id ? $video->post_id : $video->ID;
				$video_mime_type = $video->post_mime_type;
				$video_th_url = wp_get_attachment_url($attachment_id);
				echo '<div class="one-vid" data-attachment-id="'.$attachment_id.'"><video controls class="preview-vid rad3"><source src="'.$video_th_url.'" type="'.$video_mime_type.'" /></video><div class="clear5"></div><div class="remove rad17"><span class="icon icon-delete"></span> '._d('Remove',469).'</div></div>';
			}
			?>
		</div> <!-- uploaded-videos -->
	</section> <!-- videos -->

	<!--- --------------------AGREGAR VIDEOS-------------------------- ------>

	<div class="clear30"></div>
	<?php
	if(get_option('tos_ad_page_id') && !$post_id_to_edit) {
		$tos_ad_page = get_page(get_option('tos_ad_page_id'));
		echo '<div class="ad-tos no-selection">
				<div class="err-msg hide"></div>
				<label class="ad-tos-label" for="ad_tos" id="ad_tos_label">
					<span class="icon icon-checkbox'.$class.' l"></span>
					<input class="hide" type="checkbox" name="ad_tos" value="1" id="ad_tos" />
					'._d('I agree to the %s of this website',959,$tos_ad_page->post_title).'
				</label>
				<div class="clear5"></div>
				<a href="'.get_permalink(get_option('tos_ad_page_id')).'" target="_blank">'._d('Read our',960).' '.$tos_ad_page->post_title.' </a>
			</div>';
	}

	if($post_id_to_edit) {
		$sumit_button_default = _d('Update ad',494);
	} else {
		$sumit_button_default = _d('Submit ad',495);
	}
	?>
	<div class="text-center">
		<div class="formerr-msg err hide rad3"></div>
		<div class="submit-form round-corners-button rad25" data-saving="<?=_d('Saving',92)?>" data-saved="<?=_d('Saved',93)?>" data-error="<?=_d('Error',94)?>" data-default="<?=$sumit_button_default?>">
			<span class="text"><?=$sumit_button_default?></span>
			<span class="icon icon-for-default icon-arrow-right"></span>
			<span class="icon icon-for-saved icon-checkmark hide"></span>
			<svg version="1.1" class="icon icon-for-saving loader r hide" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 50 50" style="enable-background:new 0 0 50 50;" xml:space="preserve"><path fill="#000" d="M43.935,25.145c0-10.318-8.364-18.683-18.683-18.683c-10.318,0-18.683,8.365-18.683,18.683h4.068c0-8.071,6.543-14.615,14.615-14.615c8.072,0,14.615,6.543,14.615,14.615H43.935z"><animateTransform attributeType="xml" attributeName="transform" type="rotate" from="0 25 25" to="360 25 25" dur="0.6s" repeatCount="indefinite"/></path></svg>
			<span class="icon icon-for-error icon-cancel hide"></span>
		</div> <!-- submit-form -->

		<?php if(!$post_id_to_edit) { ?>
		<div class="clear20"></div>
		<div class="go-back rad25"><span class="icon icon-arrow-left"></span> <?=_d('Go Back',496)?></div>
		<div class="clear20"></div>
		<?php } ?>
	</div> <!-- text-center -->
	<div class="clear"></div>
</form> <!-- post-form -->