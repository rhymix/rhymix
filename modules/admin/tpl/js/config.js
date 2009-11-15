function completeGetFtpInfo(ret_obj)
{

    var e = jQuery("#ftplist").empty();
    var list = "";
    for(var i=0;i<ret_obj['list']['item'].length;i++)
    {   
        var v = ret_obj['list']['item'][i];
        list = list + "<li><a href='"+current_url.setQuery('pwd',pwd+v)+"#ftpSetup'>"+v+"</a></li>";
    }
    list = "<td><ul>"+list+"</ul></td>";
    e.append(jQuery(list));
}

