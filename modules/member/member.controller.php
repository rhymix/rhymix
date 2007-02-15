<?php
  /**
   * @file   : modules/member/member.module.php
   * @author : zero <zero@nzeo.com>
   * @desc   : 기본 모듈중의 하나인 member module
   *           Module class에서 상속을 받아서 사용
   *           action 의 경우 disp/proc 2가지만 존재하며 이는 action명세서에 
   *           미리 기록을 하여야 함
   **/

  class member extends Module {

    /**
     * 모듈의 정보
     **/
    var $cur_version = "20070130_0.01";

    /**
     * 기본 action 지정
     * $act값이 없거나 잘못된 값이 들어올 경우 $default_act 값으로 진행
     **/
    var $default_act = '';

    /**
     * 현재 모듈의 초기화를 위한 작업을 지정해 놓은 method
     * css/js파일의 load라든지 lang파일 load등을 미리 선언
     *
     * Init() => 공통 
     * dispInit() => disp시에
     * procInit() => proc시에
     *
     * $this->module_path는 현재 이 모듈파일의 위치를 나타낸다
     * (ex: $this->module_path = "./modules/member/";
     **/

    // 초기화
    function init() {/*{{{*/
      Context::loadLang($this->module_path.'lang');
    }/*}}}*/

    // disp 초기화
    function dispInit() {/*{{{*/
      return true;
    }/*}}}*/
    
    // proc 초기화
    function procInit() {/*{{{*/
      return true;
    }/*}}}*/

    /**
     * 여기서부터는 action의 구현
     * request parameter의 경우 각 method의 첫번째 인자로 넘어온다
     *
     * dispXXXX : 출력을 위한 method, output에 tpl file이 지정되어야 한다
     * procXXXX : 처리를 위한 method, output에는 error, message가 지정되어야 한다
     *
     * 변수의 사용은 Context::get('이름')으로 얻어오면 된다
     **/

    /**
     * 여기부터는 이 모듈과 관련된 라이브러리 개념의 method들
     **/

    // 로그인/로그아웃 처리
    // public void doLogin($user_id, $password) /*{{{*/
    // user_id, password를 체크하여 로그인 시킴
    function doLogin($user_id, $password) {
      // 변수 정리
      $user_id = trim($user_id);
      $password = trim($password);

      // 이메일 주소나 비밀번호가 없을때 오류 return
      if(!$user_id) return new Output(-1,Context::getLang('null_user_id'));
      if(!$password) return new Output(-1,Context::getLang('null_password'));

      $oDB = &DB::getInstance();

      // user_id 에 따른 정보 가져옴
      $args->user_id = $user_id;
      $member_info = $this->getMemberInfo($user_id, false);

      // return 값이 없거나 비밀번호가 틀릴 경우
      if($member_info->user_id != $user_id) return new Output(-1, Context::getLang('invalid_user_id'));
      if($member_info->password != md5($password)) return new Output(-1, Context::getLang('invalid_password'));

      // 로그인 처리
      $_SESSION['is_logged'] = true;
      $_SESSION['ipaddress'] = $_SERVER['REMOTE_ADDR'];

      unset($member_info->password);

      // 세션에 로그인 사용자 정보 저장
      $_SESSION['member_srl'] = $member_info->member_srl;
      $_SESSION['logged_info'] = $member_info;

      // 사용자 정보의 최근 로그인 시간을 기록
      $args->member_srl = $member_info->member_srl;
      $oDB->executeQuery('member.updateLastLogin', $args);

      return new Output();
    }/*}}}*/

    // public void doLogout() /*{{{*/
    // 로그아웃
    function doLogout() {
      // 로그인 처리
      $_SESSION['is_logged'] = false;
      $_SESSION['ipaddress'] = $_SERVER['REMOTE_ADDR'];
      $_SESSION['logged_info'] = NULL;
      return new Output();
    }/*}}}*/

    // 사용자 정보 
    // public object getLoggedInfo()/*{{{*/
    // user_id에 해당하는 사용자 정보 return
    function getLoggedInfo() {
      // 로그인 되어 있고 세션 정보를 요청하면 세션 정보를 return
      if($this->isLogged()) return $_SESSION['logged_info'];
    }/*}}}*/

    // public object getMemberInfo($user_id)/*{{{*/
    // user_id에 해당하는 사용자 정보 return
    function getMemberInfo($user_id) {
      // DB에서 가져오기
      $oDB = &DB::getInstance();
      $args->user_id = $user_id;
      $output = $oDB->executeQuery('member.getMemberInfo', $args);
      if(!$output) return $output;

      $member_info = $output->data;
      $member_info->group_list = $this->getMemberGroups($member_info->member_srl);

      return $member_info;
    }/*}}}*/

    // public object getMemberInfoByMemberSrl($member_srl)/*{{{*/
    // user_id에 해당하는 사용자 정보 return
    function getMemberInfoByMemberSrl($member_srl) {
      // DB에서 가져오기
      $oDB = &DB::getInstance();
      $args->member_srl = $member_srl;
      $output = $oDB->executeQuery('member.getMemberInfoByMemberSrl', $args);
      if(!$output) return $output;

      $member_info = $output->data;
      $member_info->group_list = $this->getMemberGroups($member_info->member_srl);

      return $member_info;
    }/*}}}*/

    // public int getMemberSrl() /*{{{*/
    // 현재 접속자의 member_srl을 return
    function getMemberSrl() {
      if(!$this->isLogged()) return;
      return $_SESSION['member_srl'];
    }/*}}}*/

    // public int getUserID() /*{{{*/
    // 현재 접속자의 user_id을 return
    function getUserID() {
      if(!$this->isLogged()) return;
      $logged_info = $_SESSION['logged_info'];
      return $logged_info->user_id;
    }/*}}}*/


    // member 정보 입출력 관련 
    // public void insertAdmin($args)/*{{{*/
    // 관리자를 추가한다
    function insertAdmin($args) {
      $args->is_admin = 'Y';
      return $this->insertMember($args);
    }/*}}}*/

    // public void insertMember($args)/*{{{*/
    // member 테이블에 사용자 추가
    function insertMember($args) {
      // 필수 변수들의 조절
      if($args->allow_mailing!='Y') $args->allow_mailing = 'N';
      if($args->denied!='Y') $args->denied = 'N';
      if($args->is_admin!='Y') $args->is_admin = 'N';
      list($args->email_id, $args->email_host) = explode('@', $args->email_address);

      // 금지 아이디인지 체크
      if($this->chkDeniedID($args->user_id)) return new Output(-1,'denied_user_id');

      // 아이디, 닉네임, email address 의 중복 체크
      $member_srl = $this->getMemberSrlByUserID($args->user_id);
      if($member_srl) return new Output(-1,'msg_exists_user_id');

      $member_srl = $this->getMemberSrlByNickName($args->nick_name);
      if($member_srl) return new Output(-1,'msg_exists_nick_name');

      $member_srl = $this->getMemberSrlByEmailAddress($args->email_address);
      if($member_srl) return new Output(-1,'msg_exists_email_address');

      // DB 입력
      $oDB = &DB::getInstance();
      $args->member_srl = $oDB->getNextSequence();
      if($args->password) $args->password = md5($args->password);
      else unset($args->password);
      $output = $oDB->executeQuery('member.insertMember', $args);
      if(!$output->toBool()) return $output;

      // 기본 그룹을 입력
      $default_group = $this->getDefaultGroup();

      // 기본 그룹에 추가
      $output = $this->addMemberToGroup($args->member_srl,$default_group->group_srl);
      if(!$output->toBool()) return $output;

      $output->add('member_srl', $args->member_srl);
      return $output;
    }/*}}}*/

    // public void updateMember($args)/*{{{*/
    // member 정보 수정
    function updateMember($args) {
      $member_info = $this->getMemberInfoByMemberSrl($args->member_srl);

      // 필수 변수들의 조절
      if($args->allow_mailing!='Y') $args->is_default = 'N';
      if($args->denied!='Y') $args->denied = 'N';
      if($args->is_admin!='Y') $args->use_category = 'N';
      list($args->email_id, $args->email_host) = explode('@', $args->email_address);

      // 아이디, 닉네임, email address 의 중복 체크
      $member_srl = $this->getMemberSrlByUserID($args->user_id);
      if($member_srl&&$args->member_srl!=$member_srl) return new Output(-1,'msg_exists_user_id');
      $member_srl = $this->getMemberSrlByNickName($args->nick_name);
      if($member_srl&&$args->member_srl!=$member_srl) return new Output(-1,'msg_exists_nick_name');
      $member_srl = $this->getMemberSrlByEmailAddress($args->email_address);
      if($member_srl&&$args->member_srl!=$member_srl) return new Output(-1,'msg_exists_email_address');

      // DB 입력
      $oDB = &DB::getInstance();
      if($args->password) $args->password = md5($args->password);
      else $args->password = $member_info->password;

      $output = $oDB->executeQuery('member.updateMember', $args);
      if(!$output->toBool()) return $output;

      // 그룹에 추가
      $output = $oDB->executeQuery('member.deleteMemberGroupMember', $args);
      if(!$output->toBool()) return $output;

      $group_srl_list = explode(',', $args->group_srl_list);
      for($i=0;$i<count($group_srl_list);$i++) {
        $output = $this->addMemberToGroup($args->member_srl,$group_srl_list[$i]);
        if(!$output->toBool()) return $output;
      }

      $output->add('member_srl', $args->member_srl);
      return $output;
    }/*}}}*/

    // public void deleteMember($member_srl)/*{{{*/
    // 사용자 삭제
    function deleteMember($member_srl) {

      $oDB = &DB::getInstance();

      // 해당 사용자의 정보를 가져옴
      $member_info = $this->getMemberInfoByMemberSrl($member_srl);
      if(!$member_info) return new Output(-1, 'msg_not_exists_member');

      // 관리자의 경우 삭제 불가능
      if($member_info->is_admin == 'Y') return new Output(-1, 'msg_cannot_delete_admin');

      // member_group_member에서 해당 항목들 삭제
      $args->member_srl = $member_srl;
      $output = $oDB->executeQuery('member.deleteMemberGroupMember', $args);
      if(!$output->toBool()) return $output;

      // member 테이블에서 삭제
      $output = $oDB->executeQuery('member.deleteMember', $args);
      return $output;
    }/*}}}*/

    // public boolean isLogged() {/*{{{*/
    // 로그인 되어 있는지에 대한 체크
    function isLogged() {
      if($_SESSION['is_logged']&&$_SESSION['ipaddress']==$_SERVER['REMOTE_ADDR']) return true;

      $_SESSION['is_logged'] = false;
      $_SESSION['logged_info'] = '';
      return false;
    }/*}}}*/

    // group 관련
    // public object addMemberToGroup($member_srl, $group_srl) /*{{{*/
    // member_srl에 gruop_srl을 추가
    function addMemberToGroup($member_srl,$group_srl) {
      $args->member_srl = $member_srl;
      $args->group_srl = $group_srl;

      $oDB = &DB::getInstance();

      // 추가
      $output = $oDB->executeQuery('member.addMemberToGroup',$args);
      if(!$output->toBool()) return $output;

      return $output;
    }/*}}}*/

    // public void changeGroup($source_group_srl, $target_group_srl)/*{{{*/
    // 회원의 그룹값을 변경
    function changeGroup($source_group_srl, $target_group_srl) {
      $oDB = &DB::getInstance();
      $args->source_group_srl = $source_group_srl;
      $args->target_group_srl = $target_group_srl;
      return $oDB->executeQuery('member.changeGroup', $args);
    }/*}}}*/

    // public object getMemberGroups($member_srl) /*{{{*/
    // member_srl이 속한 group 목록을 가져옴
    function getMemberGroups($member_srl) {
      $oDB = &DB::getInstance();
      $args->member_srl = $member_srl;
      $output = $oDB->executeQuery('member.getMemberGroups', $args);
      if(!$output->data) return;

      $group_list = $output->data;
      if(!is_array($group_list)) $group_list = array($group_list);
      foreach($group_list as $group) {
        $result[$group->group_srl] = $group->title;
      }
      return $result;
    }/*}}}*/

    // public object getDefaultGroup() /*{{{*/
    // 기본 그룹을 가져옴
    function getDefaultGroup() {
      $oDB = &DB::getInstance();
      $output = $oDB->executeQuery('member.getDefaultGroup');
      return $output->data;
    }/*}}}*/

    // public object getGroup($group_srl) /*{{{*/
    // group_srl에 해당하는 그룹 정보 가져옴
    function getGroup($group_srl) {
      $oDB = &DB::getInstance();
      $args->group_srl = $group_srl;
      $output = $oDB->executeQuery('member.getGroup', $args);
      return $output->data;
    }/*}}}*/

    // public object getGroups() /*{{{*/
    // 그룹 목록을 가져옴
    function getGroups() {
      $oDB = &DB::getInstance();
      $output = $oDB->executeQuery('member.getGroups');
      if(!$output->data) return;

      $group_list = $output->data;
      if(!is_array($group_list)) $group_list = array($group_list);
      foreach($group_list as $val) {
        $result[$val->group_srl] = $val;
      }
      return $result;
    }/*}}}*/

    // public object insertGroup() /*{{{*/
    // 그룹 등록
    function insertGroup($args) {
      $oDB = &DB::getInstance();

      // is_default값을 체크, Y일 경우 일단 모든 is_default에 대해서 N 처리
      if($args->is_default!='Y') $args->is_default = 'N';
      else $oDB->executeQuery('member.updateGroupDefaultClear');

      $output = $oDB->executeQuery('member.insertGroup', $args);
      return $output;
    }/*}}}*/

    // public object updateGroup() /*{{{*/
    // 그룹 등록
    function updateGroup($args) {
      $oDB = &DB::getInstance();
      // is_default값을 체크, Y일 경우 일단 모든 is_default에 대해서 N 처리
      if($args->is_default!='Y') $args->is_default = 'N';
      else {
        $oDB->executeQuery('member.updateGroupDefaultClear');
      }

      $output = $oDB->executeQuery('member.updateGroup', $args);
      return $output;
    }/*}}}*/

    // public object deleteGroup($group_srl) /*{{{*/
    // 그룹 등록
    function deleteGroup($group_srl) {
      // 삭제 대상 그룹을 가져와서 체크 (is_default == 'Y'일 경우 삭제 불가)
      $group_info = $this->getGroup($group_srl);
      if(!$group_info) return new Output(-1, 'lang->msg_not_founded');
      if($group_info->is_default == 'Y') return new Output(-1, 'msg_not_delete_default');

      // is_default == 'Y'인 그룹을 가져옴
      $default_group = $this->getDefaultGroup();
      $default_group_srl = $default_group->group_srl;

      // default_group_srl로 변경
      $this->changeGroup($group_srl, $default_group_srl);

      // 그룹 삭제
      $oDB = &DB::getInstance();
      $args->group_srl = $group_srl;
      $output = $oDB->executeQuery('member.deleteGroup', $args);
      return $output;
    }/*}}}*/

    // 금지 아이디
    // public object getDeniedIDList() /*{{{*/
    // 금지 아이디 목록 가져오기
    function getDeniedIDList() {
      $oDB = &DB::getInstance();
      $args->sort_index = "list_order";
      $args->page = Context::get('page');
      $args->list_count = 40;
      $args->page_count = 10;
      $output = $oDB->executeQuery('member.getDeniedIDList', $args);
      return $output;
    }/*}}}*/

    // public object insertDeniedID() /*{{{*/
    // 금지아이디 등록
    function insertDeniedID($user_id, $desription = '') {
      $oDB = &DB::getInstance();

      $args->user_id = $user_id;
      $args->description = $description;
      $args->list_order = -1*$oDB->getNextSequence();

      return $oDB->executeQuery('member.insertDeniedID', $args);
    }/*}}}*/

    // public object deleteDeniedID() /*{{{*/
    // 금지아이디 등록
    function deleteDeniedID($user_id) {
      $oDB = &DB::getInstance();

      $args->user_id = $user_id;
      return $oDB->executeQuery('member.deleteDeniedID', $args);
    }/*}}}*/

    // public object chkDeniedID($user_id) /*{{{*/
    // 금지아이디 등록
    function chkDeniedID($user_id) {
      $oDB = &DB::getInstance();

      $args->user_id = $user_id;

      $output = $oDB->executeQuery('member.chkDeniedID', $args);
      if($output->data->count) return true;
      return false;
    }/*}}}*/

    // 기타
    // public boolean getMemberSrlByUserID($user_id) {/*{{{*/
    // userid에 해당하는 member_srl을 구함
    function getMemberSrlByUserID($user_id) {
      $oDB = &DB::getInstance();
      $args->user_id = $user_id;
      $output = $oDB->executeQuery('member.getMemberSrl', $args);
      return $output->data->member_srl;
    }/*}}}*/

    // public boolean getMemberSrlByEmailAddress($email_address) {/*{{{*/
    // userid에 해당하는 member_srl을 구함
    function getMemberSrlByEmailAddress($email_address) {
      $oDB = &DB::getInstance();
      $args->email_address = $email_address;
      $output = $oDB->executeQuery('member.getMemberSrl', $args);
      return $output->data->member_srl;
    }/*}}}*/

    // public boolean getMemberSrlByNickName($nick_name) {/*{{{*/
    // userid에 해당하는 member_srl을 구함
    function getMemberSrlByNickName($nick_name) {
      $oDB = &DB::getInstance();
      $args->nick_name = $nick_name;
      $output = $oDB->executeQuery('member.getMemberSrl', $args);
      return $output->data->member_srl;
    }/*}}}*/
  }
?>
