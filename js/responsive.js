jQuery(document).ready(function($) {
	var windowWidth;

	// vertical center anything
	vcenter = function() {
		$('.vcenter').each(function(index, el) {
			$(this).css('margin-top', ($(this).parent().outerHeight() - $(this).outerHeight()) / 2);
		});
		$('.vcenter2').each(function(index, el) {
			$(this).css('margin-top', ($(this).parent().height() - $(this).outerHeight()) / 2);
		});
	}

	create_responsive();

	var resizeId;
	$(window).resize(function() {
		clearTimeout(resizeId);
		resizeId = setTimeout(function(){ create_responsive(); }, 500);
	});
	$('.header-search input[name="c"]').change(function(event) {
		search_form_resizing();
	});


	function create_responsive() {
        if($(window).width() != windowWidth) {
            windowWidth = $(window).width();
        } else {
        	return false;
        }
		var isphone_size = parseInt('901'); // when should the mobile version for phones kick in

		// header user-menu-avatar START
		if($('header .user-menu .user-info .text').outerWidth() < $('header .user-menu .user-info .name').outerWidth()) {
			$('header .user-menu .user-info .name').css('width', $('header .user-menu .user-info .text').outerWidth()+'px');
		}
		// header user-menu-avatar END

		// header menu-nav START
		$('header nav').removeClass('nav-mobile1');
		$('header nav').removeClass('nav-mobile2');
		$('header .header-content').removeClass('header-content-mobile');
		$('header nav .main-nav').show();
		$('header .main-nav ul').css("display", "");
		$('header .main-nav li a .icon').hide();
		$('header .main-nav li a .main-icon').show();
		if($('header .logo').length) {
			if($('header .logo').offset().top > ($('header nav').offset().top + 15)) {
				$('header nav').addClass('nav-mobile1');
				$('header nav .main-nav').hide();

				setTimeout(function() {
					if($('.is-phone .user-menu').length) {
						$('.is-phone .nav-button-mobile').removeClass('nav-button-mobile-small');
						$('.is-phone .fake-select-header-language-chooser').removeClass('fake-select-header-language-chooser-small');
						$('.is-phone .user-menu').removeClass('user-menu-small');
						if($('.is-phone .nav-button-mobile').offset().top < $('.is-phone .user-menu').offset().top) {
							$('.is-phone .nav-button-mobile').addClass('nav-button-mobile-small');
							$('.is-phone .user-menu').addClass('user-menu-small');
							$('.is-phone .fake-select-header-language-chooser').addClass('fake-select-header-language-chooser-small');
						}
					}

					if($('.is-phone .register-login').length) {
						$('.is-phone .nav-button-mobile').removeClass('nav-button-mobile-small');
						if($('.is-phone .nav-button-mobile').offset().top < $('.is-phone .register-login').offset().top) {
							$('.is-phone .nav-button-mobile').addClass('nav-button-mobile-small');
						}
					}
				}, 10);

				if($('header .logo').offset().top > ($('.nav-button-mobile').offset().top + 15)) {
					$('header nav').removeClass('nav-mobile1').addClass('nav-mobile2');
					$('header .header-content').addClass('header-content-mobile');
				}
			}
		}
		// header menu-nav END

		// search and "post new ad" button START
		// this is a global function so we can use it for the change() event of the category dropdown
		// that way we only run the code in this function and not the whole create_responsive() function
		search_form_resizing=function() {
			if($('header .nav2 .header-search').length) {
				if($('.nav2').hasClass('nav2-mobile1')) { $('.nav2').removeClass('nav2-mobile1'); }
				if($('.nav2').hasClass('nav2-mobile2')) { $('.nav2').removeClass('nav2-mobile2'); }
				if($('.nav2 .header-search').hasClass('header-search-stretched')) { $('.nav2 .header-search').removeClass('header-search-stretched'); }
				if($('header .nav2 .postnew-button').hasClass('stretch')) { $('header .nav2 .postnew-button').removeClass('stretch'); }
				$('header .nav2 .postnew-button .icon').show();

				if($('header .nav2 .header-search .keyword').offset().top > ($('header .nav2 .postnew-button').offset().top + 10)) {
					$('.nav2').addClass('nav2-mobile1');
					if(($('header .nav2 .header-search .keyword').offset().top + 10) < $('header .nav2 .search-button').offset().top) {
						$('.nav2').addClass('nav2-mobile2');

						if(!$('.nav2-mobile2 .keyword').data('original-width')) {
							$('.nav2-mobile2 .keyword').data('original-width', $('.nav2-mobile2 .keyword').outerWidth())
							var original_width = $('.nav2-mobile2 .keyword').outerWidth();
						} else {
							var original_width = $('.nav2-mobile2 .keyword').data('original-width');
						}

						var button_category = $('.nav2-mobile2 .fake-select-header-category-chooser').outerWidth();
						var button_distance = $('.nav2-mobile2 .fake-select-distance').outerWidth(true);
						var button_search = $('.nav2-mobile2 .search-button').outerWidth(true);

						var form_size = (original_width * 2) + button_category + button_distance + button_search + 30;

						if(button_category < button_distance) {
							button_category = button_distance;
						}

						var keyword_width = $('.nav2-mobile2 .header-search').outerWidth() - button_category;
						var location_width = $('.nav2-mobile2 .header-search').outerWidth() - button_distance - button_search;
						if(keyword_width < 280) {
							$('.nav2 .header-search').addClass('header-search-stretched');
							keyword_width = "100%";
							location_width = $('.nav2-mobile2 .header-search').outerWidth() - $('.nav2-mobile2 .header-search .fake-select-distance').outerWidth();
						}

						if(form_size > $('.nav2-mobile2 .header-search').outerWidth()) {
							$('.nav2-mobile2 .keyword').css('width', keyword_width);
							$('.nav2-mobile2 .location').css('width', location_width);
						} else {
							$('.nav2-mobile2 .keyword, .nav2-mobile2 .location').css('width', original_width+'px');
							$('.nav2').removeClass('nav2-mobile2');
						}
					};
				}

				if($('header .nav2-container').outerWidth() < $('header .nav2 .postnew-button').outerWidth() + 60) {
					$('header .nav2 .postnew-button').addClass('stretch');
				}
				if($('header .nav2 .postnew-button').length && $('header .nav2 .postnew-button .icon').offset().top > $('header .nav2 .postnew-button .text').offset().top) {
					$('header .nav2 .postnew-button .icon').hide();
				}
			} // if $('header .nav2 .header-search').length
		}
		// force recalculation of input fields after a certain time
		// this fixes a bug in iOS
		setTimeout(function() { search_form_resizing(); }, 20);
		setTimeout(function() { search_form_resizing(); }, 1000);
		setTimeout(function() { search_form_resizing(); }, 2000);

		$('.fake-select-header-category-chooser').on('click', function(event) {
			var options = $(this).find('.options');
			setTimeout(function() {
				if(options.data('normal-width')) {
					options.css('width', options.data('normal-width'));
				}
				if($('body').hasClass('is-phone') && options.offset().left < 0) {
					options.data('normal-width', options.outerWidth());
					options.css('width', ($('.keyword-box-wrapper').outerWidth()));
				}
			}, 20);
		});
		// search and "post new ad" button END

		// admin menu START
		if($('.admin-menu .top').length) {
			if($('.admin-menu').hasClass('admin-menu-mobile')) $('.admin-menu').removeClass('admin-menu-mobile');
			if($('.admin-menu').hasClass('admin-menu-mobile-first')) $('.admin-menu').removeClass('admin-menu-mobile-first');
			$('.admin-menu .top-a .text-short').attr('style', '');

			$('.admin-menu .top .top-a .text-short').hide();
			$('.admin-menu .top .top-a .text').show();
			if(($('.admin-menu .top').first().offset().top + 20) < $('.admin-menu .top').last().offset().top) {
				$('.admin-menu').addClass('admin-menu-mobile-first');

				$('.admin-menu .top .top-a .text').hide().css('white-space', 'nowrap');
				$('.admin-menu .top .top-a .text-short').show();
				$.each($('.admin-menu .top'), function(index, val) {
					if($(this).find('.top-a .icon').offset().top < $(this).find('.top-a .text-short').offset().top) {
						$('.admin-menu').addClass('admin-menu-mobile');
						return false;
					}
				});
				if($('.admin-menu').hasClass('admin-menu-mobile')) {
					$('.admin-menu .top-a .text-short:visible').each(function(index, el) {
						$(this).attr('style', '');
						var font_size = parseInt($(this).css('font-size'));
						while($(this)[0].scrollWidth >  $(this).innerWidth() && font_size > 0) {
							font_size--;
							$('.admin-menu .top-a .text-short').css('font-size', font_size+'px');
						}
					});
				}
			}
		}
		// admin menu END

		// pagination START
		pagination_resize();
		setTimeout(function() { pagination_resize(); }, 1000);
		function pagination_resize() {
			$('.pagination .page-numbers').show().attr('style', '');
			$('.pagination .prev .text, .pagination .next .text').show();
			if($('.pagination-wrapper').outerWidth() < $('.pagination').outerWidth()) {
				$('.pagination .prev .text, .pagination .next .text').hide();

				if($('.pagination .dots').length == "2") {
					$('.pagination .current').nextAll().eq('1').hide();
					$('.pagination .current').prevAll().eq('1').hide();
				}

				var font_size = parseInt($('.pagination a.page-numbers').first().css('font-size'));
				while($('.pagination-wrapper').outerWidth() < $('.pagination').outerWidth() && font_size > 5) {
					font_size--;
					$('.pagination .page-numbers').css({
						'font-size': font_size,
						'padding': '2px 4px'
					});
				}
			}
		}
		// pagination END

		// Add mobile marker START
		if($(document).outerWidth() < isphone_size) {
			$('body').addClass('is-phone');
		} else {
			$('body').removeClass('is-phone');
		}
		// Add mobile marker END

		// Make content as big as sidebar START
		if($(document).outerWidth() < isphone_size) {
			$('.content').css('min-height', '');
		} else {
			if($('.sidebar').outerHeight() > $('.content').outerHeight(true)) {
				$('.content').css('min-height', $('.sidebar').outerHeight(true));
			}
		}
		// Make content as big as sidebar END

		resize_sidebar_sublinks = function() {
			$('.mobile-sidebar .selected-category-subcategories .sub-link:visible').each(function(index, el) {
				var width = '0';
				$(this).find('> span').each(function(index, el) {
					if(!$(this).hasClass('text')) {
						width = parseInt(width) + parseInt($(this).outerWidth());
					}
				});
				$(this).find('.text').css('width', ($(this).parent().outerWidth() - width - 25));
			});
		}
		// Move sidebar to top of page START
		if($(document).outerWidth() < isphone_size) {
			if(!$('.mobile-sidebar').length) {
				$(".items-loop").before('<div class="mobile-sidebar">'+$(".sidebar").html()+'</div>');
				$('.sidebar').each(function(index, el) {
					if(!$(this).hasClass('mobile-sidebar')) {
						$(this).hide();
					}
				});
			}
			resize_sidebar_sublinks();
			$(window).resize(function() {
				resize_sidebar_sublinks();
			});
		} else {
			if($('.mobile-sidebar').length) {
				$('.mobile-sidebar').remove();
			}
			$('.sidebar').show();
		}
		// Move sidebar to top of page END

		// Equilize width of all sidebar category icons
		if($('body').hasClass('is-phone')) {
			var width = 0;
			$('.mobile-sidebar .categories .category-icon').each(function(index, el) {
				if($(this).outerWidth(true) > width) {
					width = $(this).outerWidth(true);
				}
			});
			$('.mobile-sidebar .categories .category-icon').css('width', width);
		}

		// Calculate sidebar links width
		$('.mobile-sidebar .categories .top-link .text, .mobile-sidebar .selected-category-subcategories .sub-link .text, .mobile-sidebar .selected-category .text').each(function(index, el) {
			var total_width = $(this).parent().outerWidth(true);
			var divs_width = 0;
			var equilizer = 22;
			if($(this).parent().hasClass('selected-category')) {
				equilizer = 52;
			}
			$(this).siblings('.icon').each(function(index, el) {
				divs_width = $(this).outerWidth(true) + divs_width;
			});
			if($(this).siblings('.post-count').length) {
				divs_width = $(this).siblings('.post-count').outerWidth(true) + divs_width;
			}
			$(this).css('width', (total_width - divs_width - equilizer));
		});
		vcenter();


		// Make images responsive START
		if($(document).outerWidth() < isphone_size) {
			$('.item-img-responsive').each(function(index, el) {
				$(this).attr('src', $(this).data('isphone-src'));
			});
		} else {
			$('.item-img-responsive').each(function(index, el) {
				$(this).attr('src', $(this).data('original-src'));
			});
		}
		// Make images responsive END

		// Make price responsive in item-loop START
		$('.item .loop-item-details .price, .item2 .item2-img-link .item2-price').each(function(index, el) {
			var font_size = parseInt($(this).css('font-size'));
			$(this).attr('style', '');
			while($(this).outerWidth() > $(this).parent().outerWidth() && font_size > 0) {
				if(!$(this).data('font-size')) {
					$(this).data('font-size', font_size);
				}
				font_size--;
				$(this).css('font-size', font_size+'px');
			}
		});
		// Make price responsive in item-loop END


		// Resize long item description values START
		$('.item-page .entry .item-specifications .specification').each(function(index, el) {
			var font_size = parseInt($(this).css('font-size'));
			$(this).attr('style', '');
			while($(this)[0].scrollWidth > $(this).innerWidth() && font_size > 0) {
				if(!$(this).data('font-size')) {
					$(this).data('font-size', font_size);
				}
				font_size--;
				$(this).css('font-size', font_size+'px');
			}
		});
		// Resize long imte description values END

		// Post new ad START
		// Move "change category" link START
		if($(document).outerWidth() < isphone_size) {
			if(!$('.is-phone .post-new-ad .selected-category .form-label .label').data('cloned-attached')) {
				$('.is-phone .post-new-ad .selected-category .form-label .label').data('cloned-attached', 'yes');
				$('.is-phone .post-new-ad .change-category').clone().appendTo($('.is-phone .post-new-ad .selected-category .form-label .label'));
				$('.is-phone .item-page .entry .item-conditions').css('height', '');
			}
		} else {
			$('.post-new-ad .selected-category .form-label .label .change-category').remove();
		}
		// Move "change category" link END
		// Post new ad END

		// Ad page - create image slider for mobile from available images START
		$('.is-phone .item-page .swiper-container .swiper-wrapper').empty();
		if($(document).outerWidth() < isphone_size && $('.is-phone .item-page .thumbs-gallery .gallery-thumb').length) {
			$('.is-phone .item-page .thumbs-gallery .gallery-thumb').each(function(index, el) {
				if($(this).data('preview-th')) {
					$('.is-phone .item-page .swiper-container .swiper-wrapper').append('<div class="swiper-slide vcenter center"><img class="vcenter" src="'+$(this).data('preview-th')+'" /></div>');
				}
			});

		    var mySwiper = new Swiper ('.swiper-container', {
				pagination: '.swiper-pagination',
				grabCursor: 'true',
				onImagesReady: function(){
				    vcenter();
				},
				onTap: function(event) {
					$('.item-page .item-images .thumbs-gallery img[data-index="'+event.activeIndex+'"]').trigger('click');
				}
		    });
		}
		// Ad page, create image slider for mobile from available images END

		// Item-page - Move phone number START
		// $('.is-phone .item-page .entry .seller-and-report .phone-number-mobile').remove();
		// $('.is-phone .item-page .entry .seller-and-report').removeClass('seller-and-report-mobile');
		// if($(document).outerWidth() < isphone_size && $('.is-phone .item-page .entry .seller-and-report').length) {
		// 	$('.is-phone .item-page .entry .seller-and-report').addClass('seller-and-report-mobile');
		// 	$('.is-phone .item-page .entry .seller-and-report-mobile .seller-link').after($('.is-phone .item-page .entry .phone-number').first().clone().addClass('phone-number-mobile'));
		// }
		// Item-page - Move phone number END

		// Item loop, edit links and stats START
		if($('.items-loop .item .loop-edit-links').length) {
			$('.items-loop .item .loop-edit-links').removeClass('loop-edit-links-screen-too-small')
			if($('.items-loop .item .loop-edit-links .links').first().offset().top < $('.items-loop .item .loop-edit-links .stats').first().offset().top && !$('body').hasClass('is-phone')) {
				$('.items-loop .item .loop-edit-links').addClass('loop-edit-links-screen-too-small');
			}
		}
		// Item loop, edit links and stats END

		// Payment buttons alignment on small screens START
		setTimeout(function() {
			if($('.ad-needs-payment-section .payment-buttons').length) {
				$('.ad-needs-payment-section .payment-buttons').each(function(index, el) {
					var payment_buttons_text = $(this).find('.payment-buttons-text').offset().top;
					$(this).find('.generated-payment-buttons').removeClass('generated-payment-buttons-mobile');

					if($(this).find('.pay-button-paypal').length && $(this).find('.pay-button-credit-card').length) {
						// both payment options are active
						var pay_button_credit_card = $(this).find('.pay-button-credit-card').offset().top;
						if(payment_buttons_text < pay_button_credit_card) {
							$(this).find('.generated-payment-buttons').addClass('generated-payment-buttons-mobile');
						}
					} else if($(this).find('.pay-button-paypal').length) {
						// only PayPal is active
						var pay_button_paypal = $(this).find('.pay-button-paypal').offset().top;
						if(payment_buttons_text < pay_button_paypal) {
							$(this).find('.generated-payment-buttons').addClass('generated-payment-buttons-mobile');
						}
					} else if($(this).find('.pay-button-credit-card').length) {
						// only STRIPE is active
						var pay_button_credit_card = $(this).find('.pay-button-credit-card').offset().top;
						if(payment_buttons_text < pay_button_credit_card) {
							$(this).find('.generated-payment-buttons').addClass('generated-payment-buttons-mobile');
						}
					}
				});
			}
		}, 10);
		// Payment buttons alignment on small screens END
	} // create_responsive()

	vcenter();
	// Code version: vohurlm2lr567bte7476
});