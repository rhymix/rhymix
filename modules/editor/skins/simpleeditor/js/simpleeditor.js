"use strict";

(function($) {
	
	// Save the cursor position.
	var ranges = [];
	var saveSelection = function() {
		var sel = window.getSelection();
		ranges = [];
		if (sel.getRangeAt && sel.rangeCount) {
			for (let i = 0; i < sel.rangeCount; i++) {
				ranges.push(sel.getRangeAt(i));
			}
		}
	};
	
	// Insert content at cursor position.
	var insertContent = function(instance, content) {
		if (content.match(/<(audio|video)\b[^>]+>(<\/p>)?/)) {
			content = content + '<p><br></p>';
		}
		if (ranges.length) {
			var range = ranges[0];
			range.collapse(false);
			ranges = [];
		} else {
			var range = document.createRange();
			range.selectNodeContents(instance.get(0));
			range.collapse(false);
		}
		var sel = window.getSelection();
		sel.removeAllRanges();
		sel.addRange(range);
		if (String(navigator.userAgent).match(/Trident\/7/)) {
			range.insertNode(range.createContextualFragment(content));
			range.collapse(false);
		} else {
			document.execCommand('insertHTML', false, content);
		}
	};
	
	// Simplify HTML content by removing unnecessary tags.
	var simplifyContent = function(str) {
		str = String(str);
		str = str.replace(/<!--(.*?)-->/gs, '');
		str = str.replace(/<\/?(\?xml|meta|link|font|span|style|script|noscript|frame|noframes|(?:st1|o):[a-z0-9]+)\b[^>]*?>/ig, '');
		str = str.replace(/\b(id|class|style|on(?:[a-z0-9]+)|Mso(?:[a-z0-9]+))="[^"]*"/ig, '');
		str = str.replace(/(<\/?)div(\W)/g, '$1p$2');
		if (!str.match(/<\/?p>/)) {
			str = '<p>' + str + '</p>';
		}
		return str;
	};
	
	// Convert YouTube links.
	var convertYouTube = function(str) {
		var regexp = /https?:\/\/(www\.youtube(?:-nocookie)?\.com\/(?:watch\?v=|v\/|embed\/)|youtu\.be\/)([a-zA-Z0-9_-]+)\S*/g;
		var embed = '<iframe width="560" height="315" src="https://www.youtube.com/embed/$2" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe><p></p>';
		return String(str).replace(regexp, embed);
	};
	
	// Page load event handler.
	$(function() {
		$('.rx_simpleeditor').each(function() {
			
			// Load editor info.
			var editor = $(this);
			var editor_sequence = editor.data('editor-sequence');
			var content_key = editor.data('editor-content-key-name');
			var primary_key = editor.data('editor-primary-key-name');
			var insert_form = editor.closest('form');
			var content_input = insert_form.find('input,textarea').filter('[name=' + content_key + ']');
			var editor_height = editor.data('editor-height');
			if (editor_height) {
				editor.css('height', editor_height + 'px');
			}
			
			// Set editor sequence and other info to the form.
			insert_form[0].setAttribute('editor_sequence', editor_sequence);
			editorRelKeys[editor_sequence] = {};
			editorRelKeys[editor_sequence].primary = insert_form.find("input[name='" + primary_key + "']").get(0);
			editorRelKeys[editor_sequence].content = content_input;
			editorRelKeys[editor_sequence].func = editorGetContent;
			
			// Force <p> as paragraph separator.
			document.execCommand('defaultParagraphSeparator', false, 'p');
			
			// Capture some simple keyboard shortcuts.
			editor.on('keydown', function(event) {
				if (!event.ctrlKey) {
					return;
				}
				var char = String.fromCharCode(event.which).toLowerCase();
				if (char === 'b') {
					document.execCommand('bold');
					event.preventDefault();
				}
				if (char === 'i') {
					document.execCommand('italic');
					event.preventDefault();
				}
				if (char === 'u') {
					document.execCommand('underline');
					event.preventDefault();
				}
			});
			
			// Save cursor position on moseup & keyup.
			editor.on('mouseup keyup', function() {
				saveSelection();
			});
			
			// Clean up pasted content.
			editor.on('paste', function(event) {
				var clipboard_data = (event.clipboardData || window.clipboardData || event.originalEvent.clipboardData);
				if (typeof clipboard_data !== 'undefined') {
					var content = clipboard_data.getData('text/html');
					if (content === '') {
						content = clipboard_data.getData('text');
					}
				} else {
					return;
				}
				content = convertYouTube(simplifyContent(content));
				insertContent(editor, content);
				event.preventDefault();
			});
			
			// Load existing content.
			if (content_input.size()) {
				editor.html(content_input.val());
			}
			
			// Copy edited content to the actual input element.
			editor.on('input blur mouseup keyup', function() {
				var content = simplifyContent(editor.html());
				content_input.val(content);
			});
		});
	});
	
	// Simulate CKEditor for file upload integration.
	window._getCkeInstance = function(editor_sequence) {
		var instance = $('#simpleeditor_instance_' + editor_sequence);
		return {
			getData: function() {
				return String(instance.html());
			},
			setData: function(content) {
				instance.html(content);
			},
			insertHtml: function(content) {
				insertContent(instance, content);
			}
		};
	};
	
})(jQuery);
