(function($){

xe.ModuleListManager = xe.createApp("ModuleListManager", {
	values: null,
	keyObj: null,
	moduleNameObj: null,
	moduleSrlObj: null,
	selectedObj: null,

	init: function(key, values){
		var values  = this.values = values, self = this;
		var $keyObj = this.keyObj = $('input[name='+key+']');
		this.moduleNameObj = $keyObj.parent().find('.moduleList');
		this.moduleSrlObj  = $keyObj.parent().find('.moduleIdList');
		this.selectedObj   = $keyObj.parent().find('.modulelist_selected');
		
		this.moduleSrlObj
			.nextAll('button')
				.filter('.modulelist_add').bind('click', function(){ self.cast('MODULELIST_ADD'); return false; }).hide().end()
				.filter('.modulelist_del').bind('click', function(){ self.cast('MODULELIST_DEL'); return false; }).end()
				.filter('.modulelist_up').bind('click', function(){ self.cast('MODULELIST_UP'); return false; }).end()
				.filter('.modulelist_down').bind('click', function(){ self.cast('MODULELIST_DOWN'); return false; }).end()
			.end()
			.bind('show', function(){
				$(this).nextAll().show();
			});
	},

	API_MODULELIST_ADD: function(){
		var moduleTitle = this.moduleNameObj.find('>option:selected').text();
		
		this.moduleSrlObj
			.find('>option:selected').clone(true)
			.text(function(){ return $(this).text() + ' ('+moduleTitle+')'; })
			.appendTo(this.selectedObj);

		this.removeDuplicated();
		this.refreshValue();
	},

	API_MODULELIST_DEL: function(){
		this.selectedObj.find('>option:selected').remove();
		this.refreshValue();
	},

	API_MODULELIST_UP: function(){
		var $selected = this.selectedObj.find('>option:selected');
		$selected.eq(0).prev('option').before($selected);
		this.refreshValue();
	},

	API_MODULELIST_DOWN: function(){
		var $selected = this.selectedObj.find('>option:selected');
		$selected.eq(-1).next('option').after($selected);
		this.refreshValue();
	},

	removeDuplicated : function() {
		var selected = {};
		this.selectedObj.find('>option').each(function(){
			if(selected[this.value]) $(this).remove();
			selected[this.value] = true;
		});
	},

	refreshValue : function() {
		var srls = [];
		
		this.selectedObj.find('>option').each(function(){
			srls.push(this.value);
		});
		
		this.keyObj.val(srls.join(','));
	}
});

})(jQuery);