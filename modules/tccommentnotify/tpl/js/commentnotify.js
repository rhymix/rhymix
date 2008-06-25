function deleteChild(notified_srl)
{
    var e = xGetElementById('notified');
    e.value=notified_srl;
    var hF = xGetElementById("deleteChildForm");
    procFilter(hF, delete_child);
}

function deleteParent(notified_srl)
{
    var e = xGetElementById('notified');
    e.value=notified_srl;
    var hF = xGetElementById("deleteChildForm");
    procFilter(hF, delete_parent);
}

function completeDelete(ret_obj)
{
    var error = ret_obj['error'];
    var message = ret_obj['message'];
    alert(message);

    var url = current_url.setQuery('act','dispCommentNotifyAdminIndex');
    location.href = url;
}
