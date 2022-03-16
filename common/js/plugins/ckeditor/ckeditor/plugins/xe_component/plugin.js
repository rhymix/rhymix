CKEDITOR.plugins.add('xe_component', {
	requires: 'button',

	init: function(editor) {
		var config = editor.config;

		editor.ui.addToolbarGroup('xecomponent');

		for(var key in config.xe_component_arrays) {
			var component_name = key;
			var component_title = config.xe_component_arrays[key];

			(function(component_name) {
				editor.addCommand('openXeComponent_' + component_name , {
					exec: function() {
						window.openComponent(component_name, config.xe_editor_sequence);
					}
				});
			})(component_name);

			editor.ui.addButton( component_name, {
				label: component_title,
				icon : request_uri + 'modules/editor/components/' + component_name + '/component_icon.gif',
				command: 'openXeComponent_' + component_name,
				toolbar: 'xecomponent'
			} );
		}

		editor.on( 'doubleclick', function( evt ) {
			var element = evt.data.element;
			var editor_component = element.getAttribute('editor_component');
			window.editorPrevNode = element.$;

			while(!editor_component && element) {
				editor_component = element.getAttribute('editor_component');
				if(editor_component) {
					window.editorPrevNode = element.$;
				} else {
					element = element.getParent();
				}
			}

			if(!element) element = evt.data.element;
			if(!editor_component && element && element.is('img')) {
				editor_component = 'image_link';
			}
			if(editor_component && !config.xe_component_arrays[editor_component]) {
				editor_component = null;
			}

			if(editor_component) {
				evt.cancel();
				window.openComponent(editor_component, config.xe_editor_sequence);
			}
		});
	}
});
