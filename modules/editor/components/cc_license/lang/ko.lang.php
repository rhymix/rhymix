<?php
    /**
     * @file   /modules/editor/components/cc_license/lang/ko.lang.php
     * @author zero <zero@zeroboard.com>
     * @brief  위지윅에디터(editor) 모듈 >  CCL 출력 에디터 컴포넌트
     **/

    $lang->ccl_default_title = '크리에이티브 커먼즈 코리아 저작자표시';
    $lang->ccl_default_message = '이 저작물은 <a rel="license" href="http://creativecommons.org/licenses/by%s%s%s%s" onclick="winopen(this.href);return false;">%s%s%s%s</a>에 따라 이용하실 수 있습니다';

    $lang->ccl_title = '제목';
    $lang->ccl_use_mark = '마크 사용';
    $lang->ccl_allow_commercial = '영리목적 허용';
    $lang->ccl_allow_modification = '저작물 변경 허용';

    $lang->ccl_allow = '허용';
    $lang->ccl_disallow = '금지';
    $lang->ccl_sa = '동일 조건 변경';

    $lang->ccl_options = array(
        'ccl_allow_commercial' => array('Y'=>'-영리', 'N'=>'-비영리'),
        'ccl_allow_modification' => array('Y'=>'-변경허용', 'N'=>'-변경금지', 'SA'=>'-동일조건변경허락'),
    );

    $lang->about_ccl_title = '제목을 표시합니다. 비워져 있으면 기본 메세지가 출력됩니다.';
    $lang->about_ccl_use_mark = '마크의 출력 여부를 선택할 수 있습니다. (기본: 출력)';
    $lang->about_ccl_allow_commercial = '영리목적 이용을 허가 여부를 선택할 수 있습니다 (기본: 허용안함)';
    $lang->about_ccl_allow_modification = '저작물의 변경 여부를 허용할 수 있습니다. (기본: 동일 조건 변경)';
?>
