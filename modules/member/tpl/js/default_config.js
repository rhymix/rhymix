jQuery(function($){
	$('.__sync').click(function (){
		exec_xml(
			'importer', // module
			'procImporterAdminSync', // act
			null,
			function(ret){if(ret && (!ret.error || ret.error == '0'))alert(ret.message);}, // callback
			resp = ['error','message'] // response tags
		);
	});
});
