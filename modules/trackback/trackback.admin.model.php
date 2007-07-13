<?php
    /**
     * @class  trackbackAdminModel
     * @author zero (zero@nzeo.com)
     * @brief  trackback 모듈의 admin model class
     **/

    class trackbackAdminModel extends trackback {

        /**
         * @brief 초기화
         **/
        function init() {
        }

        /**
         * @brief 모든 엮인글를 시간 역순으로 가져옴 (관리자용)
         **/
        function getTotalTrackbackList($obj) {
            // 검색 옵션 정리
            $search_target = trim(Context::get('search_target'));
            $search_keyword = trim(Context::get('search_keyword'));

            if($search_target && $search_keyword) {
                switch($search_target) {
                    case 'url' :
                            if($search_keyword) $search_keyword = str_replace(' ','%',$search_keyword);
                            $args->s_url = $search_keyword;
                        break;
                    case 'title' :
                            if($search_keyword) $search_keyword = str_replace(' ','%',$search_keyword);
                            $args->s_title= $search_keyword;
                        break;
                    case 'blog_name' :
                            if($search_keyword) $search_keyword = str_replace(' ','%',$search_keyword);
                            $args->s_blog_name= $search_keyword;
                        break;
                    case 'excerpt' :
                            if($search_keyword) $search_keyword = str_replace(' ','%',$search_keyword);
                            $args->s_excerpt = $search_keyword;
                        break;
                    case 'regdate' :
                            $args->s_regdate = $search_keyword;
                        break;
                    case 'ipaddress' :
                            $args->s_ipaddress= $search_keyword;
                        break;
                }
            }

            // 변수 설정
            $args->sort_index = $obj->sort_index;
            $args->page = $obj->page?$obj->page:1;
            $args->list_count = $obj->list_count?$obj->list_count:20;
            $args->page_count = $obj->page_count?$obj->page_count:10;
            $args->s_module_srl = $obj->module_srl;

            // trackback.getTotalTrackbackList 쿼리 실행
            $output = executeQuery('trackback.getTotalTrackbackList', $args);

            // 결과가 없거나 오류 발생시 그냥 return
            if(!$output->toBool()||!count($output->data)) return $output;

            return $output;
        }
    }
?>
