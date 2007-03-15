/**
 * richtext 에디터 관련
 **/
// iframe의 id prefix
var iframe_id = 'editor_iframe_';

// srl값에 해당하는 iframe의 object를 return
function editorGetIFrame(upload_target_srl) {
  var obj_id = iframe_id+upload_target_srl;
  return xGetElementById(obj_id);
}

// editor 초기화를 onload이벤트 후에 시작시킴
function editorInit(upload_target_srl) {
  var start_func = function() { editorStart(upload_target_srl); }
  var init_func = function() { setTimeout(start_func, 300); }
  xAddEventListener(window, 'load', init_func);
}

// editor 초기화 (upload_target_srl로 iframe객체를 얻어서 쓰기 모드로 전환)
function editorStart(upload_target_srl) {
  // iframe obj를 찾음
  var iframe_obj = editorGetIFrame(upload_target_srl);
  if(!iframe_obj) return;

  // 현 에디터를 감싸고 있는 form문을 찾아서 content object를 찾아서 내용 sync
  var fo_obj = iframe_obj.parentNode;
  while(fo_obj.nodeName != 'FORM') { fo_obj = fo_obj.parentNode; }

  var content = fo_obj.content.value;

  // 기본 폰트를 가져옴
  var default_font = xGetElementById('editor_font_'+upload_target_srl).options[1].value;

  // iframe내의 document object 
  var contentDocument = iframe_obj.contentWindow.document;

  // editing가능하도록 설정
  

  // 기본 내용 작성
  var contentHtml = ''+
    '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">'+
    '<html><head><meta http-equiv="content-type" content="text/html; charset=utf-8"/>';

  /********* 오페라에서 stylesheet를 못 가져와서 일단 주석 처리하고 수동 처리
  for(var i in document.styleSheets) {
    var tmp_obj = document.styleSheets[i];
    if(typeof(tmp_obj.href)=='undefined'||tmp_obj.href.lastIndexOf(".css")<0) continue;
    contentHtml += "<link rel=\"StyleSheet\" HREF=\""+tmp_obj.href+"\" type=\"text/css\" />";
  }
  **********/
  contentHtml += "<link rel=\"stylesheet\" href=\"./common/css/common.css\" type=\"text/css\" />";
  contentHtml += "<link rel=\"stylesheet\" href=\""+editor_path+"/css/editor.css\" type=\"text/css\" />";
  contentHtml += ""+
              "</head><body style=\"background-color:#FFFFFF;font-family:"+default_font+";font-size:9pt;\">"+
              content+
              "</body></html>";

  contentDocument.designMode = 'on';
  contentDocument.open("text/html","replace");
  contentDocument.write(contentHtml);
  contentDocument.close();

  // 작성시 필요한 이벤트 체크
  if(xIE4Up) xAddEventListener(contentDocument, 'keydown',editorKeyPress);
  else xAddEventListener(contentDocument, 'keypress',editorKeyPress);
  xAddEventListener(contentDocument,'mousedown',editorHideObject);

  //xAddEventListener(document,'keypress',editorKeyPress);
  xAddEventListener(document,'mouseup',editorEventCheck);
  xAddEventListener(document,'mousedown',editorHideObject);

  // 문단작성기능 on/off
  if(xIE4Up) {
    xDisplay('editor_paragraph_'+upload_target_srl, 'none');
    xDisplay('editor_use_paragraph_box_'+upload_target_srl, 'inline');
  } else {
    xDisplay('editor_paragraph_'+upload_target_srl, 'block');
    xDisplay('editor_use_paragraph_box_'+upload_target_srl, 'none');
  }

  // 에디터의 내용을 지속적으로 fo_obj.content.value에 입력
  editorSyncContent(fo_obj.content, upload_target_srl);
}

var _editorSyncList = new Array(); 
function editorSyncContent(obj, upload_target_srl) {
  _editorSyncList[_editorSyncList.length] = {field:obj, upload_target_srl:upload_target_srl}
}

