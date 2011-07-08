/* NHN (developers@xpressengine.com) */
jQuery(function($){
	// Label Overlapping
	var overlapLabel = $('.form li').find('>:text,>:password,>textarea').prev('label');
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
	// Checked
	var inputRC = $('input[type=radio], input[type=checkbox]');
	inputRC.change(function(){
		var myName = $(this).attr('name');
		inputRC.filter('[name='+myName+']').not(':checked').next('label').css('fontWeight','normal');
		$(this).filter(':checked').next('label').css('fontWeight','bold');
	});
	inputRC.change();
	// Check All
	var formThCheck = $('.form th>:checkbox');
	formThCheck.change(function(){
		var formTdCheck = $(this).parents('table').find('td>:checkbox');
		if($(this).is(':checked')){
			formTdCheck.attr('checked','checked').change();
		} else {
			formTdCheck.removeAttr('checked').change();
		}
	});
	formThCheck.change();
	// Global Navigation Bar
	var gnb = $('div.gnb');
	var gnb_i = gnb.find('>ul>li');
	var gnb_a = gnb_i.find('>a');
	gnb.removeClass('jx');
	gnb_i.find('>ul').hide();
	gnb.find('>ul>li[class=active]').find('>ul').show();
	function gnbToggle(event){
		var t = $(this);
		gnb_i.removeClass('active');
		if (t.next('ul').is(':hidden')) {
			gnb_i.find('>ul').slideUp(100);
			t.next('ul').slideDown(100);
			t.parent('li').addClass('active');
		} else if (t.next('ul').is(':visible')){
			t.next('ul').show();
			t.parent('li').addClass('active');
		} else if (!t.next('ul').langth) {
			gnb_i.find('>ul').slideUp(100);
			t.parent('li').removeClass('active');
		};
	}
	gnb_a.focus(gnbToggle).click(gnbToggle);
	gnb_a.mouseover(function(){
		if($(document).width()>640){
			$(this).click();
		} else {
			return false;
		}
	});
	gnb.mouseleave(function(){
		if($(document).width()>640){
			gnbToggle();
		} else {
			return false;
		}
	});
	gnb.find('li:last-child>a, li:last-child>ul>li:last-child>a').blur(gnbToggle);
	// Lined Tab Navigation
	var tab_line = $('div.tab.line');
	var tab_line_i = tab_line.find('>ul>li');
	var tab_line_ii = tab_line.find('>ul>li>ul>li');
	tab_line.removeClass('jx');
	if($(document).width()<=640){
		tab_line.addClass('jx');	
	}
	$(window).resize(function(){
		if($(document).width()<=640){
		tab_line.addClass('jx');	
		} else {
		tab_line.removeClass('jx');
		}
	});
	tab_line_i.find('>ul').hide();
	tab_line_i.find('>ul>li[class=active]').parents('li').attr('class','active');
	tab_line.find('>ul>li[class=active]').find('>ul').show();
	function lineTabMenuToggle(event){
		if (!tab_line.hasClass('jx')){
			var t = $(this);
			tab_line_i.find('>ul').hide();
			t.next('ul').show();
			tab_line_i.removeClass('active');
			t.parent('li').addClass('active');
			return false;
		}
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
	if($(document).width()<=640){
		tab_face.addClass('jx');	
	}
	$(window).resize(function(){
		if($(document).width()<=640){
		tab_face.addClass('jx');	
		} else {
		tab_face.removeClass('jx');
		}
	});
	tab_face_i.find('>ul').hide();
	tab_face_i.find('>ul>li[class=active]').parents('li').attr('class','active');
	tab_face.find('>ul>li[class=active]').find('>ul').show();
	function faceTabMenuToggle(event){
		if (!tab_face.hasClass('jx')){
			var t = $(this);
			tab_face_i.find('>ul').hide();
			t.next('ul').show();
			tab_face_i.removeClass('active');
			t.parent('li').addClass('active');
			return false;
		}
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
	if($(document).width()<=640){
		tab_list.addClass('jx');	
	}
	$(window).resize(function(){
		if($(document).width()<=640){
		tab_list.addClass('jx');
		tab_list.css('height','auto');
		} else {
		tab_list.removeClass('jx');
		tab_list.css('height', tab_list.find('>ul>li.active>ul').height()+40);
		}
	});
	tab_list_i.find('>ul').hide();
	tab_list.find('>ul>li[class=active]').find('>ul').show();
	if (!tab_list.hasClass('jx')){
		tab_list.css('height', tab_list.find('>ul>li.active>ul').height()+40);
	} else {
		tab_list.css('height','auto');
	}
	function listTabMenuToggle(event){
		if (!tab_list.hasClass('jx')){
			var t = $(this);
			tab_list_i.find('>ul').hide();
			t.next('ul').show();
			tab_list_i.removeClass('active');
			t.parent('li').addClass('active');
			tab_list.css('height', t.next('ul').height()+40);
			return false;
		}
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
	var tNavPlus = '<button type="button" class="tNavToggle plus">+</button>';
	var tNavMinus = '<button type="button" class="tNavToggle minus">-</button>';
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
	// Layer
	var layerAnchor = $('.layerAnchor[href^=#]');
	var layer = $('.layer');
	var layerCloseHtml = '<button type="button" class="layerClose" title="Close this layer">X</button>';
	var layerBlurHtml = '<button type="button" class="layerBlur"></button>';
	layer.hide().prepend(layerCloseHtml);
	var layerClose = $('.layerClose');
	layerClose.eq(0).clone().appendTo(layer);
	layer.prepend(layerBlurHtml);
	var layerBlur = $('.layerBlur');
	layerBlur.eq(0).clone().appendTo(layer);
	layerAnchor
		.click(function(){
			$($(this).attr('href')).fadeToggle(200).find('>.layerClose:first').focus();
			return false;
		})
		.keypress(function(){
			if(event.keyCode != 32) return true;
			$(this).click();
			return false;
		});
	function closeLayer() {
		var closeId = layer.filter(':visible').attr("id");
		if(closeId) layerAnchor.filter('[href="#'+closeId+'"]').focus();
		layer.fadeOut(200);
	}
	$(document).keydown(function(event){
		if(event.keyCode != 27) return true; // ESC
		return closeLayer();
	});
	$('.layerClose').click(closeLayer);
	$('.layerBlur').focusin(function(event){
		layerClose.click();
	});
	// Modal Window
	var htmlBody = $('html,body');
	var modalAnchor = $('.modalAnchor');
	var modal = $('.modal');
	var modalBg = modal.find('>.bg');
	var modalFg = modal.find('>.fg');
	var modalCloseHtml = '<button type="button" class="modalClose" title="Close this layer">X</button>';
	var modalBlurHtml = '<button type="button" class="modalBlur"></button>';
	modal
		.hide()
		.appendTo('body')
		.height($(document).height())
		.prepend('<span class="bg"></span>')
		.append('<!--[if IE 6]><iframe class="ie6"></iframe><![endif]-->');
	modalFg
		.prepend(modalCloseHtml)
		.prepend(modalBlurHtml);
	var modalClose = $('.modalClose');
	var modalBlur = $('.modalBlur');
	modalClose.eq(0).clone().appendTo(modalFg);
	modalBlur.eq(0).clone().appendTo(modalFg);
	modalAnchor
		.click(function(){
			if(typeof document.body.style.maxHeight == "undefined"){
				htmlBody.css({'width':'100%','height':'100%'});
			}
			modal.fadeToggle(200).toggleClass('modalActive');
			modalFg.find('>.modalClose:first').focus();
			$(this).addClass('active');
		})
		.keypress(function(){
			if(event.keyCode != 32) return true;
			$(this).click();
			return false;
		});
	function closeModal() {
		if(typeof document.body.style.maxHeight == "undefined"){
			htmlBody.removeAttr('style');
		}
		modal.fadeOut(200).removeClass('modalActive');
		$('.modalAnchor.active').focus().removeClass('active');
		return false;
	}
	$(document).keydown(function(event){
		if(event.keyCode != 27) return true; // ESC
		if(modal.find('.tgContent:visible').length == 0) return closeModal();
	});
	$('.modal>.bg, .modalClose, .modal .cancel').click(closeModal);
	$('.modalBlur').focusin(function(event){
		modalClose.click();
	});
	// Toggle
	var tgContent = $('.tgContent');
	var tgBlurHtml = '<button type="button" class="tgBlur"></button>';
	tgContent.hide().prepend(tgBlurHtml).mouseleave(function(){closeTg()});
	var tgBlur = $('.tgBlur');
	tgBlur.eq(0).clone().appendTo(tgContent);
	function offsetToggle(){
		tgContent.filter(':visible').parent().css('position','relative');
		setTimeout(function(){
			tgContent.filter(':hidden').parent().css('position','');
		}, 300);
	}
	$('.tgSimple').click(function(){
		$($(this).attr('href')).toggle().find('a, input, button:not(.tgBlur), select, textarea').eq(0).focus();
		offsetToggle();
		return false;
	});
	$('.tgSlide').click(function(){
		$($(this).attr('href')).slideToggle(100).find('a, input, button:not(.tgBlur), select, textarea').eq(0).focus();
		offsetToggle();
		return false;
	});
	$('.tgFade').click(function(){
		$($(this).attr('href')).fadeToggle(200).find('a, input, button:not(.tgBlur), select, textarea').eq(0).focus();
		offsetToggle();
		return false;
	});
	$('.tgSimple, .tgSlide, .tgFade').keypress(function(){
		if(event.keyCode != 32) return true;
		$(this).click();
		return false;
	});
	function closeTg() {
		var closeId = tgContent.filter(':visible').attr('id');
		if(closeId) $('.tgSimple, .tgSlide, .tgFade').filter('[href="#'+closeId+'"]').focus();
		tgContent.prev('input').focus();
		tgContent.fadeOut(200);
	}
	$(document).keydown(function(event){
		if(event.keyCode != 27) return true; // ESC
		return closeTg();
	});
	$('.tgBlur').focusin(closeTg);
	// Portlet Action
	var action = $('.portlet .action');
	var action_li = action.parent('li');
	action.hide().css({'position':'absolute'});
	action_li.mouseleave(function(){
		action.fadeOut(100);
		return false;
	});
	action_li.mouseenter(function(){
		action_li.mouseleave();
		$(this).find('>.action').fadeIn(100);
		return false;
	});
	action_li.find('*:first-child').focusin(function(){
		$(this).parent('li').mouseenter();
	});
	// Waiting for server response
	var htmlBody = $('html,body');
	var wfsrAnchor = $('.wfsrAnchor');
	$('body').append('<div id="wfsr"><span class="bg"></span><div class="fg"><p>서버에 요청중입니다. 잠시만 기다려 주세요. <button type="button" class="reAction">다시시도</button> <button type="button" class="cancel">실행취소</button></p></div></div>');
	var wfsr = $('#wfsr');
	var wfsrBg = wfsr.find('>.bg');
	var wfsrFg = wfsr.find('>.fg');
	wfsr.hide();
	wfsr.append('<!--[if IE 6]><iframe class="ie6"></iframe><[endif]-->');
	wfsrAnchor.click(function(){
		htmlBody.css({'width':'100%','height':'100%'});
		wfsr.fadeToggle(200).addClass('wfsrActive');
		wfsrFg.find('.reAction:first').focus();
		$(this).addClass('active');
	});
	function closeWfsr() {
		htmlBody.removeAttr('style');
		wfsr.fadeOut(200).removeClass('wfsrActive');
		$('.wfsrAnchor.active').focus().removeClass('active');
		return false;
	}
	$(document).keydown(function(event){
		if(event.keyCode != 27) return true; // ESC
		closeWfsr();
	});
	$('#wfsr .reAction, #wfsr .cancel').click(closeWfsr);
	// Delete
	$('.delete').click(function(){
		confirm('Delete this(these)? Undo is impossible.');
	});
	// Section Collapse
	var h2 = $('.content:not(.dashboard) .h2:gt(0)');
	var h2AnchorHTML = '<button type="button" class="h2Anchor"></button>';
	h2
		.append(h2AnchorHTML)
		.each(function(){
			$(this).parent().children().not('.h2').hide();
			$(this).find('.h2Anchor')
			.text('Show')
			.click(function(){
				var t = $(this);
				t.parent('.h2').parent().children().not('.h2').slideToggle(200);
				setTimeout(function(event){
					if(t.parent('.h2').next().is(':visible')) {
						t.text('Hide');
					} else {
						t.text('Show');
					}
				}, 300);
			});
		});
	// Site Map
	var siteMap = $('.siteMap');
	var siteItem = siteMap.find('li');
	siteItem
		.prepend('<button type="button" class="moveTo">Move to</button>')
		.append('<span class="vr"></span><span class="hr"></span>')
		.mouseover(function(){
			$(this).addClass('active');
			$('.vr').each(function(){
				var myHeight = $(this).parent('li').height();
				$(this).height(myHeight);
			});
			return false;
		})
		.mouseout(function(){
			$(this).removeClass('active');
		})
		.find('.moveTo+input').each(function(){
			$(this).width(this.value.length+'em');
		});
	siteMap.find('.moveTo')
		.focus(function(){
			$(this).parent('li').mouseover();
		})
		.blur(function(){
			$(this).mouseout();
		});
	siteMap.find('li:first-child').css('border','0');
	// Site Map List(Layer)
	var siteMapList = $('#siteMapList');
	siteMapList.find('.portlet').hide().eq(0).show();
	siteMapList.append('<p class="btnArea"><button type="button">&rsaquo; more</button></p>');
	var siteMapMore = siteMapList.find('.btnArea>button');
	siteMapMore.click(function(){
		if(siteMapList.find('.portlet:visible').length <= 1){
			siteMapList.find('.portlet').slideDown(200);
			$(this).html('&rsaquo; less');
		} else {
			siteMapList.find('.portlet').eq(!0).slideUp(200);
			$(this).html('&rsaquo; more');
		}
	});
	// Mid Suggestion
	var midUrl = $('#midUrl');
	var midSuggestion = $('#midSuggestion');
	var findModule = $('#findModule');
	midSuggestion.css('position','absolute');
	midUrl.keypress(function(){
		$(this).next('.tgContent').fadeIn(200);
		midUrl.css('background','url(./img/preLoader16.gif) no-repeat 268px center');
	});
	midUrl.keyup(function(){
		midUrl.css('background','');
	});
	midSuggestion
		.find('li:first-child>button').css('fontWeight','bold').end()
		.find('li:gt(0)>button').click(function(){
			var myValue = $(this).text();
			midUrl.val(myValue);
			return closeTg();
		});
	findModule.find('td>button').click(function(){
		var myValue = $(this).parent('td').parent('tr').find('th:first a').eq(0).text();
		midUrl.val(myValue);
		return closeTg();
	});
	// Theme & Skin Preview
	$('.thumbPreview')
		.find('.thumb')
			.each(function(){
				if($(this).find('img').length==0 && $(this).text().length==0){
					$(this).text('Thumbnail does not exist');
				}
				$(this).click(function(){
					$(this).next(':radio').attr('checked','checked');
					$(this).parents('.thumbPreview').find(':radio').change();
				});
			});
	var themePreview = $('#theme>.thumbPreview');
	var skinPreview = $('#skin>.thumbPreview');
	function iCheck(){
		skinPreview.find('.i').removeClass('checked');
		skinPreview.find(':radio:checked').prev('.thumb').parent('.i').addClass('checked');
		themePreview.find('.i').removeClass('checked');
		themePreview.find(':radio:checked').prev('.thumb').parent('.i').addClass('checked');
	}
	iCheck();
	skinPreview.find(':radio').change(function(){
		themePreview.find('label').css('fontWeight','');
		themePreview.find(':radio:last').attr('checked','checked').next('label').css('fontWeight','bold');
		iCheck();
	});
	themePreview.find(':radio').change(function(){
		if($(this).is(':checked') && skinPreview.is(':hidden')){
			$('#skin').find('.h2Anchor').click();
		}
		iCheck();
	});
	// FTP Suggestion
	var ftp_path = $('#ftp_path');
	var ftpSuggestion = $('#ftpSuggestion');
	ftpSuggestion.css('position','absolute').find('.tgBlur').eq(0).remove();
	ftpSuggestion.find('li:not(:first-child)>button').click(function(){
		var setValue = ftp_path.val();
		var myValue = $(this).text();
		ftp_path.val(setValue+myValue);
	});
	// Sign Up Form
	var signUpForm = $('.signUpForm');
	signUpForm
		.find('li').each(function(){
			$(this).append('<span class="side"><button type="button" class="up">Up</button> <button type="button" class="down">Down</button></span>');
		}).end()
		.find('.btnArea>.side').remove().end()
		.find('.userItem>.side').each(function(){
			$(this).append('<button type="button" class="edit">Edit</button> <button type="button" class="delete">Delete</button>');
		});
	signUpForm.find('.up').click(function(){
		var myItem = $(this).parent('.side').parent('li');
		myItem.prev('li').before(myItem);
		myItem.css('background','#ffc');
		setTimeout(function(){
			myItem.css('background','#fff');
		}, 2000);
	});
	signUpForm.find('.down').click(function(){
		var myItem = $(this).parent('.side').parent('li');
		myItem.next('li').after(myItem);
		myItem.css('background','#ffc');
		setTimeout(function(){
			myItem.css('background','#fff');
		}, 2000);
	});
	signUpForm.find('.edit').click(function(){
		$('.modalAnchor').click();
	});
});

