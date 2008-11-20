function toggleWriteFormHelp(obj) {
    obj.style.display = 'none';
    obj.nextSibling.style.display = 'block';
    obj.nextSibling.focus();
}



function toggle(objclass,f,type){
    var obj = $$('.'+objclass)[0];
    return toggleObject(obj,f,type);
}


function toggleObject(obj,f,type){
    var otype = {};
    if (typeof(type) == 'undefined') {
        otype = {open:'open',close:'close'};
    } else {
        var tmp = type.match(/([^|]*)|(.*)/);
        otype = {open: tmp[1],close: tmp[2]};
    }

    var act = (typeof(f)=='undefined' || f=='')?$Element(obj).hasClass(otype.open)?otype.close:otype.open:f;

    if(act == otype.close){
        $Element(obj).removeClass(otype.open);
        if(otype.close) $Element(obj).addClass(otype.close);
    }else{
        if(otype.close) $Element(obj).removeClass(otype.close);
        $Element(obj).addClass(otype.open);
    }
    return act;
}
function doFocusPlanetUserId(obj_id) {
    if(xScrollTop()) return;
    xAddEventListener(window,'load', function() {xGetElementById(obj_id).focus();} );
}
function _getPlanetContentTagEditForm(oBtn){
    for (oChild = oBtn; oChild; oChild = oChild.parentNode) {
        if (oChild.tagName.toLowerCase() == 'div' && $Element(oChild).hasClass('tag'))
            return oChild;
    }
    return false;
}

function showPlanetContentTagEditForm(oBtn,document_srl){
    var oTag = _getPlanetContentTagEditForm(oBtn);
    if(!oTag) return false;
    $Element(oTag).addClass('edit');

    $('form_planet_content_tag:'+document_srl).planet_content_tag.focus();
    return false;
}

function closePlanetContentTagEditForm(oBtn){
    var oTag = _getPlanetContentTagEditForm(oBtn);
    if(!oTag) return false;
    $Element(oTag).removeClass('edit');
}

function doPlanetPhotoUpload(obj) {
    obj.form.submit();
}


function notReadWelcome(){
    var c=$('welcome_msg');
    var params = new Array();
    params['module_srl'] = c.value;
    var response_tags = new Array('error','message');
    exec_xml('planet', 'procNotReadWelcome', params, function(){ $('welcome').style.display = 'none'; }, response_tags);
}


function getPlanetContentTags(no){
    no = parseInt(no);
    var rtn = new Array();
    var obj = document.getElementsByName('planet_content_tag:'+no);
    if(obj && obj.length>0){
        for(var i=0,c=obj.length;i<c;i++){
            rtn.push(obj[i].innerHTML);
        }
    }
    return rtn;
}

function deletePlanetContentTag(no,i){
    var arrNoTags = $A(getPlanetContentTags(no));
    $('form_planet_content_tag:'+no).planet_content_tag.value = arrNoTags.refuse(arrNoTags.$value()[i]).$value().join(',');
    procFilter($('form_planet_content_tag:'+no), modify_content_tag);
}


function doPlanetLogout() {
    var params = new Array();
    var response_tags = new Array('error','message');
    exec_xml('member', 'procMemberLogout', params, completeLogout, response_tags);
}

function completeLogout() {
    location.reload();
}

function showWritePost(){

    var o = $Element('writePost');

    // do close
    if(!o.hasClass('open')){
        toggleWritePost();
    }
    //$('writePostForm').content.focus();
    $('writePost_content').focus();
}

function closeWritePost(){
    toggle('writePost');
    //$('writePost').reset();
    //$('writePost_content').reset();
}


function toggleWritePost(){
    var o = $Element('writePost');
    var t = $Element('writeBody');

    // do close
    if(o.hasClass('open')){

        if($Agent().navigator().ie){
            closeWritePost();
        }else{
            t.attr('style','overflow:hidden');
            moveHeight('writeBody',{end:140,callback:function(){t.attr('style','');toggle('writePost');}});
        }

        $Cookie().set('writePost','close',30);
        //$('writePostForm').reset();
    // do open
    }else{
        if ($Agent().navigator().ie) {
          toggle('writePost');
        }else{
            toggle('writePost');
            t.attr('style','overflow:hidden');
            t.height(0);
            moveHeight('writeBody',{start:0,end:30,callback:function(){t.attr('style','');}});

        }

        $Cookie().set('writePost','open',30);
        $('writePost_content').focus();

    }
}



