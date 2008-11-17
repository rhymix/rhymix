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
            $member_srl = Context::get('target_srl');
            $mid = Context::get('cur_mid');
            $logged_info = Context::get('logged_info');
            $act = Context::get('cur_act');

            // 자신의 아이디를 클릭한 경우 
            if($member_srl == $logged_info->member_srl) $member_info = $logged_info;

            // 다른 사람의 아이디를 클릭한 경우
            else $member_info = $this->getMemberInfoByMemberSrl($member_srl);

            $member_srl = $member_info->member_srl;
            if(!$member_srl) return;

            // 변수 정리
            $user_id = $member_info->user_id;
            $user_name = $member_info->user_name;

            ModuleHandler::triggerCall('member.getMemberMenu', 'before', $null);

            $oMemberController = &getController('member');

            // 회원 정보 보기 (비회원일 경우 볼 수 없도록 수정)
            if($logged_info->member_srl) {
                $url = getUrl('','mid',$mid,'act','dispMemberInfo','member_srl',$member_srl);
                $icon_path = './modules/member/tpl/images/icon_view_info.gif';
                $oMemberController->addMemberPopupMenu($url,'cmd_view_member_info',$icon_path,'self');
            }

            // 다른 사람의 아이디를 클릭한 경우
            if($member_srl != $logged_info->member_srl) {

                // 메일 보내기 
                if($member_info->email_address) {
                    $url = 'mailto:'.$member_info->email_address;
                    $icon_path = './modules/member/tpl/images/icon_sendmail.gif';
                    $oMemberController->addMemberPopupMenu($url,'cmd_send_email',$icon_path);
                }
            }

            // 홈페이지 보기
            if($member_info->homepage) 
                $oMemberController->addMemberPopupMenu($member_info->homepage, 'homepage', './modules/member/tpl/images/icon_homepage.gif','blank');

            // 블로그 보기
            if($member_info->blog) 
                $oMemberController->addMemberPopupMenu($member_info->blog, 'blog', './modules/member/tpl/images/icon_blog.gif','blank');

            // trigger 호출 (after)
            ModuleHandler::triggerCall('member.getMemberMenu', 'after', $null);

            // 최고 관리자라면 회원정보 수정 메뉴 만듬
            if($logged_info->is_admin == 'Y') {
                $url = getUrl('','module','admin','act','dispMemberAdminInsert','member_srl',$member_srl);
                $icon_path = './modules/member/tpl/images/icon_management.gif';
                $oMemberController->addMemberPopupMenu($url,'cmd_management',$icon_path,'MemberModifyInfo');

                $url = getUrl('','module','admin','act','dispDocumentAdminList','search_target','member_srl','search_keyword',$member_srl);
                $icon_path = './modules/member/tpl/images/icon_trace_document.gif';
                $oMemberController->addMemberPopupMenu($url,'cmd_trace_document',$icon_path,'TraceMemberDocument');

                $url = getUrl('','module','admin','act','dispCommentAdminList','search_target','member_srl','search_keyword',$member_srl);
                $icon_path = './modules/member/tpl/images/icon_trace_comment.gif';
                $oMemberController->addMemberPopupMenu($url,'cmd_trace_comment',$icon_path,'TraceMemberComment');
            }

            // 팝업메뉴의 언어 변경
            $menus = Context::get('member_popup_menu_list');
            $menus_count = count($menus);
            for($i=0;$i<$menus_count;$i++) {
                $menus[$i]->str = Context::getLang($menus[$i]->str);
            }

            // 최종적으로 정리된 팝업메뉴 목록을 구함
            $this->add('menus', $menus);
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
            if($this->isLogged()) {
                $logged_info = $_SESSION['logged_info'];

                // site_module_info에 따라서 관리자/ 그룹 목록을 매번 재지정
                $site_module_info = Context::get('site_module_info');
                if($site_module_info) {
                    unset($logged_info->group_list);
                    $site_srl = $site_module_info->site_srl;
                    $groups = $this->getMemberGroups($logged_info->member_srl, $site_srl);

                    // 만약 현재 접속된 사이트의 그룹이 없으면 기본 그룹을 지정
                    if(!count($groups)) {
                        $default_group = $this->getDefaultGroup($site_module_info->site_srl);
                        $oMemberController = &getController('member');
                        $oMemberController->addMemberToGroup($logged_info->member_srl, $default_group->group_srl, $site_srl);
                        $groups[$default_group->group_srl] = $default_group->title;
                    }
                   
                    $logged_info->group_list = $groups;

                    $oModuleModel = &getModel('module');
                    if($oModuleModel->isSiteAdmin()) $logged_info->is_site_admin = true;
                    else $logged_info->is_site_admin = false;
                } else {
                    $logged_info->is_site_admin = false;
                }

                $_SESSION['logged_info'] = $logged_info;
                
                return $logged_info;
            }
            return NULL;
        }

        /**
         * @brief user_id에 해당하는 사용자 정보 return
         **/
        function getMemberInfoByUserID($user_id) {
            if(!$user_id) return;

            $args->user_id = $user_id;
            $output = executeQuery('member.getMemberInfo', $args);
            if(!$output) return $output;

            $member_info = $this->arrangeMemberInfo($output->data);
            $member_info->group_list = $this->getMemberGroups($member_info->member_srl);
            return $member_info;
        }

        /**
         * @brief member_srl로 사용자 정보 return
         **/
        function getMemberInfoByMemberSrl($member_srl) {
            if(!$member_srl) return;
            $args->member_srl = $member_srl;
            $output = executeQuery('member.getMemberInfoByMemberSrl', $args);
            if(!$output) return $output;

            $member_info = $this->arrangeMemberInfo($output->data);
            $member_info->group_list = $this->getMemberGroups($member_info->member_srl);
            return $member_info;
        }

        /**
         * @brief 사용자 정보 중 extra_vars와 기타 정보를 알맞게 편집
         **/
        function arrangeMemberInfo($info) {
            $info->profile_image = $this->getProfileImage($info->member_srl);
            $info->image_name = $this->getImageName($info->member_srl);
            $info->image_mark = $this->getImageMark($info->member_srl);
            $info->signature = $this->getSignature($info->member_srl);

            $extra_vars = unserialize($info->extra_vars);
            unset($info->extra_vars);
            if(!$extra_vars) return $info;
            foreach($extra_vars as $key => $val) {
                if(preg_match('/\|\@\|/i', $val)) $val = explode('|@|', $val);
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
         * @brief member_srl이 속한 group 목록을 가져옴
         **/
        function getMemberGroups($member_srl, $site_srl = 0) {
            static $member_groups = array();
            if(!$member_groups[$member_srl][$site_srl]) {
                $args->member_srl = $member_srl;
                $args->site_srl = $site_srl;
                $output = executeQuery('member.getMemberGroups', $args);
                if(!$output->data) return array();

                $group_list = $output->data;
                if(!is_array($group_list)) $group_list = array($group_list);

                foreach($group_list as $group) {
                    $result[$group->group_srl] = $group->title;
                }
                $member_groups[$member_srl][$site_srl] = $result;
            }
            return $member_groups[$member_srl][$site_srl];
        }

        /**
         * @brief member_srl들이 속한 group 목록을 가져옴
         **/
        function getMembersGroups($member_srls, $site_srl = 0) {
            $args->member_srls = implode(',',$member_srls);
            $args->site_srl = $site_srl;
            $output = executeQuery('member.getMembersGroups', $args);
            if(!$output->data) return array();

            $result = array();
            foreach($output->data as $key=>$val) {
                $result[$val->member_srl][] = $val->title;
            }
            return $result;
        }

        /**
         * @brief 기본 그룹을 가져옴
         **/
        function getDefaultGroup($site_srl = 0) {
            $args->site_srl = $site_srl;
            $output = executeQuery('member.getDefaultGroup', $args);
            return $output->data;
        }

        /**
         * @brief 관리자 그룹을 가져옴
         **/
        function getAdminGroup() {
            $output = executeQuery('member.getAdminGroup');
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
        function getGroups($site_srl = 0) {
            $args->site_srl = $site_srl;
            $output = executeQuery('member.getGroups', $args);
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
                    $join_form_list[$i]->column_name = strtolower($join_form_list[$i]->column_name);

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

                    unset($open_obj);
                    $open_obj->name = 'open_'.$val->column_name;
                    $open_obj->required = false;
                    $filter_output[] = $open_obj;

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

            // 관리자이거나 자기 자신이 아니면 비공개의 경우 무조건 패스해버림
            $logged_info = Context::get('logged_info');

            foreach($extend_form_list as $srl => $item) {
                $column_name = $item->column_name;
                $value = $member_info->{$column_name};

                if($logged_info->is_admin != 'Y' && $logged_info->member_srl != $member_info->member_srl && $member_info->{'open_'.$column_name}!='Y') {
                    $extend_form_list[$srl]->is_private = true;
                    continue;
                }

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

                if($member_info->{'open_'.$column_name}=='Y') $extend_form_list[$srl]->is_opened = true;
                else $extend_form_list[$srl]->is_opened = false;
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
         * @brief 프로필 이미지의 정보를 구함 
         **/
        function getProfileImage($member_srl) {
            if(!isset($GLOBALS['__member_info__']['profile_image'][$member_srl])) {
                $GLOBALS['__member_info__']['profile_image'][$member_srl] = null;
                $exts = array('gif','jpg','png');
                for($i=0;$i<3;$i++) {
                    $image_name_file = sprintf('files/member_extra_info/profile_image/%s%d.%s', getNumberingPath($member_srl), $member_srl, $exts[$i]);
                    if(file_exists($image_name_file)) {
                        list($width, $height, $type, $attrs) = getimagesize($image_name_file);
                        $info = null;
                        $info->width = $width;
                        $info->height = $height;
                        $info->src = Context::getRequestUri().$image_name_file;
                        $info->file = './'.$image_name_file;
                        $GLOBALS['__member_info__']['profile_image'][$member_srl] = $info;
                        break;
                    }
                }
            }

            return $GLOBALS['__member_info__']['profile_image'][$member_srl];
        }

        /**
         * @brief 이미지이름의 정보를 구함
         **/
        function getImageName($member_srl) {
            if(!isset($GLOBALS['__member_info__']['image_name'][$member_srl])) {
                $image_name_file = sprintf('files/member_extra_info/image_name/%s%d.gif', getNumberingPath($member_srl), $member_srl);
                if(file_exists($image_name_file)) {
                    list($width, $height, $type, $attrs) = getimagesize($image_name_file);
                    $info->width = $width;
                    $info->height = $height;
                    $info->src = Context::getRequestUri().$image_name_file;
                    $info->file = './'.$image_name_file;
                    $GLOBALS['__member_info__']['image_name'][$member_srl] = $info;
                } else $GLOBALS['__member_info__']['image_name'][$member_srl] = null;
            }
            return $GLOBALS['__member_info__']['image_name'][$member_srl];
        }

        /**
         * @brief 이미지마크의 정보를 구함
         **/
        function getImageMark($member_srl) {
            if(!isset($GLOBALS['__member_info__']['image_mark'][$member_srl])) {
                $image_mark_file = sprintf('files/member_extra_info/image_mark/%s%d.gif', getNumberingPath($member_srl), $member_srl);
                if(file_exists($image_mark_file)) {
                    list($width, $height, $type, $attrs) = getimagesize($image_mark_file);
                    $info->width = $width;
                    $info->height = $height;
                    $info->src = Context::getRequestUri().$image_mark_file;
                    $info->file = './'.$image_mark_file;
                    $GLOBALS['__member_info__']['image_mark'][$member_srl] = $info;
                } else $GLOBALS['__member_info__']['image_mark'][$member_srl] = null;
            }
            return $GLOBALS['__member_info__']['image_mark'][$member_srl];
        }

        /**
         * @brief 사용자의 signature를 구함
         **/
        function getSignature($member_srl) {
            if(!isset($GLOBALS['__member_info__']['signature'][$member_srl])) {
                $filename = sprintf('files/member_extra_info/signature/%s%d.signature.php', getNumberingPath($member_srl), $member_srl);
                if(file_exists($filename)) {
                    $buff = FileHandler::readFile($filename);
                    $signature = trim(substr($buff, 40));
                    $GLOBALS['__member_info__']['signature'][$member_srl] = $signature;
                } else $GLOBALS['__member_info__']['signature'][$member_srl] = null;
            }
            return $GLOBALS['__member_info__']['signature'][$member_srl];
        }

        /**
         * @brief 입력된 plain text 비밀번호와 DB에 저장된 비밀번호와의 비교
         **/
        function isValidPassword($hashed_password, $password_text) {
            // 입력된 비밀번호가 없으면 무조건 falase
            if(!$password_text) return false;

            // md5 해쉬된값가 맞으면 return true
            if($hashed_password == md5($password_text)) return true;

            // mysql_pre4_hash_password함수의 값과 동일하면 return true
            if(mysql_pre4_hash_password($password_text) == $hashed_password) return true;

            // 현재 DB에서 mysql DB를 이용시 직접 old_password를 이용하여 검사하고 맞으면 비밀번호를 변경
            if(substr(Context::getDBType(),0,5)=='mysql') {
                $oDB = &DB::getInstance();
                if($oDB->isValidOldPassword($password_text, $hashed_password)) return true;
            }

            return false;
        }
    }
?>
