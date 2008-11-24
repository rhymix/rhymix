/**
 * @requires Xquared.js
 */
xq.DomTree = xq.Class(/** @lends xq.DomTree.prototype */{
	/**
	 * Provides various tree operations.
	 *
	 * TODO: Add specs
	 *
	 * @constructs
	 */
	initialize: function() {
		xq.addToFinalizeQueue(this);
		
		this._blockTags = ["DIV", "DD", "LI", "ADDRESS", "CAPTION", "DT", "H1", "H2", "H3", "H4", "H5", "H6", "HR", "P", "BODY", "BLOCKQUOTE", "PRE", "PARAM", "DL", "OL", "UL", "TABLE", "THEAD", "TBODY", "TR", "TH", "TD"];
		this._blockContainerTags = ["DIV", "DD", "LI", "BODY", "BLOCKQUOTE", "UL", "OL", "DL", "TABLE", "THEAD", "TBODY", "TR", "TH", "TD"];
		this._listContainerTags = ["OL", "UL", "DL"];
		this._tableCellTags = ["TH", "TD"];
		this._blockOnlyContainerTags = ["BODY", "BLOCKQUOTE", "UL", "OL", "DL", "TABLE", "THEAD", "TBODY", "TR"];
		this._atomicTags = ["IMG", "OBJECT", "PARAM", "BR", "HR"];
	},
	
	getBlockTags: function() {
		return this._blockTags;
	},
	
	/**
	 * Find common ancestor(parent) and his immediate children(left and right).<br />
	 *<br />
	 * A --- B -+- C -+- D -+- E<br />
	 *          |<br />
	 *          +- F -+- G<br />
	 *<br />
	 * For example:<br />
	 * > findCommonAncestorAndImmediateChildrenOf("E", "G")<br />
	 *<br />
	 * will return<br />
	 *<br />
	 * > {parent:"B", left:"C", right:"F"}
	 */
	findCommonAncestorAndImmediateChildrenOf: function(left, right) {
		if(left.parentNode === right.parentNode) {
			return {
				left:left,
				right:right,
				parent:left.parentNode
			};
		} else {
			var parentsOfLeft = this.collectParentsOf(left, true);
			var parentsOfRight = this.collectParentsOf(right, true);
			var ca = this.getCommonAncestor(parentsOfLeft, parentsOfRight);
	
			var leftAncestor = parentsOfLeft.find(function(node) {return node.parentNode === ca});
			var rightAncestor = parentsOfRight.find(function(node) {return node.parentNode === ca});
			
			return {
				left:leftAncestor,
				right:rightAncestor,
				parent:ca
			};
		}
	},
	
	/**
	 * Find leaves at edge.<br />
	 *<br />
	 * A --- B -+- C -+- D -+- E<br />
	 *          |<br />
	 *          +- F -+- G<br />
	 *<br />
	 * For example:<br />
	 * > getLeavesAtEdge("A")<br />
	 *<br />
	 * will return<br />
	 *<br />
	 * > ["E", "G"]
	 */
	getLeavesAtEdge: function(element) {
		if(!element.hasChildNodes()) return [null, null];
		
		var findLeft = function(el) {
			for (var i = 0; i < el.childNodes.length; i++) {
				if (el.childNodes[i].nodeType === 1 && this.isBlock(el.childNodes[i])) return findLeft(el.childNodes[i]);
			}
			return el;
		}.bind(this);
		
		var findRight=function(el) {
			for (var i = el.childNodes.length; i--;) {
				if (el.childNodes[i].nodeType === 1 && this.isBlock(el.childNodes[i])) return findRight(el.childNodes[i]);
			}
			return el;
		}.bind(this);
		
		var left = findLeft(element);
		var right = findRight(element);
		
		return [left === element ? null : left, right === element ? null : right];
	},
	
	getCommonAncestor: function(parents1, parents2) {
		for(var i = 0; i < parents1.length; i++) {
			for(var j = 0; j < parents2.length; j++) {
				if(parents1[i] === parents2[j]) return parents1[i];
			}
		}
	},
	
	collectParentsOf: function(node, includeSelf, exitCondition) {
		var parents = [];
		if(includeSelf) parents.push(node);
		
		while((node = node.parentNode) && (node.nodeName !== "HTML") && !(typeof exitCondition === "function" && exitCondition(node))) parents.push(node);
		return parents;
	},
	
	isDescendantOf: function(parent, child) {
		if(parent.length > 0) {
			for(var i = 0; i < parent.length; i++) {
				if(this.isDescendantOf(parent[i], child)) return true;
			}
			return false;
		}
		
		if(parent === child) return false;
		
	    while (child = child.parentNode)
	      if (child === parent) return true;
	    return false;
	},
	
	/**
	 * Perform tree walking (foreward)
	 */
	walkForward: function(node) {
		var target = node.firstChild;
		if(target) return target;
		
		// intentional assignment for micro performance turing
		if(target = node.nextSibling) return target;
		
		while(node = node.parentNode) {
			// intentional assignment for micro performance turing
			if(target = node.nextSibling) return target;
		}
		
		return null;
	},
	
	/**
	 * Perform tree walking (backward)
	 */
	walkBackward: function(node) {
		if(node.previousSibling) {
			node = node.previousSibling;
			while(node.hasChildNodes()) {node = node.lastChild;}
			return node;
		}
		
		return node.parentNode;
	},
	
	/**
	 * Perform tree walking (to next siblings)
	 */
	walkNext: function(node) {return node.nextSibling},
	
	/**
	 * Perform tree walking (to next siblings)
	 */
	walkPrev: function(node) {return node.previousSibling},
	
	/**
	 * Returns true if target is followed by start
	 */
	checkTargetForward: function(start, target) {
		return this._check(start, this.walkForward, target);
	},

	/**
	 * Returns true if start is followed by target
	 */
	checkTargetBackward: function(start, target) {
		return this._check(start, this.walkBackward, target);
	},
	
	findForward: function(start, condition, exitCondition) {
		return this._find(start, this.walkForward, condition, exitCondition);
	},
	
	findBackward: function(start, condition, exitCondition) {
		return this._find(start, this.walkBackward, condition, exitCondition);
	},
	
	_check: function(start, direction, target) {
		if(start === target) return false;
		
		while(start = direction(start)) {
			if(start === target) return true;
		}
		return false;
	},
	
	_find: function(start, direction, condition, exitCondition) {
		while(start = direction(start)) {
			if(exitCondition && exitCondition(start)) return null;
			if(condition(start)) return start;
		}
		return null;
	},

	/**
	 * Walks Forward through DOM tree from start to end, and collects all nodes that matches with a filter.
	 * If no filter provided, it just collects all nodes.
	 *
	 * @param {Element} start Starting element.
	 * @param {Element} end Ending element.
	 * @param {Function} filter A filter function.
	 */
	collectNodesBetween: function(start, end, filter) {
		if(start === end) return [start, end].findAll(filter || function() {return true});
		
		var nodes = this.collectForward(start, function(node) {return node === end}, filter);
		if(
			start !== end &&
			typeof filter === "function" &&
			filter(end)
		) nodes.push(end);
		
		return nodes;
	},

	collectForward: function(start, exitCondition, filter) {
		return this.collect(start, this.walkForward, exitCondition, filter);
	},
	
	collectBackward: function(start, exitCondition, filter) {
		return this.collect(start, this.walkBackward, exitCondition, filter);
	},
	
	collectNext: function(start, exitCondition, filter) {
		return this.collect(start, this.walkNext, exitCondition, filter);
	},
	
	collectPrev: function(start, exitCondition, filter) {
		return this.collect(start, this.walkPrev, exitCondition, filter);
	},
	
	collect: function(start, next, exitCondition, filter) {
		var nodes = [start];

		while(true) {
			start = next(start);
			if(
				(start === null) ||
				(typeof exitCondition === "function" && exitCondition(start))
			) break;
			
			nodes.push(start);
		}

		return (typeof filter === "function") ? nodes.findAll(filter) : nodes;
	},

	hasBlocks: function(element) {
		var nodes = element.childNodes;
		for(var i = 0; i < nodes.length; i++) {
			if(this.isBlock(nodes[i])) return true;
		}
		return false;
	},
	
	hasMixedContents: function(element) {
		if(!this.isBlock(element)) return false;
		if(!this.isBlockContainer(element)) return false;
		
		var hasTextOrInline = false;
		var hasBlock = false;
		for(var i = 0; i < element.childNodes.length; i++) {
			var node = element.childNodes[i];
			if(!hasTextOrInline && this.isTextOrInlineNode(node)) hasTextOrInline = true;
			if(!hasBlock && this.isBlock(node)) hasBlock = true;
			
			if(hasTextOrInline && hasBlock) break;
		}
		if(!hasTextOrInline || !hasBlock) return false;
		
		return true;
	},
	
	isBlockOnlyContainer: function(element) {
		if(!element) return false;
		return this._blockOnlyContainerTags.indexOf(typeof element === 'string' ? element : element.nodeName) !== -1;
	},
	
	isTableCell: function(element) {
		if(!element) return false;
		return this._tableCellTags.indexOf(typeof element === 'string' ? element : element.nodeName) !== -1;
	},
	
	isBlockContainer: function(element) {
		if(!element) return false;
		return this._blockContainerTags.indexOf(typeof element === 'string' ? element : element.nodeName) !== -1;
	},
	
	isHeading: function(element) {
		if(!element) return false;
		return (typeof element === 'string' ? element : element.nodeName).match(/H\d/);
	},
	
	isBlock: function(element) {
		if(!element) return false;
		return this._blockTags.indexOf(typeof element === 'string' ? element : element.nodeName) !== -1;
	},
	
	isAtomic: function(element) {
		if(!element) return false;
		return this._atomicTags.indexOf(typeof element === 'string' ? element : element.nodeName) !== -1;
	},
	
	isListContainer: function(element) {
		if(!element) return false;
		return this._listContainerTags.indexOf(typeof element === 'string' ? element : element.nodeName) !== -1;
	},
	
	isTextOrInlineNode: function(node) {
		return node && (node.nodeType === 3 || !this.isBlock(node));
	}
});