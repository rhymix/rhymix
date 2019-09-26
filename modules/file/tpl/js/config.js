(function($) {
	$(function() {
		$('#use_default_file_config').on('change', function() {
			if ($(this).is(':checked')) {
				$('.use_custom_file_config').hide();
			} else {
				$('.use_custom_file_config').show();
			}
		});
		$('#use_image_default_file_config').on('change', function() {
			if ($(this).is(':checked')) {
				$('.use_custom_image_file_config').hide();
			} else {
				$('.use_custom_image_file_config').show();
			}
		});
		$('#use_video_default_file_config').on('change', function() {
			if ($(this).is(':checked')) {
				$('.use_custom_video_file_config').hide();
			} else {
				$('.use_custom_video_file_config').show();
			}
		});
	});
})(jQuery);
