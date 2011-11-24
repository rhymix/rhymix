jQuery(document).ready(function($){
	$('.uploaded_image').css('cursor', 'pointer');
	$('.uploaded_image_path').hide();
	$('.uploaded_image').bind('click', function(e){
		var path = $(this).siblings('.uploaded_image_path').html();
		var html = '<div class="selected_image_path">' + path + '</div>';

		$('.selected_image_path').remove();
		$('.uploaded_image_list').after(html);
	});
});

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