function getFTPList(pwd)
{
    var form = jQuery("#ftp_form").get(0);
    if(typeof(pwd) != 'undefined')
    {
        form.ftp_root_path.value = pwd;
    }
    else
    {
        if(!form.ftp_root_path.value)
        {
            if(typeof(form.sftp) != 'undefined' && form.sftp.checked) {
                form.ftp_root_path.value = xe_root;
            }
            else
            {
                form.ftp_root_path.value = "/";
            }
        }
    }
    var params={}, data=jQuery("#ftp_form").serializeArray();
    jQuery.each(data, function(i, field){ params[field.name] = field.value });
    exec_xml('admin', 'getAdminFTPList', params, completeGetFtpInfo, ['list', 'error', 'message'], params, form);
}

function removeFTPInfo()
{
    var params = {};
    exec_xml('install', 'procInstallAdminRemoveFTPInfo', params, filterAlertMessage, ['error', 'message'], params);
}

function completeGetFtpInfo(ret_obj)
{
    if(ret_obj['error'] != 0)
    {
        alert(ret_obj['error']);
        alert(ret_obj['message']);
        return;
    }
    var e = jQuery("#ftplist").empty();
    var list = "";
    if(!jQuery.isArray(ret_obj['list']['item']))
    {
        ret_obj['list']['item'] = [ret_obj['list']['item']];
    }

    pwd = jQuery("#ftp_form").get(0).ftp_root_path.value;
    if(pwd != "/")
    {
        arr = pwd.split("/");
        arr.pop();
        arr.pop();
        arr.push("");
        target = arr.join("/");
        list = list + "<li><a href='#ftpSetup' onclick=\"getFTPList('"+target+"')\">../</a></li>";
    }
    
    for(var i=0;i<ret_obj['list']['item'].length;i++)
    {   
        var v = ret_obj['list']['item'][i];
        if(v == "../")
        {
            continue;
        } 
        else if( v == "./")
        {
            continue;
        }
        else
        {
            list = list + "<li><a href='#ftpSetup' onclick=\"getFTPList('"+pwd+v+"')\">"+v+"</a></li>";
        }
    }

    list = "<td><ul>"+list+"</ul></td>";
    e.append(jQuery(list));
}
