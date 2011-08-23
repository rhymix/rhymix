jQuery(function($){

$('li')
	.delegate('button._edit', 'click', function(){
		var $this = $(this);
		/*, site_srl = $this.data('site_srl');
		currentClickedSiteObject = $this;*/

		var formObj = $this.parents().find('form').first();

		// TODO : 모듈 목록을 찾아서 셀렉트 박스에 할당
		var params = new Array();
		var response_tags = ['error', 'message', 'lang_list', 'lang_name'];
		params['lang_name'] = formObj.find('input[name=lang_name]').val();

		exec_xml('module','getModuleAdminLangListByName',params, completeGetModuleList, response_tags);
	});
});

function completeGetModuleList(ret_obj, response_tags)
{
	var langName = ret_obj['lang_name'];
	var langList = ret_obj['lang_list']['item'];
	if(!jQuery.isArray(langList)) langList = [langList];
	var htmlListBuffer = '';

	for(var x in langList)
	{
		var objLang = langList[x];
		jQuery('#' + langName + '_' + objLang.lang_code).val(objLang.value);
	}
}
