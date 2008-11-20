/**
 * Jindo2 Framework
 * @version 1.0.5
 */
/**
 * Core object
 *
 */
if (typeof window != "undefined" && typeof window.nhn == "undefined") {
	window.nhn = new Object;
}

/**
 * 지정된 id 를 가지는 객체를 반환한다.
 * argument를 복수개로 지정하면 배열로 객체를 반환하며,
 * 아이디에 해당하는 객체가 존재하지 않으면 null 을 반환한다.
 * 또한 "<tagName>" 과 같은 형식의 문자열을 입력하면 tagName을 가지는 객체를 생성한다.
 * @id core.$
 * @param {String} 객체의 아이디(복수개 가능)
 * @return element
 */
function $(sID/*, id1, id2*/) {
	var ret = new Array;
	var el  = null;
	var reg = /^<([a-z]+|h[1-5])>$/i;

	for(var i=0; i < arguments.length; i++) {
		el = arguments[i];
		if (typeof el == "string") {
			if (reg.test(el)) {
				el = document.createElement(RegExp.$1);
			} else {
				el = document.getElementById(el);
			}
		}
		if (el) ret[ret.length] = el;
	}
	return ret.length?((arguments.length>1)?ret:ret[0]):null;
}

/**
 * 클래스 타입을 정의한다. 생성자는 $init 으로 정의한다.
 * @id core.$Class
 * @param {object} 클래스 정의 object	
 * @return {class} 클래스 타입
 */
 function $Class(oDef) {
	function typeClass() {
		var t = this;
		var a = [];

		while(typeof t.$super != "undefined") {
			t.$super.$this = this;
			if (typeof t.$super.$init == "function") a[a.length] = t;
			t = t.$super;
		}
		
		for(var i=a.length-1; i > -1; i--) a[i].$super.$init.apply(a[i].$super, arguments);

		if (typeof this.$init == "function") this.$init.apply(this,arguments);
	}
	
	if (typeof oDef.$static != "undefined") {
		var i=0, x;
		for(x in oDef) x=="$static"||i++;
		for(x in oDef.$static) typeClass[x] = oDef.$static[x];

		if (!i) return oDef.$static;
		delete oDef.$static;
	}

	typeClass.prototype = oDef;
	typeClass.prototype.constructor = typeClass;
	typeClass.extend = $Class.extend;

	return typeClass;
 }

/**
 * 클래스를 상속한다.
 * 상속된 클래스에서 this.$super.method 로 상위 메소드에 접근할 수도 있으나,
 * this.$super.$super.method 와 같이 한 단계 이상의 부모 클래스에는 접근할 수 없다.
 * @id core.$Class.extend
 * @import core.$Class
 * @param {class} 수퍼 클래스 객체
 * @return {class} 확장된 클래스 타입
 */
 $Class.extend = function(superClass) {
	this.prototype.$super = new Object;

	var superFunc = function(m, func) {
		return function() {
			var r;
			var f = this.$this[m];
			var t = this.$this;
			t[m] = func;
			r = t[m].apply(t, arguments);
			t[m] = f;

			return r;
		};
	};

	for(var x in superClass.prototype) {
		if (typeof this.prototype[x] == "undefined" && x !="$init") this.prototype[x] = superClass.prototype[x];
		if (typeof superClass.prototype[x] == "function") {
			this.prototype.$super[x] = superFunc(x, superClass.prototype[x]);
		} else {
			this.prototype.$super[x] = superClass.prototype[x];
		}
	}

	// inherit static methods of parent
	for(var x in superClass) {
		if (x == "prototype") continue;
		this[x] = superClass[x];
	}

	return this;
};
/////
/**
 * Agent 객체를 반환한다. Agent 객체는 브라우저와 OS에 대한 정보를 알 수 있도록 한다.
 * @id core.$Agent
 */
function $Agent() {
	var cl = arguments.callee;
	var cached = cl._cached;
	
	if (cl._cached) return cl._cached;
	if (!(this instanceof cl)) return new cl;
	if (typeof cl._cached == "undefined") cl._cached = this;
}

/**
 * 웹브라우저에 대한 정보 객체를 반환한다.
 * @id core.$Agent.navigator
 * @return {TypeNavigatorInfo} 웹브라우저 정보 객체
 */
$Agent.prototype.navigator = function() {
	var info = new Object;
	var ver  = -1;
	var u    = navigator.userAgent;
	var v    = navigator.vendor || "";
	
	function f(s,h){ return ((h||"").indexOf(s) > -1) };

	info.opera     = (typeof window.opera != "undefined") || f("Opera",u);
	info.ie        = !info.opera && f("MSIE",u);
	info.chrome    = f("Chrome",u);
	info.safari    = !info.chrome && f("Apple",v);
	info.mozilla   = f("Gecko",u) && !info.safari && !info.chrome;
	info.firefox   = f("Firefox",u);
	info.camino    = f("Camino",v);
	info.netscape  = f("Netscape",u);
	info.omniweb   = f("OmniWeb",u);
	info.icab      = f("iCab",v);
	info.konqueror = f("KDE",v);

	try {
		if (info.ie) {
			ver = u.match(/(?:MSIE) ([0-9.]+)/)[1];
		} else if (info.firefox||info.opera||info.omniweb) {
			ver = u.match(/(?:Firefox|Opera|OmniWeb)\/([0-9.]+)/)[1];
		} else if (info.mozilla) {
			ver = u.match(/rv:([0-9.]+)/)[1];
		} else if (info.safari) {
			ver = parseFloat(u.match(/Safari\/([0-9.]+)/)[1]);
			if (ver == 100) {
				ver = 1.1;
			} else {
				ver = [1.0,1.2,-1,1.3,2.0,3.0][Math.floor(ver/100)];
			}
		} else if (info.icab) {
			ver = u.match(/iCab[ \/]([0-9.]+)/)[1];
		} else if (info.chrome) {
			ver = u.match(/Chrome[ \/]([0-9.]+)/)[1];
		}

		info.version = parseFloat(ver);
		if (isNaN(info.version)) info.version = -1;
	} catch(e) {
		info.version = -1;
	}

	$Agent.prototype.navigator = function() {
		return info;
	};

	return info;
};

/**
 * OS에 대한 정보객체를 반환한다.
 * @id core.$Agent.os
 * @return {TypeOSInfo} OS 정보 객체
 */
$Agent.prototype.os = function() {
	var info = new Object;
	var u    = navigator.userAgent;
	var p    = navigator.platform;
	var f    = function(s,h){ return (h.indexOf(s) > -1) };

	info.win     = f("Win",p);
	info.mac     = f("Mac",p);
	info.linux   = f("Linux",p);
	info.win2000 = info.win && (f("NT 5.0",p) || f("2000",p));
	info.winxp   = info.win && (f("NT 5.1",p) || f("Win32",p));
	info.xpsp2   = info.winxp && (f("SV1",u) || f("MSIE 7",u));
	info.vista   = f("NT 6.0",p);

	$Agent.prototype.os = function() {
		return info;
	};

	return info;
};

/**
 * Flash에 대한 정보객체를 반환한다.
 * @id core.$Agent.flash
 * @return {TypeFlashInfo} Flash 정보 객체
 */
$Agent.prototype.flash = function() {
	var info = new Object;
	var p    = navigator.plugins;
	var m    = navigator.mimeTypes;
	var f    = null;

	info.installed = false;
	info.version   = -1;

	if (typeof p != "undefined" && p.length) {
		f = p["Shockwave Flash"];
		if (f) {
			info.installed = true;
			if (f.description) {
				info.version = parseFloat(f.description.match(/[0-9.]+/)[0]);
			}
		}

		if (p["Shockwave Flash 2.0"]) {
			info.installed = true;
			info.version   = 2;
		}
	} else if (typeof m != "undefined" && m.length) {
		f = m["application/x-shockwave-flash"];
		info.installed = (f && f.enabledPlugin);
	} else {
		for(var i=9; i > 1; i--) {
			try {
				f = new ActiveXObject("ShockwaveFlash.ShockwaveFlash."+i);

				info.installed = true;
				info.version   = i;
				break;
			} catch(e) {}
		}
	}

	$Agent.prototype.info = function() {
		return info;
	};

	return info;
};

/**
 * SilverLight에 대한 정보객체를 반환한다.
 * @id core.$Agent.silverlight
 */
$Agent.prototype.silverlight = function() {
	var info = new Object;
	var p    = navigator.plugins;
	var s    = null;

	info.installed = false;
	info.version   = -1;

	if (typeof p != "undefined" && p.length) {
		s = p["Silverlight Plug-In"];
		if (s) {
			info.installed = true;
		}
	} else {
		try {
			s = new ActiveXObject("AgControl.AgControl");
			info.installed = true;
		} catch(e) {}
	}

	$Agent.prototype.silverlight = function() {
		return info;
	};

	return info;
};
/**
 * 주어진 원소를 가진 배열 객체를 만든다.
 * @id core.$A
 * @import core.$A.toArray
 * @param {array} 배열 혹은 배열에 준하는 컬렉션 타입
 * @return {$A}
 */
function $A(array) {
	var cl = arguments.callee;
	
	if (typeof array == "undefined") array = [];
	if (array instanceof cl) return array;
	if (!(this instanceof cl)) return new cl(array);

	this._array = [];
	for(var i=0; i < array.length; i++) {
		this._array[this._array.length] = array[i];
	}
};

$A.prototype.toString = function() {
	return this._array.toString();
};

/**
 * 배열의 크기를 반환한다
 * @id core.$A.length
 * @return Number 배열의 크기
 * @
 */
$A.prototype.length = function(len, elem) {
	if (typeof len == "number") {
		var l = this._array.length;
		this._array.length = len;
		
		if (typeof elem != "undefined") {
			for(var i=l; i < len; i++) {
				this._array[i] = elem;
			}
		}

		return this;
	} else {
		return this._array.length;
	}
};

/**
 * 주어진 원소가 존재하는지 검사한다. 존재하면 true를, 그렇지 않으면 false를 반환한다
 * @id core.$A.has
 * @param {void} 검색할 값
 * @return Boolean
 * @import core.$A.indexOf
 */
$A.prototype.has = function(any) {
	return (this.indexOf(any) > -1);
};

/**
 * 주어진 원소가 배열에 몇 번째 요소로서 존재하는지 반환한다.
 * 배열의 인덱스는 0부터 시작한다. 해당 원소가 존재하지 않으면 -1 을 반환한다.
 * @id core.$A.indexOf
 * @param {void} 검색할 값
 * @return {Number} 검색결과 인덱스 번호
 */
$A.prototype.indexOf = function(any) {
	if (typeof this._array.indexOf != 'undefined') return this._array.indexOf(any);

	for(var i=0; i < this._array.length; i++) {
		if (this._array[i] == any) return i;
	}
	return -1;
};

/*
 * JavaScript 배열 객체를 반환한다
 * @id core.$A.$value
 * @return {Array} JavaScript 배열 객체
 */
$A.prototype.$value = function() {
	return this._array;
};

/**
 * 배열 객체에 엘리먼트를 추가한다.
 * @id core.$A.push
 * @param {void} 추가할 엘리먼트(복수개 가능)
 * @return {Number} 엘리먼트를 추가한 후의 배열 객체 크기
 */
$A.prototype.push = function(element1/*, ...*/) {
	return this._array.push.apply(this._array, $A(arguments).$value());
};

/**
 * 배열의 마지막 엘리먼트를 제거하고 제거된 엘리먼트를 반환한다.
 * @id core.$A.pop
 * @return {void} 제거된 엘리먼트
 */
$A.prototype.pop = function() {
	return this._array.pop();
};

/**
 * 배열의 첫 엘리먼트를 제거하고 제거된 엘리먼트를 반환한다.
 * @id core.$A.shift
 * @return {void} 제거된 엘리먼트
 */
$A.prototype.shift = function() {
	return this._array.shift();
};

/**
 * 주어진 한 개 이상의 엘리먼트를 배열 앞부분에 삽입하고, 해당 배열의 바뀐 크기를 반환한다.
 * @id core.$A.unshift
 * @param {void} 추가할 엘리먼트(복수개 가능)
 * @return {Nmber} 엘리먼트를 추가한 후의 배열 객체 크기
 */
$A.prototype.unshift = function(element1/*, ...*/) {
	this._array.unshift.apply(this._array, $A(arguments).$value());

	return this._array.length;
};

/**
 * 주어진 콜백함수를 배열의 각 요소에 실행한다.
 * @id core.$A.forEach
 * @import core.$A[Break, Continue]
 */
$A.prototype.forEach = function(callback, thisObject) {
	var arr         = this._array;
	var errBreak    = this.constructor.Break;
	var errContinue = this.constructor.Continue;
	
	function f(v,i,a) {
		try {
			callback.call(thisObject, v, i, a);
		} catch(e) {
			if (!(e instanceof errContinue)) throw e;
		}
	};

	if (typeof this._array.forEach == "function") {
		try {
			this._array.forEach(f);
		} catch(e) {
			if (!(e instanceof errBreak)) throw e;
		}
		return this;
	}

	for(var i=0; i < arr.length; i++) {
		try {
			f(arr[i], i, arr);
		} catch(e) {
			if (e instanceof errBreak) break;
			throw e;
			
		}
	}

	return this;
};

/**
 * 주어진 함수를 현재 객체의 모든 엘리먼트에 적용하고 현재 객체를 반환한다.
 * @id core.$A.map
 */
$A.prototype.map = function(callback, thisObject) {
	var arr         = this._array;
	var errBreak    = this.constructor.Break;
	var errContinue = this.constructor.Continue;
	
	function f(v,i,a) {
		try {
			return callback.call(thisObject, v, i, a);
		} catch(e) {
			if (e instanceof errContinue) return v;
			else throw e;
		}
	};

	if (typeof this._array.map == "function") {
		try {
			this._array = this._array.map(f);
		} catch(e) {
			if(!(e instanceof errBreak)) throw e;
		}
		return this;
	}

	for(var i=0; i < this._array.length; i++) {
		try {
			arr[i] = f(arr[i], i, arr);
		} catch(e) {
			if (e instanceof errBreak) break;
			throw e;
		}
	}

	return this;
};

/**
 * 주어진 콜백 함수를 만족시키는 요소만으로 만들어진 새로운 $A 배열을 반환한다.
 * 콜백 함수는 Boolean 값을 반환해야 한다.
 * @id core.$A.filter
 * @import core.$A.forEach
 */
$A.prototype.filter = function(callback, thisObject) {
	var ar = new Array;

	this.forEach(function(v,i,a) {
		if (callback.call(thisObject, v, i, a) === true) {
			ar[ar.length] = v;
		}
	});

	return $A(ar);
};

/**
 * 모든 배열의 원소가 주어진 콜백 함수를 만족시키는지를 검사한다.
 * 콜백함수는 Boolean 값을 반환해야 한다.
 * @id core.$A.every
 * @import core.$A.forEach
 */
$A.prototype.every = function(callback, thisObject) {
	if (typeof this._array.every != "undefined") return this._array.every(callback, thisObject);

	var result = true;
	this.forEach(function(v, i, a) {
		if (callback.call(thisObject, v, i, a) === false) {
			result = false;
			$A.Break();
		}
	});
	return result;
};

/**
 * 주어진 콜백 함수를 만족시키는 배열의 원소가 존재하는지를 검사한다.
 * 모든 배열의 요소중에서 하나라도 콜백함수를 만족시키면 이 메소드는 true를 반환한다.
 * 콜백함수는 Boolean 값을 반환해야 한다.
 * @id core.$A.every
 * @import core.$A.forEach
 */
$A.prototype.some = function(callback, thisObject) {
	if (typeof this._array.some != "undefined") return this._array.some(callback, thisObject);

	var result = false;
	this.forEach(function(v, i, a) {
		if (callback.call(thisObject, v, i, a) === true) {
			result = true;
			$A.Break();
		}
	});
	return result;
};

/**
 * 주어진 값을 제외한 새로운 $A배열을 반환한다
 * @id core.$A.refuse
 * @import core.$A.filter
 */
$A.prototype.refuse = function(value) {
	var a = $A(arguments);
	return this.filter(function(v,i) { return !a.has(v) });
};

/**
 * 주어진 시작 인덱스와 끝 인덱스까지의 배열 요소로 이루어진 새로운 $A 배열을 반환한다.
 * @id core.$A.slice
 */
$A.prototype.slice = function(start, end) {
	var a = this._array.slice.call(this._array, start, end);
	return $A(a);
};

/**
 * 특정 인덱스로부터 주어진 갯수만큼의 배열을 잘라서 반환한다.
 * @id core.$A.splice
 */
$A.prototype.splice = function(index, howMany/*, element*/) {
	var a = this._array.splice.apply(this._array, arguments);

	return $A(a);
};

/**
 * 배열의 원소를 무작위적으로 섞는다.
 * @id core.$A.shuffle
 */
$A.prototype.shuffle = function() {
	this._array.sort(function(a,b){ return Math.random()>Math.random()?1:-1 });
	
	return this;
};

/**
 * 배열에서 중복되는 원소를 제거한다.
 * @id core.$A.unique
 */
$A.prototype.unique = function() {
	var a = this._array, b = [], l = a.length;
	var i, j;

	// 중복되는 원소 제거
	for(i = 0; i < l; i++) {
		for(j = 0; j < b.length; j++) {
			if (a[i] == b[j]) break;
		}
		
		if (j >= b.length) b[j] = a[i];
	}
	
	this._array = b;
	
	return this;
};

/**
 * 배열 요소를 거꾸로 정렬한다.
 * @id core.$A.reverse
 */
