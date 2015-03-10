/*
 * @author Arnia <dev@karybu.org>
 * @modifier XpressEngine <developers@xpressengine.com>
 */
CKEDITOR.plugins.add('xe_component', {
	requires: 'menubutton',
	icons: 'xe_component',

	init: function(editor) {
		var config=editor.config;
		var items = {};

		editor.addMenuGroup('xe_component');

		var openSelectComponent = function() {
			window.editorPrevSrl = config.xe_editor_sequence;
			openComponent(this.name, config.xe_editor_sequence);
		};

		for(var key in config.xe_component_arrays) {
			var component_name = key;
			var component_title = config.xe_component_arrays[key];

			items[component_name] = {
				label: component_title,
				group: 'xe_component',
				icon : request_uri + 'modules/editor/components/' + component_name + '/component_icon.gif',
				onClick: openSelectComponent
			};
		}

		editor.addMenuItems( items );

		editor.ui.add('xe_component', CKEDITOR.UI_MENUBUTTON, {
			label: '추가 기능', // @TODO lang
			modes: {
				wysiwyg: 1
			},
			onMenu: function() {
				var active = {};

				for(var p in items)
				{
					active[p] = CKEDITOR.TRISTATE_OFF;
				}

				return active;
			}
		});

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

			if(editor_component) {
				evt.cancel();
				window.openComponent(editor_component, config.xe_editor_sequence);
			}
		});
	}
});

