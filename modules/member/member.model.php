<?php
    /**
     * @class  memberModel
     * @author zero (zero@nzeo.com)
     * @brief  member module의 Model class
     **/

    class memberModel extends member {

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
         * @brief 선택된 회원의 간단한 메뉴를 표시
         **/
        function getMemberMenu() {
            // 요청된 회원 번호와 현재 사용자의 로그인 정보 구함
            $member_srl = Context::get('member_srl');
            $mid = Context::get('cur_mid');
            $module = Context::get('cur_module');
            $logged_info = Context::get('logged_info');

            // 자신의 아이디를 클릭한 경우 
            if($member_srl == $logged_info->member_srl) {
                $member_info = $logged_info;

            // 다른 사람의 아이디를 클릭한 경우
            } else {
                // 회원의 정보를 구함
                $member_info = $this->getMemberInfoByMemberSrl($member_srl);
            }

            // 변수 정리
            $user_id = $member_info->user_id;
            $user_name = $member_info->user_name;
            $email_address = $member_info->email_address;

            // menu_list 에 "표시할글,target,url" 을 배열로 넣는다
            $menu_list = array();

            // 게시판이나 블로그등일 경우는 특별 옵션 지정
            if($mid) {
                // 회원 정보 보기
                $menu_str = Context::getLang('cmd_view_member_info');
                $menu_url = sprintf('./?mid=%s&amp;act=dispMemberInfo&amp;member_srl=%s', $mid, $member_srl);
                $menu_list[] = sprintf('%s,move_url(\'%s\')', $menu_str, $menu_url);

                // 아이디로 검색
                $menu_str = Context::getLang('cmd_view_own_document');
                $menu_url = sprintf('./?mid=%s&amp;search_target=user_id&amp;search_keyword=%s', $mid, $user_id);
                $menu_list[] = sprintf('%s,move_url(\'%s\')', $menu_str, $menu_url);
            }

            // 다른 사람의 아이디를 클릭한 경우 (메일, 쪽지 보내기등은 다른 사람에게만 보내는거로 설정)
            if($member_srl != $logged_info->member_srl) {

                // 메일 보내기 
                $menu_str = Context::getLang('cmd_send_email');
                $menu_url = sprintf('%s <%s>', $user_name, $email_address);
                $menu_list[] = sprintf('%s,sendMailTo(\'%s\')', $menu_str, $menu_url);
            }

            // 정보를 저장
            $this->add("menu_list", implode("\n",$menu_list));
        }

        /**
         * @brief 로그인 되어 있는지에 대한 체크
         **/
        function isLogged() {
            if($_SESSION['is_logged']&&$_SESSION['ipaddress']==$_SERVER['REMOTE_ADDR']) return true;

            $_SESSION['is_logged'] = false;
            $_SESSION['logged_info'] = '';
            return false;
        }

        /**
         * @brief 인증된 사용자의 정보 return
         **/
        function getLoggedInfo() {
            // 로그인 되어 있고 세션 정보를 요청하면 세션 정보를 return
            if($this->isLogged()) return $_SESSION['logged_info'];
            return NULL;
        }

        /**
         * @brief user_id에 해당하는 사용자 정보 return
         **/
        function getMemberInfoByUserID($user_id) {
            if(!$this->member_info[$member_srl]) {
                $args->user_id = $user_id;
                $output = executeQuery('member.getMemberInfo', $args);
                if(!$output) return $output;

                $member_info = $this->arrangeMemberInfo($output->data);
                $member_info->group_list = $this->getMemberGroups($member_info->member_srl);

                $this->member_info[$user_id] = $member_info;
            }
            return $this->member_info[$user_id];
        }

        /**
         * @brief member_srl로 사용자 정보 return
         **/
        function getMemberInfoByMemberSrl($member_srl) {
            if(!$this->member_info[$member_srl]) {
                $args->member_srl = $member_srl;
                $output = executeQuery('member.getMemberInfoByMemberSrl', $args);
                if(!$output) return $output;

                $member_info = $this->arrangeMemberInfo($output->data);
                $member_info->group_list = $this->getMemberGroups($member_info->member_srl);

                $this->member_info[$member_srl] = $member_info;
            }
            return $this->member_info[$member_srl];
        }

        /**
         * @brief 사용자 정보 중 extra_vars와 기타 정보를 알맞게 편집
         **/
        function arrangeMemberInfo($info) {
            $info->image_name = $this->getImageName($info->member_srl);
            $info->image_mark = $this->getImageMark($info->member_srl);

            $extra_vars = unserialize($info->extra_vars);
            unset($info->extra_vars);
            if(!$extra_vars) return $info;
            foreach($extra_vars as $key => $val) {
                if(eregi('\|\@\|', $val)) $val = explode('|@|', $val);
                if(!$info->{$key}) $info->{$key} = $val;
            }
            return $info;
        }

        /**
         * @brief userid에 해당하는 member_srl을 구함
         **/
        function getMemberSrlByUserID($user_id) {
            $args->user_id = $user_id;
            $output = executeQuery('member.getMemberSrl', $args);
            return $output->data->member_srl;
        }

        /**
         * @brief userid에 해당하는 member_srl을 구함
         **/
        function getMemberSrlByEmailAddress($email_address) {
            $args->email_address = $email_address;
            $output = executeQuery('member.getMemberSrl', $args);
            return $output->data->member_srl;
        }

        /**
         * @brief userid에 해당하는 member_srl을 구함
         **/
        function getMemberSrlByNickName($nick_name) {
            $args->nick_name = $nick_name;
            $output = executeQuery('member.getMemberSrl', $args);
            return $output->data->member_srl;
        }

        /**
         * @brief 현재 접속자의 member_srl을 return
         **/
        function getLoggedMemberSrl() {
            if(!$this->isLogged()) return;
            return $_SESSION['member_srl'];
        }

        /**
         * @brief 현재 접속자의 user_id을 return
         **/
        function getLoggedUserID() {
            if(!$this->isLogged()) return;
            $logged_info = $_SESSION['logged_info'];
            return $logged_info->user_id;
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

        /**
         * @brief member_srl이 속한 group 목록을 가져옴
         **/
        function getMemberGroups($member_srl) {
            if(!$this->member_groups[$member_srl]) {
                $args->member_srl = $member_srl;
                $output = executeQuery('member.getMemberGroups', $args);
                if(!$output->data) return;

                $group_list = $output->data;
                if(!is_array($group_list)) $group_list = array($group_list);

                foreach($group_list as $group) {
                    $result[$group->group_srl] = $group->title;
                }
                $this->member_groups[$member_srl] = $result;
            }
            return $this->member_groups[$member_srl];
        }

        /**
         * @brief 기본 그룹을 가져옴
         **/
        function getDefaultGroup() {
            $output = executeQuery('member.getDefaultGroup');
            return $output->data;
        }

        /**
         * @brief group_srl에 해당하는 그룹 정보 가져옴
         **/
        function getGroup($group_srl) {
            $args->group_srl = $group_srl;
            $output = executeQuery('member.getGroup', $args);
            return $output->data;
        }

        /**
         * @brief 그룹 목록을 가져옴
         **/
        function getGroups() {
            $output = executeQuery('member.getGroups');
            if(!$output->data) return;

            $group_list = $output->data;
            if(!is_array($group_list)) $group_list = array($group_list);

            foreach($group_list as $val) {
                $result[$val->group_srl] = $val;
            }
            return $result;
        }

        /**
         * @brief 회원 가입폼 추가 확장 목록 가져오기
         *
         * 이 메소드는 modules/member/tpl/filter/insert.xml 의 extend_filter로 동작을 한다.
         * extend_filter로 사용을 하기 위해서는 인자값으로 boolean값을 받도록 규정한다.
         * 이 인자값이 true일 경우 filter 타입에 맞는 형태의 object로 결과를 return하여야 한다.
         **/
        function getJoinFormList($filter_response = false) {
            global $lang;

            if(!$this->join_form_list) {
                // list_order 컬럼의 정렬을 위한 인자 세팅
                $args->sort_index = "list_order";
                $output = executeQuery('member.getJoinFormList', $args);

                // 결과 데이터가 없으면 NULL return
                $join_form_list = $output->data;
                if(!$join_form_list) return NULL;

                // default_value의 경우 DB에 array가 serialize되어 입력되므로 unserialize가 필요
                if(!is_array($join_form_list)) $join_form_list = array($join_form_list);
                $join_form_count = count($join_form_list);
                for($i=0;$i<$join_form_count;$i++) {
                    $member_join_form_srl = $join_form_list[$i]->member_join_form_srl;
                    $column_type = $join_form_list[$i]->column_type;
                    $column_name = $join_form_list[$i]->column_name;
                    $column_title = $join_form_list[$i]->column_title;
                    $default_value = $join_form_list[$i]->default_value;

                    // 언어변수에 추가
                    $lang->extend_vars[$column_name] = $column_title;

                    // checkbox, select등 다수 데이터 형식일 경우 unserialize해줌
                    if(in_array($column_type, array('checkbox','select'))) {
                        $join_form_list[$i]->default_value = unserialize($default_value);
                        if(!$join_form_list[$i]->default_value[0]) $join_form_list[$i]->default_value = '';
                    } else {
                        $join_form_list[$i]->default_value = '';
                    }

                    $list[$member_join_form_srl] = $join_form_list[$i];
                }
                $this->join_form_list = $list;
            }

            // filter_response가 true일 경우 object 스타일을 구함
            if($filter_response && count($this->join_form_list)) {

                foreach($this->join_form_list as $key => $val) {
                    if($val->is_active != 'Y') continue;
                    unset($obj);
                    $obj->type = $val->column_type;
                    $obj->name = $val->column_name;
                    $obj->lang = $val->column_title;
                    $obj->required = $val->required=='Y'?true:false;
                    $filter_output[] = $obj;
                }
                return $filter_output;

            }

            // 결과 리턴
            return $this->join_form_list;
        }

        /**
         * @brief 추가 회원가입폼과 특정 회원의 정보를 조합 (회원정보 수정등에 사용)
         **/
        function getCombineJoinForm($member_info) {
            $extend_form_list = $this->getJoinFormlist();
            if(!$extend_form_list) return;

            foreach($extend_form_list as $srl => $item) {
                $column_name = $item->column_name;
                $value = $member_info->{$column_name};

                // 추가 확장폼의 종류에 따라 값을 변경
                switch($item->column_type) {
                    case 'checkbox' :
                            if($value && !is_array($value)) $value = array($value);
                        break;
                    case 'text' :
                    case 'homepage' :
                    case 'email_address' :
                    case 'tel' :
                    case 'textarea' :
                    case 'select' :
                    case 'kr_zip' :
                        break;
                }

                $extend_form_list[$srl]->value = $value;
            }
            return $extend_form_list;
        }

        /**
         * @brief 한개의 가입항목을 가져옴
         **/
        function getJoinForm($member_join_form_srl) {
            $args->member_join_form_srl = $member_join_form_srl;
            $output = executeQuery('member.getJoinForm', $args);
            $join_form = $output->data;
            if(!$join_form) return NULL;

            $column_type = $join_form->column_type;
            $default_value = $join_form->default_value;

            if(in_array($column_type, array('checkbox','select'))) {
                $join_form->default_value = unserialize($default_value);
            } else {
                $join_form->default_value = '';
            }

            return $join_form;
        }

        /**
         * @brief 금지 아이디 목록 가져오기
         **/
        function getDeniedIDList() {
            if(!$this->denied_id_list) {
                $args->sort_index = "list_order";
                $args->page = Context::get('page');
                $args->list_count = 40;
                $args->page_count = 10;

                $output = executeQuery('member.getDeniedIDList', $args);
                $this->denied_id_list = $output;
            }
            return $this->denied_id_list;
        }

        /**
         * @brief 금지 아이디인지 확인
         **/
        function isDeniedID($user_id) {
            $args->user_id = $user_id;
            $output = executeQuery('member.chkDeniedID', $args);
            if($output->data->count) return true;
            return false;
        }

        /**
         * @brief 이미지이름의 정보를 구함
         **/
        function getImageName($member_srl) {
            $image_name_file = sprintf('files/attach/image_name/%s%d.gif', getNumberingPath($member_srl), $member_srl);
            if(!file_exists($image_name_file)) return;
            list($width, $height, $type, $attrs) = getimagesize($image_name_file);
            $info->width = $width;
            $info->height = $height;
            $info->src = Context::getRequestUri().$image_name_file;
            $info->file = './'.$image_name_file;
            return $info;
        }

        /**
         * @brief 이미지마크의 정보를 구함
         **/
        function getImageMark($member_srl) {
            $image_mark_file = sprintf('files/attach/image_mark/%s%d.gif', getNumberingPath($member_srl), $member_srl);
            if(!file_exists($image_mark_file)) return;
            list($width, $height, $type, $attrs) = getimagesize($image_mark_file);
            $info->width = $width;
            $info->height = $height;
            $info->src = Context::getRequestUri().$image_mark_file;
            $info->file = './'.$image_mark_file;
            return $info;
        }

        /**
         * @brief 사용자의 signature를 구함
         **/
        function getSignature($member_srl) {
            $filename = sprintf('files/attach/signature/%s%d.signature.php', getNumberingPath($member_srl), $member_srl);
            if(!file_exists($filename)) return '';

            $buff = FileHandler::readFile($filename);
            $signature = substr($buff, 29);
            return $signature;
        }
    }
?>
