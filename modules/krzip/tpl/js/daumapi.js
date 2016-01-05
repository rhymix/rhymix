/* Copyright (C) NAVER <http://www.navercorp.com> */

(function ($) {
	"use strict";

	$.fn.Krzip = function () {
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
			search        : $this.find(".krzip-search"),
			guide         : $this.find(".krzip-guide")
		};

		var krzip = new daum.Postcode({
			oncomplete: function (response) {
				var fullAddr = "", extraAddr = "";

				/* 도로명 주소를 선택했을 경우 */
				if(response.userSelectedType === "R") {
					fullAddr = response.roadAddress;
					/* 법정동명이 있을 경우 */
					if(response.bname !== "") {
						extraAddr += response.bname;
					}
					/* 건물명이 있을 경우 */
					if(response.buildingName !== "") {
						extraAddr += (extraAddr !== "" ? ", " + response.buildingName : response.buildingName);
					}
					if(extraAddr) {
						extraAddr = "(" + extraAddr + ")";
					}
				}
				/* 지번 주소를 선택했을 경우 */
				else {
					fullAddr = response.jibunAddress;
				}

				/* 우편번호 저장 */
				ui.postcode.val(response.zonecode).trigger("change");

				/* 도로명 주소 저장 */
				var roadAddr = (response.userSelectedType === "R" ? fullAddr : response.roadAddress);
				ui.roadAddress.val(roadAddr).trigger("change");

				/* 지번 주소 저장 */
				var jibunAddr = (response.userSelectedType === "R" ? response.jibunAddress : fullAddr);
				ui.jibunAddress.val(jibunAddr ? "(" + jibunAddr + ")" : jibunAddr).trigger("change");

				/* 부가 주소 저장 */
				ui.extraAddress.val(extraAddr).trigger("change");

				/* 예상 주소 저장 */
				ui.guide.hide().html("");
				if(response.autoRoadAddress) {
					var expRoadAddr = (response.autoRoadAddress + extraRoadAddr);
					ui.guide
						.html("(" + xe.lang.msg_krzip_road_address_expectation.replace("%s", expRoadAddr) + ")")
						.show();
				}
				else if(response.autoJibunAddress) {
					var expJibunAddr = response.autoJibunAddress;
					ui.guide
						.html("(" + xe.lang.msg_krzip_jibun_address_expectation.replace("%s", expJibunAddr) + ")")
						.show();
				}

				/* 상세 주소로 커서 이동 */
				ui.detailAddress.trigger("focus");
			}
		});

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
				krzip.open();
			});
		}
	};
})(jQuery);

/* End of file daumapi.js */
/* Location: ./modules/krzip/tpl/js/daumapi.js */
