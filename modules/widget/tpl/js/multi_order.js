(function($){

xe.MultiOrderManager = xe.createApp("MultiOrderManager", {
	$keyObj: null,
	$showObj: null,
	$selectedObj: null,

	init: function(key){
		var self = this;
		var $keyObj = this.$keyObj = jQuery('input[name='+key+']');
		this.$showObj 		= $keyObj.parent().find('.multiorder_show');
		this.$selectedObj 	= $keyObj.parent().find('.multiorder_selected');

		this.$showObj
			.nextAll('button')
				.filter('.multiorder_add').bind('click', function(){ self.cast('MULTIORDER_ADD'); return false; }).end()
				.filter('.multiorder_del').bind('click', function(){ self.cast('MULTIORDER_DEL'); return false; }).end()
				.filter('.multiorder_up').bind('click', function(){ self.cast('MULTIORDER_UP'); return false; }).end()
				.filter('.multiorder_down').bind('click', function(){ self.cast('MULTIORDER_DOWN'); return false; }).end()
	},

	API_MULTIORDER_ADD: function(){
		this.$showObj
			.find('>option:selected')
			.appendTo(this.$selectedObj);

		this.refreshValue();
	},

	API_MULTIORDER_DEL: function(){
		this.$selectedObj
			.find('>option:selected[default!="true"]')
			.appendTo(this.$showObj);

		this.refreshValue();
	},

	API_MULTIORDER_UP: function(){
		var $selected = this.$selectedObj.find('>option:selected');
		$selected.eq(0).prev('option').before($selected);
		this.refreshValue();
	},

	API_MULTIORDER_DOWN: function(){
		var $selected = this.$selectedObj.find('>option:selected');
		$selected.eq(-1).next('option').after($selected);
		this.refreshValue();
	},

	refreshValue : function() {
		var values = [];

		this.$selectedObj.find('>option').each(function(){
			values.push(this.value);
		});

		this.$keyObj.val(values.join(','));
	}
});

})(jQuery);