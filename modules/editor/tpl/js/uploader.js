/**
 * @author zero (zero@nzeo.com)
 * @version 0.1
 * @brief 파일 업로드 관련
 *
 ***********************************************************************************************************************************************
 * 제로보드XE의 게시물 파일업로드 컴포넌트는 "mmSWFUpload 1.0: Flash upload dialog - http://swfupload.mammon.se/" 를 사용합니다.
 * - SWFUpload is (c) 2006 Lars Huring and Mammon Media and is released under the MIT License:http://www.opensource.org/licenses/mit-license.php
 ***********************************************************************************************************************************************
 * 감사합니다.
 **/
var uploading_file = false;
var uploaded_files = new Array();

// 업로드를 하기 위한 준비 시작
function editor_upload_init(upload_target_srl) {
      xAddEventListener(window,'load',function() {editor_upload_form_set(upload_target_srl);} );
}

// upload_target_srl에 해당하는 form의 action을 iframe으로 변경
function editor_upload_form_set(upload_target_srl) {
    try {
        document.execCommand('BackgroundImageCache',false,true);
    } catch(e) { }

    // SWFUploader load
    var uploader_name = "swf_uploader_"+upload_target_srl;
    var embed_html = "";
    var flashVars = 'uploadProgressCallback=editor_upload_progress&uploadFileErrorCallback=editor_upload_error_handle&allowedFiletypesDescription='+uploader_setting["allowed_filetypes_description"]+'&autoUpload=true&allowedFiletypes='+uploader_setting["allowed_filetypes"]+'&maximumFilesize='+uploader_setting["allowed_filesize"]+'&uploadQueueCompleteCallback=editor_display_uploaded_file&uploadScript='+escape(request_uri+'?mid='+current_url.getQuery('mid')+'&act=procFileUpload&upload_target_srl='+upload_target_srl+'&PHPSESSID='+xGetCookie(zbxe_session_name));

    if(navigator.plugins&&navigator.mimeTypes&&navigator.mimeTypes.length) {
        embed_html = '<embed type="application/x-shockwave-flash" src="'+request_uri+'/modules/editor/tpl/images/SWFUpload.swf" width="1" height="1" id="'+uploader_name+'" name="'+uploader_name+'" quality="high" wmode="transparent" menu="false" flashvars="'+flashVars+'" />';
    } else {
        embed_html = '<object id="'+uploader_name+'" classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" width="1" height="1"><param name="movie" value="'+request_uri+'./modules/editor/tpl/images/SWFUpload.swf" /><param name="bgcolor" value="#000000" /><param name="quality" value="high" /><param name="wmode" value="transparent" /><param name="menu" value="false" /><param name="flashvars" value="'+flashVars+'" /></object>';
    }

    if(xIE4Up) {
        window.document.body.insertAdjacentHTML("afterEnd", "<div style='width:1px;height:1px;position:absolute;top:0px;left:0px'>"+embed_html+"</div>");
    } else {
        var dummy = xCreateElement("div");
        dummy.style.width = "1px";
        dummy.style.height = "1px";
        dummy.style.position="absolute";
        dummy.style.top="0px";
        dummy.style.left="0px";
        xInnerHtml(dummy, embed_html);
        window.document.body.appendChild(dummy);
    }

    // 임시 iframe을 생성
    if(!xGetElementById('tmp_upload_iframe')) {
        if(xIE4Up) {
            window.document.body.insertAdjacentHTML("afterEnd", "<iframe id='tmp_upload_iframe' name='tmp_upload_iframe' style='display:none;width:1px;height:1px;position:absolute;top:-10px;left:-10px'></iframe>");
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

    // upload_target_srl에 해당하는 첨부파일 목록을 로드 (procDeleteFile에 file_srl을 보내주지 않으면 삭제시도는 없이 목록만 갱신할 수 있음) 
    var module = "";
    if(fo_obj["module"]) module = fo_obj.module.value;
    var mid = "";
    if(fo_obj["mid"]) mid = fo_obj.mid.value;
    var document_srl = "";
    if(fo_obj["document_srl"]) document_srl = fo_obj.document_srl.value;

    // 기 등록된 파일 표시
    editor_display_uploaded_file(upload_target_srl);
}

// upload_target_srl에 등록된 파일 표시
var _prev_upload_target_srl = 0;
function editor_display_uploaded_file(upload_target_srl) {
    if(typeof(upload_target_srl)=='undefined'||!upload_target_srl) {
        if(_prev_upload_target_srl) {
            upload_target_srl = _prev_upload_target_srl;
            _prev_upload_target_srl = 0;
        } else return;
    }
    var url = request_uri + "?act=procFileDelete&upload_target_srl="+upload_target_srl+"&mid="+current_url.getQuery('mid');

    // iframe에 url을 보내버림
    var iframe_obj = xGetElementById('tmp_upload_iframe');
    if(!iframe_obj) return;
    iframe_obj.contentWindow.document.location.href=url;
}

// 파일 업로드 에러 핸들링
function editor_upload_error_handle(errcode,file,msg) {
    switch(errcode) {
        case -10 : 
                alert("- Error Code: HTTP Error\n- File name: "+file.name+"\n- Message: "+msg);
            break;
        case -20 : 
                alert("- Error Code: No upload script\n- File name: "+file.name+"\n- Message: "+msg);
            break;
        case -30 :
                alert("- Error Code: IO Error\n- File name: "+file.name+"\n- Message: "+msg);
            break;
        case -40 : 
                alert("- Error Code: Security Error\n- File name: "+file.name+"\n- Message: "+msg);
            break;
        case -50 : 
                alert("- Error Code: Filesize exceeds limit\n- File name: "+file.name+"\n- File size: "+file.size+"\n- Message: "+msg);
            break;
    }
}

// 파일 업로드
function editor_upload_file(upload_target_srl) {
    var swf_uploader = xGetElementById("swf_uploader_"+upload_target_srl);
    if(!swf_uploader) return;
    swf_uploader.browse();
    _prev_upload_target_srl = upload_target_srl;
}

// 업로드 진행상태 표시
var _progress_start = false;
function editor_upload_progress(file, bytesLoaded) {
    var obj = xGetElementById('uploaded_file_list_'+_prev_upload_target_srl);
    if(!_progress_start) {
        while(obj.options.length) {
            obj.remove(0);
        }
        _progress_start = true;
    }

    var percent = Math.ceil((bytesLoaded / file.size) * 100);
    var filename = file.name;
    if(filename.length>15) filename = filename.substr(0,15)+'...';

    var text = filename + ' ('+percent+'%)';
    if(!obj.options.length || obj.options[obj.options.length-1].value != file.id) {
        var opt_obj = new Option(text, file.id, true, true);
        obj.options[obj.options.length] = opt_obj;
    } else {
        obj.options[obj.options.length-1].text = text;
    }
}

// 업로드된 파일 목록 비움
function editor_upload_clear_list(upload_target_srl) {
    var obj = xGetElementById('uploaded_file_list_'+upload_target_srl);
    while(obj.options.length) {
        obj.remove(0);
    }
    var preview_obj = xGetElementById('preview_uploaded_'+upload_target_srl);
    xInnerHtml(preview_obj,'')
}

// 업로드된 파일 정보를 목록에 추가
function editor_insert_uploaded_file(upload_target_srl, file_srl, filename, file_size, disp_file_size, uploaded_filename, sid) {
    var obj = xGetElementById('uploaded_file_list_'+upload_target_srl);
    if(filename.length>15) filename = filename.substr(0,15)+'...';
    var text = filename+' ('+disp_file_size+')';
    var opt_obj = new Option(text, file_srl, true, true);
    obj.options[obj.options.length] = opt_obj;

    var file_obj = {file_srl:file_srl, filename:filename, file_size:file_size, uploaded_filename:uploaded_filename, sid:sid}
    uploaded_files[file_srl] = file_obj;

    editor_preview(obj, upload_target_srl);
    _progress_start = false;
}

// 파일 목록창에서 클릭 되었을 경우 미리 보기
function editor_preview(sel_obj, upload_target_srl) {
    var preview_obj = xGetElementById('preview_uploaded_'+upload_target_srl);
    if(!sel_obj.options.length) {
        xInnerHtml(preview_obj, '');
        return;
    }

    var file_srl = sel_obj.options[sel_obj.selectedIndex].value;
    var obj = uploaded_files[file_srl];
    if(typeof(obj)=='undefined'||!obj) return;
    var uploaded_filename = obj.uploaded_filename;

    if(!uploaded_filename) {
        xInnerHtml(preview_obj, '');
        return;
    }

    var html = "";

    // 플래쉬 동영상의 경우
    if(/\.flv$/i.test(uploaded_filename)) {
        html = "<EMBED src=\"./common/tpl/images/flvplayer.swf?autoStart=false&file="+uploaded_filename+"\" width=\"110\" height=\"110\" type=\"application/x-shockwave-flash\"></EMBED>";

    // 플래쉬 파일의 경우
    } else if(/\.swf$/i.test(uploaded_filename)) {
        html = "<EMBED src=\""+uploaded_filename+"\" width=\"110\" height=\"110\" type=\"application/x-shockwave-flash\"></EMBED>";

    // wmv, avi, mpg, mpeg등의 동영상 파일의 경우
    } else if(/\.(wmv|avi|mpg|mpeg|asx|asf|mp3)$/i.test(uploaded_filename)) {
        html = "<EMBED src=\""+uploaded_filename+"\" width=\"110\" height=\"110\" autostart=\"true\" Showcontrols=\"0\"></EMBED>";

    // 이미지 파일의 경우
    } else if(/\.(jpg|jpeg|png|gif)$/i.test(uploaded_filename)) {
        html = "<img src=\""+uploaded_filename+"\" border=\"0\" width=\"110\" height=\"110\" />";

    }
    xInnerHtml(preview_obj, html);
}

// 업로드된 파일 삭제
function editor_remove_file(upload_target_srl) {
    var obj = xGetElementById('uploaded_file_list_'+upload_target_srl);
    if(obj.options.length<1) return;

    // 삭제하려는 파일의 정보를 챙김;;
    var fo_obj = obj;
    while(fo_obj.nodeName != 'FORM') { fo_obj = fo_obj.parentNode; }
    var mid = fo_obj.mid.value;

    // 빈 iframe 구함
    var iframe_obj = xGetElementById('tmp_upload_iframe');
    if(!iframe_obj) return;

    for(var i=0;i<obj.options.length;i++) {
        var sel_obj = obj.options[i];
        if(!sel_obj.selected) continue;

        var file_srl = sel_obj.value;
        if(!file_srl) continue;

        var url = request_uri+"/?act=procFileDelete&upload_target_srl="+upload_target_srl+"&file_srl="+file_srl+"&mid="+current_url.getQuery('mid');

        iframe_obj.contentWindow.document.location.href=url;

    }

    var preview_obj = xGetElementById('preview_uploaded_'+upload_target_srl);
    xInnerHtml(preview_obj, "");
}

// 업로드 목록의 선택된 파일을 내용에 추가
function editor_insert_file(upload_target_srl) {
    var obj = xGetElementById('uploaded_file_list_'+upload_target_srl);
    if(obj.options.length<1) return;

    var iframe_obj = editorGetIFrame(upload_target_srl);
    editorFocus(upload_target_srl);

    var fo_obj = obj;
    while(fo_obj.nodeName != 'FORM') { fo_obj = fo_obj.parentNode; }

    // 다중 선택된 경우를 위해 loop
    for(var i=0;i<obj.options.length;i++) {
        var sel_obj = obj.options[i];
        if(!sel_obj.selected) continue;

        var file_srl = sel_obj.value;
        if(!file_srl) continue;

        var file_obj = uploaded_files[file_srl];
        var filename = file_obj.filename;
        var sid = file_obj.sid;
        var url = file_obj.uploaded_filename.replace(request_uri,'');

        // 바로 링크 가능한 파일의 경우 (이미지, 플래쉬, 동영상 등..)
        if(url.indexOf("binaries")==-1) {
            // 이미지 파일의 경우 image_link 컴포넌트 열결
            if(/\.(jpg|jpeg|png|gif)$/i.test(url)) {
                var text = "<img editor_component=\"image_link\" src=\""+url+"\" alt=\""+file_obj.filename+"\" />";
                editorReplaceHTML(iframe_obj, text);
            // 이미지외의 경우는 multimedia_link 컴포넌트 연결
            } else {
                var text = "<img src=\"./common/tpl/images/blank.gif\" editor_component=\"multimedia_link\" multimedia_src=\""+url+"\" width=\"400\" height=\"320\" style=\"display:block;width:400px;height:320px;border:2px dotted #4371B9;background:url(./modules/editor/components/multimedia_link/tpl/multimedia_link_component.gif) no-repeat center;\" auto_start=\"false\" alt=\"\" />";
                editorReplaceHTML(iframe_obj, text);
            }

            // binary파일의 경우 url_link 컴포넌트 연결 
        } else {
            var mid = fo_obj.mid.value;
            var url = request_uri+"/?module=file&amp;act=procFileDownload&amp;file_srl="+file_srl+"&amp;sid="+sid;
            var text = "<a href=\""+url+"\">"+filename+"</a><br />\n";
            editorReplaceHTML(iframe_obj, text);
        } 
    }
}
