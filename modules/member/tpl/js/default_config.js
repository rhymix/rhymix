jQuery(function($){

	$('input[name=enable_join]').on('change', function() {
		if ($('#enable_join_only_with_key').is(':checked')) {
			$('#enable_join_key').show();
		} else {
			$('#enable_join_key').hide();
		}
	});

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
