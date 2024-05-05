function doPreviewLayoutCode()
{
	var $form = jQuery('#fo_layout'), $act = $form.find('input[name=act]');
	var og_act = $act.val();

	$form.attr('target', '_LayoutPreview');
	$act.val('dispLayoutPreview');
	$form.submit();

	$form.removeAttr('target');
	$act.val(og_act);
}

$(function() {
	$('.reset_layout').on('click', function(e) {
		var msg = $(this).data('confirmationMsg');
		if (!confirm(msg)) {
			e.preventDefault();
		}
	});
});
