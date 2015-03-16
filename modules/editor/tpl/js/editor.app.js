(function($){
	"use strict";
	var settings = {
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
			{ name: 'styles' },
			{ name: 'colors' },
			{ name: 'xecomponent' },
			{ name: 'others' }
		],
		allowedContent: true,
		removePlugins: 'stylescombo,language,bidi,flash,pagebreak',
		removeButtons: 'Save,Preview,Print,Cut,Copy,Paste'
	};

	var XeCkEditor = xe.createApp('XeCkEditor', {
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
		API_ONREADY : function() {
		},
		editorInit : function(containerEl, opts) {
			var self = this;
			var $containerEl = containerEl;
			var $form     = $containerEl.closest('form');
			var $contentField = opts.content_field;
			var data = $containerEl.data();
			var editor_sequence = $containerEl.data().editorSequence;
			var ckeconfig = $.extend({}, settings, opts.ckeconfig || {});

			this.editor_sequence = data.editorSequence;

			$form.attr('editor_sequence', data.editorSequence);

			var insance = CKEDITOR.appendTo($containerEl[0], ckeconfig, $contentField.val());
			$containerEl.data('cke_instance', insance);

			insance.on('change', function(e){
				if($contentField.length){
					$contentField.val(e.editor.getData());
				}
			});

			window.editorRelKeys[data.editorSequence] = {};
			window.editorRelKeys[data.editorSequence].primary   = $form.find('[name='+data.editorPrimaryKeyName+']')[0];
			window.editorRelKeys[data.editorSequence].content   = $form.find('[name='+data.editorContentKeyName+']')[0];
			window.editorRelKeys[data.editorSequence].func      = function(seq) {
				return self.getContent.call(self, seq);
			};
			window.editorRelKeys[data.editorSequence].pasteHTML = function(text){
				insance.insertHtml(text, 'html');
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
