<?php
    /**
     * @class  pointModel
     * @author zero (zero@nzeo.com)
     * @brief  point 모듈의 model class
     **/

    class pointModel extends point {

        /**
         * @brief 초기화
         **/
        function init() {
        }

        /**
         * @brief 포인트 정보가 있는지 체크
         **/
        function isExistsPoint($member_srl) {
            $args->member_srl = $member_srl;
            $output = executeQuery('point.getPoint', $args);
            if($output->data->member_srl == $member_srl) return true;
            return false;
        }

        /**
         * @brief 포인트를 구해옴
         **/
        function getPoint($member_srl, $from_db = false) {
            $path = sprintf('./files/member_extra_info/point/%s',getNumberingPath($member_srl));
            if(!is_dir($path)) FileHandler::makeDir($path);
            $cache_filename = sprintf('%s%d.cache.txt', $path, $member_srl);

            if(!$from_db && file_exists($cache_filename)) return trim(FileHandler::readFile($cache_filename));

            // DB에서 가져옴
            $args->member_srl = $member_srl;
            $output = executeQuery('point.getPoint', $args);
            $point = (int)$output->data->point;

            FileHandler::writeFile($cache_filename, $point);

            return $point;
        }

        /**
         * @brief 레벨을 구함
         **/
        function getLevel($point, $level_step) {
            $level_count = count($level_step);
            for($level=0;$level<=$level_count;$level++) if($point < $level_step[$level]) break;
            $level --;
            return $level;
        }

        /**
         * @brief 포인트 순 회원목록 가져오기
         **/
        function getMemberList($args = null) {
            // member model 객체 생성후 목록을 구해옴
            $oMemberModel = &getAdminModel('member');
            $output = $oMemberModel->getMemberList();

            if($output->total_count) {
                $oModuleModel = &getModel('module');
                $config = $oModuleModel->getModuleConfig('point');

                foreach($output->data as $key => $val) {
                    $point = $this->getPoint($val->member_srl);
                    $output->data[$key]->point = $point;
                    $output->data[$key]->level = $this->getLevel($point, $config->level_step);
                }
            }

            return $output;
        }
    }
?>
