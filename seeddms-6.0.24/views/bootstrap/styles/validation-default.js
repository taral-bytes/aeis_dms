jQuery.validator.setDefaults({
	errorElement: "em",
	errorClass: 'help-block',
	errorPlacement: function ( error, element ) {
		// Add the `error` class to the control-group
		$(element).closest('.control-group').addClass('error');
		if ( element.prop( "type" ) === "checkbox" ) {
			error.insertAfter( element.parent( "label" ) );
		} else {
			error.insertAfter( element );
		}

		// Add the span element, if doesn't exists, and apply the icon classes to it.
		if ( !element.next( "span" )[ 0 ] ) {
			$( "<span class='glyphicon glyphicon-remove form-control-feedback'></span>" ).insertAfter( element );
		}
	},
	invalidHandler: function(e, validator) {
		noty({
			text: (validator.numberOfInvalids() == 1) ? trans.js_form_error.replace('#', validator.numberOfInvalids()) : trans.js_form_errors.replace('#', validator.numberOfInvalids()),
			type: 'error',
			dismissQueue: true,
			layout: 'topRight',
			theme: 'defaultTheme',
			timeout: 3500,
		});
	},
	highlight: function ( element, errorClass, validClass ) {
		$( element ).parents( ".control-group" ).addClass( "error" ).removeClass( "success" );
	},
	unhighlight: function ( element, errorClass, validClass ) {
		$( element ).parents( ".control-group" ).addClass( "success" ).removeClass( "error" );
	}
});
