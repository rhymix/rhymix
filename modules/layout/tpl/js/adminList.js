var ConfirmCheck = xe.createPlugin('confirm_check', {
	API_BEFORE_VALIDATE: function(sender, params){
		return confirm(xe.lang.confirm_delete);
	}
});

var Validator = xe.getApp('Validator')[0];
Validator.registerPlugin(new ConfirmCheck());