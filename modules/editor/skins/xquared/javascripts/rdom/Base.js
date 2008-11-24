/**
 * @namespace
 */
xq.rdom = {}

/**
 * @requires Xquared.js
 * @requires DomTree.js
 */
xq.rdom.Base = xq.Class(/** @lends xq.rdom.Base.prototype */{
	/**
	 * Encapsulates browser incompatibility problem and provides rich set of DOM manipulation API.<br />
	 * <br />
	 * Base provides basic CRUD + Advanced DOM manipulation API, various query methods and caret/selection management API.	 
	 *
     * @constructs
	 */
	initialize: function() {
		xq.addToFinalizeQueue(this);

		/**
		 * Instance of DomTree
		 * @type xq.DomTree
		 */
		this.tree = new xq.DomTree();
		this.focused = false;
		this._lastMarkerId = 0;
	},
	
	
	
	/**
	 * Initialize Base instance using window object.
	 * Reads document and body from window object and sets them as a property
	 * 
	 * @param {Window} win Browser's window object
	 */
	setWin: function(win) {
		if(!win) throw "[win] is null";
		this.win = win;
	},
	
	/**
	 * Initialize Base instance using root element.
	 * Reads window and document from root element and sets them as a property.
	 * 
	 * @param {Element} root Root element
	 */
	setRoot: function(root) {
		if(!root) throw "[root] is null";
		this.root = root;
	},
	
	/**
	 * @returns Browser's window object.
	 */
	getWin: function() {
		return this.win ||
			(this.root ? (this.root.ownerDocument.defaultView || this.root.ownerDocument.parentWindow) : window);
	},
	
	/**
	 * @returns Root element.
	 */
	getRoot: function() {
		return this.root || this.win.document.body;
	},
	
	/**
	 * @returns Document object of root element.
	 */
	getDoc: function() {
		return this.getWin().document || this.getRoot().ownerDocument;
	},
	
	
	
	/////////////////////////////////////////////
	// CRUDs
	
	clearRoot: function() {
		this.getRoot().innerHTML = "";
		this.getRoot().appendChild(this.makeEmptyParagraph());
	},
	
	/**
	 * Removes place holders and empty text nodes of given element.
	 *
	 * @param {Element} element target element
	 */
	removePlaceHoldersAndEmptyNodes: function(element) {
		if(!element.hasChildNodes()) return;
		
		var stopAt = this.getBottommostLastChild(element);
		if(!stopAt) return;
		stopAt = this.tree.walkForward(stopAt);
		
		while(element && element !== stopAt) {
			if(
					this.isPlaceHolder(element) ||
					(element.nodeType === 3 && (element.nodeValue === "" || (!element.nextSibling && element.nodeValue.isBlank())))
			) {
				var deleteTarget = element;
				element = this.tree.walkForward(element);
				this.deleteNode(deleteTarget);
			} else {
				element = this.tree.walkForward(element);
			}
		}
	},
	
	/**
	 * Sets multiple attributes into element at once
	 *
	 * @param {Element} element target element
	 * @param {Object} map key-value pairs
	 */
	setAttributes: function(element, map) {
		for(var key in map) element.setAttribute(key, map[key]);
	},

	/**
	 * Creates textnode by given node value.
	 *
	 * @param {String} value value of textnode
	 * @returns {Node} Created text node
	 */	
	createTextNode: function(value) {return this.getDoc().createTextNode(value);},

	/**
	 * Creates empty element by given tag name.
	 *
	 * @param {String} tagName name of tag
	 * @returns {Element} Created element
	 */	
	createElement: function(tagName) {return this.getDoc().createElement(tagName);},

	/**
	 * Creates element from HTML string
	 * 
	 * @param {String} html HTML string
	 * @returns {Element} Created element
	 */
	createElementFromHtml: function(html) {
		var node = this.createElement("div");
		node.innerHTML = html;
		if(node.childNodes.length !== 1) {
			throw "Illegal HTML fragment";
		}
		return this.getFirstChild(node);
	},
	
	/**
	 * Deletes node from DOM tree.
	 *
	 * @param {Node} node Target node which should be deleted
	 * @param {boolean} deleteEmptyParentsRecursively Recursively delete empty parent elements
	 * @param {boolean} correctEmptyParent Call #correctEmptyElement on empty parent element after deletion
	 */	
	deleteNode: function(node, deleteEmptyParentsRecursively, correctEmptyParent) {
		if(!node || !node.parentNode) return;
		if(node.nodeName === "BODY") throw "Cannot delete BODY";
		
		var parent = node.parentNode;
		parent.removeChild(node);
		
		if(deleteEmptyParentsRecursively) {
			while(!parent.hasChildNodes()) {
				node = parent;
				parent = node.parentNode;
				if(!parent || this.getRoot() === node) break;
				parent.removeChild(node);
			}
		}
		
		if(correctEmptyParent && this.isEmptyBlock(parent)) {
			parent.innerHTML = "";
			this.correctEmptyElement(parent);
		}
	},

	/**
	 * Inserts given node into current caret position
	 *
	 * @param {Node} node Target node
	 * @returns {Node} Inserted node. It could be different with given node.
	 */
	insertNode: function(node) {throw "Not implemented"},

	/**
	 * Inserts given html into current caret position
	 *
	 * @param {String} html HTML string
	 * @returns {Node} Inserted node. It could be different with given node.
	 */
	insertHtml: function(html) {
		return this.insertNode(this.createElementFromHtml(html));
	},
	
	/**
	 * Creates textnode from given text and inserts it into current caret position
	 *
	 * @param {String} text Value of textnode
	 * @returns {Node} Inserted node
	 */
	insertText: function(text) {
		this.insertNode(this.createTextNode(text));
	},
	
	/**
	 * Places given node nearby target.
	 *
	 * @param {Node} node Node to be inserted.
	 * @param {Node} target Target node.
	 * @param {String} where Possible values: "before", "start", "end", "after"
	 * @param {boolean} performValidation Validate node if needed. For example when P placed into UL, its tag name automatically replaced with LI
	 *
	 * @returns {Node} Inserted node. It could be different with given node.
	 */
	insertNodeAt: function(node, target, where, performValidation) {
		if(
			["HTML", "HEAD"].indexOf(target.nodeName) !== -1 ||
			"BODY" === target.nodeName && ["before", "after"].indexOf(where) !== -1
		) throw "Illegal argument. Cannot move node[" + node.nodeName + "] to '" + where + "' of target[" + target.nodeName + "]"
		
		var object;
		var message;
		var secondParam;
		
		switch(where.toLowerCase()) {
			case "before":
				object = target.parentNode;
				message = 'insertBefore';
				secondParam = target;
				break
			case "start":
				if(target.firstChild) {
					object = target;
					message = 'insertBefore';
					secondParam = target.firstChild;
				} else {
					object = target;
					message = 'appendChild';
				}
				break
			case "end":
				object = target;
				message = 'appendChild';
				break
			case "after":
				if(target.nextSibling) {
					object = target.parentNode;
					message = 'insertBefore';
					secondParam = target.nextSibling;
				} else {
					object = target.parentNode;
					message = 'appendChild';
				}
				break
		}

		if(performValidation && this.tree.isListContainer(object) && node.nodeName !== "LI") {
			var li = this.createElement("LI");
			li.appendChild(node);
			node = li;
			object[message](node, secondParam);		
		} else if(performValidation && !this.tree.isListContainer(object) && node.nodeName === "LI") {
			this.wrapAllInlineOrTextNodesAs("P", node, true);
			var div = this.createElement("DIV");
			this.moveChildNodes(node, div);
			this.deleteNode(node);
			object[message](div, secondParam);
			node = this.unwrapElement(div, true);
		} else {
			object[message](node, secondParam);
		}
		
		return node;
	},

	/**
	 * Creates textnode from given text and places given node nearby target.
	 *
	 * @param {String} text Text to be inserted.
	 * @param {Node} target Target node.
	 * @param {String} where Possible values: "before", "start", "end", "after"
	 *
	 * @returns {Node} Inserted node.
	 */
	insertTextAt: function(text, target, where) {
		return this.insertNodeAt(this.createTextNode(text), target, where);
	},

	/**
	 * Creates element from given HTML string and places given it nearby target.
	 *
	 * @param {String} html HTML to be inserted.
	 * @param {Node} target Target node.
	 * @param {String} where Possible values: "before", "start", "end", "after"
	 *
	 * @returns {Node} Inserted node.
	 */
	insertHtmlAt: function(html, target, where) {
		return this.insertNodeAt(this.createElementFromHtml(html), target, where);
	},

	/**
	 * Replaces element's tag by removing current element and creating new element by given tag name.
	 *
	 * @param {String} tag New tag name
	 * @param {Element} element Target element
	 *
	 * @returns {Element} Replaced element
	 */	
	replaceTag: function(tag, element) {
		if(element.nodeName === tag) return null;
		if(this.tree.isTableCell(element)) return null;
		
		var newElement = this.createElement(tag);
		this.moveChildNodes(element, newElement);
		this.copyAttributes(element, newElement, true);
		element.parentNode.replaceChild(newElement, element);
		
		if(!newElement.hasChildNodes()) this.correctEmptyElement(newElement);
		
		return newElement;
	},

	/**
	 * Unwraps unnecessary paragraph.
	 *
	 * Unnecessary paragraph is P which is the only child of given container element.
	 * For example, P which is contained by LI and is the only child is the unnecessary paragraph.
	 * But if given container element is a block-only-container(BLOCKQUOTE, BODY), this method does nothing.
	 *
	 * @param {Element} element Container element
	 * @returns {boolean} True if unwrap performed.
	 */
	unwrapUnnecessaryParagraph: function(element) {
		if(!element) return false;
		
		if(!this.tree.isBlockOnlyContainer(element) && element.childNodes.length === 1 && element.firstChild.nodeName === "P" && !this.hasImportantAttributes(element.firstChild)) {
			var p = element.firstChild;
			this.moveChildNodes(p, element);
			this.deleteNode(p);
			return true;
		}
		return false;
	},
	
	/**
	 * Unwraps element by extracting all children out and removing the element.
	 *
	 * @param {Element} element Target element
	 * @param {boolean} wrapInlineAndTextNodes Wrap all inline and text nodes with P before unwrap
	 * @returns {Node} First child of unwrapped element
	 */
	unwrapElement: function(element, wrapInlineAndTextNodes) {
		if(wrapInlineAndTextNodes) this.wrapAllInlineOrTextNodesAs("P", element);
		
		var nodeToReturn = element.firstChild;
		
		while(element.firstChild) this.insertNodeAt(element.firstChild, element, "before");
		this.deleteNode(element);
		
		return nodeToReturn;
	},
	
	/**
	 * Wraps element by given tag
	 *
	 * @param {String} tag tag name
	 * @param {Element} element target element to wrap
	 * @returns {Element} wrapper
	 */
	wrapElement: function(tag, element) {
		var wrapper = this.insertNodeAt(this.createElement(tag), element, "before");
		wrapper.appendChild(element);
		return wrapper;
	},
	
	/**
	 * Tests #smartWrap with given criteria but doesn't change anything
	 */
	testSmartWrap: function(endElement, criteria) {
		return this.smartWrap(endElement, null, criteria, true);
	},
	
	/**
	 * Create inline element with given tag name and wraps nodes nearby endElement by given criteria
	 *
	 * @param {Element} endElement Boundary(end point, exclusive) of wrapper.
	 * @param {String} tag Tag name of wrapper.
	 * @param {Object} function which returns text index of start boundary.
	 * @param {boolean} testOnly just test boundary and do not perform actual wrapping.
	 *
	 * @returns {Element} wrapper
	 */
	smartWrap: function(endElement, tag, criteria, testOnly) {
		var block = this.getParentBlockElementOf(endElement);

		tag = tag || "SPAN";
		criteria = criteria || function(text) {return -1};
		
		// check for empty wrapper
		if(!testOnly && (!endElement.previousSibling || this.isEmptyBlock(block))) {
			var wrapper = this.insertNodeAt(this.createElement(tag), endElement, "before");
			return wrapper;
		}
		
		// collect all textnodes
		var textNodes = this.tree.collectForward(block, function(node) {return node === endElement}, function(node) {return node.nodeType === 3});
		
		// find textnode and break-point
		var nodeIndex = 0;
		var nodeValues = [];
		for(var i = 0; i < textNodes.length; i++) {
			nodeValues.push(textNodes[i].nodeValue);
		}
		var textToWrap = nodeValues.join("");
		var textIndex = criteria(textToWrap)
		var breakPoint = textIndex;
		
		if(breakPoint === -1) {
			breakPoint = 0;
		} else {
			textToWrap = textToWrap.substring(breakPoint);
		}
		
		for(var i = 0; i < textNodes.length; i++) {
			if(breakPoint > nodeValues[i].length) {
				breakPoint -= nodeValues[i].length;
			} else {
				nodeIndex = i;
				break;
			}
		}
		
		if(testOnly) return {text:textToWrap, textIndex:textIndex, nodeIndex:nodeIndex, breakPoint:breakPoint};
		
		// break textnode if necessary 
		if(breakPoint !== 0) {
			var splitted = textNodes[nodeIndex].splitText(breakPoint);
			nodeIndex++;
			textNodes.splice(nodeIndex, 0, splitted);
		}
		var startElement = textNodes[nodeIndex] || block.firstChild;
		
		// split inline elements up to parent block if necessary
		var family = this.tree.findCommonAncestorAndImmediateChildrenOf(startElement, endElement);
		var ca = family.parent;
		if(ca) {
			if(startElement.parentNode !== ca) startElement = this.splitElementUpto(startElement, ca, true);
			if(endElement.parentNode !== ca) endElement = this.splitElementUpto(endElement, ca, true);
			
			var prevStart = startElement.previousSibling;
			var nextEnd = endElement.nextSibling;
			
			// remove empty inline elements
			if(prevStart && prevStart.nodeType === 1 && this.isEmptyBlock(prevStart)) this.deleteNode(prevStart);
			if(nextEnd && nextEnd.nodeType === 1 && this.isEmptyBlock(nextEnd)) this.deleteNode(nextEnd);
			
			// wrap
			var wrapper = this.insertNodeAt(this.createElement(tag), startElement, "before");
			while(wrapper.nextSibling !== endElement) wrapper.appendChild(wrapper.nextSibling);
			return wrapper;
		} else {
			// wrap
			var wrapper = this.insertNodeAt(this.createElement(tag), endElement, "before");
			return wrapper;
		}
	},
	
	/**
	 * Wraps all adjust inline elements and text nodes into block element.
	 *
	 * TODO: empty element should return empty array when it is not forced and (at least) single item array when forced
	 *
	 * @param {String} tag Tag name of wrapper
	 * @param {Element} element Target element
	 * @param {boolean} force Force wrapping. If it is set to false, this method do not makes unnecessary wrapper.
	 *
	 * @returns {Array} Array of wrappers. If nothing performed it returns empty array
	 */
	wrapAllInlineOrTextNodesAs: function(tag, element, force) {
		var wrappers = [];
		
		if(!force && !this.tree.hasMixedContents(element)) return wrappers;
		
		var node = element.firstChild;
		while(node) {
			if(this.tree.isTextOrInlineNode(node)) {
				var wrapper = this.wrapInlineOrTextNodesAs(tag, node);
				wrappers.push(wrapper);
				node = wrapper.nextSibling;
			} else {
				node = node.nextSibling;
			}
		}

		return wrappers;
	},

	/**
	 * Wraps node and its adjust next siblings into an element
	 */
	wrapInlineOrTextNodesAs: function(tag, node) {
		var wrapper = this.createElement(tag);
		var from = node;

		from.parentNode.replaceChild(wrapper, from);
		wrapper.appendChild(from);

		// move nodes into wrapper
		while(wrapper.nextSibling && this.tree.isTextOrInlineNode(wrapper.nextSibling)) wrapper.appendChild(wrapper.nextSibling);

		return wrapper;
	},
	
	/**
	 * Turns block element into list item
	 *
	 * @param {Element} element Target element
	 * @param {String} type One of "UL", "OL".
	 * @param {String} className CSS class name.
	 *
	 * @return {Element} LI element
	 */
	turnElementIntoListItem: function(element, type, className) {
		type = type.toUpperCase();
		className = className || "";
		
		var container = this.createElement(type);
		if(className) container.className = className;
		
		if(this.tree.isTableCell(element)) {
			var p = this.wrapAllInlineOrTextNodesAs("P", element, true)[0];
			container = this.insertNodeAt(container, element, "start");
			var li = this.insertNodeAt(this.createElement("LI"), container, "start");
			li.appendChild(p);
		} else {
			container = this.insertNodeAt(container, element, "after");
			var li = this.insertNodeAt(this.createElement("LI"), container, "start");
			li.appendChild(element);
		}
		
		this.unwrapUnnecessaryParagraph(li);
		this.mergeAdjustLists(container);
		
		return li;
	},
	
	/**
	 * Extracts given element out from its parent element.
	 * 
	 * @param {Element} element Target element
	 */
	extractOutElementFromParent: function(element) {
		if(element === this.getRoot() || element.parentNode === this.getRoot() || !element.offsetParent) return null;
		
		if(element.nodeName === "LI") {
			this.wrapAllInlineOrTextNodesAs("P", element, true);
			element = element.firstChild;
		}

		var container = element.parentNode;
		var nodeToReturn = null;
		
		if(container.nodeName === "LI" && container.parentNode.parentNode.nodeName === "LI") {
			// nested list item
			if(element.previousSibling) {
				this.splitContainerOf(element, true);
				this.correctEmptyElement(element);
			}
			
			this.outdentListItem(element);
			nodeToReturn = element;
		} else if(container.nodeName === "LI") {
			// not-nested list item
			
			if(this.tree.isListContainer(element.nextSibling)) {
				// 1. split listContainer
				var listContainer = container.parentNode;
				this.splitContainerOf(container, true);
				this.correctEmptyElement(element);
				
				// 2. extract out LI's children
				nodeToReturn = container.firstChild;
				while(container.firstChild) {
					this.insertNodeAt(container.firstChild, listContainer, "before");
				}
				
				// 3. remove listContainer and merge adjust lists
				var prevContainer = listContainer.previousSibling;
				this.deleteNode(listContainer);
				if(prevContainer && this.tree.isListContainer(prevContainer)) this.mergeAdjustLists(prevContainer);
			} else {
				// 1. split LI
				this.splitContainerOf(element, true);
				this.correctEmptyElement(element);
				
				// 2. split list container
				var listContainer = this.splitContainerOf(container);
				
				// 3. extract out
				this.insertNodeAt(element, listContainer.parentNode, "before");
				this.deleteNode(listContainer.parentNode);
				
				nodeToReturn = element;
			}
		} else if(this.tree.isTableCell(container) || this.tree.isTableCell(element)) {
			// do nothing
		} else {
			// normal block
			this.splitContainerOf(element, true);
			this.correctEmptyElement(element);
			nodeToReturn = this.insertNodeAt(element, container, "before");
			
			this.deleteNode(container);
		}
		
		return nodeToReturn;
	},
	
	/**
	 * Insert new block above or below given element.
	 *
	 * @param {Element} block Target block
	 * @param {boolean} before Insert new block above(before) target block
	 * @param {String} forceTag New block's tag name. If omitted, target block's tag name will be used.
	 *
	 * @returns {Element} Inserted block
	 */
	insertNewBlockAround: function(block, before, forceTag) {
		var isListItem = block.nodeName === "LI" || block.parentNode.nodeName === "LI";
		
		this.removeTrailingWhitespace(block);
		if(this.isFirstLiWithNestedList(block) && !forceTag && before) {
			var li = this.getParentElementOf(block, ["LI"]);
			var newBlock = this._insertNewBlockAround(li, before);
			return newBlock;
		} else if(isListItem && !forceTag) {
			var li = this.getParentElementOf(block, ["LI"]);
			var newBlock = this._insertNewBlockAround(block, before);
			if(li !== block) newBlock = this.splitContainerOf(newBlock, false, "prev");
			return newBlock;
		} else if(this.tree.isBlockContainer(block)) {
			this.wrapAllInlineOrTextNodesAs("P", block, true);
			return this._insertNewBlockAround(block.firstChild, before, forceTag);
		} else {
			return this._insertNewBlockAround(block, before, this.tree.isHeading(block) ? "P" : forceTag);
		}
	},
	
	/**
	 * @private
	 *
	 * TODO: Rename
	 */
	_insertNewBlockAround: function(element, before, tagName) {
		var newElement = this.createElement(tagName || element.nodeName);
		this.copyAttributes(element, newElement, false);
		this.correctEmptyElement(newElement);
		newElement = this.insertNodeAt(newElement, element, before ? "before" : "after");
		return newElement;
	},
	
	/**
	 * Wrap or replace element with given tag name.
	 *
	 * @param {String} [tag] Tag name. If not provided, it does not modify tag name.
	 * @param {Element} element Target element
	 * @param {String} [className] Class name of tag. If not provided, it does not modify current class name, and if empty string is provided, class attribute will be removed.
	 *
	 * @return {Element} wrapper element or replaced element.
	 */
	applyTagIntoElement: function(tag, element, className) {
		if(!tag && !className) return null;
		
		var result = element;
		
		if(tag) {
			if(this.tree.isBlockOnlyContainer(tag)) {
				result = this.wrapBlock(tag, element);
			} else if(this.tree.isBlockContainer(element)) {
				var wrapper = this.createElement(tag);
				this.moveChildNodes(element, wrapper);
				result = this.insertNodeAt(wrapper, element, "start");
			} else if(this.tree.isBlockContainer(tag) && this.hasImportantAttributes(element)) {
				result = this.wrapBlock(tag, element);
			} else {
				result = this.replaceTag(tag, element);
			}
		}
		
		if(className) {
			result.className = className;
		}	
		
		return result;
	},
	
	/**
	 * Wrap or replace elements with given tag name.
	 *
	 * @param {String} [tag] Tag name. If not provided, it does not modify tag name.
	 * @param {Element} from Start boundary (inclusive)
	 * @param {Element} to End boundary (inclusive)
	 * @param {String} [className] Class name of tag. If not provided, it does not modify current class name, and if empty string is provided, class attribute will be removed.
	 *
	 * @returns {Array} Array of wrappers or replaced elements
	 */
	applyTagIntoElements: function(tagName, from, to, className) {
		if(!tagName && !className) return [from, to];
		
		var applied = [];
		
		if(tagName) {
			if(this.tree.isBlockContainer(tagName)) {
				var family = this.tree.findCommonAncestorAndImmediateChildrenOf(from, to);
				var node = family.left;
				var wrapper = this.insertNodeAt(this.createElement(tagName), node, "before");
				
				var coveringWholeList =
					family.parent.nodeName === "LI" &&
					family.parent.parentNode.childNodes.length === 1 &&
					!family.left.previousSilbing &&
					!family.right.nextSibling;
					
				if(coveringWholeList) {
					var ul = node.parentNode.parentNode;
					this.insertNodeAt(wrapper, ul, "before");
					wrapper.appendChild(ul);
				} else {
					while(node !== family.right) {
						next = node.nextSibling;
						wrapper.appendChild(node);
						node = next;
					}
					wrapper.appendChild(family.right);
				}
				applied.push(wrapper);
			} else {
				// is normal tagName
				var elements = this.getBlockElementsBetween(from, to);
				for(var i = 0; i < elements.length; i++) {
					if(this.tree.isBlockContainer(elements[i])) {
						var wrappers = this.wrapAllInlineOrTextNodesAs(tagName, elements[i], true);
						for(var j = 0; j < wrappers.length; j++) {
							applied.push(wrappers[j]);
						}
					} else {
						applied.push(this.replaceTag(tagName, elements[i]) || elements[i]);
					}
				}
			}
		}
		
		if(className) {
			var elements = this.tree.collectNodesBetween(from, to, function(n) {return n.nodeType == 1;});
			for(var i = 0; i < elements.length; i++) {
				elements[i].className = className;
			}
		}	
		
		return applied;
	},
	
	/**
	 * Moves block up or down
	 *
	 * @param {Element} block Target block
	 * @param {boolean} up Move up if true
	 * 
	 * @returns {Element} Moved block. It could be different with given block.
	 */
	moveBlock: function(block, up) {
		// if block is table cell or contained by table cell, select its row as mover
		block = this.getParentElementOf(block, ["TR"]) || block;
		
		// if block is only child, select its parent as mover
		while(block.nodeName !== "TR" && block.parentNode !== this.getRoot() && !block.previousSibling && !block.nextSibling && !this.tree.isListContainer(block.parentNode)) {
			block = block.parentNode;
		}
		
		// find target and where
		var target, where;
		if (up) {
			target = block.previousSibling;
			
			if(target) {
				var singleNodeLi = target.nodeName === 'LI' && ((target.childNodes.length === 1 && this.tree.isBlock(target.firstChild)) || !this.tree.hasBlocks(target));
				var table = ['TABLE', 'TR'].indexOf(target.nodeName) !== -1;

				where = this.tree.isBlockContainer(target) && !singleNodeLi && !table ? "end" : "before";
			} else if(block.parentNode !== this.getRoot()) {
				target = block.parentNode;
				where = "before";
			}
		} else {
			target = block.nextSibling;
			
			if(target) {
				var singleNodeLi = target.nodeName === 'LI' && ((target.childNodes.length === 1 && this.tree.isBlock(target.firstChild)) || !this.tree.hasBlocks(target));
				var table = ['TABLE', 'TR'].indexOf(target.nodeName) !== -1;
				
				where = this.tree.isBlockContainer(target) && !singleNodeLi && !table ? "start" : "after";
			} else if(block.parentNode !== this.getRoot()) {
				target = block.parentNode;
				where = "after";
			}
		}
		
		
		// no way to go?
		if(!target) return null;
		if(["TBODY", "THEAD"].indexOf(target.nodeName) !== -1) return null;
		
		// normalize
		this.wrapAllInlineOrTextNodesAs("P", target, true);
		
		// make placeholder if needed
		if(this.isFirstLiWithNestedList(block)) {
			this.insertNewBlockAround(block, false, "P");
		}
		
		// perform move
		var parent = block.parentNode;
		var moved = this.insertNodeAt(block, target, where, true);
		
		// cleanup
		if(!parent.hasChildNodes()) this.deleteNode(parent, true);
		this.unwrapUnnecessaryParagraph(moved);
		this.unwrapUnnecessaryParagraph(target);

		// remove placeholder
		if(up) {
			if(moved.previousSibling && this.isEmptyBlock(moved.previousSibling) && !moved.previousSibling.previousSibling && moved.parentNode.nodeName === "LI" && this.tree.isListContainer(moved.nextSibling)) {
				this.deleteNode(moved.previousSibling);
			}
		} else {
			if(moved.nextSibling && this.isEmptyBlock(moved.nextSibling) && !moved.previousSibling && moved.parentNode.nodeName === "LI" && this.tree.isListContainer(moved.nextSibling.nextSibling)) {
				this.deleteNode(moved.nextSibling);
			}
		}
		
		this.correctEmptyElement(moved);
		
		return moved;
	},
	
	/**
	 * Remove given block
	 *
	 * @param {Element} block Target block
	 * @returns {Element} Nearest block of remove element
	 */
	removeBlock: function(block) {
		var blockToMove;

		// if block is only child, select its parent as mover
		while(block.parentNode !== this.getRoot() && !block.previousSibling && !block.nextSibling && !this.tree.isListContainer(block.parentNode)) {
			block = block.parentNode;
		}
		
		var finder = function(node) {return this.tree.isBlock(node) && !this.tree.isAtomic(node) && !this.tree.isDescendantOf(block, node) && !this.tree.hasBlocks(node);}.bind(this);
		var exitCondition = function(node) {return this.tree.isBlock(node) && !this.tree.isDescendantOf(this.getRoot(), node)}.bind(this);
		
		if(this.isFirstLiWithNestedList(block)) {
			blockToMove = this.outdentListItem(block.nextSibling.firstChild);
			this.deleteNode(blockToMove.previousSibling, true);
		} else if(this.tree.isTableCell(block)) {
			var rtable = new xq.RichTable(this, this.getParentElementOf(block, ["TABLE"]));
			blockToMove = rtable.getBelowCellOf(block);
			
			// should not delete row when there's thead and the row is the only child of tbody
			if(
				block.parentNode.parentNode.nodeName === "TBODY" &&
				rtable.hasHeadingAtTop() &&
				rtable.getDom().tBodies[0].rows.length === 1) return blockToMove;
			
			blockToMove = blockToMove ||
				this.tree.findForward(block, finder, exitCondition) ||
				this.tree.findBackward(block, finder, exitCondition);
			
			this.deleteNode(block.parentNode, true);
		} else {
			blockToMove = blockToMove ||
				this.tree.findForward(block, finder, exitCondition) ||
				this.tree.findBackward(block, finder, exitCondition);
			
			if(!blockToMove) blockToMove = this.insertNodeAt(this.makeEmptyParagraph(), block, "after");
			
			this.deleteNode(block, true);
		}
		if(!this.getRoot().hasChildNodes()) {
			blockToMove = this.createElement("P");
			this.getRoot().appendChild(blockToMove);
			this.correctEmptyElement(blockToMove);
		}
		
		return blockToMove;
	},
	
	/**
	 * Removes trailing whitespaces of given block
	 *
	 * @param {Element} block Target block
	 */
	removeTrailingWhitespace: function(block) {throw "Not implemented"},
	
	/**
	 * Extract given list item out and change its container's tag
	 *
	 * @param {Element} element LI or P which is a child of LI
	 * @param {String} type "OL", "UL"
	 * @param {String} className CSS class name
	 *
	 * @returns {Element} changed element
	 */
	changeListTypeTo: function(element, type, className) {
		type = type.toUpperCase();
		className = className || "";
		
		var li = this.getParentElementOf(element, ["LI"]);
		if(!li) throw "IllegalArgumentException";
		
		var container = li.parentNode;

		this.splitContainerOf(li);
		
		var newContainer = this.insertNodeAt(this.createElement(type), container, "before");
		if(className) newContainer.className = className;
		
		this.insertNodeAt(li, newContainer, "start");
		this.deleteNode(container);
		
		this.mergeAdjustLists(newContainer);
		
		return element;
	},
	
	/**
	 * Split container of element into (maxium) three pieces.
	 */
	splitContainerOf: function(element, preserveElementItself, dir) {
		if([element, element.parentNode].indexOf(this.getRoot()) !== -1) return element;

		var container = element.parentNode;
		if(element.previousSibling && (!dir || dir.toLowerCase() === "prev")) {
			var prev = this.createElement(container.nodeName);
			this.copyAttributes(container, prev);
			while(container.firstChild !== element) {
				prev.appendChild(container.firstChild);
			}
			this.insertNodeAt(prev, container, "before");
			this.unwrapUnnecessaryParagraph(prev);
		}
		
		if(element.nextSibling && (!dir || dir.toLowerCase() === "next")) {
			var next = this.createElement(container.nodeName);
			this.copyAttributes(container, next);
			while(container.lastChild !== element) {
				this.insertNodeAt(container.lastChild, next, "start");
			}
			this.insertNodeAt(next, container, "after");
			this.unwrapUnnecessaryParagraph(next);
		}
		
		if(!preserveElementItself) element = this.unwrapUnnecessaryParagraph(container) ? container : element;
		return element;
	},

	/**
	 * TODO: Add specs
	 */
	splitParentElement: function(seperator) {
		var parent = seperator.parentNode;
		if(["HTML", "HEAD", "BODY"].indexOf(parent.nodeName) !== -1) throw "Illegal argument. Cannot seperate element[" + parent.nodeName + "]";

		var previousSibling = seperator.previousSibling;
		var nextSibling = seperator.nextSibling;
		
		var newElement = this.insertNodeAt(this.createElement(parent.nodeName), parent, "after");
		
		var next;
		while(next = seperator.nextSibling) newElement.appendChild(next);
		
		this.insertNodeAt(seperator, newElement, "start");
		this.copyAttributes(parent, newElement);
		
		return newElement;
	},
	
	/**
	 * TODO: Add specs
	 */
	splitElementUpto: function(seperator, element, excludeElement) {
		while(seperator.previousSibling !== element) {
			if(excludeElement && seperator.parentNode === element) break;
			seperator = this.splitParentElement(seperator);
		}
		return seperator;
	},
	
	/**
	 * Merges two adjust elements
	 *
	 * @param {Element} element base element
	 * @param {boolean} withNext merge base element with next sibling
	 * @param {boolean} skip skip merge steps
	 */
	mergeElement: function(element, withNext, skip) {
		this.wrapAllInlineOrTextNodesAs("P", element.parentNode, true);
		
		// find two block
		if(withNext) {
			var prev = element;
			var next = this.tree.findForward(
				element,
				function(node) {return this.tree.isBlock(node) && !this.tree.isListContainer(node) && node !== element.parentNode}.bind(this)
			);
		} else {
			var next = element;
			var prev = this.tree.findBackward(
				element,
				function(node) {return this.tree.isBlock(node) && !this.tree.isListContainer(node) && node !== element.parentNode}.bind(this)
			);
		}
		
		// normalize next block
		if(next && this.tree.isDescendantOf(this.getRoot(), next)) {
			var nextContainer = next.parentNode;
			if(this.tree.isBlockContainer(next)) {
				nextContainer = next;
				this.wrapAllInlineOrTextNodesAs("P", nextContainer, true);
				next = nextContainer.firstChild;
			}
		} else {
			next = null;
		}
		
		// normalize prev block
		if(prev && this.tree.isDescendantOf(this.getRoot(), prev)) {
			var prevContainer = prev.parentNode;
			if(this.tree.isBlockContainer(prev)) {
				prevContainer = prev;
				this.wrapAllInlineOrTextNodesAs("P", prevContainer, true);
				prev = prevContainer.lastChild;
			}
		} else {
			prev = null;
		}
		
		try {
			var containersAreTableCell =
				prevContainer && (this.tree.isTableCell(prevContainer) || ['TR', 'THEAD', 'TBODY'].indexOf(prevContainer.nodeName) !== -1) &&
				nextContainer && (this.tree.isTableCell(nextContainer) || ['TR', 'THEAD', 'TBODY'].indexOf(nextContainer.nodeName) !== -1);
			
			if(containersAreTableCell && prevContainer !== nextContainer) return null;
			
			// if next has margin, perform outdent
			if((!skip || !prev) && next && nextContainer.nodeName !== "LI" && this.outdentElement(next)) return element;

			// nextContainer is first li and next of it is list container ([I] represents caret position):
			//
			// * A[I]
			// * B
			//   * C
			if(nextContainer && nextContainer.nodeName === 'LI' && this.tree.isListContainer(next.nextSibling)) {
				// move child nodes and...
				this.moveChildNodes(nextContainer, prevContainer);
				
				// merge two paragraphs
				this.removePlaceHoldersAndEmptyNodes(prev);
				this.moveChildNodes(next, prev);
				this.deleteNode(next);
				
				return prev;
			}
			
			// merge two list containers
			if(nextContainer && nextContainer.nodeName === 'LI' && this.tree.isListContainer(nextContainer.parentNode.previousSibling)) {
				this.mergeAdjustLists(nextContainer.parentNode.previousSibling, true, "next");
				return prev;
			}

			if(next && !containersAreTableCell && prevContainer && prevContainer.nodeName === 'LI' && nextContainer && nextContainer.nodeName === 'LI' && prevContainer.parentNode.nextSibling === nextContainer.parentNode) {
				var nextContainerContainer = nextContainer.parentNode;
				this.moveChildNodes(nextContainer.parentNode, prevContainer.parentNode);
				this.deleteNode(nextContainerContainer);
				return prev;
			}
			
			// merge two containers
			if(next && !containersAreTableCell && prevContainer && prevContainer.nextSibling === nextContainer && ((skip && prevContainer.nodeName !== "LI") || (!skip && prevContainer.nodeName === "LI"))) {
				this.moveChildNodes(nextContainer, prevContainer);
				return prev;
			}

			// unwrap container
			if(nextContainer && nextContainer.nodeName !== "LI" && !this.getParentElementOf(nextContainer, ["TABLE"]) && !this.tree.isListContainer(nextContainer) && nextContainer !== this.getRoot() && !next.previousSibling) {
				return this.unwrapElement(nextContainer, true);
			}
			
			// delete table
			if(withNext && nextContainer && nextContainer.nodeName === "TABLE") {
				this.deleteNode(nextContainer, true);
				return prev;
			} else if(!withNext && prevContainer && this.tree.isTableCell(prevContainer) && !this.tree.isTableCell(nextContainer)) {
				this.deleteNode(this.getParentElementOf(prevContainer, ["TABLE"]), true);
				return next;
			}
			
			// if prev is same with next, do nothing
			if(prev === next) return null;

			// if there is a null block, do nothing
			if(!prev || !next || !prevContainer || !nextContainer) return null;
			
			// if two blocks are not in the same table cell, do nothing
			if(this.getParentElementOf(prev, ["TD", "TH"]) !== this.getParentElementOf(next, ["TD", "TH"])) return null;
			
			var prevIsEmpty = false;
			
			// cleanup empty block before merge

			// 1. cleanup prev node which ends with marker + &nbsp;
			if(
				xq.Browser.isTrident &&
				prev.childNodes.length >= 2 &&
				this.isMarker(prev.lastChild.previousSibling) &&
				prev.lastChild.nodeType === 3 &&
				prev.lastChild.nodeValue.length === 1 &&
				prev.lastChild.nodeValue.charCodeAt(0) === 160
			) {
				this.deleteNode(prev.lastChild);
			}

			// 2. cleanup prev node (if prev is empty, then replace prev's tag with next's)
			this.removePlaceHoldersAndEmptyNodes(prev);
			if(this.isEmptyBlock(prev)) {
				// replace atomic block with normal block so that following code don't need to care about atomic block
				if(this.tree.isAtomic(prev)) prev = this.replaceTag("P", prev);
				
				prev = this.replaceTag(next.nodeName, prev) || prev;
				prev.innerHTML = "";
			} else if(prev.firstChild === prev.lastChild && this.isMarker(prev.firstChild)) {
				prev = this.replaceTag(next.nodeName, prev) || prev;
			}
			
			// 3. cleanup next node
			if(this.isEmptyBlock(next)) {
				// replace atomic block with normal block so that following code don't need to care about atomic block
				if(this.tree.isAtomic(next)) next = this.replaceTag("P", next);
				
				next.innerHTML = "";
			}
			
			// perform merge
			this.moveChildNodes(next, prev);
			this.deleteNode(next);
			return prev;
		} finally {
			// cleanup
			if(prevContainer && this.isEmptyBlock(prevContainer)) this.deleteNode(prevContainer, true);
			if(nextContainer && this.isEmptyBlock(nextContainer)) this.deleteNode(nextContainer, true);
			
			if(prevContainer) this.unwrapUnnecessaryParagraph(prevContainer);
			if(nextContainer) this.unwrapUnnecessaryParagraph(nextContainer);
		}
	},
	
	/**
	 * Merges adjust list containers which has same tag name
	 *
	 * @param {Element} container target list container
	 * @param {boolean} force force adjust list container even if they have different list type
	 * @param {String} dir Specify merge direction: PREV or NEXT. If not supplied it will be merged with both direction.
	 */
	mergeAdjustLists: function(container, force, dir) {
		var prev = container.previousSibling;
		var isPrevSame = prev && (prev.nodeName === container.nodeName && prev.className === container.className);
		if((!dir || dir.toLowerCase() === 'prev') && (isPrevSame || (force && this.tree.isListContainer(prev)))) {
			while(prev.lastChild) {
				this.insertNodeAt(prev.lastChild, container, "start");
			}
			this.deleteNode(prev);
		}
		
		var next = container.nextSibling;
		var isNextSame = next && (next.nodeName === container.nodeName && next.className === container.className);
		if((!dir || dir.toLowerCase() === 'next') && (isNextSame || (force && this.tree.isListContainer(next)))) {
			while(next.firstChild) {
				this.insertNodeAt(next.firstChild, container, "end");
			}
			this.deleteNode(next);
		}
	},
	
	/**
	 * Moves child nodes from one element into another.
	 *
	 * @param {Elemet} from source element
	 * @param {Elemet} to target element
	 */
	moveChildNodes: function(from, to) {
		if(this.tree.isDescendantOf(from, to) || ["HTML", "HEAD"].indexOf(to.nodeName) !== -1)
			throw "Illegal argument. Cannot move children of element[" + from.nodeName + "] to element[" + to.nodeName + "]";
		
		if(from === to) return;
		
		while(from.firstChild) to.appendChild(from.firstChild);
	},
	
	/**
	 * Copies attributes from one element into another.
	 *
	 * @param {Element} from source element
	 * @param {Element} to target element
	 * @param {boolean} copyId copy ID attribute of source element
	 */
	copyAttributes: function(from, to, copyId) {
		// IE overrides this
		
		var attrs = from.attributes;
		if(!attrs) return;
		
		for(var i = 0; i < attrs.length; i++) {
			if(attrs[i].nodeName === "class" && attrs[i].nodeValue) {
				to.className = attrs[i].nodeValue;
			} else if((copyId || "id" !== attrs[i].nodeName) && attrs[i].nodeValue) {
				to.setAttribute(attrs[i].nodeName, attrs[i].nodeValue);
			}
		}
	},

	_indentElements: function(node, blocks, affect) {
		for (var i=0; i < affect.length; i++) {
			if (affect[i] === node || this.tree.isDescendantOf(affect[i], node))
				return;
		}
		leaves = this.tree.getLeavesAtEdge(node);
		
		if (blocks.includeElement(leaves[0])) {
			var affected = this.indentElement(node, true);
			if (affected) {
				affect.push(affected);
				return;
			}
		}
		
		if (blocks.includeElement(node)) {
			var affected = this.indentElement(node, true);
			if (affected) {
				affect.push(affected);
				return;
			}
		}

		var children=xq.$A(node.childNodes);
		for (var i=0; i < children.length; i++)
			this._indentElements(children[i], blocks, affect);
		return;
	},

	indentElements: function(from, to) {
		var blocks = this.getBlockElementsBetween(from, to);
		var top = this.tree.findCommonAncestorAndImmediateChildrenOf(from, to);
		
		var affect = [];
		
		leaves = this.tree.getLeavesAtEdge(top.parent);
		if (blocks.includeElement(leaves[0])) {
			var affected = this.indentElement(top.parent);
			if (affected)
				return [affected];
		}
		
		var children = xq.$A(top.parent.childNodes);
		for (var i=0; i < children.length; i++) {
			this._indentElements(children[i], blocks, affect);
		}
		
		affect = affect.flatten()
		return affect.length > 0 ? affect : blocks;
	},
	
	outdentElementsCode: function(node) {
		if (node.tagName === 'LI')
			node = node.parentNode;
		if (node.tagName === 'OL' && node.className === 'code')
			return true;
		return false;
	},
	
	_outdentElements: function(node, blocks, affect) {
		for (var i=0; i < affect.length; i++) {
			if (affect[i] === node || this.tree.isDescendantOf(affect[i], node))
				return;
		}
		leaves = this.tree.getLeavesAtEdge(node);
		
		if (blocks.includeElement(leaves[0]) && !this.outdentElementsCode(leaves[0])) {
			var affected = this.outdentElement(node, true);
			if (affected) {
				affect.push(affected);
				return;
			}
		}
		
		if (blocks.includeElement(node)) {
			var children = xq.$A(node.parentNode.childNodes);
			var isCode = this.outdentElementsCode(node);
			var affected = this.outdentElement(node, true, isCode);
			if (affected) {
				if (children.includeElement(affected) && this.tree.isListContainer(node.parentNode) && !isCode) {
					for (var i=0; i < children.length; i++) {
						if (blocks.includeElement(children[i]) && !affect.includeElement(children[i]))
							affect.push(children[i]);
					}
				}else
					affect.push(affected);
				return;
			}
		}

		var children=xq.$A(node.childNodes);
		for (var i=0; i < children.length; i++)
			this._outdentElements(children[i], blocks, affect);
		return;
	},

	outdentElements: function(from, to) {
		var start, end;
		
		if (from.parentNode.tagName === 'LI') start=from.parentNode;
		if (to.parentNode.tagName === 'LI') end=to.parentNode;
		
		var blocks = this.getBlockElementsBetween(from, to);
		var top = this.tree.findCommonAncestorAndImmediateChildrenOf(from, to);
		
		var affect = [];
		
		leaves = this.tree.getLeavesAtEdge(top.parent);
		if (blocks.includeElement(leaves[0]) && !this.outdentElementsCode(top.parent)) {
			var affected = this.outdentElement(top.parent);
			if (affected)
				return [affected];
		}
		
		var children = xq.$A(top.parent.childNodes);
		for (var i=0; i < children.length; i++) {
			this._outdentElements(children[i], blocks, affect);
		}

		if (from.offsetParent && to.offsetParent) {
			start = from;
			end = to;
		}else if (blocks.first().offsetParent && blocks.last().offsetParent) {
			start = blocks.first();
			end = blocks.last();
		}
		
		affect = affect.flatten()
		if (!start || !start.offsetParent)
			start = affect.first();
		if (!end || !end.offsetParent)
			end = affect.last();
		
		return this.getBlockElementsBetween(start, end);
	},
	
	/**
	 * Performs indent by increasing element's margin-left
	 */	
	indentElement: function(element, noParent, forceMargin) {
		if(
			!forceMargin &&
			(element.nodeName === "LI" || (!this.tree.isListContainer(element) && !element.previousSibling && element.parentNode.nodeName === "LI"))
		) return this.indentListItem(element, noParent);
		
		var root = this.getRoot();
		if(!element || element === root) return null;
		
		if (element.parentNode !== root && !element.previousSibling && !noParent) element=element.parentNode;
		
		var margin = element.style.marginLeft;
		var cssValue = margin ? this._getCssValue(margin, "px") : {value:0, unit:"em"};
		
		cssValue.value += 2;
		element.style.marginLeft = cssValue.value + cssValue.unit;
		
		return element;
	},
	
	/**
	 * Performs outdent by decreasing element's margin-left
	 */	
	outdentElement: function(element, noParent, forceMargin) {
		if(!forceMargin && element.nodeName === "LI") return this.outdentListItem(element, noParent);
		
		var root = this.getRoot();
		if(!element || element === root) return null;
		
		var margin = element.style.marginLeft;
		
		var cssValue = margin ? this._getCssValue(margin, "px") : {value:0, unit:"em"};
		if(cssValue.value === 0) {
			return element.previousSibling || forceMargin ?
				null :
				this.outdentElement(element.parentNode, noParent);
		}
		
		cssValue.value -= 2;
		element.style.marginLeft = cssValue.value <= 0 ? "" : cssValue.value + cssValue.unit;
		if(element.style.cssText === "") element.removeAttribute("style");
		
		return element;
	},
	
	/**
	 * Performs indent for list item
	 */
	indentListItem: function(element, treatListAsNormalBlock) {
		var li = this.getParentElementOf(element, ["LI"]);
		var container = li.parentNode;
		var prev = li.previousSibling;
		if(!li.previousSibling) return this.indentElement(container);
		
		if(li.parentNode.nodeName === "OL" && li.parentNode.className === "code") return this.indentElement(li, treatListAsNormalBlock, true);
		
		if(!prev.lastChild) prev.appendChild(this.makePlaceHolder());
		
		var targetContainer = 
			this.tree.isListContainer(prev.lastChild) ?
			// if there's existing list container, select it as target container
			prev.lastChild :
			// if there's nothing, create new one
			this.insertNodeAt(this.createElement(container.nodeName), prev, "end");
		
		this.wrapAllInlineOrTextNodesAs("P", prev, true);
		
		// perform move
		targetContainer.appendChild(li);
		
		// flatten nested list
		if(!treatListAsNormalBlock && li.lastChild && this.tree.isListContainer(li.lastChild)) {
			var childrenContainer = li.lastChild;
			var child;
			while(child = childrenContainer.lastChild) {
				this.insertNodeAt(child, li, "after");
			}
			this.deleteNode(childrenContainer);
		}
		
		this.unwrapUnnecessaryParagraph(li);
		
		return li;
	},
	
	/**
	 * Performs outdent for list item
	 *
	 * @return {Element} outdented list item or null if no outdent performed
	 */
	outdentListItem: function(element, treatListAsNormalBlock) {
		var li = this.getParentElementOf(element, ["LI"]);
		var container = li.parentNode;

		if(!li.previousSibling) {
			var performed = this.outdentElement(container);
			if(performed) return performed;
		}

		if(li.parentNode.nodeName === "OL" && li.parentNode.className === "code") return this.outdentElement(li, treatListAsNormalBlock, true);
		
		var parentLi = container.parentNode;
		if(parentLi.nodeName !== "LI") return null;
		
		if(treatListAsNormalBlock) {
			while(container.lastChild !== li) {
				this.insertNodeAt(container.lastChild, parentLi, "after");
			}
		} else {
			// make next siblings as children
			if(li.nextSibling) {
				var targetContainer =
					li.lastChild && this.tree.isListContainer(li.lastChild) ?
						// if there's existing list container, select it as target container
						li.lastChild :
						// if there's nothing, create new one
						this.insertNodeAt(this.createElement(container.nodeName), li, "end");
				
				this.copyAttributes(container, targetContainer);
				
				var sibling;
				while(sibling = li.nextSibling) {
					targetContainer.appendChild(sibling);
				}
			}
		}
		
		// move current LI into parent LI's next sibling
		li = this.insertNodeAt(li, parentLi, "after");
		
		// remove empty container
		if(container.childNodes.length === 0) this.deleteNode(container);
		
		if(li.firstChild && this.tree.isListContainer(li.firstChild)) {
			this.insertNodeAt(this.makePlaceHolder(), li, "start");
		}
		
		this.wrapAllInlineOrTextNodesAs("P", li);
		this.unwrapUnnecessaryParagraph(parentLi);
		
		return li;
	},
	
	/**
	 * Performs justification
	 *
	 * @param {Element} block target element
	 * @param {String} dir one of "LEFT", "CENTER", "RIGHT", "BOTH"
	 */
	justifyBlock: function(block, dir) {
		// if block is only child, select its parent as mover
		while(block.parentNode !== this.getRoot() && !block.previousSibling && !block.nextSibling && !this.tree.isListContainer(block.parentNode)) {
			block = block.parentNode;
		}
		
		var styleValue = dir.toLowerCase() === "both" ? "justify" : dir;
		if(styleValue === "left") {
			block.style.textAlign = "";
			if(block.style.cssText === "") block.removeAttribute("style");
		} else {
			block.style.textAlign = styleValue;
		}
		return block;
	},
	
	justifyBlocks: function(blocks, dir) {
		for(var i = 0; i < blocks.length; i++) {
			this.justifyBlock(blocks[i], dir);
		}
		return blocks;
	},
	
	/**
     * Turn given element into list. If the element is a list already, it will be reversed into normal element.
	 *
	 * @param {Element} element target element
	 * @param {String} type one of "UL", "OL"
	 * @param {String} className CSS className
	 * @returns {Element} affected element
	 */
	applyList: function(element, type, className) {
		type = type.toUpperCase();
		className = className || "";
		
		var containerTag = type;
		
		if(element.nodeName === "LI" || (element.parentNode.nodeName === "LI" && !element.previousSibling)) {
			var element = this.getParentElementOf(element, ["LI"]);
			var container = element.parentNode;
			if(container.nodeName === containerTag && container.className === className) {
				return this.extractOutElementFromParent(element);
			} else {
				return this.changeListTypeTo(element, type, className);
			}
		} else {
			return this.turnElementIntoListItem(element, type, className);
		}
	},
	
	applyLists: function(from, to, type, className) {
		type = type.toUpperCase();
		className = className || "";

		var containerTag = type;
		var blocks = this.getBlockElementsBetween(from, to);
		
		// LIs or Non-containing blocks
		var whole = blocks.findAll(function(e) {
			return e.nodeName === "LI" || !this.tree.isBlockContainer(e);
		}.bind(this));
		
		// LIs
		var listItems = whole.findAll(function(e) {return e.nodeName === "LI"}.bind(this));
		
		// Non-containing blocks which is not a descendant of any LIs selected above(listItems).
		var normalBlocks = whole.findAll(function(e) {
			return e.nodeName !== "LI" &&
				!(e.parentNode.nodeName === "LI" && !e.previousSibling && !e.nextSibling) &&
				!this.tree.isDescendantOf(listItems, e)
		}.bind(this));
		
		var diffListItems = listItems.findAll(function(e) {
			return e.parentNode.nodeName !== containerTag;
		}.bind(this));
		
		// Conditions needed to determine mode
		var hasNormalBlocks = normalBlocks.length > 0;
		var hasDifferentListStyle = diffListItems.length > 0;
		
		var blockToHandle = null;
		
		if(hasNormalBlocks) {
			blockToHandle = normalBlocks;
		} else if(hasDifferentListStyle) {
			blockToHandle = diffListItems;
		} else {
			blockToHandle = listItems;
		}
		
		// perform operation
		for(var i = 0; i < blockToHandle.length; i++) {
			var block = blockToHandle[i];
			
			// preserve original index to restore selection
			var originalIndex = blocks.indexOf(block);
			blocks[originalIndex] = this.applyList(block, type, className);
		}
		
		return blocks;
	},

	/**
	 * Insert place-holder for given empty element. Empty element does not displayed and causes many editing problems.
	 *
	 * @param {Element} element empty element
	 */
	correctEmptyElement: function(element) {throw "Not implemented"},

	/**
	 * Corrects current block-only-container to do not take any non-block element or node.
	 */
	correctParagraph: function() {throw "Not implemented"},
	
	/**
	 * Makes place-holder for empty element.
	 *
	 * @returns {Node} Platform specific place holder
	 */
	makePlaceHolder: function() {throw "Not implemented"},
	
	/**
	 * Makes place-holder string.
	 *
	 * @returns {String} Platform specific place holder string
	 */
	makePlaceHolderString: function() {throw "Not implemented"},
	
	/**
	 * Makes empty paragraph which contains only one place-holder
	 */
	makeEmptyParagraph: function() {throw "Not implemented"},

	/**
	 * Applies background color to selected area
	 *
	 * @param {Object} color valid CSS color value
	 */
	applyBackgroundColor: function(color) {throw "Not implemented";},

	/**
	 * Applies foreground color to selected area
	 *
	 * @param {Object} color valid CSS color value
	 */
	applyForegroundColor: function(color) {
		this.execCommand("forecolor", color);
	},
	
	/**
	 * Applies font face to selected area
	 *
	 * @param {String} face font face
	 */
	applyFontFace: function(face) {
		this.execCommand("fontname", face);
	},
	
	/**
	 * Applies font size to selected area
	 *
	 * @param {Number} size font size (px)
	 */
	applyFontSize: function(size) {
		this.execCommand("fontsize", size);
	},
	
	execCommand: function(commandId, param) {throw "Not implemented";},
	
	applyRemoveFormat: function() {throw "Not implemented";},
	applyEmphasis: function() {throw "Not implemented";},
	applyStrongEmphasis: function() {throw "Not implemented";},
	applyStrike: function() {throw "Not implemented";},
	applyUnderline: function() {throw "Not implemented";},
	applySuperscription: function() {
		this.execCommand("superscript");
	},
	applySubscription: function() {
		this.execCommand("subscript");
	},
	indentBlock: function(element, treatListAsNormalBlock) {
		return (!element.previousSibling && element.parentNode.nodeName === "LI") ?
			this.indentListItem(element, treatListAsNormalBlock) :
			this.indentElement(element);
	},
	outdentBlock: function(element, treatListAsNormalBlock) {
		while(true) {
			if(!element.previousSibling && element.parentNode.nodeName === "LI") {
				element = this.outdentListItem(element, treatListAsNormalBlock);
				return element;
			} else {
				var performed = this.outdentElement(element);
				if(performed) return performed;
				
				// first-child can outdent container
				if(!element.previousSibling) {
					element = element.parentNode;
				} else {
					break;
				}
			}
		}
		
		return null;
	},
	wrapBlock: function(tag, start, end) {
		if(this.tree._blockTags.indexOf(tag) === -1) throw "Unsuppored block container: [" + tag + "]";
		if(!start) start = this.getCurrentBlockElement();
		if(!end) end = start;
		
		// Check if the selection captures valid fragement
		var validFragment = false;
		
		if(start === end) {
			// are they same block?
			validFragment = true;
		} else if(start.parentNode === end.parentNode && !start.previousSibling && !end.nextSibling) {
			// are they covering whole parent?
			validFragment = true;
			start = end = start.parentNode;
		} else {
			// are they siblings of non-LI blocks?
			validFragment =
				(start.parentNode === end.parentNode) &&
				(start.nodeName !== "LI");
		}
		
		if(!validFragment) return null;
		
		var wrapper = this.createElement(tag);
		
		if(start === end) {
			// They are same.
			if(this.tree.isBlockContainer(start) && !this.tree.isListContainer(start)) {
				// It's a block container. Wrap its contents.
				if(this.tree.isBlockOnlyContainer(wrapper)) {
					this.correctEmptyElement(start);
					this.wrapAllInlineOrTextNodesAs("P", start, true);
				}
				this.moveChildNodes(start, wrapper);
				start.appendChild(wrapper);
			} else {
				// It's not a block container. Wrap itself.
				wrapper = this.insertNodeAt(wrapper, start, "after");
				wrapper.appendChild(start);
			}
			
			this.correctEmptyElement(wrapper);
		} else {
			// They are siblings. Wrap'em all.
			wrapper = this.insertNodeAt(wrapper, start, "before");
			var node = start;
			
			while(node !== end) {
				next = node.nextSibling;
				wrapper.appendChild(node);
				node = next;
			}
			wrapper.appendChild(node);
		}
		
		return wrapper;
	},


	
	/////////////////////////////////////////////
	// Focus/Caret/Selection
	
	/**
	 * Gives focus to root element's window
	 */
	focus: function() {throw "Not implemented";},

	/**
	 * Returns selection object
	 */
	sel: function() {throw "Not implemented";},
	
	/**
	 * Returns range object
	 */
	rng: function() {throw "Not implemented";},
	
	/**
	 * Returns true if DOM has selection
	 */
	hasSelection: function() {throw "Not implemented";},

	/**
	 * Returns true if root element's window has selection
	 */
	hasFocus: function() {
		return this.focused;
	},
	
	/**
	 * Adjust scrollbar to make the element visible in current viewport.
	 *
	 * @param {Element} element Target element
	 * @param {boolean} toTop Align element to top of the viewport
	 * @param {boolean} moveCaret Move caret to the element
	 */
	scrollIntoView: function(element, toTop, moveCaret) {
		element.scrollIntoView(toTop);
		if(moveCaret) this.placeCaretAtStartOf(element);
	},
	
	/**
	 * Select all document
	 */
	selectAll: function() {
		return this.execCommand('selectall');
	},
	
	/**
	 * Select specified element.
	 *
	 * @param {Element} element element to select
	 * @param {boolean} entireElement true to select entire element, false to select inner content of element 
	 */
	selectElement: function(node, entireElement) {throw "Not implemented"},
	
	/**
	 * Select all elements between two blocks(inclusive).
	 *
	 * @param {Element} start start of selection
	 * @param {Element} end end of selection
	 */
	selectBlocksBetween: function(start, end) {throw "Not implemented"},
	
	/**
	 * Delete selected area
	 */
	deleteSelection: function() {throw "Not implemented"},
	
	/**
	 * Collapses current selection.
	 *
	 * @param {boolean} toStart true to move caret to start of selected area.
	 */
	collapseSelection: function(toStart) {throw "Not implemented"},
	
	/**
	 * Returns selected area as HTML string
	 */
	getSelectionAsHtml: function() {throw "Not implemented"},
	
	/**
	 * Returns selected area as text string
	 */
	getSelectionAsText: function() {throw "Not implemented"},
	
	/**
	 * Places caret at start of the element
	 *
	 * @param {Element} element Target element
	 */
	placeCaretAtStartOf: function(element) {throw "Not implemented"},

	
	/**
	 * Checks if the caret is place at start of the block
	 */
	isCaretAtBlockStart: function() {
		if(this.isCaretAtEmptyBlock()) return true;
		if(this.hasSelection()) return false;
		var node = this.getCurrentBlockElement();
		var marker = this.pushMarker();
		
		var isTrue = false;
		while (node = this.getFirstChild(node)) {
			if (node === marker) {
				isTrue = true;
				break;
			}
		}
		
		this.popMarker();
		
		return isTrue;
	},
	
	/**
	 * Checks if the caret is place at end of the block
	 */
	isCaretAtBlockEnd: function() {throw "Not implemented"},
	
	/**
	 * Checks if the node is empty-text-node or not
	 */
	isEmptyTextNode: function(node) {
		return node.nodeType === 3 && (node.nodeValue.length === 0 || (node.nodeValue.length === 1 && (node.nodeValue.charAt(0) === 32 || node.nodeValue.charAt(0) === 160)));
	},
	
	/**
	 * Checks if the caret is place in empty block element
	 */
	isCaretAtEmptyBlock: function() {
		return this.isEmptyBlock(this.getCurrentBlockElement());
	},
	
	/**
	 * Saves current selection info
	 *
	 * @returns {Object} Bookmark for selection
	 */
	saveSelection: function() {throw "Not implemented"},
	
	/**
	 * Restores current selection info
	 *
	 * @param {Object} bookmark Bookmark
	 */
	restoreSelection: function(bookmark) {throw "Not implemented"},
	
	/**
	 * Create marker
	 */
	createMarker: function() {
		var marker = this.createElement("SPAN");
		marker.id = "xquared_marker_" + (this._lastMarkerId++);
		marker.className = "xquared_marker";
		return marker;
	},

	/**
	 * Create and insert marker into current caret position.
	 * Marker is an inline element which has no child nodes. It can be used with many purposes.
	 * For example, You can push marker to mark current caret position.
	 *
	 * @returns {Element} marker
	 */
	pushMarker: function() {
		var marker = this.createMarker();
		return this.insertNode(marker);
	},
	
	/**
	 * Removes last marker
	 *
	 * @params {boolean} moveCaret move caret into marker before delete.
	 */
	popMarker: function(moveCaret) {
		var id = "xquared_marker_" + (--this._lastMarkerId);
		var marker = this.$(id);
		if(!marker) return;
		
		if(moveCaret) {
			this.selectElement(marker, true);
			this.collapseSelection(false);
		}
		
		this.deleteNode(marker);
	},
	
	
	
	/////////////////////////////////////////////
	// Query methods
	
	isMarker: function(node) {
		return (node.nodeType === 1 && node.nodeName === "SPAN" && node.className === "xquared_marker");
	},
	
	isFirstBlockOfBody: function(block) {
		var root = this.getRoot();
		if(this.isFirstLiWithNestedList(block)) block = block.parentNode;
		
		var found = this.tree.findBackward(
			block,
			function(node) {
				return node === root || (this.tree.isBlock(node) && !this.tree.isBlockOnlyContainer(node));
			}.bind(this)
		);
		
		return found === root;
	},
	
	/**
	 * Returns outer HTML of given element
	 */
	getOuterHTML: function(element) {throw "Not implemented"},
	
	/**
	 * Returns inner text of given element
	 * 
	 * @param {Element} element Target element
	 * @returns {String} Text string
	 */
	getInnerText: function(element) {
		return element.innerHTML.stripTags();
	},
	
	/**
	 * Checks if given node is place holder or not.
	 * 
	 * @param {Node} node DOM node
	 */
	isPlaceHolder: function(node) {throw "Not implemented"},
	
	/**
	 * Checks if given block is the first LI whose next sibling is a nested list.
	 *
	 * @param {Element} block Target block
	 */
	isFirstLiWithNestedList: function(block) {
		return !block.previousSibling &&
			block.parentNode.nodeName === "LI" &&
			this.tree.isListContainer(block.nextSibling);
	},
	
	/**
	 * Search all links within given element
	 *
	 * @param {Element} [element] Container element. If not given, the root element will be used.
	 * @param {Array} [found] if passed, links will be appended into this array.
	 * @returns {Array} Array of anchors. It returns empty array if there's no links.
	 */
	searchAnchors: function(element, found) {
		if(!element) element = this.getRoot();
		if(!found) found = [];

		var anchors = element.getElementsByTagName("A");
		for(var i = 0; i < anchors.length; i++) {
			found.push(anchors[i]);
		}

		return found;
	},
	
	/**
	 * Search all headings within given element
	 *
	 * @param {Element} [element] Container element. If not given, the root element will be used.
	 * @param {Array} [found] if passed, headings will be appended into this array.
	 * @returns {Array} Array of headings. It returns empty array if there's no headings.
	 */
	searchHeadings: function(element, found) {
		if(!element) element = this.getRoot();
		if(!found) found = [];

		var regexp = /^h[1-6]/ig;
		var nodes = element.childNodes;
		if (!nodes) return [];
		
		for(var i = 0; i < nodes.length; i++) {
			var isContainer = nodes[i] && this.tree._blockContainerTags.indexOf(nodes[i].nodeName) !== -1;
			var isHeading = nodes[i] && nodes[i].nodeName.match(regexp);

			if (isContainer) {
				this.searchHeadings(nodes[i], found);
			} else if (isHeading) {
				found.push(nodes[i]);
			}
		}

		return found;
	},
	
	/**
	 * Collect structure and style informations of given element.
	 *
	 * @param {Element} element target element
	 * @returns {Object} object that contains information: {em: true, strong: false, block: "p", list: "ol", ...}
	 */
	collectStructureAndStyle: function(element) {
		if(!element || element.nodeName === "#document") return {};

		var block = this.getParentBlockElementOf(element);
		
		if(block === null || (xq.Browser.isTrident && ["ready", "complete"].indexOf(block.readyState) === -1)) return {};
		
		var parents = this.tree.collectParentsOf(element, true, function(node) {return block.parentNode === node});
		var blockName = block.nodeName;

		var info = {};
		var doc = this.getDoc();
		var em = doc.queryCommandState("Italic");
		var strong = doc.queryCommandState("Bold");
		var strike = doc.queryCommandState("Strikethrough");
		var underline = doc.queryCommandState("Underline") && !this.getParentElementOf(element, ["A"]);
		var superscription = doc.queryCommandState("superscript");
		var subscription = doc.queryCommandState("subscript");
		var foregroundColor = doc.queryCommandValue("forecolor");
		var fontName = doc.queryCommandValue("fontname");
		var fontSize = doc.queryCommandValue("fontsize");
		// @WORKAROUND: Trident's fontSize value is affected by CSS
		if(xq.Browser.isTrident && fontSize === "5" && this.getParentElementOf(element, ["H1", "H2", "H3", "H4", "H5", "H6"])) fontSize = "";
		
		// @TODO: remove conditional
		var backgroundColor;
		if(xq.Browser.isGecko) {
			this.execCommand("styleWithCSS", "true");
			try {
				backgroundColor = doc.queryCommandValue("hilitecolor");
			} catch(e) {
				// if there's selection and the first element of the selection is
				// an empty block...
				backgroundColor = "";
			}
			this.execCommand("styleWithCSS", "false");
		} else {
			backgroundColor = doc.queryCommandValue("backcolor");
		}
		
		// if block is only child, select its parent
		while(block.parentNode && block.parentNode !== this.getRoot() && !block.previousSibling && !block.nextSibling && !this.tree.isListContainer(block.parentNode)) {
			block = block.parentNode;
		}

		var list = false;
		if(block.nodeName === "LI") {
			var parent = block.parentNode;
			var isCode = parent.nodeName === "OL" && parent.className === "code";
			var hasClass = parent.className.length > 0;
			if(isCode) {
				list = "CODE";
			} else if(hasClass) {
				list = false;
			} else {
				list = parent.nodeName;
			}
		}
		
		var justification = block.style.textAlign || "left";
		
		return {
			block:blockName,
			em: em,
			strong: strong,
			strike: strike,
			underline: underline,
			superscription: superscription,
			subscription: subscription,
			list: list,
			justification: justification,
			foregroundColor: foregroundColor,
			backgroundColor: backgroundColor,
			fontSize: fontSize,
			fontName: fontName
		};
	},
	
	/**
	 * Checks if the element has one or more important attributes: id, class, style
	 *
	 * @param {Element} element Target element
	 */
	hasImportantAttributes: function(element) {throw "Not implemented"},
	
	/**
	 * Checks if the element is empty or not. Place-holder is not counted as a child.
	 *
	 * @param {Element} element Target element
	 */
	isEmptyBlock: function(element) {throw "Not implemented"},
	
	/**
	 * Returns element that contains caret.
	 */
	getCurrentElement: function() {throw "Not implemented"},
	
	/**
	 * Returns block element that contains caret. Trident overrides this method.
	 */
	getCurrentBlockElement: function() {
		var cur = this.getCurrentElement();
		if(!cur) return null;
		
		var block = this.getParentBlockElementOf(cur);
		if(!block) return null;
		
		return (block.nodeName === "BODY") ? null : block;
	},
	
	/**
	 * Returns parent block element of parameter.
	 * If the parameter itself is a block, it will be returned.
	 *
	 * @param {Element} element Target element
	 *
	 * @returns {Element} Element or null
	 */
	getParentBlockElementOf: function(element) {
		while(element) {
			if(this.tree._blockTags.indexOf(element.nodeName) !== -1) return element;
			element = element.parentNode;
		}
		return null;
	},
	
	/**
	 * Returns parent element of parameter which has one of given tag name.
	 * If the parameter itself has the same tag name, it will be returned.
	 *
	 * @param {Element} element Target element
	 * @param {Array} tagNames Array of string which contains tag names
	 *
	 * @returns {Element} Element or null
	 */
	getParentElementOf: function(element, tagNames) {
		while(element) {
			if(tagNames.indexOf(element.nodeName) !== -1) return element;
			element = element.parentNode;
		}
		return null;
	},
	
	/**
	 * Collects all block elements between two elements
	 *
	 * @param {Element} from Start element(inclusive)
	 * @param {Element} to End element(inclusive)
	 */
	getBlockElementsBetween: function(from, to) {
		return this.tree.collectNodesBetween(from, to, function(node) {
			return node.nodeType === 1 && this.tree.isBlock(node);
		}.bind(this));
	},
	
	/**
	 * Returns block element that contains selection start.
	 *
	 * This method will return exactly same result with getCurrentBlockElement method
	 * when there's no selection.
	 */
	getBlockElementAtSelectionStart: function() {throw "Not implemented"},
	
	/**
	 * Returns block element that contains selection end.
	 *
	 * This method will return exactly same result with getCurrentBlockElement method
	 * when there's no selection.
	 */
	getBlockElementAtSelectionEnd: function() {throw "Not implemented"},
	
	/**
	 * Returns blocks at each edge of selection(start and end).
	 *
	 * TODO: implement ignoreEmptyEdges for FF
	 *
	 * @param {boolean} naturalOrder Mak the start element always comes before the end element
	 * @param {boolean} ignoreEmptyEdges Prevent some browser(Gecko) from selecting one more block than expected
	 */
	getBlockElementsAtSelectionEdge: function(naturalOrder, ignoreEmptyEdges) {throw "Not implemented"},
	
	/**
	 * Returns array of selected block elements
	 */
	getSelectedBlockElements: function() {
		var selectionEdges = this.getBlockElementsAtSelectionEdge(true, true);
		var start = selectionEdges[0];
		var end = selectionEdges[1];
		
		return this.tree.collectNodesBetween(start, end, function(node) {
			return node.nodeType === 1 && this.tree.isBlock(node);
		}.bind(this));
	},
	
	/**
	 * Get element by ID
	 *
	 * @param {String} id Element's ID
	 * @returns {Element} element or null
	 */
	getElementById: function(id) {return this.getDoc().getElementById(id)},
	
	/**
	 * Shortcut for #getElementById
	 */
	$: function(id) {return this.getElementById(id)},
	
	/**
	  * Returns first "valid" child of given element. It ignores empty textnodes.
	  *
	  * @param {Element} element Target element
	  * @returns {Node} first child node or null
	  */
	getFirstChild: function(element) {
		if(!element) return null;
		
		var nodes = xq.$A(element.childNodes);
		return nodes.find(function(node) {return !this.isEmptyTextNode(node)}.bind(this));
	},
	
	/**
	  * Returns last "valid" child of given element. It ignores empty textnodes and place-holders.
	  *
	  * @param {Element} element Target element
	  * @returns {Node} last child node or null
	  */
	getLastChild: function(element) {throw "Not implemented"},

	getNextSibling: function(node) {
		while(node = node.nextSibling) {
			if(node.nodeType !== 3 || !node.nodeValue.isBlank()) break;
		}
		return node;
	},

	getBottommostFirstChild: function(node) {
		while(node.firstChild && node.nodeType === 1) node = node.firstChild;
		return node;
	},
	
	getBottommostLastChild: function(node) {
		while(node.lastChild && node.nodeType === 1) node = node.lastChild;
		return node;
	},
	
	/** @private */
	_getCssValue: function(str, defaultUnit) {
		if(!str || str.length === 0) return {value:0, unit:defaultUnit};
		
		var tokens = str.match(/(\d+)(.*)/);
		return {
			value:parseInt(tokens[1]),
			unit:tokens[2] || defaultUnit
		};
	}
});