function _editorSync() {
  for(var i=0;i<_editorSyncList.length;i++) {
    var field = _editorSyncList[i].field;
    var upload_target_srl = _editorSyncList[i].upload_target_srl;
    var content = editorGetContent(upload_target_srl);
    if(typeof(content)=='undefined'||!content) continue;
    field.value = content;
  }
  setTimeout(_editorSync, 1000);
}
xAddEventListener(window, 'load', _editorSync);

// 문단기능 toggle
function editorUseParagraph(obj, upload_target_srl) { 
  toggleDisplay('editor_paragraph_'+upload_target_srl);
}

// 에디터의 내용 return
function editorGetContent(upload_target_srl) {
  var iframe_obj = editorGetIFrame(upload_target_srl);
  if(!iframe_obj) return;
  var html = '';
  html = xInnerHtml(iframe_obj.contentWindow.document.body);
  if(!html) return;
  return html.trim();
}

// 에디터 내의 선택된 부분의 html 코드를 return
function editorGetSelectedHtml(upload_target_srl) {
  var iframe_obj = editorGetIFrame(upload_target_srl);
  if(xIE4Up) {
    var range = iframe_obj.contentWindow.document.selection.createRange();
    var html = range.htmlText;
    range.select();
    return html;
  } else {
    var range = iframe_obj.contentWindow.getSelection().getRangeAt(0);
    var dummy = xCreateElement('div');
    dummy.appendChild(range.cloneContents());
    var html = xInnerHtml(dummy);
    return html;
  }
}

// 에디터 내의 선택된 부분의 html코드를 변경
function editorReplaceHTML(iframe_obj, html) {
  iframe_obj.contentWindow.focus();
  if(xIE4Up) {
    var range = iframe_obj.contentWindow.document.selection.createRange();
    range.pasteHTML(html);
  } else {
    var range = iframe_obj.contentWindow.getSelection().getRangeAt(0);
    range.deleteContents();
    range.insertNode(range.createContextualFragment(html));
  }
}

// 입력 키에 대한 이벤트 체크
function editorKeyPress(evt) {
  var e = new xEvent(evt);
  if (e.keyCode == 13) {
    if(xIE4Up && e.shiftKey == false && !xGetElementById("use_paragraph").checked ) {
      if(e.target.parentElement.document.designMode!="On") return;
      var obj = e.target.parentElement.document.selection.createRange();
      obj.pasteHTML('<br />');
      obj.select();
      evt.cancelBubble = true;
      evt.returnValue = false;
      return;
    }
  }

  if (e.ctrlKey) {
    switch(e.keyCode) {
      case 98 : // b
          editorDo('Bold',null,e.target);
          xPreventDefault(evt);
          xStopPropagation(evt);
        break;
      case 105 : // i
          editorDo('Italic',null,e.target);
          xPreventDefault(evt);
          xStopPropagation(evt);
        break;
      case 117 : // u
          editorDo('Underline',null,e.target);
          xPreventDefault(evt);
          xStopPropagation(evt);
        break;
      case 83 : // s
      case 115 : // s
          editorDo('StrikeThrough',null,e.target);
          xPreventDefault(evt);
          xStopPropagation(evt);
        break;
    }
  }
}

