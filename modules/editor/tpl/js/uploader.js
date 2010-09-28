/**
 * @author NHN (developers@xpressengine.com)
 * @version 0.1.1
 * @brief 파일 업로드 관련
 **/
var uploadedFiles = new Array();
var uploaderSettings = new Array();
var loaded_images = new Array();
var swfUploadObjs = new Array();
var uploadSettingObj = new Array();
var uploadAutosaveChecker = false;

/**
 * 업로드를 하기 위한 준비 시작
 * 이 함수는 editor.html 에서 파일 업로드 가능할 경우 호출됨
 **/
// window.load 이벤트일 경우 && 문서 번호가 가상의 번호가 아니면 기존에 저장되어 있을지도 모르는 파일 목록을 가져옴
function editorUploadInit(obj, exe) {
    if(typeof(obj["editorSequence"])=="undefined") return;
    if(typeof(obj["sessionName"])=="undefined") obj["sessionName"]= "PHPSESSID";
    if(typeof(obj["allowedFileSize"])=="undefined") obj["allowedFileSize"]= 2*1024*1024;
    if(typeof(obj["allowedFileTypes"])=="undefined") obj["allowedFileTypes"]= "*.*";
    if(typeof(obj["allowedFileTypesDescription"])=="undefined") obj["allowedFileTypesDescription"]= "All Files";
    if(typeof(obj["replaceButtonID"])=="undefined") obj["replaceButtonID"] = "swfUploadButton"+obj["editorSequence"];
    if(typeof(obj["insertedFiles"])=="undefined") obj["insertedFiles"] = 0;
    if(exe) XEUploaderStart(obj);
    if(!exe) xAddEventListener(window,"load",function() { XEUploaderStart(obj) });
    uploadSettingObj[obj["editorSequence"]] = obj;
}

// 파일 업로드를 위한 기본 준비를 함
function XEUploaderStart(obj) {
    try { document.execCommand('BackgroundImageCache',false,true); } catch(e) { }

    var btnObj = xGetElementById(obj["replaceButtonID"]);
    var btnWidth = xWidth(btnObj);
    var btnHeight = xHeight(btnObj);
    btnObj.style.position = "relative";

    var dummy = xCreateElement("span");
    dummy.id = "dummy"+obj["replaceButtonID"];
    btnObj.appendChild(dummy);

    var settings = {
        flash_url : request_uri+"modules/editor/tpl/images/SWFUpload.swf",
        upload_url: request_uri.replace(/^https/i,'http'),
        post_params: {
            "mid" : current_mid,
            "act" : "procFileUpload",
            "editor_sequence" : obj["editorSequence"],
            "uploadTargetSrl" : editorRelKeys[obj["editorSequence"]]["primary"].value
        },
        file_size_limit : parseInt(parseInt(obj["allowedFileSize"],10)/1024,10),
        file_queue_limit : 0,
        file_upload_limit : 0,
        file_types : obj["allowedFileTypes"],
        file_types_description : obj["allowedFileTypesDescription"],
        custom_settings : {
            progressTarget : null,
            cancelButtonId : null
        },
        debug: false,

        // Button settings
        button_window_mode: 'transparent',
        button_placeholder_id: dummy.id,
        button_text: null,
        button_image_url: "",
        button_width: btnWidth,
        button_height: btnHeight,
        button_text_style: null,
        button_text_left_padding: 0,
        button_text_top_padding: 0,
        button_cursor:-2,

        // The event handler functions are defined in handlers.js
        file_queued_handler : fileQueued,
        file_queue_error_handler : fileQueueError,
        file_dialog_complete_handler : fileDialogComplete,
        upload_start_handler : uploadStart,
        upload_progress_handler : uploadProgress,
        upload_error_handler : uploadError,
        upload_success_handler : uploadSuccess,
        upload_complete_handler : uploadComplete,
        queue_complete_handler :queueComplete
    };
    if(typeof(xeVid)!='undefined') settings["post_params"]["vid"] = xeVid;
    settings["post_params"][obj["sessionName"]] = xGetCookie(obj["sessionName"]);
    settings["editorSequence"] = obj["editorSequence"];
    settings["uploadTargetSrl"] = editorRelKeys[obj["editorSequence"]]["primary"].value;
    settings["fileListAreaID"] = obj["fileListAreaID"];
    settings["previewAreaID"] = obj["previewAreaID"];
    settings["uploaderStatusID"] = obj["uploaderStatusID"];

    uploaderSettings[obj["editorSequence"]] = settings;

    var swfu = new SWFUpload(settings);
    var swfObj = xGetElementById(swfu.movieName);
    swfUploadObjs[obj["editorSequence"]] = swfu.movieName;
    if(!swfObj) return;

    swfObj.style.display = "block";
    swfObj.style.cursor = "pointer";
    swfObj.style.position = "absolute";
    swfObj.style.left = 0;
    swfObj.style.top = "-3px";
    swfObj.style.width = btnWidth+"px";
    swfObj.style.height = btnHeight+"px";

    if(obj["insertedFiles"]>0 || editorRelKeys[obj["editorSequence"]]["primary"].value > 0) reloadFileList(settings);
}

