/**
 * @file   modules/importer/js/importer_admin.js
 * @author zero (zero@nzeo.com)
 * @brief  importer에서 사용하는 javascript
 **/

/* Step 1 처리 */
function completeStep1(ret_obj) {
    var error = ret_obj['error'];
    var message = ret_obj['message'];
    var next_step = ret_obj['next_step'];
    var module_list = ret_obj['module_list'];

    if(module_list) {
        var sel = xGetElementById("target_module");
        var module_list_arr = module_list.split("\n");
        for(var i=0;i<module_list_arr.length;i++) {
            var pos = module_list_arr[i].indexOf(',');
            var value = module_list_arr[i].substr(0,pos);
            var text = module_list_arr[i].substr(pos+1);

            var opt_obj = new Option(text, value, true, true);
            sel.options[sel.options.length] = opt_obj;
        }
        sel.selectedIndex = 0;
    }

    xGetElementById('step1').style.display = 'none';
    xGetElementById('step'+next_step).style.display = 'block';
}

/* Step 1-2 모듈선택 처리 (카테고리 있으면 카테고리 선택으로, 아니면 바로 파일 업로드로) */
function completeStep12(ret_obj) {
    var error = ret_obj['error'];
    var message = ret_obj['message'];
    var next_step = ret_obj['next_step'];
    var category_list = ret_obj['category_list'];

    if(category_list) {
        var sel = xGetElementById("target_category");
        var category_list_arr = category_list.split("\n");
        for(var i=0;i<category_list_arr.length;i++) {
            var pos = category_list_arr[i].indexOf(',');
            var value = category_list_arr[i].substr(0,pos);
            var text = category_list_arr[i].substr(pos+1);

            var opt_obj = new Option(text, value, true, true);
            sel.options[sel.options.length] = opt_obj;
        }
        sel.selectedIndex = 0;
    }

    xGetElementById('step12').style.display = 'none';
    xGetElementById('step'+next_step).style.display = 'block';
}

/* Step 1-3 카테고리 선택후 파일 업로드 보여주기 */
function doStep13(fo_obj) {
    xGetElementById('step13').style.display = 'none';
    xGetElementById('step2').style.display = 'block';
    return false;
}

/* Step 2 XML파일을 입력받아서 처리~ */
function doStep2(fo_obj) {
    var sel_module = xGetElementById("target_module");
    if(sel_module.options.length>0) {
        var module_srl = sel_module.options[sel_module.selectedIndex].value;
        fo_obj.module_srl.value  = module_srl;
    }

    var sel_category = xGetElementById("target_category");
    if(sel_category.options.length>1) {
        var category_srl = sel_category.options[sel_category.selectedIndex].value;
        fo_obj.category_srl.value = category_srl;
    }

    procFilter(fo_obj, import_xml);


    xGetElementById('step2_status').style.display = 'block';
    return false;
}

/* Step Complete Import */
function completeImport(ret_obj) {
    var message = ret_obj['message'];
    var is_finished = ret_obj['is_finished'];
    var position = ret_obj['position'];

    if(is_finished=='Y') {
        alert(ret_obj["message"]);
        location.href = location.href;
    } else {
        var fo_obj = xGetElementById('fo_step2');
        fo_obj.position.value = position;
        xInnerHtml('step2_position', position);
        procFilter(fo_obj, import_xml);
    }
}

function doManualProcess() {
    var fo_obj = xGetElementById('fo_step2');
    procFilter(fo_obj, import_xml);
}

/* 회원정보와 게시물의 싱크 */
function doStep3(fo_obj) {
    exec_xml('importer','procImporterAdminSync', new Array(), completeStep3);
    return false;
}

function completeStep3(ret_obj) {
    alert(ret_obj['message']);
    location.href=location.href;
}
