function doSubmitConfig()
{
	var $forms = jQuery('#layout_config').find('input[name][type="hidden"], input[name][type="text"], input[name][type="checkbox"]:checked, select[name], textarea[name]');
	var $configForm = jQuery('#config_form');
	var $container = $configForm.children('div');
	$container.empty();
	
	$forms.each(function($)
	{
		var $this = jQuery(this);

		if($this.parents('.imageUpload').length) return;

		var $input = jQuery('<input>').attr('type', 'hidden').attr('name', $this.attr('name')).val($this.val());
		$container.append($input);
	});

	$configForm.submit();
}

function afterUploadConfigImage(name, fileName)
{
	jQuery('#preview_' + name + ' img').attr('src', fileName);
	jQuery('#preview_' + name).show();
	jQuery('#file_' + name).val('');
}

function afterDeleteConfigImage(name)
{
	jQuery('#preview_' + name + ' img').removeAttr('src');
	jQuery('#preview_' + name).hide();
}
