/**********************************************************
 * image gallery in zeroboard5
 * created by zero (zero@nzeo.com, http://www.nzeo.com)
 *********************************************************/

var z_gallery_opacity = new Array();
var z_gallery_highlight_speed = new Array();
var z_gallery_highlight_opacity = new Array();

var _gallery_srl_list = new Array();
var _gallery_images = new Array();
var _gallery_idx = new Array();
var _obj_thumbnail = new Array();

function z_add_gallery(srl, thumbnail_url, source_file_url) {
    var obj = new Array();

    var image = new Image();
    image.src = source_file_url;

    var t_image = new Image();
    t_image.src = thumbnail_url.replace("&amp;","&");

    obj['thumbnail_url'] = thumbnail_url;
    obj['source_url'] = source_file_url;
    obj['thumbnail'] = t_image;
    obj['gallery'] = image;

    if(typeof(_gallery_images[srl])=='undefined') {
        _gallery_images[srl] = new Array();
        _gallery_idx[srl] = 0;
        _obj_thumbnail[srl] = new Array();
    }

    _gallery_images[srl][_gallery_images[srl].length] = obj;
}

function z_start_gallery() {

    for(var i = 0; i < _gallery_srl_list.length; i++) {
        var srl = _gallery_srl_list[i];

        if(!_gallery_images[srl].length) continue;

        var zone_gallery = xGetElementById('zone_gallery_'+srl);
        var zone_thumbnail = xGetElementById('zone_thumbnail_'+srl);

        xInnerHtml(zone_gallery,'');

        var obj = _gallery_images[srl][0];

        var obj_gallery = new Image();
        obj_gallery.src = obj['gallery'].src;
        obj_gallery.id = 'main_gallery_'+srl;
        obj_gallery.srl = srl;
        obj_gallery.style.cursor = 'pointer';
        obj_gallery.style.opacity = 1;
        obj_gallery.style.filter = "alpha(opacity=100)";
        obj_gallery.start_opacity = 0;

        zone_gallery.appendChild(obj_gallery);

        for(var idx = 0; idx < _gallery_images[srl].length; idx++) {
            var s_obj = _gallery_images[srl][idx];

            if(s_obj) {
                _obj_thumbnail[srl][idx] = new Image();
                _obj_thumbnail[srl][idx].src = s_obj['thumbnail'].src;
                _obj_thumbnail[srl][idx].start_opacity = 0;
                _obj_thumbnail[srl][idx].id = 'thumbnail_'+srl+'_'+idx;
                _obj_thumbnail[srl][idx].srl = srl;
                _obj_thumbnail[srl][idx].idx = idx;
                _obj_thumbnail[srl][idx].style.cursor = 'pointer';
                _obj_thumbnail[srl][idx].style.width = '60px';
                _obj_thumbnail[srl][idx].style.height= '60px';
                if(idx == _gallery_idx[srl]) {
                    _obj_thumbnail[srl][idx].style.opacity = 1;
                    _obj_thumbnail[srl][idx].style.filter = "alpha(opacity=100)";
                } else {
                    _obj_thumbnail[srl][idx].style.opacity = 0.3;
                    _obj_thumbnail[srl][idx].style.filter = "alpha(opacity=30)";
                }
                _obj_thumbnail[srl][idx].style.margin = '0px 5px 5px 5px';
                zone_thumbnail.appendChild(_obj_thumbnail[srl][idx]);

            }
        }
    }

    if(xIE4Up) {
        xAddEventListener(document,'mousewheel',z_gallery_check_wheel);
    } 
    xAddEventListener(document,'mouseup',z_change_gallery);
    xAddEventListener(document,'mouseover',z_gallery_do_focus);
    xAddEventListener(document,'mouseout',z_gallery_do_focusout);

    _gallery_srl_list = new Array();
}

