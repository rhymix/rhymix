/**
 * @requires Xquared.js
 * @requires rdom/Base.js
 */
xq.rdom.Trident = xq.Class(xq.rdom.Base,
	/**
	 * @name xq.rdom.Trident
	 * @lends xq.rdom.Trident.prototype
	 * @extends xq.rdom.Base
	 * @constructor
	 */
	{
	makePlaceHolder: function() {
		return this.createTextNode(" ");
	},
	
	makePlaceHolderString: function() {
		return '&nbsp;';
	},
	
	makeEmptyParagraph: function() {
		return this.createElementFromHtml("<p>&nbsp;</p>");
	},

	isPlaceHolder: function(node) {
		return false;
	},

	getOuterHTML: function(element) {
		return element.outerHTML;
	},

	getCurrentBlockElement: function() {
		var cur = this.getCurrentElement();
		if(!cur) return null;
		
		var block = this.getParentBlockElementOf(cur);
		if(!block) return null;
		
		if(block.nodeName === "BODY") {
			// Atomic block such as HR
			var newParagraph = this.insertNode(this.makeEmptyParagraph());
			var next = newParagraph.nextSibling;
			if(this.tree.isAtomic(next)) {
				this.deleteNode(newParagraph);
				return next;
			}
		} else {
			return block;
		}
	},
	
	insertNode: function(node) {
		if(this.hasSelection()) this.collapseSelection(true);
		
		this.rng().pasteHTML('<span id="xquared_temp"></span>');
		var marker = this.$('xquared_temp');
		if(node.id === 'xquared_temp') return marker;
		
		if(marker) marker.replaceNode(node);
		return node;
	},
	
	removeTrailingWhitespace: function(block) {
		if(!block) return;
		
		// @TODO: reimplement to handle atomic tags and so on. (use DomTree)
		if(this.tree.isBlockOnlyContainer(block)) return;
		if(this.isEmptyBlock(block)) return;
		
		var text = block.innerText;
		var html = block.innerHTML;
		var lastCharCode = text.charCodeAt(text.length - 1);
		if(text.length <= 1 || [32,160].indexOf(lastCharCode) === -1) return;
		
		// shortcut for most common case
		if(text == html.replace(/&nbsp;/g, " ")) {
			block.innerHTML = html.replace(/&nbsp;$/, "");
			return;
		}
		
		var node = block;
		while(node && node.nodeType !== 3) node = node.lastChild;
		if(!node) return;
		
		// DO NOT REMOVE OR MODIFY FOLLOWING CODE. Modifying following code will crash IE7
		var nodeValue = node.nodeValue;
		if(nodeValue.length <= 1) {
			this.deleteNode(node, true);
		} else {
			node.nodeValue = nodeValue.substring(0, nodeValue.length - 1);
		}
	},
	
	correctEmptyElement: function(element) {
		if(!element || element.nodeType !== 1 || this.tree.isAtomic(element)) return;
		
		if(element.firstChild) {
			this.correctEmptyElement(element.firstChild);
		} else {
			element.innerHTML = "&nbsp;";
		}
	},

	copyAttributes: function(from, to, copyId) {
		to.mergeAttributes(from, !copyId);
	},

	correctParagraph: function() {
		if(!this.hasFocus()) return false;
		if(this.hasSelection()) return false;
		
		var block = this.getCurrentElement();
		
		// if caret is at
		//  * atomic block level elements(HR) or
		//  * ...
		// then following is true
		if(this.tree.isBlockOnlyContainer(block)) {
			// check for atomic block element such as HR
			block = this.insertNode(this.makeEmptyParagraph());
			if(this.tree.isAtomic(block.nextSibling)) {
				// @WORKAROUND:
				// At this point, HR has a caret but getCurrentElement() doesn't return the HR and
				// I couldn't find a way to get this HR. So I have to keep this reference.
				// I will be used in Editor._handleEnter.
				this.recentHR = block.nextSibling;
				this.deleteNode(block);
				return false;
			} else {
				// I can't remember exactly when following is executed and what it does :-(
				//  * Case 1: Performing Ctrl+A and Ctrl+X repeatedly
				//  * ...
				var nextBlock = this.tree.findForward(
					block,
					function(node) {return this.tree.isBlock(node) && !this.tree.isBlockOnlyContainer(node)}.bind(this)
				);
				
				if(nextBlock) {
					this.deleteNode(block);
					this.placeCaretAtStartOf(nextBlock);
				} else {
					this.placeCaretAtStartOf(block);
				}
				
				return true;
			}
		} else {
			block = this.getCurrentBlockElement();
			if(block.nodeType === 3) block = block.parentNode;
			
			if(this.tree.hasMixedContents(block)) {
				var marker = this.pushMarker();
				this.wrapAllInlineOrTextNodesAs("P", block, true);
				this.popMarker(true);
				return true;
			} else if((this.tree.isTextOrInlineNode(block.previousSibling) || this.tree.isTextOrInlineNode(block.nextSibling)) && this.tree.hasMixedContents(block.parentNode)) {
				// @WORKAROUND:
				// IE에서는 Block과 Inline/Text가 인접한 경우 getCurrentElement 등이 오작동한다.
				// 따라서 현재 Block 주변까지 한번에 잡아주어야 한다.
				this.wrapAllInlineOrTextNodesAs("P", block.parentNode, true);
				return true;
			} else {
				return false;
			}
		}
	},
	
	
	
	//////
	// Commands
	execCommand: function(commandId, param) {
		return this.getDoc().execCommand(commandId, false, param);
	},
	
	applyBackgroundColor: function(color) {
		this.execCommand("BackColor", color);
	},
	
	applyEmphasis: function() {
		// Generate <i> tag. It will be replaced with <emphasis> tag during cleanup phase.
		this.execCommand("Italic");
	},
	applyStrongEmphasis: function() {
		// Generate <b> tag. It will be replaced with <strong> tag during cleanup phase.
		this.execCommand("Bold");
	},
	applyStrike: function() {
		// Generate <strike> tag. It will be replaced with <style class="strike"> tag during cleanup phase.
		this.execCommand("strikethrough");
	},
	applyUnderline: function() {
		// Generate <u> tag. It will be replaced with <em class="underline"> tag during cleanup phase.
		this.execCommand("underline");
	},
	applyRemoveFormat: function() {
		this.execCommand("RemoveFormat");
	},
	applyRemoveLink: function() {
		this.execCommand("Unlink");
	},



	//////
	// Focus/Caret/Selection
	
	focus: function() {
		this.getWin().focus();
	},

	sel: function() {
		return this.getDoc().selection;
	},
	
	crng: function() {
		return this.getDoc().body.createControlRange();
	},
	
	rng: function() {
		try {
			var sel = this.sel();
			return (sel === null) ? null : sel.createRange();
		} catch(ignored) {
			// IE often fails
			return null;
		}
	},
	
	hasSelection: function() {
		var selectionType = this.sel().type.toLowerCase();
		if("none" === selectionType) return false;
		if("text" === selectionType && this.getSelectionAsHtml().length === 0) return false;
		return true;
	},
	
	deleteSelection: function() {
		if(this.getSelectionAsText() !== "") this.sel().clear();
	},
	
	placeCaretAtStartOf: function(element) {
		// If there's no empty span, caret sometimes moves into a previous node.
		var ph = this.insertNodeAt(this.createElement("SPAN"), element, "start");
		this.selectElement(ph);
		this.collapseSelection(false);
		this.deleteNode(ph);
	},
	
	selectElement: function(element, entireElement, forceTextSelection) {
		if(!element) throw "[element] is null";
		if(element.nodeType !== 1) throw "[element] is not an element";
		
		var rng = null;
		if(!forceTextSelection && this.tree.isAtomic(element)) {
			rng = this.crng();
			rng.addElement(element);
		} else {
			var rng = this.rng();
			rng.moveToElementText(element);
		}
		rng.select();
	},

	selectBlocksBetween: function(start, end) {
		var rng = this.rng();
		var rngTemp = this.rng();

		rngTemp.moveToElementText(start);
		rng.setEndPoint("StartToStart", rngTemp);
		
		rngTemp.moveToElementText(end);
		rng.setEndPoint("EndToEnd", rngTemp);
		
		rng.select();
	},
	
	collapseSelection: function(toStart) {
		if(this.sel().type.toLowerCase() === "control") {
			var curElement = this.getCurrentElement();
			this.sel().empty();
			this.selectElement(curElement, false, true);
		}
		var rng = this.rng();
		rng.collapse(toStart);
		rng.select();
	},
	
	getSelectionAsHtml: function() {
		var rng = this.rng()
		return rng && rng.htmlText ? rng.htmlText : ""
	},
	
	getSelectionAsText: function() {
		var rng = this.rng();
		return rng && rng.text ? rng.text : "";
	},
	
	hasImportantAttributes: function(element) {
		return !!(element.id || element.className || element.style.cssText);
	},

	isEmptyBlock: function(element) {
		if(!element.hasChildNodes()) return true;
		if(element.nodeType === 3 && !element.nodeValue) return true;
		if(["&nbsp;", " ", ""].indexOf(element.innerHTML) !== -1) return true;
		
		return false;
	},
	
	getLastChild: function(element) {
		if(!element || !element.hasChildNodes()) return null;
		
		var nodes = xq.$A(element.childNodes).reverse();
		
		for(var i = 0; i < nodes.length; i++) {
			if(nodes[i].nodeType !== 3 || nodes[i].nodeValue.length !== 0) return nodes[i];
		}
		
		return null;
	},
	
	getCurrentElement: function() {
		if(this.sel().type.toLowerCase() === "control") return this.rng().item(0);
		
		var rng = this.rng();
		if(!rng) return false;
		
		var element = rng.parentElement();
		if(element.nodeName == "BODY" && this.hasSelection()) return null;
		return element;
	},
	
	getBlockElementAtSelectionStart: function() {
		var rng = this.rng();
		var dup = rng.duplicate();
		dup.collapse(true);
		
		var result = this.getParentBlockElementOf(dup.parentElement());
		if(result.nodeName === "BODY") result = result.firstChild;
		
		return result;
	},
	
	getBlockElementAtSelectionEnd: function() {
		var rng = this.rng();
		var dup = rng.duplicate();
		dup.collapse(false);
		
		var result = this.getParentBlockElementOf(dup.parentElement());
		if(result.nodeName === "BODY") result = result.lastChild;
		
		return result;
	},
	
	getBlockElementsAtSelectionEdge: function(naturalOrder, ignoreEmptyEdges) {
		return [
			this.getBlockElementAtSelectionStart(),
			this.getBlockElementAtSelectionEnd()
		];
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
			} else if(
				node.nodeType === 3 &&
				node.previousSibling === marker &&
				(nodeValue === " " || (nodeValue.length === 1 && nodeValue.charCodeAt(0) === 160))
			) {
				isTrue = true;
				break;
			}
		}
		
		this.popMarker();
		return isTrue;
	},
	
	saveSelection: function() {
		return this.rng();
	},
	
	restoreSelection: function(bookmark) {
		bookmark.select();
	}
});
