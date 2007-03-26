/**
 * @file  slide_gallery.js
 * @brief 이미지 이미지갤러리 쇼 스크립트
 * @author zero (zero@nzeo.com)
 **/

// 이미지갤러리쇼를 하기 위한 변수
var slide_gallery_images = new Array();
var thumbnail_zone_height = new Array();

// 이미지갤러리쇼 이미지 목록에 추가
function slide_gallery_add_image(srl, image_url) {
    if(!image_url) return;

    // 객체 생성
    var obj = {"srl":0, "thumbnail_url":null, "thumbnail":null, "image_url":null, "image":null}

    // slide_gallery_images에 이미지갤러리 쇼 고유번호에 해당하는 공간을 초기화
    if(typeof(slide_gallery_images[srl])=="undefined") slide_gallery_images[srl] = new Array();

    // 이미지갤러리쇼 고유번호를 세팅
    obj.srl = srl;
    obj.idx = slide_gallery_images[srl].length;

    // 원본 이미지를 미리 로딩
    obj.image = new Image();
    obj.image.src = image_url;
    if(!obj.image.width) return;

    // 썸네일 이미지를 미리 로딩
    obj.thumbnail = new Image();
    obj.thumbnail.src = image_url;
    obj.thumbnail.style.cursor = "pointer";
    obj.thumbnail.style.width = "60px";
    obj.thumbnail.style.height = "60px";
    obj.thumbnail.style.margin = "5px";
    obj.thumbnail.style.opacity = 0.5;
    obj.thumbnail.style.filter = "alpha(opacity=50)";

    // 썸네일 클릭시 메인 이미지로 바꾸어줌
    xAddEventListener(obj.thumbnail, "mousedown", function() { display_gallery_image(obj) });

    // 생성된 객체를 slide_gallery_images[이미지갤러리쇼 고유번호]에 추가
    slide_gallery_images[srl][slide_gallery_images[srl].length] = obj;
}

// 이미지갤러리쇼 시작
function start_slide_gallery() {

    // 등록된 모든 이미지 목록을 돌면서 thumbnail 목록을 만들어줌
    for(var srl in slide_gallery_images) {

        // 등록된 이미지가 없으면 pass~
        if(!slide_gallery_images[srl].length) continue;

        // 메인이미지가 나올 곳과 썸네일이 노출될 곳의 객체를 구함
        var zone_thumbnail = xGetElementById('zone_thumbnail_'+srl);

        // 썸네일 출력
        for(var i=0; i<slide_gallery_images[srl].length;i++) {
            zone_thumbnail.appendChild(slide_gallery_images[srl][i].thumbnail);
        }
        thumbnail_zone_height[srl] = xHeight(zone_thumbnail)+20;

        // 첫번째 이미지의 경우 큰 이미지 출력 시작 이미지 출력
        display_gallery_image(slide_gallery_images[srl][0],true);

        // 로딩 텍스트 없앰
        xGetElementById("slide_gallery_loading_text_"+srl).style.display = "none";
    }
}

