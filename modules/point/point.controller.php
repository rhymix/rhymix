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
            if($this->member_code[$member_srl]) return $this->member_code[$member_srl];

            $point = $this->oPointModel->getPoint($member_srl);
            $level = $this->oPointModel->getLevel($point, $this->config->level_step);

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

            $code = sprintf('<%s title="%s:%s%s %s, %s:%s/%s" style="cursor:pointer;background:url(%s) no-repeat left;padding-left:%dpx; height:%dpx">%s</%s> ', $matches[6], Context::getLang('point'), $point, $this->config->point_name, $per?"(".$per."%)":"", Context::getLang('level'), $level, $this->config->max_level, Context::getRequestUri().$src, $this->icon_width+2, $this->icon_height, $matches[0], $matches[6]);
            $this->member_code[$member_srl] = $code;

            return $this->member_code[$member_srl];
        }
    }
?>
