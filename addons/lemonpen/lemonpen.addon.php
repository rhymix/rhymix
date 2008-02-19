<?php
    if(!defined("__ZBXE__")) exit();

    /**
     * @file rainbow.addon.php
     * @author zero (zero@nzeo.com)
     * @brief Rainbow link addon
     *
     * 링크가 걸린 텍스트에 마우스 오버를 하면 무지개색으로 변하게 하는 애드온입니다.
     * rainbow.js 파일만 추가하는 것으로 끝납니다.
     * rainbow.js는 http://www.dynamicdrive.com에서 제작하였으며 저작권을 가지고 있습니다.
     * before_display_content 에서만 요청이 됩니다.
     **/

    if(Context::getResponseMethod()=="XMLRPC") return;

    // 모듈의 실행 이후에 script를 추가함
    if(Context::get('module')!='admin' && $called_position == 'after_module_proc' ) {
        if($this->getLayoutFile() != 'popup_layout.html') {
            $sid = $addon_info->sid;
            if($sid) {
                Context::addHtmlFooter(sprintf('<script src="http://script.lemonpen.com/site/lemonpen.js?sid=%s" type="text/javascript" charset="UTF-8"></script>', $sid));
                $GLOBALS['__lemonpen_is_called__'] = true;
            }
        }
        return;
    }

    // 제로보드XE의 문서와 permant link를 레몬펜의 규약에 맞춰서 출력
    if($GLOBALS['__lemonpen_is_called__'] && $called_position == 'before_display_content') {
        // 글 본문을 링크
        $output = preg_replace('/<div class="document_([0-9]+)_([0-9]+) xe_content">/is','<div class="document_$1_$2 xe_content hentry"><a href="'.getUrl('','document_srl',"$1").'" rel="bookmark" style="display:none;">'.getUrl('','document_srl',"$1").'</a>', $output);

        // 댓글 본문을 링크
        $output = preg_replace('/<div class="comment_([0-9]+)_([0-9]+) xe_content">/is','<div class="comment_$1_$2 xe_content hentry"><a href="'.getUrl('','document_srl',"$1").'" rel="bookmark" style="display:none;">'.getUrl('','document_srl',"$1").'</a>', $output);
    }
?>