// 메인 이미지 표시
function display_gallery_image(obj, is_first_display) {
    // 처음으로 요청되는지에 대한 검사
    if(typeof(is_first_display)=="undefined") is_first_display = false;

    // 썸네일 영역
    var zone_thumbnail = xGetElementById('zone_thumbnail_'+obj.srl);

    // 슬라이드 갤러리의 영역
    var zone = xGetElementById('zone_slide_gallery_' + obj.srl );

    // 슬라이드 갤러리 영역의 정확한 크기를 위해서 border값 검사 (border는 조절 가능하기에)
    var borderTop = parseInt(zone.style.borderTopWidth.replace(/px$/,''),10);
    var borderLeft = parseInt(zone.style.borderLeftWidth.replace(/px$/,''),10);
    var borderRight = parseInt(zone.style.borderRightWidth.replace(/px$/,''),10);
    var borderBottom = parseInt(zone.style.borderBottomWidth.replace(/px$/,''),10);

    // 갤러리 영역의 가로 길이 구함
    var zone_width = xWidth(zone)-borderLeft-borderRight;

    // 이미지 크기가 갤러리 영역보다 클 경우를 계산하기 위한 비율 변수 설정
    var resize_scale = 1;

    // 이미지의 크기 구함
    var image_width = obj.image.width;
    var image_height = obj.image.height;

    // 이미지갤러리 쇼 박스보다 큰 이미지는 크기를 줄여서 출력 (ersize_scale 조절)
    if(image_width>(zone_width-20)) {
        resize_scale = (zone_width-20)/image_width;
        image_width = parseInt(image_width*resize_scale,10);
        image_height = parseInt(image_height*resize_scale,10);
    }
    var x = parseInt((zone_width - image_width)/2,10);

    // 이미지 표시 
    var target_image = xGetElementById("slide_gallery_main_image_"+obj.srl);
    target_image.style.display = "none";

    target_image.src = obj.image.src;
    target_image.srl = obj.srl;
    target_image.idx = obj.idx;
    target_image.style.opacity = 1;
    target_image.style.filter = "alpha(opacity=100)";
    target_image.start_opacity = 0;
    xWidth(target_image, image_width);
    xHeight(target_image, image_height);

    target_image.style.margin = "0px;";
    target_image.style.marginLeft = x+"px";

    if(image_height<150) {
      target_image.style.marginTop = (150-image_height)+"px";
      target_image.style.marginBottom = (150-image_height)+"px";
    } else {
      target_image.style.marginTop = "10px";
      target_image.style.marginBottom = "10px";
    }

    if(resize_scale!=1) {
        xAddEventListener(target_image, 'dblclick', showOriginalImage);
    } else {
        xRemoveEventListener(target_image, 'dblclick', showOriginalImage);
    }

    // resize_scale이 1이 아니면, 즉 리사이즈 되었다면 해당 이미지 클릭시 원본을 새창으로 띄워줌
    var next_idx = obj.idx+1;
    if(slide_gallery_images[obj.srl].length<=next_idx) next_idx = 0;

    target_image.style.cursor = 'pointer';

    target_image.style.display = "block";

    // srl의 모든 썸네일의 투명도 조절
    for(var i=0; i<slide_gallery_images[obj.srl].length;i++) {
        if(i==obj.idx) {
            slide_gallery_images[obj.srl][i].thumbnail.style.opacity = 1;
            slide_gallery_images[obj.srl][i].thumbnail.style.filter = "alpha(opacity=100)";
        } else {
            slide_gallery_images[obj.srl][i].thumbnail.style.opacity = 0.5;
            slide_gallery_images[obj.srl][i].thumbnail.style.filter = "alpha(opacity=50)";
        }
    }

    // 네이게이션 영역의 숫자 변경
    var zone_navigator = xGetElementById("zone_gallery_navigator_status_"+obj.srl);
    var html = (obj.idx+1) + " / " + slide_gallery_images[obj.srl].length;
    xInnerHtml(zone_navigator, html);
}

// 이전 보기
function gallery_view_prev(srl) {
    var target_image = xGetElementById("slide_gallery_main_image_"+srl);
    var idx = target_image.idx;
    var max_length = slide_gallery_images[srl].length;
    idx--;
    if(idx<0) idx = max_length-1;
    display_gallery_image(slide_gallery_images[srl][idx]);
}

// 다음 보기
function gallery_view_next(srl) {
    var target_image = xGetElementById("slide_gallery_main_image_"+srl);
    var idx = target_image.idx;
    var max_length = slide_gallery_images[srl].length;
    idx++;
    if(idx>max_length-1) idx = 0;
    display_gallery_image(slide_gallery_images[srl][idx]);
}

// 썸네일 보기
function gallery_view_thumbnail(srl) {
    var thumbnail_zone = xGetElementById("zone_thumbnail_"+srl);
    if(thumbnail_zone.style.display == "none") thumbnail_zone.style.display = "block";
    else thumbnail_zone.style.display = "none";
}

