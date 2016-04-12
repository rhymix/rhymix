var ncenterlite_need_highlight = true;
function ncenterlite_highlight() {
		function y(element, startcolour, endcolour, time_elapsed, c) {
			var interval = 30;
			var steps = time_elapsed / interval;
			var red_change = (startcolour[0] - endcolour[0]) / steps;
			var green_change = (startcolour[1] - endcolour[1]) / steps;
			var blue_change = (startcolour[2] - endcolour[2]) / steps;
			var currentcolour = startcolour;
			var stepcount = 0;
			element.style.backgroundColor = 'rgb(' + currentcolour.toString() + ')';
			var timer = setInterval(function(){
				currentcolour[0] = parseInt(currentcolour[0] - red_change);
				currentcolour[1] = parseInt(currentcolour[1] - green_change);
				currentcolour[2] = parseInt(currentcolour[2] - blue_change);
				element.style.backgroundColor = 'rgb(' + currentcolour.toString() + ')';
				stepcount += 1;
				if (stepcount >= steps) {
				//element.style.backgroundColor = 'rgb(' + endcolour.toString() + ')';
				element.style.backgroundColor = c; //'rgb(' + endcolour.toString() + ')';
				clearInterval(timer);
				}
			}, interval);
		}

		var s = decodeURIComponent(location.href).replace(/.*#comment_/,'');
		if(!s || s === decodeURIComponent(location.href)) return;
		s = 'comment_' + s;
		jQuery('.xe_content').each(function(){
			var t = jQuery(this);
			if(t.hasClass(s) || (new RegExp(s + '_')).test(t.attr('class'))){
				var c = t.css('display','block').css('background-color');
				y(this, [255,255,60], [255,255,255], 750, c);
				ncenterlite_need_highlight = false;
			}
		});
}

if(typeof _viewSubComment == 'function') {
	old__viewSubComment = _viewSubComment;
	_viewSubComment = function(ret_obj) {
		old__viewSubComment(ret_obj);
		if(ncenterlite_need_highlight) {
			setTimeout(function(){
				var s = decodeURIComponent(location.href).match(/#.*comment_([0-9]+)/);
				if(s) {
					ncenterlite_highlight();
					location.href = '#social_comment_' + s[1];
				}
			}, 500);
		}
	};
}

jQuery(function(){
	ncenterlite_highlight();
});