function fileQueued(file) {
}

function fileQueueError(file, errorCode, message) {
    try {
        switch(errorCode) {
            case SWFUpload.QUEUE_ERROR.QUEUE_LIMIT_EXCEEDED :
                alert("You have attempted to queue too many files.\n" + (message === 0 ? "You have reached the upload limit." : "You may select " + (message > 1 ? "up to " + message + " files." : "one file.")));
                break;
            case SWFUpload.QUEUE_ERROR.FILE_EXCEEDS_SIZE_LIMIT:
                alert("Error Code: File too big, File name: " + file.name + ", File size: " + file.size + ", Message: " + message);
                break;
            case SWFUpload.QUEUE_ERROR.ZERO_BYTE_FILE:
                alert("Error Code: Zero byte file, File name: " + file.name + ", File size: " + file.size + ", Message: " + message);
                break;
            case SWFUpload.QUEUE_ERROR.INVALID_FILETYPE:
                alert("Error Code: Invalid File Type, File name: " + file.name + ", File size: " + file.size + ", Message: " + message);
                break;
            default:
                alert("Error Code: " + errorCode + ", File name: " + file.name + ", File size: " + file.size + ", Message: " + message);
                break;
        }
    } catch(ex) {
        this.debug(ex);
    }
}

function fileDialogComplete(numFilesSelected, numFilesQueued) {
    try {
        this.startUpload();
    } catch (ex)  {
        this.debug(ex);
    }
}

function uploadStart(file) {
    return true;
}

function uploadProgress(file, bytesLoaded, bytesTotal) {
    try {
        var obj = xGetElementById(this.settings["fileListAreaID"]);

        var percent = Math.ceil((bytesLoaded / bytesTotal) * 100);
        var filename = file.name;
        if(filename.length>20) filename = filename.substr(0,20)+'...';

        var text = filename + ' ('+percent+'%)';
        if(!obj.options.length || obj.options[obj.options.length-1].value != file.id) {
            var opt_obj = new Option(text, file.id, true, true);
            obj.options[obj.options.length] = opt_obj;
        } else {
            obj.options[obj.options.length-1].text = text;
        }
    } catch (ex)  {
        this.debug(ex);
    }
}

function uploadSuccess(file, serverData) {
    try {
        if(this.getStats().files_queued !== 0) this.startUpload();
    } catch (ex)  {
        this.debug(ex);
    }
}

function uploadError(file, errorCode, message) {
    try {
        switch (errorCode) {
        case SWFUpload.UPLOAD_ERROR.HTTP_ERROR:
            alert("Error Code: HTTP Error, File name: " + file.name + ", Message: " + message);
            break;
        case SWFUpload.UPLOAD_ERROR.UPLOAD_FAILED:
            alert("Error Code: Upload Failed, File name: " + file.name + ", File size: " + file.size + ", Message: " + message);
            break;
        case SWFUpload.UPLOAD_ERROR.IO_ERROR:
            alert("Error Code: IO Error, File name: " + file.name + ", Message: " + message);
            break;
        case SWFUpload.UPLOAD_ERROR.SECURITY_ERROR:
            alert("Error Code: Security Error, File name: " + file.name + ", Message: " + message);
            break;
        case SWFUpload.UPLOAD_ERROR.UPLOAD_LIMIT_EXCEEDED:
            alert("Error Code: Upload Limit Exceeded, File name: " + file.name + ", File size: " + file.size + ", Message: " + message);
            break;
        case SWFUpload.UPLOAD_ERROR.FILE_VALIDATION_FAILED:
            alert("Error Code: File Validation Failed, File name: " + file.name + ", File size: " + file.size + ", Message: " + message);
            break;
        case SWFUpload.UPLOAD_ERROR.FILE_CANCELLED:
            // If there aren't any files left (they were all cancelled) disable the cancel button
            if (this.getStats().files_queued === 0) {
                document.getElementById(this.customSettings.cancelButtonId).disabled = true;
            }
            break;
        case SWFUpload.UPLOAD_ERROR.UPLOAD_STOPPED:
            break;
        default:
            alert("Error Code: " + errorCode + ", File name: " + file.name + ", File size: " + file.size + ", Message: " + message);
            break;
        }
    } catch (ex) {
        this.debug(ex);
    }
}

function uploadComplete(file) {
    try {
        var fileListAreaID = this.settings["fileListAreaID"];
        var uploadTargetSrl = this.settings["uploadTargetSrl"];
        reloadFileList(this.settings);
    } catch(e) {
        this.debug(ex);
    }
}