$A.prototype.reverse = function() {
	this._array.reverse();

	return this;
};

/**
 * each, filter, map 메소드에서 반복구문을 중단한다.
 * @id core.$A.Break
 */
$A.Break = function() {
	if (!(this instanceof arguments.callee)) throw new arguments.callee;
};

/**
 * each, filter, map 메소드에서 현재 인덱스의 반복구문을 건너뛴다.
 * @id core.$A.Continue
 */
$A.Continue = function() {
	if (!(this instanceof arguments.callee)) throw new arguments.callee;
};
/**
 * Ajax 객체를 반환한다.
 * @id core.$Ajax
 * @import core.$Ajax.option
 */
function $Ajax(url, option) {
	var cl = arguments.callee;
	if (!(this instanceof cl)) return new cl(url, option);

	function _getXHR() {
		if (window.XMLHttpRequest) {
			return new XMLHttpRequest();
		} else if (ActiveXObject) {
			try { return new ActiveXObject('MSXML2.XMLHTTP'); }
			catch(e) { return new ActiveXObject('Microsoft.XMLHTTP'); }
			return null;
		}
	}

	var loc    = location.toString();
	var domain = '';
	try { domain = loc.match(/^https?:\/\/([a-z0-9_\-\.]+)/i)[1]; } catch(e) {}

	this._url = url;
	this._options  = new Object;
	this._headers  = new Object;
	this._options = {
		type   :"xhr",
		method :"post",
		proxy  :"",
		timeout:0,
		onload :function(){},
		ontimeout:function(){},
		jsonp_charset : "utf-8"
	};

	this.option(option);
	
	var _opt = this._options;
	
	_opt.type   = _opt.type.toLowerCase();
	_opt.method = _opt.method.toLowerCase();
	
	if (typeof window.__jindo2_callback == "undefined") {
		window.__jindo2_callback = new Array();
	}
	
	switch (_opt.type) {
		case "get":
		case "post":
			_opt.method = _opt.type;
			_opt.type   = "xhr";
		case "xhr":
			this._request = _getXHR();
			break;
		case "flash":
			this._request = new $Ajax.SWFRequest();
			break;
		case "jsonp":
			_opt.method = "get";
			this._request = new $Ajax.JSONPRequest();
			this._request.charset = _opt.jsonp_charset;
			break;
		case "iframe":
			this._request = new $Ajax.FrameRequest();
			this._request._proxy = _opt.proxy;
			break;
	}
};

/**
 * 주어진 데이터로 Ajax를 호출한다.
 * @id core.$Ajax._onload
 * @param {Function} Ajax 호출이 완료된 후 실행할 함수
 */
$Ajax.prototype._onload = function() {
	if (this._request.readyState == 4) {
		this._options.onload($Ajax.Response(this._request));
	}
};

/**
 * Ajax를 호출한다.
 * @id core.$Ajax.request
 * @param {Object} oData 요청시 보낼 데이터
 * @param {Function} onComplete 요청이 완료되었을 때 실행할 함수
 */
$Ajax.prototype.request = function(oData) {
	var t   = this;
	var req = this._request;
	var opt = this._options;
	var data, v,a = [], data = "";
	if (typeof oData == "undefined" || !oData) {
		data = null;
	} else {
		for(var k in oData) {
			v = oData[k];
			if (typeof v == "function") v = v();
			a[a.length] = k+"="+encodeURIComponent(v);
		}
		data = a.join("&");
	}

	req.open(opt.method.toUpperCase(), this._url, true);
	req.setRequestHeader("Content-Type", "application/json; charset=utf-8");
//	req.setRequestHeader("charset", "utf-8");
	for(var x in this._headers) {
		if (typeof this._headers[x] == "function") continue;
		req.setRequestHeader(x, String(this._headers[x]));
	}
	
	if (typeof req.onload != "undefined") {
		req.onload = function(rq){ t._onload(rq) };
	} else {
		req.onreadystatechange = function(rq){ t._onload(rq) };
	}
	
	req.send(data);

	return this;
};

/**
 * @id core.$Ajax.abort
 */
$Ajax.prototype.abort = function() {
	this._request.abort();

	return this;
};

/**
 * 옵션을 가져오거나 설정한다.
 * 첫번째 전달값의 타입이 Object 이면 값을 설정하고 문자열이면 해당하는 옵션값을 반환한다.
 * @id core.$Ajax.option
 * @param {String} name 가지고 오거나 설정할 옵션이름
 * @param {void}   value 값을 설정할 옵션이름
 * @return {void}  설정된 옵션값 혹은 $Ajax 객체
 */
$Ajax.prototype.option = function(name, value) {
	if (typeof name == "undefined") return "";
	if (typeof name == "string") {
		if (typeof value == "undefined") return this._options[name];
		this._options[name] = value;
		return this;
	}

	try { for(var x in name) this._options[x] = name[x] } catch(e) {};
	
	return this;
};

/**
 * 헤더를 가져오거나 설정한다.
 * 첫번째 전달값의 타입이 Object 이면 값을 설정하고 문자열이면 해당하는 헤더값을 반환한다.
 * @id core.$Ajax.header
 * @param {String} name 가지고 오거나 설정할 헤더 이름
 * @param {void} value 값을 설정할 헤더 값
 * @return {void} 설정된 헤더값 혹은 $Ajax 객체
 */
$Ajax.prototype.header = function(name, value) {
	if (typeof name == "undefined") return "";
	if (typeof name == "string") {
		if (typeof value == "undefined") return this._headers[name];
		this._headers[name] = value;
		return this;
	}
	
	try { for(var x in name) this._headers[x] = name[x] } catch(e) {};
	
	return this;
};

/**
 * Ajax 응답 객체
 * @id core.$Ajax.Response
 * @param {Object} req 요청 객체
 */
$Ajax.Response  = function(req) {
	if (this === $Ajax) return new $Ajax.Response(req);	
	this._response = req;
};

/**
 * XML 객체를 반환한다.
 * @id core.$Ajax.Response.xml
 */
$Ajax.Response.prototype.xml = function() {
	return this._response.responseXML; 
};

$Ajax.Response.prototype.text = function() {
	return this._response.responseText;
};

/**
 * Returns json object
 * @id core.$Ajax.Response.json
 */
$Ajax.Response.prototype.json = function() {
	if (this._response.responseJSON) {
		return this._response.responseJSON;
	} else if (this._response.responseText) {
		try {
			if (typeof $JSON != "undefined") {
				return $JSON(this._response.responseText);
			} else {
				return eval("("+this._response.responseText+")");
			}
		} catch(e) {
			return {};
		}
	}
	
	return {};
};

/**
 * 응답헤더를 가져온다. 인자를 전달하지 않으면 모든 헤더를 반환한다.
 * @id core.$Ajax.Response.header
 * @param {String} 가져올 응답헤더의 이름
 */
$Ajax.Response.prototype.header = function(name) {
	if (typeof name == "string") return this._response.getResponseHeader(name);
	return this._response.getAllResponseHeaders();
};

/**
 * @id core.$Ajax.RequestBase
 */
$Ajax.RequestBase = $Class({
	_headers : {},
	_respHeaders : {},
	_respHeaderString : "",
	responseXML  : null,
	responseJSON : null,
	responseText : "",
	$init  : function(){},
	onload : function(){},
	abort  : function(){},
	open   : function(){},
	send   : function(){},
	setRequestHeader  : function(sName, sValue) {
		this._headers[sName] = sValue;
	}, 
	getResponseHeader : function(sName) {
		return this._respHeaders[sName] || "";
	},
	getAllResponseHeaders : function() {
		return this._respHeaderString;
	},
	_getCallbackInfo : function() {
		var id = "";
	
		do {
			id = "$"+Math.floor(Math.random()*10000);
		} while(window.__jindo2_callback[id]);
		
		return {id:id,name:"window.__jindo2_callback."+id};
	}
});

/**
 * @id core.$Ajax.JSONPRequest
 */
$Ajax.JSONPRequest = $Class({
	charset : "utf-8",
	_script : null,
	_callback : function(data) {
		var self = this;

		this.readyState = 4;
		this.responseJSON = data;
		this.onload(this);
		
		setTimeout(function(){ self.abort() }, 10);
	},
	abort : function() {
		if (this._script) {
			try { this._script.parentNode.removeChild(this._script) }catch(e){}; 
		}
	},
	open  : function(method, url) {
		this.responseJSON = null;

		this._url = url;
	},
	send  : function(data) {
		var t    = this;
		var info = this._getCallbackInfo();
		var head = document.getElementsByTagName("head")[0];
		
		this._script = $("<script>");
		this._script.type    = "text/javascript";
		this._script.charset = this.charset;
		
		if (head) {
			head.appendChild(this._script);
		} else if (document.body) {
			document.body.appendChild(this._script);
		}
		
		window.__jindo2_callback[info.id] = function(data){
			try {
				t._callback(data);
			} finally {
				delete window.__jindo2_callback[info.id];
			}
		};
		
		this._script.src = this._url+"?_callback="+info.name+"&"+data;
	}
}).extend($Ajax.RequestBase);

/**
 * @id core.$Ajax.SWFRequest
 */
