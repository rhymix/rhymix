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
    for(var key in params) {
	if(!params.hasOwnProperty(key)) continue;
        var val = params[key];
        oXml.addParam(key, val);
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
    this.obj_xmlHttp = null;
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

    if(this.obj_xmlHttp.readyState!=0) {
        this.obj_xmlHttp.abort();
        this.obj_xmlHttp = this.getXmlHttp();
    }
    this.obj_xmlHttp.onreadystatechange = function () {callBackFunc(xmlObj, callBackFunc2, response_tags, callback_func_arg, fo_obj)};

    // 모든 xml데이터는 POST방식으로 전송. try-cacht문으로 오류 발생시 대처
    try {

        this.obj_xmlHttp.open("POST", this.xml_path, true);

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

function xml_parseXmlDoc(dom) {
    if(!dom) return;

    var ret_obj = new Array();

    var obj = dom.firstChild;
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