// 에디터 상단의 버튼 클릭시 action 처리
var editorPrevObj = null;
var editorPrevSrl = null;
function editorEventCheck(evt) {
  var e = new xEvent(evt);
  var target_id = e.target.id;
  if(target_id.indexOf('editor_')!=-1) {
    var tmp_str = target_id.split('_');
    var method_name = tmp_str[1];
    var upload_target_srl = tmp_str[2];
    switch(method_name) {
      case 'Bold' :
      case 'Italic' :
      case 'Underline' :
      case 'StrikeThrough' :
      case 'justifyleft' :
      case 'justifycenter' :
      case 'justifyright' :
      case 'indent' :
      case 'outdent' :
      case 'insertorderedlist' :
      case 'insertunorderedlist' :
          editorDo(method_name, '', upload_target_srl);
        break;
      default :
          editorPrevSrl = upload_target_srl;
          switch(method_name) {
            case "addemoticon" :
                var x = (screen.availWidth - 225)/2;
                var y = (screen.availHeight - 150)/2;
                var editor_popup = window.open(editor_path+"popup/add_emoticon.php","_editorPopup","top="+y+",left="+x+",width=225,height=150,resizable=no,toolbars=no,scrollbars=no");
                if(editor_popup) editor_popup.focus();
                return;
              break;
            case "quotation" :
                var x = (screen.availWidth - 400)/2;
                var y = (screen.availHeight - 400)/2;
                var editor_popup = window.open(editor_path+"popup/add_quotation.php","_editorPopup","top="+y+",left="+x+",width=400,height=400,resizable=no,toolbars=no,scrollbars=no");
                if(editor_popup) editor_popup.focus();
                return;
              break;
            case "addurl" :
                var x = (screen.availWidth - 400)/2;
                var y = (screen.availHeight - 220)/2;
                var editor_popup = window.open(editor_path+"popup/add_url.php","_editorPopup","top="+y+",left="+x+",width=400,height=220,resizable=no,toolbars=no,scrollbars=no");
                if(editor_popup) editor_popup.focus();
                return;
              break;
            case "addimage" :
                var x = (screen.availWidth - 420)/2;
                var y = (screen.availHeight - 80)/2;
                var editor_popup = window.open(editor_path+"popup/add_image.php","_editorPopup","top="+y+",left="+x+",width=420,height=80,resizable=no,toolbars=no,scrollbars=no");
                if(editor_popup) editor_popup.focus();
                return;
              break;
            case "addmultimedia" :
                var x = (screen.availWidth - 400)/2;
                var y = (screen.availHeight - 220)/2;
                var editor_popup = window.open(editor_path+"popup/add_multi.php","_editorPopup","top="+y+",left="+x+",width=420,height=110,resizable=no,toolbars=no,scrollbars=no");
                if(editor_popup) editor_popup.focus();
                return;
              break;
            case "addhtml" :
                var x = (screen.availWidth - 400)/2;
                var y = (screen.availHeight - 500)/2;
                var editor_popup = window.open(editor_path+"popup/add_html.php","_editorPopup","top="+y+",left="+x+",width=400,height=500,resizable=no,toolbars=no,scrollbars=no");
                if(editor_popup) editor_popup.focus();
                return;
              break;
            case "ForeColor" :
            case "BackColor" :
                var x = (screen.availWidth - 145)/2;
                var y = (screen.availHeight - 95)/2;
                var editor_popup = window.open(editor_path+"popup/color_box.php?mode="+method_name,"_editorPopup","top="+y+",left="+x+",width=145,height=95,resizable=no,toolbars=no,scrollbars=no");
                if(editor_popup) editor_popup.focus();
                return;
          }
        break;
    }
  }
  return;
}

// focus
function editorFocus(upload_target_srl) {
  var iframe_obj = editorGetIFrame(upload_target_srl);
  iframe_obj.contentWindow.focus();
}


// 편집 기능 실행
function editorDo(name, value, target) {
  if(typeof(target)=='object') _editorDoObject(name, value, target);
  else _editorDoSrl(name, value, target);
}

function _editorDoSrl(name, value, upload_target_srl) {
  var iframe_obj = editorGetIFrame(upload_target_srl);
  editorFocus(upload_target_srl);
  if(xIE4Up) iframe_obj.contentWindow.document.execCommand(name, false, value);
  else iframe_obj.contentWindow.document.execCommand(name, false, value);
  editorFocus(upload_target_srl);
}

function _editorDoObject(name, value, obj) {
  if(xIE4Up) {
    obj.parentElement.document.execCommand(name, false, value);
  } else {
    obj.parentNode.execCommand(name, false, value);
  }
}

function editorHideObject(evt) {
  if(!editorPrevObj) return;
  var e = new xEvent(evt);
  var tobj = e.target;
  while(tobj) {
    if(tobj.id == editorPrevObj.id) { 
      return;
    }
    tobj = xParent(tobj);
  }
  editorPrevObj.style.visibility = 'hidden';
  editorPrevObj = null;
  return;
}

function editorChangeFontName(obj,srl) {
  var value = obj.options[obj.selectedIndex].value;
  if(!value) return;
  editorDo('FontName',value,srl);
  obj.selectedIndex = 0;
}

