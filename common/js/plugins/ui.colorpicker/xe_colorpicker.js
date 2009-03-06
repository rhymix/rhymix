/**
 * @brief XE Colorpicker
 * @author mygony (http://mygony.com)
 **/
jQuery(function($){
	var ready  = false;
	var tmp    = $('<span>').hide();
	// var panel  = null;
	
	$.fn.xe_colorpicker = function(settings){
		var selection = this;

		if (!ready) {
			ColorPicker.init(settings);
			ready = true;
		}
		
		this.each(function(){
			var col = color($(this).val());

			$(this).val( col ).css('background-color', col );
			setTextColor( $(this) );
		}).focus(function(event){
			var t = this;
			$(this).select();
			
			// show color picker
			ColorPicker.show(this);
		}).keypress(function(event){
			if (!ColorPicker.is(':visible')) return;
			
			if (/^#?[0-9a-f]{6}$/i.test( event.target.value )) {
				ColorPicker.color( event.target.value );
			}
		});
		
		$(document).mousedown(function(event){
			var target = event.target;
			
			if (selection.index(target) > -1) return;
			if ($(target).parents().add(target).index(ColorPicker.element) > -1) return;
			if ($(target).parents().add(target).index(ColorPicker.buttons) > -1) return;
			
			ColorPicker.hide();
		});

		return this;
	};
	
	var ColorPicker = {
		element  : null,
		picker   : null,
		colpane: null,
		buttons  : null,
		_target  : null,
		_backup  : null,
		_hsv     : null,
		_mode    : 'none',

		init : function() {
			var cp = this;
			
			this.element = $('<div class="xe_colorpicker"><div class="colorpicker"><div class="colortable"><div class="background"><div class="indicator"></div></div></div><div class="huebar"><div class="background"><div class="indicator"></div></div></div></div><div class="buttons"><button type="button" class="ok">OK</button><button type="button" class="cancel">Cancel</button><button type="button" class="none">None</button></div></div>');
			
			this.picker    = this.element.find('> div.colorpicker');
			this.colpane   = this.picker.find('div.colortable > div.background');
			this.colpoint  = this.colpane.find('> .indicator');
			this.buttons   = this.element.find('> div.buttons');
			this.huepane   = this.element.find('div.huebar > .background');
			this.huepoint  = this.huepane.find('> .indicator');
			
			this._mousedown = method(this.onmousedown, this);
			this._mousemove = method(this.onmousemove, this);
			this._mouseup   = method(this.onmouseup, this);
			
			this.picker.find('.background').mousedown(this._mousedown);
			
			this.buttons.find('button.ok').click(method(this.ok,this));
			this.buttons.find('button.cancel').click(method(this.cancel,this));
			this.buttons.find('button.none').click(method(this.none,this));
			
			// only for IE6
			if ($.browser.msie && parseInt($.browser.version) < 7) {
				this.element.append( $('<iframe>').css({position:'absolute','z-index':-1,left:0,top:0,width:9999,height:9999}) );
			}
		},
		show : function(input) {
			var pos = (input=$(input)).offset(), pos_panel;
			//var par = input.get(0).offsetParent;
			var par = $("body").get(0);
			var btn = this.buttons.hide();
			var col = color(input.val());
			
			this._target = input;
			this._backup = col;
			
			this.color(col);
			this._target.val(col);

			pos_panel = this.element.hide().css({'z-index':99999,left:0,top:0}).appendTo( par ).show(300,function(){btn.slideDown(150)}).offset();
			this.element.css({left:pos.left-pos_panel.left,top:pos.top-pos_panel.top+input.get(0).offsetHeight});
		},
		hide : function() {
			var e = this.element;
			
			this._target = null;
			this.buttons.slideUp(100, function(){e.hide(200)});
		},
		visible : function() {
			return this.element.is(':visible');
		},
		color : function(sColor) {
			if (typeof sColor == 'string') {
				var col = color(sColor);
				var hsv = _hsv(rgb2hsv(hex2rgb(col)));
				
				this.hsv(hsv.h, hsv.s, hsv.v);	
			} else if (this._target) {
				return color(this._target.val());
			}
		},
		hsv : function(h, s, v) {
			var col = rgb2hex(hsv2rgb(h, s, v));

			this._hsv = _hsv(h, s, v);
			
			// background color and text color
			this._target.val(col).css('background-color', col);
			setTextColor(this._target);
			
			// hue bar indicator
			if (this._hue_h) this.huepoint.css('top', limit(0, Math.round((360-this._hsv.h)/360*this._hue_h), this._hue_h-1) - 3 );
			
			// color - background
			this.colpane.css('background-color', rgb2hex(hsv2rgb(h, 100, 100)) );
			
			// color - indicator
			if (this._col_h && this._col_w) {
				this.colpoint.css({
					top  : limit(0, Math.round((100-this._hsv.v)/100*this._col_h), this._col_h-1) - 5,
					left : limit(0, Math.round(this._hsv.s/100*this._col_w), this._col_w-1 ) - 5
				});
			}
		},
		onmousedown : function(event) {
			var cur = $(event.target);
			var par = cur.parent();
			var pos = cur.offset();
			var hue, sat, val; // hue, saturation, value
			
			this._height = cur.height();
			this._width  = cur.width();
			this._top    = pos.top;
			this._left   = pos.left;

			this._col_w  = this.colpane.width();
			this._col_h  = this.colpane.width();
			this._hue_h  = this.huepane.height();
			
			if (par.is('.colortable')) {
				this._mode = 'color';
				hue = this._hsv.h;
				sat = ( limit(0, (event.pageX - this._left), this._width ) / this._width * 100);
				val = ( limit(0, (this._height - event.pageY + this._top), this._height ) / this._height * 100);
			} else if (par.is('.huebar')) {
				this._mode = 'hue';
				hue = limit(0, (this._height - event.pageY + this._top), this._height) / this._height * 360;
				sat = this._hsv.s;
				val = this._hsv.v;
			}
			
			this.hsv( hue, sat, val );
			
			$(document).bind('mousemove', this._mousemove).bind('mouseup', this._mouseup);
		},
		onmousemove : function(event) {
			var hue, sat, val; // hue, saturation, value

			switch(this._mode) {
				case 'color':
					hue = this._hsv.h;
					sat = ( limit(0, (event.pageX - this._left), this._width ) / this._width * 100);
					val = ( limit(0, (this._height - event.pageY + this._top), this._height ) / this._height * 100);
					break;
				case 'hue':
					hue = limit(0, (this._height - event.pageY + this._top), this._height) / this._height * 360;
					sat = this._hsv.s;
					val = this._hsv.v;
					break;
			}
			
			this.hsv( hue, sat, val );
		},
		onmouseup : function(event) {
			this._mode = 'none';
			$(document).unbind('mousemove', this._mousemove).unbind('mouseup', this._mouseup);
		},
		ok : function() {
			this.hide();
		},
		cancel : function() {
			this.color(this._backup);
			this.hide();
		},
		none : function() {
            this._target.attr('value','transparent');
			this.hide();
		}
	};
	
	function setTextColor(input) {
		var hex = input.css('color', '').val(), hsv, rgb;
		
		if (hex == 'transparent' || hex == '') return;
		
		rgb = hex2rgb(hex);
		hsv = rgb2hsv(255-rgb.r, 255-rgb.g, 255-rgb.b); // 보색을 구한 뒤
		hex = rgb2hex(hsv2rgb(0, 0, hsv.v>50?100:0)); // 보색에 해당하는 흑백으로 결정
		
		input.css('color', hex);
	}
	
	function method(func, thisObj) {
		return function() { return func.apply(thisObj, arguments) }
	}
	
	function color(str) {
		var col = $.trim(str);
		var regHex1 = /^#[0-9a-f]{6}$/i;
		var regHex2 = /^#?([0-9a-f])([0-9a-f])([0-9a-f])$/i; // short hex
		
		if (regHex1.test(col)) return col.toUpperCase();
		if (regHex2.test(col)) return col.replace(regHex2, '#$1$1$2$2$3$3').toUpperCase();
		
		try {
			col = tmp.appendTo($('<body>')).css('background-color', col).css('background-color');
		} catch(e) {
			col = '#FFFFFF';
		} finally {
			tmp.css('background-color','').remove();
		}
		
		if (/^rgb\(([0-9, ]+)\)$/i.test(col)) col = rgb2hex(RegExp.$1.split(/,\s*/));
		if (!/#[0-9a-f]{6}/i.test(col)) col = '#FFFFFF';
		
		return col;
	}
	
	function _rgb(rgb, _g, _b) {
		var r, g, b;
		
		if (typeof arguments[2] == "number") {
			r = rgb;
			g = _g;
			b = _b;
		} else if (typeof rgb == "object") {
			if (rgb.constructor == Array) {
				r = rgb[0] || 0; g = rgb[1] || 0; b = rgb[2] || 0;
			} else {
				r = rgb.r || 0; g = rgb.g || 0; b = rgb.b || 0;
			}
		}
		
		rgb = [];
		
		rgb.r = rgb[0] = r = parseInt(r, 10);
		rgb.g = rgb[1] = g = parseInt(g, 10);
		rgb.b = rgb[2] = b = parseInt(b, 10);
		
		return rgb;
	}
	
	function _hsv(hsv, _s, _v) {
		var h, s, v;
		
		if (typeof arguments[2] == "number") {
			h = hsv; s = _s; v = _v;
		} else if (typeof hsv == "object") {
			if (hsv.constructor == Array) {
				h = hsv[0] || 0; s = hsv[1] || 0; v = hsv[2] || 0;
			} else {
				h = hsv.h || 0; s = hsv.s || 0; v = hsv.v || 0;
			}
		}
		
		hsv = [];
		
		hsv.h = hsv[0] = h = parseInt(h, 10);
		hsv.s = hsv[1] = s = parseInt(s, 10);
		hsv.v = hsv[2] = v = parseInt(v, 10);
		
		return hsv;
	}
	
	function rgb2hex(rgb, _g, _b) {
		var rgb = _rgb(rgb, _g, _b);
		
		for(var i=0; i < rgb.length; i++) {
			(rgb[i] = Number(rgb[i]).toString(16)).length<2?rgb[i]='0'+rgb[i]:0;
		}
		
		return '#'+rgb.join('').toUpperCase();
	}
	
	function hex2rgb(hex) {
		var r=0, g=0, b=0;
		
		if (/^#?([0-9a-f]{1,2})([0-9a-f]{1,2})([0-9a-f]{1,2})$/i.test(hex)) {
			r = parseInt(RegExp.$1, 16);
			g = parseInt(RegExp.$2, 16);
			b = parseInt(RegExp.$3, 16);
		}
		
		return _rgb(r, g, b);
	}
	
	function hsv2rgb(hsv, _s, _v) {
		var r=0, g=0, b=0;
		var h=0, s=0, v=0;
		var i, f, p, q, t;
		
		hsv = _hsv(hsv, _s, _v);

		h = (hsv[0] % 360) / 60; s = hsv[1] / 100; v = hsv[2] / 100;
		
		i = Math.floor(h);
		f = h-i;
		p = v*(1-s);
		q = v*(1-s*f);
		t = v*(1-s*(1-f));

		switch (i) {
			case 0: r=v; g=t; b=p; break;
			case 1: r=q; g=v; b=p; break;
			case 2: r=p; g=v; b=t; break;
			case 3: r=p; g=q; b=v; break;
			case 4: r=t; g=p; b=v; break;
			case 5: r=v; g=p; b=q; break;
			case 6: break;
		}
		
		return _rgb(Math.floor(r*255), Math.floor(g*255), Math.floor(b*255));
	}
	
	function rgb2hsv(rgb, _g, _b) {
		var rgb = _rgb(rgb, _g, _b);
		var r = rgb[0], g = rgb[1], b = rgb[2];
		var h = 0, s = 0, v = Math.max(r,g,b), min = Math.min(r,g,b), delta = v - min;

		if (s = v?delta/v:0) {
			if (r == v) h = 60 * (g - b) / delta;
			else if (g == v) h = 120 + 60 * (b - r) / delta;
			else if (b == v) h = 240 + 60 * (r - g) / delta;
			
			if (h < 0) h += 360;
		}
		
		return _hsv(Math.floor(h), Math.floor(s*100), Math.floor(v/255*100));
	}
	
	function limit(min, val, max){
		return Math.min(Math.max(min, val), max);
	}
	
	$('input.color-indicator').xe_colorpicker();
});
