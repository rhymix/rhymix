var xeSmartMenu = null;
var xeSmartUpperMenu = null;
function showXEMenu() {
    if(!xeSmartMenu) {

        xeSmartMenu = jQuery('<div>')
            .attr("className","smartPhoneList")
            .css('display','none')
            .css('backgroundColor','#fff');

        jQuery(document.body).append(xeSmartMenu);

        xeSmartMenu.slideIn = function(step) {
            var w = this.width() + Math.pow(step,2)*30;

            if(w>jQuery(document).width()) {
                this.css({left:0,right:0,display:'block'});
                this.width('');
                jQuery('.smartPhoneContentArea').css("display","none");
            } else {
                this.width(w);
                var o = parseInt(jQuery(document).width/w,10)/5;
                if(o>1) o = 1;
                setTimeout(function() { xeSmartMenu.slideIn(step+1); }, 50);
            }
        }

        xeSmartMenu.slideOut = function(step) {
            var l = parseInt(this.css('left'),10) + Math.pow(step,2)*30;

            if(l>jQuery('.smartPhoneContent').width()) {
                this.css({display:'none','left':''});
		jQuery('.smartPhoneContentArea').css("display","block");
            } else {
                var o = parseInt(jQuery(document).width/l,10)/5;
                if(o<0) o = 0;
                this.css('left',l+'px');
                setTimeout(function() { xeSmartMenu.slideOut(step+1); }, 50);
            }
        }
    }

    if(xeSmartMenu.css('display')=='none' && typeof(xeMenus)!='undefined') {
        xeSmartUpperMenu = null;
        var menu = findSmartNode(xeMenus);
        if(!menu) menu = xeMenus;
        var html = '<ul>';
        if(location.href.getQuery('mid')) html += '<li><a href="'+current_url.setQuery('mid','')+'">&lt; go Home &gt;</a></li>';
        if(xeSmartUpperMenu) html += '<li><a href="'+current_url.setQuery('mid',xeSmartUpperMenu.url)+'">&lt; go Upper &gt;</a></li>';
        for(var text in menu) {
            if(!text) continue;
            var url = menu[text].url;
            var href = '';
            if(/^[a-z0-9_]+$/i.test(url)) {
                href = request_uri.setQuery('mid',url);
                if(href.indexOf('?')>-1) href += '&smartphone=true';
                else href += '?smartphone=true';
            }
            else href = url;
            if(typeof(xeVid)!='undefined') {
                if(href.indexOf('?')>-1) href += '&vid='+xeVid;
                else href += '?vid='+xeVid;
            }
            html += '<li><a href="'+href+'">'+text+'</a></li>';
        }
        html += '</ul>';

        jQuery(xeSmartMenu).html(html);
        jQuery(xeSmartMenu).css({
            width:'1px',
            right:'0',
            top:'43px',
            display:'block',
            position:'absolute',
            padding:0
        });
        xeSmartMenu.slideIn(0);
    } else if(location.href.getQuery('mid')||location.href.getQuery('document_srl')) {
        xeSmartMenu.slideOut(0);
    }
}

function findSmartNode(nodes) {
    var mid = current_url.getQuery('mid');
    if(typeof(mid)=='undefined'||!mid) return nodes;
    for(var text in nodes) {
        if(!text) continue;
        if(nodes[text].childs) {
            var n = findSmartNode(nodes[text].childs);
            if(n) {
                xeSmartUpperMenu = nodes[text];
                return n;
            }
        }
        if(nodes[text].url == mid) {
            if(nodes[text].childs) return nodes[text].childs;
            return nodes;
        }
    }
    return null;
}
