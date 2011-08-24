jQuery(function($){

// multi-lingual text list
$('#langList')
	.find('ul').hide().attr('aria-hidden','true').end() // collapse all language input control
	.delegate('button._edit', 'click', function(){
		var $this = $(this), $ul = $this.next('ul'), form;

		// toggle input control
		if($ul.attr('aria-hidden') == 'false') {
			$ul.slideUp('fast');
			$ul.attr('aria-hidden', 'true');
		}else{
			$ul.slideDown('fast');
			$ul.attr('aria-hidden', 'false');
		}

		if($ul.data('lang-loaded') == true) return;
	
		$ul.data('lang-loaded', true);
		form = $this.closest('form').get(0);

		function on_complete(ret) {
			var name = ret['lang_name'], list = ret['lang_list']['item'], elems = form.elements, item;

			$ul.find('label+textarea').prev('label').css('visibility','hidden');

			if(!$.isArray(list)) list = [list];
			for(var i=0,c=list.length; i < c; i++) {
				item = list[i];
				if(item && item.lang_code && elems[item.lang_code]) {
					elems[item.lang_code].value = item.value;
					if(!item.value) $(elems[item.lang_code]).prev('label').css('visibility','visible');
				}
			}
		}

		exec_xml(
			'module',
			'getModuleAdminLangListByName',
			{lang_name:form.elements['lang_name'].value},
			on_complete,
			'error,message,lang_list,lang_name'.split(',')
		);
	})

});
