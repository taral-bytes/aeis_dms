jQuery.validator.setDefaults({
	errorElement: 'div',
	errorClass: 'invalid-feedback',
	errorPlacement: function(error, element) {
		if(element.parent('.input-group').length) {
			error.insertAfter(element.parent());
		} else {
			error.insertAfter(element);
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
	highlight: function (element, errorClass, validClass) {
		if($(element).data('target-highlight'))
			$('#'+$(element).data('target-highlight')).addClass('is-invalid');
		else
			$(element).addClass('is-invalid');
	},
	unhighlight: function (element, errorClass, validClass) {
		if($(element).data('target-highlight'))
			$('#'+$(element).data('target-highlight')).removeClass('is-invalid');
		else
			$(element).removeClass('is-invalid');
	}
});
