(function($){

xe.MultiLangManager = xe.createApp("MultiLangManager", {
	$keyObj: null,

	init: function(key){
		var $keyObj = this.$keyObj = $('.extra_vars input[name='+key+']');
	},

	API_MULTILANG_SYNC: function(){
		var self = this;
		var regexp = /^\$user_lang\-\>/;

		var langCode = this.$keyObj.val();
		if (!regexp.test(langCode)) return;

		function on_complete(data){
			if (data.error){
				alert(data.message);
				return;
			}

			$('#' + self.$keyObj.attr('name')).val(data.lang);
		}

		$.exec_json('module.getLangByLangcode', {'langCode': langCode}, on_complete);
	}
});

})(jQuery);