function editorChangeFontSize(obj,srl) {
  var value = obj.options[obj.selectedIndex].value;
  if(!value) return;
  editorDo('FontSize',value,srl);
  obj.selectedIndex = 0;
}

function editorSetForeColor(color_code) {
  editorDo("ForeColor",color_code,editorPrevSrl);
  editorPrevObj.style.visibility = 'hidden';
  editorFocus(editorPrevSrl);
}

function editorSetBackColor(color_code) {
  if(xIE4Up) editorDo("BackColor",color_code,editorPrevSrl);
  else editorDo("hilitecolor",color_code,editorPrevSrl);
  editorFocus(editorPrevSrl);
}

function editorInsertEmoticon(obj) {
  editorFocus(editorPrevSrl);
  editorDo("InsertImage",obj.src,editorPrevSrl);
  editorFocus(editorPrevSrl);
}

function editorDoInsertUrl(link, upload_target_srl) {
  editorFocus(upload_target_srl);
  var iframe_obj = editorGetIFrame(srl);
  editorReplaceHTML(iframe_obj, link);
}

function editorInsertUrl(text, url, link_type) {
  if(!text || !url) return;
  //if(!/^(http|ftp)/i.test(url)) url = 'http://'+url;

  var link = '';
  if(!link_type) link = "<a href=\""+url+"\" target=\"_blank\">"+text+"</a>";
  else link = "<a href=\""+url+"\" class=\""+link_type+"\" style='text-decoration:none;' target=\"_blank\">"+text+"</a>";

  editorFocus(editorPrevSrl);
  var obj = editorGetIFrame(editorPrevSrl)
  editorReplaceHTML(obj, link);
}

function editorInsertImage(url, src_align) {
  if(!url) return;
  //if(!/^(http|ftp)/i.test(url)) url = 'http://'+url;

  editorFocus(editorPrevSrl);

  var html = "<img src=\""+url+"\" border=\"0\" alt=\"i\" ";
  if(typeof(src_align)!='undefined'&&src_align) html += " align=\""+src_align+"\"";
  html += " />";
  var obj = editorGetIFrame(editorPrevSrl);
  editorReplaceHTML(obj, html);
}

function editorGetMultimediaHtml(url, width, height, source_filename) {
  if(typeof(width)=='undefined'||!width) width = 540;
  if(typeof(height)=='undefined'||!height) height= 420;

  var type = "application/x-mplayer2";
  var pluginspace = "http://www.microsoft.com/Windows/MediaPlayer/";
  var kind = 'movie';

  if(/\.swf$/i.test(url)) kind = 'flash';
  if(typeof(source_filename)!='undefined' && /\.swf$/i.test(source_filename)) kind = 'flash';

  if(kind=='flash') {
    type = "application/x-shockwave-flash";
    pluginspace = "http://www.macromedia.com/go/getflashplayer";
  }
  var html = "<embed src=\""+url+"\" width=\""+width+"\" height=\""+height+"\" autostart=\"0\"  type=\""+type+"\" pluginspage=\""+pluginspace+"\"></embed><BR />";
  return html;
}

function editorInsertMultimedia(url, width, height) {
  if(url) {
    var html = editorGetMultimediaHtml(url, width, height);
    editorFocus(editorPrevSrl);
    var obj = editorGetIFrame(editorPrevSrl)
    editorReplaceHTML(obj, html);
    editorFocus(editorPrevSrl);
  }
}

function editorInsertHTML(html) {
  if(!html) return;

  editorFocus(editorPrevSrl);
  var obj = editorGetIFrame(editorPrevSrl)
  editorReplaceHTML(obj, html);
  editorFocus(editorPrevSrl);
}

function editorInsertQuotation(html) {
  if(!html) return;

  if(!xIE4Up) html += "<br />";
  editorFocus(editorPrevSrl);
  var obj = editorGetIFrame(editorPrevSrl)
  editorReplaceHTML(obj, html);
  editorFocus(editorPrevSrl);
}

function editorHighlight(ret_obj, response_tags, obj) {
  var html = ret_obj['html'];
  html = "<div class='php_code'>"+html+"</div>";
  if(!xIE4Up) html += "<br />";
  editorReplaceHTML(obj, html);
}

