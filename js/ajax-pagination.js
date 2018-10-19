(function($) {

	function find_page_number( element ) {
        element.find('span').remove();
        return parseInt( element.html() );
	}

	$(document).on( 'click', '.pagination a', function( event ) {
		event.preventDefault();

		page = find_page_number( $(this).clone() );

		$.ajax({
			url: ajaxpagination.ajaxurl,
			type: 'post',
			data: {
				action: 'ajax_pagination',
				query_vars: ajaxpagination.query_vars,
				page: page
			},
			beforeSend: function() {
				$('.content').find('.items-loop').remove();
				$(document).scrollTop();
				$('.content').append( '<div class="page-content" id="loader">Loading New Posts...</div>' );
			},
			success: function( html ) {
				$('.content #loader').remove();
				$('.content').append( html );
				$.getScript( "../wp-content/themes/dolceclassifieds/js/dolceclassifieds.js");
				$.getScript( "../wp-content/themes/dolceclassifieds/js/responsive.js");
			}
		})
	})
})(jQuery);

(function($) {

	function find_category_string( element ) {
		return element.find('input[name="s"]').val();
	}

	function find_category_id( element ) {
		return element.find('input[name="c"]').val();
	}

	function find_location( element ) {
		return element.find('input[name="l"]').val();
	}

	function find_location_string( element ) {
		return element.find('input[name="ls"]').val();
	}

	function find_location_id( element ) {
		return element.find('input[name="ld"]').val();
	}

	function find_price_start( element ) {
		return element.find('input[name="ps"]').val();
	}

	function find_price_end( element ) {
		return element.find('input[name="pe"]').val();
	}

	function find_sorting_order_by( element ) {
		return element.find('input[name="sorting_order_by"]').val();
	}


	$('form#header_search').on( 'change', function( event ) {
		event.preventDefault();

		s = find_category_string( $(this).clone() );
		c = find_category_id( $(this).clone() );
		l = find_location( $(this).clone() );
		ls = find_location_string( $(this).clone() );
		ld = find_location_id( $(this).clone() );
		ps = find_price_start( $(this).clone() );
		pe = find_price_end( $(this).clone() );
		sort = find_sorting_order_by( $(this).clone() );

		$.ajax({
			url: ajaxpagination.ajaxurl,
			type: 'get',
			data: {
				action: 'ajax_filter',
				query_vars: ajaxpagination.query_vars,
				s: s,
				c: c,
				l: l,
				ls: ls,
				ld: ld,
				ps: ps,
				pe: pe,
				sort: sort
			},
			beforeSend: function() {
				$('.content').find('.items-loop').remove();
				$(document).scrollTop();
				$('.content').append( '<div class="page-content" id="loader">Loading New Posts...</div>' );
			},
			success: function( html ) {
				$('.content #loader').remove();
				$('.content').append( html );
				$.getScript( "../wp-content/themes/dolceclassifieds/js/dolceclassifieds.js");
				$.getScript( "../wp-content/themes/dolceclassifieds/js/responsive.js");
			}
		})
	})
})(jQuery);


(function($) {

	$('#slider-range').on( 'slidechange', function( event ) {
		event.preventDefault();

		s = $('input[name="s"]').val();
		c = $('input[name="c"]').val();
		ls = $('input[name="ls"]').val();
		ld = $('input[name="ld"]').val();
		ps = $('input[name="ps"]').val();
		pe = $('input[name="pe"]').val();
		sort = $('input[name="sorting_order_by"]').val();
		$.ajax({
			url: ajaxpagination.ajaxurl,
			type: 'get',
			data: {
				action: 'ajax_filter',
				query_vars: ajaxpagination.query_vars,
				s: s,
				c: c,
				ls: ls,
				ld: ld,
				ps: ps,
				pe: pe,
				sort: sort
			},
			beforeSend: function() {
				$('.content').find('.items-loop').remove();
				$(document).scrollTop();
				$('.content').append( '<div class="page-content" id="loader">Loading New Posts...</div>' );
			},
			success: function( html ) {
				$('.content #loader').remove();
				$('.content').append( html );
				$.getScript( "../wp-content/themes/dolceclassifieds/js/dolceclassifieds.js");
				$.getScript( "../wp-content/themes/dolceclassifieds/js/responsive.js");
			}
		})
	})
})(jQuery);