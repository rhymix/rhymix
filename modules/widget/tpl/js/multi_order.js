var MultiOrderManager = xe.createApp("MultiOrderManager", {
	options: null,
	values: null,
	keyObj: null,
	showObj: null,
	selectedObj: null,

	init: function(key, options, values){
		var opstions = this.options = options;
		var values = this.values = values;
		var keyObj = this.keyObj = jQuery('input[name='+key+']');
		var showObj = this.showObj = keyObj.parent().find('.multiorder_show');
		var selectedObj = this.selectedObj = keyObj.parent().find('.multiorder_selected');

		for (var key in options){
			var option = options[key];
			var html = '<option value="'+key+'">'+option.value+'</option>';
			showObj.append(html);

			if (option.init){
				this.addValue(key);
			}
		}
		this.apply();

		var thisObj = this;
		keyObj.parent().find('.multiorder_add').bind('click', function(){ thisObj.cast('MULTIORDER_ADD'); return false; });
		keyObj.parent().find('.multiorder_del').bind('click', function(){ thisObj.cast('MULTIORDER_DEL'); return false; });
		keyObj.parent().find('.multiorder_up').bind('click', function(){ thisObj.cast('MULTIORDER_UP'); return false; });
		keyObj.parent().find('.multiorder_down').bind('click', function(){ thisObj.cast('MULTIORDER_DOWN'); return false; });
	},

	API_MULTIORDER_ADD: function(){
		var index = this.showObj.get(0).selectedIndex;
		if (index == -1) return;

		if (this.addValue(index)) this.apply();
	},

	API_MULTIORDER_DEL: function(){
		var index = this.selectedObj.get(0).selectedIndex;
		if (index == -1) return;

		if (this.delValue(index)) this.apply();
	},

	API_MULTIORDER_UP: function(){
		var index = this.selectedObj.get(0).selectedIndex;
		if (index == -1) return;

		if (this.up(index)) this.apply();
	},

	API_MULTIORDER_DOWN: function(){
		var index = this.selectedObj.get(0).selectedIndex;
		if (index == -1) return;

		if (this.down(index)) this.apply();
	},

	addValue: function(index){
		var option = this.options[index];
		if (!option) return false;

		if (this.values == undefined) this.values = new Array();
		for (var i in this.values){
			if (this.values[i].key == option.key) return false;
		}

		this.values.push(option);
		return true;
	},

	delValue: function(index){
		if (this.options[index].default) return false;

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
			keys.push(this.values[i].key);
		}
		this.keyObj.val(keys.join(','));

		var prevValue = this.selectedObj.val();
		this.selectedObj.empty();
		for (var i in this.values){
			var option = this.values[i];
			var html = '<option value="'+option.key+'">'+option.value+'</option>';
			this.selectedObj.append(html);
		}
		this.selectedObj.val(prevValue);
	}
});
