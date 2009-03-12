function doSiteSignUp() {
    exec_xml('member','procModuleSiteSignUp', new Array(), function() { location.reload(); } );
}

function doSiteLeave(leave_msg) {
    if(!confirm(leave_msg)) return;
    exec_xml('member','procModuleSiteLeave', new Array(), function() { location.reload(); } );
}
