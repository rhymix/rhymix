/**
 * @file   common/js/xml_js_filter.js
 * @author zero (zero@nzeo.com)
 * @brief  xml filter에서 사용될 js
 **/

var alertMsg = new Array();

/**
 * @function filterAlertMessage
 * @brief ajax로 서버에 요청후 결과를 처리할 callback_function을 지정하지 않았을 시 호출되는 기본 함수
 **/
function filterAlertMessage(ret_obj) {
    var error = ret_obj["error"];
    var message = ret_obj["message"];
    var redirect_url = ret_obj["redirect_url"];
    var url = location.href;
    if(typeof(message)!='undefined'&&message&&message!='success') alert(message);
    if(typeof(redirect_url)!='undefined'&&redirect_url) url = redirect_url;
    location.href = url;
}

/**
 * @class XmlJsFilter
 * @authro zero (zero@nzeo.com)
 * @brief form elements, module/act, callback_user_func을 이용하여 서버에 ajax로 form 데이터를 넘기고 결과를 받아오는 js class
 **/
function XmlJsFilter(form_object, module, act, callback_user_func) {

    this.field = new Array();
    this.parameter = new Array();
    this.response = new Array();

    this.fo_obj = form_object;
    this.module = module;
    this.act = act;
    this.user_func = callback_user_func;

    this.addFieldItem = XmlJsFilterAddFieldItem;
    this.addParameterItem = XmlJsFilterAddParameterItem;
    this.addResponseItem = XmlJsFilterAddResponseItem;
    this.getValue = XmlJsFilterGetValue;
    this.executeFilter = XmlJsFilterExecuteFilter;
    this.checkFieldItem = XmlJsFilterCheckFieldItem;
    this.getParameterParam = XmlJsFilterGetParameterParam;
    this.alertMsg = XmlJsFilterAlertMsg;
    this.proc = XmlJsFilterProc;
}

function XmlJsFilterAddFieldItem(target, required, minlength, maxlength, equalto, filter) {
    var obj = new Array(target, required, minlength, maxlength, equalto, filter);
    this.field[this.field.length] = obj;
}

function XmlJsFilterAddParameterItem(param, target) {
    var obj = new Array(param, target);
    this.parameter[this.parameter.length] = obj;
}

function XmlJsFilterAddResponseItem(name) {
    this.response[this.response.length] = name;
}

function XmlJsFilterGetValue(target_name) {
    var obj = this.fo_obj[target_name];
    if(typeof(obj)=='undefined') return '';
    var value = '';
    var length = obj.length;
    var type = obj.type;

    if(length) type = obj[0].type;

    switch(type) {
      case 'checkbox' :
            if(typeof(length)!='undefined') {
              value_list = new Array();
              for(var i=0;i<obj.length;i++) {
                if(obj[i].checked) value_list[value_list.length] = obj[i].value;
              }
              value = value_list.join(",");
            } else {
              if(obj.checked) value = obj.value;
              else value = '';
            }
          break;
      case 'radio' :
            if(typeof(length)!='undefined') {
              for(var i=0;i<obj.length;i++) {
                if(obj[i].checked) value = obj[i].value;
              }
            } else {
              if(obj.checked) value = obj.value;
              else value = '';
            }
          break;
      case 'select' :
      case 'select-one' :
            if(obj.options.length>0) {
                value = obj.options[obj.selectedIndex].value;
            }
          break;
      default :
            value = obj.value;
          break;
    }
    if(typeof(value)=='undefined'||!value) return '';
    return value.trim();
}

