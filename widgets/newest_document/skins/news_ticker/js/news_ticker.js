/**
 * @brief 뉴스티커 형식으로 표시하기 위한 초기화 작업
 **/

var newsTickerMoveUpStep = new Array();
function doStartScroll(id, itemHeight, scrollSpeed) {
    var pObj = xGetElementById(id);
    var childObj = pObj.firstChild;

    while(childObj) {
        if(childObj.nodeName == 'UL') {
            childObj.id = id+'_first';
            var cloneObj = childObj.cloneNode(true);
            cloneObj.id = id+'_second';
            childObj.parentNode.insertBefore(cloneObj, childObj);

            var ticker = {"pObj":pObj, "child":childObj, "itemHeight":itemHeight, "scrollSpeed":scrollSpeed}

            newsTickerMoveUpStep[id] = 1;

            xAddEventListener(pObj, 'mouseover', function() { newsTickerMoveUpStep[id] = 0; } );
            xAddEventListener(pObj, 'mouseout', function() { newsTickerMoveUpStep[id] = 1; } );

            doScroll(ticker);
            return;
        }
        childObj = childObj.nextSibling;
    }

}
function doScroll(obj) {
    var st = obj.pObj.scrollTop;
    st += newsTickerMoveUpStep[obj.pObj.id];

    if(st > xHeight(obj.child)) st = 0;
    obj.pObj.scrollTop = st;

    if(obj.pObj.scrollTop % obj.itemHeight == 0) setTimeout( function() { doScroll(obj); }, 1000 );
    else setTimeout( function() { doScroll(obj); }, obj.scrollSpeed );

}
