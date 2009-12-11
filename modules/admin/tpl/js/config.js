function completeGetFtpInfo(ret_obj)
{
    var e = jQuery("#ftplist").empty();
    var list = "";
    if(!jQuery.isArray(ret_obj['list']['item']))
    {
        ret_obj['list']['item'] = [ret_obj['list']['item']];
    }

    if(pwd != "/")
    {
        arr = pwd.split("/");
        arr.pop();
        arr.pop();
        arr.push("");
        target = arr.join("/");
        list = list + "<li><a href='"+current_url.setQuery('pwd',target)+"#ftpSetup'>../</a></li>";
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
            list = list + "<li><a href='"+current_url.setQuery('pwd',pwd+v)+"#ftpSetup'>"+v+"</a></li>";
        }
    }

    list = "<td><ul>"+list+"</ul></td>";
    e.append(jQuery(list));
}

