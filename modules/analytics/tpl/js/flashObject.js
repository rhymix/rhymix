//////////////////////////////////////////////////////////////////////////////// 
// 
// NHN CORPORATION
// Copyright 2002-2007 NHN Coporation 
// All Rights Reserved. 
// 
// 이 문서는 NHN㈜의 지적 자산이므로 NHN(주)의 승인 없이 이 문서를 다른 용도로 임의 
// 변경하여 사용할 수 없습니다. 
// 
// 파일명 : flashObject.js 
// 
// 작성일 : 2009.02.02 
// 
// 최종 수정일: 2009.04.08
// 
// Version : 1.1.0
// 
////////////////////////////////////////////////////////////////////////////////

/**
 * @author seungkil choi / kgoon@nhncorp.com
 */

 if (typeof nhn == 'undefined') nhn = {};
 
 nhn.FlashObject = (function(){
 	
	var FlashObject = {};
 	
	//-------------------------------------------------------------
	// private properties
	//-------------------------------------------------------------
	var sClassPrefix = 'F' + new Date().getTime() + parseInt(Math.random() * 1000000);
	var bIE = /MSIE/i.test(navigator.userAgent);
	var bFF = /FireFox/i.test(navigator.userAgent);
	var bChrome = /Chrome/i.test(navigator.userAgent);
		
	
	
    /**
     *  이벤트 등록 함수
     *
     *  @param oElement 이벤트 등록 객체.
     *  @param sEvent	등록할 이벤트 Type
     *  @param fHandler	이벤트 핸들러
     *  @return void
     */
	var bind = function(oElement, sEvent, fHandler) 
	{
		
		if (typeof oElement.attachEvent != 'undefined')
			oElement.attachEvent('on' + sEvent, fHandler);
		else
			oElement.addEventListener(sEvent, fHandler, true);
		
	};
	
	
	var objectToString = function(oObj, sSeparator)
	{
		
		var s = "";
		var first = true;
		var name = "";
		var value;

		for (var p in oObj)
		{
			if (first)
				first = false;
			else
				s += sSeparator;

			value = oObj[p];
			
			switch (typeof(value)) {
				case "string":
					s += p + '=' + encodeURIComponent(value);
					break;

				case "number":
					s += p + '=' + encodeURIComponent(value.toString());
					break;

				case "boolean":
					s += p + '=' + (value ? "true" : "false");
					break;

				default:
					// array 이거나 object 일때 변환하지 않는다.
			}
		}

		return s;
	}

    /**
     *  플래시 ExternalInterface 버그 패치
     *  for 'Out of memory line at 56' error
     *
     *  @return void
     */
	var unloadHandler = function() {
		
		obj = document.getElementsByTagName('OBJECT');

		for (var i = 0, theObj; theObj = obj[i]; i++) {

			theObj.style.display = 'none';

			for (var prop in theObj)
				if (typeof(theObj[prop]) == 'function')
					try { theObj[prop] = null; } catch(e) {}

		}
		
	};
	
    /**
     *  휠마우스 이벤트 처리 함수
     *  이벤트가 발생한 객체가 플래시인 경우 
     *  delta 값과 마우스 좌표를 플래시에 전달 
     *
     *  @param e		이벤트 객체
     *  @return void
     */
	var wheelHandler = function(e) {
		
		e = e || window.event;
		
		var nDelta = e.wheelDelta / (bChrome ? 360 : 120);
		if (!nDelta) nDelta = -e.detail / 3;
		
		var oEl = e.target || e.srcElement;
		
		// 휠 이벤트가 발생한 오브젝트가 FlashObject가 생산한 플래시가 아니면 중지
		if (!(new RegExp('(^|\b)' + sClassPrefix + '_([a-z0-9_$]+)(\b|$)', 'i').test(oEl.className))) return;
		
		var sMethod = RegExp.$2;

		var nX = 'layerX' in e ? e.layerX : e.offsetX;
		var nY = 'layerY' in e ? e.layerY : e.offsetY;
		
		try {
			
			if (!oEl[sMethod](nDelta, nX, nY)) {

				if (e.preventDefault) e.preventDefault();
				else e.returnValue = false;

			}
			
		} catch(err) {
			// 등록한 핸들러가 없는 경우 
		}
		
	};	

	/**
	 * 넘겨받은 오브젝트의 절대 좌표를 구해주는 함수
	 * 
	 * @param {Object} oEl	오브젝트 참조
	 */
	var getAbsoluteXY = function(oEl) {
		
		var oPhantom = null;
	
		// getter
		var bSafari = /Safari/.test(navigator.userAgent);
		var bIE = /MSIE/.test(navigator.userAgent);
	
		var fpSafari = function(oEl) {
	
			var oPos = { left : 0, top : 0 };
	
			// obj.offsetParent is null in safari, because obj.parentNode is '<object>'.
			if (oEl.parentNode.tagName.toLowerCase() == "object") {
				oEl = oEl.parentNode;
			}
	
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
	
			return oPos;
	
		};
	
		return (bSafari ? fpSafari : fpOthers)(oEl);
	}
	
	/**
	 * 현재 스크롤 위치를 알려주는 함수
	 * 
	 */
	var getScroll = function() {
		var bIE = /MSIE/.test(navigator.userAgent);
		
		if (bIE) {
			var sX = document.documentElement.scrollLeft || document.body.scrollLeft;
			var sY = document.documentElement.scrollTop || document.body.scrollTop;
			return {scrollX:sX, scrollY:sY}
		}
		else {
			return {scrollX:window.pageXOffset, scrollY:window.pageYOffset};
		}
	}
	
	/**
	 * 현재 스크린 사이즈를 알려주는 함수
	 * 
	 */
	var getInnerWidthHeight = function() {
		var bIE = /MSIE/.test(navigator.userAgent);
		var obj = {};
		
		if (bIE) {
			obj.nInnerWidth = document.documentElement.clientWidth || document.body.clientWidth;
			obj.nInnerHeight = document.documentElement.clientHeight || document.body.clientHeight;
		}
		else {
			obj.nInnerWidth = window.innerWidth;
			obj.nInnerHeight = window.innerHeight;
		}
		return obj;
	}


	//-------------------------------------------------------------
	// public static function
	//-------------------------------------------------------------

    /**
     *  플래시 오브젝트를 HTML에 임베드하는 함수 
     *
     *  @param div			삽입할 DIV ID
     *  @param sTag			플래시 임베드 테그
     *  		
     *  @return void
     */
	FlashObject.showAt = function(sDiv, sTag){
		document.getElementById(sDiv).innerHTML = sTag;
	}


    /**
     *  플래시 오브젝트를 HTML에 임베드하는 함수 
     *  generateTag 함수와 파라미터 동일
     *
     *  @param sURL			플래시 무비 주소
     *  @param nWidth		플래시 무비 가로크기 (default : 100%)
     *  @param nHeight		플래시 무비 세로크기 (default : 100%)
     *  @param oParam		플래시에 설정할 옵션 파라미터 (default : null)
     *  @param sAlign		플래시 정렬 기준
     *  @param sFPVersion	플레이어 다운로드 목표 버전
     *  		
     *  @return void
     */
	FlashObject.show = function(sURL, sID, nWidth, nHeight, oParam, sAlign, sFPVersion){
		document.write( FlashObject.generateTag(sURL, sID, nWidth, nHeight, oParam, sAlign, sFPVersion) );
	}


    /**
     *  플래시 오브젝트를 HTML에 임베드할 때 사용할 태그 생성 함수 
     *
     *  @param sURL			플래시 무비 주소
     *  @param nWidth		플래시 무비 가로크기 (default : 100%)
     *  @param nHeight		플래시 무비 세로크기 (default : 100%)
     *  @param oParam		플래시에 설정할 옵션 파라미터 (default : null)
     *  @param sAlign		플래시 정렬 기준
     *  @param sFPVersion	플레이어 다운로드 목표 버전
     *  		
     *  @return String
     */
	FlashObject.generateTag = function(sURL, sID, nWidth, nHeight, oParam, sAlign, sFPVersion) {
		
		nWidth = nWidth || "100%";
		nHeight = nHeight || "100%";
		sFPVersion = sFPVersion || "9,0,0,0";
		sAlign = sAlign || "middle";
		
		var oOptions = FlashObject.getDefaultOption();
		
		if (oParam)
		{
			if(oParam.flashVars && typeof(oParam.flashVars) == "object")
				oParam.flashVars = objectToString(oParam.flashVars, "&");
			
			for (var key in oParam)
				oOptions[key] = oParam[key];
		}

		var sClsID = 'clsid:d27cdb6e-ae6d-11cf-96b8-444553540000';
		var sCodeBase = 'http://fpdownload.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=' + sFPVersion;

		var sStyle = 'position:relative !important;';
		var sClassName = sClassPrefix + '_' + oOptions.wheelHandler;

		var objCode = [];
		var embedCode = [];
		
		
		objCode.push('<object classid="' + sClsID + '" codebase="' + sCodeBase + '" class="' + sClassName + '" style="' + sStyle + '" ' + '" width="' + nWidth + '" height="' + nHeight + '" id="' + sID + '" align="' + sAlign + '">');
		objCode.push('<param name="movie" value="' + sURL + '" />');

		embedCode.push('<embed width="' + nWidth + '" height="' + nHeight + '" name="' + sID + '" class="' + sClassName + '" style="' + sStyle + '" ' + '" src="' + sURL + '" align="' + sAlign + '" ');
		
		
		for(var vars in oOptions){
			objCode.push('<param name="'+vars+'" value="' + oOptions[vars] + '" />');
			embedCode.push(vars +'="' + oOptions[vars] + '" ');
		}

		embedCode.push('type="application/x-shockwave-flash" pluginspage="http://www.macromedia.com/go/getflashplayer" />'); 

		objCode.push(embedCode.join(""));
		objCode.push('</object>');
		

		if (bind) {
			bind(window, 'unload', unloadHandler);
			bind(document, !bFF ? 'mousewheel' : 'DOMMouseScroll', wheelHandler);
			bind = null;
		}

		return objCode.join("");

	};


    /**
     *  플래시 옵션 기본 설정값 
     *
     *  @return object
     */
	FlashObject.getDefaultOption = function() {
		return {
					 quality:"high",
					 bgColor:"#FFFFFF", 
					 allowScriptAccess:"always",
					 wmode:"window",
					 menu:"false",
					 allowFullScreen:"true"
				};
	};
	

    /**
     *  플래시 오브젝트를 찾아 반환해주는 함수 
     *
     *  @param objID		찾아야하는 플래시 오브젝트 ID
     *  @param doc			플래시를 갖고 있는 document 객체 / default : null
     *  @return object
     */
	FlashObject.find = function(sID, oDoc) {
		oDoc = oDoc || document;
		return oDoc[sID] || oDoc.all[sID];
	};

    /**
     *  매개변수로 넘겨 받은 아이디의 플래시 오브젝트를 찾아 가로 크기를 변경하는 함수 
     *
     *  @param objID		찾아야하는 플래시 오브젝트 ID
     *  @param value		가로크기로 설정할 값
     *  @return void
     */
	FlashObject.setWidth = function(sID, value) {
		FlashObject.find(sID).width = value;
	};
	
    /**
     *  매개변수로 넘겨 받은 아이디의 플래시 오브젝트를 찾아 세로 크기를 변경하는 함수 
     *
     *  @param objID		찾아야하는 플래시 오브젝트 ID
     *  @param value		세로크기로 설정할 값
     *  @return void
     */
	FlashObject.setHeight = function(sID, value) {
		FlashObject.find(sID).height = value;
	};
	
    /**
     *  매개변수로 넘겨 받은 아이디의 플래시 오브젝트를 찾아 사이즈를 변경하는 함수 
     *
     *  @param objID		찾아야하는 플래시 오브젝트 ID
     *  @param nWidth		가로크기로 설정할 값
     *  @param nHeight		세로크기로 설정할 값
     *  @return void
     */
	FlashObject.setSize = function(sID, nWidth, nHeight) {
		FlashObject.find(sID).height = nHeight;
		FlashObject.find(sID).width = nWidth;
	};
	
	/**
	 *	오브젝트 아이디를 넘겨 받으면 해당 오브젝트의 절대 좌표 및 스크롤을 감안한 상대죄표를
	 *	반환하는 함수
	 * 
	 * 	@param sID			플래시 오브젝트 ID
	 */
	FlashObject.getPositionObj = function(sID){
		var targetObj = FlashObject.find(sID);
		if(targetObj == null)
			return null;
			
		var absPosi = getAbsoluteXY(targetObj);
		var scrollPosi = getScroll();
		
		var obj = {}
		obj.absoluteX = absPosi.left;
		obj.absoluteY = absPosi.top;
		obj.scrolledX = obj.absoluteX - scrollPosi.scrollX;
		obj.scrolledY = obj.absoluteY - scrollPosi.scrollY;
		obj.browserWidth = getInnerWidthHeight().nInnerWidth;
		
		return obj;		
	}
	
	return FlashObject;
 })()
