/**
 * @brief 화면내에서 상위 영역보다 이미지가 크면 리사이즈를 하고 클릭시 원본을 보여줄수 있도록 변경
 **/
(function($){
	
var xScreen = null;

// 슬라이드를 위한 블랙 스크린을 만들거나 반환하는 함수
function getScreen() {
	var body    = $(document.body);
	var controls, imgframe, closebtn, prevbtn, nextbtn;
	
	// 스크린이 없으면 스크린을 만든다.
	if (!xScreen) {
		// 검은 스크린
		xScreen = $("<div>")
			.attr("id","xe_gallery_screen")
			.css({
				position:"absolute",
				display:"none",
				backgroundColor:"black",
				zIndex:500,
				opacity:0.5
			});
			
		// 이미지를 보여주고 컨트롤 버튼을 다룰 레이어
		controls = $("<div>")
			.attr("id","xe_gallery_controls")
			.css({
				position:"absolute",
				display:"none",
				overflow:"hidden",
				zIndex:510
			});
			
		// 닫기 버튼
		closebtn = $("<img>")
			.attr("id", "xe_gallery_closebtn")
			.attr("src", request_uri+"addons/resize_image/iconClose.png")
			.css({
				top : "10px"
			})
			.click(function(){xScreen.xeHide()})
			.appendTo(controls);
			
		// 이전 버튼
		prevbtn = $("<img>")
			.attr("id", "xe_gallery_prevbtn")
			.attr("src", request_uri+"addons/resize_image/iconLeft.png")
			.css("left","10px")
			.click(function(){xScreen.xePrev()})
			.appendTo(controls);
		
		// 다음 버튼
		nextbtn = $("<img>")
			.attr("id", "xe_gallery_nextbtn")
			.attr("src", request_uri+"addons/resize_image/iconRight.png")
			.css("right","10px")
			.click(function(){xScreen.xeNext()})
			.appendTo(controls);
			
		// 버튼 공통 속성
		controls.find("img")
			.attr({
				width  : 60,
				height : 60,
				className : "iePngFix"
			})
			.css({
				position : "absolute",
				width : "60px",
				height : "60px",
				zIndex : 530,
				cursor : "pointer"
			});
			
		// 이미지 홀더
		imgframe = $("<img>")
			.attr("id", "xe_gallery_holder")
			.css("border", "7px solid white")
			.css("zIndex", 520)
			.appendTo(controls).draggable();

		body.append(xScreen).append(controls);
		
		// xScreen 객체를 확장한다.
		xScreen.xeShow = function() {
			var clientWidth  = $(window).width();
			var clientHeight = $(window).height();
			
			$("#xe_gallery_controls,#xe_gallery_screen").css({
				display:"block",
				width  : clientWidth + "px",
				height : clientHeight + "px",
				left   : $(document).scrollLeft(),
				top    : $(document).scrollTop()
			});

			closebtn.css("left", Math.round((clientWidth-60)/2) + "px");

			$("#xe_gallery_prevbtn,#xe_gallery_nextbtn").css("top", Math.round( (clientHeight-60)/2 ) + "px");

			this.xeMove(0);
		};
		xScreen.xeHide = function(event) {
			xScreen.css("display","none");
			controls.css("display","none");
		};
		xScreen.xePrev = function() {
			this.xeMove(-1);
		};
		xScreen.xeNext = function() {
			this.xeMove(1);
		};
		xScreen.xeMove = function(val) {
			var clientWidth  = $(window).width();
			var clientHeight = $(window).height();
			
			this.index += val;

			prevbtn.css("visibility", (this.index>0)?"visible":"hidden");
			nextbtn.css("visibility", (this.index<this.list.size()-1)?"visible":"hidden");
			
			imgframe.attr("src", this.list.eq(this.index).attr("src"))
				.css({
					left : Math.round( Math.max( (clientWidth-imgframe.width()-14)/2, 0 ) ) + "px",
					top  : Math.round( Math.max( (clientHeight-imgframe.height()-14)/2, 0 ) ) + "px"
				});
		};
		
		// 스크린을 닫는 상황
		$(document).scroll(xScreen.xeHide);
		$(document).keydown(xScreen.xeHide);
		$(window).resize(xScreen.xeHide);
	} else {
		controls = $("#xe_gallery_controls");
		imgframe = $("#xe_gallery_holder");
		closebtn = $("#xe_gallery_closebtn");
		prevbtn  = $("#xe_gallery_prevbtn");
		nextbtn  = $("#xe_gallery_nextbtn");
	}
	
	return xScreen;
}

// 이미지 슬라이드를 보는 함수
function slideshow(event) {
	var container  = $(this).parents(".xe_content");
	var imglist    = container.find("img[rel=xe_gallery]");
	var currentIdx = $.inArray($(this).get(0), imglist.get());
	var xScreen    = getScreen();
	
	// 스크린을 보여주고
	xScreen.list  = imglist;
	xScreen.index = currentIdx;
	xScreen.xeShow();
}

// 이미지를 리사이즈 하는 함수
function resize(event) {
	var img = event.data[0];
	var img_width  = parseInt(event.data[1]);
	var img_height = parseInt(event.data[2]);
	var img_src    = event.data[3];
	var dummy = $("<div>").css({height:"1px",overflow:"hidden",opacity:0,display:"block"});
	var newWidth = -1;
	
	// 더미 객체를 넣어서 너비 구하기
	img.before(dummy);
	newWidth = dummy.innerWidth();
	dummy.remove();

	// 리사이즈 및 경로 지정
	if (img_width  <= 0) img_width  = $(this).attr("width");
	if (img_height <= 0) img_height = $(this).attr("height");
	if (img_width > newWidth) {
		img_height = newWidth * img_height/img_width;
		img_width  = newWidth;
	}
	img.attr({width:img_width,height:img_height,src:img_src});
	
	// 링크가 설정되어 있거나 onclick 이벤트가 부여되어 있으면 원본 보기를 하지 않음
	if ( img.parent("a").size() || img.attr("onclick") ) return;
	
	// 스타일 설정
	img.css("cursor", "pointer");
	
	// 클릭하면 슬라이드쇼 시작
	img.click(slideshow);
}

$(document).ready(function(){
	var regx_skip   = /(?:modules|addons|classes|common|layouts|libs|widgets)/i;
	var regx_parent = /(?:document|comment)_[0-9]+_[0-9]+/i;

	// 이미지 목록을 가져와서 리사이즈
	$(".xe_content img").each(function(){
		var img = $(this);
		var src = img.attr("src");
		var width  = img.attr("width");
		var height = img.attr("height");
		
		// XE 내부 프로그램 또는 스킨의 이미지라면 이미지 리사이즈를 하지 않음
		if ( regx_skip.test(src) ) return;
		
		// 커스텀 속성 추가
		img.attr("rel", "xe_gallery");
		
		// 크기를 줄인다.
		img.attr({src:"about:blank", width:16,height:16});
		
		// 이미지를 다 읽어들이면 리사이즈
		$("<img>").bind("load", [img, width, height, src], resize).attr("src", src);
	});
});

})(jQuery);