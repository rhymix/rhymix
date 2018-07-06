/**
 * iOS enter key fix for IME
 *
 * https://github.com/rhymix/rhymix/issues/932
 */
CKEDITOR.plugins.add( 'ios_enterkey',
{
	icons: 'ios_enterkey',
	init: function(editor)
	{
		editor.on('contentDom', function()
		{
			var editable = editor.editable();
			editable.attachListener(editable, 'keyup', function(e)
			{
				if(e.data.getKey() === 13)
				{
					$(editor.document.$).find('.cke_wysiwyg_div').blur();
					$(editor.document.$).find('.cke_wysiwyg_div').focus();
				}
			});
		});
	}
});
