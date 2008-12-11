/**
 * @file   common/js/xml_handler.js
 * @author zero <zero@nzeo.com>
 * @brief  zbxe내에서 ajax기능을 이용함에 있어 module, act를 잘 사용하기 위한 자바스크립트
 **/

// xml handler을 이용하는 user function
var show_waiting_message = true;
function exec_xml(module, act, params, callback_func, response_tags, callback_func_arg, fo_obj) {
    var oXml = new xml_handler();
    oXml.reset();
    if(typeof(params)!='undefined') {
        for(var key in params) {
            if(!params.hasOwnProperty(key)) continue;
            var val = params[key];
            oXml.addParam(key, val);
        }
    }
    oXml.addParam("module", module);
    oXml.addParam("act", act);

    if(typeof(response_tags)=="undefined" || response_tags.length<1) response_tags = new Array('error','message');

    oXml.request(xml_response_filter, oXml, callback_func, response_tags, callback_func_arg, fo_obj);
}

// 결과 처리 후 callback_func에 넘겨줌
function xml_response_filter(oXml, callback_func, response_tags, callback_func_arg, fo_obj) {
    var xmlDoc = oXml.getResponseXml();
    if(!xmlDoc) return null;

    var waiting_obj = xGetElementById("waitingforserverresponse");
    if(waiting_obj) waiting_obj.style.visibility = "hidden";

    var ret_obj = oXml.toZMsgObject(xmlDoc, response_tags);
    if(ret_obj["error"]!=0) {
        alert(ret_obj["message"]);
        return null;
    }

    if(ret_obj["redirect_url"]) {
        location.href=ret_obj["redirect_url"];
        return null;
    }

    if(!callback_func) return null;

    callback_func(ret_obj, response_tags, callback_func_arg, fo_obj);

    return null;
}

// xml handler
function xml_handler() {
    this.objXmlHttp = null;
    this.method_name = null;
    this.xml_path = request_uri+"index.php";

    this.params = new Array();

    this.reset = xml_handlerReset;
    this.getXmlHttp = zGetXmlHttp;
    this.request = xml_handlerRequest;
    this.setPath = xml_handlerSetPath;
    this.addParam = xml_handlerAddParam;
    this.getResponseXml = xml_handlerGetResponseXML;
    this.toZMsgObject = xml_handlerToZMsgObject;
    this.parseXMLDoc = xml_parseXmlDoc;

    this.objXmlHttp = this.getXmlHttp();
}

function zGetXmlHttp() {
    if (window.XMLHttpRequest) return new XMLHttpRequest();
    else if (window.ActiveXObject) {
        try {
            return new ActiveXObject("Msxml2.XMLHTTP");
        } catch (e) {
            return new ActiveXObject("Microsoft.XMLHTTP");
        }
    }
    return null;
}

