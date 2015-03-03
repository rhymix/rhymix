/*
 * @author Arnia <dev@karybu.org>
 * @modifier XpressEngine <developers@xpressengine.com>
 */
CKEDITOR.plugins.add('xe_component', {
	requires: 'richcombo',
	icons: 'xe_component',

	init: function(editor) {
		var config=editor.config;

		editor.on( 'doubleclick', function( evt ) {
			var element = evt.data.element;
			var editor_component = element.getAttribute('editor_component');
			window.editorPrevNode = element.$;

			if(!editor_component && element.is('img')) {
				editor_component = 'image_link';
			}

			if(editor_component)
			{
				evt.cancel();
				window.openComponent(editor_component, config.xe_editor_sequence);
			}
		});

		editor.ui.addRichCombo('Xe_component', {
			label: '확장기능', // @TODO: lang
			title: 'Extension Components',
			panel: {
				css: [CKEDITOR.skin.getPath('editor')].concat(config.contentsCss),
				multiSelect: false
			},
			init: function(){
				this.startGroup('Extension Components');
				for(var key in config.xe_component_arrays){
					var component_name=key;
					var component_title=config.xe_component_arrays[key];
					this.add(component_name, component_title, component_title);
				}
			},
			onClick: function(value){
				if(typeof openComponent=='function'){
					if(config.xe_editor_sequence)
					{
						window.editorPrevSrl = config.xe_editor_sequence;
						openComponent(value, config.xe_editor_sequence);
					}
				}
			}
		});
	}
});