function queueComplete(numFilesUploaded) {
}

function reloadFileList(settings) {
    var params = new Array();
    params["file_list_area_id"] = settings["fileListAreaID"];
    params["editor_sequence"] = settings["editorSequence"];
    params["upload_target_srl"] = settings["uploadTargetSrl"];
    params["mid"] = current_mid;
    var response_tags = new Array("error","message","files","upload_status","upload_target_srl","editor_sequence","left_size");
    exec_xml("file","getFileList", params, completeReloadFileList, response_tags, settings);
}

function completeReloadFileList(ret_obj, response_tags, settings) {
    var upload_target_srl = ret_obj['upload_target_srl'];
    var editor_sequence = ret_obj['editor_sequence'];
    var upload_status = ret_obj['upload_status'];
    var files = ret_obj['files'];
    var file_list_area_id = settings["fileListAreaID"];
    var listObj = xGetElementById(file_list_area_id);
    var left_size = parseInt(parseInt(ret_obj["left_size"],10)/1024,10);
    while(listObj.options.length) {
        listObj.remove(0);
    }

    if(upload_target_srl && upload_target_srl != 0) {
        // 자동 저장된 문서와 target_srl이 다르게 될 경우 다시 자동 저장.
        if(editorRelKeys[editor_sequence]["primary"].value != upload_target_srl) {
            editorRelKeys[editor_sequence]["primary"].value = upload_target_srl;
            uploadAutosaveChecker = true;
            _editorAutoSave(true);
        }

        editorRelKeys[editor_sequence]["primary"].value = upload_target_srl;
        settings["uploadTargetSrl"] = upload_target_srl;
    }

    var statusObj = xGetElementById(settings["uploaderStatusID"]);
    if(statusObj) xInnerHtml(statusObj, upload_status);

    var previewObj = xGetElementById(settings["previewAreaID"]);
    if(previewObj) xInnerHtml(previewObj,"");

    if(files && typeof(files['item'])!='undefined') {
        var item = files['item'];
        if(typeof(item.length)=='undefined' || item.length<1) item = new Array(item);
        if(item.length) {
            for(var i=0;i<item.length;i++) {
                var file_srl = item[i].file_srl;
                item[i].previewAreaID = settings["previewAreaID"];
                uploadedFiles[file_srl] = item[i];
                var opt = new Option(item[i].source_filename+" ("+item[i].disp_file_size+")", file_srl, true, true);
                listObj.options[listObj.options.length] = opt;
                item[i].download_url = encodeURI(item[i].download_url);
                if(/\.(jpg|jpeg|png|gif)$/i.test(item[i].download_url)) {
                    var loadingImage = new Image();
                    loadingImage.src = item[i].download_url;
                    loaded_images[file_srl] = loadingImage;
                    item[i].download_url = item[i].download_url.replace(/&/g, "&amp;");
                }
            }
            previewFiles('', item[item.length-1].file_srl);
        }
    }

    // var swfu = SWFUpload.instances[swfUploadObjs[editor_sequence]].setFileSizeLimit(left_size);

    // 문서 강제 자동저장 1번만 사용 ( 첨부파일 target_srl로 자동 저장문서를 저장하기 위한 용도일 뿐 )
    if(typeof(_editorAutoSave) == 'function' && uploadAutosaveChecker == false) {
        uploadAutosaveChecker = true;
        _editorAutoSave(true);
    }

    xAddEventListener(listObj,'click',previewFiles);
}

