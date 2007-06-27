function fnStartInit() {
    var obj = xGetElementById('loading');
    obj.style.visibility = 'hidden';
}
xAddEventListener(window,'load',fnStartInit);


// footer를 화면 크기에 맞춰 설정
xAddEventListener(window, 'load', fixLayoutFooter);
xAddEventListener(window, 'resize', fixLayoutFooter);
function fixLayoutFooter() {
    var headerHeight = 145;
    var bodyHeight = xHeight(xGetElementById('content_body'));
    var footerHeight = 55;
    var clientHeight = xClientHeight();
    var newHeight = clientHeight - footerHeight - headerHeight;
    if(typeof(editor_height)!='undefined') newHeight += editor_height;

    if(newHeight<bodyHeight) newHeight = bodyHeight;
    xHeight('content_body', newHeight);
    xHeight('left_menu_table', newHeight);
}
