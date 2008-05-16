/**
 * @brief 애드온의 활성/비활성 토글용 함수
 * fo_addon이라는 id를 가지는 form에 인자로 주어진 addon값을 세팅후 실행
 **/
function doToggleAddon(addon) {
    var fo_obj = xGetElementById('fo_addon');
    fo_obj.addon.value = addon;
    procFilter(fo_obj, toggle_activate_addon);
}

function toggleSectionCheckBox(obj, id) {
    var box_list = xGetElementsByTagName('input', xGetElementById(id));
    if(typeof(box_list.length)=='undefined') return;
    for(var i in box_list) {
        box_list[i].checked = obj.checked;
    }
}
