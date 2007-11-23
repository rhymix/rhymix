function overTab(obj) {
    var tab_id = obj.id;

    var cObj = obj.parentNode.firstChild;
    while(cObj) {
        if(cObj.nodeName == "DIV" && cObj.id) {
            var cTabID= cObj.id;
            if(cTabID.indexOf('tab')<0) continue;
            var cContentID = cTabID.replace(/^tab/,'content');

            if(tab_id == cTabID) {
                cObj.className = "tab on";
                xGetElementById(cContentID).className = "tabContent show";
            } else {
                cObj.className = "tab";
                xGetElementById(cContentID).className = "tabContent hide";
            }
        }
        cObj = cObj.nextSibling;
    }

}
