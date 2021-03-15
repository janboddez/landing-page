( function( $ ) {
	$( '.settings_page_landing-page .form-table:first-of-type' ).on( 'click', 'button.add-row', function() {
		// Find `.new` table row, and clone it.
		var row    = $( '.settings_page_landing-page .form-table:first-of-type tr.new' );
		var newRow = row.clone();

		row.removeClass( 'new' );
		newRow.find( 'input' ).val( '' );

		// And insert it below.
		newRow.insertBefore( $( '.settings_page_landing-page .form-table:first-of-type tr:nth-last-child(2)' ) );
	} );

	$( '.settings_page_landing-page .form-table:first-of-type' ).on( 'click', 'button.remove-row' , function() {
		// Remove the entire table row.
		$( this ).closest( 'tr' ).remove();
	} );

	$( '.settings_page_landing-page .form-table:first-of-type' ).on( 'focus', 'input[name="landing_page_settings[mapping][target][]"]', function() {
		if ( ! $( this ).data( 'autocomplete' ) ) {
			$( this ).autocomplete( {
				source: function( request, response ) {
					$.post(
						landing_page_obj.ajax_url,
						{
							search: request.term,
							action: 'search_posts',
							wp_nonce: landing_page_obj.ajax_nonce
						},
						function( result ) {
							response( result.data );
						},
						'json'
					);
				}
			} )
		}
	} );
} )( jQuery );
