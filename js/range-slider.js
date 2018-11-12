(function($) {



  $( "#slider-range" ).slider({
      range: true,
      min: 0,
      max: 300,
      values: [ 0, 300 ],
      slide: function( event, ui ) {
        $( "#amount" ).val( "$" + ui.values[ 0 ] + " - $" + ui.values[ 1 ] );
        $( "#precios_start" ).val( ui.values[ 0 ] );
        $( "#precios_end" ).val( ui.values[ 1 ] );


      }
    });

    $( "#amount" ).val( "$" + $( "#slider-range" ).slider( "values", 0 ) +
      " - $" + $( "#slider-range" ).slider( "values", 1 ) );
})(jQuery);