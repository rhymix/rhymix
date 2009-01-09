/**
 * @file   modules/widget/js/widget_admin.js
 * @author zero (zero@nzeo.com)
 * @brief  widget 모듈의 관리자용 javascript
 **/

/* 생성된 코드를 textarea에 출력 */
function completeGenerateCode(ret_obj) {
    var widget_code = ret_obj["widget_code"];

    var zone = xGetElementById("widget_code");
    zone.value = widget_code;
} 

/* 생성된 코드를 페이지 zone에 출력 */
function completeGenerateCodeInPage(ret_obj,response_tags,params,fo_obj) {
    var widget_code = ret_obj["widget_code"];
    if(!opener || !widget_code) {
        window.close(); 
        return;
    }

    opener.doAddWidgetCode(widget_code);

    window.close();
} 

/* 위젯 코드 생성시 스킨을 고르면 컬러셋의 정보를 표시 */
function doDisplaySkinColorset(sel, colorset) {
    var skin = sel.options[sel.selectedIndex].value;
    if(!skin) {
        xGetElementById("colorset_area").style.display = "none";
        setFixedPopupSize();
        return;
    }

    var params = new Array();
    params["selected_widget"] = xGetElementById("fo_widget").selected_widget.value;
    params["skin"] = skin;
    params["colorset"] = colorset;

    var response_tags = new Array("error","message","colorset_list");

    exec_xml("widget", "procWidgetGetColorsetList", params, completeGetSkinColorset, response_tags, params);
}

/* 서버에서 받아온 컬러셋을 표시 */
function completeGetSkinColorset(ret_obj, response_tags, params, fo_obj) {
    var sel = xGetElementById("fo_widget").widget_colorset;
    var length = sel.options.length;
    var selected_colorset = params["colorset"];
    for(var i=0;i<length;i++) sel.remove(0);

    var colorset_list = ret_obj["colorset_list"].split("\n");
    var selected_index = 0;
    for(var i=0;i<colorset_list.length;i++) {
        var tmp = colorset_list[i].split("|@|");
        if(selected_colorset && selected_colorset==tmp[0]) selected_index = i;
        var opt = new Option(tmp[1], tmp[0], false, false);
        sel.options.add(opt);
    }

    sel.selectedIndex = selected_index;

    xGetElementById("colorset_area").style.display = "block";
    setFixedPopupSize();
}

/* 페이지 모듈에서 내용의 위젯을 더블클릭하여 수정하려고 할 경우 */
var selected_node = null;
function doFillWidgetVars() {
    if(!opener || !opener.selectedWidget || !opener.selectedWidget.getAttribute("widget")) return;

    selected_node = opener.selectedWidget;

    // 스킨과 컬러셋은 기본
    var skin = selected_node.getAttribute("skin");
    var colorset = selected_node.getAttribute("colorset");
    var widget_sequence = parseInt(selected_node.getAttribute("widget_sequence"),10);

    var fo_obj = xGetElementById("fo_widget");

    var style = selected_node.getAttribute("style");
    if(typeof(style)=="object") style = style["cssText"];
    fo_obj.style.value = style;

    fo_obj.widget_padding_left.value = selected_node.getAttribute("widget_padding_left");
    fo_obj.widget_padding_right.value = selected_node.getAttribute("widget_padding_right");
    fo_obj.widget_padding_bottom.value = selected_node.getAttribute("widget_padding_bottom");
    fo_obj.widget_padding_top.value = selected_node.getAttribute("widget_padding_top");

    for(var name in fo_obj) {
        if(name.indexOf('_')==0) continue;
        var node = fo_obj[name];
        if(!node || typeof(node)=="undefined") continue;

        var length = node.length;
        var type = node.type;
        if((typeof(type)=='undefined'||!type) && typeof(length)!='undefined' && typeof(node[0])!='undefined' && length>0) type = node[0].type;
        else length = 0;

        switch(type) {
            case "hidden" :
            case "text" :
            case "textarea" :
                    var val = selected_node.getAttribute(name);
                    if(!val) continue;
                    var unescaped_val = unescape(val);
                    if(!unescaped_val) node.value = val;
                    else node.value = unescaped_val;
                break;
            case "checkbox" :
                    if(selected_node.getAttribute(name)) {
                        var val = selected_node.getAttribute(name).split(',');
                        if(fo_obj[name].length) {
                            for(var i=0;i<fo_obj[name].length;i++) {
                                var v = fo_obj[name][i].value;
                                for(var j=0;j<val.length;j++) {
                                    if(v == val[j]) {
                                        fo_obj[name][i].checked=true;
                                        break;
                                    }
                                }
                            }
                        } else {
                            if(fo_obj[name].value == val) fo_obj[name].checked =true;
                        }
                    }
                break;
            case "select" :
            case "select-one" :
                    var val = selected_node.getAttribute(name);
                    var sel = fo_obj[name];
                    if(!val) break;
                    for(var i=0;i<sel.options.length;i++) {
                        if(sel.options[i].value == val) sel.options[i].selected = true;
                        else sel.options[i].selected = false;
                    }
                break;
        }

    }

/*
    var marginLeft = 0;
    if(selected_node.style.marginLeft) marginLeft = parseInt(selected_node.style.marginLeft.replace(/px$/,''),10);
    var marginRight = 0;
    if(selected_node.style.marginRight) marginRight = parseInt(selected_node.style.marginRight.replace(/px$/,''),10);
    var border = 0;
    if(selected_node.style.border) border= parseInt(selected_node.style.boarder.replace(/px$/,''),10);
*/
    
    //  컬러셋 설정
    if(skin && xGetElementById("widget_colorset").options.length<1 && colorset) {
        doDisplaySkinColorset(xGetElementById("widget_skin"), colorset);
    }

    // widget sequence 설정
    fo_obj.widget_sequence.value = widget_sequence;
}

