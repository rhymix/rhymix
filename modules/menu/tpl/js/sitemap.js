/* NHN (developers@xpressengine.com) */
jQuery(function($){

var 
	dragging = false,
	$holder  = $('<li class="placeholder">');

$('form.siteMap')
	.delegate('li:not(.placeholder)', {
		'mousedown.st' : function(event) {
			var $this, $uls, $ul, width, height, offset, position, offsets, i, dropzone, wrapper='';

			if($(event.target).is('a,input,label,textarea') || event.which != 1) return;

			dragging = true;

			$this  = $(this);
			height = $this.height();
			width  = $this.width();
			$uls   = $this.parentsUntil('.siteMap').filter('ul');
			$ul    = $uls.eq(-1);

			$ul.css('position', 'relative');

			position = {x:event.pageX, y:event.pageY};
			offset   = getOffset(this, $ul.get(0));

			$clone = $this.clone(true).attr('target', true);

			for(i=$uls.length-1; i; i--) {
				$clone = $clone.wrap('<li><ul /></li>').parent().parent();
			}

			// get offsets of all list-item elements
			offsets = [];
			$ul.find('li').each(function(idx) {
				if($this[0] === this || $this.has(this).length) return true;

				var o = getOffset(this, $ul.get(0));
				offsets.push({top:o.top, bottom:o.top+32, item:this});
			});

			// Remove unnecessary elements from the clone, set class name and styles.
			// Append it to the list
			$clone
				.find('.side,input').remove().end()
				.addClass('draggable')
				.css({
					position: 'absolute',
					opacity : .6,
					width   : width,
					height  : height,
					left    : offset.left,
					top     : offset.top,
					zIndex  : 100
				})
				.appendTo($ul.eq(0));

			// Set a place holder
			$holder
				.css({
					position:'absolute',
					opacity : .6,
					width   : width,
					height  : '5px',
					left    : offset.left,
					top     : offset.top,
					zIndex  :99
				})
				.appendTo($ul.eq(0));

			$this.css('opacity', .6);

			$(document)
				.unbind('mousemove.st mouseup.st')
				.bind('mousemove.st', function(event) {
					var diff, nTop, item, i, c, o;

					dropzone = null;

					diff = {x:position.x-event.pageX, y:position.y-event.pageY};
					nTop = offset.top - diff.y;
					
					for(i=0,c=offsets.length; i < c; i++) {
						o = offsets[i];
						if(o.top <= nTop && o.bottom >= nTop) { 
							dropzone = {element:o.item, state:setHolder(o,nTop)};
							break;
						}
					}

					$clone.css({top:nTop});
				})
				.bind('mouseup.st', function(event) {
					var $dropzone, $li;

					dragging = false;

					$(document).unbind('mousemove.st mouseup.st');
					$this.css('opacity', '');
					$clone.remove();
					$holder.remove();

					// dummy list item for animation
					$li = $('<li />').height($this.height());

					if(!dropzone) return;
					$dropzone = $(dropzone.element);

					$this.before($li);

					if(dropzone.state == 'prepend') {
						if(!$dropzone.find('>ul').length) $dropzone.find('>.side').after('<ul>');
						$dropzone.find('>ul').prepend($this.hide());
					} else {
						$dropzone[dropzone.state]($this.hide());
					}

					$this.slideDown(100, function(){ $this.removeClass('active') });
					$li.slideUp(100, function(){ var $par = $li.parent(); $li.remove(); if(!$par.children('li').length) $par.remove()  });
				});

			return false;
		},
		'mouseover.st' : function() {
			if(!dragging) $(this).addClass('active');
			return false;
		},
		'mouseout.st' : function() {
			if(!dragging) $(this).removeClass('active');
			return false;
		}
	})
	.find('li')
		.prepend('<button type="button" class="moveTo">Move to</button>')
		.append('<span class="vr"></span><span class="hr"></span>')
		.find('input:text')
			.focus(function(){
				var $this = $(this), $label = $this.prev('label'), $par = $this.parent();

				$this.width($par.width() - (parseInt($par.css('text-indent'))||0) - $this.next('.side').width() - 60).css('opacity', '');
				$label.hide();
			})
			.blur(function(){
				var $this = $(this), $label = $this.prev('label'), val = $this.val();

				$this.width(0).css('opacity', 0);
				$label.removeClass('no-text').empty().text(val).show();
				if(!val) $label.addClass('no-text').text('---');
			})
			.each(function(i,input){
				var $this = $(this), id='sitemap-id-'+i;

				$this
					.attr('id', id)
					.css({width:0,opacity:0,overflow:'hidden'})
					.before('<label />')
						.prev('label')
						.attr('for', id)
						.text($this.val());
			})
		.end()
	.end()

$('<div id="dropzone-marker" />')
	.css({display:'none',position:'absolute',backgroundColor:'#000',opacity:0.7})
	.appendTo('body');

function getOffset(elem, offsetParent) {
	var top = 0, left = 0;

	while(elem && elem != offsetParent) {
		top  += elem.offsetTop;
		left += elem.offsetLeft;

		elem = elem.offsetParent;
	}

	return {top:top, left:left};
}

function setHolder(info, yPos) {
	if(Math.abs(info.top-yPos) <= 3) {
		$holder.css({top:info.top-3,height:'5px'});
		return 'before';
	} else if(Math.abs(info.bottom-yPos) <= 3) {
		$holder.css({top:info.bottom-3,height:'5px'});
		return 'after';
	} else {
		$holder.css({top:info.top+3,height:'27px'});
		return 'prepend';
	}
}

/*
$('.tgMap').click(function(){
	var t = $(this);
	t.parent('.siteMap').toggleClass('fold');
	if(t.parent('.siteMap').hasClass('fold')){
		t.text('펼치기').next('.lined').slideUp(200).next('.btnArea').hide();
	} else {
		t.text('접기').next('.lined').slideDown(200).next('.btnArea').show();
	}
	return false;
});
*/
	var editForm = $('#editForm');
	var menuSrl = null;
	var menuForm = null;

	$('a._edit').click(function(){
		var parentKey = $(this).parent().prevAll('._parent_key').val();
		var childKey = $(this).parent().prevAll('._child_key').val();
		menuSrl = $(this).parents().prevAll('input[name=menu_srl]').val();
		menuForm = $('#menu_'+menuSrl);
		var menuItemSrl = null;

		if(parentKey) menuItemSrl = parentKey;
		else if(childKey) menuItemSrl = childKey;
		else
		{
			alert('empty menu item key');
			return;
		}

		var params = new Array();
		var response_tags = new Array('menu_item');
		params['menu_item_srl'] = menuItemSrl;

		exec_xml("menu","getMenuAdminItemInfo", params, completeGetActList, response_tags);
	});

	function completeGetActList(obj)
	{
		var menuItem = obj.menu_item;
		editForm.find('input[name=menu_srl]').val(menuItem.menu_srl);
		editForm.find('input[name=menu_item_srl]').val(menuItem.menu_item_srl);
		editForm.find('input[name=parent_srl]').val(menuItem.parent_srl);
		editForm.find('input[name=menu_name]').val(menuItem.name);
		editForm.find('input=[name=menu_url]').val(menuItem.url);

		var openWindow = menuItem.open_window;
		var openWindowForm = editForm.find('input=[name=menu_open_window]');
		if(openWindow == 'Y') openWindowForm[1].checked = true;
		else openWindowForm[0].checked = true;

		var htmlBuffer = '';
		for(x in menuItem.groupList.item)
		{
			var groupObj = menuItem.groupList.item[x];

			htmlBuffer += '<input type="checkbox" name="group_srls[]" id="group_srls_'+groupObj.group_srl+'" value="'+groupObj.group_srl+'"';
			if(groupObj.isChecked) htmlBuffer += ' checked="checked" ';
			htmlBuffer += '/> <label for="group_srls_'+groupObj.group_srl+'">'+groupObj.title+'</label>'
		}
		$('#groupList').html(htmlBuffer);
	}

	$('a._delete').click(function() {
		menuSrl = $(this).parents().prevAll('input[name=menu_srl]').val();
		menuForm = $('#menu_'+menuSrl);

		var menu_item_srl = $(this).parent().prevAll('._child_key').val();
		menuForm.find('input[name=menu_item_srl]').val(menu_item_srl);
		menuForm.submit();
	});

	$('a._add').click(function()
	{
		var menuItem = obj.menu_item;
		editForm.find('input[name=menu_srl]').val('');
		editForm.find('input[name=menu_item_srl]').val('');
		editForm.find('input[name=parent_srl]').val('');
		editForm.find('input[name=menu_name]').val('');
		editForm.find('input=[name=menu_url]').val('');
		editForm.find('input=[name=menu_open_window]')[0].checked = true;

		var htmlBuffer = '';
		for(x in menuItem.groupList.item)
		{
			var groupObj = menuItem.groupList.item[x];

			htmlBuffer += '<input type="checkbox" name="group_srls[]" id="group_srls_'+groupObj.group_srl+'" value="'+groupObj.group_srl+'"';
			if(groupObj.isChecked) htmlBuffer += ' checked="checked" ';
			htmlBuffer += '/> <label for="group_srls_'+groupObj.group_srl+'">'+groupObj.title+'</label>'
		}
		$('#groupList').html(htmlBuffer);
	});
});
