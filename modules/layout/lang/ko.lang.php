<?php
    /**
     * @file   modules/layout/lang/ko.lang.php
     * @author zero <zero@nzeo.com>
     * @brief  레이아웃(layout) 모듈의 기본 언어팩
     **/

    $lang->cmd_layout_management = '레이아웃 설정';
    $lang->cmd_layout_edit = '레이아웃 편집';

    $lang->layout_name = '레이아웃 이름';
    $lang->layout_maker = '레이아웃 제작자';
    $lang->layout_license = '라이선스';
    $lang->layout_history = '변경 이력';
    $lang->layout_info = '레이아웃 정보';
    $lang->layout_list = '레이아웃 목록';
    $lang->menu_count = '메뉴수';
    $lang->downloaded_list = '다운로드 목록';
    $lang->layout_preview_content = '내용이 출력되는 부분입니다';
    $lang->not_apply_menu = '레이아웃 일괄 적용';

    $lang->cmd_move_to_installed_list = '생성된 목록 보기';

    $lang->about_downloaded_layouts = '다운로드 되어 있는 레이아웃 목록';
    $lang->about_title = '모듈에 연결시 쉽게 구분할 수 있는 제목을 입력해주세요';
    $lang->about_not_apply_menu = '체크를 하시면 연결된 모든 메뉴의 모듈 레이아웃을 일괄 변경합니다.';

    $lang->about_layout = '레이아웃 모듈은 사이트의 레이아웃을 쉽게 만들 수 있도록 도와줍니다.<br />레이아웃 설정과 메뉴의 연결을 통해서 다양한 모듈이 완성된 사이트의 모습으로 보여줄 수 있도록 합니다.<br />* 삭제나 수정이 불가능한 레이아웃은 블로그나 기타 모듈의 자체 레이아웃이므로 해당 모듈로 가서 설정하셔야 합니다.';
    $lang->about_layout_code = 
        "아래 레이아웃의 코드를 직접 수정후 저장하시면 서비스에 반영이 됩니다.
        꼭 미리보기를 하신 후에 저장을 하세요.
        XE의 템플릿 문법은 <a href=\"#\" onclick=\"winopen('http://trac.zeroboard.com/trac/wiki/TemplateHandler');return false;\">XE 템플릿</a> 을 참고하시면 됩니다.";

    $lang->layout_export = '내보내기';
    $lang->layout_btn_export = '내 레이아웃 다운로드';
    $lang->about_layout_export = '현재 수정된 레이아웃을 내보내기를 합니다.';
    $lang->layout_import = '가져오기';
    $lang->about_layout_import = '가져오기를 할 경우 기존 수정된 레이아웃을 삭제가 됩니다. 가져오기를 하기전에 내보내기를 통해 백업을 하시기 바랍니다.';

    $lang->layout_manager = array(
        0  => '레이아웃 매니저',
        1  => '저장',
        2  => '취소',
        3  => '형태',
        4  => '배열',
        5  => '정렬',
        6  => '고정 레이아웃',
        7  => '가변 레이아웃',
        8  => '고정+가변(내용)',
        9  => '1칸',
        10 => '2칸 (내용 왼쪽)',
        11 => '2칸 (내용 오른쪽)',
        12 => '3칸 (내용 왼쪽)',
        13 => '3칸 (내용 가운데)',
        14 => '3칸 (내용 오른쪽)',
        15 => '왼쪽',
        16 => '가운데',
        17 => '오른쪽',
        18 => '전체',
        19 => '레이아웃',
        20 => '위젯 추가',
        21 => '내용 위젯 추가',
        22 => '속성',
        23 => '위젯 스타일',
        24 => '수정',
        25 => '삭제',
        26 => '정렬',
        27 => '한줄 차지',
        28 => '왼쪽',
        29 => '오른쪽',
        30 => '가로 너비',
        31 => '높이',
        32 => '바깥 여백',
        33 => '안쪽 여백',
        34 => '위',
        35 => '왼',
        36 => '오른',
        37 => '아래',
        38 => '테두리', 
        39 => '없음',
        40 => '배경',
        41 => '색상',
        42 => '그림',
        43 => '선택',
        44 => '배경 그림 반복',
        45 => '반복',
        46 => '반복 안함',
        47 => '가로 반복',
        48 => '세로 반복',
        49 => '적용',
        50 => '취소',
        51 => '초기화',
        52 => '글자',
        53 => '글자 폰트',
        54 => '글자 색',
    );

    $lang->layout_image_repository = '레이아웃 파일 저장소';
    $lang->about_layout_image_repository = '선택된 레이아웃에 사용될 이미지/플래시파일등을 올릴 수 있습니다. 내보내기에 같이 포함이 됩니다';
    $lang->msg_layout_image_target = 'gif, png, jpg, swf, flv파일만 가능합니다';
    $lang->layout_migration = '레이아웃 내보내기/ 들이기';
    $lang->about_layout_migration = '수정된 레이아웃을 tar 파일로 내보내거나 tar 파일로 저장된 것을 불러올 수 있습니다'."\n".'(아직은 faceOff레이아웃만 내보내기/들이기가 됩니다';

    $lang->about_faceoff = array(
        'title' => 'XpressEngine FaceOff Layout 관리자',
        'description' => 'FaceOff Layout관리자는 웹상에서 쉽게 레이아웃을 꾸밀 수 있습니다.<br/>아래 그림을 보시고 구성요소와 기능을 이용하여 원하시는 레이아웃을 만드세요',
        'layout' => 'FaceOff는 위와 같은 HTML 구조로 되어 있습니다.<br/>이 구조에서 CSS를 이용하여 형태/배열/정렬을 할 수 있고 또 Style을 이용하여 꾸밀 수 있습니다.<br/>위젯 추가는 Extension(e1, e2)와 Neck, Knee에서 가능합니다.<br/>이 외 Body, Layout, Header, Body, Footer는 Style을 꾸밀 수 있고 Content는 모듈의 내용이 출력됩니다.',
        'setting' => '좌측 상단의 메뉴에 대해 설명 드립니다.<br/><ul><li>저장 : 설정된 내용을 저장합니다.</li><li>취소 : 설정한 내용을 저장하지 않고 돌아갑니다.</li><li>초기화 : 아무 설정도 되어 있지 않은 백지 상태로 돌립니다</li><li>형태 : 고정/ 가변/ 고정+가변(내용)의 형태를 지정합니다.</li><li>배열 : Extension 2개와 Content를 배열합니다.</li><li>정렬 : 레이아웃의 위치를 정렬시킬 수 있습니다.</li></ul>',
        'hotkey' => '마우스로 각 영역을 선택하면서 Hot Key를 이용하시면 더 쉽게 꾸미실 수 있습니다.<br/><ul><li>tab 키 : 위젯이 선택되어 있지 않으면 Header, Body, Footer 순으로 선택됩니다. 위젯이 선택되어 있다면 다음 위젯으로 선택이 이동됩니다.</li><li>Shift + tab키 : tab키와 반대 역할을 합니다.</li><li>Esc : 아무것도 선택되어 있지 않을때 Esc를 누르면 Neck, Extension(e1,e2),Knee 순서대로 선택이 되며 위젯이 선택되어 있다면 선택된 위젯을 감싸는 영역이 선택됩니다.</li><li>방향키 : 위젯이 선택되어 있을때 방향키를 이용하여 위젯을 다른 영역으로 이동시킬 수 있습니다.</li></ul>',
        'attribute' => '위젯을 제외한 각 영역들은 모두 배경 색/ 이미지를 지정할 수 있고 글자색(a 태그 포함됨)을 정할 수 있습니다.',

    );
?>