function previewFiles(evt, given_file_srl) {
    if(!given_file_srl) {
    var e = new xEvent(evt);
    var obj = e.target;
    var selObj = null;
    if(obj.nodeName=="OPTION") selObj = obj.parentNode;
    else selObj = obj;
    if(selObj.nodeName != "SELECT") return;
    if(selObj.selectedIndex<0) return;
    obj = selObj.options[selObj.selectedIndex];

    var file_srl = obj.value;
    }
    else {
        var file_srl = given_file_srl;
    }
    if(!file_srl || typeof(uploadedFiles[file_srl])=="undefined") return;
    var file_info = uploadedFiles[file_srl];
    var previewAreaID = file_info.previewAreaID;
    var previewObj = xGetElementById("previewAreaID");
    if(!previewAreaID) return;
    xInnerHtml(previewAreaID,"&nbsp;");
    if(file_info.direct_download != "Y") {
        var html = "<img src=\""+request_uri+"./modules/editor/tpl/images/files.gif\" border=\"0\" width=\"100%\" height=\"100%\" />";
        xInnerHtml(previewAreaID, html);
        return;
    }

    var html = "";
    var uploaded_filename = file_info.download_url;

    // 플래쉬 동영상의 경우
    if(/\.flv$/i.test(uploaded_filename)) {
        html = "<embed src=\"./common/tpl/images/flvplayer.swf?autoStart=false&file="+uploaded_filename+"\" width=\"100%\" height=\"100%\" type=\"application/x-shockwave-flash\"></embed>";

    // 플래쉬 파일의 경우
    } else if(/\.swf$/i.test(uploaded_filename)) {
        html = "<embed src=\""+uploaded_filename+"\" width=\"100%\" height=\"100%\" type=\"application/x-shockwave-flash\"></embed>";

    // wmv, avi, mpg, mpeg등의 동영상 파일의 경우
    } else if(/\.(wmv|avi|mpg|mpeg|asx|asf|mp3)$/i.test(uploaded_filename)) {
        html = "<embed src=\""+uploaded_filename+"\" width=\"100%\" height=\"100%\" autostart=\"true\" Showcontrols=\"0\"></embed>";

    // 이미지 파일의 경우
    } else if(/\.(jpg|jpeg|png|gif)$/i.test(uploaded_filename)) {
        html = "<img src=\""+uploaded_filename+"\" border=\"0\" width=\"100%\" height=\"100%\" />";
    } else if(uploaded_filename) {
        html = "<img src=\""+request_uri+"/modules/editor/tpl/images/files.gif\" border=\"0\" width=\"100%\" height=\"100%\" />";
    }
    xInnerHtml(previewAreaID, html);
}

function removeUploadedFile(editorSequence) {
    var settings = uploaderSettings[editorSequence];
    var fileListAreaID = settings["fileListAreaID"];
    var fileListObj = xGetElementById(fileListAreaID);
    if(!fileListObj) return;

    if(fileListObj.selectedIndex<0) return;

    var file_srls = new Array();
    for(var i=0;i<fileListObj.options.length;i++) {
        if(!fileListObj.options[i].selected) continue;
        var file_srl = fileListObj.options[i].value;
        if(!file_srl) continue;
        file_srls[file_srls.length] = file_srl;
    }

    if(file_srls.length<1) return;

    var params = new Array();
    params["file_srls"]  = file_srls.join(',');
    params["editor_sequence"] = editorSequence;
    var response_tags = new Array("error","message");
    exec_xml("file","procFileDelete", params, function() { reloadFileList(settings); } );
}

function insertUploadedFile(editorSequence) {

    var settings = uploaderSettings[editorSequence];
    var fileListAreaID = settings["fileListAreaID"];
    var fileListObj = xGetElementById(fileListAreaID);
    if(!fileListObj) return;

    if(editorMode[editorSequence]=='preview') return;

    var text = new Array();
    for(var i=0;i<fileListObj.options.length;i++) {
        if(!fileListObj.options[i].selected) continue;
        var file_srl = fileListObj.options[i].value;
        if(!file_srl) continue;

        var file = uploadedFiles[file_srl];
        editorFocus(editorSequence);

        // 바로 링크 가능한 파일의 경우 (이미지, 플래쉬, 동영상 등..)
        if(file.direct_download == 'Y') {
            // 이미지 파일의 경우 image_link 컴포넌트 열결
            if(/\.(jpg|jpeg|png|gif)$/i.test(file.download_url)) {
                if(loaded_images[file_srl]) {
                    var obj = loaded_images[file_srl];
                }
                else {
                    var obj = new Image();
                    obj.src = file.download_url;
                }
                temp_code = '';
                temp_code += "<img src=\""+file.download_url+"\" alt=\""+file.source_filename+"\"";
                if(obj.complete == true) { temp_code += " width=\""+obj.width+"\" height=\""+obj.height+"\""; }
                temp_code += " />\r\n";
                text.push(temp_code);
            // 이미지외의 경우는 multimedia_link 컴포넌트 연결
            } else {
                text.push("<img src=\"common/tpl/images/blank.gif\" editor_component=\"multimedia_link\" multimedia_src=\""+file.download_url+"\" width=\"400\" height=\"320\" style=\"display:block;width:400px;height:320px;border:2px dotted #4371B9;background:url(./modules/editor/components/multimedia_link/tpl/multimedia_link_component.gif) no-repeat center;\" auto_start=\"false\" alt=\"\" />");
            }

        // binary파일의 경우 url_link 컴포넌트 연결
        } else {
            text.push("<a href=\""+file.download_url+"\">"+file.source_filename+"</a>\n");
        }
    }

    // html 모드
    if(editorMode[editorSequence]=='html'){
        if(text.length>0) xGetElementById('editor_textarea_'+editorSequence).value += text.join('');

    // 위지윅 모드
    }else{
        var iframe_obj = editorGetIFrame(editorSequence);
        if(!iframe_obj) return;
        if(text.length>0) editorReplaceHTML(iframe_obj, text.join(''));
    }
}
