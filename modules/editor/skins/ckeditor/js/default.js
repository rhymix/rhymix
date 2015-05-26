//Insert uploaded file to editor
function ckInsertUploadedFile(editorSequence){
	var temp_code='';

	var settings = uploaderSettings[editorSequence];
	var fileListAreaID = settings["fileListAreaID"];
	var fileListObj = get_by_id(fileListAreaID);
	if(!fileListObj) return;

	if(editorMode[editorSequence]=='preview') return;

	for(var i=0;i<fileListObj.options.length;i++) {
		if(!fileListObj.options[i].selected) continue;
		var file_srl = fileListObj.options[i].value;
		if(!file_srl) continue;

		var file = uploadedFiles[file_srl];

		if(file.direct_download == 'Y') {
			if(/\.(jpg|jpeg|png|gif)$/i.test(file.download_url)) {
				if(loaded_images[file_srl]) {
					var obj = loaded_images[file_srl];
				}
				else {
					var obj = new Image();
					obj.src = file.download_url;
				}
				temp_code += "<img src=\""+file.download_url+"\" alt=\""+file.source_filename+"\"";
				if(obj.complete == true) { temp_code += " width=\""+obj.width+"\" height=\""+obj.height+"\""; }
				temp_code += " />\r\n";
			} else {
				temp_code="<img src=\"common/img/blank.gif\" editor_component=\"multimedia_link\" multimedia_src=\""+file.download_url+"\" width=\"400\" height=\"320\" style=\"display:block;width:400px;height:320px;border:2px dotted #4371B9;background:url(./modules/editor/components/multimedia_link/tpl/multimedia_link_component.gif) no-repeat center;\" auto_start=\"false\" alt=\"\" />";
			}

		} else {
			temp_code="<a href=\""+file.download_url+"\">"+file.source_filename+"</a>\n";
		}
	}
	cked_instance = 'ckeditor_instance_'+editorSequence;
	CKEDITOR.instances[cked_instance].insertHtml(temp_code);
}
