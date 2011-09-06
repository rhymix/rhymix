(function($){
	$('input[name=allow_outlink]').click(function(){
		if($(this).val() == 'Y'){
			$('._outLink').show();
		}else{
			$('._outLink').hide();
		}
	});
 }(jQuery))
