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
            $cache_filename = sprintf('./files/member_extra_info/point/%s%d.cache.txt', getNumberingPath($member_srl), $member_srl);

            if(!$from_db && file_exists($target_filename)) return trim(FileHandler::readFile($cache_filename));

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
    }
?>
