<?php
  /**
   * @file   : modules/member/member.admin.php
   * @author : zero <zero@nzeo.com>
   * @desc   : member의 관리자 파일
   *           Module class에서 상속을 받아서 사용
   *           action 의 경우 disp/proc 2가지만 존재하며 이는 action명세서에 
   *           미리 기록을 하여야 함
   **/

  class member_admin extends Module {

    /**
     * 기본 action 지정
     * $act값이 없거나 잘못된 값이 들어올 경우 $default_act 값으로 진행
     **/
    var $default_act = 'dispContent';

    /**
     * 현재 모듈의 초기화를 위한 작업을 지정해 놓은 method
     * css/js파일의 load라든지 lang파일 load등을 미리 선언
     *
     * Init() => 공통 
     * dispInit() => disp시에
     * procInit() => proc시에
     *
     * $this->module_path는 현재 이 모듈파일의 위치를 나타낸다
     * (ex: $this->module_path = "./modules/system_install/";
     **/

    // 초기화
    function init() {/*{{{*/
      // 기본 정보를 읽음
      Context::loadLang($this->module_path.'lang');
    }/*}}}*/

    // disp 초기화
    function dispInit() {/*{{{*/
      $oMember = getModule('member');

      // member_srl이 있으면 미리 체크하여 member_info 세팅
      $member_srl = Context::get('member_srl');
      if($member_srl) {
        $member_info = $oMember->getMemberInfoByMemberSrl($member_srl);
        if(!$member_info) {
          Context::set('member_srl','');
          $this->act = 'dispContent';
        } else Context::set('member_info',$member_info);
      }

      // group 목록 가져오기
      $group_list = $oMember->getGroups();
      Context::set('group_list', $group_list);

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
     **/

    // 출력 부분
    function dispContent() {/*{{{*/

      // 등록된 member 모듈을 불러와 세팅
      $oDB = &DB::getInstance();
      $args->sort_index = "member_srl";
      $args->page = Context::get('page');
      $args->list_count = 40;
      $args->page_count = 10;
      $output = $oDB->executeQuery('member.getMemberList', $args);

      // 템플릿에 쓰기 위해서 context::set
      Context::set('total_count', $output->total_count);
      Context::set('total_page', $output->total_page);
      Context::set('page', $output->page);
      Context::set('member_list', $output->data);
      Context::set('page_navigation', $output->page_navigation);

      // 템플릿 파일 지정
      $this->setTemplateFile('list');
    }/*}}}*/

    function dispInfo() {/*{{{*/
      // 템플릿 파일 지정
      $this->setTemplateFile('member_info');
    }/*}}}*/

    function dispInsert() {/*{{{*/
      // 템플릿 파일 지정
      $this->setTemplateFile('insert_member');
    }/*}}}*/

    function dispDeleteForm() {/*{{{*/
      if(!Context::get('member_srl')) return $this->dispContent();

      // 템플릿 파일 지정
      $this->setTemplateFile('delete_form');
    }/*}}}*/

    function dispGroup() {/*{{{*/
      // 그룹 목록 가져오기
      $oMember = getModule('member');
      $group_list = $oMember->getGroups();
      Context::set('group_list', $group_list);

      // 선택된 gruop_srl이 있으면 selected_group에 담기
      $group_srl = Context::get('group_srl');
      if($group_srl && $group_list[$group_srl]) {
        Context::set('selected_group', $group_list[$group_srl]);
        $this->setTemplateFile('group_update_form');
      } else {
        $this->setTemplateFile('group_list');
      }
      
    }/*}}}*/

    function dispDeniedID() {/*{{{*/
      // 사용금지 목록 가져오기
      $oMember = getModule('member');
      $output = $oMember->getDeniedIDList();

      // 템플릿에 쓰기 위해서 context::set
      Context::set('total_count', $output->total_count);
      Context::set('total_page', $output->total_page);
      Context::set('page', $output->page);
      Context::set('member_list', $output->data);
      Context::set('page_navigation', $output->page_navigation);

      // 템플릿 파일 지정
      $this->setTemplateFile('denied_list');
    }/*}}}*/

    // 실행 부분
    function procInsert() {/*{{{*/
      // 일단 입력된 값들을 모두 받아서 db 입력항목과 그외 것으로 분리
      $args = Context::gets('member_srl','user_id','user_name','nick_name','email_address','password','allow_mailing','denied','is_admin','signature','profile_image','image_nick','image_mark','description','group_srl_list');

      // member_srl이 있으면 원본을 구해온다
      $oMember = getModule('member');

      // member_srl이 넘어오면 원 모듈이 있는지 확인
      if($args->member_srl) {
        $member_info = $oMember->getMemberInfoByMemberSrl($args->member_srl);
        // 만약 원래 모듈이 없으면 새로 입력하기 위한 처리
        if($member_info->member_srl != $args->member_srl) unset($args->member_srl);
      }

      // member_srl의 값에 따라 insert/update
      if(!$args->member_srl) {
        $output = $oMember->insertMember($args);
        $msg_code = 'success_registed';
      } else {
        $output = $oMember->updateMember($args);
        $msg_code = 'success_updated';
      }

      if(!$output->toBool()) return $output;

      $this->add('sid','member');
      $this->add('member_srl',$output->get('member_srl'));
      $this->add('act','dispInfo');
      $this->add('page',Context::get('page'));
      $this->setMessage($msg_code);
    }/*}}}*/

    function procDelete() {/*{{{*/
      // 일단 입력된 값들을 모두 받아서 db 입력항목과 그외 것으로 분리
      $member_srl = Context::get('member_srl');

      // member_srl이 있으면 원본을 구해온다
      $oMember = getModule('member');
      $output = $oMember->deleteMember($member_srl);
      if(!$output->toBool()) return $output;

      $this->add('sid','member');
      $this->add('page',Context::get('page'));
      $this->setMessage("success_deleted");
    }/*}}}*/

    function procInsertGroup() {/*{{{*/
      $args = Context::gets('title','description','is_default');
      $oMember = getModule('member');
      $output = $oMember->insertGroup($args);
      if(!$output->toBool()) return $output;

      $this->add('sid','member');
      $this->add('act','dispGroup');
      $this->add('group_srl','');
      $this->add('page',Context::get('page'));
      $this->setMessage('success_registed');
    }/*}}}*/

    function procUpdateGroup() {/*{{{*/
      $group_srl = Context::get('group_srl');
      $mode = Context::get('mode');

      $oMember = getModule('member');

      switch($mode) {
        case 'delete' :
            $output = $oMember->deleteGroup($group_srl);
            if(!$output->toBool()) return $output;
            $msg_code = 'success_deleted';
          break;
        case 'update' :
            $args = Context::gets('group_srl','title','description','is_default');
            $output = $oMember->updateGroup($args);
            if(!$output->toBool()) return $output;
            $msg_code = 'success_updated';
          break;
      }

      $this->add('sid','member');
      $this->add('act','dispGroup');
      $this->add('group_srl','');
      $this->add('page',Context::get('page'));
      $this->setMessage($msg_code);
    }/*}}}*/

    function procInsertDeniedID() {/*{{{*/
      $user_id = Context::get('user_id');
      $description = Context::get('description');
      $oMember = getModule('member');
      $output = $oMember->insertDeniedID($user_id, $description);
      if(!$output->toBool()) return $output;

      $this->add('sid','member');
      $this->add('act','dispDeniedID');
      $this->add('group_srl','');
      $this->add('page',Context::get('page'));
      $this->setMessage('success_registed');
    }/*}}}*/

    function procUpdateDeniedID() {/*{{{*/
      $user_id = Context::get('user_id');
      $mode = Context::get('mode');

      $oMember = getModule('member');

      switch($mode) {
        case 'delete' :
            $output = $oMember->deleteDeniedID($user_id);
            if(!$output->toBool()) return $output;
            $msg_code = 'success_deleted';
          break;
      }

      $this->add('sid','member');
      $this->add('act','dispDeniedID');
      $this->add('page',Context::get('page'));
      $this->setMessage($msg_code);
    }/*}}}*/
    /**
     * 여기부터는 이 모듈과 관련된 라이브러리 개념의 method들
     **/
  }
?>
