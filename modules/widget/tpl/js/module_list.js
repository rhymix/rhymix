var ModuleListManager = xe.createApp("ModuleListManager", {
	values: null,
	keyObj: null,
	moduleNameObj: null,
	moduleSrlObj: null,
	selectedObj: null,

	init: function(key, values){
		var values = this.values = values;
		var keyObj = this.keyObj = jQuery('input[name='+key+']');
		var moduleNameObj = this.moduleNameObj = keyObj.parent().find('.moduleList');
		var moduleSrlObj = this.moduleSrlObj = keyObj.parent().find('.moduleIdList');
		var selectedObj = this.selectedObj = keyObj.parent().find('.modulelist_selected');

		var thisObj = this;
		keyObj.parent().find('.modulelist_add').bind('click', function(){ thisObj.cast('MODULELIST_ADD'); return false; });
		keyObj.parent().find('.modulelist_del').bind('click', function(){ thisObj.cast('MODULELIST_DEL'); return false; });
		keyObj.parent().find('.modulelist_up').bind('click', function(){ thisObj.cast('MODULELIST_UP'); return false; });
		keyObj.parent().find('.modulelist_down').bind('click', function(){ thisObj.cast('MODULELIST_DOWN'); return false; });
	},

	API_MODULELIST_ADD: function(){
		var module = this.moduleNameObj.children('option:selected').text();
		var moduleSrl = this.moduleSrlObj.val();
		var browserTitle = this.moduleSrlObj.children('option:selected').text();

		if (this.addValue(moduleSrl, module, browserTitle)) this.apply();
	},

	API_MODULELIST_DEL: function(){
		var index = this.selectedObj.get(0).selectedIndex;
		if (index == -1) return;

		if (this.delValue(index)) this.apply();
	},

	API_MODULELIST_UP: function(){
		var index = this.selectedObj.get(0).selectedIndex;
		if (index == -1) return;

		if (this.up(index)) this.apply();
	},

	API_MODULELIST_DOWN: function(){
		var index = this.selectedObj.get(0).selectedIndex;
		if (index == -1) return;

		if (this.down(index)) this.apply();
	},

	addValue: function(moduleSrl, module, browserTitle){
		var value = {'moduleSrl': moduleSrl, 'module': module, 'browserTitle': browserTitle};

		if (this.values == undefined) this.values = new Array();
		for (var i in this.values){
			if (this.values[i].moduleSrl == value.moduleSrl) return false;
		}

		this.values.push(value);
		return true;
	},

	delValue: function(index){
		this.values.splice(index, 1);
		return true;
	},

	up: function(index){
		if (index == 0) return false;

		var targets = this.values.splice(index-1, 2);
		for(var i in targets){
			this.values.splice(index-1, 0, targets[i]);
		}
		return true;
	},

	down: function(index){
		if (index == this.values.length-1) return false;

		var targets = this.values.splice(index, 2);
		for(var i in targets){
			this.values.splice(index, 0, targets[i]);
		}
		return true;
	},

	apply: function(){
		var keys = new Array();
		for (var i in this.values){
			keys.push(this.values[i].moduleSrl);
		}
		this.keyObj.val(keys.join(','));

		var prevValue = this.selectedObj.val();
		this.selectedObj.empty();
		for (var i in this.values){
			var module = this.values[i];
			var html = '<option value="'+module.moduleSrl+'">'+module.browserTitle+'('+module.module+')</option>';
			this.selectedObj.append(html);
		}
		this.selectedObj.val(prevValue);
	}
});
