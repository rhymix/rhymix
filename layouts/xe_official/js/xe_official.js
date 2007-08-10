// Hide And Show Toggle
var cc=0
function hideShow(id) {
    if (cc==0) {
        cc=1
        document.getElementById(id).style.display="none";
    } else {
        cc=0
        document.getElementById(id).style.display="block";
    }
}

// Show And Hide Toggle
var cc=0
function showHide(id) {
    if (cc==0) {
        cc=1
        document.getElementById(id).style.display="block";
    } else {
        cc=0
        document.getElementById(id).style.display="none";
    }
}

// Local Navigation Toggle
function lnbToggle(id) {
	for(num=1; num<=3; num++) document.getElementById('D3MG'+num).style.display='none'; //D4MG1~D4MG3 까지 숨긴 다음
	document.getElementById(id).style.display='block'; //해당 ID만 보임
}

// IS
function chkIsKind(key, value) {
    showHide('selectOrder');
    xGetElementById('search_kind'+key).checked = true;
    xInnerHtml('search_kind_label', value);
}
