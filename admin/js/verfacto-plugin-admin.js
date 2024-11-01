(function ( $ ) {
	'use strict';

	//Hide error message after 5 sec
	setTimeout(
		function () {
			$( document.getElementsByClassName("verfacto-error-message") ).fadeOut( 'fast' );
		},
		5000
	);

	//Handle form validation	
	let $submit = $("#submit_integration_form"),
	$inputs = $('input[type=email], input[type=password]');

	function check_empty_field() {
		return $inputs.filter(function() {
			return !$.trim(this.value);
		}).length === 0;
	}

	$inputs.on('blur keyup', function() {
		$submit.prop("disabled", !check_empty_field());
	}).blur();

	//Handle Sign Up/Login form submits
	$( '#verfacto-integrate-form' ).submit(
		function (e) {
			e.preventDefault();
			$( '.verfacto-loader' ).css( "visibility", "visible" );

			let form      = $( this );
			let form_data = new FormData( form[0] );
			form_data.append( "action", "activate_verfacto" );
			form_data.append( "vf_nonce", verfacto_ajax.vf_nonce );

			$.ajax(
				{
					type: "POST",
					url: verfacto_ajax.admin_ajax,
					contentType: false,
					processData: false,
					dataType: 'JSON',
					data: form_data,
					cache: false,
					success: function (data) {
						form.trigger( 'reset' );
						$( '.verfacto-loader' ).css( "visibility", "hidden" );
						$( '.verfacto-error-message.verfacto-error-danger.ajax' ).hide();
						window.open( data.url, data.open_in );
					}
				}
			).fail(
				function (jqXHR) {
					if (jqXHR.responseText === 'internal-server-error') {
						show_error_message( "Something unexpected happened please try again" );
						$( '.verfacto-loader' ).css( "visibility", "hidden" );
						return;
					}

					if ( ! jqXHR.responseJSON.success && jqXHR.responseJSON.data !== '') {
						show_error_message( jqXHR.responseJSON.data );
						$( '.verfacto-loader' ).css( "visibility", "hidden" );
					} else {
						show_error_message( 'Some issue with api service please try again later' )
						$( '.verfacto-loader' ).css( "visibility", "hidden" );
					}
				}
			);

		}
	);

	function show_error_message(message_text) {
		let error_block = $( '.verfacto-error-message.verfacto-error-danger.ajax' ).show();
		error_block.find( '.verfacto-error-text' ).html( message_text );

		setTimeout(
			function () {
				$( '.verfacto-error-message.verfacto-error-danger.ajax' ).fadeOut( 'fast' );
			},
			5000
		);	
	}

})( jQuery );
