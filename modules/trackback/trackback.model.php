<?php
    /**
     * @class  trackbackModel
     * @author zero (zero@nzeo.com)
     * @brief  trackback 모듈의 model class
     **/

    class trackbackModel extends trackback {

        /**
         * @brief 초기화
         **/
        function init() {
        }

        /**
         * @brief 하나의 트랙백 정보를 구함
         **/
        function getTrackback($trackback_srl) {
            $oDB = &DB::getInstance();

            $args->trackback_srl = $trackback_srl;
            return $oDB->executeQuery('trackback.getTrackback', $args);
        }

        /**
         * @brief document_srl 에 해당하는 엮인글의 전체 갯수를 가져옴
         **/
        function getTrackbackCount($document_srl) {
            $oDB = &DB::getInstance();

            $args->document_srl = $document_srl;
            $output = $oDB->executeQuery('trackback.getTrackbackCount', $args);
            $total_count = $output->data->count;

            return (int)$total_count;
        }

        /**
         * @brief 특정 document에 특정 ip로 기록된 트랙백의 갯수
         * spamfilter 에서 사용할 method임
         **/
        function getTrackbackCountByIPAddress($document_srl, $ipaddress) {
            $oDB = &DB::getInstance();

            $args->document_srl = $document_srl;
            $args->ipaddress = $ipaddress;
            $output = $oDB->executeQuery('trackback.getTrackbackCountByIPAddress', $args);
            $total_count = $output->data->count;

            return (int)$total_count;
        }

        /**
         * @brief 특정 문서에 속한 엮인글의 목록을 가져옴
         **/
        function getTrackbackList($document_srl) {
            $oDB = &DB::getInstance();

            $args->document_srl = $document_srl;
            $args->list_order = 'list_order';
            $output = $oDB->executeQuery('trackback.getTrackbackList', $args);

            if(!$output->toBool()) return $output;

            $trackback_list = $output->data;

            if(!is_array($trackback_list)) $trackback_list = array($trackback_list);

            return $trackback_list;
        }

        /**
         * @brief 모든 엮인글를 시간 역순으로 가져옴 (관리자용)
         **/
        function getTotalTrackbackList($obj) {

            // DB 객체 생성
            $oDB = &DB::getInstance();

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

            // trackback.getTotalTrackbackList 쿼리 실행
            $output = $oDB->executeQuery('trackback.getTotalTrackbackList', $args);

            // 결과가 없거나 오류 발생시 그냥 return
            if(!$output->toBool()||!count($output->data)) return $output;

            return $output;
        }
    }
?>
