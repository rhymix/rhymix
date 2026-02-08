function doGetSkinColorset(skin, type) {
	type = type == 'M' ? 'M' : 'P';

	var params = {
		'skin' : skin,
		'type': type,
	};

	Rhymix.ajax('message.getMessageAdminColorset', params).then(function(ret) {
		var $container = type == 'M' ? $('#mcolorset') : $('#colorset');
		var old_h = $container.is(':visible') ? $container.outerHeight() : 0;

		if (ret.tpl == '') {
			$container.hide();
		} else {
			$container.show();
			var $colorset = (type == 'M') ? $('#message_mcolorset') : $('#message_colorset');
			$colorset.html(ret.tpl);
		}

		var new_h = $container.is(':visible') ? $container.outerHeight() : 0;
		try {
			fixAdminLayoutFooter(new_h - old_h)
		} catch (e) {};
	});
}

$(function() {
	doGetSkinColorset($('#skin').val());
	doGetSkinColorset($('#mskin').val(), 'M');
});