function checkFixType(obj) {
    var val = obj.options[obj.selectedIndex].value;
    if(val != "px") {
        var fo_obj = xGetElementById("fo_widget");
        var width = fo_obj.widget_width.value;
        if(width>100) fo_obj.widget_width.value = 100;
    }
}

// 위젯의 대상 모듈 입력기 (단일 선택)
function insertSelectedModule(id, module_srl, mid, browser_title) {
    var obj= xGetElementById('_'+id);
    var sObj = xGetElementById(id);
    sObj.value = module_srl;
    obj.value = browser_title+' ('+mid+')';
    
}

// 위젯의 대상 모듈 입력기 (다중 선택)
function insertSelectedModules(id, module_srl, mid, browser_title) {
    var sel_obj = xGetElementById('_'+id);
    for(var i=0;i<sel_obj.options.length;i++) if(sel_obj.options[i].value==module_srl) return;
    var opt = new Option(browser_title+' ('+mid+')', module_srl, false, false);
    sel_obj.options[sel_obj.options.length] = opt;
    if(sel_obj.options.length>8) sel_obj.size = sel_obj.options.length;
    
    syncMid(id);
}

function midMoveUp(id) {
    var sel_obj = xGetElementById('_'+id);
    if(sel_obj.selectedIndex<0) return;
    var idx = sel_obj.selectedIndex;

    if(idx < 1) return;

    var s_obj = sel_obj.options[idx];
    var t_obj = sel_obj.options[idx-1];
    var value = s_obj.value;
    var text = s_obj.text;
    s_obj.value = t_obj.value;
    s_obj.text = t_obj.text;
    t_obj.value = value;
    t_obj.text = text;
    sel_obj.selectedIndex = idx-1;
    
    syncMid(id);
}

function midMoveDown(id) {
    var sel_obj = xGetElementById('_'+id);
    if(sel_obj.selectedIndex<0) return;
    var idx = sel_obj.selectedIndex;

    if(idx == sel_obj.options.length-1) return;

    var s_obj = sel_obj.options[idx];
    var t_obj = sel_obj.options[idx+1];
    var value = s_obj.value;
    var text = s_obj.text;
    s_obj.value = t_obj.value;
    s_obj.text = t_obj.text;
    t_obj.value = value;
    t_obj.text = text;
    sel_obj.selectedIndex = idx+1;

    syncMid(id);
}

function midRemove(id) {
    var sel_obj = xGetElementById('_'+id);
    if(sel_obj.selectedIndex<0) return;
    var idx = sel_obj.selectedIndex;
    sel_obj.remove(idx);
    idx = idx-1;
    if(idx < 0) idx = 0;
    if(sel_obj.options.length) sel_obj.selectedIndex = idx;

    syncMid(id);
}

function syncMid(id) {
    var sel_obj = xGetElementById('_'+id);
    var valueArray = new Array();
    for(var i=0;i<sel_obj.options.length;i++) valueArray[valueArray.length] = sel_obj.options[i].value;
    xGetElementById(id).value = valueArray.join(',');
}

function getModuleSrlList(id) {
    var obj = xGetElementById(id);
    if(!obj.value) return;
    var value = obj.value;
    var params = new Array();
    params["module_srls"] = obj.value;
    params["id"] = id;

    var response_tags = new Array("error","message","module_list","id");
    exec_xml("widget", "getWidgetAdminModuleList", params, completeGetModuleSrlList, response_tags, params);
}

function completeGetModuleSrlList(ret_obj, response_tags) {
    var id = ret_obj['id'];
    var sel_obj = xGetElementById('_'+id);
    if(!sel_obj) return;

    var module_list = ret_obj['module_list'];
    if(!module_list) return;
    var item = module_list['item'];
    if(typeof(item.length)=='undefined' || item.length<1) item = new Array(item);

    for(var i=0;i<item.length;i++) {
        var module_srl = item[i].module_srl;
        var mid = item[i].mid;
        var browser_title = item[i].browser_title;
        var opt = new Option(browser_title+' ('+mid+')', module_srl);
        sel_obj.options.add(opt);
    }
}

function getModuleSrl(id) {
    var obj = xGetElementById(id);
    if(!obj.value) return;
    var value = obj.value;
    var params = new Array();
    params["module_srls"] = obj.value;
    params["id"] = id;

    var response_tags = new Array("error","message","module_list","id");
    exec_xml("widget", "getWidgetAdminModuleList", params, completeGetModuleSrl, response_tags, params);
}

function completeGetModuleSrl(ret_obj, response_tags) {
    var id = ret_obj['id'];
    var obj = xGetElementById('_'+id);
    var sObj = xGetElementById(id);
    if(!sObj || !obj) return;

    var module_list = ret_obj['module_list'];
    if(!module_list) return;
    var item = module_list['item'];
    if(typeof(item.length)=='undefined' || item.length<1) item = new Array(item);

    sObj.value = item[0].module_srl;
    obj.value = item[0].browser_title+' ('+item[0].mid+')';
}
