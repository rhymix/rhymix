var xeSmartMenu = null;
function showXEMenu() {
    if(!xeSmartMenu) {

        xeSmartMenu = jQuery('<div>')
            .attr("className","smartPhoneList")
            .css('display','none');

        jQuery(document.body).append(xeSmartMenu);

        xeSmartMenu.slideIn = function(step) {
            var w = this.width() + Math.pow(step,2)*30;

            if(w>jQuery(document).width()) {
                this.css({left:0,right:0,display:'block'});
                this.width('');
            } else {
                this.width(w);
                var o = parseInt(jQuery(document).width/w,10)/10;
                if(o>1) o = 1;
                setTimeout(function() { xeSmartMenu.slideIn(step+1); }, 50);
            }
        }

        xeSmartMenu.slideOut = function(step) {
            var l = parseInt(this.css('left'),10) + Math.pow(step,2)*30;

            if(l>jQuery('.smartPhoneContent').width()) {
                this.css({display:'none','left':''});
            } else {
                var o = parseInt(jQuery(document).width/l,10)/10;
                if(o<0) o = 0;
                this.css('left',l+'px');
                setTimeout(function() { xeSmartMenu.slideOut(step+1); }, 50);
            }
        }
    }

    if(xeSmartMenu.css('display')=='none' && typeof(xeMenus)!='undefined') {
        var menu = findSmartNode(xeMenus);
        if(!menu) menu = xeMenus;
        var html = '<ul><li><a href="'+request_uri.setQuery('smartphone','true')+'">Top page</a></li>';
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
            if(current_mid == url) html += '<li class="selected">';
            else html += '<li>';
            html += '<a href="'+href+'">'+text+'</a></li>';
        }
        html += '</ul>';

        jQuery(xeSmartMenu).html(html);
        jQuery(xeSmartMenu).css({
            width:'1px',
            right:'0',
            top:'43px',
            bottom:'43px',
            display:'block',
            padding:0
        });
        xeSmartMenu.slideIn(0);
    } else {
        xeSmartMenu.slideOut(0);
    }
}

function findSmartNode(nodes) {
    if(typeof(current_mid)=='undefined') return;
    var mid = current_mid;
    if(location.href.indexOf(mid)<0) mid = null;
    for(var text in nodes) {
        if(!text) continue;
        if(nodes[text].url == mid) {
            if(nodes[text].childs) return nodes[text].childs;    
        }
        if(nodes[text].childs && nodes[text].childs.length) {
            var n = findSmartNode(nodes[text].childs);
            if(n) return n;
        }
    }
    return null;
}
