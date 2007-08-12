/**
 * @file  list_gallery.js
 * @brief 이미지 이미지갤러리 쇼 스크립트
 * @author zero (zero@nzeo.com)
 **/

// 이미지갤러리쇼를 하기 위한 변수
var list_gallery_images = new Array();

// 이미지갤러리쇼 이미지 목록에 추가
function list_gallery_add_image(srl, image_url) {
    if(!image_url) return;
    if(image_url.indexOf('files')==0) image_url = request_uri+image_url;

    // 객체 생성
    var obj = {"srl":0, "image_url":null, "image":null}

    // list_gallery_images에 이미지갤러리 쇼 고유번호에 해당하는 공간을 초기화
    if(typeof(list_gallery_images[srl])=="undefined") list_gallery_images[srl] = new Array();

    // 이미지갤러리쇼 고유번호를 세팅
    obj.srl = srl;
    obj.idx = list_gallery_images[srl].length;

    // 원본 이미지를 미리 로딩
    obj.image = new Image();
    obj.image.src = image_url;
    obj.image.srl = obj.srl;
    obj.image.idx = obj.idx;

    // 생성된 객체를 list_gallery_images[이미지갤러리쇼 고유번호]에 추가
    list_gallery_images[srl][list_gallery_images[srl].length] = obj;
}

// 이미지갤러리쇼 시작
function start_list_gallery() {

    // 등록된 모든 이미지 목록을 돌면서 목록을 만들어줌
    for(var srl in list_gallery_images) {

      // 등록된 이미지가 없으면 pass~
      if(!list_gallery_images[srl].length) continue;

      // 메인이미지가 나올 곳과 썸네일이 노출될 곳의 객체를 구함
      var zone = xGetElementById('zone_list_gallery_'+srl);

      // 갤러리 외부 박스보다 이미지가 클 경우 resizing시킴 
      var borderTop = parseInt(zone.style.borderTopWidth.replace(/px$/,''),10);
      var borderLeft = parseInt(zone.style.borderLeftWidth.replace(/px$/,''),10);
      var borderRight = parseInt(zone.style.borderRightWidth.replace(/px$/,''),10);
      var borderBottom = parseInt(zone.style.borderBottomWidth.replace(/px$/,''),10);

      var zone_width = xWidth(zone)-borderLeft-borderRight;

      // 이미지 출력
      for(var i=0; i<list_gallery_images[srl].length;i++) {
        var obj = list_gallery_images[srl][i];
        var image_width = obj.image.width;
        var image_height = obj.image.height;
        var resize_scale = 1;

        // 이미지갤러리 쇼 박스보다 큰 이미지는 크기를 줄여서 출력
        if(image_width>(zone_width-25)) {
            resize_scale = (zone_width-25)/image_width;
            image_width = parseInt(image_width*resize_scale,10);
            image_height = parseInt(image_height*resize_scale,10);
        }

        obj.image.style.width = image_width+"px";
        obj.image.style.height = image_height+"px";
        obj.image.style.marginLeft = "10px";
        obj.image.style.marginBottom = "10px";
        obj.image.style.display = "block";

        // resize_scale이 1이 아니면, 즉 리사이즈 되었다면 해당 이미지 클릭시 원본을 새창으로 띄워줌
        if(resize_scale!=1) {
            obj.image.style.cursor = 'pointer';
            xAddEventListener(obj.image, 'click', showOriginalImage);
        }

        zone.appendChild(obj.image);
      }
      zone.style.paddingTop = "10px";
    }
}
