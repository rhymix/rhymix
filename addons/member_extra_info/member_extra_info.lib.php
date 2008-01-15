<?php
    /**
     * @brief div 또는 span에 member_번호 가 있을때 해당 회원 번호에 맞는 이미지이름이나 닉이미지를 대체
     **/
    function memberTransImageName($matches) {
        // 회원번호를 추출하여 0보다 찾으면 본문중 text만 return
        $member_srl = $matches[3];
        if($member_srl<0) return $matches[5];

        // 회원이 아닐경우(member_srl = 0) 본문 전체를 return
        $nick_name = $matches[5];
        if(!$member_srl) return $matches[0];

        // 전역변수에 미리 설정한 데이터가 있다면 그걸 return
        if(!$GLOBALS['_transImageNameList'][$member_srl]->cached) {
            $GLOBALS['_transImageNameList'][$member_srl]->cached = true;
            $image_name_file = sprintf('./files/member_extra_info/image_name/%s%d.gif', getNumberingPath($member_srl), $member_srl);
            $image_mark_file = sprintf('./files/member_extra_info/image_mark/%s%d.gif', getNumberingPath($member_srl), $member_srl);
            if(file_exists($image_name_file)) $GLOBALS['_transImageNameList'][$member_srl]->image_name_file = $image_name_file;
            else $image_name_file = '';
            if(file_exists($image_mark_file)) $GLOBALS['_transImageNameList'][$member_srl]->image_mark_file = $image_mark_file;
            else $image_mark_file = '';
        }  else {
            $image_name_file = $GLOBALS['_transImageNameList'][$member_srl]->image_name_file;
            $image_mark_file = $GLOBALS['_transImageNameList'][$member_srl]->image_mark_file;
        }

        // 이미지이름이나 마크가 없으면 원본 정보를 세팅
        if(!$image_name_file && !$image_mark_file) return $matches[0];

        $nick_name = htmlspecialchars(strip_tags($nick_name));

        // 이미지 이름이 있을 경우
        if($image_name_file) $text = sprintf('<img src="%s" border="0" alt="id: %s" title="id: %s" style="vertical-align:middle;margin-right:3px" />', $image_name_file, $nick_name, $nick_name);

        if($image_mark_file) $text = sprintf('<img src="%s" border="0" alt="id: %s" title="id : %s" style="vertical-align:middle;margin-right:3px"/>%s', $image_mark_file, $nick_name, $nick_name, $text);

        return sprintf('<span class="nowrap member_%d" style="cursor:pointer">%s</span>',$member_srl, $text);
    }

    /**
     * @brief 게시글의 하단에 서명을 추가하는 코드
     **/
    function memberTransSignature($matches) {
        $member_srl = $matches[2];
        if(!$member_srl) return $matches[0];

        if(!$GLOBALS['_memberModuleConfig_']) {
            $oModuleModel = &getModel('module');
            $GLOBALS['_memberModuleConfig_'] = $oModuleModel->getModuleConfig('member');
        }
        $memberModuleConfig = $GLOBALS['_memberModuleConfig_'];

        // 전역변수에 미리 설정한 데이터가 있다면 그걸 return
        if(!$GLOBALS['_transSignatureList'][$member_srl]->cached) {
            $GLOBALS['_transSignatureList'][$member_srl]->cached = true;

            // 서명을 구해옴
            $signature = null;
            $signature_file = sprintf('files/member_extra_info/signature/%s%d.signature.php', getNumberingPath($member_srl), $member_srl);
            if(file_exists($signature_file)) $signature = trim(substr(FileHandler::readFile($signature_file),40));

            // 프로필 이미지를 구해옴
            $exts = array('gif','jpg','png');
            for($i=0;$i<3;$i++) {
                $profile_file = sprintf('files/member_extra_info/profile_image/%s%d.%s', getNumberingPath($member_srl), $member_srl, $exts[$i]);
                if(file_exists($profile_file)) {
                    $signature = sprintf('<img src="%s" alt="" class="member_profile_image" />%s', $profile_file, $signature);
                    break;
                }
            }

            $GLOBALS['_transSignatureList'][$member_srl]->signature = $signature;
        } else $signature = $GLOBALS['_transSignatureList'][$member_srl]->signature;

        if(!$signature) return $matches[0];

        // 서명 높이 제한 값이 있으면 표시 높이 제한
        if($memberModuleConfig->signature_max_height) {
            return sprintf('<div class="member_signature" style="max-height: %spx; overflow: auto; height: expression(this.scrollHeight > %s? \'%spx\': \'auto\');">%s<div class="clear"></div></div>', $memberModuleConfig->signature_max_height, $memberModuleConfig->signature_max_height, $memberModuleConfig->signature_max_height, $signature);
        } else {
            return sprintf('<div class="member_signature">%s<div class="clear"></div></div>', $signature);
        }
    }
?>
