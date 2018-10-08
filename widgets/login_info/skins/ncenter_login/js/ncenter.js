//<![CDATA[
(function ($) {
	$(function () {
		var n = $('#nc_container');
		$('.close', n).click(function () {
			setCookie('_ncenterlite_hide_id', '{$ncenterlite_latest_notify_id}', 1);
			n.hide().next('div').hide();
			return false;
		});
		$('.readall', n).click(function () {
			exec_xml('ncenterlite', 'procNcenterliteNotifyReadAll');
			$('.close', n).triggerHandler('click');
			return false;
		});
		$('a.notify', n).click(function () {
			$('.list', n).toggle();
			$('.readall', n).toggle();
			return false;
		});
		$(document).click(function (e) {
			var t = $(e.target);
			if (!t.is('#nc_container') && t.parents('#nc_container').length == 0) {
				if ($('.list', n).is(':visible')) {
					$('.list', n).hide();
					$('.readall', n).hide();
					return false;
				}
			}
		});

		var $listWrap = $('.list ul', n);
		var $btnMore = $('.more', n);
		$btnMore.click(function () {
			var page = $(this).data('page');
			var $item_html = $('<li><a><span class="msg"></span><span class="ago"></span></a></li>');
			var $profileImg = $('<img class="nc_profile_img" alt="" />');
			$.exec_json('ncenterlite.getMyNotifyListTpl', {'page': page}, function (ret) {
				if (!ret.list.data) return;

				for (var i in ret.list.data) {
					if (ret.list.data.hasOwnProperty(i)) {
						var item = ret.list.data[i];
						var $html = $item_html.clone();
						if (ret.useProfileImage == 'Y') {
							var $img = $profileImg.clone();
							if (!item.profileImage) item.profileImage = request_uri + 'modules/ncenterlite/skins/default/img/p.png';
							$img.attr('src', item.profileImage);
							$html.find('a').prepend($img);
						}

						$('span.msg', $html).html(item.text);
						$('span.ago', $html).html(item.ago);
						$('a', $html).attr('href', item.url);

						if (i == 0) $html.attr('id', 'ncenterlite_page_' + ret.list.page.cur_page);
						$listWrap.append($html);
					}
				}

				$listWrap.animate({scrollTop: (ret.list.page.cur_page - 1) * 265}, 800);

				if (ret['list'].page.total_page <= ret.list.page.cur_page) {
					$btnMore.remove();
				}
			}, ['list']);

			$(this).data('page', ++page);
			return false;
		});
	});
})(jQuery);
//]]>
