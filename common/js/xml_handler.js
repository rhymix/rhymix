/**
 * @file   : common/js/xml_handler.js
 * @author : zero <zero@nzeo.com>
 * @desc   : ajax 사용을 위한 기본 js
 **/

// xml handler을 이용하는 user function
function exec_xml(module, act, params, callback_func, response_tags, callback_func_arg) {
  var oXml = new xml_handler();
  oXml.reset();
  for(var key in params) {
    var val = params[key];
    oXml.addParam(key, val);
  }
  oXml.addParam('module', module);
  oXml.addParam('act', act);

  var waiting_obj = document.getElementById('waitingforserverresponse');
  waiting_obj.style.visibility = 'visible';
  oXml.request(xml_response_filter, oXml, callback_func, response_tags, callback_func_arg);
}

// 결과 처리 후 callback_func에 넘겨줌
function xml_response_filter(oXml, callback_func, response_tags, callback_func_arg) {
  var xmlDoc = oXml.getResponseXml();
  if(!xmlDoc) return;

  var waiting_obj = document.getElementById('waitingforserverresponse');
  waiting_obj.style.visibility = 'hidden';
  var ret_obj = oXml.toZMsgObject(xmlDoc, response_tags);
  if(ret_obj['error']!=0) {
    alert(ret_obj['message']);
    return;
  }

  callback_func(ret_obj, response_tags, callback_func_arg);
}

// xml handler
function xml_handler() {
    this.obj_xmlHttp = null;
    this.method_name = null;
    if(location.href.indexOf('admin.php')>0) this.xml_path = "./admin.php";
    else this.xml_path = "./index.php";

    this.params = new Array();

    this.reset = xml_handlerReset;
    this.getXmlHttp = zGetXmlHttp;
    this.request = xml_handlerRequest;
    this.setPath = xml_handlerSetPath;
    this.addParam = xml_handlerAddParam;
    this.getResponseXml = xml_handlerGetResponseXML;
    this.toZMsgObject = xml_handlerToZMsgObject;

    this.obj_xmlHttp = this.getXmlHttp();
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

function xml_handlerRequest(callBackFunc, xmlObj, callBackFunc2, response_tags, callback_func_arg) {

    var rd = "";
    rd += "<?xml version=\"1.0\" encoding=\"utf-8\" ?>\n"
       +  "<methodCall>\n"
       +  "<params>\n"

    for (var key in this.params) {
        var val = this.params[key];
        rd += "<"+key+"><![CDATA["+val+"]]></"+key+">\n";
    }
    
    rd += "</params>\n"
       +  "</methodCall>\n";
    if(this.obj_xmlHttp.readyState!=0) {
        this.obj_xmlHttp.abort();
        this.obj_xmlHttp = this.getXmlHttp();
    }
    this.obj_xmlHttp.onreadystatechange = function () {callBackFunc(xmlObj, callBackFunc2, response_tags, callback_func_arg)};
    this.obj_xmlHttp.open('POST', this.xml_path, true);
    this.obj_xmlHttp.send(rd);
}

function xml_handlerSetPath(path) {
    this.xml_path = "./"+path;
}


function xml_handlerReset() {
    this.obj_xmlHttp = this.getXmlHttp();
    this.params = new Array();
}

function xml_handlerAddParam(key, val) {
    this.params[key] = val;
}

function xml_handlerGetResponseXML() {
    if(this.obj_xmlHttp && this.obj_xmlHttp.readyState == 4 && isDef(this.obj_xmlHttp.responseXML)) {
        var xmlDoc = this.obj_xmlHttp.responseXML;
        this.reset();
        return xmlDoc;
    }
    return null;
}

function xml_handlerToZMsgObject(xmlDoc, tags) {
    if(!xmlDoc) return null;
    if(!tags) {
        tags = new Array('error','message');
    }
    var obj_ret = new Array();
    for(var i=0; i<tags.length; i++) {
        try {
            obj_ret[tags[i]] = xmlDoc.getElementsByTagName(tags[i])[0].firstChild.nodeValue;
        } catch(e) {
            obj_ret[tags[i]] = '';
        }
    }
    return obj_ret;
}
