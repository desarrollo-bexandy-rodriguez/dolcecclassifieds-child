jQuery(document).ready(function($) {
	// Add mobile marker START
	$(window).resize(function() {
		var isphone_size = parseInt('901'); // when should the mobile version for phones kick in
		if($(document).outerWidth() < isphone_size) {
			if(!$('body').hasClass('is-phone')) {
				$('body').addClass('is-phone');
			}
		} else {
			$('body').removeClass('is-phone');
		}
	});
	// Add mobile marker END

	// change language
	$('input[name="header_language"]').change(function() {
		var lang = $(this).val();
		Cookies.set('sitelang', lang, { expires: 7 });
		location.reload();
	});

	$('.welcoming-message').on('click', '.hide-welcoming-message', function(event) {
		$('.welcoming-message-wrapper').slideUp('fast');
		$.post(wpvars.wpthemeurl+'/ajax/save-settings.php', { action: 'hide-welcoming-message' });
	});

	var hash = window.location.hash.replace('#', '');

	//yes/no toggle
	$('.form-builder, .form-styling .form-input').on('click', '.toggle', function(event) {
		if($(this).hasClass('disabled')) return false;

		$(this).find('.toggle-text').toggle(0, function(){
			var value = $(this).parent().find('.toggle-text:visible').data('value');
			$(this).parent().find('input').val(value).trigger('change');
		});
	});
	check_toggle_state=function(div) {
		div.find('.toggle').each(function(index, toggle) {
			var val = $(toggle).find('.input').val();
			if(!val) val = "2";
			$(toggle).find('.toggle-text').hide();
			$(toggle).find('.toggle-text[data-value="'+val+'"]').show();
		});
	}

	//show admin menu submenu
	$('.admin-menu ul li.top').hover(function() {
		$(this).find('.sub-menu').fadeIn('fast');
		var submenu_width = 0;
		$(this).find('.sub-menu .sub a').css('width', '').each(function() {
			if($(this).outerWidth() > submenu_width) {
				submenu_width = $(this).outerWidth();
			}
		});
		$(this).find('.sub-menu .sub a').css('width', submenu_width);
	}, function() {
		$(this).find('.sub-menu').hide();
	});

	// add icon to header menu link with submenu
	$('.main-nav li').each(function(index, el) {
		if($(this).find('ul').length) {
			$(this).find('a').first().append('<span class="icon main-icon icon-arrow-down"></span><span class="icon icon-arrow-up hide"></span>');
		}
	});

	// header main menu in mobile
	$("header").on({
		mouseenter: function(){
			$('header .nav-mobile1 .main-nav').show();
		},
		mouseleave: function(){
			$('header .nav-mobile1 .main-nav').hide();
		}
	}, ".nav-mobile1 .nav-button-mobile, .nav-mobile1 .main-nav");

	$('header').on('click', '.nav-mobile2 .nav-button-mobile', function(event) {
		$('header .nav-mobile2 .main-nav').slideToggle();
	});
	$('header').on('click', '.nav-mobile2 .main-nav li a', function(event) {
		if($(this).parent().find('ul').length) {
			$(this).parent()
			event.preventDefault();
			var ul = $(this).parent().find('ul').first();
			ul.slideToggle();
			$(this).find('.icon').toggle();
			var link_html = $(this).clone().wrapAll("<div/>").parent().html();
			if(!ul.find('li.mobile').length) {
				ul.prepend('<li class="mobile">'+link_html+'</li>').find('.mobile').css('display', 'block');
				ul.find('li.mobile a .icon').remove();
			}
		}
	});

// sidebar pop-up menu START
	$('.sidebar .popover').hover(function() {
		$(this).parent('li').find('a:first').addClass('selected');
	}, function() {
		$(this).parent('li').find('a:first').removeClass('selected');
	});

	//show all subcategories on click
	$('.sidebar .show-all-subcats span').on('click', function(event) {
		$(this).parent().hide().parent().find('li.hide').slideDown();
	});

	// categories dropdown menu
	var menu = $(".sidebar .categories .dropdown-menu");
	menu.menuAim({
		activate: function(row) {
			activeRow = null;
			activateSubmenu(row);
		},
		deactivate: deactivateSubmenu
	});
	function activateSubmenu(row) {
		var row = $(row),
			submenuId = row.data("submenu-id"),
			submenu = $("#" + submenuId),
			height = menu.outerHeight(true),
			width = menu.outerWidth(true),
			submenu_height = submenu.outerHeight(true),
			submenu_width = submenu.outerWidth(true);
			if(height < '414') { height = '414'; }

		//show the submenu
		if(!submenu.find('li').length) {
			return false;
		}

		submenu.css({display: "block", left: width});

		$('.post-count').each(function() {
			$(this).css('margin-top', ($(this).parent().outerHeight()/2 - $(this).outerHeight()/2));
		});

		//if the menu is above the browser windows then we move the submenu down so it shows in the window
        var submenu_top = 0;
        if(ElementPosition($('.dropdown-menu')) < 0) {
        	submenu_top = ElementPosition($('.dropdown-menu')) * -1;
        }
		submenu.css('top', submenu_top);

		if(!submenu.hasClass('formatted')) {
			//if the submenu is longer then the predefined container we trim the list and create columns
			var column_height = 0, ul = '', li = '', multiplier = 0;
			if(submenu.find('li').length == "1") {
				ul = '<ul class="l"><li>'+submenu.find('li').html()+'</li></ul>';
				multiplier = 1;
			} else if(submenu.find('li').length > "1") {
				submenu.find('li').each(function(index, el) {
					if((column_height + $(this).outerHeight(true)) < height && !$(this).is(':last-child')) {
						column_height = column_height + $(this).outerHeight(true);
						li = li+'<li>'+$(this).html()+'</li>';
					} else {
						if($(this).is(':last-child')) {
							if((column_height + $(this).outerHeight(true)) < height) {
								ul = ul+'<ul class="l">'+li+'<li>'+$(this).html()+'</li></ul>';
							} else {
								ul = ul+'<ul class="l">'+li+'</ul><ul class="l"><li>'+$(this).html()+'</li></ul>';
								multiplier = multiplier + 1;
							}
						} else {
							ul = ul+'<ul class="l">'+li+'</ul>';
						}
						column_height = $(this).outerHeight(true);
						li = '<li>'+$(this).html()+'</li>';
						multiplier = multiplier + 1;
					}
				});
			}
			submenu.html(ul).css('width', (multiplier * width)).addClass('formatted');

			var ul_height = "0";
			submenu.find('ul').each(function(index, el) {
				if(ul_height < $(this).outerHeight()) {
					ul_height = $(this).find('li').first().outerHeight() * $(this).find('li').length;
				}
			});
			height = ul_height;

			// if the hovered link from the menu is below the submenu then make the submenu bigger. if it's very big than make the submenu fit in the page
			if((ElementPosition(submenu.parent('.top-menu')) + submenu.parent('.top-menu').outerHeight(true)) > (submenu.outerHeight(true) + ElementPosition(submenu))) {
				height = ElementPosition(submenu.parent('.top-menu')) + submenu.parent('.top-menu').outerHeight(true) - ElementPosition(submenu);
			}

			// resize the submenu based on the calculations above
			if(multiplier == '0') { multiplier = '1'; }
			submenu.css({
				height: height,
				width: ((multiplier * submenu_width) - multiplier * 2)
			});
		} // if submenu doesn't have class 'formatted'

		// if the menu passes over the right browser line then truncate the extra links and add a "see more link".
		submenu.find('ul').show();
		submenu.css('width', submenu.find('ul').first().width() * submenu.find('ul:visible').length + 4);

		// if we have a "see all categories" link then we first disable it
		if(submenu.find('.see-more-cats-link').length) {
			var see_more_cats_link = submenu.find('.see-more-cats-link');
			see_more_cats_link.attr('href', see_more_cats_link.data('original-link')).find('.text').text(see_more_cats_link.data('original-text'));
			see_more_cats_link.removeClass('see-more-cats-link');
		}

		var add_see_more = false;
		while(($('.content').offset().left + $('.content').width() - (submenu.offset().left + submenu.width())) < 0) {
			submenu.find('ul:visible').last().hide();
			submenu.css('width', submenu.find('ul').first().width() * submenu.find('ul:visible').length + 4);
			add_see_more = true;
		}
		// place the "see more categories" link at the end and store the original category name and link
		if(add_see_more) {
			var last_li_a = submenu.find('ul:visible').last().find('li a').last();
			last_li_a.addClass('see-more-cats-link').attr({
				'data-original-text': last_li_a.find('.text').text(),
				'data-original-link': last_li_a.attr('href')
			});
			last_li_a.attr('href', submenu.data('see-more-link')).find('.text').text(submenu.data('see-more-text'));
		}
	}//function activateSubmenu

    function deactivateSubmenu(row) {
        var row = $(row),
            submenuId = row.data("submenu-id"),
            submenu = $("#" + submenuId);
        submenu.css({"display": "none"});
    }

    // don't close dropdown menu on dropdown link click
    $(".sidebar .dropdown-menu li").click(function(e) {
        e.stopPropagation();
    });
	// immediately close dropdown menu on content hover.
    $('.content, header, footer').hover(function() {
        $(".sidebar .popover").css("display", "none");
    });

    function ElementPosition(e) {
		//get the position of the menu so we can show the submenu in the window
		var e_position = 0;
		var eTop = e.offset().top; //get the offset top of the element
		e_position = eTop - $(window).scrollTop(); //position of the ele w.r.t window
		$(window).scroll(function() { //when window is scrolled
			e_position = eTop - $(window).scrollTop();
		});
		return e_position;
	}
// sidebar pop-up menu END

	$('.sidebar .selected-category-subcategories .see-all-cats, .mobile-sidebar .selected-category-subcategories .see-all-cats').on('click', function(event) {
		$(this).parent().find('li.extra-category').toggle();
		$(this).find('.link').toggle();
		vcenter();
	});
	$('.mobile-sidebar .selected-category-subcategories .see-all-cats').on('click', function(event) {
		resize_sidebar_sublinks();
	});

	$('.sidebar .sortable-fields .sortable .show-more-filters, .mobile-sidebar .sortable-fields .sortable .show-more-filters').on('click', function(event) {
		$(this).siblings('ul').find('li.extra-filter').toggle();
		$(this).find('.link').toggle();
	});

	//show bigger image when a small thumbnail is hovered
	$('.item-page .item-images .thumbs-gallery img').hover(function() {
		$('.item-page .item-images .selected-thumb').html('<a href="'+$(this).data('full-img')+'"><img src="'+$(this).data('preview-th')+'" data-index="'+$(this).data('index')+'" alt="" /></a>');
	}, function() {
		/* hover out */
	});

	//if the thumbnails column overflows then activate the scrollbar
	if($('.item-page .item-images .thumbs-gallery .overview').height() > $('.item-page .item-images .thumbs-gallery .viewport').height() ) {
		$("#scrollbar").tinyscrollbar({ wheelSpeed: "20" });
		$(this).find('.scrollbar').show();
	}

	//equalize item specifications
	var div_class = $('.item-page .item-specifications .specification');
	if(div_class.length) {
		add_row_separator(div_class);
	}

	//equalize loop items
	var div_class = $('.loop .item');
	if(div_class.length) {
		add_row_separator(div_class);
	}

	//add a divider between each row to prevent wrong positioning of the divs
	function add_row_separator(div_class) {
		var position = div_class.first().position().top;
		div_class.each(function(e) {
			if(position != $(this).position().top) {
				position = $(this).position().top;
			}
		});
	} //function add_row_separator


	// position detailed star reviews
	if($('.seller-detailed-reviews').length) {
		$('.seller-detailed-reviews .stars-wrapper .stars-yellow-inner').css('width', $('.seller-detailed-reviews .stars-wrapper .stars-gray').first().outerWidth());
		$(window).on('resize', function(){
			$('.seller-detailed-reviews .stars-wrapper .stars-yellow-inner').css('width', $('.seller-detailed-reviews .stars-wrapper .stars-gray').first().outerWidth());
		});
	}


	if($('.auto-font-size').length) {
		automatic_font_size();
		$(window).on('resize', function(){
			automatic_font_size();
		});
	}
	function automatic_font_size() {
		$('.auto-font-size').each(function(index, el) {
			$(this).attr('style', '');
			if(!$(this).find('span.inner').length) {
				$(this).html('<span class="inner">'+$(this).html()+'</span>');
			}
			var label = $(this);
			var font_size = parseInt($(this).css('font-size'));
			while($(this).innerWidth() < $(this).find('span.inner').innerWidth() && font_size > 0) {
				font_size--;
				label.css('font-size', font_size+'px');
			}
		});
	}

// login / Register START
	//show register/login popup and add overlay
	if(hash == "login" && !$('body').hasClass('logged-in')) { show_and_resize_login_box(); }

	$(document).on('click', '.show-login-popup', function(event) {
		event.preventDefault();
		show_and_resize_login_box();
	});
	$(window).on('resize', function(){
		if($('.login-box').is(':visible')) {
			show_and_resize_login_box('nope');
		}
	});
	function show_and_resize_login_box(nohide) {
		if(!$('.login-overlay').length) {
			$('body').append('<div class="login-overlay overlay hide"></div>');
		}

		if(!nohide) {
			$('.login-box').fadeIn('0');
		}
		$('.login-overlay').css({
			width: $('body').outerWidth(),
			height: $('body').outerHeight(),
			opacity: '0.9'
		}).fadeIn('100');

		if($('.login-box').is(':visible')) {
			$('.login-box .action-button-wrapper').removeClass('action-button-wrapper-small-screen');
			$('.login-box .login-social').removeClass('login-social-small-screen');
			if($('.login-box .login-social').length) {
				if($('.login-box .action-button:visible').offset().top < $('.login-box .login-social').offset().top) {
					$('.login-box .action-button-wrapper').addClass('action-button-wrapper-small-screen');
					$('.login-box .login-social').addClass('login-social-small-screen');
				}
			}
		}

		$('.login-box').css('height', 'auto').removeClass('login-box-extra-small');
		$('.login-box .form .clear60, .login-box .form .clear50, .login-box .form .input').attr('style', '');
		$('.login-box .form .input').removeClass('col-50 col-33 l').addClass('col-100');
		if($(window).outerHeight() < $('.login-box').outerHeight()) {
			$('.login-box').addClass('col-100');
			$('.login-box .form .clear60').css('height', '20');

			if($(window).outerHeight() < $('.login-box').outerHeight()) {
				$('.login-box .form .clear50').css('height', '20');

				if($(window).outerHeight() < $('.login-box').outerHeight()) {
					$('.login-box').addClass('login-box-extra-small');

					if($('.login-box .form .input:visible').length == 3) {
						$('.login-box .form .input').removeClass('col-100 col-50 col-33').addClass('col-33 l');
					} else {
						$('.login-box .form .input').removeClass('col-100 col-50 col-33').addClass('col-50 l');
					}
				}
			}
		}

		setTimeout(function() {
			var top = (($(window).outerHeight() - $('.login-box').outerHeight()) / 2);
			var left = (($(window).outerWidth() - $('.login-box').outerWidth()) / 2);
			if($('.login-box').hasClass('login-box-extra-small')) {
				top = 0;
			}
			$('.login-box').css({'top': top, 'left': left});
			if(!nohide) {
				$('.login-box').hide().fadeIn('200');
			}
		}, 50);
	}
	//close login box on click and hide overlay
	$('body').on('click', '.login-box .close, .login-overlay', function(event) {
		$('.login-box').fadeOut('200').animate({top: 70}, 400);
		$('.login-overlay').fadeOut('200');
	});

	$('.login-box .form .tabs .tab').on('click', function(event) {
		$('.login-box .form .tabs .tab').toggleClass('active');
		$('.login-box .form form .toggle').toggleClass('hide');
		if(!$('.login-box').hasClass('login-box-extra-small')) {
			show_and_resize_login_box('nope');
		}
		if($('.login-box .form .input').first().hasClass('col-50') || $('.login-box .form .input').hasClass('col-33')) {
			if($('.login-box .form .input:visible').length == 3) {
				$('.login-box .form .input').removeClass('col-100 col-50 col-33').addClass('col-33 l');
			} else {
				$('.login-box .form .input').removeClass('col-100 col-50 col-33').addClass('col-50 l');
			}
		}
	});

	// show user menu
	$('header .user-menu').hover(function() {
		$('header .user-menu .menu-links').fadeIn('fast');
	}, function() {
		$('header .user-menu .menu-links').hide();
	});

	// post form for login and registration
	$('.login-box .form form').on('keypress', function(event) {
		if (event.which == 13) { // if enter is pressed
			submit_login_register_form(event);
		}
	});
	$('.login-box .form form').on('submit', function(event) {
		submit_login_register_form(event);
	});
	$('.login-box .form form .action-button').on('click', function(event) {
		submit_login_register_form(event);
	});

	function submit_login_register_form(event) {
		event.preventDefault();
		var form = $('.login-box .form form');
		var name = $('.login-box .form form #h_name').val();
		var email = $('.login-box .form form #h_email').val();
		var pass = $('.login-box .form form #h_pass').val();
		var tos = null;
		if($('.login-box .form form #reg_tos').length) {
			if($('.login-box .form form #reg_tos').is(":checked")) {
				tos = "yes";
			}
		}

		if(form.find('.action-button').hasClass('form-is-processing')) {
			return false;
		}

		form.find('.action-button').addClass('form-is-processing');
		form.find('.action-button .text').hide().parent().find('.text-saving').show();
		form.find('.err-msg').slideUp(200, function(){
			$(this).hide().text('');
			show_and_resize_login_box('nope');
		});
		form.find('.input-err').removeClass('input-err');
		form.find('.hide-on-error').show();

		var action;
		if(form.find('.login-button').is(':visible')){
			action = "login";
		} else if(form.find('.register-button').is(':visible')){
			action = "register-header";
		}

		$.ajax({
			type: "POST",
			url: wpvars.wpthemeurl+'/ajax/register-user.php',
			data: { action: action, name: name, email: email, pass: pass, tos: tos },
			cache: false,
			timeout: 20000, // in milliseconds
			success: function(raw_data) {
				var is_err = false;
				var is_json = true;
				try {
					var data = JSON.parse(raw_data);
				} catch(err) {
					is_json = false;
				}
				if(is_json && data != null) {
					if(data.status == 'ok') {
						form.remove();
						if(action == "login") {
							$('.login-box .login-over').fadeIn('200');
							setTimeout(function() {
								location.reload();
							}, 1000);
						} else if(action == "register-header") {
							$('.login-box .registration-over').fadeIn('200');
						}
					} else {
						is_err = true;
						if(data.form_err) {
							form.find('.hide-on-error').hide();
							form.find('.err-msg').html(data.form_err).slideDown(200, function(){
								show_and_resize_login_box('nope');
							});
						}
					}//if error
				}

				if(is_err || !is_json || !raw_data) {
					form.find('.action-button .text').hide().parent().find('.text-error').show();
					setTimeout(function() {
						form.find('.action-button .text').hide().parent().find('.text-default').show();
						form.find('.action-button').removeClass('form-is-processing');
					}, 2000);
				}
			},
			error: function(request, status, err) {
				form.find('.action-button .text').hide().parent().find('.text-error').show();
				setTimeout(function() {
					form.find('.action-button .text').hide().parent().find('.text-default').show();
					form.find('.action-button').removeClass('form-is-processing');
				}, 2000);
			}
		});
	}

	$('.login-box .registration-over, .content').on('click', '.resend-email-active', function(event) {
		var link = $(this);
		link.removeClass('resend-email-active');
		$('.resend-email-msg').text('Sending').fadeIn(200);
		$.post(wpvars.wpthemeurl+'/ajax/register-user.php', { action: 'resend' }, function(data, textStatus, xhr) {
			$('.resend-email-msg').text(data).fadeIn(200);
			setTimeout(function() {
				$('.resend-email-msg').fadeOut(200);
				link.addClass('resend-email-active');
			}, 5000);
		});
	});
// Login / Register END

// Private messages START
	//show private message popup
	$(document).on('click', '.send-message-popup', function(event) {
		var form = $('.private-message-form');
		form.find('.err-msg, .form-msg').hide(0, function(){ $(this).text(''); });
		form.find('.input-err').removeClass('input-err');
		form.find('.submit-button').attr('class', function(i, c){ return c.replace(/(^|\s)submit-button-\S+/g, ''); }).addClass('submit-button-default').find('.for-default').attr('style', '');
		if(!$('.private-message-popup').is(':visible')) {
			show_or_resize_private_message_popup('yes');
		}
	});
	$(window).on('resize', function(event) {
		if($('.private-message-popup').is(':visible')) {
			show_or_resize_private_message_popup('no');
		}
	});
	function show_or_resize_private_message_popup(click) {
		//show private message overlay
		if(!$('.send-message-overlay').length) {
			$('body').append('<div class="send-message-overlay overlay hide"></div>');
		}
		var overlay = $('.send-message-overlay');
		overlay.css({
			width: $('body').outerWidth(),
			height: $('body').outerHeight(),
		})
		if(!overlay.is(':visible') && click == "yes") {
			overlay.css('opacity', '0').show().animate({opacity: 0.9}, 400);
		}

		//show private message popup
		var popup = $('.private-message-popup');
		if(!popup.is(':visible') && click == "yes") {
			popup.css({ opacity: 0 }).show();
		}
		var width, height, top, temp_bottom, left;
		top = ($(window).outerHeight(true) - popup.outerHeight(true)) / 2;
		left = ($(window).outerWidth(true) - popup.outerWidth(true)) / 2;
		width = '700';
		//if popup height is bigger then the window height
		if(popup.outerHeight(true) > $(window).outerHeight(true)) {
			height = '100%';
		}
		//if popup width is bigger then the window width
		if($(window).outerWidth(true) < '720') {
			width = '100%';
		}

		if($('body').hasClass('is-phone')) {
			height = width = "100%";
			left = top = "0";
		}

		if(click == "yes") {
			temp_top = '0';
		} else {
			temp_top = top;
		}
		popup.animate({
			height: height,
			width: width,
			top: temp_top,
			left: left
		}, 300);
		if(click == "yes") {
			popup.animate({top: top, opacity: '1'}, 400);
		}
	}
	$('body').on('click', '.private-message-popup .close, .private-message-popup .cancel-private-message, .send-message-overlay', function(event) {
		$('.private-message-popup').animate({bottom: 0, opacity: 0}, 400, function() {
			$(this).hide();
		});
		$('.send-message-overlay').animate({opacity: 0}, 400, function() {
			$(this).hide();
		});
	});

	//submit form and send message
	$(document).on('submit', '.private-message-form', function(event) { event.preventDefault(); });
	$(document).on('click', '.private-message-form .submit-button-default', function(event) {
		event.preventDefault();
		var form = $('.private-message-form');
		var form_data = form.serializeArray();
		form.find('.err-msg, .form-msg').slideUp(200, function(){ $(this).text(''); show_or_resize_private_message_popup('no'); });
		form.find('.input-err').removeClass('input-err');
		form.find('.submit-button .text-default').css('padding-left', form.find('.submit-button .for-default').outerWidth());
		form.find('.submit-button .for-default').css('position', 'absolute').animate({top: '-30', left: '60', opacity: 0}, 400, function(){
			form.find('.submit-button').attr('class', function(i, c){ return c.replace(/(^|\s)submit-button-\S+/g, ''); }).addClass('submit-button-loading').find('.text-default').css('padding', '0');
		});
		$.post(wpvars.wpthemeurl+'/ajax/send-private-message.php', { action: 'send_private_message', form_data: form_data }, function(raw_data, textStatus, xhr) {
			var is_err = false;
			var is_json = true;
			try {
				var data = JSON.parse(raw_data);
			} catch(err) {
				is_json = false;
			}
			if(is_json) {
				if(data.status == "err") {
					is_err = true;
					//main form error
					if(data.form_err) {
						form.find('.form-err-msg').html(data.form_err).slideDown(200, function(){
							show_or_resize_private_message_popup('no');
						});
					}
					//form field errors
					if(data.fields_err) {
						$.each(data.fields_err, function(index, val) {
							$('#'+index).addClass('input-err').siblings('.err-msg').text(val).slideDown(200, function(){
								show_or_resize_private_message_popup('no');
							});
						});
					}
				} else {
					if(data.form_ok) {
						form.find('.form-ok-msg').html(data.form_ok).slideDown(200, function(){
							show_or_resize_private_message_popup('no');
						});
					}
					form.find('.submit-button').attr('class', function(i, c){ return c.replace(/(^|\s)submit-button-\S+/g, ''); }).addClass('submit-button-done');
					form.trigger("reset");
					form.find('[name="private_name"]').val('');
				}
			}

			if(is_err || !is_json || !raw_data) {
				form.find('.submit-button').attr('class', function(i, c){ return c.replace(/(^|\s)submit-button-\S+/g, ''); }).addClass('submit-button-err');
				setTimeout(function() {
					form.find('.submit-button').attr('class', function(i, c){ return c.replace(/(^|\s)submit-button-\S+/g, ''); }).addClass('submit-button-default').find('.for-default').css('position', 'relative').animate({opacity: '1', left: '0', top: '0'}, 400, function(){
						$(this).attr('style', '')
					});
				}, 2000);
			}
		});
	}); //submit form and send message
// Private messages END

// Report ad button START
	$(document).on('click', '.save-print-report .send-report-popup', function(event) {
		if(!$('.report-ad-popup').is(':visible')) {
			show_or_resize_report_ad_popup();
		}
	});
	$(window).on('resize', function(event) {
		if($('.report-ad-popup').is(':visible')) {
			show_or_resize_report_ad_popup();
		}
	});
	function show_or_resize_report_ad_popup() {
		// show report ad overlay
		if(!$('.report-ad-overlay').length) {
			$('body').append('<div class="report-ad-overlay overlay hide"></div>');
		}
		var overlay = $('.report-ad-overlay');
		overlay.css({
			width: $('body').outerWidth(),
			height: $('body').outerHeight(),
		})
		if(!overlay.is(':visible')) {
			overlay.css('opacity', '0.9').fadeIn('400');
		}

		// show report ad popup
		var popup = $('.report-ad-popup');
		if(!popup.is(':visible')) {
			popup.show();
		}

		popup.css({
			width: '700',
			height: 'auto'
		});

		// var width, height, bottom, temp_bottom, left;
		var top, left, width, height;
		// if popup is wider than window
		if($(window).outerWidth(true) < popup.outerWidth()) {
			left = '0';
			width = $(window).outerWidth(true);
		} else {
			left = ($(window).outerWidth(true) - popup.outerWidth()) / 2;
			width = '700';
		}
		// if popup is taller than window
		if($(window).outerHeight() < popup.outerHeight()) {
			top = '0';
			height = $(window).outerHeight();
		} else {
			top = ($(window).outerHeight(true) - popup.outerHeight()) / 2;
			height = 'auto';
		}

		popup.css({
			top: top,
			left: left,
			height: height,
			width: width
		});
	}
	$('body').on('click', '.report-ad-popup .close, .report-ad-popup .cancel-report, .report-ad-overlay', function(event) {
		$('.report-ad-popup, .report-ad-overlay').fadeOut('300');
	});
	$('.report-ad-form').on('submit', function(event) { event.preventDefault(); });
	$('.report-ad-form').on('click', '.submit-button', function(event) {
		if($(this).hasClass('working-button')) {
			return false;
		} else {
			$(this).addClass('working-button');
		}
		var form = $('.report-ad-form');
		var form_data = form.serializeArray();
		form.find('.err-msg, .form-msg').slideUp(200, function(){ $(this).text(''); show_or_resize_report_ad_popup(); });
		form.find('.submit-button').attr('class', function(i, c){ return c.replace(/(^|\s)submit-button-\S+/g, ''); }).addClass('submit-button-loading');

		$.post(wpvars.wpthemeurl+'/ajax/report-ad.php', { action: 'report_ad', form_data: form_data }, function(raw_data, textStatus, xhr) {
			var is_err = false;
			var is_json = true;
			try {
				var data = JSON.parse(raw_data);
			} catch(err) {
				is_json = false;
			}
			if(is_json) {
				if(data.status == "err") {
					is_err = true;
					//main form error
					if(data.form_err) {
						form.find('.form-err-msg').html(data.form_err).slideDown(200, function(){
							show_or_resize_report_ad_popup();
						});
					}
					//form field errors
					if(data.fields_err) {
						$.each(data.fields_err, function(index, val) {
							$('#'+index).addClass('input-err').siblings('.err-msg').text(val).slideDown(200, function(){
								show_or_resize_report_ad_popup();
							});
						});
					}
				} else {
					if(data.form_ok) {
						form.find('.form-ok-msg').html(data.form_ok).slideDown(200, function(){
							show_or_resize_report_ad_popup();
						});
					}
					// form.find('.form-label, .form-input, .buttons').hide();
					form.find('.submit-button').attr('class', function(i, c){ return c.replace(/(^|\s)submit-button-\S+/g, ''); }).addClass('submit-button-done');
				}
			}

			if(is_err || !is_json || !raw_data) {
				form.find('.submit-button').attr('class', function(i, c){ return c.replace(/(^|\s)submit-button-\S+/g, ''); }).addClass('submit-button-err');
				setTimeout(function() {
					form.find('.submit-button').attr('class', function(i, c){ return c.replace(/(^|\s)submit-button-\S+/g, ''); }).addClass('submit-button-default').removeClass('working-button');
				}, 2000);
			}
		});
	});
// Report ad button END

// Print button START
	$(document).on('click', '.save-print-report .print', function(event) {
		// add a class so the user doesn't click multiple types
		if($(this).hasClass('print-disabled')) {
			return false;
		} else {
			$(this).addClass('print-disabled');
		}

		var post_id = $('.item-page').data('post-id');
		$.get(wpvars.wpthemeurl+'/ajax/print-stats.php?post_id='+post_id);

		if($('.item-page .phone-number').hasClass('show-login-popup')) {
			$(this).removeClass('print-disabled');
			$('.item-page .phone-number').hide();
			window.print();
		} else if($('.item-page .phone-number').find('.icon-asterisk').length) {
			$(this).find('.loader').show();
			show_phone_number('print');
		} else {
			$(this).removeClass('print-disabled');
			window.print();
		}
		// return false;
	});
// Print button END

// Fake Select START
	check_all_fake_selects=function() {
		$('.fake-select').each(function(index, val) {
			var value = $(this).find(':input').last().val();
			if(!value && $(this).find('.first .text').text().length == "0") {
				value = $(this).find('.options .option').first().data('value');
			}
			$(this).find('.first .text').html($(this).find('.option[data-value="'+value+'"]').html()).children().not('img').remove();
			$(this).find(':input').last().val($(this).find('.option[data-value="'+value+'"]').data('value')); // in case the input is empty
			$(this).find('.options .selected').removeClass('selected');
			$(this).find('.option[data-value="'+value+'"]').addClass('selected');

			if($(this).outerWidth() > $(this).parent().outerWidth()) {
				$(this).addClass('parent-bigger-than-select');
			}
		});
	}
	check_all_fake_selects();
	$(document).on('click', '.fake-select', function(event) {
		var options_div = $(this).find('.options');
	    if($(this).hasClass('working')) return false;
	    if($(event.target).hasClass('select-search')) return false;

		if($(this).hasClass('active')) {
			$(this).removeClass('active');
		} else {
			$('.fake-select.active .options').hide().parent().removeClass('active');
			$(this).addClass('active');
		}

		if($(this).outerWidth() > options_div.outerWidth()) {
			options_div.css('width', $(this).outerWidth());
		}

		$(this).find('.options').toggle();
		if($(this).find('.icon-arrow-up').is(':visible') || $(this).find('.icon-arrow-up').is(':visible')) {
			// if both icons are hidden then don't toggle them
			$(this).find('.icon-arrow-up, .icon-arrow-down').toggle();
		}

		if(options_div.get(0).scrollWidth < options_div.get(0).scrollHeight && !options_div.data('has-width')) {
			options_div.css('width', (options_div.outerWidth(true) + 25)).data('has-width', 'yes');
		}

		// if the right side is out of the window frame
		if(options_div.offset().left + options_div.outerWidth() > $('body').outerWidth()) {
			options_div.css({
				right: '0',
				left: 'auto'
			});
		}
	});
	$(document).on('click', '.fake-select .options .option', function(event) {
		event.stopPropagation();
		var select = $(this).parents('.fake-select');
		if(select.find('.icon-arrow-up').is(':visible') || select.find('.icon-arrow-up').is(':visible')) {
			// if both icons are hidden then don't toggle them
			select.find('.icon-arrow-up, .icon-arrow-down').toggle();
		}
		select.find('.first .text').html($(this).html()).children().not('img').remove();
		select.find(':input').first().val('');
		select.find('.option').show();
		select.removeClass('parent-bigger-than-select');
		var option_val = $(this).data('value');
		select.find(':input').last().val(option_val).trigger('change');
		select.find('.selected').removeClass('selected');
		$(this).addClass('selected');
		$(this).parents('.options').hide();

		if(select.hasClass('input-err')) {
			select.removeClass('input-err');
		}
		if(select.siblings('.err-msg').length) {
			select.siblings('.err-msg').hide().text();
		}

		if(select.siblings('.err-msg').length) {
			select.siblings('.err-msg').hide().text();
		}
		if(select.outerWidth() > select.parent().outerWidth()) {
			select.addClass('parent-bigger-than-select')
		}
	});
	$(document).on('mouseup', function(e) {
	    var container = $('.fake-select');
	    if($(e.target).hasClass('select-search')) return false;

	    if (!container.is(e.target) && container.has(e.target).length === 0 && !$(e.target).hasClass('select-search')) {
			container.find('.options:visible').hide(0, function() {
				if($(this).parents('.fake-select').find('.icon-arrow-up').is(':visible') || $(this).parents('.fake-select').find('.icon-arrow-up').is(':visible')) {
					// if both icons are hidden then don't toggle them
					$(this).parents('.fake-select').find('.icon-arrow-up, .icon-arrow-down').toggle();
				}
				$(this).parents('.fake-select').find('.option').show();
			});
		}
	});
	$(document).on('keyup', '.select-search', function(){
		var text = $(this).val();
		if($(this).val.length == "0") {
			$(this).siblings('.option').show();
		} else {
			$(this).siblings('.option').hide();
			$(this).siblings('.option').each(function(){
				if($(this).text().toUpperCase().indexOf(text.toUpperCase()) != -1){
					$(this).show();
				}
			});
		}
	});
	$('.fake-select-header-language-chooser').on('click', function(event) {
		if($('nav.nav-mobile1 .main-nav').is(':visible') || $('nav.nav-mobile2 .main-nav').is(':visible')) {
			$('nav .main-nav').hide();
		}
	});
// Fake Select END

// Edit categories START
	//save category settings
	$('.edit-categories').on('click', '.save-category-settings-not-saved .round-corners-button', function(event) {
		$('.edit-categories .why-this-is-needed-message').slideUp();
		var div = $(this).parent();
		div.removeClass().addClass('save-category-settings-saving');

		var form_data = $(".cat-list-form :input[value!='']").serializeArray();
		$.ajax({
			type: "POST",
			url: wpvars.wpthemeurl+'/ajax/edit-categories-ajax.php',
			data: { action: 'save_category_settings', form_data: form_data },
			cache: false,
			timeout: 30000, // in milliseconds
			success: function(raw_data) {
				var is_err = false;
				var is_json = true;
				try {
					var data = JSON.parse(raw_data);
				} catch(err) {
					is_json = false;
				}
				if(is_json && data != null) {
					if(data.status == 'ok') {
						div.removeClass().addClass('save-category-settings-saved');
						$('.edit-categories .generate-new-font-file .font-generation-response').html(data.msg);
						setTimeout(function() {
							$('.generate-new-font-file').slideUp(function(){
								div.removeClass().addClass('save-category-settings-not-saved');
								$('.edit-categories .generate-new-font-file .font-generation-response').html('');
							});
						}, 3000);
					} else {
						is_err = true;
						div.removeClass().addClass('save-category-settings-error');
						$('.edit-categories .generate-new-font-file .font-generation-response').html(data.msg);
						setTimeout(function() {
							div.removeClass().addClass('save-category-settings-not-saved');
						}, 3000);
					}//if error
				}

				if(is_err || !is_json || !raw_data) {
					div.removeClass().addClass('save-category-settings-error');
					setTimeout(function() {
						div.removeClass().addClass('save-category-settings-not-saved');
					}, 3000);
				}
			},
			error: function(request, status, err) {
				div.removeClass().addClass('save-category-settings-error');
				setTimeout(function() {
					div.removeClass().addClass('save-category-settings-not-saved');
				}, 3000);
			}
		});
	});
	$('.edit-categories').on('click', '.why-this-is-needed, .why-this-is-needed-message .close', function(event) {
		$('.why-this-is-needed-message').slideToggle();
	});

	//add new category
	$('.add-cat-form').submit(function(event) {
		event.preventDefault();
		if($(this).hasClass('edit-cat-form')) {
			add_update_category('update_category');
		} else {
			add_update_category('add_new_category');
		}
	});
	//update category
	$('.edit-categories .add-cat-form').on('click', '.update-category', function(event) {
		event.preventDefault();
		add_update_category('update_category');
	});

	function add_update_category(action) {
		var form = $('.add-cat-form');
		var form_data = form.serializeArray();
		var catname = form.find('[name="catname"]').val();
		var parent = form.find('[name="parent"]').val();
		if(parent == "default") { parent = null; }
		var div_class = Math.floor(Math.random()*(99999-9+1)+9);
		var catid = '';
		if(action == "update_category") {
			catid = form.data('cat-to-edit');
		}
		var subcats_html = '<div class="sub-cat" data-subcats-for-cat="'+catid+'">'+$('.edit-categories .cat-list .sub-cat[data-subcats-for-cat="'+catid+'"]').html()+'</div>';
		var dropdown_cat_subcats = '<div class="sub-cat" data-subcats-for-cat="'+catid+'">'+$('.edit-categories .add-cat .choose-parent-cat [data-subcats-for-cat="'+catid+'"]').html()+'</div>';
		form.find('.field-err').hide().find('.text').text(); //hide any previous error messages
		form.find('.input').removeClass('input-err'); //remove the red border for the input error
		form.find('.round-corners-button .icon, .round-corners-button .loader').toggle(); //show the spinning loader
		$.post(wpvars.wpthemeurl+'/ajax/edit-categories-ajax.php', { action: action, form_data: form_data, catid: catid }, function(data, textStatus, xhr) {
			var data = JSON.parse(data);
			if(data.status == "err") {
				if(data.catname) {
					form.find('.catname-err').show().find('.text').text(data.catname);
					form.find('[name="catname"]').addClass('input-err');
				}
				if(data.catslug) {
					form.find('.catslug-err').show().find('.text').text(data.catslug);
					form.find('[name="catslug"]').addClass('input-err');
				}
			} else {
				var new_cat_html = '<div class="'+div_class+' newly-added-cat rad5">'+data.new_cat_html+'</div>';
				if(action == "update_category") {
					$('.edit-categories .cat-list #cat-'+catid).remove();
					$('.add-cat-form').removeClass('edit-cat-form');
				}
				form.trigger("reset");
				form.find('[name="catslug"]').data('edited', 'no')
				form.find('.selected-icon .icon').removeClass().addClass('icon all-icons-angle-right');

				//add the new category in the left category list
				var added = false;
				if(parent == 'default' || parent == null) {
					parent = "0";
				}

				var cats = $('.edit-categories .cat-list').find('[data-cat-parent="'+parent+'"]');
				cats.each(function(index, el) {
					if($(this).text().toLowerCase().toString().trim() > catname.toLowerCase().toString().trim()) {
						$(this).before(new_cat_html);
						added = true;
						return false;
					}
				});

				if(!added) {
					if(parent > 0) {
						$('.edit-categories .cat-list').find('[data-subcats-for-cat="'+parent+'"]').append(new_cat_html);
					} else {
						$('.edit-categories .cat-list .cat-list-form').append(new_cat_html);
					}
				}

				// add the subcats under the parent
				$('.edit-categories .cat-list .sub-cat[data-subcats-for-cat="'+catid+'"]').remove();
				$('.edit-categories .cat-list #cat-'+catid).after(subcats_html);

				//show the new category
				$('.'+div_class).css('opacity', '0');
				$('html, body').animate({ scrollTop: $('.'+div_class).offset().top }, 400);
				$('.'+div_class).animate({opacity: 1}, 700, function(){
					setTimeout(function() {
						$('.'+div_class).removeClass('newly-added-cat');
					}, 600);
				});

				//add the new category in the "ad new cat" form. in the category dropdown
				if(action == "update_category") {
					$('.edit-categories .add-cat .choose-parent-cat [data-value="'+catid+'"]').remove();
					$('.add-cat-form').find('.add-new-category').show();
					$('.add-cat-form').find('.update-category, .cancel-edit').hide();
				}
				added = false;
				cats = $('.edit-categories .add-cat .choose-parent-cat').find('[data-cat-parent="'+parent+'"]');
				cats.each(function(index, el) {
					if($(this).text().toLowerCase().toString().trim() > catname.toLowerCase().toString().trim()) {
						$(this).before(data.new_cat_in_dropdown_html);
						added = true;
						return false;
					}
				});
				if(!added) {
					if(parent > 0) {
						$('.edit-categories .add-cat .choose-parent-cat').find('[data-subcats-for-cat="'+parent+'"]').append(data.new_cat_in_dropdown_html);
					} else {
						$('.edit-categories .add-cat .choose-parent-cat .options').append(data.new_cat_in_dropdown_html);
					}
				}

				// dropdown-cats - add the subcats under the parent
				$('.edit-categories .add-cat .option[data-value="'+catid+'"]').after(dropdown_cat_subcats);

				if(!$('.edit-categories .cat-list .generate-new-font-file').is(':visible')) {
					$('.edit-categories .cat-list .generate-new-font-file').slideDown();
				}
			}
		}).done(function() {
			form.find('.round-corners-button .icon, .round-corners-button .loader').toggle();
		});
	}

	//delete category
	$('.edit-categories .cat-list-form').on('click', '.delete-cat', function(event) {
		var cat_div = $(this).parents('.one-cat');
		var cat_id = cat_div.attr('id').replace(/\D/g,'');
		var cat_post_count = cat_div.find('.post-count').text();
		cat_div.find('.delete-cat .loader, .delete-cat .icon').toggle();
		function remove_cat_divs(cat_div, cat_id) {
			//hide category div
			cat_div.css({background: '#EF3A39', color: '#fff'}).animate({opacity: 0}, 500, function(){
				$(this).remove();
			});
			//hide subcategories di, if any
			$('.edit-categories .cat-list').find('[data-subcats-for-cat="'+cat_id+'"]').
			css({background: '#EF3A39', color: '#fff'}).animate({opacity: 0}, 500, function(){
				$(this).remove();
			});
			//hide category from right side dropdown
			$('.edit-categories .add-cat .choose-parent-cat .options').find('[data-value="'+cat_id+'"]').remove();
			$('.edit-categories .add-cat .choose-parent-cat .options').find('[data-subcats-for-cat="'+cat_id+'"]').remove();
			if(!$('.edit-categories .cat-list .generate-new-font-file').is(':visible')) {
				$('.edit-categories .cat-list .generate-new-font-file').slideDown();
			}
		}
		if(cat_post_count > 0) {
			swal({
				title: "Are you sure?",
				text: "You have "+cat_post_count+" ads in this category!\nDeleting this category will hide those ads.",
				type: "warning",
				allowOutsideClick: 'true',
				showCancelButton: 'true',
				confirmButtonText: "Delete",
				confirmButtonColor: '#EF3A39',
				cancelButtonColor: '#b8c3d9',
				showLoaderOnConfirm: true,
				preConfirm: function() {
					return new Promise(function(resolve, reject) {
						$.get(wpvars.wpthemeurl+'/ajax/edit-categories-ajax.php?action=delete_cat&cat_id='+cat_id, function(data) {
							if(data.trim() == "ok") {
								remove_cat_divs(cat_div, cat_id);
								resolve();
							}
						});
					});
				},
			}).then(function() {
				swal({title: "Deleted!", text: "The category has been deleted.", type: "success", timer: '3000'});
				resize_swal2();
			}, function(dismiss) {
				cat_div.find('.delete-cat .loader, .delete-cat .icon').toggle();
			});
			resize_swal2();
		} else {
			$.get(wpvars.wpthemeurl+'/ajax/edit-categories-ajax.php?action=delete_cat&cat_id='+cat_id, function(data) {
				if(data.trim() == "ok") {
					remove_cat_divs(cat_div, cat_id);
				} else {
					cat_div.find('.delete-cat .loader, .delete-cat .icon').toggle();
				}
			});
		}
	});

	//edit category
	$('.edit-categories .cat-list-form').on('click', '.edit-cat', function(event) {
		var cat_div = $(this).parent();
		var cat_id = cat_div.attr('id').replace(/\D/g,'');
		cat_div.find('.edit-cat .loader, .edit-cat .icon').toggle();
		var form = $('.add-cat-form');
		form.find('.field-err').hide().find('.text').text(); //hide any previous error messages
		form.find('.input').removeClass('input-err'); //remove the red border for the input error
		$.get(wpvars.wpthemeurl+'/ajax/edit-categories-ajax.php?action=edit_cat&cat_id='+cat_id, function(data) {
			if(data) {
				$('html, body').animate({ scrollTop: $('.edit-categories .add-cat').offset().top }, 400);
				var formdata = JSON.parse(data);
				$('.add-cat-form').data('cat-to-edit', cat_id);
				$('.add-cat-form [name="catname"]').val(formdata.name);
				$('.add-cat-form [name="catslug"]').data('edited', 'yes').val(formdata.slug);
				$('.add-cat-form [name="catdescription"]').val(formdata.description);
				$('.add-cat-form .add-new-category').hide();
				$('.add-cat-form').find('.update-category, .cancel-edit').show();
				$('.add-cat-form').addClass('edit-cat-form');

				$('.add-cat-form').find('.choose-parent-cat .option[data-value="'+formdata.parent+'"]').click();
				$('.add-cat-form .selected-icon .icon').removeClass().addClass('icon all-icons-'+formdata.icon);
				$('.add-cat-form [name="caticon"]').val(formdata.icon);
			}
			cat_div.find('.edit-cat .loader, .edit-cat .icon').toggle();
			if($('body').hasClass('is-phone')) {
				$('html, body').animate({ scrollTop: $('.add-cat').offset().top }, 400);
			}
		});
	});
	//cancel edit category
	$('.edit-categories .add-cat-form').on('click', '.cancel-edit', function(event) {
		event.preventDefault();
		var form = $('.add-cat-form');
		form.trigger("reset");
		form.find('[name="catslug"]').data('edited', 'no')
		form.removeData('cat-to-edit');
		form.find('.choose-parent-cat .option').first().click();
		form.find('.selected-icon .icon').removeClass().addClass('icon all-icons-angle-right');
		$('.add-cat-form [name="caticon"]').val('');
		form.find('.add-new-category').show();
		form.find('.update-category, .cancel-edit').hide();
		form.removeClass('edit-cat-form');
	});


	$('.edit-categories [name="catname"]').on('keyup', function(event) {
		if($('.edit-categories [name="catslug"]').data('edited') == "no") {
			$('.edit-categories [name="catslug"]').val($(this).val().replace(/\W+/g, "-").toLowerCase());
		}
	});
	$('.edit-categories [name="catslug"]').on('keyup', function(event) {
		$(this).data('edited', 'yes');
		var val = $(this).val().replace(/\W+/g, "-").toLowerCase();
		$(this).val(val);
	});

	$('.edit-categories').on('click', '.cat-list .cat-icon, .add-cat .choose-new-cat-icon', function(event) {
		if($(this).hasClass('choose-new-cat-icon-disabled')) return false;
		show_icon_picker($(this))
	});
	$(window).on('resize', function(){
		if($('.edit-categories .all-icons').is(':visible')) {
			show_icon_picker();
		}
	});

	function show_icon_picker(div) {
		div = typeof div !== 'undefined' ? div : false;
		if(!$('.edit-categories .all-icons').is(':visible')) {
			$('.edit-categories .all-icons, .edit-categories .overlay').show();
		}
		var window_height = $(window).height();
		var window_width = $(window).width();
		var height = window_height * 0.9;
		var width;
		if(window_width > 1200) {
			width = '1200';
		} else {
			var width = window_width * 0.9;
		}
		$('body').css('overflow', 'hidden');
		$('.edit-categories .overlay').css({
			height: window_height,
			width: window_width,
			opacity: 0.9
		});
		$('.edit-categories .all-icons').css({
			height: height,
			width: width,
			top: (window_height / 2) - (height / 2),
			left: (window_width / 2) - (width / 2)
		});
		$('.edit-categories .all-icons .icon-list').css('height', ($('.edit-categories .all-icons').outerHeight() - $('.edit-categories .all-icons .top-frame').outerHeight() - 2));
		if(div) {
			$('.edit-categories .all-icons').data('parent', div.data('identifier'));
			$('.all-icons .search-icon').val('');
		}
	}
	function hide_icon_picker() {
		$('.edit-categories .all-icons, .edit-categories .overlay').hide();
		$('body').css('overflow', 'auto');
		$('.all-icons .search-icon').val('');
		$('.all-icons .icon-list .icon-group, .all-icons .icon-list .icon').show();
	}
	$('.all-icons .search-icon').keyup(function(){
		var search = $(this).val();
		$('.all-icons .icon-list .icon').each(function(index, val) {
			var css = $(this).data('css');
			if(css.indexOf(search) !== -1) {
				$(this).show();
			} else {
				$(this).hide();
			}
		});
		$('.icon-group').each(function(index, el) {
			$(this).show();
			if(!$(this).find('.icon:visible').length) {
				$(this).hide();
			}
		});
	});

	$('.edit-categories .all-icons .icon-list .icon').on('click', function(event) {
		$('[data-identifier="'+$('.edit-categories .all-icons').data('parent')+'"]').find('.selected-icon').html('<span class="icon all-icons-'+$(this).data('css')+'"></span>')
		$('[data-identifier="'+$('.edit-categories .all-icons').data('parent')+'"]').find('[data-value="caticon"]').val($(this).data('css'));
		hide_icon_picker();
		if(!$('.edit-categories .cat-list .generate-new-font-file').is(':visible')) {
			$('.edit-categories .cat-list .generate-new-font-file').slideDown();
		}
	});

	$('.edit-categories .all-icons .close').on('click', function(event) {
		hide_icon_picker();
	});

	$('.edit-categories .cat-list .show-all-cats').on('click', function(event) {
		$(this).siblings('.one-cat').show();
		$(this).remove();
	});
// Edit categories END

// Single Ad Page START
	// edit the ad
	$('.item-page .edit-ad-menu .edit-ad-buttons .edit').on('click', function(event) {
		edit_ad();
	});

	if(hash == "edit") { edit_ad(); }
	function edit_ad() {
		$('.edit-entry .post-form .submit-form').show();
		$('.item-page .edit-ad-menu .edit-ad-buttons .active').removeClass('active');

		if($('.item-page .edit-entry').is(':visible') && $('.edit-entry .post-form section.fields').first().is(':visible')) {
			// close edit ad section
			$('.item-page .page-section').hide();
			$('.item-page .entry').fadeIn('250');
		} else {
			// edit ad
			$('.post-form .fake-select-category-chooser input').last().val($('.post-form .fake-select-category-chooser').data('original-cat'));

			load_form_fields_edit_ad($('.post-form').data('post-id'), $('.item-page .edit-entry .post-form .fake-select-category-chooser input').last().val());

			$('.item-page .page-section').hide();
			$('.item-page .edit-entry').fadeIn('250');
			$('.item-page .edit-ad-menu .edit-ad-buttons .edit').addClass('active');
		}

		$('.edit-entry .post-form section.images').hide();
		$('.edit-entry .post-form section.videos').hide();
		$('.edit-entry .post-form section.fields').show();
	}
	function load_form_fields_edit_ad(post_id, new_cat) {
		$('.post-form .spinner-loader').show();
		$('.post-form .generated-form-fields').empty();
		$.get(wpvars.wpthemeurl+'/ajax/get-category-form-fields.php?post_id='+post_id+'&cat_id='+new_cat, function(data) {
			$('.post-form .generated-form-fields').html(data);
			$('.post-form .spinner-loader').hide();
			check_all_fake_selects();
			format_fake_checkboxes();
			format_fake_radio();
			if($('.post-form .auto-class-make-field').length) {
				$('.post-form .auto-class-make-field').trigger('change');
			}
		});
	}

	// edit the images
	if(hash == "images") { edit_ad_images(); }
	$('.item-page .edit-ad-menu .edit-ad-buttons .edit-images').on('click', function(event) {
		edit_ad_images();
	});
	function edit_ad_images() {
		$('.item-page .edit-ad-menu .edit-ad-buttons .active').removeClass('active');

		if($('.item-page .edit-entry').is(':visible') && $('.edit-entry .post-form section.images').is(':visible')) {
			// close edit ad section
			$('.item-page .page-section').hide();
			$('.item-page .entry').fadeIn('250');
		} else {
			$('.item-page .page-section').hide();
			$('.item-page .edit-entry').fadeIn('250');
			$('.item-page .edit-ad-menu .edit-ad-buttons .edit-images').addClass('active');
		}

		$('.edit-entry .post-form section.images').show();
		$('.edit-entry .post-form section.fields, .edit-entry .post-form .submit-form').hide();
	}

	// close a section
	$('.item-page .page-section .page-section-close').on('click', function(event) {
		$('.item-page .edit-ad-menu .edit-ad-buttons .active').removeClass('active');
		$('.item-page .page-section').hide();
		$('.item-page .entry').fadeIn('250', function(){
			ad_page_price_resize();
		});
		if($(this).parent().hasClass('buy-upgrades')) {
			$('.needs-payment').show();
			vcenter();
		}
	});

	// Ad page resize big prices START
	setTimeout(function() { ad_page_price_resize(); }, 500);
	$(window).on('resize', function(){ ad_page_price_resize(); });
	function ad_page_price_resize() {
		if($('.single-item .item-details .item-conditions .price:visible').length) {
			$('.single-item .item-details .item-conditions .price').removeClass('col-100').addClass('pc-50').css('padding-bottom', '0');
			$('.single-item .item-details .item-conditions .price .value').attr('style', '');
			if ($('.single-item .item-details .item-conditions .price')[0].scrollWidth >  $('.single-item .item-details .item-conditions .price').innerWidth()) {
				$('.single-item .item-details .item-conditions .price').removeClass('pc-50').addClass('col-100').css('padding-bottom', '20px');

				var font_size = parseInt($('.single-item .item-details .item-conditions .price .value').css('font-size'));
				while($('.single-item .item-details .item-conditions .price')[0].scrollWidth >  $('.single-item .item-details .item-conditions .price').innerWidth() && font_size > 0) {
					font_size--;
					$('.single-item .item-details .item-conditions .price .value').css('font-size', font_size+'px');
				}
			}
		}
	} // Ad page resize big prices END

	$('.item-page .edit-ad-menu .edit-ad-buttons li').on('click', function(event) {
		if(!$(this).hasClass('upgrade') && !$(this).hasClass('pause') && !$(this).hasClass('delete')) {
			$('.needs-payment').show();
			vcenter();
		}
	}); // pause ad

	// pause the ad
	$('.item-page .edit-ad-menu .edit-ad-buttons .pause').on('click', function(event) {
		if($(this).hasClass('paused-blocked')) {
			return false;
		}
		var button = $(this);
		if(button.hasClass('paused')) {
			swal({
				title: button.data('swal-title-paused'),
				text: button.data('swal-text-paused'),
				type: "warning",
				allowOutsideClick: 'true',
				showCancelButton: 'true',
				confirmButtonText: button.data('swal-button-paused'),
				confirmButtonColor: '#EF3A39',
				cancelButtonColor: '#b8c3d9',
				showLoaderOnConfirm: true,
				preConfirm: function() {
					return new Promise(function(resolve, reject) {
						var post_id = $('.item-page .entry').data('post-id');
						$.post(wpvars.wpthemeurl+'/ajax/validate-post-form-data.php', { action: 'unpause', id: post_id }, function(raw_data, textStatus, xhr){
							resolve();
						});
					});
				},
			}).then(function() {
				var post_id = $('.item-page .entry').data('post-id');

				button.removeClass('paused');
				button.find('.text').text(button.data('default'));
				swal({title: button.data('swal-confirmation-paused'), text: "", type: "success", timer: '3000'});
				$('.item-page .entry .ad-is-paused').fadeOut(250);
				$('.item-page .needs-activation').fadeOut(250);
				resize_swal2();
			});
			resize_swal2();
		} else {
			swal({
				title: button.data('swal-title-default'),
				text: button.data('swal-text-default'),
				type: "question",
				allowOutsideClick: 'true',
				showCancelButton: 'true',
				confirmButtonText: button.data('swal-button-default'),
				confirmButtonColor: '#EF3A39',
				cancelButtonColor: '#b8c3d9',
				cancelButtonText: button.data('swal-button-cancel'),
				showLoaderOnConfirm: true,
				preConfirm: function() {
					return new Promise(function(resolve, reject) {
						var post_id = $('.item-page .entry').data('post-id');
						$.post(wpvars.wpthemeurl+'/ajax/validate-post-form-data.php', { action: 'pause', id: post_id }, function(raw_data, textStatus, xhr){
							resolve();
						});
					});
				},
			}).then(function() {
				$('.item-page .entry .ad-is-paused').fadeIn(250);
				button.addClass('paused');
				button.find('.text').text(button.data('paused'));
				swal({title: button.data('swal-confirmation-default'), text: "", type: "success", timer: '3000'});
				resize_swal2();
			});
			resize_swal2();
		}
	}); // pause ad

	// delete the ad
	$('.item-page .edit-ad-menu .edit-ad-buttons .delete').on('click', function(event) {
		var button = $(this);
		swal({
			title: button.data('swal-title'),
			text: button.data('swal-text'),
			type: "warning",
			allowOutsideClick: 'true',
			showCancelButton: 'true',
			confirmButtonText: button.data('swal-button'),
			confirmButtonColor: '#EF3A39',
			cancelButtonText: button.data('swal-cancel'),
			cancelButtonColor: '#b8c3d9',
			showLoaderOnConfirm: 'true',
			preConfirm: function() {
				return new Promise(function(resolve, reject) {
					var post_id = $('.item-page .entry').data('post-id');
					$.post(wpvars.wpthemeurl+'/ajax/validate-post-form-data.php', { action: 'delete', id: post_id }, function(data, textStatus, xhr) {
						resolve();
					});
				});
			}
		}).then(function() {
			$('.item-page .page-section, .item-page .edit-ad-menu').remove();
			$('.item-page .entry-deleted').fadeIn('slow');
		});
		resize_swal2();
	}); // delete ad

	// upgrade the ad
	$('.needs-payment .link').on('click', function(event) {
		if(!$('.item-page .buy-upgrades').is(':visible')) {
			// show section
			$('.item-page .edit-ad-menu .edit-ad-buttons .active').removeClass('active');
			$('.item-page .page-section').hide();
			$('.item-page .buy-upgrades').fadeIn('250');
			$('.item-page .edit-ad-menu .edit-ad-buttons .upgrade').addClass('active');
			$('.needs-payment').hide();
		}
		// scroll to section
		$('html, body').animate({ scrollTop: $('.item-page .buy-upgrades').offset().top }, 500);
		vcenter();
	}); // upgrade ad

	if(hash == "upgrade") { upgrade_ad(); }
	$('.item-page .edit-ad-menu .edit-ad-buttons .upgrade').on('click', function(event) {
		upgrade_ad();
	}); // upgrade ad
	function upgrade_ad () {
		$('.item-page .edit-ad-menu .edit-ad-buttons .active').removeClass('active');
		if($('.item-page .buy-upgrades').is(':visible')) {
			// hide section
			$('.item-page .page-section').hide();
			$('.item-page .entry').fadeIn('250');
			$('.needs-payment').show();
		} else {
			// show section
			$('.item-page .page-section').hide();
			$('.item-page .buy-upgrades').fadeIn('250');
			$('.item-page .edit-ad-menu .edit-ad-buttons .upgrade').addClass('active');
			$('.needs-payment').hide();
		}
		vcenter();
	}

	$(window).resize(function() {
		resize_swal2();
	});
	function resize_swal2() {
		if($('.swal2-modal:visible').length) {
			$('.swal2-modal h2').attr('style', '');
			$('.swal2-modal .swal2-content').attr('style', '');
			if($('.swal2-modal:visible')[0].scrollHeight > $('.swal2-modal:visible').outerHeight()) {
				$('.swal2-modal h2').css({'line-height': '1.3em', 'font-size': '1.1em', 'margin-top': '7px'});
				$('.swal2-modal .swal2-content').css({'font-size': '0.9em'});

				if($('.swal2-modal:visible')[0].scrollHeight > $('.swal2-modal:visible').outerHeight()) {
					$('.swal2-modal .swal2-icon').css({
						'margin-top': '0', 
						'margin-bottom': '5px'
					});
					$('.swal2-modal button').css('margin-top', '0');
				}
			}
		}
	}

	// make the item details at least as big as the ad author sidebar
	resize_item_conditions()
	$(window).on('resize', function(){ resize_item_conditions(); });
	function resize_item_conditions() {
		var img_box_height = $('.item-page .item-images').outerHeight();
		var conditions_box_height = $('.item-page .item-details3').outerHeight();
		var seller_box_height = $('.item-page .seller-and-report').outerHeight();
		var box_height = Math.max(img_box_height, conditions_box_height, seller_box_height) + 1;

		$('.item-page .item-details3, .item-page .seller-and-report').css('height', 'auto');
		setTimeout(function() {
			if(!$('body').hasClass('is-phone')) {
				$('.item-page .item-details3, .item-page .seller-and-report').css('height', box_height);
			}
		}, 502);
	}

	// change the form fields when the user changes the category in the "edit ad" section
	$('.item-page .edit-entry .post-form .fake-select-category-chooser .option').on('click', function(event) {
		load_form_fields_edit_ad($('.post-form').data('post-id'), $(this).data('value'));
	});

	$(document).on('click', '.item-page .phone-number', function(event) {
		if(!$(this).hasClass('show-login-popup') && $(this).find('.icon-asterisk').length) {
			show_phone_number();
		}
	});
	function show_phone_number(event) {
		$('.item-page .phone-number .show-phone-number').hide();
		var post_id = $('.item-page').data('post-id');
		$.get(wpvars.wpthemeurl+'/ajax/get-phone-number.php?post_id='+post_id, function(data) {
			if(data) {
				$('.item-page .phone-number .number .text').fadeOut('fast', function() {
					$('.item-page .phone-number .number .text').attr('href', 'tel:'+data);
					$(this).text(data).fadeIn('fast', function() {
						if(event == "print") {
							$('.save-print-report .print').removeClass('print-disabled');
							$('.save-print-report .print .loader').hide();
							window.print();
						}
					});
				});
			}
		});
	}
// Single Ad Page END

// Header search START
	$('.header-search .location-box-wrapper .location-autocomplete').css('top', ($('.header-search .location-box').outerHeight() - 4));
	$('.header-search .location').on('focus, click, keyup', function(event) {
		if($.inArray(event.keyCode, [37, 39, 13]) !== -1) { // if up/down/left/right or enter keys are pressed
			return false;
		}
		$('.header-search .location-autocomplete:not(:empty)').show();
	});
	$('.header-search .location').on('keyup', function(event) {
		if($.inArray(event.keyCode, [37, 38, 39, 40, 13]) !== -1) { // if up/down/left/right or enter keys are pressed
			return false;
		}
		$('.header-search #location_slug').val('');
		$('.header-search .location-autocomplete').html('').hide();
		$.get(wpvars.wpthemeurl+'/ajax/get-locations.php?location='+$(this).val()+'&time='+$.now(), function(raw_data) {
			if(raw_data) {
				var is_err = false;
				var is_json = true;
				try {
					var data = JSON.parse(raw_data);
				} catch(err) {
					is_json = false;
				}
				if(is_json && data != null) {
					var code = "";
					code += "<ul>";
					$.each(data, function(index, val) {
						code += '<li data-location-name="'+val.name+'" data-location-slug="'+val.slug+'">'+val.name_and_parent+'</li>';
					});
					code += "</ul>";
					$('.header-search .location-autocomplete').html(code).show();
				}

				if(is_err || !is_json || !raw_data) {
					$('.header-search .location-autocomplete').hide().html('');
				}
			} else {
				$('.header-search .location-autocomplete').hide().html('');
			}
		});
	});
	$('.header-search .location-autocomplete').on('click', 'li', function(event) {
		$('.header-search .location').val($(this).data('location-name'));
		$('.header-search .location-autocomplete').hide();
		$('.header-search #location_slug').val($(this).data('location-slug'));
	});
	// on hover out hide the autocomplete
	$('.header-search .location-autocomplete').hover(function() {
	}, function() {
		// $('.header-search .location-autocomplete').hide();
	});
	$(document).on('mouseup', function(e) {
	    var container = $('.location-autocomplete');
	    if (!container.is(e.target) && container.has(e.target).length === 0) {
			container.hide();
		}
	});

	$('.header-search .keyword').on('keydown', function(e) {
		if(e.keyCode == "9") { // tab key is pressed
			$('.header-search .fake-select-header-category-chooser').trigger('click').focus();
		}
	});
	$('.header-search .keyword, .header-search .location').on('focus', function(e) {
		$('.header-search .fake-select .options').hide();
	});
	$('.header-search .location').on('keydown', function(e) {
		if($('.header-search .location-autocomplete').is(':visible')) {
			if(e.keyCode == "38") { // up key is pressed
				if(!$('.header-search .location-autocomplete .active').length) {
					$('.header-search .location-autocomplete ul li').first().addClass('active');
				} else {
					var li = $('.header-search .location-autocomplete li');
					var total_li = li.length;
					$.each(li, function(index, val) {
						if($(this).hasClass('active') && index > 0) {
							$(this).removeClass('active').parent().find('li:eq('+(index - 1)+')').addClass('active');
						}
					});
				}
			}
			if(e.keyCode == "40") { // down key is pressed
				if(!$('.header-search .location-autocomplete .active').length) {
					$('.header-search .location-autocomplete ul li').first().addClass('active');
				} else {
					var li = $('.header-search .location-autocomplete li');
					var total_li = li.length;
					$.each(li, function(index, val) {
						if($(this).hasClass('active') && index < (total_li - 1)) {
							$(this).removeClass('active').parent().find('li:eq('+(index + 1)+')').addClass('active');
							return false;
						}
					});
				}
			}
			if(e.keyCode == "13") { // enter key is pressed
				e.preventDefault();
				$('.header-search .location-autocomplete li.active').trigger('click');
			}
		} else {
			if(e.keyCode == "40" && $('.header-search .location-autocomplete li').length > 0) { // down key is pressed
				$('.header-search .location-autocomplete').show();
			}
		}
		if(e.keyCode == "9") { // tab key is pressed
			$('.header-search .fake-select-distance').trigger('click').focus();
			$('.header-search .location-autocomplete').hide();
		}
	});
// Header search END

// Generic Settings page form validation START
	// submit form
	$('.settings-form').on('submit', function(event) { event.preventDefault(); });
	$('.settings-form').on('click', '.submit-button-default', function(event) {
		var form = $('.settings-form');
		var form_data = form.serializeArray();
		var action = form.find('input[name="action"]').val();
		form.find('.err-msg, .form-msg').slideUp(200, function(){ $(this).text(''); });
		form.find('.input-err').removeClass('input-err');
		form.find('.submit-button').attr('class', function(i, c){ return c.replace(/(^|\s)submit-button-\S+/g, ''); }).addClass('submit-button-loading').find('.text-default').hide();
		$.post(wpvars.wpthemeurl+'/ajax/save-settings.php', { action: action, form_data: form_data }, function(raw_data, textStatus, xhr) {
			var is_err = false;
			var is_json = true;
			try {
				var data = JSON.parse(raw_data);
			} catch(err) {
				is_json = false;
			}
			if(is_json) {
				if(data.status == "err") {
					is_err = true;
					//main form error
					if(data.form_err) {
						form.find('.form-err-msg').html(data.form_err).slideDown(200);
					}
					//form field errors
					if(data.fields_err) {
						$.each(data.fields_err, function(index, val) {
							$('#'+index).addClass('input-err').siblings('.err-msg').text(val).slideDown(200);
						});
					}
				} else if(data.status == "ok") {
					if(data.form_ok) {
						form.find('.form-ok-msg').html(data.form_ok).slideDown(200);
					}
					form.find('.submit-button').attr('class', function(i, c){ return c.replace(/(^|\s)submit-button-\S+/g, ''); }).addClass('submit-button-done');
				}
			}

			if(is_err || !is_json || !raw_data) {
				form.find('.submit-button').attr('class', function(i, c){ return c.replace(/(^|\s)submit-button-\S+/g, ''); }).addClass('submit-button-err');
			}
			setTimeout(function() {
				form.find('.submit-button').attr('class', function(i, c){ return c.replace(/(^|\s)submit-button-\S+/g, ''); }).addClass('submit-button-default').find('.text-default').show();
			}, 2000);
		});
	});
// Generic Settings page form validation END

// Language settings page START
	// load language to edit
	if($('.language-settings').length) {
		if($('.language-settings input[name="edit_language"]').val() != 'none') {
			$('.language-settings .edit-language-area').empty();
			$('.language-settings .page-loader').show();
			$.get(wpvars.wpthemeurl+'/ajax/edit-languages-ajax.php?edit_language='+$('.language-settings input[name="edit_language"]').val(), function(data){
				$('.language-settings .page-loader').hide();
				$('.language-settings .edit-language-area').html(data);
				if($('.language-settings .page-admin').length) {
					$('.language-settings .edit-language-area .status-icons .delete').show();
				}
				check_all_fake_selects();
			});
		}
	}
	$('.language-settings input[name="edit_language"]').on('change', function(event) {
		$('.language-settings .edit-language-area').empty();
		if($(this).val() == "none") {
			return false;
		}
		$('.language-settings .add-new-language-popup').removeClass('active');
		$('.language-settings .add-new-language').slideUp('fast');
		$('.language-settings .page-loader').show();
		$.get(wpvars.wpthemeurl+'/ajax/edit-languages-ajax.php?edit_language='+$(this).val(), function(data){
			$('.language-settings .page-loader').hide();
			$('.language-settings .edit-language-area').html(data);
			if($('.language-settings .page-admin').length) {
				$('.language-settings .edit-language-area .status-icons .delete').show();
			}
			check_all_fake_selects();
		});
	});

	// add new language
	$('.language-settings .add-new-language-popup').on('click', function(event) {
		$('.language-settings .fake-select-edit-language .options .option').first().trigger('click');
		$('.language-settings .edit-language-area').empty();
		if($('.language-settings .add-new-language').is(':visible')) {
			$('.language-settings .add-new-language').slideUp('fast');
			$(this).removeClass('active');
		} else {
			$('.language-settings .add-new-language').slideDown('fast');
			$(this).addClass('active');
		}
	});
	$('.language-settings .add-new-language .close').on('click', function(event) {
		$('.language-settings .add-new-language-popup').removeClass('active');
		$(this).parent().slideUp('fast');
	});
	$('.add-new-language-form').on('submit', function(event) {
		event.preventDefault();
		var lang = $('.add-new-language-form #new_language').val();
		var form = $('.add-new-language-form');
		if(lang) {
			form.find('.err-msg').hide().empty();
			form.find('.submit-button').attr('class', function(i, c){ return c.replace(/(^|\s)submit-button-\S+/g, ''); }).addClass('submit-button-loading');
			$.ajax({
				type: "POST",
				url: wpvars.wpthemeurl+'/ajax/edit-languages-ajax.php',
				data: { action: 'add_new_language', lang: lang },
				cache: false,
				timeout: 30000, // in milliseconds
				success: function(raw_data) {
					var is_err = false;
					var is_json = true;
					try {
						var data = JSON.parse(raw_data);
					} catch(err) {
						is_json = false;
					}
					if(is_json && data != null) {
						if(data.status == 'ok') {
							$('.add-new-language').hide();
							form.find('#new_language').val('');
							$('.language-settings .edit-language-area').empty();
							$('.language-settings .page-loader').show();
							$('.fake-select-edit-language .options').append('<div data-value="'+data.lang_id+'" class="option">'+data.lang_name+'</div>');
							$('.fake-select-edit-language .options .option[data-value="'+data.lang_id+'"]').click();
							form.find('.submit-button').attr('class', function(i, c){ return c.replace(/(^|\s)submit-button-\S+/g, ''); }).addClass('submit-button-default');
						} else {
							is_err = true;
							if(data.form_err) {
								form.find('.err-msg').html(data.form_err).slideDown(200);
								form.find('.submit-button').attr('class', function(i, c){ return c.replace(/(^|\s)submit-button-\S+/g, ''); }).addClass('submit-button-err');
								setTimeout(function() {
									form.find('.submit-button').attr('class', function(i, c){ return c.replace(/(^|\s)submit-button-\S+/g, ''); }).addClass('submit-button-default');
								}, 2000);
							}
						}//if error
					}

					if(is_err || !is_json || !raw_data) {
						form.find('.submit-button').attr('class', function(i, c){ return c.replace(/(^|\s)submit-button-\S+/g, ''); }).addClass('submit-button-err');
						setTimeout(function() {
							form.find('.submit-button').attr('class', function(i, c){ return c.replace(/(^|\s)submit-button-\S+/g, ''); }).addClass('submit-button-default');
						}, 2000);
					}
				},
				error: function(request, status, err) {
					form.find('.submit-button').attr('class', function(i, c){ return c.replace(/(^|\s)submit-button-\S+/g, ''); }).addClass('submit-button-err');
					setTimeout(function() {
						form.find('.submit-button').attr('class', function(i, c){ return c.replace(/(^|\s)submit-button-\S+/g, ''); }).addClass('submit-button-default');
					}, 2000);
				}
			});
		} // if $lang
	});



	var savelangTimeout;
	$('.language-settings').on('input keyup', '.edit-language-form input', function(event) {
		if(!$(this).hasClass('select-search')) {
			clearTimeout(savelangTimeout);
			var input = $(this);
			input.removeClass('saved').siblings('.status-icons').find('.icon').fadeOut('fast');
			savelangTimeout = setTimeout(function() {
								save_language_words(input);
						}, 1500);
		}
	});
	$('.language-settings').on('focusout', '.edit-language-form input', function(event) {
		if(!$(this).hasClass('select-search') && !$(this).removeClass('saved')) {
			save_language_words($(this));
		}
	});
	$('.language-settings').on('change', '.fake-select-edit-flag input[name="language_flag"]', function(event) {
		save_language_words($(this));
		var parent = $(this).parents('.language-flags');
		parent.find('.status-icons .icon').hide();
		parent.find('.status-icons .loader').fadeIn('250', function() {
			setTimeout(function() {
				parent.find('.status-icons .loader').hide();
				parent.find('.status-icons .saved').fadeIn('250', function() {
					setTimeout(function() {
						parent.find('.status-icons .saved').fadeOut('250');
					}, 3000);
				});
			}, 2000);
		});
	});

	$('.language-settings').on('input keyup blur', '.language_url_field', function(event) {
		var url = $(this).val().replace(/[^a-z]/g,'');
		$(this).val(url);
		$(this).parents('.form-input').find('.help .text-val').text(url);
	});

	function save_language_words(input) {
		if(input.hasClass('saved')) {
			return false;
		}
		var lang = $('.language-settings .edit-language-area .edit-language-form').data('language');
		var word = input.val();
		var word_id = input.attr('id');
		input.addClass('saved');

		input.siblings('.status-icons').find('.saved').fadeOut('fast', function() {
			input.siblings('.status-icons').find('.loader').fadeIn('fast');
		});

		$.ajax({
			type: "POST",
			url: wpvars.wpthemeurl+'/ajax/edit-languages-ajax.php',
			data: { action: 'save_words', lang: lang, word: word, word_id: word_id },
			cache: false,
			timeout: 30000, // in milliseconds
			success: function(raw_data) {
				if(raw_data.trim() == "ok") {
					input.siblings('.status-icons').find('.loader').fadeOut('fast', function() {
						input.siblings('.status-icons').find('.saved').fadeIn('fast');
					});
				} else {
					input.removeClass('saved');
					input.siblings('.status-icons').find('.loader').fadeOut('fast', function() {
						input.siblings('.status-icons').find('.error').fadeIn('fast');
					});
					setTimeout(function() {
						input.siblings('.status-icons').find('.error').fadeOut('fast');
					}, 3000);
				}
			},
			error: function(request, status, err) {
				input.removeClass('saved');
				input.siblings('.status-icons').find('.loader').fadeOut('fast', function() {
					input.siblings('.status-icons').find('.error').fadeIn('fast');
				});
				setTimeout(function() {
					input.siblings('.status-icons').find('.error').fadeOut('fast');
				}, 3000);
			}
		});
	}

	$('.language-settings .edit-language-area').on('click', '.status-icons .delete', function(event) {
		var input = $(this).parents('.form-input').find('.input');
		var lang = $('.language-settings .edit-language-area .edit-language-form').data('language');
		var word_id = input.attr('id');
		$(this).parents('.form-input').remove();
		$('#separatorfor_'+word_id).remove();
		$.ajax({
			type: "POST",
			url: wpvars.wpthemeurl+'/ajax/edit-languages-ajax.php',
			data: { action: 'delete_word', lang: lang, word_id: word_id },
			cache: false,
			timeout: 20000, // in milliseconds
			success: function(raw_data) {
			},
			error: function(request, status, err) {
			}
		});
	});


	$('.language-settings .result').on('focus', 'input', function(event) {
		$(this).select();
	});
	$('.language-settings .result').on('cut copy', 'input', function(event) {
		$('.language-settings .admin-add-words #word').val('').focus();
	});
	$('.language-settings .admin-add-words').on('keydown', '#word', function(e) {
		if(e.keyCode == "13") { // enter key is pressed
			var input = $(this);
			e.preventDefault();
			var last_key = $('.result').data('last-key'); last_key++;
			var word = $(this).val();
			var is_duplicate;
			$('.language-settings .page-admin .lang-word').each(function(index, val) {
				if($(this).text() == word) {
					last_key = $(this).data('word-key');
					is_duplicate = true;
					return false;
				}
			});

			if(last_key > 0) {
				jsword = word.replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/[\\"']/g, '\\$&').replace(/\u0000/g, '\\0');
				$('.result').empty();
				$('.result').append('<input type="text" class="rad5" value="<?=_d(\''+jsword+'\','+last_key+')?>" /><div class="clear5"></div>');
				$('.result').append('<input type="text" class="rad5" value="_d(\''+jsword+'\','+last_key+')" /><div class="clear5"></div>');
				$('.result').append('<input type="text" class="rad5" value="_de(\''+jsword+'\','+last_key+');" /><div class="clear5"></div>');
				$('.result input').first().select();
				var code_added = "yes";
				if(is_duplicate) { return false; }

				$('.result').data('last-key', last_key);
				$('.language-settings .page-admin .lang-word').last().after('<div class="clear hide"></div><span class="l hide">'+last_key+'&nbsp;&nbsp;</span><div class="lang-word l hide" data-word-key="'+last_key+'">'+word+'</div><div class="clear hide"></div>');
			}

			$.ajax({
				type: "POST",
				url: wpvars.wpthemeurl+'/ajax/edit-languages-ajax.php',
				data: { action: 'admin_add_words', word: word },
				cache: false,
				timeout: 20000, // in milliseconds
				success: function(raw_data) {
					var data = JSON.parse(raw_data);
					if(code_added != "yes") {
						$('.result').empty();
						$('.result').data('last-key', data.last_key);
						$.each(data.codes, function(index, val) {
							$('.result').append('<input class="rad5" type="text" value="'+val+'" /><div class="clear5"></div>');
						});
						$('.result input').first().select();
						$('.language-settings .page-admin .lang-word').last().after('<div class="clear hide"></div><span class="l hide">'+data.last_key+'&nbsp;&nbsp;</span><div class="lang-word l hide" data-word-key="'+data.last_key+'">'+word+'</div><div class="clear hide"></div>');
					}
				},
				error: function(request, status, err) {
				}
			});
		}
	});

	$('.language-settings').on('click', '.delete-language', function(event) {
		var button = $(this);
		swal({
			title: button.data('swal-title'),
			text: button.data('swal-text'),
			type: "question",
			allowOutsideClick: 'true',
			showCancelButton: 'true',
			confirmButtonText: button.data('swal-yes'),
			confirmButtonColor: '#EF3A39',
			cancelButtonText: button.data('swal-cancel'),
			cancelButtonColor: '#b8c3d9'
		}).then(function() {
			var lang = $('.language-settings .edit-language-area .edit-language-form').data('language');
			$.post(wpvars.wpthemeurl+'/ajax/edit-languages-ajax.php', { action: 'delete_language', lang: lang });
			$('.language-settings .edit-language-area').empty();
			$('.language-settings .fake-select-edit-language .options .option').first().click();
			$('.language-settings .fake-select-edit-language [data-value="'+lang+'"]').remove();
		});
		resize_swal2();
	});

	$('.language-settings').on('click', '.import-language', function(event) {
		if($('.import-language-textarea-wrapper').is(':visible')) {
			return false;
		}
		var button = $(this);
		swal({
			title: button.data('swal-title'),
			text: button.data('swal-text'),
			type: "question",
			allowOutsideClick: 'true',
			showCancelButton: 'true',
			confirmButtonText: button.data('swal-yes'),
			confirmButtonColor: '#EF3A39',
			cancelButtonText: button.data('swal-cancel'),
			cancelButtonColor: '#b8c3d9'
		}).then(function() {
			$('.import-language-textarea-wrapper').toggle();
		});
		resize_swal2();
	});
	$('.language-settings').on('click', '.import-language-textarea-wrapper .close', function(event) {
		$('.import-language-textarea-wrapper').toggle();
	});
	$('.language-settings').on('click', '.import-language-textarea-wrapper .import-button', function(event) {
		var lang_text = $('.import-language-textarea').val();
		var lang_name = $('.language-settings .edit-language-area .edit-language-form').data('language');
		var button = $(this);
		var form = $(this).parent();
		form.find('.import-button-err').text('').hide();
		if(lang_text.length < 1) return false;

		if(button.hasClass('working-button')) {
			return false;
		}

		button.addClass('working-button');
		button.find('.icon').toggle();
		$.ajax({
			type: "POST",
			url: wpvars.wpthemeurl+'/ajax/edit-languages-ajax.php',
			data: { action: 'import_language', lang_text: lang_text, lang_name: lang_name },
			cache: false,
			timeout: 30000, // in milliseconds
			success: function(raw_data) {
				var is_err = false;
				var is_json = true;
				try {
					var data = JSON.parse(raw_data);
				} catch(err) {
					is_json = false;
				}
				if(is_json && data != null) {
					if(data.ok == 'ok') {
						button.removeClass('working-button');
						button.find('.icon').toggle();
						var textarea = $('.import-language-textarea-wrapper .import-language-textarea');
						swal({
							title: textarea.data('swal-title'),
							text: textarea.data('swal-text'),
							type: "success",
							allowOutsideClick: 'true',
							showCancelButton: 'false',
							confirmButtonText: textarea.data('swal-yes'),
							confirmButtonColor: '#EF3A39',
							cancelButtonText: textarea.data('swal-cancel'),
							cancelButtonColor: '#b8c3d9',
							showLoaderOnConfirm: true,
							preConfirm: function() {
								return new Promise(function(resolve, reject) {
									location.reload();
								});
							}
						});
						resize_swal2();
					} else {
						is_err = true;
						if(data.err) {
							form.find('.import-button-err').html(data.err).slideDown(200);
						}
					}//if error
				}

				if(is_err || !is_json || !raw_data) {
					button.removeClass('working-button');
					button.find('.icon').toggle();
				}
			},
			error: function(request, status, err) {
				button.removeClass('working-button');
				button.find('.icon').toggle();
			}
		});
	});

	$('.language-settings').on('click', '.export-language', function(event) {
		var button = $(this);
		if(button.hasClass('working-button')) {
			return false;
		} else {
			button.addClass('working-button');
		}
		$('.export-language-textarea-wrapper').remove();
		button.find('.icon').toggle();
		$.get(wpvars.wpthemeurl+'/ajax/edit-languages-ajax.php?export_language='+$('.language-settings input[name="edit_language"]').val(), function(data){
			button.removeClass('working-button').find('.icon').toggle();
			$('.language-settings .edit-language-form .import-language-textarea-wrapper').after(data);
		});
	});
	$('.language-settings').on('focus', '.export-language-textarea', function(event) {
		$(this).select();
	});
	$('.language-settings').on('click', '.export-language-textarea-wrapper .close', function(event) {
		$('.export-language-textarea-wrapper').remove();
	});
// Language settings page END

// Edit account page START
	// delete account
	$('.settings-page.edit-account .page-content .delete-account-wrapper .delete-account').on('click', function(event) {
		var button = $(this);
		swal({
			title: button.data('swal-title'),
			text: button.data('swal-text'),
			type: "warning",
			allowOutsideClick: 'true',
			showCancelButton: 'true',
			confirmButtonText: button.data('swal-yes'),
			confirmButtonColor: '#EF3A39',
			cancelButtonText: button.data('swal-cancel'),
			cancelButtonColor: '#b8c3d9',
			showLoaderOnConfirm: 'true',
			preConfirm: function() {
				return new Promise(function(resolve, reject) {
					$.ajax({
						type: "POST",
						url: wpvars.wpthemeurl+'/ajax/save-settings.php',
						data: { action: 'delete-account', form_data: [{name: 'userid', value: button.data('user-id') }] },
						cache: false,
						timeout: 30000, // in milliseconds
						success: function(raw_data) {
							var is_err = false;
							var is_json = true;
							try {
								var data = JSON.parse(raw_data);
							} catch(err) {
								is_json = false;
							}
							if(is_json && data != null) {
								if(data.status == 'ok') {
									resolve();
								} else {
									if(data.form_err) {
										swal({
											text: data.form_err,
											type: "error",
											allowOutsideClick: 'true',
											confirmButtonText: button.data('swal-ok'),
											confirmButtonColor: '#EF3A39'
										});
									}
								}//if error
							}

							if(!is_json || !raw_data) {
								swal({
									text: button.data('swal-error'),
									type: "error",
									allowOutsideClick: 'true',
									confirmButtonText: button.data('swal-ok'),
									confirmButtonColor: '#EF3A39'
								});
								resize_swal2();
							}
						},
						error: function(request, status, err) {
							swal({
								text: button.data('swal-error'),
								type: "error",
								allowOutsideClick: 'true',
								confirmButtonText: button.data('swal-ok'),
								confirmButtonColor: '#EF3A39'
							});
							resize_swal2();
						}
					});
				});
			}
		}).then(function() {
			location.reload();
			swal({
				text: button.data('swal-account-deleted'),
				type: "success",
				allowOutsideClick: 'true',
				confirmButtonText: button.data('swal-ok'),
				confirmButtonColor: '#EF3A39'
			});
			resize_swal2();
		});
		resize_swal2();
	}); // account
// Edit account page END

// Add demo ads START
	// add demo ads
	$('.edit-demo-ads .add-demo-data-form .create-demo-ads').on('click', function(e) {
		e.preventDefault();

		if($(this).hasClass('button-active')) return false;

		var button = $(this);
		var form = $('.edit-demo-ads .add-demo-data-form');
		button.addClass('button-active');
		button.attr('class', function(i, c){ return c.replace(/(^|\s)submit-button-\S+/g, ''); }).addClass('submit-button-loading');
		form.find('.form-ok').hide();
		$.ajax({
			type: "POST",
			url: wpvars.wpthemeurl+'/ajax/save-settings.php',
			data: { action: 'add_demo_ads' },
			cache: false,
			timeout: 300000, // in milliseconds
			success: function(data) {
				if(data.trim() == "ok") {
					$('.form-ok-added').show();
					button.attr('class', function(i, c){ return c.replace(/(^|\s)submit-button-\S+/g, ''); }).addClass('submit-button-done');
					setTimeout(function() {
						button.hide();
						$('.edit-demo-ads .add-demo-data-form .delete-demo-ads').show();
						button.attr('class', function(i, c){ return c.replace(/(^|\s)submit-button-\S+/g, ''); }).addClass('submit-button-default');
					}, 3000);
				} else{
					button.attr('class', function(i, c){ return c.replace(/(^|\s)submit-button-\S+/g, ''); }).addClass('submit-button-err');
					setTimeout(function() {
						button.attr('class', function(i, c){ return c.replace(/(^|\s)submit-button-\S+/g, ''); }).addClass('submit-button-default');
					}, 3000);
				}
			},
			complete: function() {
				button.removeClass('button-active');
				button.attr('class', function(i, c){ return c.replace(/(^|\s)submit-button-\S+/g, ''); }).addClass('submit-button-default');
			}
		});
	});

	// remove demo ads
	$('.edit-demo-ads .add-demo-data-form .delete-demo-ads').on('click', function(e) {
		e.preventDefault();

		if($(this).hasClass('button-active')) return false;

		var button = $(this);
		var form = $('.edit-demo-ads .add-demo-data-form');
		button.addClass('button-active');
		button.attr('class', function(i, c){ return c.replace(/(^|\s)submit-button-\S+/g, ''); }).addClass('submit-button-loading');
		form.find('.form-ok').hide();
		$.ajax({
			type: "POST",
			url: wpvars.wpthemeurl+'/ajax/save-settings.php',
			data: { action: 'remove_demo_ads' },
			cache: false,
			timeout: 300000, // in milliseconds
			success: function(data) {
				if(data.trim() == "ok") {
					$('.form-ok-removed').show();
					button.attr('class', function(i, c){ return c.replace(/(^|\s)submit-button-\S+/g, ''); }).addClass('submit-button-done');
					setTimeout(function() {
						button.hide();
						$('.edit-demo-ads .add-demo-data-form .create-demo-ads').show();
						button.attr('class', function(i, c){ return c.replace(/(^|\s)submit-button-\S+/g, ''); }).addClass('submit-button-default');
					}, 3000);
				} else{
					button.attr('class', function(i, c){ return c.replace(/(^|\s)submit-button-\S+/g, ''); }).addClass('submit-button-err');
					setTimeout(function() {
						button.attr('class', function(i, c){ return c.replace(/(^|\s)submit-button-\S+/g, ''); }).addClass('submit-button-default');
					}, 3000);
				}
			},
			complete: function() {
				button.removeClass('button-active');
				button.attr('class', function(i, c){ return c.replace(/(^|\s)submit-button-\S+/g, ''); }).addClass('submit-button-default');
			}
		});
	});
// Add demo ads END

	// Form help icon tooltip
	$(document).on({
		mouseenter: function () {
			var document_width = $(document).width();
			var tooltip_text = $(this).find('.tooltip-text');
			$(this).css('z-index', '3');
			tooltip_text.removeClass('hide');
			if(document_width < (tooltip_text.offset().left + tooltip_text.width()) ) {
				tooltip_text.addClass('tooltip-text-right');
			}
		},
		mouseleave: function () {
			$(this).css('z-index', '');
			$(this).find('.tooltip-text').removeClass('tooltip-text-right').addClass('hide');
		}
	}, ".help-tooltip");


	// show/hide sortable fields in mobile
	$('.content').on('click', '.mobile-sidebar .sortable-fields .sortable h4', function(event) {
		$(this).parent().toggleClass('active-sortable-field');
		$(this).find('.sorting-icon-mobile').toggle('fast');
		$(this).siblings('.hide-sortable-in-mobile').toggle('fast');
		$(this).siblings('.show-more-filters').toggle('fast');
	});

	$('.sidebar').on('click', '.sortable .icon.icon-checkbox', function(event) {
		if(!$(this).hasClass('no-more-clicks')) {
			$(this).addClass('icon-checkbox-selected icon-for-selected no-more-clicks').removeClass('icon-checkbox');
		}
	});
	$('.sidebar').on('click', '.sortable .icon.icon-checkbox', function(event) {
		if(!$(this).hasClass('no-more-clicks')) {
			$(this).addClass('icon-checkbox no-more-clicks').removeClass('icon-checkbox-selected icon-for-selected');
		}
	});


// Post/Edit/Delete review form START
	$('.seller-and-reviews .add-review-popup').on('click', function(event) {
		if($(this).hasClass('add-review-disabled')) return false;
		$('html, body').animate({ scrollTop: $('.user-items-wrapper').offset().top }, 200);
		$('.user-items-wrapper .user-items, .user-items-wrapper .author-reviews-section').hide();
		$('.user-items-wrapper .add-user-review').fadeIn('100');
		setTimeout(function() {
			$('.user-items-wrapper .add-user-review .add-user-review-form .review-textarea').attr('style', '').height( $('.user-items-wrapper .add-user-review .add-user-review-form .review-textarea')[0].scrollHeight );
		}, 10);
	});
	$('.author-page .add-user-review .close').on('click', function(event) {
		$('.user-items-wrapper .user-items, .user-items-wrapper .author-reviews-section').show();
		$('.user-items-wrapper .add-user-review').hide();
	});
	$('.author-page .add-user-review .review-posted-successfully .message .edit-your-review').on('click', function(event) {
		$('.user-items-wrapper .user-items, .user-items-wrapper .author-reviews-section').hide();
		$('.user-items-wrapper .add-user-review .add-user-review-form').fadeIn('100');
		$('.user-items-wrapper .review-posted-successfully').hide();
		setTimeout(function() {
			$('.user-items-wrapper .add-user-review .add-user-review-form .review-textarea').attr('style', '').height( $('.user-items-wrapper .add-user-review .add-user-review-form .review-textarea')[0].scrollHeight );
		}, 10);
	});

	// hovering over stars
	$('.add-user-review-form .stars-wrapper .star').hover(function() {
		var index = $(this).index() + 1;
		$(this).siblings('.star').andSelf().addClass('star-disabled').slice(0,index).removeClass('star-disabled');
	}, function() { /* do nothing */ });
	$('.add-user-review-form .stars-wrapper').hover(function() {
	}, function() {
		$(this).find('.star').addClass('star-disabled').slice(0,$(this).find('.star-input').val()).removeClass('star-disabled');
	});

	// when we load the page and the stars already have a value
	if($('.add-user-review-form .stars-wrapper .star-input').length) {
		$('.add-user-review-form .stars-wrapper .star-input').each(function(index, el) {
			$(this).siblings('.star').addClass('star-disabled').slice(0,$(this).val()).removeClass('star-disabled');
		});
	}

	// selecting a star
	$('.add-user-review-form .stars-wrapper .star').on('click', function(event) {
		var index = $(this).index() + 1;
		$(this).siblings('.star-input').val(index);
	});

	// review writing and limiting of characters
	if($('.add-user-review-form .review-textarea').length) {
		if($('.add-user-review-form .review-textarea').val().length > 0) {
			var textarea = $('.add-user-review-form .review-textarea');
			var limit = $('.add-user-review-form .review-text-char-limit').data('char-limit');
			textarea.val(textarea.val().substring(0,limit));
			textarea.siblings('.review-text-char-limit').find('.limit').text(limit - textarea.val().length).parent().show();
		}
	}
	$('.add-user-review-form .review-textarea').on('keyup', function(event) {
		var limit = $('.add-user-review-form .review-text-char-limit').data('char-limit');
		$(this).val($(this).val().substring(0,limit));
		$(this).siblings('.review-text-char-limit').find('.limit').text(limit - $(this).val().length).parent().show();
	});

	// save/update review
	$(document).on('click', '.add-user-review-form .submit-button-default', function(event) {
		event.preventDefault();
		var form = $(this).parents('.add-user-review-form');
		var form_data = form.find(':input').serializeArray();
		var action = form.find('input[name="action"]').val();
		form.find('.err-msg, .form-msg').slideUp(200, function() { $(this).text(''); });
		form.find('.input-err').removeClass('input-err');
		form.find('.submit-button').attr('class', function(i, c){ return c.replace(/(^|\s)submit-button-\S+/g, ''); }).addClass('submit-button-loading');
		$.ajax({
			type: "POST",
			url: wpvars.wpthemeurl+'/ajax/save-review.php',
			data: { action: action, form_data: form_data },
			cache: false,
			timeout: 20000, // in milliseconds
			success: function(raw_data) {
				var is_err = false;
				var is_json = true;
				try {
					var data = JSON.parse(raw_data);
				} catch(err) {
					is_json = false;
				}
				if(is_json && data != null) {
					if(data.status == 'ok') {
						form.find('.submit-button').attr('class', function(i, c){ return c.replace(/(^|\s)submit-button-\S+/g, ''); }).addClass('submit-button-default');
						if(action == "save_review") {
							$("<input type='hidden' name='review_id' value='"+data.review_id+"' />").prependTo(form);
							form.find('input[name="action"]').val('update_review2');
							$('.review-posted-successfully .message .user-rating-star .rating').text(data.rating);
							$('.add-user-review-form').slideUp('fast');
							$('.add-user-review .review-posted-successfully').slideDown('fast');
							setTimeout(function() {
								if($('.user-items-wrapper .author-reviews-section').length) {
									location.reload();
									return false;
								}
								$('.add-user-review').fadeOut('150', function() {
									$('.user-items-wrapper .user-items, .user-items-wrapper .author-reviews-section').slideDown('100');
								});
							}, 5000);
						} else if(action == "update_review2") {
							$('.review-posted-successfully .message .user-rating-star .rating').text(data.rating);
							$('.add-user-review-form').slideUp('fast');
							$('.add-user-review .review-posted-successfully').slideDown('fast');
						} else if(action == "update_review") {
							form.hide().parents('.review').find('.review-meta-info').show();
							form.parents('.review').find('.review-text-wrapper .review-text').html(form.find('textarea.review-textarea').val().replace(/([^>\r\n]?)(\r\n|\n\r|\r|\n)/g, '$1' + '<br ' + '/>' + '$2'));
							form.parents('.review').find('.review-text-wrapper').show();
							form.parents('.review').find('.review-meta-info .user-rating-star .rating').text(data.rating);

							form.parents('.review').find('.review-text-wrapper .stars-wrapper:eq(0) .star-wrapper .star .icon').removeClass('star-yellow');
							form.parents('.review').find('.review-text-wrapper .stars-wrapper:eq(0) .star-wrapper:lt('+form.find('input[name="delivery"]').val()+') .star .icon').addClass('star-yellow');

							form.parents('.review').find('.review-text-wrapper .stars-wrapper:eq(1) .star-wrapper .star .icon').removeClass('star-yellow');
							form.parents('.review').find('.review-text-wrapper .stars-wrapper:eq(1) .star-wrapper:lt('+form.find('input[name="responsiveness"]').val()+') .star .icon').addClass('star-yellow');

							form.parents('.review').find('.review-text-wrapper .stars-wrapper:eq(2) .star-wrapper .star .icon').removeClass('star-yellow');
							form.parents('.review').find('.review-text-wrapper .stars-wrapper:eq(2) .star-wrapper:lt('+form.find('input[name="friendliness"]').val()+') .star .icon').addClass('star-yellow');

							form.parents('.review').find('.seller-reply').toggleClass('col-90 col-100');
						}
					} else {
						is_err = true;
						if(data.form_err) {
							form.find('.form-err-msg').html(data.form_err).slideDown(200);
						}
						if(data.fields_err) {
							$.each(data.fields_err, function(index, val) {
								$('.err-msg-'+index).text(val).slideDown(200);
							});
						}
					}//if error
				}

				if(is_err || !is_json || !raw_data) {
					form.find('.submit-button').attr('class', function(i, c){ return c.replace(/(^|\s)submit-button-\S+/g, ''); }).addClass('submit-button-err');
					setTimeout(function() {
						form.find('.submit-button').attr('class', function(i, c){ return c.replace(/(^|\s)submit-button-\S+/g, ''); }).addClass('submit-button-default');
					}, 2000);
				}
			},
			error: function(request, status, err) {
				form.find('.submit-button').attr('class', function(i, c){ return c.replace(/(^|\s)submit-button-\S+/g, ''); }).addClass('submit-button-err');
				setTimeout(function() {
					form.find('.submit-button').attr('class', function(i, c){ return c.replace(/(^|\s)submit-button-\S+/g, ''); }).addClass('submit-button-default');
				}, 2000);
			}
		});
	}); // save/update review

	// review writing and limiting of characters for seller on reply to review
	if($('.author-reviews-section .reply-textarea').length) {
		$('.author-reviews-section .reply-textarea').each(function(index, el) {
			if($(this).val().length > 0) {
				var textarea = $(this);
				var limit = textarea.siblings('.review-text-char-limit').data('char-limit');
				textarea.val(textarea.val().substring(0,limit));
				textarea.siblings('.review-text-char-limit').find('.limit').text(limit - textarea.val().length).parent().show();
			}
		});
	}
	$('.author-reviews-section .reply-textarea').on('keyup', function(event) {
		var limit = $(this).siblings('.review-text-char-limit').data('char-limit');
		$(this).val($(this).val().substring(0,limit));
		$(this).siblings('.review-text-char-limit').find('.limit').text(limit - $(this).val().length).parent().show();
	});

	$('.author-page .author-reviews-section .review .reply .action-button').on('click', function(event) {
		event.preventDefault();
		$(this).parent().find('.reply-button,.close-button,.reply-area').slideToggle('fast');
	});

	// seller reply to review
	$(document).on('click', '.author-page .author-reviews-section .review .seller-reply-form .submit-button', function(event) {
		event.preventDefault();
		var form = $(this).parents('.form-styling');
		var review_text = form.find('.reply-textarea').val();
		var review_id = $(this).data('review-id');
		if(review_text.length < 1 || review_id < 1) return false;

		form.find('.submit-button').attr('class', function(i, c){ return c.replace(/(^|\s)submit-button-\S+/g, ''); }).addClass('submit-button-loading');
		$.ajax({
			type: "POST",
			url: wpvars.wpthemeurl+'/ajax/save-review.php',
			data: { action: 'reply_to_review', review_text: review_text, review_id: review_id  },
			cache: false,
			timeout: 20000, // in milliseconds
			success: function(raw_data) {
				var is_err = false;
				var is_json = true;
				try {
					var data = JSON.parse(raw_data);
				} catch(err) {
					is_json = false;
				}
				if(is_json && data != null) {
					if(data.status == 'ok') {
						var seller_reply = form.parents('.review').find('.seller-reply');
						seller_reply.find('.seller-reply-text').text(review_text);
						seller_reply.slideDown('fast');
						form.slideUp('fast', function() {
							form.remove();
						});
					} else {
						is_err = true;
					}//if error
				}

				if(is_err || !is_json || !raw_data) {
					form.find('.submit-button').attr('class', function(i, c){ return c.replace(/(^|\s)submit-button-\S+/g, ''); }).addClass('submit-button-err');
					setTimeout(function() {
						form.find('.submit-button').attr('class', function(i, c){ return c.replace(/(^|\s)submit-button-\S+/g, ''); }).addClass('submit-button-default');
					}, 2000);
				}
			},
			error: function(request, status, err) {
				form.find('.submit-button').attr('class', function(i, c){ return c.replace(/(^|\s)submit-button-\S+/g, ''); }).addClass('submit-button-err');
				setTimeout(function() {
					form.find('.submit-button').attr('class', function(i, c){ return c.replace(/(^|\s)submit-button-\S+/g, ''); }).addClass('submit-button-default');
				}, 2000);
			}
		});
	}); // seller reply to review

	// seller update reply to review
	$(document).on('click', '.author-page .author-reviews-section .review .seller-reply-form-update .submit-button', function(event) {
		event.preventDefault();
		var form = $(this).parents('.form-styling');
		var review_text = form.find('.reply-textarea').val();
		var review_id = $(this).data('review-id');
		if(review_text.length < 1 || review_id < 1) return false;

		form.find('.submit-button').attr('class', function(i, c){ return c.replace(/(^|\s)submit-button-\S+/g, ''); }).addClass('submit-button-loading');
		$.ajax({
			type: "POST",
			url: wpvars.wpthemeurl+'/ajax/save-review.php',
			data: { action: 'update_seller_review', review_text: review_text, review_id: review_id  },
			cache: false,
			timeout: 20000, // in milliseconds
			success: function(raw_data) {
				var is_err = false;
				var is_json = true;
				try {
					var data = JSON.parse(raw_data);
				} catch(err) {
					is_json = false;
				}
				if(is_json && data != null) {
					if(data.status == 'ok') {
						var seller_reply = form.parents('.seller-reply');
						seller_reply.find('.seller-reply-text').html(review_text.replace(/([^>\r\n]?)(\r\n|\n\r|\r|\n)/g, '$1' + '<br ' + '/>' + '$2')).slideDown('fast');
						seller_reply.find('.seller-reply-form-update').hide();
						form.find('.submit-button').attr('class', function(i, c){ return c.replace(/(^|\s)submit-button-\S+/g, ''); }).addClass('submit-button-default');
					} else {
						is_err = true;
					}//if error
				}

				if(is_err || !is_json || !raw_data) {
					form.find('.submit-button').attr('class', function(i, c){ return c.replace(/(^|\s)submit-button-\S+/g, ''); }).addClass('submit-button-err');
					setTimeout(function() {
						form.find('.submit-button').attr('class', function(i, c){ return c.replace(/(^|\s)submit-button-\S+/g, ''); }).addClass('submit-button-default');
					}, 2000);
				}
			},
			error: function(request, status, err) {
				form.find('.submit-button').attr('class', function(i, c){ return c.replace(/(^|\s)submit-button-\S+/g, ''); }).addClass('submit-button-err');
				setTimeout(function() {
					form.find('.submit-button').attr('class', function(i, c){ return c.replace(/(^|\s)submit-button-\S+/g, ''); }).addClass('submit-button-default');
				}, 2000);
			}
		});
	}); // seller update reply to review

	// close review box
	$('.add-user-review-form .close').on('click', function(event) {
		var review = $(this).parents('.review');
		$(this).parent().hide();
		review.find('.review-meta-info').show();
		review.find('.review-text-wrapper').show();
		review.find('.edit-review-form').hide();
		review.find('.seller-reply').toggleClass('col-90 col-100');
	});

	// buyer edit review
	$('.author-page .author-reviews-section .review .review-meta-info .edit-review').on('click', function(event) {
		var review = $(this).parents('.review');
		review.find('.review-meta-info').hide();
		review.find('.review-text-wrapper').hide();
		review.find('.edit-review-form').slideDown('100');
		review.find('.seller-reply').toggleClass('col-90 col-100');
		setTimeout(function() {
			review.find('.edit-review-form textarea').attr('style', '').height( review.find('.edit-review-form textarea')[0].scrollHeight );
		}, 10);
	});

	// edit seller reply
	$('.author-page .review .seller-reply .edit-review').on('click', function(event) {
		var div = $(this).parents('.seller-reply');
		div.find('.seller-reply-text').hide();
		div.find('.seller-reply-form-update').show();
		setTimeout(function() {
			div.find('.seller-reply-form-update textarea').attr('style', '').height( div.find('.seller-reply-form-update textarea')[0].scrollHeight );
		}, 10);
	});
	// cancel seller reply
	$('.author-page .review .seller-reply .seller-reply-form-update .cancel-reply').on('click', function(event) {
		var div = $(this).parents('.seller-reply');
		div.find('.seller-reply-text').show();
		div.find('.seller-reply-form-update').hide();
	});

	$('.author-page .delete-review').on('click', function(event) {
		var button = $(this);
		var review_id, action;
		var swal_ok_title = $(this).data('swal-ok-title');
		var swal_ok_text = $(this).data('swal-ok-text');
		if($(this).data('review-id') > 0) {
			review_id = $(this).data('review-id');
			action = "delete_review";
		}
		if($(this).data('review-reply-id') > 0) {
			review_id = $(this).data('review-reply-id');
			action = "delete_reply";
		}
		swal({
			title: $(this).data('swal-title'),
			text: $(this).data('swal-text'),
			type: "question",
			allowOutsideClick: 'true',
			showCancelButton: 'true',
			confirmButtonText: $(this).data('swal-button'),
			confirmButtonColor: '#EF3A39',
			cancelButtonColor: '#b8c3d9',
			cancelButtonText: $(this).data('swal-cancel'),
			showLoaderOnConfirm: true,
			preConfirm: function() {
				return new Promise(function(resolve, reject) {
					$.get(wpvars.wpthemeurl+'/ajax/save-review.php?action='+action+'&review_id='+review_id, function(data) {
						if(data.trim() == "ok") {
							if(action == "delete_review") {
								button.parents('.review').slideUp('fast');
							}
							if(action == "delete_reply") {
								button.parents('.seller-reply').slideUp('fast');
							}
						}
						resolve();
						location.reload();
					});
				});
			},
		}).then(function() {
			swal({title: swal_ok_title, text: swal_ok_text, type: "success", timer: '10000', showConfirmButton: false});
			resize_swal2();
		});
		resize_swal2();
	});
// Post review form END

	author_reviews_section_mobile();
	$(window).resize(function() {
		author_reviews_section_mobile();
	});
	function author_reviews_section_mobile() {
		if($('.author-page .author-reviews-section h3').length && $('.author-page .author-reviews-section .back-to-the-ads').length) {
			$('.author-page .author-reviews-section .author-reviews-section-title').removeClass('author-reviews-section-title-mobile');
			if($('.author-page .author-reviews-section .back-to-the-ads').offset().top > $('.author-page .author-reviews-section h3').offset().top) {
				$('.author-page .author-reviews-section .author-reviews-section-title').addClass('author-reviews-section-title-mobile');
			}

			$('.author-page .author-reviews-section h3').removeClass('h3-mobile');
			if($('.author-page .author-reviews-section h3 .last-word').offset().top > $('.author-page .author-reviews-section h3 .blue').offset().top) {
				$('.author-page .author-reviews-section h3').addClass('h3-mobile');
			}
		}
	}

	$('.author-page .seller-and-reviews .user-account-links').on('click', function(event) {
		$(this).find('.arrow').toggle();
		$(this).find('.user-account-links-inner').toggle();
	});

	$('.author-page .get-verified-section button').on('click', function(event) {
		var button = $(this);
		if(!button.hasClass('submit-button-default')) return false;
		button.attr('class', function(i, c){ return c.replace(/(^|\s)submit-button-\S+/g, ''); }).addClass('submit-button-loading');
		$.post(wpvars.wpthemeurl+'/ajax/save-settings.php', {action: 'ask-for-verification'}, function(data) {
			if(data.trim() == "ok") {
				button.attr('class', function(i, c){ return c.replace(/(^|\s)submit-button-\S+/g, ''); }).addClass('submit-button-done');
			} else {
				button.attr('class', function(i, c){ return c.replace(/(^|\s)submit-button-\S+/g, ''); }).addClass('submit-button-err');
				setTimeout(function() {
					button.attr('class', function(i, c){ return c.replace(/(^|\s)submit-button-\S+/g, ''); }).addClass('submit-button-default');
				}, 4000);
			}
		});
	});

	// format checkboxes
	format_fake_checkboxes = function() {
		$.each($('.form-styling'), function(index, el) {
			$.each($(this).find('.form-input input[type="checkbox"]'), function(index, el) {
				if($(this).siblings('.fake-checkbox').length) return false;
				$(this).hide().parent().prepend('<span class="fake-checkbox icon-checkbox l"></span>');
				$(this).parent().find('.fake-checkbox').removeClass('icon-checkbox').addClass('icon-checkbox-selected');
				if($(this).is(':checked')) {
					$(this).parent().find('.fake-checkbox').removeClass('icon-checkbox').addClass('icon-checkbox-selected');
				} else {
					$(this).parent().find('.fake-checkbox').removeClass('icon-checkbox-selected').addClass('icon-checkbox');
				}
			});
		});
			
	}
	$('body').on('change', '.form-styling .form-input input[type="checkbox"]', function(event) {
		$(this).parent().find('.fake-checkbox').toggleClass('icon-checkbox icon-checkbox-selected');
	});
	format_fake_checkboxes();

	// format radio
	format_fake_radio = function() {
		$.each($('.form-styling .form-input input[type="radio"]'), function(index, el) {
			if($(this).siblings('.fake-radio').length) return false;
			$(this).hide().parent().prepend('<span class="fake-radio icon-radio l"></span>');
			$(this).parent().find('.fake-radio').removeClass('icon-radio').addClass('icon-radio-selected');
			if($(this).is(':checked')) {
				$(this).parent().find('.fake-radio').removeClass('icon-radio').addClass('icon-radio-selected');
			} else {
				$(this).parent().find('.fake-radio').removeClass('icon-radio-selected').addClass('icon-radio');
			}
		});
	}
	$('body').on('change', '.form-styling .form-input input[type="radio"]', function(event) {
		$(this).parents('.radio-parent-wrapper').find('.fake-radio').removeClass('icon-radio-selected').addClass('icon-radio');
		$(this).parent().find('.fake-radio').toggleClass('icon-radio icon-radio-selected');
	});
	format_fake_radio();

	resize_items2();
	$(window).resize(function() {
		resize_items2()
	});
	function resize_items2() {
		if($('.loop .items-wrap .item2').length && !$('body').hasClass('is-phone')) {
			$('.loop .items-wrap .items2-separator').remove();

			var items = $('.loop .items-wrap .item2');
			var offset = items.first().offset().top;
			items.each(function(index, el) {
				if(offset < $(this).offset().top) {
					$(this).before('<div class="clear items2-separator"></div>')
					offset = $(this).offset().top;
				}
			});
		}
	}
});