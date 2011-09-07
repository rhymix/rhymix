function getEditorSkinColorList(skin_name,selected_colorset,type,testid){
    if(skin_name.length>0){
        type = type || 'document';
        var response_tags = new Array('error','message','colorset');
        exec_xml('editor','dispEditorSkinColorset',{skin:skin_name},resultGetEditorSkinColorList,response_tags,{'selected_colorset':selected_colorset,'type':type,'testid':testid});
    }
}

function resultGetEditorSkinColorList(ret_obj,response_tags, params) {
    var selectbox = null;
	jQuery(function($){
		selectbox = jQuery("#"+params.testid).next('label').next('select');
		selectbox.html('');
		
		if(params.type == 'document'){
			$("select[name=sel_editor_colorset]").css('display','none');				
			$("select[name=sel_editor_colorset]").removeAttr('name');			
			selectbox.attr('name','sel_editor_colorset');			
		}else{
			$("select[name=sel_comment_editor_colorset]").css('display','none');				
			$("select[name=sel_comment_editor_colorset]").removeAttr('name');			
			selectbox.attr('name','sel_comment_editor_colorset');			
		}	

		if(ret_obj['error'] == 0 && ret_obj.colorset){	
			var it = new Array();
			
			var items = ret_obj['colorset']['item'];	
			if(typeof(items[0]) == 'undefined'){
				it[0] = items;
			}else{
				it = items;
			}			
			var selectAttr = "";
			for(var i=0;i<it.length;i++){
				selectbox.append($('<option value="'+it[i].name+'" >'+it[i].title+'</option>'));				
			}
			selectbox.css('display','');
		}else{
			selectbox.css('display','none');
			selectbox.innerHTML="";
		}
	});
}
