(function($){
	var WaitingStub = xe.createPlugin('waiting_stub', {
		API_BEFORE_VALIDATE : function(sender, params) {
			$('#rhymix_waiting').html(waiting_message).show();
		},
		API_BEFORE_SHOW_ALERT : function(sender, params) {
			$('#rhymix_waiting').hide();
		}
	});

	var Validator = xe.getApp('validator')[0];
		if(Validator){
			Validator.registerPlugin(new WaitingStub);
	}
})(jQuery);
