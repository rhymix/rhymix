/**
 * Base for W3C Standard Engine
 * 
 * @requires Xquared.js
 * @requires rdom/Base.js
 */
xq.rdom.W3 = xq.Class(xq.rdom.Base,
	/**
	 * @name xq.rdom.W3
	 * @lends xq.rdom.W3.prototype
	 * @extends xq.rdom.Base
	 * @constructor
	 */
	{
	insertNode: function(node) {
		var rng = this.rng();
		
		if(!rng) {
			this.getRoot().appendChild(node);
		} else {
			rng.insertNode(node);
			rng.selectNode(node);
			rng.collapse(false);
		}
		return node;
	},
	
	removeTrailingWhitespace: function(block) {
		// TODO: do nothing
	},
	
	getOuterHTML: function(element) {
		var div = element.ownerDocument.createElement("div");
		div.appendChild(element.cloneNode(true));
		return div.innerHTML;
	},
	
	correctEmptyElement: function(element) {
		if(!element || element.nodeType !== 1 || this.tree.isAtomic(element)) return;
		
		if(element.firstChild)
			this.correctEmptyElement(element.firstChild);
		else
			element.appendChild(this.makePlaceHolder());
	},
	
	correctParagraph: function() {
		if(this.hasSelection()) return false;
		
		var block = this.getCurrentBlockElement();
		var modified = false;
		
		if(!block) {
			try {
				this.execCommand("InsertParagraph");
				modified = true;
			} catch(ignored) {}
		} else if(this.tree.isBlockOnlyContainer(block)) {
			this.execCommand("InsertParagraph");
			
			// check for HR
			var newBlock = this.getCurrentElement();
			
			if(this.tree.isAtomic(newBlock.previousSibling) && newBlock.previousSibling.nodeName === "HR") {
				var nextBlock = this.tree.findForward(
					newBlock,
					function(node) {return this.tree.isBlock(node) && !this.tree.isBlockOnlyContainer(node)}.bind(this)
				);
				if(nextBlock) {
					this.deleteNode(newBlock);
					this.placeCaretAtStartOf(nextBlock);
				}
			}
			modified = true;
		} else if(this.tree.hasMixedContents(block)) {
			this.wrapAllInlineOrTextNodesAs("P", block, true);
			modified = true;
		}
		
		// insert placeholder - part 1
		block = this.getCurrentBlockElement();
		if(this.tree.isBlock(block) && !this._hasPlaceHolderAtEnd(block)) {
			block.appendChild(this.makePlaceHolder());
			modified = true;
		}
		
		// insert placeholder - part 2
		if(this.tree.isBlock(block)) {
			var parentsLastChild = block.parentNode.lastChild;
			if(this.isPlaceHolder(parentsLastChild)) {
				this.deleteNode(parentsLastChild);
				modified = true;
			}
		}
		
		// remove empty elements
		if(this.tree.isBlock(block)) {
			var nodes = block.childNodes;
			for(var i = 0; i < nodes.length; i++) {
				var node = nodes[i];
				if(node.nodeType === 1 && !this.tree.isAtomic(node) && !node.hasChildNodes() && !this.isPlaceHolder(node)) {
					this.deleteNode(node);
				}
			}
		}
		
		return modified;
	},
	
	_hasPlaceHolderAtEnd: function(block) {
		if(!block.hasChildNodes()) return false;
		return this.isPlaceHolder(block.lastChild) || this._hasPlaceHolderAtEnd(block.lastChild);
	},
	
	applyBackgroundColor: function(color) {
		this.execCommand("styleWithCSS", "true");
		this.execCommand("hilitecolor", color);
		this.execCommand("styleWithCSS", "false");
		
		// 0. Save current selection
		var bookmark = this.saveSelection();
		
		// 1. Get selected blocks
		var blocks = this.getSelectedBlockElements();
		if(blocks.length === 0) return;
		
		// 2. Apply background-color to all adjust inline elements
		// 3. Remove background-color from blocks
		for(var i = 0; i < blocks.length; i++) {
			if((i === 0 || i === blocks.length-1) && !blocks[i].style.backgroundColor) continue;
			
			var spans = this.wrapAllInlineOrTextNodesAs("SPAN", blocks[i], true);
			for(var j = 0; j < spans.length; j++) {
				spans[j].style.backgroundColor = color;
			}
			blocks[i].style.backgroundColor = "";
		}
		
		// 4. Restore selection
		this.restoreSelection(bookmark);
	},
	
	
	
	
	//////
	// Commands
	execCommand: function(commandId, param) {
		return this.getDoc().execCommand(commandId, false, param || null);
	},
	
	applyRemoveFormat: function() {
		this.execCommand("RemoveFormat");
	},
	applyRemoveLink: function() {
		this.execCommand("Unlink");
	},
	applyEmphasis: function() {
		// Generate <i> tag. It will be replaced with <emphasis> tag during cleanup phase.
		this.execCommand("styleWithCSS", "false");
		this.execCommand("italic");
	},
	applyStrongEmphasis: function() {
		// Generate <b> tag. It will be replaced with <strong> tag during cleanup phase.
		this.execCommand("styleWithCSS", "false");
		this.execCommand("bold");
	},
	applyStrike: function() {
		// Generate <strike> tag. It will be replaced with <style class="strike"> tag during cleanup phase.
		this.execCommand("styleWithCSS", "false");
		this.execCommand("strikethrough");
	},
	applyUnderline: function() {
		// Generate <u> tag. It will be replaced with <em class="underline"> tag during cleanup phase.
		this.execCommand("styleWithCSS", "false");
		this.execCommand("underline");
	},
	
	
	
	//////
	// Focus/Caret/Selection
	
	focus: function() {
		this.getWin().focus();
	},
	
	sel: function() {
		return this.getWin().getSelection();
	},
	
	rng: function() {
		var sel = this.sel();
		return (sel === null || sel.rangeCount === 0) ? null : sel.getRangeAt(0);
	},
	
	saveSelection: function() {
		var rng = this.rng();
		return [rng.startContainer, rng.startOffset, rng.endContainer, rng.endOffset];
	},
	
	restoreSelection: function(bookmark) {
		var rng = this.rng();
		rng.setStart(bookmark[0], bookmark[1]);
		rng.setEnd(bookmark[2], bookmark[3]);
	},
	
	hasSelection: function() {
		var sel = this.sel();
		return sel && !sel.isCollapsed;
	},
	
	deleteSelection: function() {
		this.rng().deleteContents();
		this.sel().collapseToStart();
	},
	
	selectElement: function(element, entireElement) {throw "Not implemented yet"},

	selectBlocksBetween: function(start, end) {
		// @WORKAROUND: required to avoid FF selection bug.
		try {
			if(!xq.Browser.isMac) this.getDoc().execCommand("SelectAll", false, null);
		} catch(ignored) {}
		
		var rng = this.rng();
		rng.setStart(start.firstChild, 0);
		rng.setEnd(end, end.childNodes.length);
	},

	collapseSelection: function(toStart) {
		var rng = this.rng();
		if(rng) rng.collapse(toStart);
	},
	
	placeCaretAtStartOf: function(element) {
		while(this.tree.isBlock(element.firstChild)) {
			element = element.firstChild;
		}
		this.selectElement(element, false);
		this.collapseSelection(true);
	},
	
	placeCaretAtEndOf: function(element) {
		while(this.tree.isBlock(element.lastChild)) {
			element = element.lastChild;
		}
		this.selectElement(element, false);
		this.collapseSelection(false);
	},
	
	getSelectionAsHtml: function() {
		var container = document.createElement("div");
		container.appendChild(this.rng().cloneContents());
		return container.innerHTML;
	},
	
	getSelectionAsText: function() {
		return this.rng().toString()
	},
	
	hasImportantAttributes: function(element) {
		return !!(element.id || element.className || element.style.cssText);
	},
	
	isEmptyBlock: function(element) {
		if(!element.hasChildNodes()) return true;
		var children = element.childNodes;
		for(var i = 0; i < children.length; i++) {
			if(!this.isPlaceHolder(children[i]) && !this.isEmptyTextNode(children[i])) return false;
		}
		return true;
	},
	
	getLastChild: function(element) {
		if(!element || !element.hasChildNodes()) return null;
		
		var nodes = xq.$A(element.childNodes).reverse();
		
		for(var i = 0; i < nodes.length; i++) {
			if(!this.isPlaceHolder(nodes[i]) && !this.isEmptyTextNode(nodes[i])) return nodes[i];
		}
		return null;
	},
	
	getCurrentElement: function() {
		var rng = this.rng();
		if(!rng) return null;
		
		var container = rng.startContainer;
		
		if(container.nodeType === 3) {
			return container.parentNode;
		} else if(this.tree.isBlockOnlyContainer(container)) {
			return container.childNodes[rng.startOffset];
		} else {
			return container;
		}
	},

	getBlockElementsAtSelectionEdge: function(naturalOrder, ignoreEmptyEdges) {
		var start = this.getBlockElementAtSelectionStart();
		var end = this.getBlockElementAtSelectionEnd();
		
		var reversed = false;
		
		if(naturalOrder && start !== end && this.tree.checkTargetBackward(start, end)) {
			var temp = start;
			start = end;
			end = temp;
			
			reversed = true;
		}
		
		if(ignoreEmptyEdges && start !== end) {
			// @TODO: Firefox sometimes selects one more block.
/*
			
			var sel = this.sel();
			if(reversed) {
				if(sel.focusNode.nodeType === 1) start = start.nextSibling;
				if(sel.anchorNode.nodeType === 3 && sel.focusOffset === 0) end = end.previousSibling;
			} else {
				if(sel.anchorNode.nodeType === 1) start = start.nextSibling;
				if(sel.focusNode.nodeType === 3 && sel.focusOffset === 0) end = end.previousSibling;
			}
*/
		}
		
		return [start, end];
	},

	isCaretAtBlockEnd: function() {
		if(this.isCaretAtEmptyBlock()) return true;
		if(this.hasSelection()) return false;
		
		var node = this.getCurrentBlockElement();
		var marker = this.pushMarker();
		
		var isTrue = false;
		while (node = this.getLastChild(node)) {
			var nodeValue = node.nodeValue;
			
			if (node === marker) {
				isTrue = true;
				break;
			}
		}
		
		this.popMarker();
		return isTrue;
	},
	
	getBlockElementAtSelectionStart: function() {
		var block = this.getParentBlockElementOf(this.sel().anchorNode);
		
		// find bottom-most first block child
		while(this.tree.isBlockContainer(block) && block.firstChild && this.tree.isBlock(block.firstChild)) {
			block = block.firstChild;
		}
		
		return block;
	},
	
	getBlockElementAtSelectionEnd: function() {
		var block = this.getParentBlockElementOf(this.sel().focusNode);
		
		// find bottom-most last block child
		while(this.tree.isBlockContainer(block) && block.lastChild && this.tree.isBlock(block.lastChild)) {
			block = block.lastChild;
		}
		
		return block;
	}
});
