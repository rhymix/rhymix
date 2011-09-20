/*
 * XEED - XpressEngine WYSIWYG Editor
 * @author nhn (developers@xpressengine.com)
 */
(function($){

var d = document, fn, dp, dc,
    rx_command = /(?:^| )@(\w+)(?: |$)/,
	rx_block   = /^(H[1-6R]|P|DIV|ADDRESS|PRE|FORM|T(ABLE|BODY|HEAD|FOOT|H|R|D)|LI|OL|UL|CAPTION|BLOCKQUOTE|CENTER|DL|DT|DD|DIR|FIELDSET|NOSCRIPT|MENU|ISINDEX|SAMP)$/i,
    invisibleCh = '\uFEFF',
	_nt_ = 'nodeType',
	_nn_ = 'nodeName',
    _ps_ = 'previousSibling',
	_ns_ = 'nextSibling',
	_pn_ = 'parentNode',
	_cn_ = 'childNodes',
	_ca_ = 'commonAncestorContainer',
	_sc_ = 'startContainer',
	_so_ = 'startOffset',
	_ec_ = 'endContainer',
	_eo_ = 'endOffset',
	_osc_ = 'oStartContainer',
	_iso_ = 'iStartOffset',
	_oec_ = 'oEndContainer',
	_ieo_ = 'iEndOffset',
	_ol_  = 'offsetLeft',
	_xr_  = '_xeed_root',
	rx_root = new RegExp('(?:^|\\s)'+_xr_+'(?:\\s|$)'),
    Xeed, XHTMLT, Simple, Block, Font, Filter, EditMode, LineBreak, Resize, UndoRedo, Table, URL, AutoSave, FindReplace, Clear, DOMFix;

Xeed = xe.createApp('Xeed', {
	$textarea : null,
	$richedit : null,
	$toolbar  : null,
	$root     : null,
	_options  : null,
	last_node : null,

	/**
	 * @brief constructor
	 */
	init : function(obj, options) {
		var self=this, $obj=$(obj), $text, $edit, opts, content, plugins, i, c, tmpId;

		// Options
		opts = $.extend({
			height : 200,
			minHeight : 400
		}, options);
		this._options = opts;

		// min height
		if (opts.minHeight > opts.height) opts.minHeight = opts.height;

		//
		if ($obj.is('textarea')) {
			$text = $obj;
		} else {
			$text = $obj.before('<textarea>').hide().prev().val($obj.html());
		}

		// Convert to wysiwyg editor
		this.$textarea = $text;
		this.$root     = $text.parent();
		this.$richedit = this.$root.find('div.edit>div.xdcs:first');
		this.$richedit
			.attr('contentEditable', true)
			.attr('id', tmpId = this.getOnetimeId())
			.addClass(_xr_)
			.after(this.$textarea)
			.focus(function(event){ self.cast('ON_FOCUS', [event]) })
			.mousedown(function(event){ self.cast('ON_MOUSEDOWN', [event]) })
			.keydown(function(event){ self.cast('ON_KEYDOWN', [event]) });
		this.$root.find('>div.xd').show();
		this.$toolbar  = this.$root.find('>div.xd>div.tool');
		this.$toolbar
			.find('>a:first').attr('href', '#'+tmpId).end();

		// legacy mode for firefox
		try { d.execCommand('styleWithCSS', false, false); } catch(e){};

		// hide all layer
		$(document).mousedown(function(event) {
			var $target = $(event.target);

			if ($target.is('input,button')) return;

			self.cast('HIDE_ALL_LAYER');

			if ($target.is('div.edit')) {
				self.cast('SET_FOCUS');
				return false;
			}
		});

		// focusing workaround
		if ($.browser.opera || $.browser.msie) {
			this.$richedit.parent()
				.mouseover(function(){
					var $box = $(this), $rich = self.$richedit;

					if ($rich.outerHeight() < $box.height()) {
						$rich
							.height($box.height()-parseInt($rich.css('padding-top'))-parseInt($rich.css('padding-bottom')))
							.mouseout(function(){ $rich.css('height', '') })
							.focus(function(){ $rich.css('height', '') });
					}
				});
		}

		// button hover event
		this.$toolbar
		    .delegate('li.ti>button', 'mouseover', function(){ $(this[_pn_]).addClass('hover') })
		    .delegate('li.ti>button', 'mouseout', function(){ $(this[_pn_]).removeClass('hover') });
			
		// window resize
		$(window).resize(bind(this,this._onresize)).load(bind(this,this._onresize));
		this.$toolbar.find('button.mo').click(function(){ $(this).toggleClass('mopen').nextAll('ul._overflow').toggle(); });

		// register plugins
		this.registerPlugin(new Hotkey); // Hotkey must be the first
		this.registerPlugin(new EditMode);
		this.registerPlugin(new Simple);
		this.registerPlugin(new Block);
		this.registerPlugin(new Font);
		this.registerPlugin(new Filter);
		this.registerPlugin(new LineBreak);
		this.registerPlugin(new Resize);
		this.registerPlugin(new UndoRedo);
		this.registerPlugin(new Table);
		this.registerPlugin(new URL);
		this.registerPlugin(new FileUpload);
		this.registerPlugin(new Clear);

		// set content
		if (!$.browser.msie && !$text.val()) $text.val('<br />');
		setTimeout(function(){ self.cast('SET_CONTENT', [$text.val()]) }, 0);
	},

	/**
	 * @brief Get option
	 * @param key String option key
	 * @syntax opt = oApp.getOption('keyName')
	 * @return Variant
	 */
	getOption : function(key) {
		var v = this._options[key];
		if (is_def(v)) return v;
	},

	/**
	 * @brief Set option
	 * @param key String option key
	 * @param key Variant option value
	 * @syntax oApp.setOption('keyName', 'value')
	 */
	setOption : function(key, val) {
		try { this._options[key] = val; }catch(e){};
	},

	/**
	 * @brief Set default option
	 * @param key String option key
	 * @param val Default value
	 * @syntax oApp.setDefault('key', 'defaultValue');
	 *         oApp.setDefault({'key1':'defaultValue1', 'key2':'defaultValue2', ...});
	 */
	setDefault : function(key, val) {
		var self = this;

		if ($.isPlainObject(key)) {
			$.each(key, function(k,v){ self.setDefault(k,v) });
		} else if (is_str(key)) {
			if (!is_def(this._options[key])) this._options[key] = val;
		}
	},

	/**
	 * @brief  Get current selection
	 * @syntax oSelection = oApp.getSelection()
	 * @return HuskyRange
	 * @see    getEmptySelection
	 */
	getSelection : function() {
		var range = this.getEmptySelection(), xr = '.'+_xr_, $p;

		// this may throw an exception if the selected is area is not yet shown
		try{ range.setFromSelection(); }catch(e){ return null; };
		//range.setFromSelection();

		$p = $(range[_ca_]);
		if ( $p.is(xr) || $p.parents(xr+':first').length ) return range;
	},

	/**
	 * @brief  Get empty selection
	 * @syntax oSelection = oApp.getEmptySelection()
	 * @return HuskyRange
	 * @see    getSelection
	 */
	getEmptySelection : function() {
		return new HuskyRange();
	},

	checkCurrentNode : function(sel) {
		var _sel = sel || this.getSelection(), sc;

		if (!_sel) {
			this.last_node = null;
			return false;
		}

		sc = _sel[_sc_];
		if (sc !== this.last_node) {
			this.last_node = sc;
			this.cast('ON_CHANGE_NODE', [sc]);
		}
	},

	getOnetimeId : function() {
		return 'xeed' + Math.ceil(Math.random() * 1000) + (''+(new Date).getTime()).substr(8);
	},

	/**
	 * @brief Fake interface for editor_common.js
	 */
	getFrame : function(seq) {
		var self = this;

		return {
			editor_sequence : seq,
			getSelectedHTML : function(){},
			setFocus    : function(){ self.cast('SET_FOCUS');  },
			replaceHTML : function(html){ self.cast('PASTE_HTML', [html]) }
		};
	},
	
	_onresize : function() {
		var $tb = this.$toolbar, $t1 = $tb.find('>.t1'), $t2 = $tb.find('>.t2'), $t1_mo, $t2_mo, $t1_ul, base_top;

		if ($t1.length && ($t1_ul=$t1.find('>ul')).length) {
			base_top = $t1_ul.removeClass('_overflow').show().get(0).offsetTop;
			$t1_mo   = $t1.find('>button.mo').hide();
			$.each($t1.find('>ul').get().reverse(), function(){
				if (this.offsetTop > base_top) {
					$(this).addClass('_overflow').hide();
					$t1_mo.show();
				}
			});
		}

		base_top = $t2.find('>ul').removeClass('_overflow').show().get(0).offsetTop;
		$t2_mo = $t2.find('>button.mo').hide();
		$.each($t2.find('>ul').get().reverse(), function(){
			if (this.offsetTop > base_top) {
				$(this).addClass('_overflow').hide();
				$t2_mo.show();
			}
		});
	},

	/**
	 * @brief Get an HTML code of the content
	 * @param Reserved slot for html code
	 * @param Force getting content from rich editor
	 * @return html string
	 */
	API_GET_CONTENT : function(sender, params) {
		return params[0];
	},

	API_BEFORE_GET_CONTENT : function(sender, params) {
		var force = params[1];

		if (force || this.$richedit.is(':visible')) {
			params[0] = this.$richedit.html();
		} else {
			params[0] = this.$textarea.val();
		}
	},

	/**
	 * @brief Set rich content from  HTML code
	 * @param html The HTML code string
	 *
	 * Since WebKit has a bug related to content-editable attribute,
	 * toggle action doesn't work for some inline style such as bold, itailc and so on.
	 * As a workaround, I used manually appending DOM objects instead of setting html.
	 */
	API_SET_CONTENT : function(sender, params) {
		// If the rich editor is hidden, put the content into the textarea too.
		if (this.$richedit.is(':hidden')) {
			this.$textarea.val(params[0]);
		} else {
			this.$richedit.html(params[0]);
		}
	},

	/**
	 * @brief Paste html code
	 *
	 */
	API_PASTE_HTML : function(sender, params) {
		var html = params[0], sel = this.getSelection();

		if (sel) sel.pasteHTML(html);
	},

	/**
	 * @brief Execute a command
	 * @param command String that specifies the command to execute.
	 * @param ui      When this value is true, display a user interface if the command supports one. (Default : false)
	 * @param value   Optional. Variant that specifies the string, number, or other value to assign.
	 */
	API_EXEC_COMMAND : function(sender, params) {
		var command = params[0], ui = params[1], value = params[2], sel = this.getSelection();

		if (sel) {
			d.execCommand(command, ui, value);
			this.cast('SAVE_UNDO_POINT');
			this.checkCurrentNode(sel);
		}
	},

	/**
	 * @brief Register a command
	 * @param selector A selector to find the component or a DOM object
	 * @param hotkey   Hotkey string
	 * @param command  Command string
	 * @param arg      Array for arguments
	 */
	API_REGISTER_COMMAND : function(sender, params) {
		var self = this, $btn, selector = params[0], hotkey = params[1], cmd = params[2], args = params[3], _sel;

		function fn(){
			if (_sel) {
				try { _sel.select(); } catch(e){ };
			}

			self.cast('HIDE_ALL_LAYER');
			self.cast(cmd, args);
			return false;
		}

		// register hotkey
		if (hotkey) this.cast('REGISTER_HOTKEY', [hotkey, fn]);

		// ui event
		if (selector) {
			($btn = is_str(selector)?this.$toolbar.find(selector):$(selector)).click(fn);

			if ($btn[0] && $.browser.msie) {
				$btn.mousedown(function(){ _sel = self.getSelection(); return false; });
			}
		}
	},

	/**
	 * @brief Unregister a command
	 * @param selector A selector to find the component or a DOM object
	 * @param hotkey   Hotkey string
	 */
	API_UNREGISTER_COMMAND : function(sender, params) {
		var selector = params[0], hotkey = params[1];

		// unregister hotkey
		if (hotkey) this.cast('UNREGISTER_HOTKEY', [hotkey]);

		// unregister ui event
		if (selector) {
			($btn = is_str(selector)?this.$toolbar.find(selector):$(selector)).unbind('click');
		}
	},

	/**
	 * @brief Set state of a command button
	 * @param selector A selector to find the component or a DOM object
	 * @param state    State string such as 'disable', 'active' or 'normal'
	 */
	API_SET_COMMAND_STATE : function(sender, params) {
		var $btn, $li, selector = params[0], state = params[1];

		$btn = is_str(selector)?this.$toolbar.find(selector):$(selector);

		if (!$btn[0]) return;
		if ($.inArray(state, ['disable', 'active']) == -1) state = 'normal';

		$li = $btn.parent('li').removeClass('disable active');

		if (state != 'normal') $li.addClass(state);
	},

	/**
	 * @brief Set focus
	 */
	API_SET_FOCUS : function(sender, params) {
		var self = this, sel = this.getSelection();

		if (sel) return;
		if (!$.browser.msie && !this.$richedit.html()) this.cast('SET_CONTENT', ['<br />']);

		this.$richedit.focus();
	},

	/**
	 * @brief Fire event on keydown
	 * @param event object
	 */
	API_ON_KEYDOWN : function(sender, params) {
		var timer = arguments.callee.timer;

		if (timer) clearTimeout(timer);
		arguments.callee.timer  = setTimeout(bind(this, this.checkCurrentNode), 100);
	},

	/**
	 * @brief Fire event on mousedown
	 * @param event object
	 */
	API_ON_MOUSEDOWN : function(sender, params) {
		setTimeout(bind(this, this.checkCurrentNode), 0);
	},

	/**
	 * @brief Fire event on focus
	 * @param event object
	 */
	API_ON_FOCUS : function(sender, params) {
		setTimeout(bind(this, this.checkCurrentNode), 0);
	},

	/**
	 * @brief Hide all layer
	 * @param Layer to skip hiding
	 */
	// API_HIDE_ALL_LAYER : function(sender, params) { },

	/**
	 * @brief Fire event on change current node
	 * @oaram Current selection
	 */
	// API_ON_CHANGE_NODE : function(sender, params) { },

	end : 0 // just mark end point of the definition
});

/**
 * {{{ Simple Command plugin
 * This plugin handle simple commands such as bold, italic and underline
 */
Simple = xe.createPlugin('SimpleCommand', {
	$btns : {},
	cmd   : {
		bold      : ['bd', 'ctrl+b'],
		underline : ['ue', 'ctrl+u'],
		italic    : ['ic', 'ctrl+i'],
		strikethrough : ['se'],
		superscript   : ['sp'],
		subscript     : ['sb'],
		justifyleft   : ['al'],
		justifycenter : ['ac'],
		justifyright  : ['ar'],
		justifyfull   : ['aj'],
		insertorderedlist : ['ol'],
		insertunorderedlist : ['ul']
	},

	// constructor
	init : function() {
		this.$btns = {};
	},

	// on activate
	activate : function() {
		var self = this, app, $tb, classes = [];

		// skip this code if there is no toolbar
		if (!(app=this.oApp) || !($tb=app.$toolbar) || !$tb.length) return;

		// register commands
		$.each(this.cmd, function(cmd) {
			var $btn = $tb.find('button.'+this[0]);

			if ($btn[0]) {
				self.$btns[cmd] = $btn;
				self.cast('REGISTER_COMMAND', [$btn[0], this[1], 'EXEC_COMMAND', [cmd, false, false]]);
			}
		});
	},

	// on deactivate
	deactivate : function() {
		var self = this;

		$.each(this.cmd, function(cmd) {
			if (!self.$btns[cmd]) return;
			self.cast('UNREGISTER_COMMAND', [self.$btns[this[0]], this[1]]);
		});

		// empty button list
		self.$btns = {};
	},

	API_ON_CHANGE_NODE : function(sender, params) {
		var self = this, node = params[0];

		if (!node) {
			$.each(this.$btns, function(cmd, $btn){
				self.cast('SET_COMMAND_STATE', [$btn[0], 'disable']);
			});
			return;
		}

		$.each(this.$btns, function(cmd, $btn){
			var state = 'disable';

			if (node && d.queryCommandEnabled(cmd)) {
				state = d.queryCommandState(cmd)?'active':'normal';
			}

			self.cast('SET_COMMAND_STATE', [$btn[0], state]);
		});
	}
});
/**
 * }}}
 */

/**
 * {{{ Block Command Plugin
 * @brief This plugin handles some block commands such as Heading, Quote, Indent, Outdent, Box and LineHeight.
 */
Block = xe.createPlugin('BlockCommand', {
	$head_btn : null,
	$line_btn : null,
	$head_layer : null,
	$line_layer : null,
	$btns       : {},
	cmd  : {indent:'id',outdent:'od',quote:'qm',box:'bx'},
	init : function() {
		this.$btns = {};
	},
	activate : function() {
		var self = this, app = this.oApp, $tb = app.$toolbar, $li, np = navigator.platform, i, c, lines;

		if (!$tb) return;

		// heading
		this.$head_btn   = $tb.find('li.hx:first>button').mousedown(function(){ self.cast('TOGGLE_HEADING_LAYER'); return false; })
		this.$head_layer = this.$head_btn.next('ul.lr')
			.find('>li>button')
				.hover(
					function(){ $(this[_pn_]).addClass('hover') },
					function(){ $(this[_pn_]).removeClass('hover') }
				)
				.each(function(){
					var $this = $(this), num;
					if (!/(?:^|\s)h([1-7])(?:\s|$)/i.test($this.parent().attr('class'))) return true;

					num = RegExp.$1;
					if (np && !np.indexOf('Mac')) $this.attr('title', 'Ctrl+Command+'+num);
					else $this.attr('title', 'Ctrl+'+num);

					self.cast('REGISTER_COMMAND', [this, 'ctrl+'+num, 'EXEC_HEADING', [num]]);
				})
				.end();

		// line-height
		this.$line_btn   = $tb.find('li.lh:first>button').mousedown(function(){ self.cast('TOGGLE_LINEHEIGHT_LAYER'); return false; });
		this.$line_layer = this.$line_btn.next('ul.lr');
		lines = ($li=this.$line_layer.find('>li').remove()).text().split(';');
		for(i=0, c=lines.length; i < c; i++) {
			this.$line_layer.append( $li.clone(true).find('>button').text(lines[i]).end() );
		}
		this.$line_layer
			.find('>li>button')
				.hover(
					function(){ $(this[_pn_]).addClass('hover') },
					function(){ $(this[_pn_]).removeClass('hover') }
				)
				.click(function(){
					self.cast('EXEC_LINEHEIGHT', [$(this).text()]);
					self.cast('HIDE_LINEHEIGHT_LAYER');
					return false;
				})
				.end();

		// quote, box, indent, outdent
		$.each(this.cmd, function(key,val){
			var $btn = $tb.find('button.'+val);

			if (!$btn[0]) return;

			self.$btns[val] = $btn;
			self.cast('REGISTER_COMMAND', [$btn[0], '', 'EXEC_'+key.toUpperCase()]);
		});
	},
	deactivate : function() {
		var self = this, $tb = this.oApp.$toolbar, $layer = this.$head_layer, sels = [];

		if (!$tb) return;

		// headings
		$tb.find('li.hx:first>button:first').unbind('mousedown');
		if ($layer && $layer.length) {
			$layer.find('>li').unbind();
			this.$head_layer = null;
		}

		// quote, box, indent, outdent
		$.each(this.$btns, function(key){
			self.cast('UNREGISTER_COMMAND', [this[0]]);
		});
	},
	getBlockParents : function() {
		var sel = this.oApp.getSelection(), re = this.oApp.$richedit[0], ret = [], sc, ec, sp, ep, ca, ls, le;

		if (!sel) return;
		
		function lineStopper(node){
			return (is_block(node) || node[_nn_].toLowerCase() == 'br');
		};
		
		function splitFrom(node, prev) {
			var s = prev?_ps_:_ns_, nds, $pn, $cn, del=1;

			if (rx_root.test(node[_pn_].className) && !is_block(node)) {
				nds = siblings(node, 1);
				nds.push(node);
				nds = nds.concat(siblings(node, 0));

				$(nds[0]).before($cn=$('<div />'));
				$cn.append(nds);
				$cn = null;
			}

			while(node !== ca && !rx_root(node[_pn_].className)) {
				if (node[s]) {
					if (del) del = 0;
					nds = siblings(node, prev, function(){ return 0 });

					$pn = $(node[_pn_]);
					$cn = $pn.clone().empty();

					$pn[prev?'before':'after']($cn);
					$cn.append(nds);
				}

				node = node[_pn_];
			}

			if ($cn && del) $cn.remove();
		};

		ca = get_block_parent(sel[_ca_]);
		sc = sel[_sc_]; sp = get_block_parent(sc); sc = get_child(sc, sp);
		ec = sel[_ec_]; ep = get_block_parent(ec); ec = get_child(ec, ep);

		// find line start
		ls = siblings(sc, 1, lineStopper).shift() || sc;
		if (ls[_ps_] && ls[_ps_][_nn_].toLowerCase() == 'br') $(ls[_ps_]).remove();
		splitFrom(ls, 1);

		// find line end
		le = siblings(ec, 0, lineStopper).pop() || ec;
		if (le[_ns_] && le[_ns_][_nn_].toLowerCase() == 'br') $(le[_ns_]).remove();
		splitFrom(le, 0);

		sc = get_block_parent(get_child(ls, ca));
		ec = get_block_parent(get_child(le, ca));
		
		ret = [sc];

		if (sc !== ec) {
			ret = ret.concat(siblings(sc, 0, function(nd){ return (nd !== ec) }));
		}

		return ret;
	
	},
	match : function(node, selector) {
		var $node = $(node);

		if ($node.is(selector)) {
			return $node[0];
		} else {
			$node = $node.parentsUntil('.'+_xr_).filter(selector);
			if ($node.length) return $node[0];
		}
	},
	unwrap : function(node) {
		var $node = $(node), $wrapper;

		$node.contents().each(function(){
			if(rx_block.test(this[_nn_])) {
				$wrapper = 0;
			} else {
				if (!$wrapper) $wrapper = $(this).wrap('<p>').parent();
				else $wrapper.append(this);
			}
		});
		$node.children().unwrap();
	},
	fireChangeNode : function(sel) {
		var self = this, _sel = sel || this.oApp.getSelection();

		setTimeout(function(){
			if (_sel && _sel[_sc_]) self.cast('ON_CHANGE_NODE', [_sel[_sc_]])
		}, 0);
	},
	/**
	 * @brief Quotes selection
	 */
	API_EXEC_QUOTE : function(sender, params) {
		var self = this, sel = this.oApp.getSelection(), start, end, ancestor, match, $bq, _bq_ = 'blockquote.bq';

		if (!sel) return false;

		start = sel.getStartNode();
		match = this.match(start, _bq_);

		if (match) {
			this.unwrap(match);
			sel.select();
			this.fireChangeNode(sel);
			return;
		}

		// get block-level common ancestor
		ancestor = sel.commonAncestorContainer;
		start    = get_valid_parent(get_block_parent(get_child(start, ancestor)), 'blockquote');
		end      = sel.collapsed?start:get_child(sel.getEndNode(), start[_pn_]);

		// remove quote blocks in a selection
		$(sel.getNodes()).filter(_bq_).each(function(){ self.unwrap(this) });

		// wrap nodes with new <blockquote>
		if (start == end && /^(div|p)$/i.test(start[_nn_])) {
			$bq = $(start).wrapInner('<blockquote class="bq" />').children().unwrap();
		} else {
			$bq = $(start).wrap('<blockquote class="bq" />').parent();
			if (start != end) {
				$bq.append($(end).prevUntil(_bq_).toArray().reverse()).append(end);
			}
		}

		sel.select();

		// save undo point
		this.cast('SAVE_UNDO_POINT');

		this.fireChangeNode(sel);
	},
	/**
	 * @brief
	 */
	API_EXEC_BOX : function(sender, params) {
		var self = this, sel = this.oApp.getSelection(), start, end, ancestor, match, $bx, _bx_ = 'div.bx';

		if (!sel) return false;

		start = sel.getStartNode();
		match = this.match(start, _bx_);

		if (match) {
			this.unwrap(match);
			sel.select();
			this.fireChangeNode(sel);
			return;
		}

		// get block-level common ancestor
		ancestor = sel.commonAncestorContainer;
		start    = get_valid_parent(get_block_parent(get_child(start, ancestor)), 'div');
		end      = sel.collapsed?start:get_child(sel.getEndNode(), start[_pn_]);

		// remove box in a selection
		$(sel.getNodes()).filter(_bx_).each(function(){ self.unwrap(this) });

		// wrap nodes with new <div>
		if (start == end && /^(div|p)$/i.test(start[_nn_])) {
			$bq = $(start).wrapInner('<div class="bx" />').children().unwrap();
		} else {
			$bq = $(start).wrap('<div class="bx" />').parent();
			if (start != end) $bq.append($(end).prevUntil(_bx_).toArray().reverse()).append(end);
		}

		sel.select();

		// save undo point
		this.cast('SAVE_UNDO_POINT');

		this.fireChangeNode(sel);
	},
	API_EXEC_INDENT : function(sender, params) {
		var sel = this.oApp.getSelection(), parents = this.getBlockParents();

		$(parents).each(function(){
			var $this = $(this), left = parseInt($this.css('margin-left'), 10);

			left = isNaN(left)?30:left+30;

			$this.css('margin-left', left+'px');
		});

		// save undo point
		this.cast('SAVE_UNDO_POINT');

		this.fireChangeNode();
		try { sel.select() } catch(e){};
	},
	API_EXEC_OUTDENT : function(sender, params) {
		var sel = this.oApp.getSelection(), parents = this.getBlockParents();

		$(parents).each(function(){
			var $this = $(this), left = parseInt($this.css('margin-left'), 10);

			left = Math.max(isNaN(left)?0:left-30, 0);
			left = left?left+'px':'';

			$this.css('margin-left', left);

			if (!$this.attr('style')) $this.removeAttr('style');
		});

		// save undo point
		this.cast('SAVE_UNDO_POINT');

		this.fireChangeNode();
		try { sel.select() } catch(e){};
	},
	/**
	 * @brief
	 * @param Number indicates heading level.
	 */
	API_EXEC_HEADING : function(sender, params) {
		var sel = this.oApp.getSelection(), nodes, n, i, c, $node, first, end;

		if (!sel) return false;

		nodes = this.getBlockParents();
		n     = parseInt(params[0], 10);

		for(i=0,c=nodes.length; i<c; i++) {
			$node = $(nodes[i]).wrapInner((n&&n<7)?'<h'+n+'>':'<p>');

			if (!$node.is('td,th,li')) $node = $node.children(0).unwrap();
			if (!first) first = $node[0];
		}

		end = $node[0];
		if (first == end) {
			sel.selectNode(first);
		} else {
			sel.setStartBefore(first);
			sel.setEndAfter(end);
		}

		sel.select();

		// save undo point
		this.cast('SAVE_UNDO_POINT');

		this.fireChangeNode(sel);
	},
	/**
	 * @brief Shows the heading options layer
	 */
	API_SHOW_HEADING_LAYER : function(sender, params) {
		var $layer = this.$head_layer;

		if (!$layer || $layer.hasClass('open')) return;

		$layer.addClass('open').parent('li').addClass('active');

		this.cast('HIDE_ALL_LAYER', [$layer[0]]);
	},
	/**
	 * @brief Hides the heading options layer
	 */
	API_HIDE_HEADING_LAYER : function(sender, params) {
		var $layer = this.$head_layer;

		if (!$layer || !$layer.hasClass('open')) return;

		$layer.removeClass('open').parent('li').removeClass('active');
	},
	/**
	 * @brief Toggle the haeding options layer
	 */
	API_TOGGLE_HEADING_LAYER : function(sender, params) {
		if (!this.$head_layer) return;
		if (this.$head_layer.hasClass('open')) {
			this.cast('HIDE_HEADING_LAYER');
		} else {
			this.cast('SHOW_HEADING_LAYER');
		}
	},
	API_EXEC_LINEHEIGHT : function(sender, params) {
		var sel = this.oApp.getSelection(), nodes;

		if (!sel) return;

		nodes = this.getBlockParents();
		$(nodes).css('line-height', params[0]);

		// save undo point
		this.cast('SAVE_UNDO_POINT');

		this.fireChangeNode(sel);
	},
	API_SHOW_LINEHEIGHT_LAYER : function(sender, params) {
		var $layer = this.$line_layer;

		if (!$layer || $layer.hasClass('open')) return;

		$layer.addClass('open').parent('li').addClass('active');

		this.cast('HIDE_ALL_LAYER', [$layer[0]]);
	},
	API_HIDE_LINEHEIGHT_LAYER : function(sender, params) {
		var $layer = this.$line_layer;

		if (!$layer || !$layer.hasClass('open')) return;

		$layer.removeClass('open').parent('li').removeClass('active');
	},
	API_TOGGLE_LINEHEIGHT_LAYER : function(sender, params) {
		if (!this.$line_layer) return;
		if (this.$line_layer.hasClass('open')) {
			this.cast('HIDE_LINEHEIGHT_LAYER');
		} else {
			this.cast('SHOW_LINEHEIGHT_LAYER');
		}
	},
	/**
	 * @brief Hides All layer
	 */
	API_HIDE_ALL_LAYER : function(sender, params) {
		if (this.$head_layer && this.$head_layer[0] != params[0]) this.cast('HIDE_HEADING_LAYER');
		if (this.$line_layer && this.$line_layer[0] != params[0]) this.cast('HIDE_LINEHEIGHT_LAYER');
	},
	API_ON_CHANGE_NODE : function(sender, params) {
		var self=this, node = params[0], state = {}, $nodes, $node, i, ml;

		if (!node) {
			$.each(this.$btns, function(key){
				self.cast('SET_COMMAND_STATE', [this[0], 'disable']);
			});
			return;
		}

		state.qm = state.bx = state.id = 'normal';
		state.od = 'disable';

		$nodes = $(node).parentsUntil('.'+_xr_).andSelf();

		for(i = $nodes.length-1; i > -1  ; i--) {
			if (!is_block($nodes[i])) continue;

			$node = $nodes.eq(i);

			if ($node.is('blockquote.bq')) state.qm = 'active';
			else if ($node.is('div.bx')) state.bx = 'active';

			if (state.od == 'disable' && (ml = $node.css('margin-left')) && !isNaN(ml=parseInt(ml)) && ml > 0) state.od = 'normal';
		}

		$.each(this.$btns, function(key) {
			self.cast('SET_COMMAND_STATE', [this[0], state[key]]);
		});
	}
});
/**
 * }}}
 */

/**
 * {{{ Font Plugin
 * @brief Set font name, size and color
 */
Font = xe.createPlugin('Font', {
	_fn       : {},
	rx_color  : /^(#([0-9a-f]{3}|[0-9a-f]{6})|rgb\( *\d{1,3} *, *\d{1,3} *, *\d{1,3} *\))$/i,
	selection : null, // preserved selection
	$ff_layer : null, // font famliy layer
	$fs_layer : null, // font size layer
	$fc_layer : null, // font color layer
	$bc_layer : null, // background color layer
	$ff_btn   : null,
	$fs_btn   : null,
	$fc_btn   : null,
	$bc_btn   : null,

	init : function() {
		var self = this;

		this._fn = {
			ff : function(){ self.cast('TOGGLE_FONTFAMILY_LAYER'); return false; },
			fs : function(){ self.cast('TOGGLE_FONTSIZE_LAYER'); return false; },
			hover : function(){ $(this).parent().addClass('hover'); return false; },
			out   : function(){ $(this).parent().removeClass('hover'); return false; }
		};
	},
	activate : function() {
		var self = this, $tb = this.oApp.$toolbar, $tmp, $clone, $pv, $col, $bgcol, col, bgcol, fs, i, c, _bc_ = 'background-color';

		// buttons
		this.$ff_btn = $tb.find('li.ff > button:first').mousedown(this._fn.ff);
		this.$fs_btn = $tb.find('li.fs > button:first').mousedown(this._fn.fs);
		this.$fc_btn = $tb.find('li.cr.fc > button')
			.eq(0).mousedown(function(){ self.cast('EXEC_FONTCOLOR', [$(this).css('background-color')]); return false; }).end()
			.eq(1).mousedown(function(){ self.cast('TOGGLE_FONTCOLOR_LAYER'); return false; }).end();
		this.$bc_btn = $tb.find('li.cr.bc > button')
			.eq(0).mousedown(function(){ self.cast('EXEC_FONTBGCOLOR', [$(this).css('background-color')]); return false; }).end()
			.eq(1).mousedown(function(){ self.cast('TOGGLE_BGCOLOR_LAYER'); return false; }).end();

		// layers
		this.$ff_layer = this.$ff_btn.next('.lr');
		this.$fs_layer = this.$fs_btn.next('.lr');
		this.$fc_layer = this.$fc_btn.next('.lr').mousedown(function(event){ event.stopPropagation() });
		this.$bc_layer = this.$bc_btn.next('.lr').mousedown(function(event){ event.stopPropagation() });

		// font-family items
		this.$ff_layer.find('button')
			.hover(this._fn.hover, this._fn.out)
			.click(function(){
				self.cast('EXEC_FONTFAMILY', [$(this).css('font-family')]);
				self.cast('HIDE_FONTFAMILY_LAYER');
				return false;
			});

		// font-size items
		$tmp = this.$fs_layer.find('>li').remove();
		fs   = $tmp.text().split(';');
		for(i=0, c=fs.length; i < c; i++) {
			this.$fs_layer.append( $tmp.clone(true).find('>button').css('font-size', fs[i]).text(fs[i]).end() );
		}
		this.$fs_layer.find('button')
			.hover(this._fn.hover, this._fn.out)
			.click(function(){
				self.cast('EXEC_FONTSIZE', [$(this).css('font-size')]);
				self.cast('HIDE_FONTSIZE_LAYER');
				return false;
			});

		// color items
		$tb.find('li.cr')
			.find('ul.ct,ul.cx')
				.each(function(){

					var $this = $(this), $li = $this.find('>li').remove(), $clone_li, $span, $btn, colors,i,c,types;

					colors = $li.text().split(';');
					for(i=0,c=colors.length; i < c; i++) {
						types = $.trim(colors[i]).split(':');
						$clone_li = $li.clone(true);
						$btn  = $clone_li.find('>button');
						$span = $btn.find('>span');

						(($span.length)?$span:$btn).text('#'+types[0]);

						$btn.css('backgroundColor', '#'+types[0]);
						if (types[1]) $btn.css('color', '#'+types[1]);

						$this.append($clone_li);
					}
				})
				.end()
			.filter('.fc').find('li > button')
				.mousedown(function(){
					self.cast('EXEC_FONTCOLOR', [$(this).css('background-color'), true]);
					self.cast('HIDE_FONTCOLOR_LAYER');
					return false;
				})
				.end().end()
			.filter('.bc').find('li > button')
				.mousedown(function(){
					self.cast('EXEC_FONTBGCOLOR', [$(this).css('background-color'), true]);
					self.cast('HIDE_BGCOLOR_LAYER');
					return false;
				});
	},
	deactivate : function() {
		this.$ff_btn.unbind('mousedown');
		this.$fs_btn.unbind('mousedown');
		this.$fc_btn.unbind('mousedown');
		this.$bc_btn.unbind('mousedown');

		this.$ff_layer.find('button').unbind();
		this.$fs_layer.find('button').unbind();
		this.$fc_layer.find('button').unbind();
		this.$bc_layer.find('button').unbind();
	},
	showLayer : function($layer) {
		if (!$layer || $layer.hasClass('open')) return;

		$layer.addClass('open').parent('li').addClass('active');

		this.cast('HIDE_ALL_LAYER', [$layer[0]]);
	},
	hideLayer : function($layer) {
		if (!$layer || !$layer.hasClass('open')) return;

		$layer.removeClass('open').parent('li').removeClass('active');
	},
	toggleLayer : function($layer, api) {
		if (!$layer) return;
		if ($layer.hasClass('open')) {
			this.cast('HIDE_'+api+'_LAYER');
		} else {
			this.cast('SHOW_'+api+'_LAYER');
		}
	},
	toHex : function(col) {
		var regNoSharp, regRGB;

		regNoSharp = /^([0-9A-F]{3}|[0-9A-F]{6})$/i;
		regRGB     = /^rgb\s*\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)\s*\)$/i;

		function fixed(num, count) {
			var str = num + '';

			while(str.length < count) str = '0' + str;

			return str;
		};

		if (regNoSharp.test(col)) {
			col = '#'+col;
		} else if (regRGB.test(col)) {
			col = '#'+fixed((+RegExp.$1).toString(16),2)+fixed((+RegExp.$2).toString(16),2)+fixed((+RegExp.$3).toString(16),2);
		}

		return col;
	},
	/**
	 * @brief Set font size
	 * @param Number indicates font size in pixel
	 */
	API_EXEC_FONTSIZE : function(sender, params) {
		if(!params[0]) return;

		this.cast('EXEC_FONTSTYLE', [{fontSize:params[0]}]);

		// save undo point
		this.cast('SAVE_UNDO_POINT');
	},
	/**
	 * @brief Set font family
	 * @param String indicates font family
	 */
	API_EXEC_FONTFAMILY : function(sender, params) {
		if(!params[0]) return;

		this.cast('EXEC_FONTSTYLE', [{fontFamily:params[0]}]);

		// save undo point
		this.cast('SAVE_UNDO_POINT');
	},
	/**
	 * @brief Set font color
	 * @param String indicates color
	 * @param Bool
	 */
	API_EXEC_FONTCOLOR : function(sender, params) {
		if(!params[0]) return;

		this.cast('EXEC_FONTSTYLE', [{color:params[0]}]);
		if(params[1]) this.$fc_btn.eq(0).css('background-color', params[0]);

		// save undo point
		this.cast('SAVE_UNDO_POINT');
	},
	/**
	 * @breif Set font background color
	 * @param String indicates background color
	 * @param Bool
	 */
	API_EXEC_FONTBGCOLOR : function(sender, params) {
		if(!params[0]) return;

		this.cast('EXEC_FONTSTYLE', [{backgroundColor:params[0]}]);
		if(params[1]) this.$bc_btn.eq(0).css('background-color', params[0]);

		// save undo point
		this.cast('SAVE_UNDO_POINT');
	},
	/**
	 * @brief Set font style
	 * @param styles Styling information
	 */
	API_EXEC_FONTSTYLE : function(sender, params) {
		var sel = this.oApp.getSelection(), styles = params[0], span, val;
		
		if(!sel) return;
		if(sel.collapsed) {
		}

		sel.styleRange(styles);
		this.oApp.$richedit.focus();
		sel.select();

		// save undo point
		this.cast('SAVE_UNDO_POINT');
	},
	/**
	 * @brief Show fontfamily layer
	 */
	API_SHOW_FONTFAMILY_LAYER : function(sender, params) {
		this.showLayer(this.$ff_layer);
	},
	/**
	 * @brief Hide fontfamily layer
	 */
	API_HIDE_FONTFAMILY_LAYER : function(sender, params) {
		this.hideLayer(this.$ff_layer);
	},
	/**
	 * @brief Toggle fontfamily layer
	 */
	API_TOGGLE_FONTFAMILY_LAYER : function(sender, params) {
		this.toggleLayer(this.$ff_layer, 'FONTFAMILY');
	},
	/**
	 * @brief Show fontsize layer
	 */
	API_SHOW_FONTSIZE_LAYER : function(sender, params) {
		this.showLayer(this.$fs_layer);
	},
	/**
	 * @brief Hide fontsize layer
	 */
	API_HIDE_FONTSIZE_LAYER : function(sender, params) {
		this.hideLayer(this.$fs_layer);
	},
	/**
	 * @brief Toggle fontsize layer
	 */
	API_TOGGLE_FONTSIZE_LAYER : function(sender, params) {
		this.toggleLayer(this.$fs_layer, 'FONTSIZE');
	},
	/**
	 * @brief Show fontcolor layer
	 */
	API_SHOW_FONTCOLOR_LAYER : function(sender, params) {
		this.showLayer(this.$fc_layer);
	},
	/**
	 * @brief Hide fontcolor layer
	 */
	API_HIDE_FONTCOLOR_LAYER : function(sender, params) {
		this.hideLayer(this.$fc_layer);
	},
	/**
	 * @brief Toggle fontcolor layer
	 */
	API_TOGGLE_FONTCOLOR_LAYER : function(sender, params) {
		this.toggleLayer(this.$fc_layer, 'FONTCOLOR');
	},
	/**
	 * @brief Show bgcolor layer
	 */
	API_SHOW_BGCOLOR_LAYER : function(sender, params) {
		this.showLayer(this.$bc_layer);
	},
	/**
	 * @brief Hide bgcolor layer
	 */
	API_HIDE_BGCOLOR_LAYER : function(sender, params) {
		this.hideLayer(this.$bc_layer);
	},
	/**
	 * @brief Toggle bgcolor layer
	 */
	API_TOGGLE_BGCOLOR_LAYER : function(sender, params) {
		this.toggleLayer(this.$bc_layer, 'BGCOLOR');
	},
	API_HIDE_ALL_LAYER : function(sender, params) {
		var $ff = this.$ff_layer, $fs = this.$fs_layer, $fc = this.$fc_layer, $bc = this.$bc_layer, except = params[0];

		if ($ff && $ff[0] != except) this.cast('HIDE_FONTFAMILY_LAYER');
		if ($fs && $fs[0] != except) this.cast('HIDE_FONTSIZE_LAYER');
		if ($fc && $fc[0] != except) this.cast('HIDE_FONTCOLOR_LAYER');
		if ($bc && $bc[0] != except) this.cast('HIDE_BGCOLOR_LAYER');
	}
});
/**
 * }}}
 */

/**
 * {{{ LineBreak plugin
 * @brief Insert <br> or wrap the block with <p> when return key is pressed.
 */
LineBreak = xe.createPlugin('LineBreak', {
	_fn : null,
	_br_timer : null,
	_in_br    : false,

	init : function(){
		this._fn = bind(this, this.keydown);
	},
	activate : function() {
		this.oApp.$richedit.keydown(this._fn);

		// If you pres Enter key, <br> will be inserted by default.
		this.oApp.setDefault('force_br', true);
	},
	deactivate : function() {
		this.oApp.$richedit.unbind('keydown', this._fn);
	},
	keydown : function(event) {
		var sel, sc, ec;

		if (event.keyCode != 13 || event.ctrlKey || event.altKey || event.metaKey) {
			if (this._in_br) {
				clearTimeout(this._br_timer);
				this._br_timer = null;
				this._in_br = false;
			}
			return;
		}
		if (!this.oApp.getOption('force_br')) return;
		if (!(sel = this.oApp.getSelection())) return;
		
		event.shiftKey ? this.wrapBlock(sel) : this.insertBR(sel);
		event.keyCode = 0;
		
		this.cast('SAVE_UNDO_POINT');

		return false;
	},
	wrapBlock : function(sel) {
		var self = this, sc, so, eo, nodes, $node, $clone, $bookmark, last, _xb_ = '_xeed_tmp_bookmark';

		// delete contents
		sel.deleteContents();

		// is this node in a list?
		sc = sel[_sc_];
		so = sel[_so_];
		eo = sel[_eo_];

		if (sc[_nt_] == 1 && so > -1 && (sc=sc[_cn_][so]) && sc[_nt_] == 1) {
			$node = $(sc);
		} else {
			if (sc[_nt_] == 3) sc = sc[_pn_];
			$node = $(sc=sel[_sc_]);
		}

		// find block parent
		$node = $node.parentsUntil('.'+_xr_).filter(function(){ return rx_block.test(this[_nn_])  });
		if ($node.length) {
			$node = $node.eq(0);
		} else {
			nodes = $.merge(siblings(sc,1), [sc], siblings(sc));
			nodes[0][_pn_].insertBefore(($node=$('<p />')).get(0), nodes[0]);
			$node.append(nodes);
		}

		// wrap with '<p>' in a table cell
		if ($node.is('td,th')) $node = $node.wrapInner('<p>').children(0);

		// create clone
		if (/^h[1-6]$/i.test($node[0].nodeName)) {
			$clone = $('<p />');
		} else {
			$clone = $node.clone().empty();
		}

		// append clone of this node
		$node.after($clone).append($bookmark=$('<span>').attr('id',_xb_));

		sel.setEndAfter($bookmark[0]);
		$clone.append(sel.extractContents());
		$bookmark.remove();
		$('#'+_xb_).remove();

		if (!$.browser.msie && !$clone[0][_cn_].length) {
			$clone.append(d.createTextNode(invisibleCh));
		}

		sel.setStart($clone[0], 0);
		sel.collapseToStart();
		sel.select();
	},
	insertBR : function(sel) {
		var self = this, $br = $('<br>'), st, block, child, nd, top, $par, $p, $prev_br, $clone, $block, $a, $rich, $ctn;

		// insert Node
		sel.insertNode($br[0]);
		sel.selectNode($br[0]);
		sel.collapseToEnd();

		// Opera web browser can't prevent default keydown event
		// So, you need to workaround it with blur and focus tricks.
		if ($.browser.opera) {
			st = d.documentElement.scrollTop;
			this.oApp.$richedit[0].blur();

			setTimeout(function(){
				var _sel = self.oApp.getEmptySelection();

				self.oApp.$richedit[0].focus();

				if (st != d.documentElement.scrollTop) d.documentElement.scrollTop = st;
				if (!$br[0][_ns_]) $br.after(d.createTextNode(invisibleCh));

				_sel.selectNode($br[0][_ns_]);
				_sel.collapseToStart();
				_sel.select();
			}, 0);
			return;
		}

		if (!$.browser.msie) {
			if (!$br[0][_ns_]) $br.after(d.createTextNode(invisibleCh));
			if ($.browser.safari) {
				// TODO : remove broken character which is displayed only in Safari.
			}
		}

		sel.select();

		/**
		 * twice enter function is currently disabled
		 *
		// <br> timer
		if (!this._in_br) {
			$br.after( $a = $('<a>|</a>') );
			this._scrollIntoView($a[0]);
			$a.remove();

			this._in_br = true;
			this._br_timer = setTimeout(function(){ self._in_br = false; }, 500);
			return;
		}

		if ($br && $br.prev().is('br')) {
			$prev_br = $br.prev();
			block    = get_block_parent($prev_br[0]);

			if (is_ancestor_of(block, $br[0])) {
				while(block != $br[0][_pn_]) {
					$clone = $br.parent().clone().html('');
					$clone.append(siblings($br[0]));
					$br.parent().after($clone).after($br);
				}
			}

			$p = ($block=$(block)).is('li')?$block.clone().empty():$('<p />');
			$block.after($p);
			$p.append(siblings($br[0]));

			$prev_br.remove();
			$br.remove();

			if (!$block.html()) $block.html(invisibleCh);
			$p.prepend(d.createTextNode(invisibleCh));

			sel.setStart($p[0].firstChild, 0);
			sel.setEnd($p[0].firstChild, 1);
			sel.collapseToEnd();
			sel.select();
		}

		this._in_br = false;
		clearTimeout(this._br_timer);
		*/
	},
	_scrollIntoView : function(nd) {
		var $rich, $ctn, top, sctop;

		top = nd.offsetHeight;
		while(!rx_root.test(nd.className||'')) {
			top += nd.offsetTop;
			nd  =  nd.offsetParent;
		}

		// rich editor and container
		$rich = this.oApp.$richedit;
		$ctn  = $rich.parent();

		// scrolltop
		sctop = top - $ctn.height() + parseInt($rich.css('padding-top'));
		if ($ctn[0].scrollTop < sctop) $ctn[0].scrollTop = sctop;
	}
});
/**
 * }}}
 */

/**
 * {{{ Hotkey plugin
 */
Hotkey = xe.createPlugin('Hotkey', {
	_fn  : null,
	_key : {},
	map  : {
		'BACKSPACE BKSP':8,
		'TAB':9,
		'ENTER RETURN':13,
		'ESC ESCAPE':27,
		'SPACE':32,
		// arrows
		'UP':38,
		'DOWN':40,
		'LEFT':37,
		'RIGHT':39,

		'HOME':36,
		'PAGEUP PGUP':33,
		'PAGEDOWN PGDN':34,
		'END':35,
		'INSERT INS':45,
		'DELETE DEL':46,

		// special chars
		'=':187, ', <':188, '- _':189, '. >':190, '/ ?':191, '` ~':192, '{ [':219, '\\ |':220, '} ]':221, '\'\"':222
	},

	// constructor
	init : function() {
		var self = this;

		this._fn  = function(event){ return self.hotkey(event) };
		this._key = {};

		// build key map
		if (!this.map.A) {
			// split multiple name
			$.each(this.map, function(k,v){
				if(k.indexOf(' ')<0) return true;

				var keys = k.split(' '), i, c;

				for(i=0,c=keys.length; i < c; i++)
					self.map[keys[i]] = v;

				delete(self.map[k]);
			});

			function build(from, to) {
				for(var c=from; c<=to; c++) self.map[String.fromCharCode(c)] = c;
			};

			// A~Z
			build(65, 90);

			// 0~9
			build(48, 57);
		}
	},

	// on activate
	activate : function() {
		this.oApp.$richedit.keydown(this._fn);
	},

	// on deactivate
	deactivate : function() {
		this.oApp.$richedit.unbind('keydown',this._fn);
	},

	/**
	 * @brief hotkey event handler
	 * @param e jQuery event object
	 */
	hotkey : function(e) {
		var _k  = this._key, kc = e.keyCode, k;

		// always skip - shift, alt, ctrl, windows, kor/eng
		if ((15 < kc && kc < 19) || kc == 229) return true;
		if (e.metaKey && e.ctrlKey) e.metaKey = false;

		// make hotkey string
		k = this.key2str(e);

		if (_k[k]) {
			_k[k](e);
			return false;
		}

		return true;
	},

	/**
	 * @brief return normalize hotkey string
	 */
	normalize : function(str) {
		var keys = (str||'').replace(/ \t\r\n/g, '').toUpperCase().split('+'), obj={}, i, c;

		for(i=0,c=keys.length; i < c; i++) {
			switch(keys[i]) {
				case 'ALT':   obj.altKey   = 1; break;
				case 'CTRL':  obj.ctrlKey  = 1; break;
				case 'META':  obj.metaKey  = 1; break;
				case 'SHIFT': obj.shiftKey = 1; break;
				default:
					if (this.map[keys[i]]) obj.keyCode = this.map[keys[i]];
			}
		}

		// if there is no valid keyCode, return undefined object.
		if (!obj.keyCode) return;

		return this.key2str(obj);
	},

	/**
	 * @brief make hotkey string from the key event object.
	 * @return hotkey string
	 */
	key2str : function(e) {
		var ret = [];

		if (e.altKey)   ret.push('ALT');
		if (e.ctrlKey)  ret.push('CTRL');
		if (e.metaKey)  ret.push('META');
		if (e.shiftKey) ret.push('SHIFT');
		ret.push(e.keyCode);

		return ret.join('+');
	},

	/**
	 * @brief register a hotkey
	 * @param str Hotkey string
	 * @param fn  Hotkey function
	 */
	API_REGISTER_HOTKEY : function(sender, params) {
		var str = this.normalize(params[0]), fn = params[1];

		if (str) this._key[str] = fn;
	},

	/**
	 * @brief unregister a hotkey
	 * @param str Hotkey string
	 */
	API_UNREGISTER_HOTKEY : function(sender, params) {
		var str = this.normalize(params[0]);

		if (str && this._key[str]) delete this._key[str];
	}
});
/**
 * }}}
 */

/**
 * {{{ Content Filter plugin
 * When you call GET_CONTENT message,
 *  If you using the richedit: rich content -> r2t filter -> text content -> out filter -> output
 *  If you using the textarea: text content -> out filter -> output
 * When you call SET_CONTENT message,
 *  If you using the richedit: input -> in filter -> text content -> t2r filter -> rich content
 *  If you using the textarea: input -> in filter -> text content
 */
Filter = xe.createPlugin('ContentFilter', {
	_in  : [], // input filters
	_out : [], // output filters
	_r2t : [], // rich2text filters
	_t2r : [], // text2rich filters
	_types : [], // valid types

	init : function() {
		this._in  = [];
		this._out = [];
		this._r2t = [];
		this._t2r = [];
	},
	activate : function(){ },
	/**
	 * @brief Register a filter
	 * @params type Filter type string. 'in', 'out', 'r2t', 't2r'
	 * @params func Filter function
	 */
	API_REGISTER_FILTER : function(sender, params) {
		var type = params[0], func = params[1];

		if (!$.isArray(this['_'+type]) || !$.isFunction(func)) return;
		this['_'+type].push(func);
	},

	/**
	 * @brief Unregister a filter
	 * @params type Filter type string.
	 * @params func Filter function
	 */
	API_UNREGISTER_FILTER : function(sender, params) {
		var type = params[0], func = params[1], pool, newPool=[], i, c;

		if (!$.isArray(this['_'+type])) return;
		for(i=0,pool=this['_'+type],c=pool.length; i < c; i++) {
			if (pool[i] !== func) newPool.push(pool[i]);
		}
		this['_'+type] = newPool;
	},

	/**
	 * @brief Run input filters before SET_CONTENT
	 */
	API_BEFORE_SET_CONTENT : function(sender, params) {
		var i,c,m = this.cast('GET_EDITMODE') || '';

		if (sender.getName() != 'EditMode') {
			for(i=0,c=this._in.length; i < c; i++) params[0] = this._in[i](params[0]);
		}

		for(i=0,c=this._t2r.length; i < c; i++) params[0] = this._t2r[i](params[0]);
	},

	API_BEFORE_SET_CONTENT_HTML : function(sender, params) {
		for(i=0,c=this._r2t.length; i < c; i++) params[0] = this._r2t[i](params[0]);
	},

	/**
	 * @brief Run output filters before GET_CONTENT
	 */
	API_BEFORE_GET_CONTENT : function(sender, params) {
		var i,c,m = this.cast('GET_EDITMODE') || '';

		if (m == 'wysiwyg') {
			for(i=0,c=this._r2t.length; i < c; i++) params[0] = this._r2t[i](params[0]);
		}
		for(i=0,c=this._out.length; i < c; i++) params[0] = this._out[i](params[0]);
	}
});
/**
 * }}}
 */

/**
 * {{{ Edit Mode plugin
 * @brief Switch edit mode
 */
EditMode = xe.createPlugin('EditMode', {
	// constructor
	init : function() { },
	activate : function() {
		var self = this, app = this.oApp, $r = this.oApp.$root;

		this.$btn_wysiwyg = $r.find('button.wysiwyg').mousedown(function(){ self.cast('MODE_WYSIWYG'); return false; });
		this.$btn_html    = $r.find('button.html').mousedown(function(){ self.cast('MODE_HTML'); return false; });

		this.$btn_wysiwyg_p = this.$btn_wysiwyg.parent();
		this.$btn_html_p    = this.$btn_html.parent();

		app.$textarea.hide();
		app.$richedit.show();

		if (/iPod|iPhone|Android|BlackBerry|SymbianOS|SCH\-M[0-9]+/.test(navigator.userAgent)) {
			this.$btn_html.mousedown();
		}
	},
	deactivate : function() {
		this.$btn_wysiwyg.unbind('mousedown');
		this.$btn_html.unbind('mousedown');
	},
	API_MODE_WYSIWYG : function(sender, params) {
		var app = this.oApp, param;

		if (app.$richedit.is(':visible')) return true;

		app.$richedit.show().parent().css('overflow','');
		app.$textarea.hide();

		// set active button
		this.$btn_wysiwyg_p.addClass('active');
		this.$btn_html_p.removeClass('active');

		// set content
		this.cast('SET_CONTENT', [app.$textarea.val()]);
	},
	API_MODE_HTML : function(sender, params) {
		var app = this.oApp, h;
		if (app.$richedit.is(':hidden')) return true;

		app.$textarea.show().css('height', '100%').css('width', '100%').css('border',0);
		app.$richedit.hide().parent().css('overflow','hidden');

		// Fix IE6 and 7 rendering bug
		if ($.browser.msie && $.browser.version < 8) {
			app.$textarea.css('height', app.$textarea.parent().height());
		}

		// set active button
		this.$btn_wysiwyg_p.removeClass('active');
		this.$btn_html_p.addClass('active');

		// set html code
		this.cast('SET_CONTENT_HTML', [app.$richedit.html()]);
	},
	// If html editor is activated, cancel default SET_CONTENT action
	// and call SET_CONTENT_HTML instead.
	API_BEFORE_SET_CONTENT : function(sender, params) { },
	API_BEFORE_BEFORE_SET_CONTENT : function(sender, params) {
		if (this.$btn_html_p.hasClass('active')) {
			this.cast('SET_CONTENT_HTML', params);
			return false;
		}
	},
	/**
	 * @brief Put html code into the textarea
	 * @param html String html code
	 */
	API_SET_CONTENT_HTML : function(sender, params) {
		this.oApp.$textarea.val( params[0]||'' );
	},
	/**
	 * @brief Get editing mode
	 */
	API_GET_EDITMODE : function(sender, params) {
		return this.oApp.$richedit.is(':visible')?'wysiwyg':'html';
	}
});
/**
 * }}}
 */

/**
 * {{{ Resize Plugin
 */
Resize = xe.createPlugin('Resize', {
	_fn : null,
	prev_height : 0,
	$resize_bar : null,
	$auto_check : null,
	$container  : null,

	init : function() {
		var self = this, startY, startH, resizing;

		function isLeftButton(event) {
			return (event.which == 1);
		}

		this._fn = {
			down : function(event) {
				var oChk = self.$auto_check.get(0);

				if (event.target != this || !isLeftButton(event)) return true;
				if (oChk.checked || oChk.disabled) return true;

				startY = event.pageY;
				startH = parseInt(self.$container.css('height'));
				$(document).mousemove(self._fn.move).one('mouseup', self._fn.up);

				resizing = true;

				self.cast('RESIZE_START');

				return false;
			},
			up : function(event) {
				if (!resizing) return true;

				$(document).unbind({mousemove:self._fn.move, mouseup:self._fn.up});

				resizing = false;

				self.cast('RESIZE_END');

				return false;
			},
			move : function(event) {
				var diff_y, new_h;

				if (!resizing || !isLeftButton(event)) return true;

				diff_y = event.pageY - startY;
				new_h  = startH + diff_y;

				self.$container.css('height', new_h);
				self.cast('RESIZE');

				return false;
			},
			check : function(event) {
				self.cast('SET_AUTO_RESIZE', [this.checked]);
			}
		};
	},
	activate : function() {
		var $root = this.oApp.$root, chk;

		if (!this.prev_height) this.prev_height = this.oApp.getOption('height');

		this.$container  = this.oApp.$richedit.parent();
		this.$resize_bar = $root.find('button.resize').mousedown(this._fn.down);
		this.$auto_check = $root.find('div.autoResize > :checkbox').click(this._fn.check);

		chk = this.$auto_check[0].checked;
		this.cast('SET_AUTO_RESIZE', [chk]);
	},
	deactivate : function() {
		this.$resize_bar.unbind({dragstart:this._fn.dragstart, drag:this._fn.drag});
		this.$auto_check.unbind('click', this._fn.check);
	},
	/**
	 * @brief on resizing
	 * @param new height
	 * @param new mouse y position
	 */
	// API_RESIZE : function(sender, params) { },
	/**
	 * @brief Start resizing
	 * @param current height
	 * @param current mouse y position
	 */
	// API_RESIZE_START : function(sender, params) { },
	/**
	 * @brief End resizing
	 * @param new height
	 * @param new mouse y position
	 */
	// API_RESIZE_END : function(sender, params) { },
	/**
	 * @brief Set auto resize
	 * @param Boolean indicates whether or not to resize automatically.
	 */
	API_SET_AUTO_RESIZE : function(sender, params) {
		var $ctn = this.$container, $rich = this.oApp.$richedit, h;

		if (params[0]) {
			h = parseInt($ctn.css('height'), 10);
			if (!isNaN(h)) this.prev_height = h;

			$ctn.css('height', 'auto');
		} else {
			$ctn.css('height', this.prev_height);
		}
	},
	API_BEFORE_MODE_HTML : function(sender, params) {
		var chk = this.$auto_check.get(0).checked;

		if (chk) {
			this.prev_height = this.$container.height();
			this.cast('SET_AUTO_RESIZE', [false]);
		}
	},
	API_AFTER_MODE_HTML : function(sender, params) {
		this.$auto_check.attr('disabled', true);
	},
	API_BEFORE_MODE_WYSIWYG : function(sender, params) {
		var chk = this.$auto_check.get(0).checked;

		if (chk) this.cast('SET_AUTO_RESIZE', [true]);
	},
	API_AFTER_MODE_WYSIWYG : function(sender, params) {
		this.$auto_check.removeAttr('disabled');
	}
});
/**
 * }}}
 */

/**
 * {{{ UndoRedo
 * @brief Manages undo and redo actions, save undo point.
 */
UndoRedo = xe.createPlugin('UndoRedo', {
	_history  : [],
	_index    : -1,
	_timer    : null,
	_keypress : null,
	$undo_btn : null,
	$redo_btn : null,

	init : function() {
		this._history = [];
	},
	activate : function() {
		var self = this, $tb = this.oApp.$toolbar;

		if (!$tb) return;

		this.$undo_btn = $tb.find('button.ud');
		this.$redo_btn = $tb.find('button.rd');

		this.cast('REGISTER_COMMAND', [this.$undo_btn[0], 'ctrl+z', 'EXEC_UNDO']);
		this.cast('REGISTER_COMMAND', [this.$redo_btn[0], 'ctrl+shift+z', 'EXEC_REDO']);

		// keypress
		this._keypress = function( ) {
			if (self._timer) clearTimeout(self._timer);
			self._timer = setTimeout(function(){ self.cast('SAVE_UNDO_POINT') }, 500);
		};
		this.oApp.$richedit.keypress(this._keypress);
	},
	deactivate : function() {
		this.oApp.$richedit.unbind('keypress', this._keypress);

		this.cast('UNREGISTER_COMMAND', [this.$undo_btn[0]]);
		this.cast('UNREGISTER_COMMAND', [this.$redo_btn[0]]);

		this.$undo_btn = null;
		this.$redo_btn = null;

	},
	// redraw buttons
	redraw : function(index) {
		var $undo = this.$undo_btn, $redo = this.$redo_btn, fn, index = this._index, len = this._history.length;

		if ($undo && $undo[0]) {
			fn = (index > 0 && len)?'removeClass':'addClass';
			$undo.parent()[fn]('disable');
		}

		if ($redo && $redo[0]) {
			fn = (index+1 < len)?'removeClass':'addClass';
			$redo.parent()[fn]('disable');
		}
	},
	API_EXEC_UNDO : function(sender, params) {
		this.cast('RESTORE_UNDO_POINT', [this._index-1]);
	},
	API_EXEC_REDO : function(sender, params) {
		this.cast('RESTORE_UNDO_POINT', [this._index+1]);
	},
	/**
	 * Save undo point
	 * @return Number indicates current undo point
	 */
	API_SAVE_UNDO_POINT : function(sender, params) {
		var sel = this.oApp.getSelection(), history = this._history, index = this._index, item = {}, last_item, $rich = this.oApp.$richedit;

		// if richedit is not shown, don't execute this command.
		if ($rich.is(':hidden')) return -1;

		// when undo history is saved, clear saving timer
		if (this._timer) {
			clearTimeout(this._timer);
			this._timer = null;
		}

		// delete redo history
		if (index+1 < history.length) history = history.slice(0, index+1);

		item.content  = $rich.html();
		if (sel) {
			item.bookmark = sel.getXPathBookmark();
		} else {
			item.bookmark = null;
		}

		// if the content isn't changed, don't save this history.
		if (history.length) {
			last_item = history[history.length-1];
			if (item.content == last_item.content) return this._index;
		}

		history.push(item);

		this._history = history;
		this._index   = history.length - 1;

		this.redraw();

		return this._index;
	},
	/**
	 * Restore to saved undo point
	 * @param Number indicates saved undo point
	 * @return Number indicates current undo point
	 */
	API_RESTORE_UNDO_POINT : function(sender, params) {
		var idx = params[0], item, sel, $rich = this.oApp.$richedit;

		// error : invalid index
		if (idx < 0 || !(item=this._history[idx])) return -1;

		// if richedit is not shown, don't execute this command.
		if ($rich.is(':hidden')) return -1;

		// when undo history is restored, clear saving timer
		if (this._timer) {
			clearTimeout(this._timer);
			this._timer = null;
		}

		// restore content
		this.cast('SET_CONTENT', [item.content]);

		// restore selection
		sel = this.oApp.getEmptySelection();
		if (item.bookmark) {
			sel.moveToXPathBookmark(item.bookmark);
			sel.select();
		} else {
			$rich.focus().get(0);
			if ($rich[0][_cn_][0]) {
				sel.selectNode($rich[0][_cn_][0]);
				sel.collapseToStart();
				sel.select();
			}
		}

		// next undo point
		this._index = idx;

		this.redraw();

		return idx;
	},
	API_AFTER_SET_CONTENT : function(sender, params) {
		if (sender != this && this.oApp.$richedit.is(':visible')) {
			this.cast('SAVE_UNDO_POINT');
		}
	}
});
/**
 * }}}
 */

/**
 * {{{ FileUpload
 */
FileUpload = xe.createPlugin('FileUpload', {
	$btn       : null,
	$modal_box : null,
	$template  : null,
	$file_list : null,
	esc_fn     : null,
	selection  : null,
	_index     : 0,
	_left_size : 0,
	_total_size : 0,

	init : function(){
		var self = this;

		this.esc_fn = function(event){ if(event.keyCode == 27) self.cast('HIDE_FILE_MODAL'); };
	},
	activate : function() {
		var self = this, app = this.oApp, $tb = app.$toolbar;

		this.$modal_box = app.$root.find('div.xdlw');

		if (this.$modal_box.length) {
			// #19473993 - move the file window to document's end
			jQuery(function(){ self.$modal_box.appendTo(document.body) });

			this.$attach_list = this.$modal_box.find('div.xdal');
			this.$attach_list
				.mousedown(function(event){ event.stopPropagation(); })
				.find('button.btn.cs')
					.click(function(){ self.cast('HIDE_FILE_MODAL'); return false; });

			// show and hide function button on hover
			this.$file_list = this.$attach_list.find('div.sn')
				.delegate('button.ctr.ins', 'click',
					function(){
						var $this = $(this), $item = $this.parent(), file_url = $this.parent().data('url');

						self.cast('INSERT_FILE_INTO', [$item.attr('_type'), file_url, $item.find('label').text()]);
						return false;
					}
				)
				.delegate('button.ctr.del', 'click',
					function(){
						var $this = $(this), $item = $this.parent(), file_srl = $this.parent().attr('file_srl');

						self.cast('DELETE_FILE', [file_srl]);
						return false;
					}
				);

			this.$template = this.$file_list.eq(0).find('>ul:first>li:first').remove();

			this.$attach_list.find('p.task button')
				.filter('.all') // select all
					.click(function(){
						$(this).parents('div.sn:first').find('li > :checkbox:not([disabled])').attr('checked', 'checked');
					})
					.end()
				.filter('.insert') // insert
					.click(function(){
						$(this).parents('div.sn:first').find('li > :checked:not([disabled])').prevAll('button.ins').click();
					})
					.end()
				.filter('.delete') // delete
					.click(function(){
						var file_srls = [];

						$(this).parents('div.sn:first').find('li:has(:checked:not([disabled]))').each(function(){ file_srls.push($(this).attr('file_srl')) });

						self.cast('DELETE_FILE', [file_srls]);
						return false;
					})
					.end();
		}

		if ($tb) {
			this.$btn = $tb.find('>div.t1>ul.u1 a.tb')
				.mousedown(function(){ self.selection = self.oApp.getSelection(); })
				.click(function(){ self.cast('SHOW_FILE_MODAL'); return false; });
		}

		// make it draggable
		this.$modal_box.find('.iHead, .iFoot').mousedown(bind(this, this._dragStart));

		// update filelist
		$(function(){ self.updateFileList() });
	},
	deactivate : function() {
		this.$attach_list.unbind()
			.find('div.sn').undelegate()
			.end()
			.find('button,input').unbind();

		// buttons
		$.each(this.$btns, function(key){ this.unbind('click'); });
		this.$btns = [];

		// modal box
		this.$modal_box.unbind();
	},
	_dragStart : function(event) {
		var $realwin = this.$modal_box.find('>.xdal'), m_left, m_top, fn;

		if ($(event.target).is('a,button,input')) return;

		fn = {
			move : bind(this, this._dragMove),
			up   : bind(this, this._dragEnd)
		};

		this.$modal_box.data('draggable', true).data('drag_fn', fn);

		$(document).mousemove(fn.move).mouseup(fn.up);

		m_left = parseInt($realwin.css('margin-left'), 10);
		m_top  = parseInt($realwin.css('margin-top'), 10);

		if (isNaN(m_left)) m_left = $realwin[0][_ol_];

		this.$modal_box
			.data('dragstart_pos', [event.pageX, event.pageY])
			.data('dragstart_margin', [m_left, m_top]);

		return false;
	},
	_dragMove : function(event) {
		var $real_win, start_pos, start_margin, new_margin;

		if (!this.$modal_box.data('draggable')) return;

		$realwin = this.$modal_box.find('>.xdal');

		start_pos = this.$modal_box.data('dragstart_pos');
		start_margin = this.$modal_box.data('dragstart_margin');

		$realwin.css({
			'margin-left' : (start_margin[0]+event.pageX-start_pos[0])+'px',
			'margin-top'  : (start_margin[1]+event.pageY-start_pos[1])+'px'
		});

		return false;
	},
	_dragEnd : function(event) {
		var fn = this.$modal_box.data('drag_fn');

		$(document).unbind('mousemove', fn.move).unbind('mouseup', fn.up);
		this.$modal_box.data('draggable', false);
	},
	createItem : function(file) {
		var $item, ext, match, id = 'xeed-id-'+(this._index++), type, file_types;

		ext = (match = file.name.match(/\.([a-z0-9]+)$/i))?match[1]||'':'';
		ext = ext.toLowerCase();

		file_types = 'pdf doc docx hwp ppt pps pptx txt rtf xls xlsx csv bmp tif raw avi wmv mov mpg flv divx mp3 wma wav aac flac psd ai svg xml html css js iso zip rar alz gz tar'.split(' ');

		// get file type
		if ($.inArray(ext, 'gif jpg jpeg png'.split(' ')) > -1) type = 'img';
		else if ($.inArray(ext, 'avi mov mpg wmv flv mp3 wma wav'.split(' ')) > -1) type = 'media';
		else type = 'file';

		$item = this.$template.clone()
			.find('button.ob > img').attr('alt', file.name).end()
			.find('label').text(file.name).attr('for', id).end()
			.find('input:checkbox').attr('id', id).end()
			.attr('ext', ext).attr('_type', type);

		if (type == 'file' || type == 'media') {
			if ($.inArray(ext, file_types) < 0) ext = 'etc';
			$item.find('>button:first').addClass(ext).empty().text(file.name);
		}

		return $item;
	},
	getKey : function(file) {
		return file.name.toLowerCase()+'-'+file.size;
	},
	updateCount : function() {
		var $items = this.$file_list.find('li[_type]'), $tb = this.oApp.$toolbar, $area, types = ['img','media','file'], i, c;

		this.$modal_box.find('h2 strong').text($items.length);

		for(i=0,c=types.length; i<c; i++) {
			$area  = this.$file_list.filter('.'+types[i]);
			$items = $area.find('li[_type]');
			$items.length?$area.removeClass('none'):$area.addClass('none');

			$tb.find('a.tb span.'+types[i]+' strong').text($items.length);
		}
	},
	updateFileSize : function(total_size) {
		var $info = this.$modal_box.find('p.info'), html = $info.html(), units = 'B KB MB GB TB'.split(' ');

		if (!is_def(total_size)) {
			total_size = 0;
			this.$file_list.find('li[_key]').each(function(){
				if (/\-([0-9]+)$/.test($(this).attr('_key'))) total_size += parseInt(RegExp.$1,10);
			});
		}

		// size
		while((units.length > 1) && total_size > 1024) {
			units.shift();
			total_size /= 1024;
		}

		$info.html( html.replace(/([0-9.]+)([a-zA-Z]+)\s*\//, total_size.toFixed(2)+units[0]+'/') );
	},
	updateFileList : function() {
		var self = this, params = {}, $form, seq, primary, target_srl;

		// get form
		$form = this.oApp.$textarea.parents('form:first');
		seq   = $form.attr('editor_sequence');

		// document serial number
		primary = editorRelKeys[seq].primary;

		params = {
			editor_sequence   : $form.attr('editor_sequence'),
			upload_target_srl : primary.value,
			mid : current_mid
		};

		$.exec_xml('file', 'getFileList', params, bind(this, this._callbackFileList), ['error', 'message', 'files', 'left_size', 'editor_sequence', 'upload_target_srl', 'upload_status']);
	},
	_callbackFileList : function(ret) {
		var i, c, f, k, primary, $item, $list, seq = ret.editor_sequence;

		this._left_size = parseInt(ret.left_size) || 0;

		if (!ret.files || !ret.files.item) return;
		if (!seq || !editorRelKeys[seq] || !(primary = editorRelKeys[seq].primary)) return;
		if (!$.isArray(ret.files.item)) ret.files.item = [ret.files.item];
		if (!primary.value && ret.upload_target_srl) primary.value = ret.upload_target_srl;

		for(i=0,c=ret.files.item.length; i < c; i++) {
			f = ret.files.item[i];
			k = f.source_filename.toLowerCase()+'-'+f.file_size;

			f.name = f.source_filename;
			f.size = f.file_size;

			$item = this.$file_list.find('li[_key='+k+']');

			if (!$item.length) {
				$item = this.createItem(f).attr('_key', this.getKey(f));
				$list = this.$file_list.filter('.'+$item.attr('_type')).find('ul').append($item).end();
			}

			if ($item.attr('_type') == 'img') {
				$item.find('button.ob > img')
					.load(function(){ $(this).css((this.width>this.height)?'width':'height', '54px'); })
					.attr('src', f.download_url);
			}

			$item.attr('file_srl', f.file_srl).data('url', f.download_url);
		}

		this.updateCount();
		this.updateFileSize();
	},
	API_SHOW_FILE_MODAL : function() {
		var self = this, uploader, file_group = [], $form, params, seq, offset_top;
		
		offset_top = this.$btn.offset().top;

		this.$modal_box.css('top', offset_top+13).show();
		this.$attach_list.show();

		// register ESC hotkey
		$(document).keydown(this.esc_fn);

		this.selection = this.oApp.getSelection();

		// get form
		$form = this.oApp.$textarea.parents('form:first');
		seq   = $form.attr('editor_sequence');

		// create an uploader
		if (!this.$modal_box.data('uploader')) {
			// additional parameter
			params = {
				mid : current_mid,
				act : 'procFileUpload',
				editor_sequence : seq,
				upload_target_srl : $form.find('input[name=document_srl]').val()
			};

			// file onselect event
			function file_onselect(files, old_len) {
				var html, $ob, i, c, $list, $item, type, limit_size = self.oApp.getOption('allowed_filesize'), total_size = 0, over = false;

				// size check
				for(i=old_len,c=files.length; i < c; i++) {
					if (files[i].size > limit_size) {
						over = true;
						break;
					}
					total_size += files[i].size;
				}

				if (total_size > self._left_size) over = true;
				if (over) {
					alert(lang.upload_not_enough_quota);
					while(old_len != files.length) files.pop();
					return;
				}

				for(i=old_len,c=files.length; i < c; i++) {
					$item = self.createItem(files[i]).addClass('uploading').attr('_key', self.getKey(files[i]));
					type  = $item.attr('_type');
					$list = self.$file_list.filter('.'+type).find('ul').append($item).end();

					($ob = $item.find('>button.ob'))
						.data('html', $ob.html())
						.html('<span class="progress"><span class="bar" style="width:0%"></span></span>');
				}

				self.updateCount();

				setTimeout(function(){ uploader.cast('START'); }, 10);
			}

			function upload_onprogress(file) {
				var $item = self.$file_list.find('li[_key='+self.getKey(file)+']');

				$item.find('>button.ob span.bar').css('width', parseInt(file.loaded*100/file.size)+'%');
			}

			function upload_onfinishone(file) {
				var $item = self.$file_list.find('li[_key='+self.getKey(file)+']'),
					$ob   = $item.find('>button.ob');

				if ($item.attr('_type') == 'img') {
				}

				self._total_size += file.size;
				self.updateFileSize(self._total_size);

				$ob.html($ob.data('html')).parent().removeClass('uploading');
			}

			function upload_onfinish() {
				var params = {}, primary, target_srl;

				// document serial number
				primary = editorRelKeys[seq].primary;

				params = {
					editor_sequence   : $form.attr('editor_sequence'),
					upload_target_srl : primary.value,
					mid    : current_mid
				};

				$.exec_xml('file', 'getFileList', params, bind(self, self._callbackFileList), ['error', 'message', 'files', 'left_size', 'editor_sequence', 'upload_target_srl', 'upload_status']);
			}

			uploader = xe.createUploader(
				this.$modal_box.find('button.at'),
				{
					url : request_uri+'index.php',
					dropzone : this.$modal_box.find('div.iBody'),
					params   : params,
					onselect : file_onselect,
					onprogress  : upload_onprogress,
					onfinishone : upload_onfinishone,
					onfinish    : upload_onfinish
				}
			);

			this.$modal_box.data('uploader',  uploader);
		}
	},
	API_HIDE_FILE_MODAL : function() {
		this.$modal_box.hide();
		this.$attach_list.hide();

		// unregister ESC hotkey
		$(document).unbind('keydown', this.esc_fn);

		if (this.selection) {
			try { this.selection.select(); } catch(e){};
		}
	},
	/**
	 * @brief Insert a file into the rich editor
	 */
	API_INSERT_FILE_INTO : function(sender, params) {
		var type = params[0], url = params[1], name = params[2], ext, sel, code;

		if (type == 'img') {
			code = '<img src="'+url+'" alt="'+name+'" />\n';
		} else if (type == 'media') {
			code = '<img src="./common/img/blank.gif" editor_component="multimedia_link" multimedia_src="'+url+'" width="400" height="320" style="display:block;width:400px;height:320px;border:2px dotted #4371B9;background:url(./modules/editor/components/multimedia_link/tpl/multimedia_link_component.gif) no-repeat center;" auto_start="false" alt="" class="_resizable" />';
		}

		if (!code) {
			code = '<a href="'+url+'">'+name+'</a>';
		}

		sel = this.selection || this.oApp.getSelection();

		if (sel) {
			sel.pasteHTML(code);
		} else if(this.oApp.$richedit.is(':visible')){
			this.oApp.$richedit.append(code);

			sel = this.oApp.getEmptySelection();
			sel.selectNode(this.oApp.$richedit[0].lastChild);
		}
		sel.collapseToEnd();
		sel.select();
	},
	/**
	 * @brief Delete a file
	 */
	API_DELETE_FILE : function(sender, params) {
		var self = this, file_srl = params[0], callback = params[1], $item, i, c;

		function _callback(ret){
			var i, c, selector=[];

			if (ret && ret.error && ret.error == 0) {
				if (!$.isArray(file_srl)) file_srl = [file_srl];

				for(i=0,c=file_srl.length; i < c; i++) {
					selector.push('li[file_srl='+file_srl[i]+']');
				}

				self.$file_list.find(selector.join(',')).remove();

				if ($.isFunction(callback)) callback();

				self.updateCount();
				self.updateFileSize();
			}
		}

		$.exec_xml('file', 'procFileDelete', {file_srls:file_srl, editor_sequence:1}, _callback);
	}
});
/**
 * }}}
 */

/**
 * {{{ URL
 */
URL = xe.createPlugin('URL', {
	sel    : null,
	$btn   : null,
	$layer : null,
	$text  : null,
	$btns  : null,
	$chk   : null,

	init : function(){},
	activate : function() {
		var self = this, app = this.oApp, $tb = app.$toolbar;

		if (!$tb) return;

		this.$btn   = $tb.find('button.ur')
			.mousedown(function(){ self.cast('TOGGLE_URL_LAYER'); return false; });

		this.$layer = this.$btn.next('.lr')
			.mousedown(function(event){ event.stopPropagation() });

		this.$text = this.$layer.find('input:text')
			.keypress(function(event){ if(event.keyCode == 13) self.$btns.eq(0).click() })
			.focus(function(){ this.select(); });

		this.$chk  = this.$layer.find('input:checkbox');

		this.$btns = this.$layer.find('button.btn')
			.each(function(i){
				var $this = $(this);

				if (i) {
					$this.click(function(){ self.cast('HIDE_URL_LAYER'); });
				} else {
					$this.click(function(){
						var url = self.$text.val(), newWin = self.$chk[0].checked;

						self.cast('EXEC_URL', [url, newWin]);
						self.cast('HIDE_URL_LAYER');
					});
				}
			});
	},
	deactivate : function() {
		if (this.$btn)   this.$btn.unbind('mousedown');
		if (this.$layer) this.$layer.unbind('mousedown').find('input,button').unbind();
	},
	/**
	 * @brief Insert an url
	 * @param url    String url
	 * @param newWin Boolean indicates new window link
	 */
	API_EXEC_URL : function(sender, params) {
		var sel = this.sel || this.oApp.getSelection(), url = params[0], newWin = params[1], newUrl;

		if (!sel) return;

		if (sel.collapsed && url) {

		} else {
			sel.select();
			this.cast('EXEC_COMMAND', [url?'createlink':'unlink', false, (newWin&&url?'xeed://':'')+url]);

			if (newWin && url) {
				this.oApp.$richedit.find('a[href^=xeed://]').attr('href', url).attr('target', '_blank');
			}
		}
		this.cast('HIDE_URL_LAYER');
	},
	API_SHOW_URL_LAYER : function() {
		var sel, li, offset, $node;

		if (!this.$layer || this.$layer.hasClass('open')) return;
		if (!(sel = this.oApp.getSelection())) return;

		this.sel = sel; // save selection
		this.$btn.parent().addClass('active');
		this.$layer.addClass('open');

		$node = $(sel.commonAncestorContainer);
		if (!$node.is('a')) $node = $node.parentsUntil('.'+_xr_).filter('a');
		if ($node.length) {
			this.$text.val( $node.attr('href') );
			if ($node.attr('target') == '_blank') this.$chk[0].checked = true;
		} else {
			this.$text.val('http://');
			this.$chk[0].checked = false;
		}

		li     = this.$btn.parents('li:first')[0];
		offset = li[_ol_] + li[_pn_][_ol_];
		(this.$layer.width() > offset)?this.$layer.addClass('right'):this.$layer.removeClass('right');

		this.$text.focus().select();
	},
	API_HIDE_URL_LAYER : function() {
		if (!this.$layer || !this.$layer.hasClass('open')) return;

		this.$btn.parent().removeClass('active');
		this.$layer.removeClass('open');
	},
	API_TOGGLE_URL_LAYER : function() {
		if (!this.$layer) return;

		this.cast( (this.$layer.hasClass('open')?'HIDE':'SHOW')+'_URL_LAYER' );
	},
	API_HIDE_ALL_LAYER : function() {
		if (this.$layer && this.$layer.hasClass('open')) this.cast('HIDE_URL_LAYER');
	}
});
/**
 * }}}
 */

/**
 * {{{ Table
 */
Table = xe.createPlugin('Table', {
	$btns   : null,
	$layer : null,
	$table : null, // preview table
	$th_btns   : null,
	$unit_btns : null,
	selection  : null,

	selector   : '.xeed_selected_cell',
	cell_selector : '',
	cmd : {
		cm : 'MERGE_CELLS',
		cs : 'SPLIT_CELLS',
		rs : 'SPLIT_ROWS'
	},

	init : function(){
		this.$btns = {};
		this.cell_selector = 'td'+this.selector+',th'+this.selector;
	},
	activate : function() {
		var self = this, $tb = this.oApp.$toolbar, $layer, $fieldset;

		if (!$tb) return;

		// tool buttons
		this.$btns.te = $tb.find('button.te').mousedown(function(){ self.cast('TOGGLE_TABLE_LAYER'); return false; });
		$.each(this.cmd, function(key) {
			var $btn = $tb.find('button.'+key);

			self.$btns[key] = $btn;
			self.cast('REGISTER_COMMAND', [$btn[0], '', this]);
		});

		// layer, preview table
		this.$layer = $layer = this.$btns.te.next('.lr').mousedown(function(event){ event.stopPropagation() });
		this.$table = $layer.find('fieldset.pv table:first');

		// caption setting
		$fieldset = $layer.find('fieldset.cn');
		this.$caption_txt = $fieldset.find('input:text')
			.keydown(function(){ self.$table.find('>caption').text( this.value ); })
			.focus(function(){ $(this).prev('label').hide(); })
			.blur(function(){
				if(!this.value) $(this).prev('label').show();
				$(this).keydown();
			});
		this.$caption_pos = $fieldset.find('button')
			.click(function(){
				var $li = $(this[_pn_]), pos, align, $table, $caption;

				$table   = self.$table;
				$caption = $table.find('>caption');

				$li.parent().children('li').removeClass('selected').end().end().addClass('selected');

				pos = self.parseCaptionPos($li.attr('class'));

				(pos.vert == 'top')?$table.prepend($caption):$table.append($caption);
				$table.css('caption-side', pos.vert);
				$caption.css('text-align', pos.align);

				return false;
			});

		// header setting
		this.$th_btns = $layer.find('fieldset.th button').click(function(){ self.setHeader(this); return false; });

		// px or percent
		this.$unit_btns = $layer.find('fieldset.wh button').click(function(){
			self.$unit_btns.removeClass('selected');
			$(this).addClass('selected');
			return false;
		});

		// apply, cancel
		$btns = $layer.find('div.btnArea button');
		$btns.eq(0).click(function(){
			var cfg = {rows:3, cols:3, width:'100%', caption_text:'', caption_pos:'tc', header:'no'}, $fieldset, $input;

			// get table properties
			$fieldset = $layer.find('fieldset.cn');
			cfg.caption_pos  = $fieldset.find('li.selected').attr('class').replace(/ ?selected ?/, '');
			cfg.caption_text = $fieldset.find('input:text').val();

			cfg.header = $layer.find('fieldset.th li.selected').attr('class').replace(/ ?selected ?/, '');

			$fieldset = $layer.find('fieldset.wh');
			$input    = $fieldset.find('input:text');
			cfg.rows  = $input.eq(0).val() - 0;
			cfg.cols  = $input.eq(1).val() - 0;
			cfg.width = $input.eq(2).val() - 0 + $fieldset.find('button.selected').text();

			self.cast('EXEC_TABLE', [cfg]);
			self.cast('HIDE_TABLE_LAYER');

			return false;
		});
		$btns.eq(1).click(function(){ self.cast('HIDE_TABLE_LAYER'); return false; });
	},
	deactivate : function() {
		var self = this;

		// buttons
		if(this.$btns.te) this.$btns.te.unbind('mousedown');
		$.each(this.$btns, function(key) {
			self.cast('UNREGISTER_COMMAND', [this]);
		});
		this.$btns = {};

		if (this.$layer) this.$layer.unbind().find('input,button').unbind();

	},
	parseCaptionPos : function(str) {
		var ret = {vert:'', align:''};

		str = $.trim(str.replace(/selected/g, ''));

		ret.vert = (str.indexOf('b') < 0)?'top':'bottom';

		if (str.indexOf('c') >= 0) ret.align = 'center';
		else if (str.indexOf('r') >= 0) ret.align = 'right';
		else ret.align = 'left';

		return ret;
	},
	setHeader : function(btn) {
		var $btn = $(btn), $li = $btn.parent('li'), $table = this.$table, selector;

		if (!$btn.is('button') || !$li.length) return;

		switch($li.attr('class')) {
			case 'no': selector = ''; break;
			case 'lt': selector = 'tr > td:nth-child(1)'; break;
			case 'tp': selector = 'tr:first td'; break;
			case 'bh': selector = 'tr:first td, tr > td:nth-child(1)'; break;
		}

		// conver td to th
		$table.find('th').replaceWith('<td>TD</td>');
		if (selector) $table.find(selector).replaceWith('<th>TH</th>');

		// select this button
		this.$th_btns.parent('li').removeClass('selected');
		$li.addClass('selected');
	},
	/**
	 * @brief Insert a table with a configuration
	 * @param Object contains table properties
	 */
	API_EXEC_TABLE : function(sender, params) {
		var sel = this.selection, cfg, html, caption, $rich = this.oApp.$richedit;

		if (!sel) return;

		cfg = $.extend({
			caption_pos  : 'tc',
			caption_text : '',
			cols : 3,
			rows : 3,
			header : 'no',
			width : '100%'
		}, params[0]);

		cfg.cp_side = (cfg.caption_pos.indexOf('t') > -1)?'top':'bottom';

		if (cfg.caption_pos.indexOf('c') > -1) cfg.cp_align = 'center';
		else if (cfg.caption_pos.indexOf('r') > -1) cfg.cp_align = 'right';
		else cfg.cp_align = 'left';

		if (cfg.caption_text) {
			caption = '<caption style="text-align:'+cfg.cp_align+'">'+cfg.caption_text+'</caption>';
		} else {
			caption = '';
		}

		// create table code
		html = '<table border="1" cellpadding="1" cellspacing="0" class="ts" style="width:'+cfg.width+';caption-side:'+cfg.cp_side+';">';
		// top caption
		if (cfg.cp_side == 'top')  html += caption;

		for(i=0; i < cfg.rows; i++) {

			html += '<tr>';
			for(j=0; j < cfg.cols; j++) {
				if ( (cfg.header == 'bh' && !(i*j)) || (cfg.header == 'tp' && !i) || (cfg.header == 'lt' && !j)) {
					html += '<th scope="'+(i?'row':'col')+'">TH</th>';
				} else {
					html += '<td>TD</td>';
				}
			}
			html += '</tr>';
		}

		// bottom caption
		if (cfg.cp_side == 'bottom') html += caption;

		html += '</table>';

		// insert table
		$rich.focus();
		sel.pasteHTML(html);
		sel.collapseToEnd();
	},
	API_SHOW_TABLE_LAYER : function(sender, params) {
		var $layer = this.$layer, li, offset;

		if (!$layer || $layer.hasClass('open')) return;

		this.selection = this.oApp.getSelection();
		$layer.addClass('open').parent('li').addClass('active');

		li     = this.$btns.te.parents('li:first')[0];
		offset = li[_ol_] + li[_pn_][_ol_];
		($layer.width() > offset)?$layer.addClass('right'):$layer.removeClass('right');

		this.cast('HIDE_ALL_LAYER', [$layer[0]]);
	},
	API_HIDE_TABLE_LAYER : function(sender, params) {
		var $layer = this.$layer;

		if (!$layer || !$layer.hasClass('open')) return;
		if (this.selection) this.selection.select();

		this.selection = null;
		$layer.removeClass('open').parent('li').removeClass('active');
	},
	API_TOGGLE_TABLE_LAYER : function(sender, params) {
		if (!this.$layer) return;
		this.cast( (this.$layer.hasClass('open')?'HIDE':'SHOW') + '_TABLE_LAYER' );
	},
	API_HIDE_ALL_LAYER : function(sender, params) {
		if (sender != this) this.cast('HIDE_TABLE_LAYER');
	},
	API_MERGE_CELLS : function(sender, params) {
		var self = this, html = '', $cell = this.oApp.$richedit.find(this.cell_selector);

		// if no selected cell then quit
		if (!cell.length) return;

		// merge content of all cells
		$cell.each(function(){ html += this.innerHTML; }).eq(0).html(html);

		// save undo point
		this.cast('SAVE_UNDO_POINT');
	},
	API_SPLIT_BY_COL : function(sender, params) {
		var cell = this.oApp.$richedit.find(this.cell_selector);

		if (!$cell.length) $cell = this.$current_cell;

		// save undo point
		this.cast('SAVE_UNDO_POINT');
	},
	API_SPLIT_BY_ROW : function(sender, params) {
		var $cell = this.oApp.$richedit.find(this.cell_selector);

		if (!$cell.length) $cell = this.$current_cell;

		// save undo point
		this.cast('SAVE_UNDO_POINT');
	}/*,
	API_ON_CHANGE_NODE : function(sender, params) {
		var self = this, node = params[0], $node, state;

		state = {
			cs : 'disable',
			rs : 'disable',
			cm : 'disable'
		};

		if (node) {
			sel = this.oApp.getSelection();

			if (sel.collapsed) {
				$node = $(node).parentsUntil('.'+_xr_).filter('td,th');

				if ($node.length) {
					state.cs = state.rs = 'normal';
					if ($node.is('.selected')) state.cm = 'normal';
				}
			}
		}

		$.each(this.$btns, function(key){
			self.cast('SET_COMMAND_STATE', [this[0], state[key]]);
		});
	}
	*/
});
/**
 * }}}
 */

/**
 * {{{ AutoSave
 */
AutoSave = xe.createPlugin('AutoSave', {
	_enable     : false,
	_timer      : null,
	_start_time : null,
	_save_time  : null,
	$bar        : null,
	$text       : null,

	init : function(){ },
	activate : function(){
		var self = this, app = this.oApp, $form, title, content;

		// start time
		this._start_time = (new Date).getTime();

		// set default option
		app.setDefault('use_autosave', false);
		this._enable = app.getOption('use_autosave');

		this.$bar = this.oApp.$root.find('div.time').hide().click(function(){ $(this).slideUp(300) });
		$form = $(app.$textarea[0].form);

		if (this._enable && window.editorEnableAutoSave) {
			editorEnableAutoSave($form[0], $form.attr('editor_sequence'), function(params){ self._save_callback(params) });
		}

		// restore saved content
		title   = $form[0]._saved_doc_title.value;
		content = $form[0]._saved_doc_content.value;

		if (title || content) {
			if (confirm($form[0]._saved_doc_message.value)) {
				$form.find('input[name=title]').val(title);
				app.$textarea.val(content);
			} else {
				editorRemoveSavedDoc();
			}
		}
	},
	deactivate : function() {
		this.$bar.unbind();
		clearTimeout(this._timer);
	},
	_save_callback : function(params) {
		var self = this;

		this._save_time = (new Date).getTime();

		this.$bar.slideDown(300);
		this._update_message();

		this._timer = setInterval(function(){self._update_message()}, 5000);
	},
	_update_message : function() {
		var msg = lang.autosave_format, now = (new Date).getTime(), write_interval, save_interval, write_msg, save_msg;

		write_interval = Math.floor( (now - this._start_time)/1000/60 );
		save_interval  = Math.floor( (now - this._save_time)/1000/60 );

		if (write_interval < 60) {
			write_msg = ((write_interval>1)?lang.autosave_mins:lang.autosave_min).replace('%d', write_interval);
		} else {
			write_interval = Math.floor(write_interval/60);
			write_msg = ((write_interval>1)?lang.autosave_hours:lang.autosave_hour).replace('%d', write_interval);
		}

		if (save_interval < 60) {
			save_msg = ((save_interval>1)?lang.autosave_mins_ago:lang.autosave_min_ago).replace('%d', save_interval);
		} else {
			save_interval = Math.floor(write_interval/60);
			save_msg = ((save_interval>1)?lang.autosave_hours_ago:lang.autosave_hour_ago).replace('%d', save_interval);
		}

		msg = msg.replace('%s', write_msg).replace('%s', save_msg);

		this.$bar.find('>p').html(msg);
	},
	API_EXEC_AUTOSAVE : function() {
		_editorAutoSave(true, this._save_callback);
	},
	API_ENABLE_AUTOSAVE : function(sender, params) {
		var b = this._enable = !!params[0];

		app.setOption('use_autosave', b);
	}
});
/**
 * }}}
 */

/**
 * {{{ Clear
 */
Clear = xe.createPlugin('Clear', {
	$btn : null,
	_keypress  : null,
	_mousemove : null,
	init : function(){ },
	activate : function() {
		var self = this, app = this.oApp;

		this.$btn = this.oApp.$toolbar.find('button.er');
		if (this.$btn.length) {
			this.cast('REGISTER_COMMAND', [this.$btn[0], '', 'EXEC_CLEAR']);
		}
	},
	deactivate : function() {
		if (this.$btn.length) {
			this.cast('UNREGISTER_COMMAND', [this.$btn[0], '']);
		}
	},
	API_EXEC_CLEAR : function() {
		var self = this, sel = this.oApp.getSelection(), sc, ec, so, eo, bl, nd, nodes, i, c, next, $pn;

		if (!sel || sel.collapsed) return;

		// split text nodes
		nodes = sel.getTextNodes(true);
		
		// get current position
		sc = sel[_sc_]; so = sel[_so_];
		ec = sel[_ec_]; eo = sel[_eo_];
		
		// start
		for(i=0,c=nodes.length;i<c;i++) {
			nd = nodes[i];
			bl = get_block_parent(nd);

			while(nd[_pn_] != bl) {
				next = siblings(nd);

				$pn = $(nd[_pn_]);
				if (next.length) $pn.after($pn.clone().empty().append(next));
				$pn.after(nd);
			}
		}

		sel.setStart(sc, so);
		sel.setEnd(ec, eo);
		sel.select();

		// save undo point
		this.cast('SAVE_UNDO_POINT');
		setTimeout(function(){ if (sel && sel[_sc_]) self.cast('ON_CHANGE_NODE', [sel[_sc_]]) }, 0);
	}
});
/**
 * }}}
 */

/**
 * {{{ Find
 */
FindReplace = xe.createPlugin('FindReplace', {
	init : function() {
	},
	activate : function() {
	},
	deactivate : function() {
	},
	API_EXEC_FIND : function() {
	},
	API_SHOW_FINDREPLACE_LAYER : function() {
	},
	API_SHOW_FINDREPLACE_LAYER : function() {
	},
	API_SHOW_FINDREPLACE_LAYER : function() {
	},
	API_HIDE_ALL_LAYER : function() {
	}
});
/**
 * }}}
 */

/**
 * {{{ XHTML Transitional scheme
 * This code is from the tinyMCE project.
 */
var XHTMLT = {};
(function(){
	function unpack(lookup, data) {
		function replace(value) {
			return value.replace(/[A-Z]+/g, function(key) {
				return replace(lookup[key]);
			});
		};

		// Unpack lookup
		$.each(lookup, function(key){ lookup[key] = replace(this) });

		// Unpack and parse data into object map
		replace(data).replace(/#/g, '#text').replace(/(\w+)\[([^\]]+)\]/g, function(str, name, children) {
			var i, map = {};

			children = children.split(/\|/);

			for (i = children.length - 1; i >= 0; i--)
				map[children[i]] = 1;

			XHTMLT[name] = map;
		});
	};

	// This is the XHTML 1.0 transitional elements with it's children packed to reduce it's size
	// we will later include the attributes here and use it as a default for valid elements but it
	// requires us to rewrite the serializer engine
	unpack({
		Z : '#|H|K|N|O|P',
		Y : '#|X|form|R|Q',
		X : 'p|T|div|U|W|isindex|fieldset|table',
		W : 'pre|hr|blockquote|address|center|noframes',
		U : 'ul|ol|dl|menu|dir',
		ZC : '#|p|Y|div|U|W|table|br|span|bdo|object|applet|img|map|K|N|Q',
		T : 'h1|h2|h3|h4|h5|h6',
		ZB : '#|X|S|Q',
		S : 'R|P',
		ZA : '#|a|G|J|M|O|P',
		R : '#|a|H|K|N|O',
		Q : 'noscript|P',
		P : 'ins|del|script',
		O : 'input|select|textarea|label|button',
		N : 'M|L',
		M : 'em|strong|dfn|code|q|samp|kbd|var|cite|abbr|acronym',
		L : 'sub|sup',
		K : 'J|I',
		J : 'tt|i|b|u|s|strike',
		I : 'big|small|font|basefont',
		H : 'G|F',
		G : 'br|span|bdo',
		F : 'object|applet|img|map|iframe'
	}, 'script[]' +
		'style[]' +
		'object[#|param|X|form|a|H|K|N|O|Q]' +
		'param[]' +
		'p[S]' +
		'a[Z]' +
		'br[]' +
		'span[S]' +
		'bdo[S]' +
		'applet[#|param|X|form|a|H|K|N|O|Q]' +
		'h1[S]' +
		'img[]' +
		'map[X|form|Q|area]' +
		'h2[S]' +
		'iframe[#|X|form|a|H|K|N|O|Q]' +
		'h3[S]' +
		'tt[S]' +
		'i[S]' +
		'b[S]' +
		'u[S]' +
		's[S]' +
		'strike[S]' +
		'big[S]' +
		'small[S]' +
		'font[S]' +
		'basefont[]' +
		'em[S]' +
		'strong[S]' +
		'dfn[S]' +
		'code[S]' +
		'q[S]' +
		'samp[S]' +
		'kbd[S]' +
		'var[S]' +
		'cite[S]' +
		'abbr[S]' +
		'acronym[S]' +
		'sub[S]' +
		'sup[S]' +
		'input[]' +
		'select[optgroup|option]' +
		'optgroup[option]' +
		'option[]' +
		'textarea[]' +
		'label[S]' +
		'button[#|p|T|div|U|W|table|G|object|applet|img|map|K|N|Q]' +
		'h4[S]' +
		'ins[#|X|form|a|H|K|N|O|Q]' +
		'h5[S]' +
		'del[#|X|form|a|H|K|N|O|Q]' +
		'h6[S]' +
		'div[#|X|form|a|H|K|N|O|Q]' +
		'ul[li]' +
		'li[#|X|form|a|H|K|N|O|Q]' +
		'ol[li]' +
		'dl[dt|dd]' +
		'dt[S]' +
		'dd[#|X|form|a|H|K|N|O|Q]' +
		'menu[li]' +
		'dir[li]' +
		'pre[ZA]' +
		'hr[]' +
		'blockquote[#|X|form|a|H|K|N|O|Q]' +
		'address[S|p]' +
		'center[#|X|form|a|H|K|N|O|Q]' +
		'noframes[#|X|form|a|H|K|N|O|Q]' +
		'isindex[]' +
		'fieldset[#|legend|X|form|a|H|K|N|O|Q]' +
		'legend[S]' +
		'table[caption|col|colgroup|thead|tfoot|tbody|tr]' +
		'caption[S]' +
		'col[]' +
		'colgroup[col]' +
		'thead[tr]' +
		'tr[th|td]' +
		'th[#|X|form|a|H|K|N|O|Q]' +
		'form[#|X|a|H|K|N|O|Q]' +
		'noscript[#|X|form|a|H|K|N|O|Q]' +
		'td[#|X|form|a|H|K|N|O|Q]' +
		'tfoot[tr]' +
		'tbody[tr]' +
		'area[]' +
		'base[]' +
		'body[#|X|form|a|H|K|N|O|Q]'
	);
})();
/**
 * }}}
 */

/**
 * {{{ Selection and Range Control class
 * This code is a modified version of HuskyRange, the SmartEditor project.
 */

/**
 * {{{ @class W3CDOMRange
 * @brief A cross-browser implementation of W3C's DOM Range
 */
function W3CDOMRange(){ this.init(); };
$.extend(W3CDOMRange.prototype, {
	init : function() {
		this.collapsed = true;
		this[_ca_] = d.body;
		this[_ec_] = d.body;
		this[_eo_] = 0;
		this[_sc_] = d.body;
		this[_so_] = 0;
	},
	cloneContents : function(){
		var oClonedContents = d.createDocumentFragment();
		var oTmpContainer   = d.createDocumentFragment();

		var aNodes = this._getNodesInRange();

		if(aNodes.length < 1) return oClonedContents;

		var oClonedContainers = this._constructClonedTree(aNodes, oTmpContainer);

		// oTopContainer = aNodes[aNodes.length-1].parentNode and this is not part of the initial array and only those child nodes should be cloned
		var oTopContainer = oTmpContainer.firstChild;

		if(oTopContainer){
			var elCurNode = oTopContainer.firstChild, elNextNode;

			while(elCurNode){
				elNextNode = elCurNode[_ns_];
				oClonedContents.appendChild(elCurNode);
				elCurNode = elNextNode;
			}
		}

		oClonedContainers = this._splitTextEndNodes({oStartContainer: oClonedContainers[_osc_], iStartOffset: this[_so_],
													oEndContainer: oClonedContainers[_oec_], iEndOffset: this[_eo_]});

		if(oClonedContainers[_osc_] && oClonedContainers[_osc_][_ps_])
			dp(oClonedContainers[_osc_]).removeChild(oClonedContainers[_osc_][_ps_]);

		if(oClonedContainers[_oec_] && oClonedContainers[_oec_][_ns_])
			dp(oClonedContainers[_oec_]).removeChild(oClonedContainers[_oec_][_ns_]);

		return oClonedContents;
	},

	_constructClonedTree : function(aNodes, oClonedParentNode){
		var oClonedStartContainer = null;
		var oClonedEndContainer   = null;

		var oStartContainer = this[_sc_];
		var oEndContainer   = this[_ec_];

		_recurConstructClonedTree = function(aAllNodes, iCurIdx, oParentNode, oClonedParentNode){

			if(iCurIdx < 0) return iCurIdx;

			var iChildIdx = iCurIdx-1;

			var oCurNodeCloneWithChildren = aAllNodes[iCurIdx].cloneNode(false);

			if(aAllNodes[iCurIdx] == oStartContainer) oClonedStartContainer = oCurNodeCloneWithChildren;
			if(aAllNodes[iCurIdx] == oEndContainer) oClonedEndContainer = oCurNodeCloneWithChildren;

			while(iChildIdx >= 0 && dp(aAllNodes[iChildIdx]) == aAllNodes[iCurIdx]){
				iChildIdx = this._recurConstructClonedTree(aAllNodes, iChildIdx, aAllNodes[iCurIdx], oCurNodeCloneWithChildren, oClonedStartContainer, oClonedEndContainer);
			}

			// this may trigger an error message in IE when an erroneous script is inserted
			oClonedParentNode.insertBefore(oCurNodeCloneWithChildren, oClonedParentNode.firstChild);

			return iChildIdx;
		};

		aNodes[aNodes.length] = dp(aNodes[aNodes.length-1]);
		_recurConstructClonedTree(aNodes, aNodes.length-1, aNodes[aNodes.length-1], oClonedParentNode);

		return {oStartContainer: oClonedStartContainer, oEndContainer: oClonedEndContainer};
	},

	cloneRange : function(){
		return this._copyRange(new W3CDOMRange(d));
	},

	_copyRange : function(oClonedRange){
		oClonedRange.collapsed = this.collapsed;
		oClonedRange[_ca_] = this[_ca_];
		oClonedRange[_ec_] = this[_ec_];
		oClonedRange[_eo_] = this[_eo_];
		oClonedRange[_sc_] = this[_sc_];
		oClonedRange[_so_] = this[_so_];
		oClonedRange._document = d;

		return oClonedRange;
	},

	collapse : function(toStart){
		if(toStart){
			this[_ec_] = this[_sc_];
			this[_eo_] = this[_so_];
		}else{
			this[_sc_] = this[_ec_];
			this[_so_] = this[_eo_];
		}

		this._updateRangeInfo();
	},

	compareBoundaryPoints : function(how, sourceRange){
		switch(how){
			case W3CDOMRange.START_TO_START:
				return this._compareEndPoint(this[_sc_], this[_so_], sourceRange[_sc_], sourceRange[_so_]);
			case W3CDOMRange.START_TO_END:
				return this._compareEndPoint(this[_ec_], this[_eo_], sourceRange[_sc_], sourceRange[_so_]);
			case W3CDOMRange.END_TO_END:
				return this._compareEndPoint(this[_ec_], this[_eo_], sourceRange[_ec_], sourceRange[_eo_]);
			case W3CDOMRange.END_TO_START:
				return this._compareEndPoint(this[_sc_], this[_so_], sourceRange[_ec_], sourceRange[_eo_]);
		}
	},

	_findBody : function(oNode){
		if(!oNode) return null;
		while(oNode){
			if(oNode[_nn_].toUpperCase() == "BODY") return oNode;
			oNode = dp(oNode);
		}
		return null;
	},

	_compareEndPoint : function(oContainerA, iOffsetA, oContainerB, iOffsetB){
		var iIdxA, iIdxB;

		if(!oContainerA || this._findBody(oContainerA) != d.body){
			oContainerA = d.body;
			iOffsetA = 0;
		}

		if(!oContainerB || this._findBody(oContainerB) != d.body){
			oContainerB = d.body;
			iOffsetB = 0;
		}

		var compareIdx = function(iIdxA, iIdxB){
			// iIdxX == -1 when the node is the commonAncestorNode
			// if iIdxA == -1
			// -> [[<nodeA>...<nodeB></nodeB>]]...</nodeA>
			// if iIdxB == -1
			// -> <nodeB>...[[<nodeA></nodeA>...</nodeB>]]
			if(iIdxB == -1) iIdxB = iIdxA+1;
			if(iIdxA < iIdxB) return -1;
			if(iIdxA == iIdxB) return 0;
			return 1;
		};

		var oCommonAncestor = this._getCommonAncestorContainer(oContainerA, oContainerB);

		// ================================================================================================================================================
		//  Move up both containers so that both containers are direct child nodes of the common ancestor node. From there, just compare the offset
		// Add 0.5 for each contaienrs that has "moved up" since the actual node is wrapped by 1 or more parent nodes and therefore its position is somewhere between idx & idx+1
		// <COMMON_ANCESTOR>NODE1<P>NODE2</P>NODE3</COMMON_ANCESTOR>
		// The position of NODE2 in COMMON_ANCESTOR is somewhere between after NODE1(idx1) and before NODE3(idx2), so we let that be 1.5

		// container node A in common ancestor container
		var oNodeA = oContainerA;
		if(oNodeA != oCommonAncestor){
			while((oTmpNode = dp(oNodeA)) != oCommonAncestor){oNodeA = oTmpNode;}

			iIdxA = this._getPosIdx(oNodeA)+0.5;
		}else iIdxA = iOffsetA;

		// container node B in common ancestor container
		var oNodeB = oContainerB;
		if(oNodeB != oCommonAncestor){
			while((oTmpNode = dp(oNodeB)) != oCommonAncestor){oNodeB = oTmpNode;}

			iIdxB = this._getPosIdx(oNodeB)+0.5;
		}else iIdxB = iOffsetB;

		return compareIdx(iIdxA, iIdxB);
	},

	_getCommonAncestorContainer : function(oNode1, oNode2){
		var oComparingNode = oNode2;

		while(oNode1){
			while(oComparingNode){
				if(oNode1 == oComparingNode) return oNode1;
				oComparingNode = dp(oComparingNode);
			}
			oComparingNode = oNode2;
			oNode1 = dp(oNode1);
		}

		return d.body;
	},

	deleteContents : function(){
		if(this.collapsed) return;

		this._splitTextEndNodesOfTheRange();

		var aNodes = this._getNodesInRange();

		if(aNodes.length < 1) return;

		var oPrevNode = aNodes[0][_ps_];
		while(oPrevNode && this._isBlankTextNode(oPrevNode)) oPrevNode = oPrevNode[_ps_];

		var oNewStartContainer, iNewOffset;
		if(!oPrevNode){
			oNewStartContainer = dp(aNodes[0]);
			iNewOffset = 0;
		}

		for(var i=0; i<aNodes.length; i++){
			var oNode = aNodes[i];
			if(!oNode.firstChild){
				if(oNewStartContainer == oNode){
					iNewOffset = this._getPosIdx(oNewStartContainer);
					oNewStartContainer = dp(oNode);
				}
				dp(oNode).removeChild(oNode);
			}
		}

		if(!oPrevNode){
			this.setStart(oNewStartContainer, iNewOffset);
		}else{
			if(oPrevNode.tagName == "BODY")
				this.setStartBefore(oPrevNode);
			else
				this.setStartAfter(oPrevNode);
		}

		this.collapse(true);
	},

	extractContents : function(){
		var oClonedContents = this.cloneContents();
		this.deleteContents();
		return oClonedContents;
	},

	insertNode : function(newNode){
		var oFirstNode = null, oParentContainer;

		if(this[_sc_][_nt_] == 3) {
			oParentContainer = dp(this[_sc_]);

			// Fix Opera bug : How can a text node is a child node of the other text node?
			while(oParentContainer[_nt_] == 3) oParentContainer = oParentContainer[_pn_];

			if(this[_sc_].nodeValue.length <= this[_so_])
				oFirstNode = this[_sc_][_ns_];
			else
				oFirstNode = this[_sc_].splitText(this[_so_]);
		}else{
			oParentContainer = this[_sc_];
			oFirstNode = dc(this[_sc_])[this[_so_]];
		}

		if(!oFirstNode || !dp(oFirstNode)) oFirstNode = null;

		oParentContainer.insertBefore(newNode, oFirstNode);

		this.setStartBefore(newNode);
	},

	selectNode : function(refNode){
		this.setStartBefore(refNode);
		this.setEndAfter(refNode);
	},

	selectNodeContents : function(refNode){
		this.setStart(refNode, 0);
		this.setEnd(refNode, dc(refNode).length);
	},

	_endsNodeValidation : function(oNode, iOffset){
		if(!oNode || this._findBody(oNode) != d.body) {
			throw new Error("INVALID_NODE_TYPE_ERR oNode is not part of current document");
		}

		if(oNode[_nt_] == 3){
			if(iOffset > oNode.nodeValue.length) iOffset = oNode.nodeValue.length;
		}else{
			if(iOffset > dc(oNode).length) iOffset = dc(oNode).length;
		}

		return iOffset;
	},


	setEnd : function(refNode, offset){
		offset = this._endsNodeValidation(refNode, offset);

		this[_ec_] = refNode;
		this[_eo_] = offset;
		if(!this[_sc_] || this._compareEndPoint(this[_sc_], this[_so_], this[_ec_], this[_eo_]) != -1) this.collapse(false);

		this._updateRangeInfo();
	},

	setEndAfter : function(refNode){
		if(!refNode) throw new Error("INVALID_NODE_TYPE_ERR in setEndAfter");

		if(refNode.tagName == "BODY"){
			this.setEnd(refNode, dc(refNode).length);
			return;
		}
		this.setEnd(dp(refNode), this._getPosIdx(refNode)+1);
	},

	setEndBefore : function(refNode){
		if(!refNode) throw new Error("INVALID_NODE_TYPE_ERR in setEndBefore");

		if(refNode.tagName == "BODY"){
			this.setEnd(refNode, 0);
			return;
		}

		this.setEnd(dp(refNode), this._getPosIdx(refNode));
	},

	setStart : function(refNode, offset){
		offset = this._endsNodeValidation(refNode, offset);

		this[_sc_] = refNode;
		this[_so_] = offset;

		if(!this[_ec_] || this._compareEndPoint(this[_sc_], this[_so_], this[_ec_], this[_eo_]) != -1) this.collapse(true);
		this._updateRangeInfo();
	},

	setStartAfter : function(refNode){
		if(!refNode) throw new Error("INVALID_NODE_TYPE_ERR in setStartAfter");

		if(refNode.tagName == "BODY"){
			this.setStart(refNode, dc(refNode).length);
			return;
		}

		this.setStart(dp(refNode), this._getPosIdx(refNode)+1);
	},

	setStartBefore : function(refNode){
		if(!refNode) throw new Error("INVALID_NODE_TYPE_ERR in setStartBefore");

		if(refNode.tagName == "BODY"){
			this.setStart(refNode, 0);
			return;
		}
		this.setStart(dp(refNode), this._getPosIdx(refNode));
	},

	surroundContents : function(newParent){
		newParent.appendChild(this.extractContents());
		this.insertNode(newParent);
		this.selectNode(newParent);
	},

	toString : function(){
		return $('<div />').append(this.cloneContents()).text();
	},

	_isBlankTextNode : function(oNode){
		if(oNode[_nt_] == 3 && oNode.nodeValue == "") return true;
		return false;
	},

	_getPosIdx : function(refNode){
		var idx = 0;
		for(var node = refNode[_ps_]; node; node = node[_ps_]) idx++;

		return idx;
	},

	_updateRangeInfo : function(){
		if(!this[_sc_]){
			this.init();
			return;
		}

		this.collapsed = this._isCollapsed(this[_sc_], this[_so_], this[_ec_], this[_eo_]);

		this[_ca_] = this._getCommonAncestorContainer(this[_sc_], this[_ec_]);
	},

	_isCollapsed : function(oStartContainer, iStartOffset, oEndContainer, iEndOffset){
		var bCollapsed = false;

		if(oStartContainer == oEndContainer && iStartOffset == iEndOffset){
			bCollapsed = true;
		}else{
			var oActualStartNode = this._getActualStartNode(oStartContainer, iStartOffset);
			var oActualEndNode = this._getActualEndNode(oEndContainer, iEndOffset);

			// Take the parent nodes on the same level for easier comparison when they're next to each other
			// eg) From
			//	<A>
			//		<B>
			//			<C>
			//			</C>
			//		</B>
			//		<D>
			//			<E>
			//				<F>
			//				</F>
			//			</E>
			//		</D>
			//	</A>
			//	, it's easier to compare the position of B and D rather than C and F because they are siblings
			//
			// If the range were collapsed, oActualEndNode will precede oActualStartNode by doing this
			oActualStartNode = this._getNextNode(this._getPrevNode(oActualStartNode));
			oActualEndNode = this._getPrevNode(this._getNextNode(oActualEndNode));

			if(oActualStartNode && oActualEndNode && oActualEndNode.tagName != "BODY" &&
				(this._getNextNode(oActualEndNode) == oActualStartNode || (oActualEndNode == oActualStartNode && this._isBlankTextNode(oActualEndNode)))
			)
				bCollapsed = true;
		}

		return bCollapsed;
	},

	_splitTextEndNodesOfTheRange : function(){
		var oEndPoints = this._splitTextEndNodes({oStartContainer: this[_sc_], iStartOffset: this[_so_],
													oEndContainer: this[_ec_], iEndOffset: this[_eo_]});

		this[_sc_] = oEndPoints[_osc_];
		this[_so_] = oEndPoints[_iso_];

		this[_ec_] = oEndPoints[_oec_];
		this[_eo_] = oEndPoints[_ieo_];
	},

	_splitTextEndNodes : function(oEndPoints){
		oEndPoints = this._splitStartTextNode(oEndPoints);
		oEndPoints = this._splitEndTextNode(oEndPoints);

		return oEndPoints;
	},

	_splitStartTextNode : function(oEndPoints){
		var oStartContainer = oEndPoints[_osc_];
		var iStartOffset = oEndPoints[_iso_];

		var oEndContainer = oEndPoints[_oec_];
		var iEndOffset = oEndPoints[_ieo_];

		if(!oStartContainer) return oEndPoints;
		if(oStartContainer[_nt_] != 3) return oEndPoints;
		if(iStartOffset == 0) return oEndPoints;

		if(oStartContainer.nodeValue.length <= iStartOffset) return oEndPoints;

		var oLastPart = oStartContainer.splitText(iStartOffset);

		if(oStartContainer == oEndContainer){
			iEndOffset -= iStartOffset;
			oEndContainer = oLastPart;
		}
		oStartContainer = oLastPart;
		iStartOffset = 0;

		return {oStartContainer: oStartContainer, iStartOffset: iStartOffset, oEndContainer: oEndContainer, iEndOffset: iEndOffset};
	},

	_splitEndTextNode : function(oEndPoints){
		var oStartContainer = oEndPoints[_osc_];
		var iStartOffset = oEndPoints[_iso_];

		var oEndContainer = oEndPoints[_oec_];
		var iEndOffset = oEndPoints[_ieo_];

		if(!oEndContainer) return oEndPoints;
		if(oEndContainer[_nt_] != 3) return oEndPoints;

		if(iEndOffset >= oEndContainer.nodeValue.length) return oEndPoints;
		if(iEndOffset == 0) return oEndPoints;

		oEndContainer.splitText(iEndOffset);

		return {oStartContainer: oStartContainer, iStartOffset: iStartOffset, oEndContainer: oEndContainer, iEndOffset: iEndOffset};
	},

	_getNodesInRange : function(){
		if(this.collapsed) return [];

		var oStartNode = this._getActualStartNode(this[_sc_], this[_so_]);
		var oEndNode = this._getActualEndNode(this[_ec_], this[_eo_]);

		return this._getNodesBetween(oStartNode, oEndNode);
	},

	_getActualStartNode : function(oStartContainer, iStartOffset){
		var oStartNode = oStartContainer;;

		if(oStartContainer[_nt_] == 3){
			if(iStartOffset >= oStartContainer.nodeValue.length){
				oStartNode = this._getNextNode(oStartContainer);
				if(oStartNode.tagName == "BODY") oStartNode = null;
			}else{
				oStartNode = oStartContainer;
			}
		}else{
			if(iStartOffset < dc(oStartContainer).length){
				oStartNode = dc(oStartContainer)[iStartOffset];
			}else{
				oStartNode = this._getNextNode(oStartContainer);
				if(oStartNode.tagName == "BODY") oStartNode = null;
			}
		}

		return oStartNode;
	},

	_getActualEndNode : function(oEndContainer, iEndOffset){
		var oEndNode = oEndContainer;

		if(iEndOffset == 0){
			oEndNode = this._getPrevNode(oEndContainer);
			if(oEndNode.tagName == "BODY") oEndNode = null;
		}else if(oEndContainer[_nt_] == 3){
			oEndNode = oEndContainer;
		}else{
			oEndNode = dc(oEndContainer)[iEndOffset-1];
		}

		return oEndNode;
	},

	_getNextNode : function(oNode){
		if(!oNode || oNode.tagName == "BODY") return d.body;

		if(oNode[_ns_]) return oNode[_ns_];

		return this._getNextNode(dp(oNode));
	},

	_getPrevNode : function(oNode){
		if(!oNode || oNode.tagName == "BODY") return d.body;

		if(oNode[_ps_]) return oNode[_ps_];

		return this._getPrevNode(dp(oNode));
	},

	// includes partially selected
	// for <div id="a"><div id="b"></div></div><div id="c"></div>, _getNodesBetween(b, c) will yield to b, "a" and c
	_getNodesBetween : function(oStartNode, oEndNode){
		var aNodesBetween = [];
		this._nNodesBetweenLen = 0;

		if(!oStartNode || !oEndNode) return aNodesBetween;

		this._recurGetNextNodesUntil(oStartNode, oEndNode, aNodesBetween);
		return aNodesBetween;
	},

	_recurGetNextNodesUntil : function(oNode, oEndNode, aNodesBetween){
		if(!oNode) return false;

		if(!this._recurGetChildNodesUntil(oNode, oEndNode, aNodesBetween)) return false;

		var oNextToChk = oNode[_ns_];

		while(!oNextToChk){
			if(!(oNode = dp(oNode))) return false;

			aNodesBetween[this._nNodesBetweenLen++] = oNode;

			if(oNode == oEndNode) return false;

			oNextToChk = oNode[_ns_];
		}

		return this._recurGetNextNodesUntil(oNextToChk, oEndNode, aNodesBetween);
	},

	_recurGetChildNodesUntil : function(oNode, oEndNode, aNodesBetween){
		if(!oNode) return false;

		var bEndFound = false;
		var oCurNode = oNode;
		if(oCurNode.firstChild){
			oCurNode = oCurNode.firstChild;
			while(oCurNode){
				if(!this._recurGetChildNodesUntil(oCurNode, oEndNode, aNodesBetween)){
					bEndFound = true;
					break;
				}
				oCurNode = oCurNode[_ns_];
			}
		}

		aNodesBetween[this._nNodesBetweenLen++] = oNode;

		if(bEndFound) return false;
		if(oNode == oEndNode) return false;

		return true;
	}
});

W3CDOMRange.START_TO_START = 0;
W3CDOMRange.START_TO_END = 1;
W3CDOMRange.END_TO_END = 2;
W3CDOMRange.END_TO_START = 3;
/**
 * }}} W3CDOMRange
 */

/**
 * {{{ @class HuskyRange
 * @brief A cross-browser function that implements all of the W3C's DOM Range specification and some more
 */
var
	HUSKY_BOOKMARK_START_ID_PREFIX = "husky_bookmark_start_",
	HUSKY_BOOKMARK_END_ID_PREFIX = "husky_bookmark_end_",
	rxLineBreaker = new RegExp("^("+this.sBlockElement+"|"+this.sBlockContainer+")$");

function HuskyRange(){ this.init(); }
$.extend(HuskyRange.prototype, W3CDOMRange.prototype, {
	init : function(){
		this.oSimpleSelection = new SimpleSelection();
		this.selectionLoaded = this.oSimpleSelection.selectionLoaded;

		W3CDOMRange.prototype.init.apply(this);
	},

	select : function(){
		this.oSimpleSelection.selectRange(this);
	},

	setFromSelection : function(iNum){
		this.setRange(this.oSimpleSelection.getRangeAt(iNum));
	},

	setRange : function(oW3CRange){
		this.setStart(oW3CRange[_sc_], oW3CRange[_so_]);
		this.setEnd(oW3CRange[_ec_], oW3CRange[_eo_]);
	},

	setEndNodes : function(oSNode, oENode){
		this.setEndAfter(oENode);
		this.setStartBefore(oSNode);
	},

	splitTextAtBothEnds : function(){
		this._splitTextEndNodesOfTheRange();
	},

	getStartNode : function(){
		if(this.collapsed){
			if(this[_sc_][_nt_] == 3){
				if(this[_so_] == 0) return this[_sc_];
				if(this[_sc_].nodeValue.length <= this[_so_]) return null;
				return this[_sc_];
			}
			return null;
		}

		if(this[_sc_][_nt_] == 3){
			if(this[_so_] >= this[_sc_].nodeValue.length) return this._getNextNode(this[_sc_]);
			return this[_sc_];
		}else{
			if(this[_so_] >= dc(this[_sc_]).length) return this._getNextNode(this[_sc_]);
			return dc(this[_sc_])[this[_so_]];
		}
	},

	getEndNode : function(){
		if(this.collapsed) return this.getStartNode();

		if(this[_ec_][_nt_] == 3){
			if(this[_eo_] == 0) return this._getPrevNode(this[_ec_]);
			return this[_ec_];
		}else{
			if(this[_eo_] == 0) return this._getPrevNode(this[_ec_]);
			return dc(this[_ec_])[this[_eo_]-1];
		}
	},

	getNodeAroundRange : function(bBefore, bStrict){
		if(this.collapsed && this[_sc_] && this[_sc_][_nt_] == 3) return this[_sc_];
		if(!this.collapsed || (this[_sc_] && this[_sc_][_nt_] == 3)) return this.getStartNode();

		var oBeforeRange, oAfterRange, oResult;

		if(this[_so_] >= dc(this[_sc_]).length)
			oAfterRange = this._getNextNode(this[_sc_]);
		else
			oAfterRange = dc(this[_sc_])[this[_so_]];

		if(this[_eo_] == 0)
			oBeforeRange = this._getPrevNode(this[_ec_]);
		else
			oBeforeRange = dc(this[_ec_])[this[_eo_]-1];

		if(bBefore){
			oResult = oBeforeRange;
			if(!oResult && !bStrict) oResult = oAfterRange;
		}else{
			oResult = oAfterRange;
			if(!oResult && !bStrict) oResult = oBeforeRange;
		}

		return oResult;
	},

	_getXPath : function(elNode){
		var sXPath = "";

		while(elNode && elNode[_nt_] == 1){
			sXPath = "/" + elNode.tagName+"["+this._getPosIdx4XPath(elNode)+"]" + sXPath;
			elNode = dp(elNode);
		}

		return sXPath;
	},

	_getPosIdx4XPath : function(refNode){
		var idx = 0;
		for(var node = refNode[_ps_]; node; node = node[_ps_])
			if(node.tagName == refNode.tagName) idx++;

		return idx;
	},

	// this was written specifically for XPath Bookmark and it may not perform correctly for general purposes
	_evaluateXPath : function(sXPath, oDoc){
		sXPath = sXPath.substring(1, sXPath.length-1);
		var aXPath = sXPath.split(/\//);
		var elNode = oDoc.body;

		for(var i=2; i<aXPath.length && elNode; i++){
			aXPath[i].match(/([^\[]+)\[(\d+)/i);
			var sTagName = RegExp.$1;
			var nIdx = RegExp.$2;

			var aAllNodes = dc(elNode);
			var aNodes = [];
			var nLength = aAllNodes.length;
			var nCount = 0;
			for(var ii=0; ii<nLength; ii++){
				if(aAllNodes[ii].tagName == sTagName) aNodes[nCount++] = aAllNodes[ii];
			}

			if(aNodes.length < nIdx)
				elNode = null;
			else
				elNode = aNodes[nIdx];
		}

		return elNode;
	},

	_evaluateXPathBookmark : function(oBookmark){
		var sXPath = oBookmark["sXPath"];
		var nTextNodeIdx = oBookmark["nTextNodeIdx"];
		var nOffset = oBookmark["nOffset"];

		var elContainer = this._evaluateXPath(sXPath, d);

		if(nTextNodeIdx > -1 && elContainer){
			var aChildNodes = dc(elContainer);
			var elNode = null;

			var nIdx = nTextNodeIdx;
			var nOffsetLeft = nOffset;

			while((elNode = aChildNodes[nIdx]) && elNode[_nt_] == 3 && elNode.nodeValue.length < nOffsetLeft){
				nOffsetLeft -= elNode.nodeValue.length;
				nIdx++;
			}

			elContainer = dc(elContainer)[nIdx];
			nOffset = nOffsetLeft;
		}

		if(!elContainer){
			elContainer = d.body;
			nOffset = 0;
		}
		return {elContainer: elContainer, nOffset: nOffset};
	},

	// this was written specifically for XPath Bookmark and it may not perform correctly for general purposes
	getXPathBookmark : function(){
		var nTextNodeIdx1 = -1;
		var htEndPt1 = {elContainer: this[_sc_], nOffset: this[_so_]};
		var elNode1 = this[_sc_];
		if(elNode1[_nt_] == 3){
			htEndPt1 = this._getFixedStartTextNode();
			nTextNodeIdx1 = this._getPosIdx(htEndPt1.elContainer);
			elNode1 = dp(elNode1);
		}
		var sXPathNode1 = this._getXPath(elNode1);
		var oBookmark1 = {sXPath:sXPathNode1, nTextNodeIdx:nTextNodeIdx1, nOffset: htEndPt1.nOffset};

		var nTextNodeIdx2 = -1;
		var htEndPt2 = {elContainer: this[_ec_], nOffset: this[_eo_]};
		var elNode2 = this[_ec_];
		if(elNode2[_nt_] == 3){
			htEndPt2 = this._getFixedEndTextNode();
			nTextNodeIdx2 = this._getPosIdx(htEndPt2.elContainer);
			elNode2 = dp(elNode2);
		}
		var sXPathNode2 = this._getXPath(elNode2);
		var oBookmark2 = {sXPath:sXPathNode2, nTextNodeIdx:nTextNodeIdx2, nOffset: htEndPt2.nOffset};

		return [oBookmark1, oBookmark2];
	},

	moveToXPathBookmark : function(aBookmark){
		if(!aBookmark) return;

		var oBookmarkInfo1 = this._evaluateXPathBookmark(aBookmark[0]);
		var oBookmarkInfo2 = this._evaluateXPathBookmark(aBookmark[1]);

		if(!oBookmarkInfo1["elContainer"] || !oBookmarkInfo2["elContainer"]) return;

		this[_sc_] = oBookmarkInfo1["elContainer"];
		this[_so_] = oBookmarkInfo1["nOffset"];

		this[_ec_] = oBookmarkInfo2["elContainer"];
		this[_eo_] = oBookmarkInfo2["nOffset"];
	},

	_getFixedTextContainer : function(elNode, nOffset){
		while(elNode && elNode[_nt_] == 3 && elNode[_ps_] && elNode[_ps_][_nt_] == 3){
			nOffset += elNode[_ps_].nodeValue.length;
			elNode = elNode[_ps_];
		}

		return {elContainer:elNode, nOffset:nOffset};
	},

	_getFixedStartTextNode : function(){
		return this._getFixedTextContainer(this[_sc_], this[_so_]);
	},

	_getFixedEndTextNode : function(){
		return this._getFixedTextContainer(this[_ec_], this[_eo_]);
	},

	placeStringBookmark : function(){
		var sTmpId = (new Date()).getTime(), oInsertionPoint, oEndMarker, oStartMarker;

		(oInsertionPoint = this.cloneRange()).collapseToEnd();
		oInsertionPoint.insertNode( $('<a>').attr('id', HUSKY_BOOKMARK_END_ID_PREFIX+sTmpId)[0] );

		(oInsertionPoint = this.cloneRange()).collapseToStart();
		oInsertionPoint.insertNode( $('<a>').attr('id', HUSKY_BOOKMARK_START_ID_PREFIX+sTmpId)[0] );

		this.moveToBookmark(sTmpId);

		return sTmpId;
	},

	cloneRange : function(){
		return this._copyRange(new HuskyRange());
	},

	moveToBookmark : function(vBookmark){
		if(typeof(vBookmark) != "object")
			this.moveToStringBookmark(vBookmark);
		else
			this.moveToXPathBookmark(vBookmark);
	},

	moveToStringBookmark : function(sBookmarkID){
		var oStartMarker = $('#'+HUSKY_BOOKMARK_START_ID_PREFIX+sBookmarkID)[0];
		var oEndMarker = $('#'+HUSKY_BOOKMARK_END_ID_PREFIX+sBookmarkID)[0];

		if(!oStartMarker || !oEndMarker) return;

		this.setEndBefore(oEndMarker);
		this.setStartAfter(oStartMarker);
	},

	removeStringBookmark : function(sBookmarkID){
		$(
			'#' + HUSKY_BOOKMARK_START_ID_PREFIX + sBookmarkID + ',' +
			'#' + HUSKY_BOOKMARK_END_ID_PREFIX   + sBookmarkID
		).remove();
	},

	collapseToStart : function(){
		this.collapse(true);
	},

	collapseToEnd : function(){
		this.collapse(false);
	},

	createAndInsertNode : function(sTagName){
		tmpNode = d.createElement(tagName);
		this.insertNode(tmpNode);
		return tmpNode;
	},

	getNodes : function(bSplitTextEndNodes, fnFilter){
		if(bSplitTextEndNodes) this._splitTextEndNodesOfTheRange();

		var aAllNodes = this._getNodesInRange();
		var aFilteredNodes = [];

		if(!fnFilter) return aAllNodes;

		for(var i=0; i<aAllNodes.length; i++)
			if(fnFilter(aAllNodes[i])) aFilteredNodes[aFilteredNodes.length] = aAllNodes[i];

		return aFilteredNodes;
	},

	getTextNodes : function(bSplitTextEndNodes){
		var txtFilter = function(oNode){
			if (oNode[_nt_] == 3 && oNode.nodeValue != "\n" && oNode.nodeValue != "")
				return true;
			else
				return false;
		}

		return this.getNodes(bSplitTextEndNodes, txtFilter);
	},

	surroundContentsWithNewNode : function(sTagName){
		var oNewParent = d.createElement(sTagName);
		this.surroundContents(oNewParent);
		return oNewParent;
	},

	isRangeInRange : function(oAnotherRange, bIncludePartlySelected){
		var startToStart = this.compareBoundaryPoints(this.START_TO_START, oAnotherRange);
		var startToEnd = this.compareBoundaryPoints(this.START_TO_END, oAnotherRange);
		var endToStart = this.compareBoundaryPoints(this.END_TO_START, oAnotherRange);
		var endToEnd = this.compareBoundaryPoints(this.END_TO_END, oAnotherRange);

		if(startToStart <= 0 && endToEnd >= 0) return true;
		return bIncludePartlySelected && (startToEnd != 1 && endToStart != -1);
	},

	isNodeInRange : function(oNode, bIncludePartlySelected, bContentOnly){
		var oTmpRange = new HuskyRange();

		if(bContentOnly && oNode.firstChild){
			oTmpRange.setStartBefore(oNode.firstChild);
			oTmpRange.setEndAfter(oNode.lastChild);
		}else{
			oTmpRange.selectNode(oNode);
		}

		return this.isRangeInRange(oTmpRange, !!bIncludePartlySelected);
	},

	pasteHTML : function(sHTML){
		if(sHTML == ""){
			this.deleteContents();
			return;
		}

		var oTmpDiv = $('<div>').html(sHTML)[0];
		var oFirstNode = oTmpDiv.firstChild;
		var oLastNode = oTmpDiv.lastChild;

		var clone = this.cloneRange();
		var sBM = clone.placeStringBookmark();

		while(oTmpDiv.lastChild) this.insertNode(oTmpDiv.lastChild);

		this.setEndNodes(oFirstNode, oLastNode);

		// delete the content later as deleting it first may mass up the insertion point
		// eg) <p>[A]BCD</p> ---paste O---> O<p>BCD</p>
		clone.moveToBookmark(sBM);
		clone.deleteContents();
		clone.removeStringBookmark(sBM);
	},

	toString : function(){
		this.toString = W3CDOMRange.prototype.toString;
		return this.toString();
	},

	toHTMLString : function(){
		return $('<div>').append(this.cloneContents()).html();
	},

	findAncestorByTagName : function(sTagName){
		var oNode = this[_ca_];
		while(oNode && oNode.tagName != sTagName) oNode = dp(oNode);

		return oNode;
	},

	selectNodeContents : function(oNode){
		if(!oNode) return;

		var oFirstNode = oNode.firstChild?oNode.firstChild:oNode;
		var oLastNode = oNode.lastChild?oNode.lastChild:oNode;

		if(oFirstNode[_nt_] == 3)
			this.setStart(oFirstNode, 0);
		else
			this.setStartBefore(oFirstNode);

		if(oLastNode[_nt_] == 3)
			this.setEnd(oLastNode, oLastNode.nodeValue.length);
		else
			this.setEndAfter(oLastNode);
	},

	styleRange : function(oStyle, oAttribute, sNewSpanMarker){
		var aStyleParents = this._getStyleParentNodes(sNewSpanMarker), c = aStyleParents.length, i;
		if(c < 1) return;

		for(i=0; i < c; i++) {
			if (oStyle) $(aStyleParents[i]).css(oStyle);
			if (oAttribute) $(aStyleParents[i]).attr(oAttribute);
		}

		this.setStartBefore(aStyleParents[0]);
		this.setEndAfter(aStyleParents[aStyleParents.length-1]);
	},

	_getStyleParentNodes : function(sNewSpanMarker){
		this._splitTextEndNodesOfTheRange();

		var oSNode = this.getStartNode();
		var oENode = this.getEndNode();

		var aAllNodes = this._getNodesInRange();
		var aResult = [];
		var nResult = 0;

		var oNode, oTmpNode, iStartRelPos, iEndRelPos, $span, iSIdx, iEIdx;
		var nInitialLength = aAllNodes.length;
		var arAllBottmNodes = array_filter(aAllNodes, function(v){return (!v.firstChild);});

		for(var i=0; i<nInitialLength; i++){
			oNode = aAllNodes[i];

			if(!oNode) continue;
			if(oNode[_nt_] != 3) continue;
			if(oNode.nodeValue == "") continue;

			var oParentNode = dp(oNode);

			if(oParentNode.tagName == "SPAN"){
				// check if the SPAN element is fully contained
				// do quick checks before trying indexOf() because indexOf() function is very slow
				oTmpNode = this._getVeryFirstRealChild(oParentNode);
				if(oTmpNode == oNode) iSIdx = 1;
				else iSIdx = arAllBottmNodes.indexOf(oTmpNode);

				if(iSIdx != -1){
					oTmpNode = this._getVeryLastRealChild(oParentNode);
					if(oTmpNode == oNode) iEIdx = 1;
					else iEIdx = arAllBottmNodes.indexOf(oTmpNode);
				}

				if(iSIdx != -1 && iEIdx != -1){
					aResult[nResult++] = oParentNode;
					continue;
				}
			}

			aResult[nResult++] = $span = $(oNode).wrap('<span />').parent()[0];

			if(sNewSpanMarker) $span.attr(sNewSpanMarker, "true");
		}

		this.setStartBefore(oSNode);
		this.setEndAfter(oENode);

		return aResult;
	},

	_getVeryFirstChild : function(oNode){
		if(oNode.firstChild) return this._getVeryFirstChild(oNode.firstChild);
		return oNode;
	},

	_getVeryLastChild : function(oNode){
		if(oNode.lastChild) return this._getVeryLastChild(oNode.lastChild);
		return oNode;
	},

	_getFirstRealChild : function(oNode){
		var oFirstNode = oNode.firstChild;
		while(oFirstNode && oFirstNode[_nt_] == 3 && oFirstNode.nodeValue == "") oFirstNode = oFirstNode[_ns_];

		return oFirstNode;
	},

	_getLastRealChild : function(oNode){
		var oLastNode = oNode.lastChild;
		while(oLastNode && oLastNode[_nt_] == 3 && oLastNode.nodeValue == "") oLastNode = oLastNode[_ps_];

		return oLastNode;
	},

	_getVeryFirstRealChild : function(oNode){
		var oFirstNode = this._getFirstRealChild(oNode);
		if(oFirstNode) return this._getVeryFirstRealChild(oFirstNode);
		return oNode;
	},
	_getVeryLastRealChild : function(oNode){
		var oLastNode = this._getLastRealChild(oNode);
		if(oLastNode) return this._getVeryLastChild(oLastNode);
		return oNode;
	},

	_getLineStartInfo : function(node){
		var frontEndFinal = null;
		var frontEnd = node;
		var lineBreaker = node;
		var bParentBreak = true;

		// vertical(parent) search
		function getLineStart(node){
			if(!node) return;
			if(frontEndFinal) return;

			if(rxLineBreaker.test(node.tagName)){
				lineBreaker = node;
				frontEndFinal = frontEnd;

				bParentBreak = true;

				return;
			}else{
				frontEnd = node;
			}

			getFrontEnd(node[_ps_]);

			if(frontEndFinal) return;
			getLineStart(dp(node));
		}

		// horizontal(sibling) search
		function getFrontEnd(node){
			if(!node) return;
			if(frontEndFinal) return;

			if(rxLineBreaker.test(node.tagName)){
				lineBreaker = node;
				frontEndFinal = frontEnd;

				bParentBreak = false;
				return;
			}

			if(node.firstChild && node.tagName != "TABLE"){
				var curNode = node.lastChild;
				while(curNode && !frontEndFinal){
					getFrontEnd(curNode);

					curNode = curNode[_ps_];
				}
			}else{
				frontEnd = node;
			}

			if(!frontEndFinal){
				getFrontEnd(node[_ps_]);
			}
		}

		getLineStart(node);

		return {oNode: frontEndFinal, oLineBreaker: lineBreaker, bParentBreak: bParentBreak};
	},

	_getLineEndInfo : function(node){
		var backEndFinal = null;
		var backEnd = node;
		var lineBreaker = node;
		var bParentBreak = true;

		// vertical(parent) search
		function getLineEnd(node){
			if(!node) return;
			if(backEndFinal) return;

			if(rxLineBreaker.test(node.tagName)){
				lineBreaker = node;
				backEndFinal = backEnd;

				bParentBreak = true;

				return;
			}else{
				backEnd = node;
			}

			getBackEnd(node[_ns_]);
			if(backEndFinal) return;

			getLineEnd(dp(node));
		}

		// horizontal(sibling) search
		function getBackEnd(node){
			if(!node) return;
			if(backEndFinal) return;

			if(rxLineBreaker.test(node.tagName)){
				lineBreaker = node;
				backEndFinal = backEnd;

				bParentBreak = false;

				return;
			}

			if(node.firstChild && node.tagName != "TABLE"){
				var curNode = node.firstChild;
				while(curNode && !backEndFinal){
					getBackEnd(curNode);

					curNode = curNode[_ns_];
				}
			}else{
				backEnd = node;
			}

			if(!backEndFinal){
				getBackEnd(node[_ns_]);
			}
		}

		getLineEnd(node);

		return {oNode: backEndFinal, oLineBreaker: lineBreaker, bParentBreak: bParentBreak};
	},

	getLineInfo : function(){
		var oSNode = this.getStartNode();
		var oENode = this.getEndNode();

		// the range is currently collapsed
		if(!oSNode) oSNode = this.getNodeAroundRange(true, true);
		if(!oENode) oENode = this.getNodeAroundRange(true, true);

		var oStart = this._getLineStartInfo(oSNode);
		var oStartNode = oStart.oNode;
		var oEnd = this._getLineEndInfo(oENode);
		var oEndNode = oEnd.oNode;

		var iRelativeStartPos = this._compareEndPoint(dp(oStartNode), this._getPosIdx(oStartNode), this[_ec_], this[_eo_]);
		var iRelativeEndPos = this._compareEndPoint(dp(oEndNode), this._getPosIdx(oEndNode)+1, this[_sc_], this[_so_]);

		if(!(iRelativeStartPos <= 0 && iRelativeEndPos >= 0)){
			oSNode = this.getNodeAroundRange(false, true);
			oENode = this.getNodeAroundRange(false, true);
			oStart = this._getLineStartInfo(oSNode);
			oEnd = this._getLineEndInfo(oENode);
		}

		return {oStart: oStart, oEnd: oEnd};
	}
});
/**
 * }}} HuskyRange
 */

/**
 * {{{ @class SimpleSelection
 * @brief Cross-browser selection function
 */
function SimpleSelection(win){
	this.init();

	if(!this._oSelection) this.selectionLoaded = false;
};
$.extend(SimpleSelection.prototype, {
	selectionLoaded : true,
	selectRange : function(oRng) {
		this.selectNone();
		this.addRange(oRng);
	}
});

function SimpleSelectionImpl_FF() { };
$.extend(SimpleSelectionImpl_FF.prototype, {
	init : function() {
		this._oSelection = window.getSelection();
	},
	getRangeAt : function(iNum) {
		iNum = iNum || 0;

		try{
			var oFFRange = this._oSelection.getRangeAt(iNum);
		}catch(e){return new W3CDOMRange();}

		return this._FFRange2W3CRange(oFFRange);
	},
	addRange : function(oW3CRange){
		var oFFRange = this._W3CRange2FFRange(oW3CRange);
		this._oSelection.addRange(oFFRange);
	},
	selectNone : function() {
		this._oSelection.removeAllRanges();
	},
	_FFRange2W3CRange : function(oFFRange){
		var oW3CRange = new W3CDOMRange();
		oW3CRange.setStart(oFFRange[_sc_], oFFRange[_so_]);
		oW3CRange.setEnd(oFFRange[_ec_], oFFRange[_eo_]);
		return oW3CRange;
	},
	_W3CRange2FFRange : function(oW3CRange){
		var oFFRange = d.createRange();
		oFFRange.setStart(oW3CRange[_sc_], oW3CRange[_so_]);
		oFFRange.setEnd(oW3CRange[_ec_], oW3CRange[_eo_]);

		return oFFRange;
	}
});

function SimpleSelectionImpl_IE(){ };
$.extend(SimpleSelectionImpl_IE.prototype, {
	init : function() {
		this._oSelection = d.selection;
	},
	getRangeAt : function(iNum) {
		iNum = iNum || 0;

		if(this._oSelection.type == "Control"){
			var oW3CRange = new W3CDOMRange();
			var oSelectedNode = this._oSelection.createRange().item(iNum);

			// if the selction occurs in a different document, ignore
			if(!oSelectedNode || oSelectedNode.ownerDocument != d) return oW3CRange;

			oW3CRange.selectNode(oSelectedNode);

			return oW3CRange;
		}else{
			var oSelectedNode = this._oSelection.createRangeCollection().item(iNum).parentElement();

			// if the selction occurs in a different document, ignore
			if(!oSelectedNode || oSelectedNode.ownerDocument != d){
				var oW3CRange = new W3CDOMRange();
				return oW3CRange;
			}
			return this._IERange2W3CRange(this._oSelection.createRangeCollection().item(iNum));
		}
	},
	addRange : function(oW3CRange){
		var oIERange = this._W3CRange2IERange(oW3CRange);
		oIERange.select();
	},
	selectNone : function(){
		this._oSelection.empty();
	},
	_W3CRange2IERange : function(oW3CRange){
		var oStartIERange = this._getIERangeAt(oW3CRange[_sc_], oW3CRange[_so_]);
		var oEndIERange = this._getIERangeAt(oW3CRange[_ec_], oW3CRange[_eo_]);
		oStartIERange.setEndPoint("EndToEnd", oEndIERange);

		return oStartIERange;
	},
	_getIERangeAt : function(oW3CContainer, iW3COffset){
		var oIERange = d.body.createTextRange();

		var oEndPointInfoForIERange = this._getSelectableNodeAndOffsetForIE(oW3CContainer, iW3COffset);

		var oSelectableNode = oEndPointInfoForIERange.oSelectableNodeForIE;
		var iIEOffset = oEndPointInfoForIERange.iOffsetForIE;

		oIERange.moveToElementText(oSelectableNode);
		oIERange.collapse(oEndPointInfoForIERange.bCollapseToStart);
		oIERange.moveStart("character", iIEOffset);

		return oIERange;
	},
	_getSelectableNodeAndOffsetForIE : function(oW3CContainer, iW3COffset){
		var oIERange = d.body.createTextRange();

		var oNonTextNode = null;
		var aChildNodes =  null;
		var iNumOfLeftNodesToCount = 0;

		if(oW3CContainer[_nt_] == 3){
			oNonTextNode = dp(oW3CContainer);
			aChildNodes = dc(oNonTextNode);
			iNumOfLeftNodesToCount = aChildNodes.length;
		}else{
			oNonTextNode = oW3CContainer;
			aChildNodes = dc(oNonTextNode);
			iNumOfLeftNodesToCount = iW3COffset;
		}

		var oNodeTester = null;

		var iResultOffset = 0;

		var bCollapseToStart = true;

		for(var i=0; i<iNumOfLeftNodesToCount; i++){
			oNodeTester = aChildNodes[i];

			if(oNodeTester[_nt_] == 3){
				if(oNodeTester == oW3CContainer) break;

				iResultOffset += oNodeTester.nodeValue.length;
			}else{
				oIERange.moveToElementText(oNodeTester);
				oNonTextNode = oNodeTester;
				iResultOffset = 0;

				bCollapseToStart = false;
			}
		}

		if(oW3CContainer[_nt_] == 3) iResultOffset += iW3COffset;

		return {oSelectableNodeForIE:oNonTextNode, iOffsetForIE: iResultOffset, bCollapseToStart: bCollapseToStart};
	},
	_IERange2W3CRange : function(oIERange){
		var oW3CRange = new W3CDOMRange();

		var oIEPointRange = null;
		var oPosition = null;

		oIEPointRange = oIERange.duplicate();
		oIEPointRange.collapse(true);

		oPosition = this._getW3CContainerAndOffset(oIEPointRange, true);

		oW3CRange.setStart(oPosition.oContainer, oPosition.iOffset);

		var oCollapsedChecker = oIERange.duplicate();
		oCollapsedChecker.collapse(true);
		if(oCollapsedChecker.isEqual(oIERange)){
			oW3CRange.collapse(true);
		}else{
			oIEPointRange = oIERange.duplicate();
			oIEPointRange.collapse(false);
			oPosition = this._getW3CContainerAndOffset(oIEPointRange);
			oW3CRange.setEnd(oPosition.oContainer, oPosition.iOffset);
		}

		return oW3CRange;
	},
	_getW3CContainerAndOffset : function(oIEPointRange, bStartPt){
		var oRgOrigPoint = oIEPointRange;

		var oContainer = oRgOrigPoint.parentElement();
		var offset = -1;

		var oRgTester = d.body.createTextRange();
		var aChildNodes = dc(oContainer);
		var oPrevNonTextNode = null;
		var pointRangeIdx = 0;

		for(var i=0;i<aChildNodes.length;i++){
			if(aChildNodes[i][_nt_] == 3) continue;

			oRgTester.moveToElementText(aChildNodes[i]);

			if(oRgTester.compareEndPoints("StartToStart", oIEPointRange)>=0) break;

			oPrevNonTextNode = aChildNodes[i];
		}

		var pointRangeIdx = i;

		if(pointRangeIdx != 0 && aChildNodes[pointRangeIdx-1][_nt_] == 3){
			var oRgTextStart = d.body.createTextRange();
			var oCurTextNode = null;
			if(oPrevNonTextNode){
				oRgTextStart.moveToElementText(oPrevNonTextNode);
				oRgTextStart.collapse(false);
				oCurTextNode = oPrevNonTextNode.nextSibling;
			}else{
				oRgTextStart.moveToElementText(oContainer);
				oRgTextStart.collapse(true);
				oCurTextNode = oContainer.firstChild;
			}

			var oRgTextsUpToThePoint = oRgOrigPoint.duplicate();
			oRgTextsUpToThePoint.setEndPoint("StartToStart", oRgTextStart);

			var textCount = oRgTextsUpToThePoint.text.length

			while(textCount > oCurTextNode.nodeValue.length && oCurTextNode.nextSibling){
				textCount -= oCurTextNode.nodeValue.length;
				oCurTextNode = oCurTextNode.nextSibling;
			}

			// this will enforce IE to re-reference oCurTextNode
			var oTmp = oCurTextNode.nodeValue;

			if(bStartPt && oCurTextNode.nextSibling && oCurTextNode.nextSibling[_nt_] == 3 && textCount == oCurTextNode.nodeValue.length){
				textCount -= oCurTextNode.nodeValue.length;
				oCurTextNode = oCurTextNode.nextSibling;
			}

			oContainer = oCurTextNode;
			offset = textCount;
		}else{
			oContainer = oRgOrigPoint.parentElement();
			offset = pointRangeIdx;
		}

		return {"oContainer" : oContainer, "iOffset" : offset};
	}
});
$.extend(SimpleSelection.prototype, ($.browser.msie?SimpleSelectionImpl_IE:SimpleSelectionImpl_FF).prototype);
/**
 * }}} SimpleSelection
 */

// {{{ DOMFix
DOMFix = {
	init : function(){
		if ($.browser.msie || $.browser.opera) {
			this[_cn_] = this._childNodes_Fix;
			this[_pn_] = this._parentNode_Fix;
		} else {
			this[_cn_] = this._childNodes_Native;
			this[_pn_] = this._parentNode_Native;
		}
	},

	_parentNode_Native : function(elNode){
		return elNode[_pn_];
	},

	_parentNode_Fix : function(elNode){
		if(!elNode) return elNode;

		while(elNode[_ps_]){ elNode = elNode[_ps_]; }

		return elNode[_pn_];
	},

	_childNodes_Native : function(elNode){
		return elNode[_cn_];
	},

	_childNodes_Fix : function(elNode){
		var aResult = null;
		var nCount = 0;

		if(elNode){
			var aResult = [];
			elNode = elNode.firstChild;
			while(elNode){
				aResult[nCount++] = elNode;
				elNode=elNode[_ns_];
			}
		}

		return aResult;
	}
};
DOMFix.init();
dp = DOMFix[_pn_];
dc = DOMFix[_cn_];
// }}}

/**
 * }}} Selection
 */

/**
 * {{{ Utility functions
 */
// bind a function to the host object
function bind(obj, func) {
	return function(){ return func.apply(obj, arguments); };
};

// check block element
function is_block(el) {
	return (el && el[_nt_] == 1 && rx_block.test(el[_nn_]));
};

// is defined?
function is_def(v){ return typeof(v)!='undefined'; };

// is string?
function is_str(v){ return typeof(v)=='string'; };

// filter
function array_filter(arr, fn) {
	var ret=[], i, c;

	for(i=0,c=arr.length; i < c; i++) {
		if (fn(arr[i])) ret.push(arr[i]);
	}

	return ret;
};

// node index
function node_index(node) {
	var cs = node[_pn_][_cn_], c, i;

	for(i=0,c=cs.length; i < c; i++) {
		if (cs[i] === node) return i;
	}

	return -1;
};

// block parent
function get_block_parent(nd) {
	while(!is_block(nd)) {
		if (rx_root.test(nd[_pn_].className)) break;
		nd = nd[_pn_];
	}

	if (!is_block(nd)) {
		var ss = $.merge(siblings(nd,1),nd,siblings(nd)), $p = $('<p />');

		nd[_pn_].insertBefore($p[0], nd);
		nd = $p.append(ss)[0];
	}

	return nd;
};

function get_child(node, container) {
	if (node == container) return node;

	while(node) {
		if (node[_pn_] == container) return node;
		node = node[_pn_];
	}
};

/**
 * @brief Get a valid parent (evaluate with XHTML)
 * @param par Element initial parent node
 * @param childName String child node name
 */
function get_valid_parent(par, childName) {
	while(!XHTMLT[par[_pn_][_nn_].toLowerCase()][childName]) {
		par = par[_pn_];
	}
	return par;
};

// collect all inline sibling
function siblings(node, prev, stopper) {
	var s, ret = [];
	
	if (!stopper) stopper = is_block;

	while(s=node[prev?_ps_:_ns_]) {
		if (stopper(s)) break;
		if (s[_nt_] == 3 || s[_nt_] == 1) ret.push(s);
		node = s;
	}

	if (prev) ret = ret.reverse();

	return ret;
};

function is_ancestor_of(anc, desc) {
	while(desc && !rx_root.test(desc.className||'')) {
		if ((desc=desc[_pn_]) == anc) return true;
	}
	return false;
};

/**
 * }}}
 */

// global context
if (xe.Xeed) xe.Xeed = $.extend(Xeed, xe.Xeed);
else xe.Xeed = Xeed;

xe.W3CDOMRange = W3CDOMRange;
xe.HuskyRange  = HuskyRange;
xe.Xeed.AutoSave = AutoSave;

// run callback functions
if ($.isArray(xe.Xeed.callbacks) && xe.Xeed.callbacks.length) {
	while(fn = xe.Xeed.callbacks.shift()) fn();
}

})(jQuery);
