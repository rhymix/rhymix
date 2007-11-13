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
    if(selected_node  && selected_node.getAttribute("widget")) {
        selected_node = replaceOuterHTML(selected_node, widget_code);
        if(opener.doFitBorderSize) opener.doFitBorderSize();
    } else {
        var obj = opener.xGetElementById('zonePageContent');
        xInnerHtml(obj, xInnerHtml(obj)+widget_code);
    }
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

    fo_obj.widget_margin_left.value = selected_node.getAttribute("widget_margin_left");
    fo_obj.widget_margin_right.value = selected_node.getAttribute("widget_margin_right");
    fo_obj.widget_margin_bottom.value = selected_node.getAttribute("widget_margin_bottom");
    fo_obj.widget_margin_top.value = selected_node.getAttribute("widget_margin_top");



    for(var name in fo_obj) {
        var node = fo_obj[name];
        if(!node || typeof(node)=="undefined") continue;

        var length = node.length;
        var type = node.type;
        if((typeof(type)=='undefined'||!type) && typeof(length)!='undefined' && typeof(node[0])!='undefined' && length>0) type = node[0].type;
        else length = 0;

        switch(type) {
            case "text" :
            case "textarea" :
                    var val = selected_node.getAttribute(name);
                    if(val) node.value = val;
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
