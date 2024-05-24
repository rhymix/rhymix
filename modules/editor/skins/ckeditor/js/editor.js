'use strict';

/**
 * Initialize each instance of CKEditor on the page.
 */
$(function() {
	$('.rx_ckeditor').each(function() {

		// Load editor configuration.
		const container = $(this);
		const form = container.closest('form');
		const editor_sequence = parseInt(container.data('editorSequence'), 10);
		const config = container.data('editorConfig');

		// Apply auto dark mode.
		if (config.auto_dark_mode) {
			$('body').addClass('cke_auto_dark_mode');
			if (getColorScheme() === 'dark') {
				if (config.skin !== 'moono-lisa') {
					config.skin = 'moono-dark';
				}
			}
		}

		// If the default font is not set, use the browser default font.
		if (config.default_font === 'none' && window.getComputedStyle) {
			let test_content = $('<div class="rhymix_content xe_content"></div>').hide().appendTo($(document.body));
			let test_styles = window.getComputedStyle(test_content[0], null);
			if (test_styles && test_styles.getPropertyValue) {
				let default_font = test_styles.getPropertyValue('font-family');
				if (default_font) {
					config.default_font = $.trim(default_font.split(',')[0].replace(/['"]/g, ''));
					config.css_content = '.rhymix_content.editable { font-family:' + default_font + '; } ' + config.css_content;
				}
			}
		}

		// Define the initial structure for CKEditor settings.
		const settings = {
			ckeconfig: {
				height: config.height,
				skin: config.skin,
				contentsCss: config.css_files,
	            font_defaultLabel: config.default_font,
	            font_names: config.fonts.join(';'),
	            fontSize_defaultLabel: config.default_font_size,
	            fontSize_sizes: config.font_sizes.join(';'),
				toolbarStartupExpanded: !config.hide_toolbar,
				toolbarCanCollapse: true,
				allowedContent: true,
				startupFocus: config.focus,
				language: config.language,
				iframe_attributes: {},
				versionCheck: false,
				rx_allow_upload: config.allow_upload,
				xe_editor_sequence: editor_sequence,
			},
			loadXeComponent: true,
			enableToolbar: true
		};

		// Add stylesheets from the current document.
		$('link[rel=stylesheet]').each(function() {
			settings.ckeconfig.contentsCss.push($(this).attr('href'));
		});

		// Add and remove plugins.
		if (config.add_plugins) {
			settings.ckeconfig.extraPlugins = config.add_plugins.join(',');
		}
		if (config.remove_plugins) {
			settings.ckeconfig.removePlugins = config.remove_plugins.join(',');
		}

		// Add editor components.
		if (config.enable_component) {
			settings.ckeconfig.xe_component_arrays = config.components;
		} else {
			settings.ckeconfig.xe_component_arrays = {};
			settings.loadXeComponent = false;
		}

		if (!config.enable_default_component) {
			settings.enableToolbar = false;
			settings.ckeconfig.toolbarCanCollapse = false;
		}

		// Patch for iOS: https://github.com/rhymix/rhymix/issues/932
		if (config.ios_patch) {
			settings.loadXeComponent = false;
			$('head').append('<style>'
				+ '.cke_wysiwyg_div { padding: 8px !important; }'
				+ 'html { min-width: unset; min-height: unset; width: unset; height: unset; margin: unset; padding: unset; }'
				+ config.css_content.replace(/\.rhymix_content\.editable/g, '.cke_wysiwyg_div')
				+ '</style>'
			);
		}

		// Define the simple toolbar.
		if (config.toolbar === 'simple') {
			settings.ckeconfig.toolbar = [
				{ name: 'styles', items: [ 'Font', 'FontSize', '-', 'Bold', 'Italic', 'Underline', 'Strike', 'TextColor', 'BGColor' ] },
				{ name: 'paragraph', items: [ 'JustifyLeft', 'JustifyCenter', 'JustifyRight' ] },
				{ name: 'clipboard', items: [ 'Cut', 'Copy', 'Paste' ] },
				{ name: 'insert', items: [ 'Link', 'Image', 'Table' ] },
				{ name: 'tools', items: [ 'Maximize', '-', 'Source' ] }
			];
		}

		// Support legacy HTML (full editing) mode.
		if (!config.legacy_html_mode) {
			settings.ckeconfig.removeButtons = 'Save,Preview,Print,Cut,Copy,Paste,Source';
		}

		// Disable loading of custom configuration if config.js does not exist.
		if (!config.custom_config_exists) {
			CKEDITOR.config.customConfig = '';
		}

		// Prevent removal of icon fonts and Google code.
		CKEDITOR.dtd.$removeEmpty.i = 0;
		CKEDITOR.dtd.$removeEmpty.ins = 0;

		// Set the cache-busting timestamp for plugins.
		CKEDITOR.timestamp = config.timestamp;

		// Set the custom CSS content.
		CKEDITOR.addCss(config.css_content);

		// Initialize the CKEditor XE app.
		const ckeApp = container.XeCkEditor(settings);

		// Add use_editor and use_html fields to the parent form.
		const use_editor = form.find('input[name=use_editor]');
		const use_html = form.find('input[name=use_html]');
		if (use_editor.length) {
			use_editor.val('Y');
		} else {
			form.append('<input type="hidden" name="use_editor" value="Y" />');
		}
		if (use_html.length) {
			use_html.val('Y');
		} else {
			form.append('<input type="hidden" name="use_html" value="Y" />');
		}

	});
});

/**
 * This function is only retained for backward compatibility.
 * Do not depend on it for any reason.
 */
function ckInsertUploadedFile() {
	if (typeof console == "object" && typeof console.warn == "function") {
		const msg = "DEPRECATED : ckInsertUploadedFile() is obsolete in Rhymix.";
		if (navigator.userAgent.match(/Firefox/)) {
			console.error(msg);
		} else {
			console.warn(msg);
		}
	}
}

/**
 * Legacy function to get iframe content and insert it into CKEditor.
 */
function editorReplaceHTML(iframe_obj, content) {
	if (typeof console == "object" && typeof console.warn == "function") {
		const msg = "DEPRECATED : editorReplaceHTML() is deprecated in Rhymix.";
		if (navigator.userAgent.match(/Firefox/)) {
			console.error(msg);
		} else {
			console.warn(msg);
		}
	}
	var editor_sequence = parseInt(iframe_obj.id.replace(/^.*_/, ''), 10);
	_getCkeInstance(editor_sequence).insertHtml(content, 'unfiltered_html');
}

/**
 * Legacy function to get a direct reference to the CKEditor container element.
 */
function editorGetIFrame(editor_sequence) {
	if (typeof console == "object" && typeof console.warn == "function") {
		const msg = "DEPRECATED : editorGetIFrame() is deprecated in Rhymix.";
		if (navigator.userAgent.match(/Firefox/)) {
			console.error(msg);
		} else {
			console.warn(msg);
		}
	}
	return $('#ckeditor_instance_' + editor_sequence).get(0);
}

/**
 * Legacy function to get an instance of CKEditor.
 */
function _getCkeInstance(editor_sequence) {
	return $('#ckeditor_instance_' + editor_sequence).data('cke_instance');
}

/**
 * Legacy function to get the container element for CKEditor.
 */
function _getCkeContainer(editor_sequence) {
	return $('#ckeditor_instance_' + editor_sequence);
}

/**
 * Legacy function to get HTML content from CKEditor.
 */
function editorGetContent(editor_sequence) {
	return _getCkeInstance(editor_sequence).getData();
}

/**
 * Legacy function to get text content from CKEditor.
 */
function editorGetContentTextarea_xe(editor_sequence) {
	return _getCkeInstance(editor_sequence).getText();
}

/**
 * Legacy function to get currently selected text from CKEditor.
 */
function editorGetSelectedHtml(editor_sequence) {
	return _getCkeInstance(editor_sequence).getSelection().getSelectedText();
}
