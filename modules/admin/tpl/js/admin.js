/**
 * @file   admin.js
 * @author NHN (developers@xpressengine.com)
 * @brief  admin 모듈의 javascript
 **/

// 캐시파일 모두 재 생성
function doRecompileCacheFile() {
    exec_xml("admin","procAdminRecompileCacheFile", new Array(), completeMessage);
}

// 모듈 목록 오픈
function toggleModuleMenu(category) {
	jQuery('#module_'+category).toggleClass('close');
	
	var arr = new Array();
	jQuery('ul.navigation > li').each(function(){
		var o = jQuery(this);
		if(!o.hasClass('close')) return;
		var idx = o.attr('id').replace(/^module_/,'');
		arr.push(idx);
	});
	var expire= new Date();
	expire.setTime(expire.getTime()+(7000*24*3600000));
	xSetCookie('XEAM',arr.join(','),expire,'/');
}

// 메인 모듈/ 애드온 토글
function toggleModuleAddon(target) {
	var b = (target == 'module');

	jQuery('#moduleOn').attr('class', b?'on':'');
	jQuery('#addonOn').attr('class', b?'':'on');
	jQuery('#xeModules')[b?'show':'hide']();
	jQuery('#xeAddons')[b?'hide':'show']();
}

// toggle language list
function toggleAdminLang() {
	jQuery('#adminLang').toggleClass('open');
}

// string to regex(초성검색용)
function str2regex(str) {
	// control chars
	str = str.replace(/([\[\]\{\}\(\)\*\-\+\!\?\^\|\\])/g, '\\$1');

	// find consonants and replace it
	str = str.replace(/[ㄱ-ㅎ]/g, function(c){
		var c_order = 'ㄱㄲㄴㄷㄸㄹㅁㅂㅃㅅㅆㅇㅈㅉㅊㅋㅌㅍㅎ'.indexOf(c);
		var ch_first = String.fromCharCode(0xAC00 + c_order*21*28 + 0 + 0);
		var ch_last  = String.fromCharCode(0xAC00 + c_order*21*28 + 20*28 + 27);

		return '['+ch_first+'-'+ch_last+']';
	});

	return new RegExp(str, 'ig');
}

jQuery(function($){
	// paint table rows
    jQuery("table.rowTable tr").attr('class','').filter(":nth-child(even)").attr('class','bg1');

	// set menu tooltip - taggon
	$('ul.navigation:first > li').each(function(){
		var texts = [];
		$(this).find('li').each(function(){
			texts.push($(this).text());
		});

		if (!texts.length) return true;

		$(this).find('>a').qtip({
			content : texts.join(', '),
			position : {
				corner : {
					target:'rightMiddle',
					tooltip:'leftMiddle'
				},
				adjust : {
					x : -30
				}
			},
			style : {
				name : 'cream',
				tip : true,
				textAlign : 'center',
				padding : 5,
				border : {
					radius : 2
				}
			}
		});
	});

	// menu search
	var nav = $('#search_nav + ul.navigation');
	var inp = $('#search_nav input[type=text]:first');
	var btn = $('#search_nav button:first');
	var result = $('<ul class="_result" />');

    if(inp.length == 0) return;

	nav.after( result.hide() );

	inp.keydown(function(event){
			if (event.keyCode == 27) { // ESC
				$(this).val('');
				if ($.browser.msie) $(this).keypress();
			}
		})
		.watch_input({
			oninput : function() {
				var str = $.trim( $(this).val() );

				if (str.length == 0) {
					nav.show();
					result.hide();
					btn.removeClass('close');
					return false;
				}

				// remove all sub nodes
				result.empty();

				var regex = str2regex(str);
				nav.find('li li > a').each(function(){
					var text = $(this).text();

					if (regex.exec(text) != null) {
						$(this).parent().clone().appendTo(result);
					}

					// fix regular expression bug
					regex.exec('');
				});

				nav.hide();
				result.show();
				btn.addClass('close');
			}
		});

	// cancel search
	btn.click(function(){
		if ($(this).hasClass('close')) {
			$(this).removeClass('close');

			inp.focus();
			inp.val('');
			inp.keydown();
		} 

		return false;
	});

});