/**
 * iframe 드래그 관련
 **/
var editorIsDrag = false;
var editorDragY = 0;
var editorDragObj = null;
var editorDragID = '';
xAddEventListener(document, 'mousedown', editorDragStart);
xAddEventListener(document, 'mouseup', editorDragStop);
function editorDragStart(evt) {
  var e = new xEvent(evt);
  var obj = e.target;
  if(typeof(obj.id)=='undefined'||!obj.id) return;
  var id = obj.id;
  if(id.indexOf('editor_drag_bar_')!=0) return;

  editorIsDrag = true;
  editorDragObj = e.target;
  editorDragY = e.pageY; 
  editorDragID = id.substr('editor_drag_bar_'.length);
  xAddEventListener(document, 'mousemove', editorDragMove);
  xAddEventListener(editorDragObj, 'mouseout', editorDragMove);

  var iframe_obj = editorGetIFrame(editorDragID);
  xAddEventListener(iframe_obj, 'mousemove', editorDragMove);
}

function editorDragStop(evt) {
  var iframe_obj = editorGetIFrame(editorDragID);
  xRemoveEventListener(document, 'mousemove', editorDragMove);
  xRemoveEventListener(iframe_obj, 'mousemove', editorDragMove);

  editorIsDrag = false;
  editorDragY = 0;
  editorDragObj = null;
  editorDragID = '';
}

function editorDragMove(evt) {
  if(typeof(editorIsDrag)=='undefined'||!editorIsDrag) return;
  var e = new xEvent(evt);
  var iframe_obj = editorGetIFrame(editorDragID);

  var y = e.pageY;
  var yy = y - editorDragY;
  if(yy<0) return;
  editorDragY = y; 

  var editorHeight = xHeight(iframe_obj);
  xHeight(iframe_obj, editorHeight+yy);
}

/**
 * 파일 업로드 관련
 **/
var uploading_file = false;
var uploaded_files = new Array();

// 업로드를 하기 위한 준비 시작
function editor_upload_init(upload_target_srl) {
  xAddEventListener(window,'load',function() {editor_upload_form_set(upload_target_srl);} );
}

// upload_target_srl에 해당하는 form의 action을 iframe으로 변경
function editor_upload_form_set(upload_target_srl) {
  // 업로드용 iframe을 생성
  if(!xGetElementById('tmp_upload_iframe')) {
    if(xIE4Up) {
      window.document.body.insertAdjacentHTML("afterEnd", "<iframe name='tmp_upload_iframe' style='display:none;width:1px;height:1px;position:absolute;top:-10px;left:-10px'></iframe>");
    } else {
      var obj_iframe = xCreateElement('IFRAME');
      obj_iframe.name = obj_iframe.id = 'tmp_upload_iframe';
      obj_iframe.style.display = 'none';
      obj_iframe.style.width = '1px';
      obj_iframe.style.height = '1px';
      obj_iframe.style.position = 'absolute';
      obj_iframe.style.top = '-10px';
      obj_iframe.style.left = '-10px';
      window.document.body.appendChild(obj_iframe);
    }
  }

  // form의 action 을 변경
  var field_obj = xGetElementById("uploaded_file_list_"+upload_target_srl);
  if(!field_obj) return;
  var fo_obj = field_obj.parentNode;
  while(fo_obj.nodeName != 'FORM') { fo_obj = fo_obj.parentNode; }
  fo_obj.target = 'tmp_upload_iframe';

  // upload_target_srl에 해당하는 첨부파일 목록을 로드
  var module = "";
  if(fo_obj["module"]) module = fo_obj.module.value;
  var mid = "";
  if(fo_obj["mid"]) mid = fo_obj.mid.value;
  var upload_target_srl = fo_obj.upload_target_srl.value;

  var url = "./?act=procDeleteFile&upload_target_srl="+upload_target_srl;
  if(module) url+="&module="+module;
  if(mid) url+="&mid="+mid;

  // iframe에 url을 보내버림
  var iframe_obj = xGetElementById('tmp_upload_iframe');
  if(!iframe_obj) return;

  iframe_obj.contentWindow.document.location.href=url;
}

