function completeGetFtpInfo(ret_obj)
{

    var e = jQuery("#ftplist").empty();
    var list = "";
    for(var i=0;i<ret_obj['list']['item'].length;i++)
    {   
        var v = ret_obj['list']['item'][i];
        if(v == "../")
        {
            if(pwd == "/")
            {
                continue;
            }

            arr = pwd.split("/");
            arr.pop();
            arr.pop();
            arr.push("");
            target = arr.join("/");
            list = list + "<li><a href='"+current_url.setQuery('pwd',target)+"#ftpSetup'>"+v+"</a></li>";
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

