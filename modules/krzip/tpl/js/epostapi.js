/* Copyright (C) NAVER <http://www.navercorp.com> */

(function ($) {
	"use strict";

	$.fn.Krzip = function (order, data) {
		var $this = $(this);

		var values = {
			postcode      : $this.find(".krzip-hidden-postcode"),
			roadAddress   : $this.find(".krzip-hidden-roadAddress"),
			jibunAddress  : $this.find(".krzip-hidden-jibunAddress"),
			detailAddress : $this.find(".krzip-hidden-detailAddress"),
			extraAddress  : $this.find(".krzip-hidden-extraAddress")
		};

		var ui = {
			postcode      : $this.find(".krzip-postcode"),
			roadAddress   : $this.find(".krzip-roadAddress"),
			jibunAddress  : $this.find(".krzip-jibunAddress"),
			detailAddress : $this.find(".krzip-detailAddress"),
			extraAddress  : $this.find(".krzip-extraAddress"),
			search        : $this.find(".krzip-search")
		};

		var krzip = $this.data("krzip");
		if(!krzip) {
			krzip = {
				open: function (query) {
					var request_url = "./"
						.setQuery("module", "krzip")
						.setQuery("act", "dispKrzipSearchForm")
						.setQuery("query", query);
					popopen(request_url, $this.selector);
				}
			};

			/* 상세 주소 저장 이벤트 등록 */
			var i, val, key = ["postcode", "roadAddress", "jibunAddress", "detailAddress", "extraAddress"];
			for(i = 0; i < key.length; i++) {
				val = key[i];
				ui[val].data("linked", val).on("change", function (e) {
					var $this = $(this);
					values[$this.data("linked")].val($this.val());
				});
			}

			/* 검색 이벤트 등록 */
			key = ["postcode", "roadAddress", "jibunAddress", "extraAddress", "search"];
			for(i = 0; i < key.length; i++) {
				val = key[i];
				ui[val].on("click", function (e) {
					var query = krzip.query;
					krzip.open(query);
				});
			}
		}
		else if(order === "query" && data) {
			krzip.query = data.query;
			ui.postcode.val(data[0]).trigger("change");
			ui.roadAddress.val(data[1]).trigger("change");
			ui.jibunAddress.val(data[2]).trigger("change");
			ui.extraAddress.val(data[4]).trigger("change");
			ui.detailAddress.trigger("focus");
		}

		/* 인스턴스 저장 */
		$this.data("krzip", krzip);
	};
})(jQuery);

/* End of file epostapi.js */
/* Location: ./modules/krzip/tpl/js/epostapi.js */
