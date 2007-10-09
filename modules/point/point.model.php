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
         * @brief 회원 목록 갱신
         **/
        function updateMemberList() {
            $output = executeQuery("point.getPointCount");
            $point_count = $output->data->count;

            $output = executeQuery("point.getMemberCount");
            $member_count = count($output->data);

            if($member_count > $point_count) {
                foreach($output->data as $key => $val) {

                    // 포인트가 있는지 체크
                    if($this->isExistsPoint($val->member_srl)) continue;

                    // 변수 설정
                    $args->member_srl = $val->member_srl;
                    $args->point = 0;

                    $insert = executeQuery("point.insertPoint", $args);

                    // 캐시 설정
                    $cache_path = sprintf('./files/member_extra_info/point/%s/', getNumberingPath($val->member_srl));
                    FileHandler::makedir($cache_path);

                    $cache_filename = sprintf('%s%d.cache.txt', $cache_path, $val->member_srl);
                    FileHandler::writeFile($cache_filename, 0);
                }
            }
        }

        /**
         * @brief 포인트 순 회원목록 가져오기
         **/
        function getMemberList($args = null) {

            // 검색 옵션 정리
            $args->is_admin = Context::get('is_admin')=='Y'?'Y':'';
            $args->is_denied = Context::get('is_denied')=='Y'?'Y':'';
            $args->selected_group_srl = Context::get('selected_group_srl');

            $search_target = trim(Context::get('search_target'));
            $search_keyword = trim(Context::get('search_keyword'));

            if($search_target && $search_keyword) {
                switch($search_target) {
                    case 'user_id' :
                            if($search_keyword) $search_keyword = str_replace(' ','%',$search_keyword);
                            $args->s_user_id = $search_keyword;
                        break;
                    case 'user_name' :
                            if($search_keyword) $search_keyword = str_replace(' ','%',$search_keyword);
                            $args->s_user_name = $search_keyword;
                        break;
                    case 'nick_name' :
                            if($search_keyword) $search_keyword = str_replace(' ','%',$search_keyword);
                            $args->s_nick_name = $search_keyword;
                        break;
                    case 'email_address' :
                            if($search_keyword) $search_keyword = str_replace(' ','%',$search_keyword);
                            $args->s_email_address = $search_keyword;
                        break;
                    case 'regdate' :
                            $args->s_regdate = $search_keyword;
                        break;
                    case 'last_login' :
                            $args->s_last_login = $search_keyword;
                        break;
                    case 'extra_vars' :
                            $args->s_extra_vars = $search_keyword;
                        break;
                }
            }

            // selected_group_srl이 있으면 query id를 변경 (table join때문에)
            if($args->selected_group_srl) {
                $query_id = 'point.getMemberListWithinGroup';
            } else {
                $query_id = 'point.getMemberList';
            }

            $output = executeQuery($query_id, $args);

            if($output->total_count) {
                $oModuleModel = &getModel('module');
                $config = $oModuleModel->getModuleConfig('point');

                foreach($output->data as $key => $val) {
                    $output->data[$key]->level = $this->getLevel($val->point, $config->level_step);
                }
            }

            return $output;
        }
    }
?>
