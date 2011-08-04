/* NHN (developers@xpressengine.com) */
jQuery(function($){

$('form.siteMap')
	.find('li')
		.prepend('<button type="button" class="moveTo">Move to</button>')
		.append('<span class="vr"></span><span class="hr"></span>')
		.mouseover(function(){
			$(this).addClass('active');
			return false;
		})
		.mouseout(function(){
			$(this).removeClass('active');
			return false;
		})
		.mousedown(function(event){
			var $this, $clone, $target = $(event.target), $uls, $ghost, $last;

			if($target.is('a,input:text,textarea')) return;

			$this  = $(this);
			$clone = $this.clone(true).find('.side,input').remove().end();
			$uls   = $this.parentsUntil('form.siteMap').filter('ul');

			$ghost = $last = $('<ul class="lined" />');
			for(var i=1,c=$uls.length; i < c; i++) {
				$last = $last.append('<li><ul /></li>').find('>ul');
			}
			
			$last.append($clone);
			$ghost
				.css({
					backgroundColor : '#fff',
					position : 'absolute',
					opacity  : .5
				});
		
			return false;
		})
		.find('input:text')
			.each(function(i,input){
				var $this = $(this), id='sitemap-id-'+i;

				$this
					.attr('id', id)
					.css({width:0,opacity:0,overflow:'hidden'})
					.before('<label />')
						.prev('label')
						.attr('for', id)
						.text($this.val());
			})
		.end()
	.end()

$('<div id="dropzone-marker" />')
	.css({display:'none',position:'absolute',backgroundColor:'#000',opacity:0.7})
	.appendTo('body');

/*
$('.tgMap').click(function(){
	var t = $(this);
	t.parent('.siteMap').toggleClass('fold');
	if(t.parent('.siteMap').hasClass('fold')){
		t.text('펼치기').next('.lined').slideUp(200).next('.btnArea').hide();
	} else {
		t.text('접기').next('.lined').slideDown(200).next('.btnArea').show();
	}
	return false;
});
*/

});
