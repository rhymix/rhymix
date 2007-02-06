/**
 * 몇가지 유용한 & 기본적으로 자주 사용되는 자바스크립트 함수들 모음
 **/

// string prototype으로 trim 함수 추가
String.prototype.trim = function() {/*{{{*/
  return this.replace(/(^\s*)|(\s*$)/g, "");
}/*}}}*/

// 주어진 인자가 하나라도 defined되어 있지 않으면 false return
function isDef() {/*{{{*/
  for(var i=0; i<arguments.length; ++i) {
    if(typeof(arguments[i])=='undefined') return false;
  }
  return true;
}/*}}}*/

// 특정 div(or span...)의 display옵션 토글
function toggleDisplay(obj, opt) {/*{{{*/
  obj = xGetElementById(obj);
  if(typeof(opt)=='undefined') opt = 'inline';
  if(obj.style.display == 'none') obj.style.display = opt;
  else obj.style.display = 'none';
}/*}}}*/

// 멀티미디어 출력용 (IE에서 플래쉬/동영상 주변에 점선 생김 방지용)
function displayMultimedia(type, src, style) {/*{{{*/
  var clsid = '';
  var codebase = '';
  var html = '';
  switch(type) {
    case 'flv' :
    case 'swf' :
        clsid = 'clsid:D27CDB6E-AE6D-11cf-96B8-444553540000'; 
        codebase = 'http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.c-ab#version=6,0,29,0'; 
        html = ""+
          "<script type=\"text/javascript\">"+
          "document.writeln(\""+
          "<object classid='"+clsid+"' codebase='"+codebase+"' "+style+">"+
          "<param name='movie' value='"+src+"' />"+
          "<param name='quality' value='high' />"+
          "<embed src='"+src+"' autostart='0' "+style+"></embed>"+
          "<\/object>"+
          "\");"+
          "</script>";
      break;
    default : 
        html = ""+
          "<script type=\"text/javascript\">"+
          "document.writeln(\""+
          "<embed src='"+src+"' autostart='0' "+style+"></embed>"+
          "\");"+
          "</script>";
      break;
  }

  return html;
}/*}}}*/

// 화면내에서 이미지 리사이즈 및 클릭할 수 있도록 
function resizeImageContents() {/*{{{*/
  var objs = xGetElementsByTagName('img');
  for(var i in objs) {
    var obj = objs[i];
    var parent = xParent(obj);
    if(!obj||!parent) continue;

    var parent_width = xWidth(parent);
    var obj_width = xWidth(obj);
    if(parent_width>=obj_width) continue;

    obj.style.cursor = 'pointer';
    obj.source_width = obj_width;
    obj.source_height = xHeight(obj);
    xWidth(obj, xWidth(parent)-1);

    xAddEventListener(obj,'click', resizeImagePopup);
  }
}/*}}}*/
xAddEventListener(window, 'load', resizeImageContents);

function resizeImagePopup(evt) {/*{{{*/
  var e = new xEvent(evt);
  if(!e.target.src) return;
  var obj = e.target;
  var scrollbars = "no";
  var resizable = "no";

  var width = obj.source_width;
  if(width>screen.availWidth) {
    width = screen.availWidth-50;
    scrollbars = "yes";
    resizable = "yes";
  }
  var height = obj.source_height;
  if(height>screen.availHeight) {
    height = screen.availHeight-50;
    scrollbars = "yes";
    resizable = "yes";
  }
  var popup = window.open(e.target.src,"_imagePopup","width="+width+",height="+height+",top=1,left=1,resizable="+resizable+",toolbars=no,scrollbars="+resizable);
  if(popup) popup.focus();
}/*}}}*/

// 에디터에서 사용하는 내용 여닫는 코드 (고정)
function svc_folder_open(id) {/*{{{*/
    var open_text_obj = xGetElementById("_folder_open_"+id);
    var close_text_obj = xGetElementById("_folder_close_"+id);
    var folder_obj = xGetElementById("_folder_"+id);
    open_text_obj.style.display = "none";
    close_text_obj.style.display = "block";
    folder_obj.style.display = "block";
}/*}}}*/

function svc_folder_close(id) {/*{{{*/
    var open_text_obj = xGetElementById("_folder_open_"+id);
    var close_text_obj = xGetElementById("_folder_close_"+id);
    var folder_obj = xGetElementById("_folder_"+id);
    open_text_obj.style.display = "block";
    close_text_obj.style.display = "none";
    folder_obj.style.display = "none";
}/*}}}*/
