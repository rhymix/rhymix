/**
 * @file   modules/ttimporter/js/importer_admin.js
 * @author zero (zero@nzeo.com)
 * @brief  importer에서 사용하는 javascript
 **/

/* Step Complete Import */
function completeImport(ret_obj) {
    var message = ret_obj['message'];
    var is_finished = ret_obj['is_finished'];
    var position = ret_obj['position'];
    alert(message);
    alert(position);

    xGetElementById("import_status").style.display = "block";

    if(is_finished=='Y') {
        alert(ret_obj["message"]);
        location.href = location.href;
    } else {
        var fo_obj = xGetElementById('fo_import');
        fo_obj.position.value = position;
        xInnerHtml('import_status', message);
        procFilter(fo_obj, import_tt);
    }
}

function doManualProcess() {
    var fo_obj = xGetElementById('fo_import');
    procFilter(fo_obj, import_tt);
}