// 파일 업로드
function editor_file_upload(field_obj, upload_target_srl) {
    if(uploading_file) return;

    var fo_obj = field_obj.parentNode;
    while(fo_obj.nodeName != 'FORM') { fo_obj = fo_obj.parentNode; }

    uploading_file = true;
    fo_obj.submit();
    uploading_file = false;

    var sel_obj = xGetElementById('uploaded_file_list_'+upload_target_srl);
    var string = 'wait for uploading...';
    var opt_obj = new Option(string, '', true, true);
    sel_obj.options[sel_obj.options.length] = opt_obj;
}

// 업로드된 파일 목록을 삭제
function editor_upload_clear_list(upload_target_srl) {
  var obj = xGetElementById('uploaded_file_list_'+upload_target_srl);
  while(obj.options.length) {
    obj.remove(0);
  }
  var preview_obj = xGetElementById('uploaded_file_preview_box_'+upload_target_srl);
  xInnerHtml(preview_obj,'')
}

// 업로드된 파일 정보를 목록에 추가
function editor_insert_uploaded_file(upload_target_srl, file_srl, filename, file_size, disp_file_size, uploaded_filename, sid) {
  var obj = xGetElementById('uploaded_file_list_'+upload_target_srl);
  var string = filename+' ('+disp_file_size+')';
  var opt_obj = new Option(string, file_srl, true, true);
  obj.options[obj.options.length] = opt_obj;

  var file_obj = {file_srl:file_srl, filename:filename, file_size:file_size, uploaded_filename:uploaded_filename, sid:sid}
  uploaded_files[file_srl] = file_obj;

  editor_preview(obj, upload_target_srl);
}

// 파일 목록창에서 클릭 되었을 경우 미리 보기
function editor_preview(sel_obj, upload_target_srl) {
  if(sel_obj.options.length<1) return;
  var file_srl = sel_obj.options[sel_obj.selectedIndex].value;
  var obj = uploaded_files[file_srl];
  if(typeof(obj)=='undefined'||!obj) return;
  var uploaded_filename = obj.uploaded_filename;
  var preview_obj = xGetElementById('uploaded_file_preview_box_'+upload_target_srl);

  if(!uploaded_filename) {
    xInnerHtml(preview_obj, '');
    return;
  }

  var html = "";

  // 플래쉬 동영상의 경우
  if(/\.flv$/i.test(uploaded_filename)) {
    html = "<EMBED src=\""+editor_path+"flvplayer/flvplayer.swf?autoStart=true&file="+uploaded_filename+"\" width=\"120\" height=\"120\" type=\"application/x-shockwave-flash\"></EMBED>";
  // 플래쉬 파일의 경우
  } else if(/\.swf$/i.test(uploaded_filename)) {
    html = "<EMBED src=\""+uploaded_filename+"\" width=\"120\" height=\"120\" type=\"application/x-shockwave-flash\"></EMBED>";
  // wmv, avi, mpg, mpeg등의 동영상 파일의 경우
  } else if(/\.(wmv|avi|mpg|mpeg|asx|asf|mp3)$/i.test(uploaded_filename)) {
    html = "<EMBED src=\""+uploaded_filename+"\" width=\"120\" height=\"120\" autostart=\"true\" Showcontrols=\"0\"></EMBED>";
  // 이미지 파일의 경우
  } else if(/\.(jpg|jpeg|png|gif)$/i.test(uploaded_filename)) {
    html = "<img src=\""+uploaded_filename+"\" border=\"0\" width=\"120\" height=\"120\" />";
  }
  xInnerHtml(preview_obj, html);
}

