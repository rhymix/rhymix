jQuery(function($){

$('#theme,#skin')
	// all thumbnail area
	.find('>.thumbPreview')
		// thumbnail list
		.find('.a')
			.has('li.i:nth-child(2)')
				.after('<a href="#" class="prevToggle">Show</a>')
				.next('a.prevToggle')
					.click(function(){
						var $list = $(this).prev('.a');

						if($list.parent().hasClass('active')) {
							$list.trigger('hide.tp');
						} else {
							$list.trigger('show.tp');
						}

						return false;
					})
				.end()
			.end()
			.bind('show.tp', function(){
				$(this)
					.parent().addClass('active').end()
					.find('>.i:gt(0)')
						.slideDown(100, function(){
							var $this = $(this);

							if(!$this.prev('.checked').length) return;
						});
			})
			.bind('hide.tp', function(){
				$(this)
					.parent().removeClass('active').end()
					.find('>.i:gt(0)')
						.slideUp(100, function(){
							var $this = $(this);

							if(!$this.prev('.checked').length) return;
						});
			})
		.end()
		.find('.i:not(:first-child)').hide().end()
		.bind('paint.pr', function(){
			$(this)
				.find('.i')
					.removeClass('checked')
					.find('.checkIcon').remove().end()
				.end()
				.find('.i:has(>input:checked)')
					.addClass('checked')
					.each(function(){
						var $this = $(this);
						$this.parent().prepend($this);
					})
				.end()
				.find('.i.checked').show();
		})
		.trigger('paint.pr')
		.find('span.thumb')
			.attr('role', 'radio') // WAI-ARIA role
			.click(function(){
				var $radio = $(this).next('input:radio');

				$radio.is(':checked')?
					$radio.closest('.a').trigger('show.tp') :
					$radio.prop('checked', true).change();
			})
			.next('input:radio').change(function(){ $(this).closest('.a').trigger('paint.pr') }).end()
		.end()
	.end()
});
