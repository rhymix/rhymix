(function($){
	"use strict";
	var App = window.xe.getApp('xeEditorApp')[0];
	var CK = window.CKEDITOR;

	var xeCKEditor = App.createPlugin("CKEditor", {
		instance_prefix : 'ckeditor_instance_',

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
		editorInit : function(sequence, obj) {
			var $editor_area = jQuery("#ckeditor_instance_"+sequence);
			var $form     = $editor_area.closest('form');
			var $contentField = $('input[name=' + obj.content_key + ']');
			var ckconfig = obj.ckconfig || {};
			ckconfig.xe_editor_sequence = sequence;

			$form.attr('sequence', sequence);

			var insance = CKEDITOR.appendTo(this.instance_prefix + sequence, ckconfig, obj.content);
			$editor_area.data('cke_instance', insance);

			insance.on('change', function(e){
				if($contentField.length){
					$contentField.val(e.editor.getData());
				}
			});

			window.editorRelKeys[sequence] = {};
			window.editorRelKeys[sequence].primary   = $form.find('[name='+obj.primary_key+']').val();
			window.editorRelKeys[sequence].content   = $form.find('[name='+obj.content_key+']').val();
			window.editorRelKeys[sequence].func      = this.getContent;
			window.editorRelKeys[sequence].pasteHTML = function(text){
				insance.insertHtml(text, 'html');
			}
		},
		getContent : function() {
			var self = this;
			var content = this.getInstance.getData();

			self.cast('GET_CONTENT', [content]);

			return content;
		},
		API_ONREADY : function() {
		},
		API_GET_CONTENT: function() {
			console.info('CK @ API GET CONTENT');
		},
		getInstance : function(name) {
			return CKEDITOR.instances[name];
		}
	});

	App.registerPlugin(new xeCKEditor());
})(jQuery);
