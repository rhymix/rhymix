(function($){
	"use strict";
	var default_ckeconfig = {
		bodyClass: 'xe_content editable',
		toolbarCanCollapse: true,
		toolbarGroups: [
			{ name: 'clipboard',   groups: [ 'undo', 'clipboard' ] },
			{ name: 'editing',     groups: [ 'find', 'selection' ] },
			{ name: 'links' },
			{ name: 'insert' },
			{ name: 'tools' },
			{ name: 'document',    groups: [ 'mode' ] },
			'/',
			{ name: 'basicstyles', groups: [ 'basicstyles', 'cleanup' ] },
			{ name: 'paragraph',   groups: [ 'list', 'indent', 'blocks', 'align', 'bidi' ] },
			'/',
			{ name: 'styles' },
			{ name: 'colors' },
			{ name: 'xecomponent' },
			{ name: 'others' }
		],
		allowedContent: true,
		removePlugins: 'stylescombo,language,bidi,flash,pagebreak',
		removeButtons: 'Save,Preview,Print,Cut,Copy,Paste',
		uiColor: '#EFF0F0'
	};

	function arrayUnique(data) {
		return $.grep(data, function(v, k){
			return (v.length && $.inArray(v, data) === k);
		});
	}

	var XeCkEditor = xe.createApp('XeCkEditor', {
		ckeconfig: {},
		editor_sequence: null,
		init : function() {
			var self = this;

			CKEDITOR.on('instanceCreated', function(evt){
				self.cast('CKEDITOR_CREATED');
			});

			CKEDITOR.on('ready', function(evt){
				self.cast('CKEDITOR_READY');
			});

			CKEDITOR.on('instanceReady', function(evt){
				self.cast('CKEDITOR_INSTANCE_READY');
			});

			CKEDITOR.on('instanceLoaded', function(evt){
				self.cast('CKEDITOR_LOADED');
			});
		},
		editorInit : function(containerEl, opts) {
			var self = this;
			var $containerEl = containerEl;
			var $form     = $containerEl.closest('form');
			var $contentField = opts.content_field;
			var data = $containerEl.data();
			var editor_sequence = $containerEl.data().editorSequence;

			this.ckeconfig = $.extend({}, default_ckeconfig, opts.ckeconfig || {});

			this.editor_sequence = data.editorSequence;
			$form.attr('editor_sequence', data.editorSequence);
			
			if(CKEDITOR.env.mobile) CKEDITOR.env.isCompatible = true;

			var instance = CKEDITOR.appendTo($containerEl[0], {}, $contentField.val());

			instance.on('customConfigLoaded', function(e) {
				instance.config = $.extend({}, e.editor.config, self.ckeconfig);

				if($.isFunction(CKEDITOR.editorConfig)) {
					var customConfig = {};
					CKEDITOR.editorConfig(customConfig);

					$.each(customConfig, function(key, val) {
						instance.config[key] = val;
					});
				}

				var bodyClass = e.editor.config.bodyClass.split(' ');
				bodyClass.push(default_ckeconfig.bodyClass);
				bodyClass = arrayUnique(bodyClass);
				instance.config.bodyClass = bodyClass.join(' ');

				if(opts.loadXeComponent) {
					var extraPlugins = e.editor.config.extraPlugins.split(',');

					extraPlugins.push('xe_component');
					extraPlugins = arrayUnique(extraPlugins);
					instance.config.extraPlugins = extraPlugins.join(',');
				}

				if(!opts.enableToolbar) instance.config.toolbar = [];
			});

			$containerEl.data('cke_instance', instance);

			window.editorRelKeys[data.editorSequence] = {};
			window.editorRelKeys[data.editorSequence].primary   = $form.find('[name='+data.editorPrimaryKeyName+']')[0];
			window.editorRelKeys[data.editorSequence].content   = $form.find('[name='+data.editorContentKeyName+']')[0];
			window.editorRelKeys[data.editorSequence].func      = function(seq) {
				return self.getContent.call(self, seq);
			};
			window.editorRelKeys[data.editorSequence].pasteHTML = function(text){
				instance.insertHtml(text, 'html');
			};
		},
		getContent : function(seq) {
			var self = this;
			var content = _getCkeInstance(seq).getData();
			self.cast('GET_CONTENT', [content]);

			return content;
		},
		getInstance : function(name) {
			return CKEDITOR.instances[name];
		},
		API_EDITOR_CREATED : function(){
		},
	});

	// Shortcut function in jQuery
	$.fn.XeCkEditor = function(opts) {
		var u = new XeCkEditor(this.eq(0), opts);

		if(u) {
			xe.registerApp(u);
			u.editorInit(this.eq(0), opts);
		}

		return u;
	};

	// Shortcut function in XE
	window.xe.XeCkEditor = function() {
		var u = new XeCkEditor();

		return u;
	};

})(jQuery);