$Ajax.SWFRequest = $Class({
	_callback : function(success, data){
		this.readyState = 4;
		if (success) {
			if (typeof data == "string") {
				try {
					this.responseText = decodeURIComponent(data);
				} catch(e) {}
			}
			this.onload(this);
		}
	},
	open : function(method, url) {
		var re  = /https?:\/\/([a-z0-9_\-\.]+)/i;
		
		this._url    = url;
		this._method = method;
	},
	send : function(data) {
		this.responseXML  = false;
		this.responseText = "";
		
		var t    = this;
		var dat  = {};
		var info = this._getCallbackInfo();
		var swf  = window.document[$Ajax.SWFRequest._tmpId];
		var header = [];
		
		function f(arg) {
			switch(typeof arg){
				case "string":
					return '"'+arg.replace(/\"/g, '\\"')+'"';
					break;
				case "number":
					return arg;
					break;
				case "object":
					var ret = "", arr = [];
					if (arg instanceof Array) {
						for(var i=0; i < arg.length; i++) {
							arr[i] = f(arg[i]);
						}
						ret = "["+arr.join(",")+"]";
					} else {
						for(var x in arg) {
							arr[arr.length] = f(x)+":"+f(arg[x]);
						}
						ret = "{"+arr.join(",")+"}";
					}
					return ret;
				default:
					return '""';
			}
		}

		data = (data || "").split("&");
		
		for(var i=0; i < data.length; i++) {
			pos = data[i].indexOf("=");
			key = data[i].substring(0,pos);
			val = data[i].substring(pos+1);
			
			dat[key] = decodeURIComponent(val);
		}
		
		window.__jindo2_callback[info.id] = function(success, data){
			try {
				t._callback(success, data);
			} finally {
				delete window.__jindo2_callback[info.id];
			}
		};

		swf.requestViaFlash(f({
			url  : this._url,
			type : this._method,
			data : dat,
			charset  : "UTF-8",
			callback : info.name,
			headers_json : this._headers
		}));
	}
}).extend($Ajax.RequestBase);

$Ajax.SWFRequest.write = function(swf_path) {
	if(typeof swf_path == "undefined") swf_path = "./ajax.swf";
	$Ajax.SWFRequest._tmpId = 'tmpSwf'+(new Date).getMilliseconds()+Math.floor(Math.random()*100000);
	
	document.write('<div style="position:absolute;top:-1000px;left:-1000px"><object id="'+$Ajax.SWFRequest._tmpId+'" width="1" height="1" classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000" codebase="http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=6,0,0,0"><param name="movie" value="'+swf_path+'"><param name = "allowScriptAccess" value = "always" /><embed name="'+$Ajax.SWFRequest._tmpId+'" src="'+swf_path+'" type="application/x-shockwave-flash" pluginspage="http://www.macromedia.com/go/getflashplayer" width="1" height="1" allowScriptAccess="always" swLiveConnect="true"></embed></object></div>');
};

/**
 * @id core.$Ajax.FrameRequest
 */
$Ajax.FrameRequest = $Class({
	_frame  : null,
	_proxy  : "",
	_domain : "",
	_callback : function(id, data, header) {
		var self = this;

		this.readyState   = 4;
		this.responseText = data;
		
		this._respHeaderString = header;
		header.replace(/^([\w\-]+)\s*:\s*(.+)$/m, function($0,$1,$2) {
			self._respHeaders[$1] = $2;
		});
	
		this.onload(this);
	
		setTimeout(function(){ self.abort() }, 10);
	},
	abort : function() {
		if (this._frame) {
			try {
				this._frame.parentNode.removeChild(this._frame);
			} catch(e) {}
		}
	},
	open : function(method, url) {
		var re  = /https?:\/\/([a-z0-9_\-\.]+)/i;
		var dom = document.location.toString().match(re);
		
		this._method = method;
		this._url    = url;
		this._remote = String(url).match(/(https?:\/\/[a-z0-9_\-\.]+)/i)[1];
		this._frame = null;
		this._domain = (dom[1] != document.domain)?document.domain:"";
	},
	send : function(data) {
		this.responseXML  = "";
		this.responseText = "";
	
		var t      = this;
		var re     = /https?:\/\/([a-z0-9_\-\.]+)/i;
		var info   = this._getCallbackInfo();
		var url    = this._remote+"/ajax_remote_callback.html?method="+this._method;
		var header = new Array;
	
		window.__jindo2_callback[info.id] = function(id, data, header){
			try {
				t._callback(id, data, header);
			} finally {
				delete window.__jindo2_callback[info.id];
			}
		};
	
		for(var x in this._headers) {
			header[header.length] = "'"+x+"':'"+this._headers[x]+"'";
		}
	
		header = "{"+header.join(",")+"}";
	
		url += "&id="+info.id;
		url += "&header="+encodeURIComponent(header);
		url += "&proxy="+encodeURIComponent(this._proxy);
		url += "&domain="+this._domain;
		url += "&url="+encodeURIComponent(this._url.replace(re, ""));
		url += "#"+encodeURIComponent(data);
	
		var fr = this._frame = $("<iframe>");
		fr.style.position = "absolute";
		fr.style.visibility = "hidden";
		fr.style.width = "1px";
		fr.style.height = "1px";
	
		var body = document.body || document.documentElement;
		if (body.firstChild) body.insertBefore(fr, body.firstChild);
		else body.appendChild(fr);
	
		fr.src = url;
	}
}).extend($Ajax.RequestBase);
/**
 * $H 해시 객체를 리턴한다
 * @id core.$H
 */
function $H(hashObject) {
	var cl = arguments.callee;
	if (typeof hashObject == "undefined") hashObject = new Object;
	if (hashObject instanceof cl) return hashObject;
	if (!(this instanceof cl)) return new cl(hashObject);

	this._table = {};
	for(var k in hashObject) {
		if (this._table[k] == hashObject[k]) continue;
		this._table[k] = hashObject[k];
	}
};

/**
 * Object 를 반환한다.
 */
$H.prototype.$value = function() {
	return this._table;
};

/**
 * 해시 객체의 크기를 반환한다.
 * @id core.$H.length
 * @return {Number} 해시의 크기
 */
$H.prototype.length = function() {
	var i = 0;
	for(var k in this._table) {
		if (typeof Object.prototype[k] != "undeifned" && Object.prototype[k] === this._table[k]) continue;
		i++;
	}

	return i;
};

/**
 * $H 해시 객체의 각 요소에 주어진 콜백함수를 실행한다.
 * @id core.$H.forEach
 * @import core.$H[Break, Continue]
 */
$H.prototype.forEach = function(callback, thisObject) {
	var t = this._table;
	var h = this.constructor;
	
	for(var k in t) {
		if (!t.propertyIsEnumerable(k)) continue;
		try {
			callback.call(thisObject, t[k], k, t);
		} catch(e) {
			if (e instanceof h.Break) break;
			if (e instanceof h.Continue) continue;
		}
	}
	return this;
};

/**
 * $H 해시 객체에서 필터 콜백함수를 만족하는 객체의 $H 객체를 리턴한다.
 * 콜백함수는 Boolean 값을 반환해야 한다.
 * @id core.$H.map
 * @import core.$H.forEach
 * @return {$H} 해시객체
 */
$H.prototype.filter = function(callback, thisObject) {
	var h = $H();
	this.forEach(function(v,k,o) {
		if(callback.call(thisObject, v, k, o) === true) {
			h.add(k,v);
		}
	});
	return h;
};

/**
 * $H 해시 객체의 각 요소에 주어진 콜백함수를 실행하고, 함수의 반환값을 해당 요소에 대입한다.
 * @id core.$H.map
 * @import core.$H.forEach
 * @return {$H} 해시객체
 */
$H.prototype.map = function(callback, thisObject) {
	var t = this._table;
	this.forEach(function(v,k,o) {
		t[k] = callback.call(thisObject, v, k, o);
	});
	return this;
};

/**
 * 해시 테이블에 값을 추가한다.
 * @id core.$H.add
 * @return {void} 추가된 값
 */
$H.prototype.add = function(key, value) {
	this._table[key] = value;
	return this._table[key];
};

/**
 * 해시 테이블에 존재하는 값을 제거한다.
 * @id core.$H.remove
 * @return {void} 제거된 값
 */
$H.prototype.remove = function(key) {
	if (typeof this._table[key] == "undefined") return null;
	var val = this._table[key];
	delete this._table[key];
	
	return val;
};

/**
 * 해시 테이블에서 주어진 값을 검색하고, 결과가 존재하면 키를 반환한다.
 * 결과가 존재하지 않으면 false 를 반환한다.
 * @id core.$H.search
 */
$H.prototype.search = function(value) {
	var result = false;
	this.forEach(function(v,k,o) {
		if (v === value) {
			result = k;
			$H.Break();
		}
	});
	return result;
};

/**
 * 해시 테이블에 주어진 키가 있는지 확인한다.
 * @id core.$H.hasKey
 * @return {Boolean} 키의 존재 여부
 */
$H.prototype.hasKey = function(key) {
	var result = false;
	
	return (typeof this._table[key] != "undefined");
};

/**
 * 해시 테이블에 주어진 값이 있는지 확인한다.
 * @id core.$H.hasValue
 * @import core.$H.search
 * @return {Boolean} 값의 존재 여부
 */
$H.prototype.hasValue = function(value) {
	return (this.search(value) !== false);
};

/**
 * 값을 기준으로 해시 객체를 정렬하고 현재 객체를 리턴한다.
 * @id core.$H.sort
 * @import core.$H.values, core.$H.search
 * @return {$H} 해시 객체
 */
$H.prototype.sort = function() {
	var o = new Object;
	var a = this.values();
	var k = false;

	a.sort();

	for(var i=0; i < a.length; i++) {
		k = this.search(a[i]);

		o[k] = a[i];
		delete this._table[k];
	}
	
	this._table = o;
	
	return this;
};

/**
 * 키값을 기준으로 해시 객체를 정렬하고 현재 객체를 리턴한다.
 * @id core.$H.ksort
 * @import core.$H.keys
 * @return {$H} 해시 객체
 */
$H.prototype.ksort = function() {
	var o = new Object;
	var a = this.keys();

	a.sort();

	for(var i=0; i < a.length; i++) {
		o[a[i]] = this._table[a[i]];
	}

	this._table = o;

	return this;
};

/**
 * 해시 키의 배열을 반환한다.
 * @id core.$H.keys
 * @return {Array} 해시 키의 배열
 */
$H.prototype.keys = function() {
	var keys = new Array;
	for(var k in this._table) {
		keys.push(k);
	}

	return keys;
};

/**
 * 해시 값의 배열을 반환한다.
 * @id core.$H.values
 * @return {Array} 해시 값의 배열
 */
$H.prototype.values = function() {
	var values = [];
	for(var k in this._table) {
		values[values.length] = this._table[k];
	}

	return values;
};

/**
 * 해시 객체를 쿼리 스트링 형태로 만들어준다.
 * @id core.$H.toQueryString
 * @return {String} 
 */
$H.prototype.toQueryString = function() {
	var buf = [], val = null, idx = 0;
	for(var k in this._table) {
		if (typeof(val = this._table[k]) == "object" && val.constructor == Array) {
			for(i=0; i < val.length; i++) {
				buf[buf.length] = encodeURIComponent(k)+"[]="+encodeURIComponent(val[i]+"");
			}
		} else {
			buf[buf.length] = encodeURIComponent(k)+"="+encodeURIComponent(this._table[k]+"");
		}
	}
	
	return buf.join("&");
};

/**
 * forEach, filter 등의 반복문 도중 실행을 중단할 때 사용한다.
 * @id core.$H.Break
 */
$H.Break = function() {
	if (!(this instanceof arguments.callee)) throw new arguments.callee;
};

/**
 * forEach, filter 등의 반복문 도중 현재 단계를 건너뛀 때 사용한다.
 * @id core.$H.Continue
 */
$H.Continue = function() {
	if (!(this instanceof arguments.callee)) throw new arguments.callee;
};

/**
 * JSON 객체를 반환한다.첫번째 argument는 객체 혹은 JSON 문자열이 된다.
 * @id core.$Json
 */
function $Json(sObject) {
	var cl = arguments.callee;
	if (typeof sObject == "undefined") sObject = new Object;
	if (sObject instanceof cl) return sObject;
	if (!(this instanceof cl)) return new cl(sObject);
	
	if (typeof sObject == "string") {
		try {
			sObject = eval("("+sObject+")");
		} catch(e) {
			sObject = new Object;
		}
	}

	this._object = sObject;
}

/**
 * XML 문자열로부터 JSON 객체를 반환한다.
 * @id core.$Json.fromXML
 */
$Json.fromXML = function(sXML) {
	var o  = new Object;
	var re = /\s*<(\/?[\w:\-]+)((?:\s+[\w:\-]+\s*=\s*"[^"]*")*)\s*>(?:(<\/\1>)|\s*)|\s*<!\[CDATA\[([\w\W]*?)\]\]>\s*|\s*([^<]*)\s*/ig;
	var re2= /^[0-9]+(?:\.[0-9]+)?$/;
	var ec = {"&amp;":"&","&nbsp;":" ","&quot;":"\"","&lt;":"<","&gt;":">"};
	var fg = {tags:["/"],stack:[o]};
	var es = function(s){return s.replace(/&[a-z]+;/g, function(m){ return (typeof ec[m] == "string")?ec[m]:m; })};
	var at = function(s,c){s.replace(/([\w\:\-]+)\s*=\s*"([^"]*)"/g, function($0,$1,$2){ c[$1] = es($2) }) };
	var em = function(o){for(var x in o){if(Object.prototype[x])continue;return false;};return true};
	var cb = function($0,$1,$2,$3,$4,$5) {
		var cur, cdata = "";
		var idx = fg.stack.length - 1;
		
		if (typeof $1 == "string" && $1) {
			if ($1.substr(0,1) != "/") {
				var has_attr = (typeof $2 == "string" && $2);
				var closed   = (typeof $3 == "string" && $3);
				var newobj   = (!has_attr && closed)?"":{};

				cur = fg.stack[idx];
				
				if (typeof cur[$1] == "undefined") {
					cur[$1] = newobj; 
					cur = fg.stack[idx+1] = cur[$1];
				} else if (cur[$1] instanceof Array) {
					var len = cur[$1].length;
					cur[$1][len] = newobj;
					cur = fg.stack[idx+1] = cur[$1][len];  
				} else {
					cur[$1] = [cur[$1], newobj];
					cur = fg.stack[idx+1] = cur[$1][1];
				}
				
				if (has_attr) at($2,cur);

				fg.tags[idx+1] = $1;

				if (closed) {
					fg.tags.length--;
					fg.stack.length--;
				}
			} else {
				fg.tags.length--;
				fg.stack.length--;
			}
		} else if (typeof $4 == "string" && $4) {
			cdata = $4;
		} else if (typeof $5 == "string" && $5) {
			cdata = es($5);
		}
		
		if (cdata.length > 0) {
			var par = fg.stack[idx-1];
			var tag = fg.tags[idx];

			if (re2.test(cdata)) cdata = parseFloat(cdata);
			else if (cdata == "true" || cdata == "false") cdata = new Boolean(cdata);

			if (par[tag] instanceof Array) {
				var o = par[tag];
				if (typeof o[o.length-1] == "object" && !em(o[o.length-1])) {
					o[o.length-1].$cdata = cdata;
					o[o.length-1].toString = function(){ return cdata; }
				} else {
					o[o.length-1] = cdata;
				}
			} else {
				if (typeof par[tag] == "object" && !em(par[tag])) {
					par[tag].$cdata = cdata;
					par[tag].toString = function(){ return cdata; }
				} else {
					par[tag] = cdata;
				}
			}
		}
	};
	
	sXML = sXML.replace(/<(\?|\!-)[^>]*>/g, "");
	sXML.replace(re, cb);
	
	return $Json(o);
};

/**
 * JSON 객체의 값을 path 형태로 받아온다.
 * @id core.$Json.get
 * @param {String} sPath path 문자열
 * @return {Array} 객체의 배열
 */
$Json.prototype.get = function(sPath) {
	var o = this._object;
	var p = sPath.split("/");
	var re = /^([\w:\-]+)\[([0-9]+)\]$/;
	var stack = [[o]], cur = stack[0];
	var len = p.length, c_len, idx, buf, j, e;
	
	for(var i=0; i < len; i++) {
		if (p[i] == "." || p[i] == "") continue;
		if (p[i] == "..") {
			stack.length--;
		} else {
			buf = [];
			idx = -1;
			c_len = cur.length;
			
			if (c_len == 0) return [];
			if (re.test(p[i])) idx = +RegExp.$2;
			
			for(j=0; j < c_len; j++) {
				e = cur[j][p[i]];
				if (typeof e == "undefined") continue;
				if (e instanceof Array) {
					if (idx > -1) {
						if (idx < e.length) buf[buf.length] = e[idx];
					} else {
						buf = buf.concat(e);
					}
				} else if (idx == -1) {
					buf[buf.length] = e;
				}
			}
			
			stack[stack.length] = buf;
		}
		
		cur = stack[stack.length-1];
	}

	return cur;
};

/**
 * JSON 객체를 JSON 문자열로 변환한다.
 * @id core.$Json.toString
 * @return {String} JSON 문자열
 */
$Json.prototype.toString = function() {
	var func = {
		$ : function($) {
			if (typeof $ == "undefined") return '""';
			if (typeof $ == "boolean") return $?"true":"false";
			if (typeof $ == "string") return this.s($);
			if (typeof $ == "number") return $;
			if ($ instanceof Array) return this.a($);
			if ($ instanceof Object) return this.o($);
		},
		s : function(s) {
			var e = {'"':'\\"',"\\":"\\\\","\n":"\\n","\r":"\\r","\t":"\\t"};
			var c = function(m){ return (typeof e[m] != "undefined")?e[m]:m };
			return '"'+s.replace(/[\\"'\n\r\t]/g, c)+'"';
		},
		a : function(a) {
			var s = "[",c = "",n=a.length;
			for(var i=0; i < n; i++) {
				if (typeof a[i] == "function") continue;
				s += c+this.$(a[i]);
				if (!c) c = ",";
			}
			return s+"]";
		},
		o : function(o) {
			var s = "{",c = "";
			for(var x in o) {
				if (typeof o[x] == "function") continue;
				s += c+this.s(x)+":"+this.$(o[x]);
				if (!c) c = ",";
			}
			return s+"}";
		}
	}

	return func.$(this._object);
};

/**
 * JSON 객체를 XML 문자열로 변환한다.
 * @id core.$Json.toXML
 * @return {String} XML 문자열
 */
$Json.prototype.toXML = function() {
	var f = function($,tag) {
		var t = function(s,at) { return "<"+tag+(at||"")+">"+s+"</"+tag+">" };
		
		switch (typeof $) {
			case "undefined":
			case "null":
				return t("");
			case "number":
				return t($);
			case "string":
				if ($.indexOf("<") < 0) return t($.replace(/&/g,"&amp;"));
				else return t("<![CDATA["+$+"]]>");
			case "boolean":
				return t(String($));
			case "object":
				var ret = "";
				if ($ instanceof Array) {
					var len = $.length;
					for(var i=0; i < len; i++) { ret += f($[i],tag); };
				} else {
					var at = "";

					for(var x in $) {
						if (x == "$cdata" || typeof $[x] == "function") continue;
						ret += f($[x], x);
					}

					if (tag) ret = t(ret, at);
				}
				return ret;
		}
	};
	
	return f(this._object, "");
};

/**
 * JSON  데이터 객체를 반환한다.
 * @id core.$Json.toObject
 * @import core.$Json.$value
 * @return {Object} 데이터 객체
 */
$Json.prototype.toObject = function() {
	return this._object;
};

/**
 * $Json.toObject의 alias function
 * @id core.$Json.$value
 */
$Json.prototype.$value = $Json.prototype.toObject;
/**
 * $Cookie 객체를 반환한다.
 * @id core.$Cookie
 */
function $Cookie() {
	var cl = arguments.callee;
	var cached = cl._cached;
	
	if (cl._cached) return cl._cached;
	if (!(this instanceof cl)) return new cl;
	if (typeof cl._cached == "undefined") cl._cached = this;
};

/**
 * 쿠키 이름의 배열을 반환한다.
 * @id core.$Cookie.keys
 * @return {Array} 쿠키 이름의 배열
 */
$Cookie.prototype.keys = function() {
	var ca = document.cookie.split(";");
	var re = /^\s+|\s+$/g;
	var a  = new Array;
	
	for(var i=0; i < ca.length; i++) {
		a[a.length] = ca[i].substr(0,ca[i].indexOf("=")).replace(re, "");
	}
	
	return a;
};

/**
 * 이름에 해당하는 쿠키 값을 가져온다. 값이 존재하지 않는다면 null을 반환한다.
 * @id core.$Cookie.get
 * @param {String} sName 쿠키 이름
 * @return {String} 쿠키 값
 */
$Cookie.prototype.get = function(sName) {
	var ca = document.cookie.split(/\s*;\s*/);
	var re = new RegExp("^(\\s*"+sName+"\\s*=)");
	
	for(var i=0; i < ca.length; i++) {
		if (re.test(ca[i])) return ca[i].substr(RegExp.$1.length);
	}
	
	return null;
};

/**
 * 이름에 해당하는 쿠키 값을 설정한다.
 * @id core.$Cookie.set
 * @param {String} sName 쿠키 이름
 * @param {String} sValue 쿠키값
 * @param {Number} nDays 쿠키 유효 시간(일단위)
 * @return {$Cookie} 쿠키 객체
 */
$Cookie.prototype.set = function(sName, sValue, nDays) {
	var sExpire = "";
	
	if (typeof nDays == "number") {
		sExpire = ";expires="+(new Date((new Date()).getTime()+nDays*1000*60*60*24)).toGMTString();
	}
	
	document.cookie = sName+"="+escape(sValue)+sExpire+"; path=/";
	
	return this;
};

/**
 * 이름에 해당하는 쿠키 값을 제거한다.
 * @id core.$Cookie.remove
 * @import core.$Cookie.set
 * @param {String} sName 쿠키 이름
 * @return {$Cookie} 쿠키 객체
 */
$Cookie.prototype.remove = function(sName) {
	if (this.get(sName) != null) this.set(sName, "", -1);
	
	return this;
};
/**
 * $Element 객체를 반환한다.
 * @id core.$Element
 */
function $Element(el) {
	var cl = arguments.callee;
	if (el instanceof cl) return el;
	if (!(this instanceof cl)) return new cl(el);

	this._element = $(el);
	this.tag = this._element?this._element.tagName.toLowerCase():'';

	this._queue = new Array;
}

/**
 * DOMElement 객체를 반환한다.
 * @id core.$Element.$value
 */
$Element.prototype.$value = function() {
	return this._element;
};

/**
 * 객체의 display style 속성을 조사해서 none 이면 false 를 반환한다.
 * @id core.$Element.visible
 * @import core.$Element.css
 */
$Element.prototype.visible = function() {
	return (this.css("display") != "none");
};

/**
 * 객체가 화면에 보이도록 display style 속성을 변경한다.
 * @id core.$Element.show
 */
$Element.prototype.show = function() {
	var s = this._element.style;
	var b = "block";
	var c = {p:b,div:b,form:b,h1:b,h2:b,h3:b,h4:b,ol:b,ul:b,fieldset:b,td:"table-cell",th:"table-cell",li:"list-item",table:"table",thead:"table-header-group",tbody:"table-row-group",tfoot:"table-footer-group",tr:"table-row",col:"table-column",colgroup:"table-column-group",caption:"table-caption",dl:b,dt:b,dd:b};

	try {
		if(typeof c[this.tag] == "string") {
			s.display = c[this.tag];
		} else {
			s.display = "inline";
		}
	} catch(e) {
		s.display = "block";
	}

	return this;
};

/**
 * 객체가 화면에 보이지 않도록 display style 속성을 변경한다.
 * @id core.$Element.hide
 */
$Element.prototype.hide = function() {
	this._element.style.display = "none";

	return this;
};

/**
 * 객체를 보이거나 감추도록 display 속성을 toggle 한다.
 * @id core.$Element.toggle
 * @import core.$Element[visible, show, hide]
 */
$Element.prototype.toggle = function() {
	this[this.visible()?"hide":"show"]();

	return this;
};

/**
 * 투명도 값을 가져오거나 설정한다. 첫번째 argument가 설정되어있으면 해당값으로 투명도를 설정한다.
 * 투명도 값은 0 ~ 1 사이의 실수값으로 정한다.
 * @id core.$Element.opacity
 * @import core.$Element.visible
 * @return {Number} 불투명도 실수값
 */
$Element.prototype.opacity = function(value) {
	var v,e = this._element,b=this.visible();
	if (typeof value == "number") {
		value = Math.max(Math.min(value,1),0);

		if (typeof e.filters != "undefined") {
			value = Math.ceil(value*100);
			if (typeof e.filters.alpha != "undefined") {
				e.filters.alpha.opacity = value;
			} else {
				e.style.filter = (e.style.filter + " alpha(opacity=" + value + ")");
			}
		} else {
			e.style.opacity = value;
		}

		return value;
	}

	if (typeof e.filters != "undefined") {
		v = (typeof e.filters.alpha == "undefined")?(b?100:0):e.filters.alpha.opacity;
		v = v / 100;
	} else {
		v = parseFloat(e.style.opacity);
		if (isNaN(v)) v = b?1:0;
	}

	return v;
};

/**
 * 객체를 Fade-in 효과와 함께 나타나도록 한다.
 * id core.$Element.appear
 * @import core.$Element[visible,opacity,show]
 * @param {Number} duration 나타나는 시간(초단위)
 * @param {Function} 완전히 나타나고 난 후의 콜백 함수
 */
$Element.prototype.appear = function(duration, callback) {
	var self = this;
	var op   = this.opacity();

	if (op == 1) return this;
	try { clearTimeout(this._fade_timer); } catch(e){};

	callback = callback || new Function;

	var step = (1-op) / ((duration||0.3)*100);
	var func = function(){
		op += step;
		self.opacity(op);

		if (op >= 1) {
			callback(self);
		} else {
			self._fade_timer = setTimeout(func, 10);
		}
	};

	this.show();
	func();

	return this;
};

/**
 * 객체를 Fade-out 효과와 함께 사라지도록 한다. 완전히 투명해지고 나면 display 속성이 none 으로 변한다.
 * @id core.$Element.disappear
 * @import core.$Element[visible,opacity,hide]
 * @param {Number} duration 사라지는 시간(초단위)
 * @param {Function} 완전히 사라지고 난 후의 콜백 함수
 */
$Element.prototype.disappear = function(duration, callback) {
	var self = this;
	var op   = this.opacity();

	if (op == 0) return this;
	try { clearTimeout(this._fade_timer); } catch(e){};

	callback = callback || new Function;

	var step = op / ((duration||0.3)*100);
	var func = function(){
		op -= step;
		self.opacity(op);

		if (op <= 0) {
			self.hide();
			callback(self);
		} else {
			self._fade_timer = setTimeout(func, 10);
		}
	};

	func();

	return this;
};

/**
 * 객체의 CSS 속성을 얻을 수 있다. 단, 첫번째 argument 에 얻을 속성키를 입력해야 한다.
 * 만일, 첫번째 argument 가 Object 혹은 $Hash 타입이면 반대로 CSS를 주어진 값으로 적용한다.
 * @id core.$Element.css
 * @param {String,Object} sName CSS 속성이름 혹은 설정값 객체
 * @param {String} sValue 설정값
 */
$Element.prototype.css = function(sName, sValue) {
	var e = this._element;

	if (typeof sName == "string") {
		var view;

		if (typeof sValue == "string" || typeof sValue == "number") {
			var obj = new Object;
			obj[sName] = sValue;
			sName = obj;
		} else {
			if (e.currentStyle) {
				if (sName == "cssFloat") sName = "styleFloat";
				return e.currentStyle[sName]||e.style[sName];
			} else if (window.getComputedStyle) {
				if (sName == "cssFloat") sName = "float";
				return document.defaultView.getComputedStyle(e,null).getPropertyValue(sName.replace(/([A-Z])/g,"-$1").toLowerCase())||e.style[sName];
			} else {
				if (sName == "cssFloat" && /MSIE/.test(window.navigator.userAgent)) sName = "styleFloat";
				return e.style[sName];
			}

			return null;
		}
	}


	if (typeof $H != "undefined" && sName instanceof $H) {
		sName = sName.$value();
	}

	if (typeof sName == "object") {
		var v, type;

		for(var k in sName) {
			v    = sName[k];
			type = (typeof v);
			if (type != "string" && type != "number") continue;
			if (k == "cssFloat" && navigator.userAgent.indexOf("MSIE") > -1) k = "styleFloat";
			try {
				e.style[k] = v;
			} catch(err) {
				if (k == "cursor" && v == "pointer") {
					e.style.cursor = "hand";
				} else if (("#top#left#right#bottom#").indexOf(k+"#") > 0 && (type == "number" || !isNaN(parseInt(v)))) {
					e.style[k] = parseInt(v)+"px";
				}
			}
		}
	}

	return this;
};

/**
 * 객체의 속성을 구하거나 설정한다.
 * @param {String,Object} sName 속성이름 혹은 설정값 객체
 * @param {String} sValue 설정값
 */
$Element.prototype.attr = function(sName, sValue) {
	var e = this._element;

	if (typeof sName == "string") {
		if (typeof sValue != "undefined") {
			var obj = new Object;
			obj[sName] = sValue;
			sName = obj;
		} else {
			if (sName == "class" || sName == "className") return e.className;
			return e.getAttribute(sName);
		}
	}

	if (typeof $H != "undefined" && sName instanceof $H) {
		sName = sName.$value();
	}

	if (typeof sName == "object") {
		for(var k in sName) {
			if (sValue == null) e.removeAttribute(k);
			else e.setAttribute(k, sName[k]);
		}
	}

	return this;
};

/**
 * 객체의 문서상의 offset 위치값을 반환한다. top, left 값을 전달하면 해당 값으로 위치값을 정의한다.
 * @id core.$Element.offset
 * @import core.$Element.css
 * @param {Number} nTop 문서 좌상단으로부터의 top 좌표(px)
 * @param {Number} nLeft 문서 좌상단으로부터의 left 좌표(px)
 * @return {TypePos} 문서 좌상단으로부터의 좌표(px)
 */
$Element.prototype.offset = function(nTop, nLeft) {

	var oEl = this._element;
	var oPhantom = null;

	// setter
	if (typeof nTop == 'number' && typeof nLeft == 'number') {

		if (isNaN(parseInt(this.css('top')))) this.css('top', 0);
		if (isNaN(parseInt(this.css('left')))) this.css('left', 0);

		var oPos = this.offset();
		var oGap = { top : nTop - oPos.top, left : nLeft - oPos.left };

		oEl.style.top = parseInt(this.css('top')) + oGap.top + 'px';
		oEl.style.left = parseInt(this.css('left')) + oGap.left + 'px';

		return this;

	}

	// getter
	var bSafari = /Safari/.test(navigator.userAgent);
	var bIE = /MSIE/.test(navigator.userAgent);

	var fpSafari = function(oEl) {

		var oPos = { left : 0, top : 0 };

		for (var oParent = oEl, oOffsetParent = oParent.offsetParent; oParent = oParent.parentNode; ) {

			if (oParent.offsetParent) {

				oPos.left -= oParent.scrollLeft;
				oPos.top -= oParent.scrollTop;

			}

			if (oParent == oOffsetParent) {

				oPos.left += oEl.offsetLeft + oParent.clientLeft;
				oPos.top += oEl.offsetTop + oParent.clientTop ;

				if (!oParent.offsetParent) {

					oPos.left += oParent.offsetLeft;
					oPos.top += oParent.offsetTop;

				}

				oOffsetParent = oParent.offsetParent;
				oEl = oParent;
			}
		}

		return oPos;

	};

	var fpOthers = function(oEl) {

		var oPos = { left : 0, top : 0 };

		var oDoc = oEl.ownerDocument || oEl.document || document;
		var oHtml = oDoc.documentElement;
		var oBody = oDoc.body;

		if (oEl.getBoundingClientRect) { // has getBoundingClientRect

			if (!oPhantom) {

				if (bIE && window.external) {
					
					oPhantom = { left : 2, top : 2 };

					/*
					var oBase = oDoc.createElement('div');
					oBase.style.cssText = 'position:absolute !important; left:0 !important; top:0 !important; margin:0 !important; padding:0 !important;';
					oDoc.body.insertBefore(oBase, oDoc.body.firstChild);

					oPhantom = oBase.getBoundingClientRect();
					oPhantom.left += oHtml.scrollLeft || oBody.scrollLeft;
					oPhantom.top += oHtml.scrollTop || oBody.scrollTop;

					oDoc.body.removeChild(oBase);
					*/

					oBase = null;

				} else {

					oPhantom = { left : 0, top : 0 };

				}

			}

			var box = oEl.getBoundingClientRect();
			if (oEl !== oHtml && oEl !== oBody) {

				oPos.left = box.left - oPhantom.left;
				oPos.top = box.top - oPhantom.top;
				
				oPos.left += oHtml.scrollLeft || oBody.scrollLeft;
				oPos.top += oHtml.scrollTop || oBody.scrollTop;
				
			}

		} else if (oDoc.getBoxObjectFor) { // has getBoxObjectFor

			var box = oDoc.getBoxObjectFor(oEl);
			var vpBox = oDoc.getBoxObjectFor(oHtml || oBody);

			oPos.left = box.screenX - vpBox.screenX;
			oPos.top = box.screenY - vpBox.screenY;

		} else {

			for (var o = oEl; o; o = o.offsetParent) {

				oPos.left += o.offsetLeft;
				oPos.top += o.offsetTop;

			}

			for (var o = oEl.parentNode; o; o = o.parentNode) {

				if (o.tagName == 'BODY') break;
				if (o.tagName == 'TR') oPos.top += 2;

				oPos.left -= o.scrollLeft;
				oPos.top -= o.scrollTop;

			}

		}
		
		return oPos;

	};

	return (bSafari ? fpSafari : fpOthers)(oEl);

};

/**
 * 객체의 픽셀단위 실제 너비를 구하거나 설정한다.
 * @id core.$Element.width
 * @return {Number} 객체의 실제 너비
 */
$Element.prototype.width = function(width) {
	if (typeof width == "number") {
		var e = this._element;

		e.style.width = width+"px";
		if (e.offsetWidth != width) {
			e.style.width = (width*2 - e.offsetWidth) + "px";
		}
	}

	return this._element.offsetWidth;
};

/**
 * 객체의 픽셀단위 실제 높이를 구하거나 설정한다.
 * @id core.$Element.height
 * @return {Number} 객체의 실제 녺이
 */
$Element.prototype.height = function(height) {
	if (typeof height == "number") {
		var e = this._element;

		e.style.height = height+"px";
		if (e.offsetHeight != height) {
			e.style.height = (height*2 - e.offsetHeight) + "px";
		}
	}

	return this._element.offsetHeight;
};

/**
 * 클래스 이름을 설정하거나 반환한다.
 * @id core.$Element.className
 * @param {String} 클래스 이름
 */
$Element.prototype.className = function(sClass) {
	var e = this._element;

	if (typeof sClass == "undefined") return e.className;
	e.className = sClass;

	return this;
};

/**
 * 지정한 클래스 이름이 설정되어 있는지 확인한다.
 * @id core.$Element.hasClass
 * @param {String} sClass 확인할 클래스 이름
 * @return {Boolean} 클래스 이름 설정 여부
 */
$Element.prototype.hasClass = function(sClass) {
	return (" "+this._element.className+" ").indexOf(" "+sClass+" ") > -1;
};

/**
 * 지정한 클래스 이름을 추가한다.
 * @id core.$Element.addClass
 * @param {String} sClass 추가할 클래스 이름
 * @return {$Element} 현재의 객체
 */
$Element.prototype.addClass = function(sClass) {
	var e = this._element;
	if (this.hasClass(sClass)) return this;
	e.className = (e.className+" "+sClass).replace(/^\s+/, "");
	return this;
};

/**
 * 지정한 클래스 이름을 제거한다.
 * @id core.$Element.removeClass
 * @param {String} sClass 제거할 클래스 이름
 * @return {$Element} 현재의 객체
 */
$Element.prototype.removeClass = function(sClass) {
	var e = this._element;
	e.className = (e.className+" ").replace(sClass+" ", "").replace(/\s+$/, "");

	return this;
};

/**
 * 클래스 이름을 토글한다.
 * @id core.$Element.toggleClass
 * @import core.$Element[hasClass,addClass,removeClass]
 */
$Element.prototype.toggleClass = function(sClass, sClass2) {
	sClass2 = sClass2 || "";
	if (this.hasClass(sClass)) {
		this.removeClass(sClass);
		if (sClass2) this.addClass(sClass2);
	} else {
		this.addClass(sClass);
		if (sClass2) this.removeClass(sClass2);
	}

	return this;
};

/**
 * 객체의 text값을 반환한다. sText 값이 설정되면 설정된 값으로 엘리먼트의 text를 변경한다.
 * @id core.$Element.text
 * @param {String} sText 설정할 텍스트
 */
$Element.prototype.text = function(sText) {
	var prop = (typeof this._element.innerText != "undefined")?"innerText":"textContent";

	if (typeof sText == "string") {
		this._element[prop] = sText;
		return this;
	}

	return this._element[prop];
};

/**
 * 객체 내부의 html을 반환한다. sHTML 값이 설정되면 전달받은 값으로 내부 html을 설정한다.
 * @id core.$Element.html
 * @param {String} sHTML 설정할 HTML 문자열
 * @return {String} 내부 HTML
 */
$Element.prototype.html = function(sHTML) {
	if (typeof sHTML == "string") {

		var oEl = this._element;
		var bBugAgent = $Agent().navigator().ie || ($Agent().navigator().firefox && !oEl.parentNode);

		if (bBugAgent) {

			/*
				IE 나 FireFox 의 일부 상황에서 SELECT 태그나 TABLE, TR, THEAD, TBODY 태그에 innerHTML 을 셋팅해도
				문제가 생기지 않도록 보완 - hooriza
			*/
			var sId = 'R' + new Date().getTime() + parseInt(Math.random() * 100000);
			var oDoc = oEl.ownerDocument || oEl.document || document;

			var oDummy;
			var sTag = oEl.tagName.toLowerCase();

			switch (sTag) {
			case 'select':
			case 'table':
				oDummy = $('<div>');
				oDummy.innerHTML = '<' + sTag + ' class="' + sId + '">' + sHTML + '</' + sTag + '>';
				break;

			case 'tr':
			case 'thead':
			case 'tbody':
				oDummy = $('<div>');
				oDummy.innerHTML = '<table><' + sTag + ' class="' + sId + '">' + sHTML + '</' + sTag + '></table>';
				break;

			default:
				oEl.innerHTML = sHTML;
				break;
			}

			if (oDummy) {

				var oFound;
				for (oFound = oDummy.firstChild; oFound; oFound = oFound.firstChild)
					if (oFound.className == sId) break;

				if (oFound) {

					for (var oChild; oChild = oEl.firstChild;) oChild.removeNode(true); // innerHTML = '';

					for (var oChild = oFound.firstChild; oChild; oChild = oFound.firstChild)
						oEl.appendChild(oChild);

					oDummy.removeNode && oDummy.removeNode(true);

				}

				oDummy = null;

			}

		} else {

			oEl.innerHTML = sHTML;

		}

		return this;

	}

	return this._element.innerHTML;
};

/**
 * 객체의 outerHTML 을 반환한다.
 * @id core.$Element.outerHTML
 * @return {String} 외부 HTML
 */
$Element.prototype.outerHTML = function() {
	var e = this._element;
	if (typeof e.outerHTML != "undefined") return e.outerHTML;

	var div = $("<div>");
	var par = e.parentNode;

	par.insertBefore(div, e);
	div.style.display = "none";
	div.appendChild(e);

	var s = div.innerHTML;
	par.insertBefore(e, div);
	par.removeChild(div);

	return s;
};

/**
 * 객체를 HTML 로 표현한 문자열을 반환한다. outerHTML과 동일하다.
 * @id core.$Element.toString
 * @import core.$Element.outerHTML
 * @return {String} 외부 HTML
 */
$Element.prototype.toString = $Element.prototype.outerHTML;

/**
 * 현재 객체의 마지막 자식노드로 새 객체를 추가한다.
 * @id core.$Element.append
 */
$Element.prototype.append = function(oElement) {
	var o = $Element(oElement).$value();

	this._element.appendChild(o);

	return $Element(o);
};

/**
 * 현재 객체의 첫번째 자식노드로 새 객체를 추가한다.
 * @id core.$Element.prepend
 */
$Element.prototype.prepend = function(oElement) {
	var e = this._element;
	var o = $Element(oElement).$value();

	if (e.childNodes.length > 0) {
		e.insertBefore(o, e.childNodes[0]);
	} else {
		e.appendChild(o);
	}

	return $Element(o);
};

/**
 * 현재의 객체를 다른 노드로 대체한다.
 * @id core.$Element.replace
 */
$Element.prototype.replace = function(oElement) {
	var e = this._element;
	var o = $Element(oElement).$value();

	e.parentNode.insertBefore(o, e);
	e.parentNode.removeChild(e);

	return $Element(o);
};

/**
 * 현재 객체를 지정한 객체의 마지막 자식노드로 추가한다.
 * @id core.$Element.appendTo
 */
$Element.prototype.appendTo = function(oElement) {
	var o = $Element(oElement).$value();

	o.appendChild(this._element);

	return this;
};

/**
 * 현재 객체를 지정한 객체의 첫번째 자식노드로 추가한다.
 * @id core.$Element.prependTo
 */
$Element.prototype.prependTo = function(oElement) {
	var o = $Element(oElement).$value();

	if (o.childNodes.length > 0) {
		o.insertBefore(this._element, o.childNodes[0]);
	} else {
		o.appendChild(this._element);
	}

	return this;
};

/**
 * 현재 객체의 앞에 지정한 객체를 삽입한다.
 * @id core.$Element.before
 */
$Element.prototype.before = function(oElement) {
	var o = $Element(oElement).$value();

	this._element.parentNode.insertBefore(o, this._element);

	return $Element(o);
};

/**
 * 현재 객체의 뒤에 지정한 객체를 삽입한다.
 * @id core.$Element.after
 * @import core.$Element.before
 */
$Element.prototype.after = function(oElement) {
	var o = this.before(oElement);
	o.before(this);

	return o;
};

/**
 * 전체 혹은 조건에 맞는 부모 객체를 배열로 반환한다. 인자를 전달하지 않으면 바로 상위의 부모만 반환한다.
 * @id core.$Element.parent
 * @param {pFunc}  부모 노드 중 결과에 포함할 노드를 선택하는 콜백함수, 조건에 맞으면 true를 반환하면 된다. 결과 필터링을 원치 않으면 null을 대입한다.
 * @param {Number} limit 탐색할 상위 노드 깊이
 */
$Element.prototype.parent = function(pFunc, limit) {
	var e = this._element;
	var a = [], p = null;

	if (typeof pFunc == "undefined") return $Element(e.parentNode);
	if (typeof limit == "undefined" || limit == 0) limit = -1;

	while (e.parentNode && limit-- != 0) {
		p = $Element(e.parentNode);
		if (e.parentNode == document.documentElement) break;
		if (!pFunc || (pFunc && pFunc(p))) a[a.length] = p;

		e = e.parentNode;
	}

	return a;
};

/**
 * 전체 혹 조건에 맞는 자식 객체를 $Element의 배열로 반환한다. 인자를 전달하지 않으면 바로 하위의 자식노드를 반환한다.
 * @id core.$Element.child
 * @param {pFunc}  자식 노드 중 결과에 포함할 노드를 선택하는 콜백함수, 조건에 맞으면 true를 반환하면 된다.
 * @param {Number} limit 탐색할 하위 노드 깊이
 * @import $A.map
 */
$Element.prototype.child = function(pFunc, limit) {
	var e = this._element;
	var a = [], c = null, f = null;

	if (typeof pFunc == "undefined") return $A(e.childNodes).filter(function(v){ return v.nodeType == 1}).map(function(v){ return $Element(v) }).$value();
	if (typeof limit == "undefined" || limit == 0) limit = -1;

	(f = function(el, lim){
		var ch = null, o = null;

		for(var i=0; i < el.childNodes.length; i++) {
			ch = el.childNodes[i];
			if (ch.nodeType != 1) continue;

			o = $Element(el.childNodes[i]);
			if (!pFunc || (pFunc && pFunc(o))) a[a.length] = o;
			if (lim != 0) f(el.childNodes[i], lim-1);
		}
	})(e, limit-1);

	return a;
};

/**
 * 전체 혹 조건에 맞는 이전 형제객체를 $Element의 배열로 반환한다. 인자를 전달하지 않으면 바로 앞의 형제노드를 반환한다.
 * @id core.$Element.prev
 * @param {pFunc} 형제 노드 중 결과에 포함할 노드를 선택하는 콜백함수, 조건에 맞으면 true를 반환하면 된다.
 */
$Element.prototype.prev = function(pFunc) {
	var e = this._element;
	var a = [];

	if (typeof pFunc == "undefined") return $Element(e.previousSibling);

	while(e && e.nextSibling) {
		o = e.previousSibling;
		if (!pFunc || (pFunc && pFunc(o))) a[a.length] = $Element(o);
		e = e.previousSibling;
	}

	return a;
};

/**
 * 전체 혹 조건에 맞는 다음 형제객체를 $Element의 배열로 반환한다. 인자를 전달하지 않으면 바로 앞의 형제노드를 반환한다.
 * @id core.$Element.next
 * @param {pFunc} 형제 노드 중 결과에 포함할 노드를 선택하는 콜백함수, 조건에 맞으면 true를 반환하면 된다.
 */
$Element.prototype.next = function(pFunc) {
	var e = this._element;
	var a = [], o = null;

	if (typeof pFunc == "undefined") return $Element(e.nextSibling);

	while(e && e.nextSibling) {
		o = e.nextSibling;
		if (!pFunc || (pFunc && pFunc(o))) a[a.length] = $Element(o);
		e = e.nextSibling;
	}

	return a;
};

/**
 * 현재 객체가 주어진 객체의 자식인지 확인한다.
 * @id core.$Element.isChildOf
 * @param {HTMLElement,$Element} 체크할 HTMLElement 혹은 $Element 객체
 */
$Element.prototype.isChildOf = function(element) {
	var e  = this._element;
	var el = $Element(element).$value();

	while(e && e.parentNode) {
		e = e.parentNode;
		if (e == el) return true;
	}
	return false;
};

/**
 * 현재 객체가 주어진 객체의 부모인지 확인한다.
 * @id core.$Element.isParentOf
 * @param {HTMLElement,$Element} 체크할 HTMLElement 혹은 $Element 객체
 */
$Element.prototype.isParentOf = function(element) {
	var el = $Element(element).$value();

	while(el && el.parentNode) {
		el = el.parentNode;
		if (this._element == el) return true;
	}
	return false;
};

/**
 * 이벤트를 실행한다.
 * @id core.$Element.fireEvent
 * @param {String} 실행할 이벤트 이름. on 접두사는 생략한다.
 */
$Element.prototype.fireEvent = function(sEvent) {
	function IE(sEvent) {
		sEvent = (sEvent+"").toLowerCase();
		this._element.fireEvent("on"+sEvent);
		return this;
	};

	function DOM2(sEvent) {
		var sType = "HTMLEvents";
		sEvent = (sEvent+"").toLowerCase();

		if (sEvent == "click" || sEvent.indexOf("mouse") == 0) {
			sType = "MouseEvents";
			if (sEvent == "mousewheel") sEvent = "dommousescroll";
		} else if (sEvent.indexOf("key") == 0) {
			sType = "KeyEvents";
		}

		var evt   = document.createEvent(sType);

		evt.initEvent(sEvent, true, true);

		this._element.dispatchEvent(evt);
		return this;
	};

	$Element.prototype.fireEvent = (typeof this._element.dispatchEvent != "undefined")?DOM2:IE;

	return this.fireEvent(sEvent);
};

/**
 * 현재 객체의 하위 노드를 모두 제거한다.
 * @id core.$Element.empty
 */
$Element.prototype.empty = function() {
	this._element.innerHTML = "";
	return this;
};

/**
 * 현재 객체를 부모 노드로부터 제거한다.
 * @id core.$Element.leave
 */
$Element.prototype.leave = function() {
	var e = this._element;

	if (e.parentNode) {
		e.parentNode.removeChild(e);
	}

	return this;
};

/**
 * 주어진 객체로 현재 객체를 감싼다.
 * @id core.$Element.wrap
 */
$Element.prototype.wrap = function(wrapper) {
	var e = this._element;

	wrapper = $(wrapper);
	if (e.parentNode) {
		e.parentNode.insertBefore(wrapper, e);
	}
	wrapper.appendChild(e);

	return this;
};

/**
 * 목록의 객체들을 부모 노드로부터 제거한다.
 * @id core.$Element.ellipsis
 */
$Element.prototype.ellipsis = function(stringTail) {
	stringTail = stringTail || "...";

	var txt   = this.text();
	var len   = txt.length;
	var cur_h = this.height();
	var i     = 0;
	var h     = this.text('A').height();

	if (cur_h < h * 1.5) return this.text(txt);

	cur_h = h;
	while(cur_h < h * 1.5) {
		i += Math.max(Math.ceil((len - i)/2), 1);
		cur_h = this.text(txt.substring(0,i)+stringTail).height();
	}

	while(cur_h > h * 1.5) {
		i--;
		cur_h = this.text(txt.substring(0,i)+stringTail).height();
	}
};
/**
 * 함수 객체를 리턴한다.
 * @id core.$Fn
 * @param {Function} 함수 객체
 * @import core.$Fn.toFunction
 */
function $Fn(func, thisObject) {
	var cl = arguments.callee;
	if (func instanceof cl) return func;
	if (!(this instanceof cl)) return new cl(func, thisObject);

	this._events = {};
	this._tmpElm = null;

	if (typeof func == "function") {
		this._func = func;
		this._this = thisObject;
	} else if (typeof func == "string" && typeof thisObject == "string") {
		this._func = new Function(func, thisObject);
	}
}

/**
 * Function 객체를 반환한다.
 * @return {Function} 함수 객체
 */
$Fn.prototype.$value = function() {
	return this._func;
};

/**
 * 함수를 thisObject 의 메소드로 묶은 Function 을 반환한다.
 * @id core.$Fn.bind
 * @import core.$A
 */
$Fn.prototype.bind = function() {
	var a = $A(arguments).$value();
	var f = this._func;
	var t = this._this;

	var b = function() {
		var args = $A(arguments).$value();

		// fix opera concat bug
		if (a.length) args = a.concat(args);

		return f.apply(t, args);
	};

	return b;
};

/**
 *
 * @id core.$Fn.bindForEvent
 * @import core.$A
 * @import core.$Event
 */
$Fn.prototype.bindForEvent = function() {
	var a = arguments;
	var f = this._func;
	var t = this._this;
	var m = this._tmpElm || null;

	var b = function(e) {
		var args = $A(a);
		if (typeof e == "undefined") e = window.event;

		if (typeof e.currentTarget == "undefined") {
			e.currentTarget = m;
		}

		args.unshift($Event(e));

		return f.apply(t, args.$value());
	};

	return b;
};

/**
 * 함수를 특정 객체의 이벤트에 추가한다
 * @id core.$Fn.attach
 * @import core.$Fn[detach, gc]
 */
$Fn.prototype.attach = function(oElement, sEvent) {
	var f;
	
	if ((oElement instanceof Array) || ($A && (oElement instanceof $A) && (oElement=oElement.$value()))) {
		for(var i=0; i < oElement.length; i++) {
			this.attach(oElement[i], sEvent);
		}
		return this;
	}

	if ($Element && oElement instanceof $Element) {
		oElement = oElement.$value();
	}

	oElement = $(oElement);
	sEvent   = sEvent.toLowerCase();
	
	this._tmpElm = oElement;
	f = this.bindForEvent();
	this._tmpElm = null;

	if (typeof oElement.attachEvent != "undefined") {
		oElement.attachEvent("on"+sEvent, f);
	} else {
		if (sEvent == "mousewheel") sEvent = "DOMMouseScroll";

		if (sEvent == "DOMMouseScroll" && navigator.userAgent.indexOf("WebKit") > 0) {
			var events = "__jindo_wheel_events";

			if (typeof oElement[events] == "undefined") oElement[events] = new Array;
			if (typeof oElement.onmousewheel == "object") {
				oElement.onmousewheel = function(evt) {
					if (!this[events]) return;
					for(var i=0; i < this[events].length; i++) {
						this[events][i](evt);
					}
				}
			}

			oElement[events][oElement[events].length] = f;
		} else {
			oElement.addEventListener(sEvent, f, false);
		}
	}

	var key = "$"+$Fn.gc.count++;
	var inf = {element:oElement, event:sEvent, func:f};
	
	$Fn.gc.pool[key] = this._events[key] = inf;

	return this;
};

/**
 * 함수를 특정 객체의 이벤트에서 제거한다
 * @id core.$Fn.detach
 * @import core.$Fn[attach, gc]
 */
$Fn.prototype.detach = function(oElement, sEvent) {
	if ((oElement instanceof Array) || ($A && (oElement instanceof $A) && (oElement=oElement.$value()))) {
		for(var i=0; i < oElement.length; i++) {
			this.detach(oElement[i], sEvent);
		}
		return this;
	}

	if ($Element && oElement instanceof $Element) {
		oElement = oElement.$value();
	}

	oElement = $(oElement);
	sEvent   = sEvent.toLowerCase();
	
	var e = this._events;
	var f = null;
	
	for(var key in e) {
		try {
			if (e[key].element !== oElement || e[key].event !== sEvent) continue;
			f = e[key].func;
			
			delete e[key];
			delete $Fn.gc.pool[key];
			break;
		} catch(e){
		}
	}

	if (typeof oElement.detachEvent != "undefined") {
		oElement.detachEvent("on"+sEvent, f);
	} else {
		if (sEvent.toLowerCase() == "mousewheel") sEvent = "DOMMouseScroll";

		if (sEvent == "DOMMouseScroll" && navigator.userAgent.indexOf("WebKit") > 0) {
			var events = "__jindo_wheel_events", found = false;
			if (!oElement[events]) return;
			for(var i=0; i < oElement[events].length; i++) {
				if (oElement[events][i] == f) {
					found = true;
				} else if (found) {
					oElement[events][i-1] = oElement[events][i];
				}
			}
			if (oElement[events].length) oElement[events].length--;
		} else {
			oElement.removeEventListener(sEvent, f, false);
		}
	}

	return this;
};

/**
 * 정해진 시간 이후에 정해진 인자로 함수를 호출한다.
 * @id core.$Fn.delay
 * @import core.$Fn.bind
 */
$Fn.prototype.delay = function(nSec, args) {
	if (typeof args == "undefined") args = [];
	setTimeout(this.bind.apply(this, args), nSec*1000);
	
	return this;
};

/**
 * Window가 종료될 때, DOM Element 에 할당된 이벤트 핸들러를 제거한다.
 * @id core.$Fn.gc
 * @import core.$Fn.gcinit
 */
$Fn.gc = function() {
	var p = $Fn.gc.pool;
	
	for(var key in p) {
		try { $Fn(p[key].func).detach(p[key].element, p[key].event)	}catch(e){};
	}
};

$Fn.gc.count = 0;

$Fn.gc.pool = new Array;
if (typeof window != "undefined") {
	$Fn($Fn.gc).attach(window, "unload");
}

/**
 * JavaScript Core 이벤트 객체로부터 $Event 객체를 생성한다.
 * evt 를 $Event 객체의 인스턴스라고 하면, evt.element 로 이벤트가 실행된 객체를 알 수 있다.
 * @id core.$Event
 */
function $Event(e) {
	var cl = arguments.callee;
	if (e instanceof cl) return e;
	if (!(this instanceof cl)) return new cl(e);

	if (typeof e == "undefined") e = window.event;
	if (e === window.event && document.createEventObject) e = document.createEventObject(e);

	this._event = e;
	this._globalEvent = window.event;

	this.type = e.type.toLowerCase();
	if (this.type == "dommousescroll") {
		this.type = "mousewheel";
	}

	this.canceled = false;

	this.element = e.target || e.srcElement;
	this.currentElement = e.currentTarget;
	this.relatedElement = null;

	if (typeof e.relatedTarget != "undefined") {
		this.relatedElement = e.relatedTarget;
	} else if(e.fromElement && e.toElement) {
		this.relatedElement = e[(this.type=="mouseout")?"toElement":"fromElement"];
	}
}

/**
 * 마우스 이벤트 정보 객체를 반환한다.
 * @id core.$Event.mouse
 */
$Event.prototype.mouse = function() {
	var e    = this._event;
	var delta = 0;
	var left  = (e.which&&e.button==0)||!!(e.button&1);
	var mid   = (e.which&&e.button==1)||!!(e.button&4);
	var right = (e.which&&e.button==2)||!!(e.button&2);

	if (e.wheelDelta) {
		delta = e.wheelDelta / 120;
	} else if (e.detail) {
		delta = -e.detail / 3;
	}

	return {
		delta  : delta,
		left   : left,
		middle : mid,
		right  : right
	};
};

/**
 * 키보드 이벤트 정보 객체를 반환한다.
 * @id core.$Event.key
 */
$Event.prototype.key = function() {
	var e     = this._event;
	var k     = e.keyCode;

	return {
		keyCode : k,
		alt     : e.altKey,
		ctrl    : e.ctrlKey,
		meta    : e.metaKey,
		shift   : e.shiftKey,
		up      : (k == 38),
		down    : (k == 40),
		left    : (k == 37),
		right   : (k == 39),
		enter   : (k == 13)
	}
};

/**
 * 커서 위치 정보 객체를 반환한다.
 * @id core.$Event.position
 */
$Event.prototype.pos = function() {
	var e   = this._event;
	var b   = document.body;
	var de  = document.documentElement;
	var pos = [b.scrollLeft || de.scrollLeft,b.scrollTop || de.scrollTop];

	return {
		clientX : e.clientX,
		clientY : e.clientY,
		pageX   : 'pageX' in e ? e.pageX : e.clientX+pos[0]-b.clientLeft,
		pageY   : 'pageY' in e ? e.pageY : e.clientY+pos[1]-b.clientTop,
		layerX  : 'offsetX' in e ? e.offsetX : e.layerX - 1,
		layerY  : 'offsetY' in e ? e.offsetY : e.layerY - 1
	};
};

/**
 * 현재의 이벤트를 중지한다.
 * @id core.$Event.stop
 */
$Event.prototype.stop = function() {
	var e = (window.event && window.event == this._globalEvent)?this._globalEvent:this._event;
	
	this.canceled = true;

	if (typeof e.preventDefault != "undefined") e.preventDefault();
	if (typeof e.stopPropagation != "undefined") e.stopPropagation();

	e.returnValue = false;
	e.cancelBubble = true;

	return this;
};
/**
 * $ElementList 객체를 반환한다.
 * @id core.$ElementList
 */
function $ElementList(els) {
	var cl = arguments.callee;
	if (els instanceof cl) return els;
	if (!(this instanceof cl)) return new cl(els);
	
	if (els instanceof Array || ($A && els instanceof $A)) {
		els = $A(els);
	} else if (typeof els == "string" && cssquery) {
		els = $A(cssquery(els));
	} else {
		els = $A();
	}

	this._elements = els.map(function(v,i,a){ return $Element(v) });
}

$ElementList.prototype.get = function(idx) {
	return this._elements[idx];
};

$ElementList.prototype.getFirst = function() {
	return this.get(0);
};

$ElementList.prototype.getLast = function() {
	return this.get(Math.Max(this._elements.length-1,0));
};

(function(proto){
	var setters = 'show,hide,toggle,addClass,removeClass,toggleClass,fireEvent,leave,';
	setters += 'empty,appear,disappear,className,width,height,text,html,css,attr';
	
	$A(setters.split(',')).forEach(function(name){
		proto[name] = function() {
			var args = $A(arguments).$value();
			this._elements.forEach(function(el){
				el[name].apply(el, args);
			});
			
			return this;
		}
	});
	
	$A(['appear','disapeear']).forEach(function(name){
		proto[name] = function(duration, callback) {
			var len  = this._elements.length;
			var self = this;
			this._elements.forEach(function(el,idx){
				if(idx == len-1) el[name](duration, function(){callback(self)});
				else el[name](duration);
			});
		}
	});
})($ElementList.prototype);
/**
 * 문자열을 다루는 클래스
 * @id core.$S
 */
function $S(str) {
	var cl = arguments.callee;

	if (typeof str == "undefined") str = "";
	if (str instanceof cl) return str;
	if (!(this instanceof cl)) return new cl(str);

	this._str = str+"";
}

/**
 * String 객체를 반환한다.
 * @id core.$S.$value
 */
$S.prototype.$value = function() {
	return this._str;
};

/**
 * String 객체를 반환한다.
 * @id core.$S.toString
 */
$S.prototype.toString = $S.prototype.$value;

/**
 * 문자열의 양 끝 공백을 제거한다.
 * @id core.$S.trim
 */
$S.prototype.trim = function() {
	return $S(this._str.replace(/^\s+|\s+$/g, ""));
};

/**
 * HTML 특수문자를 엔티티 형식으로 변환한다.
 * @id core.$S.escapeHTML
 */
$S.prototype.escapeHTML = function() {
	var entities = {'"':'quot','&':'amp','<':'lt','>':'gt'};
	var s = this._str.replace(/[<>&"]/g, function(m0){
		return entities[m0]?'&'+entities[m0]+';':m0;
	});
	return $S(s);
};

/**
 * 문자열에서 XML/HTML 태그를 제거한다.
 * @id core.$S.stripTags
 */
$S.prototype.stripTags = function() {
	return $S(this._str.replace(/<\/?(?:h[1-5]|[a-z]+(?:\:[a-z]+)?)[^>]*>/ig, ''));
};

/**
 * 문자열을 주어진 숫자만큼 반복한다.
 * @id core.$S.times
 */
$S.prototype.times = function(nTimes) {
	var buf = [];
	for(var i=0; i < nTimes; i++) {
		buf[buf.length] = this._str;
	}

	return $S(buf.join(''));
};

/**
 * HTML 엔티티 문자열을 ASCII문자열로 변환한다.
 * @id core.$S.unescapeHTML
 */
$S.prototype.unescapeHTML = function() {
	var entities = {'quot':'"','amp':'&','lt':'<','gt':'>'};
	var s = this._str.replace(/&([a-z]+);/g, function(m0,m1){
		return entities[m1]?entities[m1]:m0;
	});
	return $S(s);
};

/**
 * 문자열을 겹따옴표에 포함될 수 있는 ASCII문자열로 이스케이프 처리한다.
 * @id core.$S.escape
 */
$S.prototype.escape = function() {
	var s = this._str.replace(/([\u0080-\uFFFF]+)|[\n\r\t"'\\]/g, function(m0,m1,_){
		if(m1) return escape(m1).replace(/%/g,'\\');
		return (_={"\n":"\\n","\r":"\\r","\t":"\\t"})[m0]?_[m0]:"\\"+m0;
	});

	return $S(s);
};

/**
 * 문자열의 실제 bytes수를 반환한다. 현재는 utf-8과 그 밖의 문자셋으로만 구분한다.
 * @id core.$S.bytes
 */
$S.prototype.bytes = function() {
	var uni_bytes = 2, bytes = 0, len = this._str.length;
	var charset = ((document.charset || document.characterSet || document.defaultCharset)+"").toLowerCase();

	if (charset == "utf-8") uni_bytes = 3;

	for(var i=0; i < len; i++) {
		bytes += (this._str.charCodeAt(i) > 128)?uni_bytes:1;
	}

	return bytes;
};

/**
 * URL 쿼리 스트링을 객체 형태로 파싱합니다.
 * @id core.$S.parseString
 */
$S.prototype.parseString = function() {
	var str = this._str.split(/&/g), pos, key, val, buf = {};

	for(var i=0; i < str.length; i++) {
		key = str[i].substring(0, pos=str[i].indexOf("="));
		val = decodeURIComponent(str[i].substring(pos+1));

		if (key.substr(key.length-2,2) == "[]") {
			key = key.substring(0, key.length-2);
			if (typeof buf[key] == "undefined") buf[key] = [];
			buf[key][buf[key].length] = val;
		} else {
			buf[key] = val;
		}
	}
	
	return buf;
};

/**
 * 형식 문자열을 주어진 인자에 맞게 반환합니다.
 * @id core.$S.format
 * @import core.$S.times
 */
$S.prototype.format = function() {
	var args = arguments;
	var idx  = 0;
	var s = this._str.replace(/%([ 0])?(-)?([1-9][0-9]*)?([bcdsoxX])/g, function(m0,m1,m2,m3,m4){
		var a = args[idx++];
		var ret = "", pad = "";

		m3 = m3?+m3:0;

		if (m4 == "s") {
			ret = a+"";
		} else if (" bcdoxX".indexOf(m4) > 0) {
			if (typeof a != "number") return "";
			ret = (m4 == "c")?String.fromCharCode(a):a.toString(({b:2,d:10,o:8,x:16,X:16})[m4]);
			if (" X".indexOf(m4) > 0) ret = ret.toUpperCase();
		}

		if (ret.length < m3) pad = $S(m1||" ").times(m3 - ret.length).toString();
		(m2 == '-')?(ret+=pad):(ret=pad+ret);

		return ret;
	});

	return $S(s);
};

/**
 * @author hooriza (ajaxUI3 team)
 */

/**
 * $Document 객체를 반환한다.
 * @id core.$Document
 */
function $Document() {
	var cl = arguments.callee;

	if (this instanceof cl) {
		this._docKey = this.renderingMode() == 'Standards' ? 'documentElement' : 'body';
		return;
	}
	
	if (cl._singleton) return cl._singleton;
	return (cl._singleton = new cl());
};

/**
 * 문서의 실제 가로, 세로 크기를 구한다
 * @id core.$Document.scrollSize
 * @import core.$Agent.navigator
 * @import core.$Document.clientSize
 */
$Document.prototype.scrollSize = function() {

	var oBrowser = $Agent().navigator();

	var oDoc = document[this._docKey];
	if (oBrowser.opera || oBrowser.safari) oDoc = document.body;
 
	// IE6 미만이면
	if (oBrowser.ie && oBrowser.version < 6) {
	 
		var aOld = [ oDoc.scrollLeft, oDoc.scrollTop ];
		var oClient = this.clientSize();
	 
		oDoc.scrollLeft = 999999;
		oDoc.scrollTop = 999999;
	 
		var aRet = {
			width : oDoc.scrollLeft + oClient.width,
			height : oDoc.scrollTop + oClient.height
		};
	 
		oDoc.scrollLeft = aOld[0];
		oDoc.scrollTop = aOld[1];
	 
		return aRet;
	 
	}
 
	return {
		width : Math.max(oDoc.scrollWidth, oDoc.clientWidth),
		height : Math.max(oDoc.scrollHeight, oDoc.clientHeight)
	};

};

/**
 * 브라우저에서 보이는 문서의 가로, 세로 크기를 구한다
 * @id core.$Document.clientSize
 */
$Document.prototype.clientSize = function() {

	var oDoc = document[this._docKey];
 
	return {
		width : oDoc.clientWidth,
		height : oDoc.clientHeight
	};

};

/**
 * 문서의 렌더링 방식을 얻는다
 * @id core.$Document.renderingMode
 * @import core.$Agent.navigator
 */
$Document.prototype.renderingMode = function() {

	var oBrowser = $Agent().navigator();
	var sRet;

	if ('compatMode' in document)
		sRet = document.compatMode == 'CSS1Compat' ? 'Standards' : (oBrowser.ie ? 'Quirks' : 'Almost');
	else
		sRet = oBrowser.safari ? 'Standards' : 'Quirks';

	return sRet;

};

/**
 * @author hooriza (ajaxUI3 team)
 */

/**
 * $Form 객체를 반환한다.
 * @id core.$Form
 */
function $Form(el) {
	var cl = arguments.callee;
	if (el instanceof cl) return el;
	if (!(this instanceof cl)) return new cl(el);
	
	el = $(el);
	
	if (!el.tagName || el.tagName.toUpperCase() != 'FORM') throw new Error('The element should be a FORM element');
	this._form = el;
}

/**
 * 원래 <form> 엘리먼트의 객체를 얻어온다
 * @id core.$Form.$value
 */
$Form.prototype.$value = function() {
	return this._form;
};

/**
 * 특정 또는 전체 입력요소를 문자열 형태로 반환한다.
 * @id core.$Form.serialize
 * @import core.$Form.element, core.$Form.value, core.$A.forEach, core.$H.toQueryString
 */
$Form.prototype.serialize = function() {

 	var self = this;
 	var oRet = {};
 	
 	var nLen = arguments.length;
 	var fpInsert = function(sKey) {
 		var sVal = self.value(sKey);
 		if (typeof sVal != 'undefined') oRet[sKey] = sVal;
 	};
 	
 	if (nLen == 0) 
	 	$A(this.element()).forEach(function(o) { if (o.name) fpInsert(o.name); });
 	else
 		for (var i = 0; i < nLen; i++) fpInsert(arguments[i]);
 	
	return $H(oRet).toQueryString();
	
};

/**
 * 특정 또는 전체 입력요소를 반환한다.
 * @id core.$Form.element
 */
$Form.prototype.element = function(sKey) {

	if (arguments.length > 0)
		return this._form[sKey];
	
	return this._form.elements;
	
};

/**
 * 입력 요소의 활성화 여부를 얻거나 설정한다.
 * @id core.$Form.enable
 * @import core.$H.forEach, core.$A.forEach, core.$A.Break, core.$Form.element
 */
$Form.prototype.enable = function() {
	
	var sKey = arguments[0];

	if (typeof sKey == 'object') {
		
		var self = this;
		$H(sKey).forEach(function(bFlag, sKey) { self.enable(sKey, bFlag); });
		return this;
		
	}
	
	var aEls = this.element(sKey);
	if (!aEls) return this;
	aEls = aEls.nodeType == 1 ? [ aEls ] : aEls;
	
	if (arguments.length < 2) {
		
		var bEnabled = true;
		$A(aEls).forEach(function(o) { if (o.disabled) {
			bEnabled = false;
			$A.Break();
		}});
		return bEnabled;
		
	} else { // setter
		
		var sFlag = arguments[1];
		$A(aEls).forEach(function(o) { o.disabled = !sFlag; });
		
		return this;
		
	}
	
};

/**
 * 입력 요소의 값을 얻거나 설정한다.
 * @id core.$Form.value
 * @import core.$H.forEach, core.$A.forEach, core.$Form.element
 */
$Form.prototype.value = function(sKey) {
	
	if (typeof sKey == 'object') {
		
		var self = this;
		$H(sKey).forEach(function(bFlag, sKey) { self.value(sKey, bFlag); });
		return this;
		
	}
	
	var aEls = this.element(sKey);
	if (!aEls) throw new Error('The element is not exist');
	aEls = aEls.nodeType == 1 ? [ aEls ] : aEls;
	
	if (arguments.length > 1) { // setter
		
		var sVal = arguments[1];
		
		$A(aEls).forEach(function(o) {
			
			switch (o.type) {
			case 'radio':
			case 'checkbox':
				o.checked = (o.value == sVal);
				break;
				
			case 'select-one':
				var nIndex = -1;
				for (var i = 0, len = o.options.length; i < len; i++)
					if (o.options[i].value == sVal) nIndex = i;
				o.selectedIndex = nIndex;

				break;
				
			default:
				o.value = sVal;
				break;
			}
			
		});
		
		return this;
	}

	// getter
	
	var aRet = [];
	
	$A(aEls).forEach(function(o) {
		
		switch (o.type) {
		case 'radio':
		case 'checkbox':
			if (o.checked) aRet.push(o.value);
			break;
		
		case 'select-one':
			if (o.selectedIndex != -1) aRet.push(o.options[o.selectedIndex].value);
			break;
			
		default:
			aRet.push(o.value);
			break;
		}
		
	});
	
	return aRet.length > 1 ? aRet : aRet[0];
	
};

/**
 * 폼을 서밋한다.
 * @id core.$Form.submit
 */
$Form.prototype.submit = function() {
	
	this._form.submit();
	return this;
	
};

/**
 * 폼을 리셋한다.
 * @id core.$Form.reset
 */
$Form.prototype.reset = function() {
	
	this._form.reset();
	return this;
	
};

/**
 * 템플릿을 실행한다.
 * @id core.$Template
 */
function $Template(str) {
    var obj = null;
    var cl  = arguments.callee;

    if (str instanceof cl) return str;
    if (!(this instanceof cl)) return new cl(str);

    if(typeof str == "undefined") str = "";
    else if((obj=$(str)) && obj.tagName.toUpperCase() == "TEXTAREA") {
            str = obj.value.replace(/^\s+|\s+$/g,"");
    }

    this._str = str+"";
}
$Template.splitter = /(?!\\)[\{\}]/g;
$Template.pattern  = /^(?:if (.+)|elseif (.+)|for (?:(.+)\:)?(.+) in (.+)|(else)|\/(if|for)|=(.+))$/;

/**
 * 템플릿 해석을 실행한다.
 * @id core.$Template.process
 * @param {Object} data 변수 및 함수 데이터
 */
$Template.prototype.process = function(data) {
    var tpl = this._str.split($Template.splitter), i = tpl.length;
    var map = {'"':'\\"','\\':'\\\\','\n':'\\n','\r':'\\r','\t':'\\t','\f':'\\f'};
    var reg = [/([a-zA-Z_][\w\.]*)/g, /[\n\r\t\f"\\]/g, /^\s+/, /\s+$/];
    var cb  = ["d.$1", function(m){return map[m]||m},"",""];
    var stm = [];

    // no pattern
    if(i<2) return tpl;

    while(i--) {
        if(i%2) {
            tpl[i] = tpl[i].replace($Template.pattern, function(){
                var m = arguments;

                // variables
                if(m[8]) return 's[s.length]=d.'+m[8]+';';

                // if
                if(m[1]) {
                    return 'if('+m[1].replace(reg[0],cb[0])+'){';
                }

                // else if
                if(m[2]) return '}else if('+m[2].replace(reg[0],cb[0])+'){';

                // for loop
                if(m[5]) {
                    return 'n=0;t=d.'+m[5]+';p=(t instanceof Array);for(var x in t){if((p&&isNaN(parseInt(x)))||(!p&&!t.propertyIsEnumerable(x)))continue;d.'+m[4]+'=t[x];'+(m[3]?'d.'+m[3]+'=x;':'')+'n++;';
                }

                // else
                if(m[6]) return '}else{';

                // end if, end for
                if(m[7]) {
                    return '};';
                }

                return m[0];
            });
        } else if(tpl[i]){
            tpl[i] = 's[s.length]="'+tpl[i].replace(reg[1],cb[1])+'";';
        }
    }

    tpl = (new Function("d",'var s=[];'+tpl.join('')+'return s.join("")'))(data);

    return tpl;
};
/**
 * $Cookie 객체를 반환한다.
 * @id core.$Date
 */
function $Date(src) {
	var a=arguments,t="";
	var cl=arguments.callee;

	if (src && src instanceof cl) return src;
	if (!(this instanceof cl)) return new cl(a[0],a[1],a[2],a[3],a[4],a[5],a[6]);

	if ((t=typeof src) == "string") {
		this._date = cl.parse(src).$value();
	} else if (t == "number") {
		if (typeof a[1] == "undefined") this._date = new Date(src);
		else this._date = new Date(a[0],a[1],a[2],a[3],a[4],a[5],a[6]);
	} else if (t == "object" && src.constructor == Date) {
		(this._date = new Date).setTime(src.getTime());
		this._date.setMilliseconds(src.getMilliseconds());
	} else {
		this._date = new Date;
	}
}

/**
 * 달, 요일, 오전/오후 이름. s_ 가 붙은 것은 짧은 이름이다.
 * @id core.$Date.names
 */
$Date.names = {
	month   : ["January","Febrary","March","April","May","June","July","August","September","October","Novermber","December"],
	s_month : ["Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec"],
	day     : ["Sunday","Monday","Tuesday","Wednesday","Thursday","Friday","Saturday"],
	s_day   : ["Sun","Mon","Tue","Wed","Thu","Fri","Sat"],
	ampm    : ["AM", "PM"]
};

/**
 * 현재 시간을 밀리초 단위의 정수로 리턴
 * @id core.$Date.now
 */
$Date.now = function() {
	return Date.now();
};

/**
 * 주어진 문자열을 해석한 값을 가지는 객체 생성
 * @id core.$Date.parse
 */
$Date.parse = function(strDate) {
	return Date.parse(strDate);
};

/**
 * 원래의 Date 객체를 반환한다.
 * @id core.$Date.$value
 */
$Date.prototype.$value = function(){
	return this._date;
};

/**
 * 지정한 형식에 맞춰서 문자열을 출력한다. 형식문자열은 PHP의 date 함수에 사용하는 것에 준한다.
 * @id core.$Date.format
 * @import core.$Date[time,isLeapYear]
 */
$Date.prototype.format = function(strFormat){
	var o = {};
	var d = this._date;
	
	return (strFormat||"").replace(/[a-z]/ig, function callback(m){
		if (typeof o[m] != "undefined") return o[m];

		switch(m) {
			case"d":
			case"j":
				o.j = d.getDate();
				o.d = (o.j>9?"":"0")+o.j;
				return o[m];
			case"l":
			case"D":
			case"w":
			case"N":
				o.w = d.getDay();
				o.N = o.w?o.w:7;
				o.D = $Date.names.s_day[o.w];
				o.j = $Date.names.day[o.w];
				return o[m];
			case"S":
				return (!!(o.S=["st","nd","rd"][d.getDate()]))?o.S:(o.S="th");
			case"z":
				o.z = Math.floor((d.getTime() - (new Date(d.getFullYear(),0,1)).getTime())/(3600*24*1000));
				return o.z;
			case"m":
			case"n":
				o.n = d.getMonth()+1;
				o.m = (o.n>9?"":"0")+o.n;
				return o[m];
			case"L":
				o.L = this.isLeapYear();
				return o.L;
			case"o":
			case"Y":
			case"y":
				o.o = o.Y = d.getFullYear();
				o.y = (o.o+"").substr(2);
				return o[m];
			case"a":
			case"A":
			case"g":
			case"G":
			case"h":
			case"H":
				o.G = d.getHours();
				o.g = (o.g=o.G%12)?o.g:12;
				o.A = o.G<12?$Date.names.ampm[0]:$Date.names.ampm[1];
				o.a = o.A.toLowerCase();
				o.H = (o.G>9?"":"0")+o.G;
				o.h = (o.g>9?"":"0")+o.g;
				return o[m];
			case"i":
				o.i = (((o.i=d.getMinutes())>9)?"":"0")+o.i;
				return o.i;
			case"s":
				o.s = (((o.s=d.getSeconds())>9)?"":"0")+o.i;
				return o.s;
			case"u":
				o.u = d.getMilliseconds();
				return o.u;
			case"U":
				o.U = this.time();
				return o.U;
			default:
				return m;
		}
	});
};

/**
 * 1970/01/01 00:00:00 UTC 기준으로 지난 시간을 설정하거나 가져온다.
 * @id core.$Date.time
 */
$Date.prototype.time = function(nTime) {
	if (typeof nTime == "number") {
		this._date.setTime(nTime);
		return this;
	}

	return this._date.getTime();
};

/**
 * 년도를 설정하거나 가져온다.
 * @id core.$Date.date
 */
$Date.prototype.year = function(nYear) {
	if (typeof nYear == "number") {
		this._date.setFullYear(nDate);
		return this;
	}

	return this._date.getFullYear();
};

/**
 * 달을 설정하거나 가져온다.
 * @id core.$Date.date
 */
$Date.prototype.month = function(nMon) {
	if (typeof nMon == "number") {
		this._date.setMonth(nDate);
		return this;
	}

	return this._date.getMonth();
};

/**
 * 날짜를 설정하거나 가져온다.
 * @id core.$Date.date
 */
$Date.prototype.date = function(nDate) {
	if (typeof nDate == "number") {
		this._date.setDate(nDate);
		return this;
	}

	return this._date.getDate();
};

/**
 * 요일을 가져온다. 0이 일요일, 6이 토요일이다.
 * @id core.$Date.day
 */
$Date.prototype.day = function() {
	return this._date.getDay();
};

/**
 * 시간을 설정하거나 가져온다.
 * @id core.$Date.hours
 */
$Date.prototype.hours = function(nHour) {
	if (typeof nHour == "number") {
		this._date.setHours(nHour);
		return this;
	}

	return this._date.getHours();
};

/**
 * 초을 설정하거나 가져온다.
 * @id core.$Date.seconds
 */
$Date.prototype.seconds = function(nSec) {
	if (typeof nSec == "number") {
		this._date.setSeconds(nSec);
		return this;
	}

	return this._date.getSeconds();
};

/**
 * 분을 설정하거나 가져온다.
 * @id core.$Date.minutes
 */
$Date.prototype.minutes = function(nMin) {
	if (typeof nMin == "number") {
		this._date.setMinutes(nMin);
		return this;
	}

	return this._date.getMinutes();
};

/**
 * 윤년인지의 여부를 반환한다.
 * @id core.$Date.isLeapYear
 */
$Date.prototype.isLeapYear = function() {
	var y = this._date.getFullYear();

	return !(y%4)&&!!(y%100)||!(y%400);
};
/**
 * CSS Selector Engine 3
 *
 * @author hooriza (ajaxUI 3 team)
 * @compatibility with IE55, IE6, IE7, FF2, Opera9, Safari3
 *
 * 아직 WebKit 및 IE8 의 querySelector 메쏘드군의 기능이 완전하지 않아 사용하지 않음
 */
var cssquery = (function() {
	
	var sVersion = '2.0.7';
	
	var debugOption = { repeat : 1 };
	
	// 빠른 처리를 위해 노드마다 유일키 값 셋팅
	var UID = 1;
	
	var cost = 0;
	var validUID = {};
	
	var getUID4HTML = function(oEl) {
		
		var sUID = oEl._cssquery_UID;
		if (sUID && validUID[sUID] == oEl) return sUID;
		
		sUID = oEl._cssquery_UID = UID++;
		validUID[sUID] = oEl;
		
		return sUID;

	};
	
	var getUID4XML = function(oEl) {
		
		var nUID = oEl.getAttribute('_cssquery_UID');
		
		if (!nUID) {
			nUID = UID++;
			oEl.setAttribute('_cssquery_UID', nUID);
		}
		
		return nUID;
		
	};
	
	var getUID = getUID4HTML;
	
	var uniqid = function(sPrefix) {
		return (sPrefix || '') + new Date().getTime() + parseInt(Math.random() * 100000000);
	};
	
	/* DON'T SHRINK THIS */
	var getChilds = function(oEl, sTagName) {
		if (sTagName == '*') return oEl.all || oEl.getElementsByTagName(sTagName);
		return oEl.getElementsByTagName(sTagName);
	};

	var clearKeys = function() {
		 backupKeys._keys = {};
	};
	
	/* DON'T SHRINK THIS */
	var oDocument = document;
	
	var bXMLDocument = false;
	
	// 따옴표, [] 등 파싱에 문제가 될 수 있는 부분 replace 시켜놓기
	var backupKeys = function(sQuery) {
		
		var oKeys = backupKeys._keys;
		
		// 작은 따옴표 걷어내기
		sQuery = sQuery.replace(/'(\\'|[^'])*'/g, function(sAll) {
			var uid = uniqid('QUOT');
			oKeys[uid] = sAll;
			return uid;
		});
		
		// 큰 따옴표 걷어내기
		sQuery = sQuery.replace(/"(\\"|[^"])*"/g, function(sAll) {
			var uid = uniqid('QUOT');
			oKeys[uid] = sAll;
			return uid;
		});
		
		// [ ] 형태 걷어내기
		sQuery = sQuery.replace(/\[(.*?)\]/g, function(sAll, sBody) {
			if (sBody.indexOf('ATTR') == 0) return sAll;
			var uid = '[' + uniqid('ATTR') + ']';
			oKeys[uid] = sAll;
			return uid;
		});
	
		// ( ) 형태 걷어내기
		var bChanged;
		
		do {
			
			bChanged = false;
		
			sQuery = sQuery.replace(/\(((\\\)|[^)|^(])*)\)/g, function(sAll, sBody) {
				if (sBody.indexOf('BRCE') == 0) return sAll;
				var uid = '_' + uniqid('BRCE');
				oKeys[uid] = sAll;
				bChanged = true;
				return uid;
			});
		
		} while(bChanged);
	
		return sQuery;
		
	};
	
	// replace 시켜놓은 부분 복구하기
	var restoreKeys = function(sQuery, bOnlyAttrBrace) {
		
		var oKeys = backupKeys._keys;
	
		var bChanged;
		var rRegex = bOnlyAttrBrace ? /(\[ATTR[0-9]+\])/g : /(QUOT[0-9]+|\[ATTR[0-9]+\])/g;
		
		do {
			
			bChanged = false;
	
			sQuery = sQuery.replace(rRegex, function(sKey) {
				
				if (oKeys[sKey]) {
					bChanged = true;
					return oKeys[sKey];
				}
				
				return sKey;
	
			});
		
		} while(bChanged);
		
		// ( ) 는 한꺼풀만 벗겨내기
		sQuery = sQuery.replace(/_BRCE[0-9]+/g, function(sKey) {
			return oKeys[sKey] ? oKeys[sKey] : sKey;
		});
		
		return sQuery;
		
	};
	
	// replace 시켜놓은 문자열에서 Quot 을 제외하고 리턴
	var restoreString = function(sKey) {
		
		var oKeys = backupKeys._keys;
		var sOrg = oKeys[sKey];
		
		if (!sOrg) return sKey;
		return eval(sOrg);
		
	};
	
	var wrapQuot = function(sStr) {
		return '"' + sStr.replace(/"/g, '\\"') + '"';
	};
	
	var getStyleKey = function(sKey) {

		if (/^@/.test(sKey)) return sKey.substr(1);
		return null;
		
	};
	
	var getCSS = function(oEl, sKey) {
		
		if (oEl.currentStyle) {
			
			if (sKey == "float") sKey = "styleFloat";
			return oEl.currentStyle[sKey] || oEl.style[sKey];
			
		} else if (window.getComputedStyle) {
			
			return oDocument.defaultView.getComputedStyle(oEl, null).getPropertyValue(sKey.replace(/([A-Z])/g,"-$1").toLowerCase()) || oEl.style[sKey];
			
		}

		if (sKey == "float" && /MSIE/.test(window.navigator.userAgent)) sKey = "styleFloat";
		return oEl.style[sKey];
		
	};
	
	var getDefineCode = function(sKey) {
		
		var sVal;
		var sStyleKey;

		if (bXMLDocument) {
			
			sVal = 'oEl.getAttribute("' + sKey + '")';
		
		} else {
		
			if (sStyleKey = getStyleKey(sKey)) {
				
				sKey = '$$' + sStyleKey;
				sVal = 'getCSS(oEl, "' + sStyleKey + '")';
				
			} else {
				
				switch (sKey) {
				case 'checked':
					sVal = 'oEl.checked + ""';
					break;
					
				case 'disabled':
					sVal = 'oEl.disabled + ""';
					break;
					
				case 'enabled':
					sVal = '!oEl.disabled + ""';
					break;
					
				case 'readonly':
					sVal = 'oEl.readOnly + ""';
					break;
					
				case 'selected':
					sVal = 'oEl.selected + ""';
					break;
					
				case 'class':
					sVal = 'oEl.className';
					break;
					
				default:
					sVal = 'oEl.getAttribute("' + sKey + '")';
				}
				
			}
			
		}
			
		return '_' + sKey + ' = ' + sVal;
	};
	
	var getReturnCode = function(oExpr) {
		
		var sStyleKey = getStyleKey(oExpr.key);
		
		var sVar = '_' + (sStyleKey ? '$$' + sStyleKey : oExpr.key);
		var sVal = oExpr.val ? wrapQuot(oExpr.val) : '';
		
		switch (oExpr.op) {
		case '~=':
			return '(' + sVar + ' && (" " + ' + sVar + ' + " ").indexOf(" " + ' + sVal + ' + " ") > -1)';
		case '^=':
			return '(' + sVar + ' && ' + sVar + '.indexOf(' + sVal + ') == 0)';
		case '$=':
			return '(' + sVar + ' && ' + sVar + '.substr(' + sVar + '.length - ' + oExpr.val.length + ') == ' + sVal + ')';
		case '*=':
			return '(' + sVar + ' && ' + sVar + '.indexOf(' + sVal + ') > -1)';
		case '!=':
			return '(' + sVar + ' != ' + sVal + ')';
		case '=':
			return '(' + sVar + ' == ' + sVal + ')';
		}
	
		return '(' + sVar + ')';
		
	};

	var getNodeIndex = function(oEl) {
		
		var nUID = getUID(oEl);
		var nIndex = oNodeIndexes[nUID] || 0;
		
		// 노드 인덱스를 구할 수 없으면
		if (nIndex == 0) {

			for (var oSib = (oEl.parentNode || oEl._IE5_parentNode).firstChild; oSib; oSib = oSib.nextSibling) {
				
				if (oSib.nodeType != 1) continue;
				nIndex++;
				
				setNodeIndex(oSib, nIndex);
				
			}
			
			nIndex = oNodeIndexes[nUID];
			
		}
		
		return nIndex;
		
	};
	
	// 몇번째 자식인지 설정하는 부분
	var oNodeIndexes = {};

	var setNodeIndex = function(oEl, nIndex) {
		var nUID = getUID(oEl);
		oNodeIndexes[nUID] = nIndex;
	};
	
	var unsetNodeIndexes = function() {
		setTimeout(function() { oNodeIndexes = {}; }, 0);
	};
	
	// 가상 클래스
	/* DON'T SHRINK THIS */
	var oPseudoes = {
	
		'contains' : function(oEl, sOption) {
			return (oEl.innerText || oEl.textContent || '').indexOf(sOption) > -1;
		},
		
		'last-child' : function(oEl, sOption) {
			for (oEl = oEl.nextSibling; oEl; oEl = oEl.nextSibling)
				if (oEl.nodeType == 1)
					return false;
			
			return true;
		},
		
		'first-child' : function(oEl, sOption) {
			for (oEl = oEl.previousSibling; oEl; oEl = oEl.previousSibling)
				if (oEl.nodeType == 1)
					return false;
					
			return true;
		},
		
		'only-child' : function(oEl, sOption) {
			var nChild = 0;
			
			for (var oChild = (oEl.parentNode || oEl._IE5_parentNode).firstChild; oChild; oChild = oChild.nextSibling) {
				if (oChild.nodeType == 1) nChild++;
				if (nChild > 1) return false;
			}
			
			return nChild ? true : false;
		},

		'empty' : function(oEl, _) {
			return oEl.firstChild ? false : true;
		},
		
		'nth-child' : function(oEl, nMul, nAdd) {
			var nIndex = getNodeIndex(oEl);
			return nIndex % nMul == nAdd;
		},
		
		'nth-last-child' : function(oEl, nMul, nAdd) {
			var oLast = (oEl.parentNode || oEl._IE5_parentNode).lastChild;
			for (; oLast; oLast = oLast.previousSibling)
				if (oLast.nodeType == 1) break;
				
			var nTotal = getNodeIndex(oLast);
			var nIndex = getNodeIndex(oEl);
			
			var nLastIndex = nTotal - nIndex + 1;
			return nLastIndex % nMul == nAdd;
		}
		
	};
	
	// 단일 part 의 body 에서 expression 뽑아냄
	var getExpression = function(sBody) {

		var oRet = { defines : '', returns : 'true' };
		
		var sBody = restoreKeys(sBody, true);
	
		var aExprs = [];
		var aDefineCode = [], aReturnCode = [];
		var sId, sTagName;
		
		// 유사클래스 조건 얻어내기
		var sBody = sBody.replace(/:([\w-]+)(\(([^)]*)\))?/g, function(_, sType, _, sOption) {
			
			switch (sType) {
			case 'not':
				var oInner = getExpression(sOption); // 괄호 안에 있는거 재귀파싱하기
				
				var sFuncDefines = oInner.defines;
				var sFuncReturns = oInner.returnsID + oInner.returnsTAG + oInner.returns;
				
				aReturnCode.push('!(function() { ' + sFuncDefines + ' return ' + sFuncReturns + ' })()');
				break;
				
			case 'nth-child':
			case 'nth-last-child':
				sOption =  restoreString(sOption);
				
				if (sOption == 'even') sOption = '2n';
				else if (sOption == 'odd') sOption = '2n+1';

				var nMul, nAdd;
				
				if (/([0-9]*)n([+-][0-9]+)*/.test(sOption)) {
					nMul = parseInt(RegExp.$1) || 1;
					nAdd = parseInt(RegExp.$2) || 0;
				} else {
					nMul = Infinity;
					nAdd = parseInt(sOption);
				}
				
				aReturnCode.push('oPseudoes[' + wrapQuot(sType) + '](oEl, ' + nMul + ', ' + nAdd + ')');
				break;
				
			case 'first-of-type':
			case 'last-of-type':
				sType = (sType == 'first-of-type' ? 'nth-of-type' : 'nth-last-of-type');
				sOption = 1;
				
			case 'nth-of-type':
			case 'nth-last-of-type':
				sOption =  restoreString(sOption);
				
				if (sOption == 'even') sOption = '2n';
				else if (sOption == 'odd') sOption = '2n+1';

				var nMul, nAdd;
				
				if (/([0-9]*)n([+-][0-9]+)*/.test(sOption)) {
					nMul = parseInt(RegExp.$1) || 1;
					nAdd = parseInt(RegExp.$2) || 0;
				} else {
					nMul = Infinity;
					nAdd = parseInt(sOption);
				}
				
				oRet.nth = [ nMul, nAdd, sType ];
				break;
				
			default:
				sOption = sOption ? restoreString(sOption) : '';
				aReturnCode.push('oPseudoes[' + wrapQuot(sType) + '](oEl, ' + wrapQuot(sOption) + ')');
				break;
			}
			
			return '';
			
		});
		
		// [key=value] 형태 조건 얻어내기
		var sBody = sBody.replace(/\[(@?[\w-]+)(([!^~$*]?=)([^\]]*))?\]/g, function(_, sKey, _, sOp, sVal) {
			
			sKey = restoreString(sKey);
			sVal = restoreString(sVal);
			
			if (sKey == 'checked' || sKey == 'disabled' || sKey == 'enabled' || sKey == 'readonly' || sKey == 'selected') {
				
				if (!sVal) {
					sOp = '=';
					sVal = 'true';
				}
				
			}
			
			aExprs.push({ key : sKey, op : sOp, val : sVal });
			return '';
	
		});
	
		// 클래스 조건 얻어내기
		var sBody = sBody.replace(/\.([\w-]+)/g, function(_, sClass) { 
			aExprs.push({ key : 'class', op : '~=', val : sClass });
			return '';
		});
		
		// id 조건 얻어내기
		var sBody = sBody.replace(/#([\w-]+)/g, function(_, sIdValue) {
			if (bXMLDocument) aExprs.push({ key : 'id', op : '=', val : sIdValue });
			else sId = sIdValue;
			return '';
		});
		
		sTagName = sBody == '*' ? '' : sBody;
	
		// match 함수 코드 만들어 내기
		var oVars = {};
		
		for (var i = 0, oExpr; oExpr = aExprs[i]; i++) {
			
			var sKey = oExpr.key;
			
			if (!oVars[sKey]) aDefineCode.push(getDefineCode(sKey));
			aReturnCode.unshift(getReturnCode(oExpr)); // 유사클래스 조건 검사가 맨 뒤로 가도록 unshift 사용
			oVars[sKey] = true;
			
		}
		
		if (aDefineCode.length) oRet.defines = 'var ' + aDefineCode.join(',') + ';';
		if (aReturnCode.length) oRet.returns = aReturnCode.join('&&');
		
		oRet.quotID = sId ? wrapQuot(sId) : '';
		oRet.quotTAG = sTagName ? wrapQuot(bXMLDocument ? sTagName : sTagName.toUpperCase()) : '';
		
		oRet.returnsID = sId ? 'oEl.id == ' + oRet.quotID + ' && ' : '';
		oRet.returnsTAG = sTagName && sTagName != '*' ? 'oEl.tagName == ' + oRet.quotTAG + ' && ' : '';
		
		return oRet;
		
	};
	
	// 쿼리를 연산자 기준으로 잘라냄
	var splitToParts = function(sQuery) {
		
		var aParts = [];
		var sRel = ' ';
		
		var sBody = sQuery.replace(/(.*?)\s*(!?[+>~ ]|!)\s*/g, function(_, sBody, sRelative) {
			
			if (sBody) aParts.push({ rel : sRel, body : sBody });
	
			sRel = sRelative.replace(/\s+$/g, '') || ' ';
			return '';
			
		});
	
		if (sBody) aParts.push({ rel : sRel, body : sBody });
		
		return aParts;
		
	};
	
	/* DO NOT SHRINK THIS */
	var isNth = function(oEl, sTagName, nMul, nAdd, sDirection) {
		
		var nIndex = 0;
		for (var oSib = oEl; oSib; oSib = oSib[sDirection])
			if (oSib.nodeType == 1 && (!sTagName || sTagName == oSib.tagName))
					nIndex++;

		return nIndex % nMul == nAdd;

	};
	
	// 잘라낸 part 를 함수로 컴파일 하기
	var compileParts = function(aParts) {
		
		var aPartExprs = [];
		
		// 잘라낸 부분들 조건 만들기
		for (var i = 0, oPart; oPart = aParts[i]; i++)
			aPartExprs.push(getExpression(oPart.body));
		
		//////////////////// BEGIN
		
		var sFunc = '';
		var sPushCode = 'aRet.push(oEl); if (oOptions.single) { bStop = true; }';

		for (var i = aParts.length - 1, oPart; oPart = aParts[i]; i--) {
			
			var oExpr = aPartExprs[i];
			var sPush = (debugOption.callback ? 'cost++;' : '') + oExpr.defines;
			
			// console.log(oExpr);

			var sReturn = 'if (bStop) {' + (i == 0 ? 'return aRet;' : 'return;') + '}';
			
			if (oExpr.returns == 'true') sPush += (sFunc ? sFunc + '(oEl);' : sPushCode) + sReturn;
			else sPush += 'if (' + oExpr.returns + ') {' + (sFunc ? sFunc + '(oEl);' : sPushCode ) + sReturn + '}';
			
			var sCheckTag = 'oEl.nodeType != 1';
			if (oExpr.quotTAG) sCheckTag = 'oEl.tagName != ' + oExpr.quotTAG;
			
			var sTmpFunc =
				'(function(oBase' +
					(i == 0 ? ', oOptions) { var bStop = false; var aRet = [];' : ') {');

			if (oExpr.nth) {
				sPush =
					'if (isNth(oEl, ' +
					(oExpr.quotTAG ? oExpr.quotTAG : 'false') + ',' +
					oExpr.nth[0] + ',' +
					oExpr.nth[1] + ',' +
					'"' + (oExpr.nth[2] == 'nth-of-type' ? 'previousSibling' : 'nextSibling') + '")) {' + sPush + '}';
			}
			
			switch (oPart.rel) {
			case ' ':
				if (oExpr.quotID) {
					
					sTmpFunc +=
						'var oEl = oDocument.getElementById(' + oExpr.quotID + ');' +
						'var oCandi = oEl;' +
						'for (; oCandi; oCandi = (oCandi.parentNode || oCandi._IE5_parentNode)) {' +
							'if (oCandi == oBase) break;' +
						'}' +
						'if (!oCandi || ' + sCheckTag + ') return aRet;' +
						sPush;
					
				} else {
					
					sTmpFunc +=
						'var aCandi = getChilds(oBase, ' + (oExpr.quotTAG || '"*"') + ');' +
							'for (var i = 0, oEl; oEl = aCandi[i]; i++) {' +
							sPush +
						'}';
					
				}
			
				break;
				
			case '>':
				if (oExpr.quotID) {
	
					sTmpFunc +=
						'var oEl = oDocument.getElementById(' + oExpr.quotID + ');' +
						'if ((oEl.parentNode || oEl._IE5_parentNode) != oBase || ' + sCheckTag + ') return aRet;' +
						sPush;
					
				} else {
	
					sTmpFunc +=
						'for (var oEl = oBase.firstChild; oEl; oEl = oEl.nextSibling) {' +
							'if (' + sCheckTag + ') { continue; }' +
							sPush +
						'}';
					
				}
				
				break;
				
			case '+':
				if (oExpr.quotID) {
	
					sTmpFunc +=
						'var oEl = oDocument.getElementById(' + oExpr.quotID + ');' +
						'var oPrev;' +
						'for (oPrev = oEl.previousSibling; oPrev; oPrev = oPrev.previousSibling) { if (oPrev.nodeType == 1) break; }' +
						'if (!oPrev || oPrev != oBase || ' + sCheckTag + ') return aRet;' +
						sPush;
					
				} else {
	
					sTmpFunc +=
						'for (var oEl = oBase.nextSibling; oEl; oEl = oEl.nextSibling) { if (oEl.nodeType == 1) break; }' +
						'if (!oEl || ' + sCheckTag + ') { return aRet; }' +
						sPush;
					
				}
				
				break;
			
			case '~':
	
				if (oExpr.quotID) {
	
					sTmpFunc +=
						'var oEl = oDocument.getElementById(' + oExpr.quotID + ');' +
						'var oCandi = oEl;' +
						'for (; oCandi; oCandi = oCandi.previousSibling) { if (oCandi == oBase) break; }' +
						'if (!oCandi || ' + sCheckTag + ') return aRet;' +
						sPush;
					
				} else {
	
					sTmpFunc +=
						'for (var oEl = oBase.nextSibling; oEl; oEl = oEl.nextSibling) {' +
							'if (' + sCheckTag + ') { continue; }' +
							'if (!markElement(oEl, ' + i + ')) { break; }' +
							sPush +
						'}';
	
				}
				
				break;
				
			case '!' :
			
				if (oExpr.quotID) {
					
					sTmpFunc +=
						'var oEl = oDocument.getElementById(' + oExpr.quotID + ');' +
						'for (; oBase; oBase = (oBase.parentNode || oBase._IE5_parentNode)) { if (oBase == oEl) break; }' +
						'if (!oBase || ' + sCheckTag + ') return aRet;' +
						sPush;
						
				} else {
					
					sTmpFunc +=
						'for (var oEl = (oBase.parentNode || oBase._IE5_parentNode); oEl; oEl = (oEl.parentNode || oEl._IE5_parentNode)) {'+
							'if (' + sCheckTag + ') { continue; }' +
							sPush +
						'}';
					
				}
				
				break;
	
			case '!>' :
			
				if (oExpr.quotID) {
	
					sTmpFunc +=
						'var oEl = oDocument.getElementById(' + oExpr.quotID + ');' +
						'var oRel = (oBase.parentNode || oBase._IE5_parentNode);' +
						'if (!oRel || oEl != oRel || (' + sCheckTag + ')) return aRet;' +
						sPush;
					
				} else {
	
					sTmpFunc +=
						'var oEl = (oBase.parentNode || oBase._IE5_parentNode);' +
						'if (!oEl || ' + sCheckTag + ') { return aRet; }' +
						sPush;
					
				}
				
				break;
				
			case '!+' :
				
				if (oExpr.quotID) {
	
					sTmpFunc +=
						'var oEl = oDocument.getElementById(' + oExpr.quotID + ');' +
						'var oRel;' +
						'for (oRel = oBase.previousSibling; oRel; oRel = oRel.previousSibling) { if (oRel.nodeType == 1) break; }' +
						'if (!oRel || oEl != oRel || (' + sCheckTag + ')) return aRet;' +
						sPush;
					
				} else {
	
					sTmpFunc +=
						'for (oEl = oBase.previousSibling; oEl; oEl = oEl.previousSibling) { if (oEl.nodeType == 1) break; }' +
						'if (!oEl || ' + sCheckTag + ') { return aRet; }' +
						sPush;
					
				}
				
				break;
	
			case '!~' :
				
				if (oExpr.quotID) {
					
					sTmpFunc +=
						'var oEl = oDocument.getElementById(' + oExpr.quotID + ');' +
						'var oRel;' +
						'for (oRel = oBase.previousSibling; oRel; oRel = oRel.previousSibling) { ' +
							'if (oRel.nodeType != 1) { continue; }' +
							'if (oRel == oEl) { break; }' +
						'}' +
						'if (!oRel || (' + sCheckTag + ')) return aRet;' +
						sPush;
					
				} else {
	
					sTmpFunc +=
						'for (oEl = oBase.previousSibling; oEl; oEl = oEl.previousSibling) {' +
							'if (' + sCheckTag + ') { continue; }' +
							'if (!markElement(oEl, ' + i + ')) { break; }' +
							sPush +
						'}';
					
				}
				
				break;
			}
	
			sTmpFunc +=
				(i == 0 ? 'return aRet;' : '') +
			'})';
			
			sFunc = sTmpFunc;
			
		}
		
		// alert(sFunc);
		eval('var fpCompiled = ' + sFunc + ';');
		//alert(fpCompiled);
		return fpCompiled;
		
	};
	
	// 쿼리를 match 함수로 변환
	var parseQuery = function(sQuery) {
		
		var sCacheKey = sQuery;
		
		var fpSelf = arguments.callee;
		var fpFunction = fpSelf._cache[sCacheKey];
		
		if (!fpFunction) {
			
			sQuery = backupKeys(sQuery);
			
			var aParts = splitToParts(sQuery);
			
			fpFunction = fpSelf._cache[sCacheKey] = compileParts(aParts);
			fpFunction.depth = aParts.length;
			
		}
		
		return fpFunction;
		
	};
	
	parseQuery._cache = {};
	
	// test 쿼리를 match 함수로 변환
	var parseTestQuery = function(sQuery) {
		
		var fpSelf = arguments.callee;
		
		var aSplitQuery = backupKeys(sQuery).split(/\s*,\s*/);
		var aResult = [];
		
		var nLen = aSplitQuery.length;
		var aFunc = [];
		
		for (var i = 0; i < nLen; i++) {

			aFunc.push((function(sQuery) {
				
				var sCacheKey = sQuery;
				var fpFunction = fpSelf._cache[sCacheKey];
				
				if (!fpFunction) {
					
					sQuery = backupKeys(sQuery);
					var oExpr = getExpression(sQuery);
					
					eval('fpFunction = function(oEl) { ' + oExpr.defines + 'return (' + oExpr.returnsID + oExpr.returnsTAG + oExpr.returns + '); };');
					
				}
				
				return fpFunction;
				
			})(restoreKeys(aSplitQuery[i])));
			
		}
		
		return aFunc;
		
	};
	
	parseTestQuery._cache = {};
	
	var distinct = function(aList) {
	
		var aDistinct = [];
		var oDummy = {};
		
		for (var i = 0, oEl; oEl = aList[i]; i++) {
			
			var nUID = getUID(oEl);
			if (oDummy[nUID]) continue;
			
			aDistinct.push(oEl);
			oDummy[nUID] = true;
		}
	
		return aDistinct;
	
	};
	
	/* DON'T SHRINK THIS */
	var markElement = function(oEl, nDepth) {
		
		var nUID = getUID(oEl);
		if (cssquery._marked[nDepth][nUID]) return false;
		
		cssquery._marked[nDepth][nUID] = true;
		return true;

	};
	
	var oResultCache = null;
	var bUseResultCache = false;
		
	var cssquery = function(sQuery, oParent, oOptions) {
		
		if (typeof sQuery == 'object') {
			
			var oResult = {};
			
			for (var k in sQuery)
				oResult[k] = arguments.callee(sQuery[k], oParent, oOptions);
			
			return oResult;
		}
		
		cost = 0;
		
		var executeTime = new Date().getTime();
		var aRet;
		
		for (var r = 0, rp = debugOption.repeat; r < rp; r++) {
			
			aRet = (function(sQuery, oParent, oOptions) {
				
				oOptions = oOptions || {};
				
				if (!oParent) oParent = document;
					
				// ownerDocument 잡아주기
				oDocument = oParent.ownerDocument || oParent.document || oParent;
				
				// 브라우저 버젼이 IE5.5 이하
				if (/\bMSIE\s([0-9]+(\.[0-9]+)*);/.test(navigator.userAgent) && parseFloat(RegExp.$1) < 6) {
					oDocument.firstChild = oDocument.getElementsByTagName('html')[0];
					oDocument.firstChild._IE5_parentNode = oDocument;
				}
				
				// XMLDocument 인지 체크
				bXMLDocument = (typeof XMLDocument != 'undefined') ? (oDocument.constructor === XMLDocument) : (!oDocument.location);
				getUID = bXMLDocument ? getUID4XML : getUID4HTML;
		
				clearKeys();
				
				// 쿼리를 쉼표로 나누기
				var aSplitQuery = backupKeys(sQuery).split(/\s*,\s*/);
				var aResult = [];
				
				var nLen = aSplitQuery.length;
				
				for (var i = 0; i < nLen; i++)
					aSplitQuery[i] = restoreKeys(aSplitQuery[i]);
				
				// 쉼표로 나눠진 쿼리 루프
				for (var i = 0; i < nLen; i++) {
					
					var sSingleQuery = aSplitQuery[i];
					var aSingleQueryResult = null;
					
					var sResultCacheKey = sSingleQuery + (oOptions.single ? '_single' : '');
		
					// 결과 캐쉬 뒤짐
					var aCache = bUseResultCache ? oResultCache[sResultCacheKey] : null;
					if (aCache) {
						
						// 캐싱되어 있는게 있으면 parent 가 같은건지 검사한후 aSingleQueryResult 에 대입
						for (var j = 0, oCache; oCache = aCache[j]; j++) {
							if (oCache.parent == oParent) {
								aSingleQueryResult = oCache.result;
								break;
							}
						}
						
					}
					
					if (!aSingleQueryResult) {
						
						var fpFunction = parseQuery(sSingleQuery);
						// alert(fpFunction);
						
						cssquery._marked = [];
						for (var j = 0, nDepth = fpFunction.depth; j < nDepth; j++)
							cssquery._marked.push({});
						
						aSingleQueryResult = distinct(fpFunction(oParent, oOptions));
						
						// 결과 캐쉬를 사용중이면 캐쉬에 저장
						if (bUseResultCache) {
							if (!(oResultCache[sResultCacheKey] instanceof Array)) oResultCache[sResultCacheKey] = [];
							oResultCache[sResultCacheKey].push({ parent : oParent, result : aSingleQueryResult });
						}
						
					}
					
					aResult = aResult.concat(aSingleQueryResult);
					
				}
		
				unsetNodeIndexes();
		
				return aResult;
				
			})(sQuery, oParent, oOptions);
			
		}
		
		executeTime = new Date().getTime() - executeTime;

		if (debugOption.callback) debugOption.callback(sQuery, cost, executeTime);
		
		return aRet;
		
	};

	cssquery.test = function(oEl, sQuery) {

		clearKeys();
		
		var aFunc = parseTestQuery(sQuery);
		
		for (var i = 0, nLen = aFunc.length; i < nLen; i++)
			if (aFunc[i](oEl)) return true;
			
		return false;
		
	};

	cssquery.useCache = function(bFlag) {
	
		if (typeof bFlag != 'undefined') {
			bUseResultCache = bFlag;
			cssquery.clearCache();
		}
		
		return bUseResultCache;
		
	};
	
	cssquery.clearCache = function() {
		oResultCache = {};
	};
	
	cssquery.getSingle = function(sQuery, oParent) {
		return cssquery(sQuery, oParent, { single : true })[0] || null;
	};
	
	cssquery.xpath = function(sXPath, oParent) {
		
		var sXPath = sXPath.replace(/\/(\w+)(\[([0-9]+)\])?/g, function(_, sTag, _, sTh) {
			sTh = sTh || '1';
			return '>' + sTag + ':nth-of-type(' + sTh + ')';
		});
		
		return cssquery.getSingle(sXPath, oParent);
		
	};
	
	cssquery.debug = function(fpCallback, nRepeat) {
		
		debugOption.callback = fpCallback;
		debugOption.repeat = nRepeat || 1;
		
	};
	
	cssquery.version = sVersion;
	
	return cssquery;
	
})();

if (typeof window.$$ == "undefined") window.$$ = cssquery;