// 업로드된 파일 삭제
function editor_remove_file(upload_target_srl) {
  var obj = xGetElementById('uploaded_file_list_'+upload_target_srl);
  if(obj.options.length<1) return;
  var file_srl = obj.options[obj.selectedIndex].value;
  if(!file_srl) return;

  // 삭제하려는 파일의 정보를 챙김;;
  var fo_obj = obj;
  while(fo_obj.nodeName != 'FORM') { fo_obj = fo_obj.parentNode; }
  var mid = fo_obj.mid.value;
  var upload_target_srl = fo_obj.upload_target_srl.value;
  var url = "./?mid="+mid+"&act=procDeleteFile&upload_target_srl="+upload_target_srl+"&file_srl="+file_srl;

  // iframe에 url을 보내버림
  var iframe_obj = xGetElementById('tmp_upload_iframe');
  if(!iframe_obj) return;

  iframe_obj.contentWindow.document.location.href=url;
}

// 업로드 목록의 선택된 파일을 내용에 추가
function editor_insert_file(upload_target_srl, align) {
  var obj = xGetElementById('uploaded_file_list_'+upload_target_srl);
  if(obj.options.length<1) return;
  var file_srl = obj.options[obj.selectedIndex].value;
  if(!file_srl) return;
  var file_obj = uploaded_files[file_srl];
  var filename = file_obj.filename;
  var sid = file_obj.sid;
  var uploaded_filename = file_obj.uploaded_filename;
  editorPrevSrl = upload_target_srl;

  // 바로 링크 가능한 파일의 경우 (이미지, 플래쉬, 동영상 등..)
  if(uploaded_filename && typeof(align)!='undefined') {

    var type = "";

    // 이미지 파일의 경우
    if(/\.(jpg|jpeg|png|gif)$/i.test(uploaded_filename)) {

      editorFocus(editorPrevSrl);

      var html = "<img src=\""+uploaded_filename+"\" border=\"0\" alt=\""+filename+"\" ";
      if(typeof(align)!='undefined'&&align) html += " align=\""+align+"\"";
      html += " />"
      var obj = editorGetIFrame(editorPrevSrl);
      editorReplaceHTML(obj, html);

    // 이미지외의 경우는 대체 이미지를 넣음
    } else {
      // 플래쉬 동영상의 경우
      if(/\.flv$/i.test(uploaded_filename)) {
        type = "flv";
        uploaded_filename = editor_path+"flvplayer/flvplayer.swf?autoStart=true&file="+uploaded_filename;
      // 플래쉬 파일의 경우
      } else if(/\.swf$/i.test(uploaded_filename)) {
        type = "swf";
      // wmv, avi, mpg, mpeg등의 동영상 파일의 경우
      } else if(/\.(wmv|avi|mpg|mpeg)$/i.test(uploaded_filename)) {
        type = "multimedia";
      }

      var alt = "align="+align+"|@|src="+uploaded_filename+"|@|type="+type;
      var html = "<img src=\""+editor_path+"images/blank.gif\" style=\"width:400px;height:300px;\" alt=\""+alt+"\" class=\"editor_multimedia\"/>";

      var iframe_obj = editorGetIFrame(editorPrevSrl);
      editorReplaceHTML(iframe_obj, html);
    }

  // binary파일의 경우 다운로드 링크를 추가
  } else {
    var fo_obj = obj;
    while(fo_obj.nodeName != 'FORM') { fo_obj = fo_obj.parentNode; }
    var mid = fo_obj.mid.value;
    var upload_target_srl = fo_obj.upload_target_srl.value;
    var url = "./?mid="+mid+"&amp;act=procDownload&amp;upload_target_srl="+upload_target_srl+"&amp;file_srl="+file_srl+"&amp;sid="+sid;

    var x = (screen.availWidth - 400)/2;
    var y = (screen.availHeight - 220)/2;
    var editor_popup = window.open(editor_path+"popup/add_url.php?title="+escape(filename)+"&url="+escape(url),"_editorPopup","top="+y+",left="+x+",width=400,height=220,resizable=no,toolbars=no,scrollbars=no");
    if(editor_popup) editor_popup.focus();
  } 

}

/**
 * 글을 쓰다가 페이지 이동시 첨부파일에 대한 정리
 **/
function editorRemoveAttachFiles(mid, upload_target_srl) {
  var obj = xGetElementById('uploaded_file_list_'+upload_target_srl);
  if(obj.options.length<1) return;

  var params = new Array();
  params['upload_target_srl'] = upload_target_srl;
  exec_xml(mid, 'procClearFile', params, null, null, null);
}