function showWriteMemo(){
    toggle('memo','close');
    toggle('form');
    $('planet_memo').focus();
}


function showBtnDeleteTag(o){
    toggleObject(o,'hover','hover|');
}

function closeBtnDeleteTag(o){
    toggleObject(o,'','hover|');
}

function togglePreview(){
    var e = $Element('preview');

    if ($Cookie().get('preview') == 'off') {
        $Cookie().set('preview', '', 30);
        if ($('writePostForm').content.value.length>1) {
            planetPreview($('writePostForm').content);
        }
    } else {
        $Cookie().set('preview', 'off', 30);
        if(!e.hasClass('off')) e.addClass('off');

    }
    $('writePostForm').content.focus();
}

function toggleTagRank(){
    var o = $Element($$('.layer')[0]);
    // do close
    if($Element($$('.tagRank')[0]).hasClass('open')){
        o.disappear(0,function(){toggle('tagRank','close');o.attr('style','');})

    // do open
    }else{
        o.appear(0,function(){toggle('tagRank','open');})
    }

}


function moveHeight(obj,option){
    var self = this;
    this.obj = $Element(obj);
    this.start = typeof option.start == 'undefined' ? this.obj.height() : option.start;
    this.end = option.end;
    this.p = this.start < this.end ? 1 : -1;
    this.s = 9;
    this.callback = option.callback || new Function;

    var func = function(){
        self.s *= 1.2;
        if (self.p >0 ? self.start <= self.end : self.start >= self.end) {
            self.start = self.obj.height() + p*self.s;
            self.obj.height(self.start);
            self._timer = setTimeout(func, 4);
        }else{
            self.obj.height(self.end);
            self.callback();
        }
    };
    func();
}

function toggleMemo(){

    var p = $Element('planetMemo');
    var f = $Element('planetMemoFirst');
    var ul = $Element('planetMemoList');
    if(ul.visible()){
        p.removeClass('open');
        //f.show();
        ul.hide();

    }else{
        p.addClass('open');
        //f.hide();
        ul.show();
        p.opacity(0);
        p.appear(0,function(){});
    }

}
function showWriteMemoForm(){
    if($Element('planetMemoList').visible()){
        toggleMemo();
    }
    toggle('form');
    $('planet_memo').focus();
}

xAddEventListener(window,'load', function() {
    if(!$('btn_preview')) return;
    if($Cookie().get('preview')=='off'){
        $('btn_preview').checked = false;
    }else{
        $('btn_preview').checked = true;
    }
});

xAddEventListener(window,'load', function() {
    if(!$('writePost')) return;
    if($Cookie().get('writePost')!='close'){
        toggleObject('writePost','open');
        //$Cookie().set('writePost','open',30);
    }else{
        toggleObject('writePost','close');
        //$Cookie().set('writePost','',30);
    }

});




function showInsertPhoneNumber(){
    var p = $Element(cssquery('form.mobile dl')[0]);
    if(p.hasClass('open')){
        closeInsertPhoneNumber();
    }else{
        p.addClass('open');
        cssquery('form.mobile input[name=phone_number]')[0].focus();
    }
}

function closeInsertPhoneNumber(){
    $Element(cssquery('form.mobile dl')[0]).removeClass('open');
}

function setPhoneNumber(f){
    var phone_number = f.phone_number[0].value + f.phone_number[1].value + f.phone_number[2].value;
    if(phone_number.length >= 10){
        var response_tags = new Array('error','message');
        exec_xml('planet','procPlanetSetSMS',{'phone_number':phone_number},resultSetPhoneNumber,response_tags);
    }
}

function resultSetPhoneNumber(ret_obj, response_tags, params) {
    if(ret_obj.message) alert(ret_obj.message);
    closeInsertPhoneNumber();
}


function setTag(tag){
    tag = tag.trim();
    toggleWriteFormHelp(cssquery('input[name=about_tag]')[0]);
    var input_tag = cssquery('input[name=content_tag]')[0];
    var ck = 0;
    if(input_tag.value){
        var taglist = input_tag.value + ','+tag;
        taglist = taglist.split(',');
        for(var i=0,c=taglist.length;i<c;i++){
            taglist[i] = taglist[i].trim();
            if(taglist[i] == tag) ck++;
        }

        if(ck>1){
            taglist = $A(taglist).refuse(tag).$value();
        }

        input_tag.value = $A(taglist).unique().$value().join(',');
    }else{
        input_tag.value = tag;
    }
}