xAddEventListener(document, 'click', chkRevSelect);
function chkRevSelect(evt) {
    var e = new xEvent(evt);
    if(!e.target || e.target.nodeName != 'INPUT') return;
    
    var name = e.target.name;
    if(!/^(b|e)rev$/.test(name)) return;

    var fo = xGetElementById('logForm');
    var erev = 0;
    var brev = 0;

    var eObj = fo.erev;
    for(var i=0;i<eObj.length;i++) {
        if(eObj[i].checked) erev = parseInt(eObj[i].value,10);
    }

    var bObj = fo.brev;
    for(var i=0;i<bObj.length;i++) {
        if(bObj[i].checked) brev = parseInt(bObj[i].value,10);
    }

    if(erev<=brev) {
        for(var i=0;i<eObj.length;i++) {
            var value = eObj[i].value;
            if(value<=brev) {
                if(i>0) eObj[i-1].checked = true;
                else {
                    eObj[0].checked = true;
                    bObj[1].checked = true;
                }
                break;
            }
        }

    }
}
