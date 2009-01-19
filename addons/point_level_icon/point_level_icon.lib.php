<?php
    /**
     * @brief 포인트 아이콘 변경을 위한 함수.
     **/
    function pointLevelIconTrans($matches) {
        $member_srl = $matches[3];
        if($member_srl<1) return $matches[0];

        if(!isset($GLOBALS['_pointLevelIcon'][$member_srl])) {
            // 포인트 설정을 구해옴
            if(!$GLOBALS['_pointConfig']) {
                $oModuleModel = &getModel('module');
                $GLOBALS['_pointConfig'] = $oModuleModel->getModuleConfig('point');
            }
            $config = $GLOBALS['_pointConfig'];

            // 포인트 모델을 구해 놓음
            if(!$GLOBALS['_pointModel']) $GLOBALS['_pointModel'] = getModel('point');
            $oPointModel = &$GLOBALS['_pointModel'];

            // 포인트를 구함
            $point = $oPointModel->getPoint($member_srl);

            // 레벨을 구함
            $level = $oPointModel->getLevel($point, $config->level_step);
            $text = $matches[5];

            // 레벨 아이콘의 위치를 구함
            $level_icon = sprintf('%smodules/point/icons/%s/%d.gif', Context::getRequestUri(), $config->level_icon, $level);

            // 최고 레벨이 아니면 다음 레벨로 가기 위한 per을 구함 :: 주석과 실제 내용이 맞지 않아 실제 내용을 수정
            if($level < $config->max_level) {
                $next_point = $config->level_step[$level+1];
                $present_point = $config->level_step[$level];
                if($next_point > 0) { 
                    $per = (int)(($point - $present_point) / ($next_point - $present_point)*100);
                    $per = $per.'%';
                }
            }

            $title = sprintf('%s:%s%s%s, %s:%s/%s', Context::getLang('point'), $point, $config->point_name, $per?' ('.$per.')':'', Context::getLang('level'), $level, $config->max_level);
            $alt = sprintf('[%s:%s]', Context::getLang('level'), $level);

            $GLOBALS['_pointLevelIcon'][$member_srl] = sprintf('<img src="%s" alt="%s" title="%s" style="vertical-align:middle; margin-right:3px;" />', $level_icon, $alt, $title);
        }
        $text = $GLOBALS['_pointLevelIcon'][$member_srl];

        $orig_text = preg_replace('/'.preg_quote($matches[5],'/').'<\/'.$matches[6].'>$/', '', $matches[0]);
        return $orig_text.$text.$matches[5].'</'.$matches[6].'>';
    }
?>
