<?php
    /**
     * @class  pointController
     * @author zero (zero@nzeo.com)
     * @brief  point모듈의 Controller class
     **/

    class pointController extends point {

        var $config = null;
        var $oPointModel = null;
        var $member_code = array();
        var $icon_width = 0;
        var $icon_height = 0;

        /**
         * @brief 초기화
         **/
        function init() {
        }

        /**
         * @brief 포인트 설정
         **/
        function setPoint($member_srl, $point) {
            // 변수 설정
            $args->member_srl = $member_srl;
            $args->point = $point;

            // 포인트가 있는지 체크
            $oPointModel = &getModel('point');
            if($oPointModel->isExistsPoint($member_srl)) {
                executeQuery("point.updatePoint", $args);
            } else {
                executeQuery("point.insertPoint", $args);
            }

            // 캐시 설정
            $cache_path = sprintf('./files/member_extra_info/point/%s/', getNumberingPath($member_srl));
            FileHandler::makedir($cache_path);

            $cache_filename = sprintf('%s%d.cache.txt', $cache_path, $member_srl);
            FileHandler::writeFile($cache_filename, $point);

            return $output;
        }

        /**
         * @brief 포인트 레벨 아이콘 표시
         **/
        function transLevelIcon($matches) {
            if(!$this->config) {
                $oModuleModel = &getModel('module');
                $this->config = $oModuleModel->getModuleConfig('point');
            }

            if(!$this->oPointModel) $this->oPointModel = &getModel('point');

            $member_srl = $matches[3];
            if($member_srl<1) return $matches[0];

            if($this->member_code[$member_srl]) return $this->member_code[$member_srl];

            $point = $this->oPointModel->getPoint($member_srl);
            $level = $this->oPointModel->getLevel($point, $this->config->level_step);

            $text = $matches[5];

            $src = sprintf("modules/point/icons/%s/%d.gif", $this->config->level_icon, $level);
            if(!$this->icon_width) {
                $info = getimagesize($src);
                $this->icon_width = $info[0];
                $this->icon_height = $info[1];
            }

            if($level < $this->config->max_level) {
                $next_point = $this->config->level_step[$level+1];
                if($next_point > 0) {
                    $per = (int)($point / $next_point*100);
                }
            }

            $title = sprintf("%s:%s%s %s, %s:%s/%s", Context::getLang('point'), $point, $this->config->point_name, $per?"(".$per."%)":"", Context::getLang('level'), $level, $this->config->max_level);

            $text = sprintf('<span class="nowrap member_%s" style="cursor:pointer"><img src="%s" width="%s" height="%s" alt="%s" title="%s" align="absmiddle"  style="margin-right:3px"/>%s</span>', $member_srl, Context::getRequestUri().$src, $this->icon_width+2, $this->icon_height, $title, $title, $text);

            $this->member_code[$member_srl] = $text;

            return $this->member_code[$member_srl];
        }
    }
?>
