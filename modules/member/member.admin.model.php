<?php
    /**
     * @class  memberAdminModel
     * @author zero (zero@nzeo.com)
     * @brief  member module의 admin model class
     **/

    class memberAdminModel extends member {

        /**
         * @brief 자주 호출될거라 예상되는 데이터는 내부적으로 가지고 있자...
         **/
        var $member_info = NULL;
        var $member_groups = NULL;
        var $join_form_list = NULL;

        /**
         * @brief 초기화
         **/
        function init() {
        }

        /**
         * @brief 회원 목록을 구함
         **/
        function getMemberList() {
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
                }
            }

            // selected_group_srl이 있으면 query id를 변경 (table join때문에)
            if($args->selected_group_srl) {
                $query_id = 'member.getMemberListWithinGroup';
                $args->sort_index = "member.member_srl";
            } else {
                $query_id = 'member.getMemberList';
                $args->sort_index = "member_srl";
            }

            // 기타 변수들 정리
            $args->page = Context::get('page');
            $args->list_count = 40;
            $args->page_count = 10;
            return executeQuery($query_id, $args);
        }

    }
?>