function XmlJsFilterExecuteFilter(filter, value) {
    switch(filter) {
      case "email" :
      case "email_address" :
          var regx = /^[_0-9a-zA-Z-]+(\.[_0-9a-zA-Z-]+)*@[0-9a-zA-Z-]+(\.[0-9a-zA-Z-]+)*$/;
          return regx.test(value);
        break;
      case "userid" :
      case "user_id" :
          var regx = /^[a-zA-Z]+([_0-9a-zA-Z]+)*$/;
          return regx.test(value);
        break;
      case "homepage" :
          var regx = /(^[_0-9a-zA-Z-]+(\.[_0-9a-zA-Z-]+)*@[0-9a-zA-Z-]+(\.[0-9a-zA-Z-]+)*$)/;
          return regx.test(value);
        break;
      case "korean" :
          var regx = /^[가-힣]*$/; 
          return regx.test(value);
        break;
      case "korean_number" :
          var regx = /^[가-힣0-9]*$/; 
          return regx.test(value);
        break;
      case "alpha" :
          var regx = /^[a-zA-Z]*$/; 
          return regx.test(value);
        break;
      case "alpha_number" :
          var regx = /^[a-zA-Z0-9]*$/; 
          return regx.test(value);
        break;
      case "number" :
          return !isNaN(value);
        break;
    }
}

function XmlJsFilterAlertMsg(target, msg_code, minlength, maxlength) {
    var target_msg = "";

    if(alertMsg[target]!='undefined') target_msg = alertMsg[target];
    else target_msg = target;

    var msg = "";
    if(typeof(alertMsg[msg_code])!='undefined') {
      if(alertMsg[msg_code].indexOf('%s')>=0) msg = alertMsg[msg_code].replace('%s',target_msg);
      else msg = target_msg+alertMsg[msg_code];
    } else {
      msg = msg_code;
    }

    if(typeof(minlength)!='undefined' && typeof(maxlength)!='undefined') msg += "("+minlength+"~"+maxlength+")";

    alert(msg);
    try {
      this.fo_obj[target].focus();
    } catch(e) {
    }
    return false;
}

function XmlJsFilterCheckFieldItem() {
    for(var i=0; i<this.field.length;i++) {
      var item = this.field[i];
      var target = item[0];
      var required = item[1];
      var minlength = item[2];
      var maxlength = item[3];
      var equalto = item[4];
      var filter = item[5].split(",");

      var value = this.getValue(target);
      if(!required && !value) return true;
      if(required && !value) return this.alertMsg(target,'isnull');

      if(minlength>0 && maxlength>0 && (value.length < minlength || value.length > maxlength)) return this.alertMsg(target, 'outofrange', minlength, maxlength);

      if(equalto) {
        var equalto_value = this.getValue(equalto);
        if(equalto_value != value) return this.alertMsg(target, 'equalto');
      }

      if(filter.length && filter[0]) {
        for(var j=0;j<filter.length;j++) {
          var filter_item = filter[j];
          if(!this.executeFilter(filter_item, value)) return this.alertMsg(target, "invalid_"+filter_item);
        }
      }
    }
    return true;
} 

function XmlJsFilterGetParameterParam() {
    var prev_name = '';
    if(this.parameter.length<1) {
      for(var i=0;i<this.fo_obj.length;i++) {
        var name = this.fo_obj[i].name;
        if(typeof(name)=='undefined'||!name||name==prev_name) continue;
        this.addParameterItem(name, name);
        prev_name = name;
      }
    }

    var params = new Array();
    for(var i=0; i<this.parameter.length;i++) {
      var item = this.parameter[i];
      var param = item[0];
      var target = item[1];
      var value = this.getValue(target);
      params[param] = value;
    }
    return params;
}

function XmlJsFilterProc(confirm_msg) {
    var result = this.checkFieldItem();
    if(!result) return false;

    if(typeof(confirm_msg)=='undefined') confirm_msg = '';

    var params = this.getParameterParam();
    var response = this.response;
    if(confirm_msg && !confirm(confirm_msg)) return false;
    if(!this.act) {
      this.user_func(this.fo_obj, params);
      return true;
    }

    exec_xml(this.module, this.act, params, this.user_func, response, params);
}

// form proc
function procFilter(fo_obj, filter_func) {
    filter_func(fo_obj);
    return false;
}
