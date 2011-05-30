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

// XE UI Library
jQuery(function($){
	// Label Overlapping
	var overlapLabel = $('.form li').find(':text,:password,textarea').prev('label');
	var overlapInput = overlapLabel.next(':text,:password,textarea');
	overlapLabel.css({'position':'absolute','top':'15px','left':'5px'}).parent().css('position','relative');
	overlapInput
		.focus(function(){
			$(this).prev(overlapLabel).css('visibility','hidden');
		})
		.blur(function(){
			if($(this).val() == ''){
				$(this).prev(overlapLabel).css('visibility','visible');
			} else {
				$(this).prev(overlapLabel).css('visibility','hidden');
			}
		})
		.change(function(){
			if($(this).val() == ''){
				$(this).prev(overlapLabel).css('visibility','visible');
			} else {
				$(this).prev(overlapLabel).css('visibility','hidden');
			}
		})
		.blur();
	// Lined Tab Navigation
	var tab_line = $('div.tab.line');
	var tab_line_i = tab_line.find('>ul>li');
	var tab_line_ii = tab_line.find('>ul>li>ul>li');
	tab_line.removeClass('jx');
	tab_line_i.find('>ul').hide();
	tab_line_i.find('>ul>li[class=active]').parents('li').attr('class','active');
	tab_line.find('>ul>li[class=active]').find('>ul').show();
	function lineTabMenuToggle(event){
		var t = $(this);
		tab_line_i.find('>ul').hide();
		t.next('ul').show();
		tab_line_i.removeClass('active');
		t.parent('li').addClass('active');
		return false;
	}
	function lineTabSubMenuActive(){
		tab_line_ii.removeClass('active');
		$(this).parent(tab_line_ii).addClass('active');
		return false;
	}; 
	tab_line_i.find('>a[href=#]').click(lineTabMenuToggle).focus(lineTabMenuToggle);
	tab_line_ii.find('>a[href=#]').click(lineTabSubMenuActive).focus(lineTabSubMenuActive);
	// Faced Tab Navigation
	var tab_face = $('div.tab.face');
	var tab_face_i = tab_face.find('>ul>li');
	var tab_face_ii = tab_face.find('>ul>li>ul>li');
	tab_face.removeClass('jx');
	tab_face_i.find('>ul').hide();
	tab_face_i.find('>ul>li[class=active]').parents('li').attr('class','active');
	tab_face.find('>ul>li[class=active]').find('>ul').show();
	function faceTabMenuToggle(event){
		var t = $(this);
		tab_face_i.find('>ul').hide();
		t.next('ul').show();
		tab_face_i.removeClass('active');
		t.parent('li').addClass('active');
		return false;
	}
	function faceTabSubMenuActive(){
		tab_face_ii.removeClass('active');
		$(this).parent(tab_face_ii).addClass('active');
		return false;
	}; 
	tab_face_i.find('>a[href=#]').click(faceTabMenuToggle).focus(faceTabMenuToggle);
	tab_face_ii.find('>a[href=#]').click(faceTabSubMenuActive).focus(faceTabSubMenuActive);
	// List Tab Navigation
	var tab_list = $('div.tab.list');
	var tab_list_i = tab_list.find('>ul>li');
	tab_list.removeClass('jx');
	tab_list_i.find('>ul').hide();
	tab_list.find('>ul>li[class=active]').find('>ul').show();
	tab_list.css('height', tab_list.find('>ul>li.active>ul').height()+40);
	function listTabMenuToggle(event){
		var t = $(this);
		tab_list_i.find('>ul').hide();
		t.next('ul').show();
		tab_list_i.removeClass('active');
		t.parent('li').addClass('active');
		tab_list.css('height', t.next('ul').height()+40);
		return false;
	}
	tab_list_i.find('>a[href=#]').click(listTabMenuToggle).focus(listTabMenuToggle);
	// Vertical Navigation
	var vNav = $('div.vNav');
	var vNav_i = vNav.find('>ul>li');
	var vNav_ii = vNav.find('>ul>li>ul>li');
	vNav_i.find('>ul').hide();
	vNav.find('>ul>li>ul>li[class=active]').parents('li').attr('class','active');
	vNav.find('>ul>li[class=active]').find('>ul').show();
	function vNavToggle(event){
		var t = $(this);
		if (t.next('ul').is(':hidden')) {
			vNav_i.find('>ul').slideUp(100);
			t.next('ul').slideDown(100);
		} else if (t.next('ul').is(':visible')){
			t.next('ul').show();
		} else if (!t.next('ul').langth) {
			vNav_i.find('>ul').slideUp(100);
		}
		vNav_i.removeClass('active');
		t.parent('li').addClass('active');
		return false;
	}
	vNav_i.find('>a[href=#]').click(vNavToggle).focus(vNavToggle);
	function vNavActive(){
		vNav_ii.removeClass('active');
		$(this).parent(vNav_ii).addClass('active');
		return false;
	}; 
	vNav_ii.find('>a[href=#]').click(vNavActive).focus(vNavActive);
	vNav.find('>ul>li>ul').prev('a').append('<span class="i"></span>');
	// Tree Navigation
	var tNav = $('.tNav');
	var tNavPlus = '\<button type=\"button\" class=\"tNavToggle plus\"\>+\<\/button\>';
	var tNavMinus = '\<button type=\"button\" class=\"tNavToggle minus\"\>-\<\/button\>';
	tNav.find('li>ul').css('display','none');
	tNav.find('ul>li:last-child').addClass('last');
	tNav.find('li>ul:hidden').parent('li').prepend(tNavPlus);
	tNav.find('li>ul:visible').parent('li').prepend(tNavMinus);
	tNav.find('li.active').addClass('open').parents('li').addClass('open');
	tNav.find('li.open').parents('li').addClass('open');
	tNav.find('li.open>.tNavToggle').text('-').removeClass('plus').addClass('minus');
	tNav.find('li.open>ul').slideDown(100);
	$('.tNav .tNavToggle').click(function(){
		t = $(this);
		t.parent('li').toggleClass('open');
		if(t.parent('li').hasClass('open')){
			t.text('-').removeClass('plus').addClass('minus');
			t.parent('li').find('>ul').slideDown(100);
		} else {
			t.text('+').removeClass('minus').addClass('plus');
			t.parent('li').find('>ul').slideUp(100);
		}
		return false;
	});
	$('.tNav a[href=#]').click(function(){
		t = $(this);
		t.parent('li').toggleClass('open');
		if(t.parent('li').hasClass('open')){
			t.prev('button.tNavToggle').text('-').removeClass('plus').addClass('minus');
			t.parent('li').find('>ul').slideDown(100);
		} else {
			t.prev('button.tNavToggle').text('+').removeClass('minus').addClass('plus');
			t.parent('li').find('>ul').slideUp(100);
		}
		return false;
	});
	// Frequently Asked Question
	var article = $('.faq>.faqBody>.article');
	article.addClass('hide');
	article.find('.a').hide();
	article.eq(0).removeClass('hide');
	article.eq(0).find('.a').show();
	$('.faq>.faqBody>.article>.q>a').click(function(){
		var myArticle = $(this).parents('.article:first');
		if(myArticle.hasClass('hide')){
			article.addClass('hide').removeClass('show');
			article.find('.a').slideUp(100);
			myArticle.removeClass('hide').addClass('show');
			myArticle.find('.a').slideDown(100);
		} else {
			myArticle.removeClass('show').addClass('hide');
			myArticle.find('.a').slideUp(100);
		}
		return false;
	});
	$('.faq>.faqHeader>.showAll').click(function(){
		var hidden = $('.faq>.faqBody>.article.hide').length;
		if(hidden > 0){
			article.removeClass('hide').addClass('show');
			article.find('.a').slideDown(100);
		} else {
			article.removeClass('show').addClass('hide');
			article.find('.a').slideUp(100);
		}
	});
	// Modal Window
	var htmlBody = $('html,body');
	var modalAnchor = $('.modalAnchor');
	var modal = $('.modal');
	var modalBg = $('.modal>.bg');
	var modalFg = $('.modal>.fg');
	var modalCloseHtml = '<button type="button" class="modalClose" title="Close this layer">X</button>';
	var modalBlurHtml = '<button type="button" class="modalBlur"></button>';
	modal.appendTo('body').hide().prepend('<span class="bg"></span>');
	modalFg.prepend(modalCloseHtml);
	var modalClose = $('.modalClose');
	modalClose.clone().appendTo(modalFg);
	modalFg.prepend(modalBlurHtml);
	var modalBlur = $('.modalBlur');
	modalBlur.clone().appendTo(modalFg);
	modalAnchor.click(function(){
		htmlBody.css({'width':'100%','height':'100%'});
		modal.fadeToggle().toggleClass('modalActive');
		modalFg.find('>.modalClose:first').focus();
		return false;
	});
	$(document).keydown(function(event){
		if(event.keyCode != 27) return true;
		htmlBody.removeAttr('style');
		modal.fadeOut().removeClass('modalActive');
		modalAnchor.focus();
		return false;
	});
	$('.modal>.bg, .modalClose').click(function(event){
		htmlBody.removeAttr('style');
		modal.fadeOut().removeClass('modalActive');
		modalAnchor.focus();
		return false;
	});
	$('.modalBlur').focusin(function(event){
		modalClose.click();
	});
	// XEUI container & codeBlock Toggle
	var container = $('.container');
	container.hide().before('<button type="button" class="itemToggle">Show/Hide</button>');
	$('.itemToggle').click(function(){
		$(this).next(container).stop().slideToggle(100);
	});
	var codeBlock = $('.codeBlock');
	codeBlock.hide().before('<button type="button" class="codeToggle">Code</button>');
	$('.codeToggle').click(function(){
		$(this).next(codeBlock).slideToggle(100);
	});
});