function xml_handlerRequest(callBackFunc, xmlObj, callBackFunc2, response_tags, callback_func_arg, fo_obj) {
    var rd = "";
    rd += "<?xml version=\"1.0\" encoding=\"utf-8\" ?>\n"
    +  "<methodCall>\n"
    +  "<params>\n"

    for (var key in this.params) {
        if(!this.params.hasOwnProperty(key)) continue;
        var val = this.params[key];
        rd += "<"+key+"><![CDATA["+val+"]]></"+key+">\n";
    }

    rd += "</params>\n"
    +  "</methodCall>\n";

    // ssl action
    if(typeof(ssl_actions)!='undefined' && typeof(ssl_actions.length)!='undefined' && typeof(this.params['act'])!='undefined' && /^https:\/\//i.test(location.href) ) {
        var action = this.params['act'];
        for(i=0;i<ssl_actions.length;i++) {
            if(ssl_actions[i]==action) {
                this.xml_path = this.xml_path.replace(/^http:\/\//i,'https://');
                break;
            }
        }
    }

    if(this.objXmlHttp.readyState!=0) {
        this.objXmlHttp.abort();
        this.objXmlHttp = this.getXmlHttp();
    }
    this.objXmlHttp.onreadystatechange = function () {callBackFunc(xmlObj, callBackFunc2, response_tags, callback_func_arg, fo_obj)};

    // 모든 xml데이터는 POST방식으로 전송. try-cacht문으로 오류 발생시 대처
    try {

        this.objXmlHttp.open("POST", this.xml_path, true);

    } catch(e) {
        alert(e);
        return;
    }

    // ajax 통신중 대기 메세지 출력 (show_waiting_message값을 false로 세팅시 보이지 않음)
    var waiting_obj = xGetElementById("waitingforserverresponse");
    if(show_waiting_message && waiting_obj) {
        xInnerHtml(waiting_obj, waiting_message);

        xTop(waiting_obj, xScrollTop()+20);
        xLeft(waiting_obj, xScrollLeft()+20);
        waiting_obj.style.visibility = "visible";
    }

    this.objXmlHttp.send(rd);
}

function xml_handlerSetPath(path) {
    this.xml_path = "./"+path;
}


function xml_handlerReset() {
    this.objXmlHttp = this.getXmlHttp();
    this.params = new Array();
}

function xml_handlerAddParam(key, val) {
    this.params[key] = val;
}

function xml_handlerGetResponseXML() {
    if(this.objXmlHttp && this.objXmlHttp.readyState == 4 && isDef(this.objXmlHttp.responseXML)) {
        var xmlDoc = this.objXmlHttp.responseXML;
        this.reset();
        return xmlDoc;
    }
    return null;
}


function xml_parseXmlDoc(dom) {

    if(!dom) return;

    var jsonStr = xml2json(dom,false,false);
    var jsonObj = eval("("+ jsonStr +");");
    return jsonObj.response;
/*

    var ret_obj = new Array();

    var obj = dom.firstChild;
    var preObj;
    if(!obj) return;

    while(obj) {
        if(obj.nodeType == 1) {

            var name = obj.nodeName;
            var value = null;

            if(obj.childNodes.length==1 && obj.firstChild.nodeType != 1) {

                value = obj.firstChild.nodeValue;
            } else {
                value = this.parseXMLDoc(obj);
            }
            if(typeof(ret_obj[name])=='undefined') {
                ret_obj[name] = value;
            } else {
                if(ret_obj[name].length>0) {
                    ret_obj[name][ret_obj[name].length] = value;
                } else {
                    var tmp_value = ret_obj[name];
                    ret_obj[name] = new Array();
                    ret_obj[name][ret_obj[name].length] = tmp_value;
                    ret_obj[name][ret_obj[name].length] = value;
                }
            }

        }
        obj = obj.nextSibling;

    }
    return ret_obj;
*/
}

function xml_handlerToZMsgObject(xmlDoc, tags) {
    if(!xmlDoc) return null;
    if(!tags) tags = new Array("error","message");
    tags[tags.length] = "redirect_url";
    tags[tags.length] = "act";

    var parsed_array = this.parseXMLDoc(xmlDoc.getElementsByTagName('response')[0]);

    var obj_ret = new Array();
    for(var i=0; i<tags.length; i++) {
        var key = tags[i];
        if(parsed_array[key]) obj_ret[key] = parsed_array[key];
        else obj_ret[key] = null;
    }
    return obj_ret;
}



/*  This work is licensed under Creative Commons GNU LGPL License.

    License: http://creativecommons.org/licenses/LGPL/2.1/
   Version: 0.9
    Author:  Stefan Goessner/2006
    Web:     http://goessner.net/
*/
function xml2json(xml, tab, ignoreAttrib) {
   var X = {
      toObj: function(xml) {
         var o = {};
         if (xml.nodeType==1) {   // element node ..
            if (ignoreAttrib && xml.attributes.length)   // element with attributes  ..
               for (var i=0; i<xml.attributes.length; i++)
                  o["@"+xml.attributes[i].nodeName] = (xml.attributes[i].nodeValue||"").toString();
            if (xml.firstChild) { // element has child nodes ..
               var textChild=0, cdataChild=0, hasElementChild=false;
               for (var n=xml.firstChild; n; n=n.nextSibling) {
                  if (n.nodeType==1) hasElementChild = true;
                  else if (n.nodeType==3 && n.nodeValue.match(/[^ \f\n\r\t\v]/)) textChild++; // non-whitespace text
                  else if (n.nodeType==4) cdataChild++; // cdata section node
               }
               if (hasElementChild) {
                  if (textChild < 2 && cdataChild < 2) { // structured element with evtl. a single text or/and cdata node ..
                     X.removeWhite(xml);
                     for (var n=xml.firstChild; n; n=n.nextSibling) {
                        if (n.nodeType == 3)  // text node
                           o = X.escape(n.nodeValue);
                        else if (n.nodeType == 4)  // cdata node
//                           o["#cdata"] = X.escape(n.nodeValue);
                            o = X.escape(n.nodeValue);
                        else if (o[n.nodeName]) {  // multiple occurence of element ..
                           if (o[n.nodeName] instanceof Array)
                              o[n.nodeName][o[n.nodeName].length] = X.toObj(n);
                           else
                              o[n.nodeName] = [o[n.nodeName], X.toObj(n)];
                        }
                        else  // first occurence of element..
                           o[n.nodeName] = X.toObj(n);
                     }
                  }
                  else { // mixed content
                     if (!xml.attributes.length)
                        o = X.escape(X.innerXml(xml));
                     else
                        o["#text"] = X.escape(X.innerXml(xml));
                  }
               }
               else if (textChild) { // pure text
                  if (!xml.attributes.length)
                     o = X.escape(X.innerXml(xml));
                  else
                     o["#text"] = X.escape(X.innerXml(xml));
               }
               else if (cdataChild) { // cdata
                  if (cdataChild > 1)
                     o = X.escape(X.innerXml(xml));
                  else
                     for (var n=xml.firstChild; n; n=n.nextSibling){
                        //o["#cdata"] = X.escape(n.nodeValue);
                        o = X.escape(n.nodeValue);
                  }
               }
            }
            if (!xml.attributes.length && !xml.firstChild) o = null;
         }
         else if (xml.nodeType==9) { // document.node
            o = X.toObj(xml.documentElement);
         }
         else
            alert("unhandled node type: " + xml.nodeType);
         return o;
      },
      toJson: function(o, name, ind) {
         var json = name ? ("\""+name+"\"") : "";
         if (o instanceof Array) {
            for (var i=0,n=o.length; i<n; i++)
               o[i] = X.toJson(o[i], "", ind+"\t");
            json += (name?":[":"[") + (o.length > 1 ? ("\n"+ind+"\t"+o.join(",\n"+ind+"\t")+"\n"+ind) : o.join("")) + "]";
         }
         else if (o == null)
            json += (name&&":") + "null";
         else if (typeof(o) == "object") {
            var arr = [];
            for (var m in o)
               arr[arr.length] = X.toJson(o[m], m, ind+"\t");
            json += (name?":{":"{") + (arr.length > 1 ? ("\n"+ind+"\t"+arr.join(",\n"+ind+"\t")+"\n"+ind) : arr.join("")) + "}";
         }
         else if (typeof(o) == "string")
            json += (name&&":") + "\"" + o.toString() + "\"";
         else
            json += (name&&":") + o.toString();
         return json;
      },
      innerXml: function(node) {
         var s = ""
         if ("innerHTML" in node)
            s = node.innerHTML;
         else {
            var asXml = function(n) {
               var s = "";
               if (n.nodeType == 1) {
                  s += "<" + n.nodeName;
                  for (var i=0; i<n.attributes.length;i++)
                     s += " " + n.attributes[i].nodeName + "=\"" + (n.attributes[i].nodeValue||"").toString() + "\"";
                  if (n.firstChild) {
                     s += ">";
                     for (var c=n.firstChild; c; c=c.nextSibling)
                        s += asXml(c);
                     s += "</"+n.nodeName+">";
                  }
                  else
                     s += "/>";
               }
               else if (n.nodeType == 3)
                  s += n.nodeValue;
               else if (n.nodeType == 4)
                  s += "<![CDATA[" + n.nodeValue + "]]>";
               return s;
            };
            for (var c=node.firstChild; c; c=c.nextSibling)
               s += asXml(c);
         }
         return s;
      },
      escape: function(txt) {
         return txt.replace(/[\\]/g, "\\\\")
                   .replace(/[\"]/g, '\\"')
                   .replace(/[\n]/g, '\\n')
                   .replace(/[\r]/g, '\\r');
      },
      removeWhite: function(e) {
         e.normalize();
         for (var n = e.firstChild; n; ) {
            if (n.nodeType == 3) {  // text node
               if (!n.nodeValue.match(/[^ \f\n\r\t\v]/)) { // pure whitespace text node
                  var nxt = n.nextSibling;
                  e.removeChild(n);
                  n = nxt;
               }
               else
                  n = n.nextSibling;
            }
            else if (n.nodeType == 1) {  // element node
               X.removeWhite(n);
               n = n.nextSibling;
            }
            else                      // any other node
               n = n.nextSibling;
         }
         return e;
      }
   };
   if (xml.nodeType == 9) // document node
      xml = xml.documentElement;
   var json = X.toJson(X.toObj(X.removeWhite(xml)), xml.nodeName, "");
   return "{" + (tab ? json.replace(/\t/g, tab) : json.replace(/\t|\n/g, "")) + "}";
}


/**
 * @brief exec_json (exec_xml와 같은 용도)
 **/
(function($){
$.exec_json = function(action,data,func){
    if(typeof(data) == 'undefined') data = {};
    action = action.split(".");
    if(action.length == 2){

        if(show_waiting_message) {
            $("#waitingforserverresponse").html(waiting_message).css('top',$(document).scrollTop()+20).css('left',$(document).scrollLeft()+20).css('visibility','visible');
        }

        $.extend(data,{module:action[0],act:action[1]});
        $.ajax({
            type:"POST"
            ,dataType:"json"
            ,url:request_uri
            ,contentType:"application/json"
            ,data:$.param(data)
            ,success : function(data){
                $("#waitingforserverresponse").css('visibility','hidden');
                if(data.error > 0) alert(data.message);
                if($.isFunction(func)) func(data);
            }
        });
    }
};

$.fn.exec_html = function(action,data,type){
    if(typeof(data) == 'undefined') data = {};
    if(!$.inArray(type, ['html','append','prepend'])) type = 'html';

    var self = $(this);
    action = action.split(".");
    if(action.length == 2){
        if(show_waiting_message) {
            $("#waitingforserverresponse").html(waiting_message).css('top',$(document).scrollTop()+20).css('left',$(document).scrollLeft()+20).css('visibility','visible');
        }

        $.extend(data,{module:action[0],act:action[1]});
        $.ajax({
            type:"POST"
            ,dataType:"html"
            ,url:request_uri
            ,data:$.param(data)
            ,success : function(html){
                $("#waitingforserverresponse").css('visibility','hidden');
                self[type](html);
            }
        });
    }
};
})(jQuery);
