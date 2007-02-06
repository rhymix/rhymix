<?php
  /**
   * @file   : modules/board/board.admin.php
   * @author : zero <zero@nzeo.com>
   * @desc   : board의 관리자 파일
   *           Module class에서 상속을 받아서 사용
   *           action 의 경우 disp/proc 2가지만 존재하며 이는 action명세서에 
   *           미리 기록을 하여야 함
   **/

  class board_admin extends Module {

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

      // 스킨의 종류를 읽음
      $oModule = getModule('module_manager');
      $skins = $oModule->getSkins($this->module_path);
      Context::set('skins', $skins);
    }/*}}}*/

    // disp 초기화
    function dispInit() {/*{{{*/
      // module_srl이 있으면 미리 체크하여 존재하는 모듈이면 module_info 세팅
      $module_srl = Context::get('module_srl');
      if($module_srl) {
        $oModule = getModule('module_manager');
        $module_info = $oModule->getModuleInfoByModuleSrl($module_srl);
        if(!$module_info) {
          Context::set('module_srl','');
          $this->act = 'dispContent';
        } else Context::set('module_info',$module_info);
      }

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

      // 등록된 board 모듈을 불러와 세팅
      $oDB = &DB::getInstance();
      $args->sort_index = "module_srl";
      $args->page = Context::get('page');
      $args->list_count = 40;
      $args->page_count = 10;
      $output = $oDB->executeQuery('board.getBoardList', $args);

      // 템플릿에 쓰기 위해서 context::set
      Context::set('total_count', $output->total_count);
      Context::set('total_page', $output->total_page);
      Context::set('page', $output->page);
      Context::set('board_list', $output->data);
      Context::set('page_navigation', $output->page_navigation);

      // 템플릿 파일 지정
      $this->setTemplateFile('list');
    }/*}}}*/

    function dispInfo() {/*{{{*/
      if(!Context::get('module_srl')) return $this->dispContent();

      // 템플릿 파일 지정
      $this->setTemplateFile('info');
    }/*}}}*/

    function dispCategoryInfo() {/*{{{*/
      $module_srl = Context::get('module_srl');

      // 카테고리의 목록을 구해옴
      $oDocument = getModule('document');
      $category_list = $oDocument->getCategoryList($module_srl);
      Context::set('category_list', $category_list);

      // 수정하려는 카테고리가 있다면해당 카테고리의 정보를 가져옴
      $category_srl = Context::get('category_srl');
      if($category_srl) {
        $selected_category = $oDocument->getCategory($category_srl);
        if(!$selected_category) Context::set('category_srl','');
        else Context::set('selected_category',$selected_category);
        $this->setTemplateFile('category_update_form');
      } else {
        $this->setTemplateFile('category_list');
      }
    }/*}}}*/

    function dispGrantInfo() {/*{{{*/
      $module_srl = Context::get('module_srl');

      // 현 모듈의 권한 목록을 가져옴
      $oBoard = getModule('board');
      $grant_list = $oBoard->grant_list;

      // 권한 목록 세팅
      Context::set('grant_list', $grant_list);

      // 권한 그룹의 목록을 가져온다
      $oMember = getModule('member');
      $group_list = $oMember->getGroups();
      Context::set('group_list', $group_list);

      $this->setTemplateFile('grant_list');
    }/*}}}*/

    function dispSkinInfo() {/*{{{*/
      // 현재 선택된 모듈의 스킨의 정보 xml 파일을 읽음
      $module_info = Context::get('module_info');
      $skin = $module_info->skin;

      $oModule = getModule('module_manager');
      $skin_info = $oModule->loadSkinInfo($this->module_path, $skin);

      // skin_info에 extra_vars 값을 지정
      if(count($skin_info->extra_vars)) {
        foreach($skin_info->extra_vars as $key => $val) {
          $name = $val->name;
          $type = $val->type;
          $value = $module_info->{$name};
          if($type=="checkbox"&&!$value) $value = array();
          $skin_info->extra_vars[$key]->value= $value;
        }
      }

      Context::set('skin_info', $skin_info);
      $this->setTemplateFile('skin_info');
    }/*}}}*/

    function dispInsert() {/*{{{*/
      // 템플릿 파일 지정
      $this->setTemplateFile('insert_form');
    }/*}}}*/

    function dispDeleteForm() {/*{{{*/
      if(!Context::get('module_srl')) return $this->dispContent();

      $module_info = Context::get('module_info');

      $oDocument = getModule('document');
      $document_count = $oDocument->getDocumentCount($module_info->module_srl);
      $module_info->document_count = $document_count;

      Context::set('module_info',$module_info);

      // 템플릿 파일 지정
      $this->setTemplateFile('delete_form');
    }/*}}}*/

    // 실행 부분
    function procInsert() {/*{{{*/
      // 일단 입력된 값들을 모두 받아서 db 입력항목과 그외 것으로 분리
      $args = Context::gets('module_srl','mid','skin','use_category','browser_title','description','is_default','header_text','footer_text','admin_id');
      $args->module = 'board';
      if($args->is_default!='Y') $args->is_default = 'N';
      if($args->use_category!='Y') $args->use_category = 'N';

      // 기본 값외의 것들을 정리
      $extra_var = delObjectVars(Context::getRequestVars(), $args);
      unset($extra_var->sid);
      unset($extra_var->act);
      unset($extra_var->page);

      // module_srl이 있으면 원본을 구해온다
      $oModule = getModule('module_manager');

      // module_srl이 넘어오면 원 모듈이 있는지 확인
      if($args->module_srl) {
        $module_info = $oModule->getModuleInfoByModuleSrl($args->module_srl);
        // 만약 원래 모듈이 없으면 새로 입력하기 위한 처리
        if($module_info->module_srl != $args->module_srl) unset($args->module_srl);
      }

      // $extra_var를 serialize
      $args->extra_var = serialize($extra_var);

      // is_default=='Y' 이면
      if($args->is_default=='Y') $oModule->clearDefaultModule();

      // module_srl의 값에 따라 insert/update
      if(!$args->module_srl) {
        $output = $oModule->insertModule($args);
        $msg_code = 'success_registed';
      } else {
        $output = $oModule->updateModule($args);
        $msg_code = 'success_updated';
      }

      if(!$output->toBool()) return $output;

      $this->add('sid','board');
      $this->add('act','dispInfo');
      $this->add('page',Context::get('page'));
      $this->add('module_srl',$output->get('module_srl'));
      $this->setMessage($msg_code);
    }/*}}}*/

    function procDelete() {/*{{{*/
      $module_srl = Context::get('module_srl');

      // 원본을 구해온다
      $oModule = getModule('module_manager');
      $output = $oModule->deleteModule($module_srl);
      if(!$output->toBool()) return $output;

      $this->add('sid','board');
      $this->add('act','dispContent');
      $this->add('page',Context::get('page'));
      $this->setMessage('success_deleted');
    }/*}}}*/

    function procInsertCategory() {/*{{{*/
      // 일단 입력된 값들을 모두 받아서 db 입력항목과 그외 것으로 분리
      $module_srl = Context::get('module_srl');
      $category_title = Context::get('category_title');

      // module_srl이 있으면 원본을 구해온다
      $oDocument = getModule('document');
      $output = $oDocument->insertCategory($module_srl, $category_title);
      if(!$output->toBool()) return $output;

      $this->add('sid','board');
      $this->add('act','dispCategoryInfo');
      $this->add('page',Context::get('page'));
      $this->add('module_srl',$module_srl);
      $this->setMessage('success_registed');
    }/*}}}*/

    function procUpdateCategory() {/*{{{*/
      $category_srl = Context::get('category_srl');
      $mode = Context::get('mode');

      $oDocument = getModule('document');

      switch($mode) {
        case 'up' :
            $output = $oDocument->moveCategoryUp($category_srl);
            $msg_code = 'success_moved';
          break;
        case 'down' :
            $output = $oDocument->moveCategoryDown($category_srl);
            $msg_code = 'success_moved';
          break;
        case 'delete' :
            $output = $oDocument->deleteCategory($category_srl);
            $msg_code = 'success_deleted';
          break;
        case 'update' :
            $selected_category = $oDocument->getCategory($category_srl);
            $args->category_srl = $selected_category->category_srl;
            $args->title = Context::get('category_title');
            $args->list_order = $selected_category->list_order;
            $output = $oDocument->updateCategory($args);
            $msg_code = 'success_updated';
          break;
      }
      if(!$output->toBool()) return $output;
      $this->add('module_srl', $selected_category->module_srl);
      $this->setMessage($msg_code);
    }/*}}}*/

    function procUpdateSkinInfo() {/*{{{*/
      // module_srl에 해당하는 정보들을 가져오기
      $module_srl = Context::get('module_srl');
      $oModule = getModule('module_manager');
      $module_info = $oModule->getModuleInfoByModuleSrl($module_srl);
      $skin = $module_info->skin;

      // 스킨의 정볼르 구해옴 (extra_vars를 체크하기 위해서)
      $oModule = getModule('module_manager');
      $skin_info = $oModule->loadSkinInfo($this->module_path, $skin);

      // 입력받은 변수들을 체크 (sid, act, module_srl, page등 기본적인 변수들 없앰)
      $obj = Context::getRequestVars();
      unset($obj->sid);
      unset($obj->act);
      unset($obj->module_srl);
      unset($obj->page);

      // 원 skin_info에서 extra_vars의 type이 image일 경우 별도 처리를 해줌
      if($skin_info->extra_vars) {
        foreach($skin_info->extra_vars as $vars) {
          if($vars->type!='image') continue;

          $image_obj = $obj->{$vars->name};

          // 삭제 요청에 대한 변수를 구함
          $del_var = $obj->{"del_".$vars->name};
          unset($obj->{"del_".$vars->name});
          if($del_var == 'Y') {
            @unlink($module_info->{$vars->name});
            continue;
          }

          // 업로드 되지 않았다면 이전 데이터를 그대로 사용
          if(!$image_obj['tmp_name']) {
            $obj->{$vars->name} = $module_info->{$vars->name};
            continue;
          }

          // 정상적으로 업로드된 파일이 아니면 무시
          if(!is_uploaded_file($image_obj['tmp_name'])) {
            unset($obj->{$vars->name});
            continue;
          }

          // 이미지 파일이 아니어도 무시
          if(!eregi("\.(jpg|jpeg|gif|png)$", $image_obj['name'])) {
            unset($obj->{$vars->name});
            continue;
          }

          // 경로를 정해서 업로드
          $path = sprintf("./files/attach/images/%s/", $module_srl);

          // 디렉토리 생성
          if(!FileHandler::makeDir($path)) return false;

          $filename = $path.$image_obj['name'];

          // 파일 이동
          if(!move_uploaded_file($image_obj['tmp_name'], $filename)) {
            unset($obj->{$vars->name});
            continue;
          }

          // 변수를 바꿈
          unset($obj->{$vars->name});
          $obj->{$vars->name} = $filename;
        }
      }

      // serialize하여 저장
      $extra_vars = serialize($obj);

      $oModule = getModule('module_manager');
      $oModule->updateModuleExtraVars($module_srl, $extra_vars);

      $url = sprintf("./admin.php?sid=%s&module_srl=%s&act=dispSkinInfo&page=%s", 'board', $module_srl, Context::get('page'));
      print "<script type=\"text/javascript\">location.href=\"".$url."\";</script>";
      exit();
    }/*}}}*/

    function procInsertGrant() {/*{{{*/
      $module_srl = Context::get('module_srl');

      // 현 모듈의 권한 목록을 가져옴
      $oBoard = getModule('board');
      $grant_list = $oBoard->grant_list;

      if(count($grant_list)) {
        foreach($grant_list as $grant) {
          $arr_grant[$grant] = explode(',',Context::get($grant));
        }
        $grant = serialize($arr_grant);
      }

      $oModule = getModule('module_manager');
      $oModule->updateModuleGrant($module_srl, $grant);

      $this->add('sid','board');
      $this->add('act','dispGrantInfo');
      $this->add('page',Context::get('page'));
      $this->add('module_srl',Context::get('module_srl'));
      $this->setMessage('success_registed');
    }/*}}}*/

    /**
     * 여기부터는 이 모듈과 관련된 라이브러리 개념의 method들
     **/
  }
?>