function z_show_gallery(srl, idx) {
    if(!_gallery_images[srl].length) return;
    if(_gallery_idx[srl] == idx) return;

    _gallery_idx[srl] = idx;

    var obj = _gallery_images[srl][_gallery_idx[srl]];
    var obj_gallery = xGetElementById('main_gallery_'+srl);
    if(obj['gallery'].src == obj_gallery.src) return;

    obj_gallery.parentNode.removeChild(obj_gallery);

    obj_gallery = new Image();
    obj_gallery.src = obj['gallery'].src;
    obj_gallery.id = 'main_gallery_'+srl;
    obj_gallery.srl = srl;
    obj_gallery.style.cursor = 'pointer';

    obj_gallery.start_opacity = 0;
    obj_gallery.style.width = obj['gallery'].width+"px";
    obj_gallery.style.height = obj['gallery'].height+"px";
    obj_gallery.style.opacity = 0;
    obj_gallery.style.filter = "alpha(opacity=0)";
    obj_gallery.start_opacity = 1;

    var zone_gallery = xGetElementById('zone_gallery_'+srl);
    zone_gallery.appendChild(obj_gallery);

    setTimeout(function _start() {z_gallery_opacity_up(obj_gallery,z_gallery_opacity[srl],z_gallery_highlight_opacity[srl]);}, z_gallery_highlight_speed[srl]);

    for(var idx = 0; idx < _gallery_images[srl].length; idx ++) {
        var s_obj = xGetElementById('thumbnail_'+srl+'_'+idx);
        if(!s_obj) continue;

        if(idx == _gallery_idx[srl]) {
            s_obj.style.opacity = 1;
            s_obj.style.filter = "alpha(opacity=100)";
        } else {
            s_obj.style.opacity = z_gallery_opacity[srl];
            s_obj.style.filter = "alpha(opacity="+(z_gallery_opacity[srl]*100)+")";
        }
    }
}

function z_gallery_opacity_up(obj,opacity,up) {
    var srl = obj.srl;
    if(obj.start_opacity!=1) {
        if(obj.id == 'main_gallery_'+srl || obj.idx == _gallery_idx[srl]) {
            obj.style.opacity = 1;
            obj.style.filter = "alpha(opacity=100)";
        } else {
            obj.style.opacity = z_gallery_opacity[srl];
            obj.style.filter = "alpha(opacity="+(z_gallery_opacity[srl]*100)+")";
        }
        return;
    }
    if(opacity<1) {
        up = z_gallery_highlight_opacity[srl];
        opacity += up;
        if(opacity>=1) opacity = 1.0;
        if(opacity>=0.5) opacity = 1.0;
        obj.style.opacity = opacity;
        obj.style.filter = "alpha(opacity="+(opacity*100)+")";
        if(opacity<1) setTimeout(function _opacity_up() {z_gallery_opacity_up(obj, opacity,up);},z_gallery_highlight_speed[srl]);
    } else {
        obj.start_opacity = 0;
    }
}

function z_change_next_gallery(srl) {
    var idx = _gallery_idx[srl] +1;
    if(idx>=_gallery_images[srl].length) idx = 0;
    setTimeout(function show_gallery() { z_show_gallery(srl, idx); }, 50);
}

function z_change_prev_gallery(srl) {
    var idx = _gallery_idx[srl] -1;
    if(idx<0) idx = _gallery_images[srl].length-1;
    setTimeout(function show_gallery() { z_show_gallery(srl, idx); }, 50);
}

function z_change_gallery(evt) {
    var e = new xEvent(evt);
    var obj = e.target;
    var srl = obj.srl;
    var idx = obj.idx;
    if(typeof(srl)=='undefined') return;
    if(typeof(idx)=='undefined') z_change_next_gallery(srl);
    else {
        setTimeout(function show_gallery() { z_show_gallery(srl, idx); }, 50);
    }
}

function z_gallery_do_focus(evt) {
    var e = new xEvent(evt);
    var obj = e.target;
    var srl = obj.srl;
    if(typeof(srl)=='undefined') return;
    if(obj.idx == _gallery_idx[srl]) return;
    if(obj.id == 'main_gallery_'+srl) return;
    obj.start_opacity = 1;
    z_gallery_opacity_up(obj,z_gallery_opacity[srl],z_gallery_highlight_opacity[srl]);
    xPreventDefault(evt);
    xStopPropagation(evt);
}

function z_gallery_do_focusout(evt) {
    var e = new xEvent(evt);
    var obj = e.target;
    var srl = obj.srl;
    if(typeof(srl)=='undefined') return;
    if(obj.idx == _gallery_idx[srl]) return;
    if(obj.id == 'main_gallery_'+srl) return;
    obj.start_opacity = 0;
    obj.style.opacity = z_gallery_opacity[srl];
    obj.style.filter = "alpha(opacity="+(z_gallery_opacity[srl]*100)+")";
}

function z_gallery_check_wheel(evt) {
    var e = new xEvent(evt);
    var obj = e.target;
    var srl = obj.srl;
    if(typeof(srl)=='undefined') return;
    if(e.target.id != 'main_gallery_'+srl) return;
    if(evt.wheelDelta<0) z_change_next_gallery(srl);
    else z_change_prev_gallery(srl);
    xPreventDefault(evt);
    xStopPropagation(evt);
}

function zb5_board_cancel(url) {
    if(!confirm(alert_msg['msg_cancel'])) return false;
    location.href=url;
    return false;
}
