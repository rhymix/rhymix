function doDisplaySkinColorset()
{
	var skin = jQuery('select[name=skin]').val();
	if(!skin) {
		doHideSkinColorset();
		return;
	}

	var params = new Array();
	params["selected_widget"] = jQuery('input[name=selected_widget]').val();
	params["skin"] = skin;
	params["colorset"] = jQuery('select[name=colorset]').val();

	var response_tags = new Array("error","message","colorset_list");

	exec_xml("widget", "procWidgetGetColorsetList", params, completeGetSkinColorset, response_tags, params);
}

function completeGetSkinColorset(ret_obj)
{
	var sel = jQuery("select[name=colorset]").get(0);
	var length = sel.options.length;
	var selected_colorset = jQuery('select[name=colorset]').val();
	for(var i=0;i<length;i++) sel.remove(0);

	if(!ret_obj["colorset_list"]) return;

	var colorset_list = ret_obj["colorset_list"].split("\n");
	var selected_index = 0;
	for(var i=0;i<colorset_list.length;i++) {
		var tmp = colorset_list[i].split("|@|");
		if(selected_colorset && selected_colorset==tmp[0]) selected_index = i;
		var opt = new Option(tmp[1], tmp[0], false, false);
		sel.options.add(opt);
	}

	sel.selectedIndex = selected_index;

	doShowSkinColorset();
}

function doHideSkinColorset()
{
	jQuery('select[name=colorset]').parents('li').hide();
}

function doShowSkinColorset()
{
	jQuery('select[name=colorset]').parents('li').show();
}

var $current_filebox;

jQuery(document).ready(function($){
	$('select[name=skin]').next('input').bind('click', function(e){
		doDisplaySkinColorset();
	});
	doHideSkinColorset();

	$('.filebox').bind('before-open.mw', function(){
		$('#filebox_upload').find('input[name=comment], input[name=addfile]').val('');
	});

	$('.filebox').bind('filebox.selected', function(e, src){
		$(this)
			.siblings()
			.filter(function(){
				return this.nodeName.toLowerCase() != 'input';
			})
			.remove();

		$(this).before('<img src="'+src+'" alt="" style="border: 1px solid #ccc; padding: 5px; max-height: 200px;"> <button class="filebox_del text" type="button">'+xe.lang.cmd_delete+'</button>');

		$(this).siblings('input').val(src);

		$('.filebox_del').bind('click', function(){
			$(this).siblings('input').val('');
			$(this).prev('img').remove();
			$(this).remove();
		});
	});

	$('.filebox').click(function(){
		$current_filebox = $(this);
	});

	$('#filebox_upload').find('input[type=submit]').click(function(){
		if ($('iframe[name=iframeTarget]').length < 1){
			var $iframe = $(document.createElement('iframe'));

			$iframe.css('display', 'none');
			$iframe.attr('src', '#');
			$iframe.attr('name', 'iframeTarget');
			$iframe.load(function(){
				var data = eval('(' + $(window.iframeTarget.document.getElementsByTagName("body")[0]).html() + ')');

				if (data.error){
					alert(data.message);
					return;
				}

				$current_filebox.trigger('filebox.selected', [data.save_filename]);
				$current_filebox.trigger('close.mw');
			});

			$('body').append($iframe.get(0));

			$(this).parents('form').attr('target', 'iframeTarget');
		}
	});

	$('#widget_code_form').bind('submit', function(){
		function on_complete(data){
			if (data.error){
				alert(data.message);
				return;
			}

			$('#widget_code').val(data.widget_code);
		}

		var datas = $(this).serializeArray();
		var params = new Object();
		for(var i in datas){
			var data = datas[i];

			if(/\[\]$/.test(data.name)) data.name = data.name.replace(/\[\]$/, '');
			if(params[data.name]) params[data.name] += '|@|' + data.value;
			else params[data.name] = data.value;
		}

		$.exec_json('widget.procWidgetGenerateCode', params, on_complete);

		return false;
	});
});