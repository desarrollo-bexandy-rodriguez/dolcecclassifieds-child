jQuery(document).ready(function($) {

//upload videos START
	// Simulate a click on the file input button
	$('.post-form .video-upload').on('click', function(event) {
		$('.upload-video-button-container input[type="file"]').last().attr('accept', 'video/mp4, video/webm, video/ogg, video/quicktime, .mp4, .webm, .ogv, .mov');
		if($('.post-form .video-upload .max-vid').text() > 0) {
			$('.upload-video-button-container input[type="file"]').last().click();
		} else {
			swal({
				title: $('.post-form .video-upload').data('error-title'),
				text: $('.post-form .video-upload').data('error-text'),
				type: "error",
				allowOutsideClick: 'true',
				confirmButtonColor: '#22a4e6',
			});
		}
	});

	$('.post-form .uploaded-videos-queue').on('click', '.one-vid .close', function(event) {
		$(this).parent().remove();
		if(!$('.post-form .uploaded-videos-queue .one-vid').length) {
			var form = $('.post-form');
			form.find('.submit-form .text').text(form.find('.submit-form').data('default'));
			form.find('.submit-form .icon').hide();
			form.find('.submit-form .icon-for-default').show();
			form.find('.submit-form').removeClass('button-working');
		}
	});

	var drop_overlay = $('.post-form .drag-videos-overlay');
	//if images are dragged in the page
	$(document).on('dragenter dragover', function (e) {
		e.stopPropagation(); e.preventDefault();
		drop_overlay.css('opacity', '0.9').show();
		vcenter();
	});
	$(document).on('drop', function (e) {
		drop_overlay.hide();
		e.stopPropagation(); e.preventDefault();
		// var files = e.originalEvent.dataTransfer.files;
	});

	drop_overlay.on('click', function(event) {
		drop_overlay.hide();
	});



	//delete an video
	$('.post-form .uploaded-videos').on('click', '.one-vid .remove', function(event) {
		if($(this).hasClass('clicked')) {
			return false;
		} else {
			$(this).addClass('clicked');
		}

		var attachment_id = $(this).parents('.one-vid').data('attachment-id');
		var videos_left = parseInt($('.post-form .video-upload .no-videos-uploaded .max-vid').text()) + 1;
		$(this).parents('.one-vid').fadeOut('fast', function() {
			$(this).remove();

			if($('.post-form .uploaded-videos .one-vid').length) {
				$('.post-form .uploaded-videos .uploaded-videos-message').hide();
			}

		});
		$('.post-form .video-upload .no-videos-uploaded .max-vid, .post-form .video-upload .videos-have-been-uploaded .max-vid').text(videos_left);
		$('#upload_videos').data('uploadifive').settings.queueSizeLimit = videos_left;
		$.get(wpvars.wpthemeurl+'/ajax/delete-uploaded-image.php?id='+attachment_id);
		$('#upload_videos').data('uploadifive').uploads.count = $('#upload_videos').data('uploadifive').uploads.count - 1;
		$('#upload_videos').data('uploadifive').uploads.successful = $('#upload_videos').data('uploadifive').uploads.successful - 1;
	});
//upload videos END


});