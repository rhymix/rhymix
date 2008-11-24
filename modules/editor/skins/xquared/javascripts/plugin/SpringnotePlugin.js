/**
 * @requires Xquared.js
 * @requires Browser.js
 * @requires Editor.js
 * @requires plugin/Base.js
 */
xq.plugin.SpringnotePlugin = xq.Class(xq.plugin.Base,
	/**
	 * @name xq.plugin.SpringnotePlugin
	 * @lends xq.plugin.SpringnotePlugin.prototype
	 * @extends xq.plugin.Base
	 * @constructor
	 */
	{
	getShortcuts: function() {
		if(xq.Browser.isMac) {
			// Mac FF & Safari
			return [
				{event:"Ctrl+SPACE", handler:"xed.handleAutocompletion(); stop = true;"},
				{event:"Ctrl+Meta+0", handler:"xed.handleApplyBlock('P')"},
				{event:"Ctrl+Meta+1", handler:"xed.handleApplyBlock('H1')"},
				{event:"Ctrl+Meta+2", handler:"xed.handleApplyBlock('H2')"},
				{event:"Ctrl+Meta+3", handler:"xed.handleApplyBlock('H3')"},
				{event:"Ctrl+Meta+4", handler:"xed.handleApplyBlock('H4')"},
				{event:"Ctrl+Meta+5", handler:"xed.handleApplyBlock('H5')"},
				{event:"Ctrl+Meta+6", handler:"xed.handleApplyBlock('H6')"},
				
				{event:"Ctrl+Meta+B", handler:"xed.handleApplyBlock('BLOCKQUOTE')"},
				{event:"Ctrl+Meta+D", handler:"xed.handleApplyBlock('DIV')"},
				{event:"Ctrl+Meta+EQUAL", handler:"xed.handleSeparator()"},				
				
				{event:"Ctrl+Meta+O", handler:"xed.handleList('OL')"},
				{event:"Ctrl+Meta+U", handler:"xed.handleList('UL')"},
				
				{event:"Ctrl+Meta+E", handler:"xed.handleRemoveBlock()"},
				
				{event:"Ctrl+(Meta)+COMMA", handler:"xed.handleJustify('left')"},
				{event:"Ctrl+(Meta)+PERIOD", handler:"xed.handleJustify('center')"},
				{event:"Ctrl+(Meta)+SLASH", handler:"xed.handleJustify('right')"},
				
				{event:"Meta+UP", handler:"xed.handleMoveBlock(true)"},
				{event:"Meta+DOWN", handler:"xed.handleMoveBlock(false)"}
			];
		} else if(xq.Browser.isUbuntu) {
			//  Ubunto FF
			return [
				{event:"Ctrl+SPACE", handler:"xed.handleAutocompletion(); stop = true;"},
				{event:"Ctrl+0", handler:"xed.handleApplyBlock('P')"},
				{event:"Ctrl+1", handler:"xed.handleApplyBlock('H1')"},
				{event:"Ctrl+2", handler:"xed.handleApplyBlock('H2')"},
				{event:"Ctrl+3", handler:"xed.handleApplyBlock('H3')"},
				{event:"Ctrl+4", handler:"xed.handleApplyBlock('H4')"},
				{event:"Ctrl+5", handler:"xed.handleApplyBlock('H5')"},
				{event:"Ctrl+6", handler:"xed.handleApplyBlock('H6')"},
				
				{event:"Ctrl+Alt+B", handler:"xed.handleApplyBlock('BLOCKQUOTE')"},
				{event:"Ctrl+Alt+D", handler:"xed.handleApplyBlock('DIV')"},
				{event:"Alt+HYPHEN", handler:"xed.handleSeparator()"},				
				
				{event:"Ctrl+Alt+O", handler:"xed.handleList('OL')"},
				{event:"Ctrl+Alt+U", handler:"xed.handleList('UL')"},
				
				{event:"Ctrl+Alt+E", handler:"xed.handleRemoveBlock()"},
				
				{event:"Alt+COMMA", handler:"xed.handleJustify('left')"},
				{event:"Alt+PERIOD", handler:"xed.handleJustify('center')"},
				{event:"Alt+SLASH", handler:"xed.handleJustify('right')"},
				
				{event:"Alt+UP", handler:"xed.handleMoveBlock(true)"},
				{event:"Alt+DOWN", handler:"xed.handleMoveBlock(false)"}
			];
		} else {
			// Win IE & FF && Safari
			return [
				{event:"Ctrl+SPACE", handler:"xed.handleAutocompletion(); stop = true;"},
				{event:"Alt+0", handler:"xed.handleApplyBlock('P')"},
				{event:"Alt+1", handler:"xed.handleApplyBlock('H1')"},
				{event:"Alt+2", handler:"xed.handleApplyBlock('H2')"},
				{event:"Alt+3", handler:"xed.handleApplyBlock('H3')"},
				{event:"Alt+4", handler:"xed.handleApplyBlock('H4')"},
				{event:"Alt+5", handler:"xed.handleApplyBlock('H5')"},
				{event:"Alt+6", handler:"xed.handleApplyBlock('H6')"},
				{event:"Alt+7", handler:"xed.handleInsertMacro('TableOfContents')"},
				{event:"Alt+8", handler:"xed.attachLayer()"},
				
				{event:"Ctrl+Alt+B", handler:"xed.handleApplyBlock('BLOCKQUOTE')"},
				{event:"Ctrl+Alt+D", handler:"xed.handleApplyBlock('DIV')"},
				{event:"Alt+HYPHEN", handler:"xed.handleSeparator()"},
				
				{event:"Ctrl+Alt+O", handler:"xed.handleList('OL')"},
				{event:"Ctrl+Alt+U", handler:"xed.handleList('UL')"},
				
				{event:"Ctrl+Alt+E", handler:"xed.handleRemoveBlock()"},
				
				{event:"Alt+COMMA", handler:"xed.handleJustify('left')"},
				{event:"Alt+PERIOD", handler:"xed.handleJustify('center')"},
				{event:"Alt+SLASH", handler:"xed.handleJustify('right')"},
				
				{event:"Alt+UP", handler:"xed.handleMoveBlock(true)"},
				{event:"Alt+DOWN", handler:"xed.handleMoveBlock(false)"}
			];
		}
	},
	
	getAutocorrections: function() {
		return [
			{id:'bullet', criteria: /^(\s|\&nbsp\;)*(\*|-)(\s|\&nbsp\;).+$/, handler: function(xed, rdom, block, text) {
				rdom.pushMarker();
				rdom.removePlaceHoldersAndEmptyNodes(block);
				block.innerHTML = block.innerHTML.replace(/((\s|&nbsp;)*(\*|\-)\s*)/, "");
				if(block.nodeName === "LI") xed.handleIndent();
				if(block.parentNode.nodeName !== "UL") xed.handleList('UL');
				rdom.popMarker(true);
			}},
			{id:'numbering', criteria: /^(\s|\&nbsp\;)*(\d\.|#)(\s|\&nbsp\;).+$/, handler: function(xed, rdom, block, text) {
				rdom.pushMarker();
				rdom.removePlaceHoldersAndEmptyNodes(block);
				block.innerHTML = block.innerHTML.replace(/(\s|&nbsp;)*(\d\.|\#)\s*/, "")
				if(block.nodeName === "LI") xed.handleIndent();
				if(block.parentNode.nodeName !== "OL") xed.handleList('OL');
				rdom.popMarker(true);
			}},
			{id:'imageUrl', criteria: /https?:\/\/.*?\/(.*?\.(jpg|jpeg|gif|bmp|png))$/i, handler: function(xed, rdom, block, text) {
				var fileName = text.match(/https?:\/\/.*?\/(.*?\.(jpg|jpeg|gif|bmp|png))$/i)[1];
				block.innerHTML = "";
				var img = rdom.createElement("img");
				img.src = text;
				img.alt = fileName;
				img.title = fileName;
				block.appendChild(img);
				rdom.selectElement(block);
				rdom.collapseSelection(false);
			}},
			{id:'separator', criteria: /^---+(\&nbsp;|\s)*$/, handler: function(xed, rdom, block, text) {
				if(rdom.tree.isBlockContainer(block)) block = rdom.wrapAllInlineOrTextNodesAs("P", block, true)[0];
				rdom.insertNodeAt(rdom.createElement("HR"), block, "before");
				block.innerHTML = "";
				rdom.placeCaretAtStartOf(block);
				return true;
			}},
			{id:'heading', criteria: /^\=+[^=]*\=+(\&nbsp;|\s)*$/, handler: function(xed, rdom, block, text) {
				var textWithoutEqualMarks = text.strip().replace(/=/g, "");
				var level = Math.min(6, parseInt((text.length - textWithoutEqualMarks.length) / 2))
				xed.handleApplyBlock('H' + level);
				block = rdom.getCurrentBlockElement();
				block.innerHTML = textWithoutEqualMarks;
				rdom.selectElement(block);
				rdom.collapseSelection();
			}}
		];
	},
	
	getAutocompletions: function() {
		return [
			{
				id:'isbn',
				criteria: /@ISBN:\d+$/i,
				handler: function(xed, rdom, block, wrapper, text) {
					var isbn = text.split(":")[1]
					var korean = isbn.indexOf("97889") === 0 || isbn.indexOf("89") === 0
					var href = korean ?
						"http://www.aladdin.co.kr/shop/wproduct.aspx?ISBN=" :
						"http://www.amazon.com/exec/obidos/ISBN="
					var node = rdom.createElement('A');
					node.innerHTML = 'ISBN:' + isbn;
					node.href = href + isbn;
					node.className = 'external';
					node.title = 'ISBN:' + isbn;
					
					wrapper.innerHTML = "";
					wrapper.appendChild(node);
				}
			},
			{
				id:'anchor',
				criteria: /@A(:(.+))?$/i,
				handler: function(xed, rdom, block, wrapper, text) {
					var m = text.match(/@A(:(.+))?$/i);
					var anchorId = m[2] ? m[2] : function() {
						var id = 0;
						while(true) {
							var element = rdom.$("a" + (id));
							if(!element) return "a" + id;
							id++;
						}
					}();
					
					var node = rdom.createElement('A');
					node.id = anchorId;
					node.href = '#' + anchorId;
					node.className = 'anchor';
					node.title = 'Anchor ' + anchorId;
					node.innerHTML = '(' + anchorId + ')';
	
					wrapper.innerHTML = "";
					wrapper.appendChild(node);
				}
			}
		];
	},
	
	getTemplateProcessors: function() {
		return [
			{
				id:"datetime",
				handler:function(html) {
					var today = Date.get();
					var keywords = {
						year: today.getFullYear(),
						month: today.getMonth() + 1,
						date: today.getDate(),
						hour: today.getHours(),
						min: today.getMinutes(),
						sec: today.getSeconds()
					};
					
					return html.replace(/\{xq:(year|month|date|hour|min|sec)\}/img, function(text, keyword) {
						return keywords[keyword] || keyword;
					});
				}
			}
		];
	}
});