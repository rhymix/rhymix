
function doCancelDeclare() {
    var comment_srl = new Array();
    jQuery('#fo_list input[name=cart]:checked').each(function() {
        comment_srl[comment_srl.length] = jQuery(this).val();
    });

    if(comment_srl.length<1) return;

    var params = new Array();
    params['comment_srl'] = comment_srl.join(',');

    exec_xml('comment','procCommentAdminCancelDeclare', params, function() { location.reload(); });
}

function insertSelectedModule(id, module_srl, mid, browser_title) {
    location.href = current_url.setQuery('module_srl',module_srl);
}
