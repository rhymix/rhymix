/**
 * @file  slide_show.js
 * @brief 이미지 슬라이드 쇼 스크립트
 * @author zero (zero@nzeo.com)
 **/

// 슬라이드쇼를 하기 위한 변수
var slide_show_images = new Array();

// 슬라이드쇼 이미지 목록에 추가
function slide_show_add_image(srl, thumbnail_url, image_url) {
  if(!thumbnail_url || !image_url) return;

  // 객체 생성
  var obj = {"srl":0, "thumbnail_url":null, "thumbnail":null, "image_url":null, "image":null}

  // slide_show_images에 슬라이드 쇼 고유번호에 해당하는 공간을 초기화
  if(typeof(slide_show_images[srl])=="undefined") slide_show_images[srl] = new Array();

  // 슬라이드쇼 고유번호를 세팅
  obj.srl = srl;
  obj.idx = slide_show_images[srl].length;

  // 썸네일 이미지를 미리 로딩
  obj.thumbnail = new Image();
  obj.thumbnail.src = thumbnail_url;
  obj.thumbnail.style.cursor = "pointer";
  obj.thumbnail.style.width = "60px";
  obj.thumbnail.style.height = "60px";
  obj.thumbnail.style.margin = "5px";
  obj.thumbnail.style.opacity = 0.5;
  obj.thumbnail.style.filter = "alpha(opacity=50)";

  // 원본 이미지를 미리 로딩
  obj.image = new Image();
  obj.image.src = image_url;

  // 썸네일 클릭시 메인 이미지로 바꾸어줌
  xAddEventListener(obj.thumbnail, "mousedown", function() { display_slide_image(obj) });

  // 생성된 객체를 slide_show_images[슬라이드쇼 고유번호]에 추가
  slide_show_images[srl][slide_show_images[srl].length] = obj;
}

// 슬라이드쇼 시작
function start_slide_show() {

  // 등록된 모든 이미지 목록을 돌면서 thumbnail 목록을 만들어줌
  for(var srl in slide_show_images) {

    // 첫번째 이미지의 경우 큰 이미지 출력 시작 이미지 출력
    display_slide_image(slide_show_images[srl][0]);

    // 등록된 이미지가 없으면 pass~
    if(!slide_show_images[srl].length) continue;

    // 메인이미지가 나올 곳과 썸네일이 노출될 곳의 객체를 구함
    var zone_thumbnail = xGetElementById('zone_thumbnail_'+srl);

    // 썸네일 출력
    for(var i=0; i<slide_show_images[srl].length;i++) {
      zone_thumbnail.appendChild(slide_show_images[srl][i].thumbnail);
    }

  }
}

// 메인 이미지 표시
function display_slide_image(obj) {
  var zone = xGetElementById('zone_slide_show_' + obj.srl );

  // 갤러리 외부 박스보다 이미지가 클 경우 resizing시킴 
  var zone_width = xWidth(zone);
  var zone_height = xHeight(zone);

  var image_width = obj.image.width;
  var image_height = obj.image.height;

  var resize_scale = 1;

  // 슬라이드 쇼 박스보다 큰 이미지는 크기를 줄여서 출력
  if(image_width>(zone_width-30)) {
    resize_scale = (zone_width-30)/image_width;
    image_width = parseInt(image_width*resize_scale,10);
    image_height = parseInt(image_height*resize_scale,10);
  }

  if(image_height>(zone_height-30)) {
    resize_scale = (zone_height-30)/image_height;
    image_width = parseInt(image_width*resize_scale,10);
    image_height = parseInt(image_height*resize_scale,10);
  }

  var x = parseInt((zone_width - image_width)/2,10)-3;
  var y = parseInt((zone_height - image_height)/2,10)-3;

  // 로딩 텍스트 없앰
  xGetElementById("slide_loading_text").style.display = "none";

  // 이미지 표시 
  var target_image = xGetElementById("slide_main_image_"+obj.srl);
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
  target_image.style.marginTop = y+"px";
  target_image.style.marginLeft = x+"px";

  target_image.style.display = "block";

  // resize_scale이 1이 아니면, 즉 리사이즈 되었다면 해당 이미지 클릭시 원본을 새창으로 띄워줌
  if(resize_scale!=1) {
    target_image.style.cursor = 'pointer';
    xAddEventListener(target_image, 'mousedown', slide_show_winopen);
  } else {
    target_image.style.cursor = 'default';
    xRemoveEventListener(target_image, 'mousedown', slide_show_winopen);
  }

  // srl의 모든 썸네일의 투명도 조절
  for(var i=0; i<slide_show_images[obj.srl].length;i++) {
    if(i==obj.idx) {
      slide_show_images[obj.srl][i].thumbnail.style.opacity = 1;
      slide_show_images[obj.srl][i].thumbnail.style.filter = "alpha(opacity=100)";
    } else {
      slide_show_images[obj.srl][i].thumbnail.style.opacity = 0.5;
      slide_show_images[obj.srl][i].thumbnail.style.filter = "alpha(opacity=50)";
    }
  }

}

// 큰 이미지의 경우 새창으로 띄워줌
function slide_show_winopen(evt) {
  var e = new xEvent(evt);
  var obj = e.target;
  var srl = obj.srl;
  var idx = obj.idx;

  var image_width = slide_show_images[srl][idx].image.width + 20;
  var image_height = slide_show_images[srl][idx].image.height + 20;

  winopen(slide_show_images[srl][idx].image.src, "SlideShow", "left=10,top=10,scrollbars=auto,resizable=yes,toolbars=no,width="+image_width+",height="+image_height);
}
