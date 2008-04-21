<?php
    if(!defined("__ZBXE__")) exit();

    /**
     * @file lemonpen.addon.php
     * @author zero (zero@nzeo.com)
     * @brief LemonPen addon
     * 스프링노트의 레몬펜을 사이트에 설치하는 애드온입니다
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
