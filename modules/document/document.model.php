<?php
  /**
   * @file   : modules/document/document.module.php
   * @author : zero <zero@nzeo.com>
   * @desc   : 기본 모듈중의 하나인 document module
   *           Module class에서 상속을 받아서 사용
   *           action 의 경우 disp/proc 2가지만 존재하며 이는 action명세서에 
   *           미리 기록을 하여야 함
   **/

  class document extends Module {

    /**
     * 모듈의 정보
     **/
    var $cur_version = "20070130_0.01";

    // 공지사항의 고정된 list_order
    var $notice_list_order = -1000000000;

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
     * (ex: $this->module_path = "./modules/install/";
     **/

    // 초기화
    function init() {/*{{{*/
      //Context::loadLang($this->module_path.'lang');
    }/*}}}*/

    // disp 초기화
    function dispInit() {/*{{{*/
    }/*}}}*/
    
    // proc 초기화
    function procInit() {/*{{{*/
    }/*}}}*/

    /**
     * 여기서부터는 action의 구현
     * request parameter의 경우 각 method의 첫번째 인자로 넘어온다
     *
     * dispXXXX : 출력을 위한 method, output에 tpl file이 지정되어야 한다
     * procXXXX : 처리를 위한 method, output에는 document, document가 지정되어야 한다
     **/

    /**
     * 여기부터는 이 모듈과 관련된 라이브러리 개념의 method들
     **/

    // 문서의 권한 부여 
    // 세션값으로 현 접속상태에서만 사용 가능
    // public void addGrant($document_srl) {/*{{{*/
    function addGrant($document_srl) {
      $_SESSION['own_document'][$document_srl] = true;
    }/*}}}*/

    // public void isGranted($document_srl) {/*{{{*/
    function isGranted($document_srl) {
      return $_SESSION['own_document'][$document_srl];
    }/*}}}*/

    // 문서
    // public object insertDocument($obj)/*{{{*/
    // 문서 입력
    function insertDocument($obj) {
      // 입력
      $oDB = &DB::getInstance();

      // 카테고리가 있나 검사하여 없는 카테고리면 0으로 세팅
      if($obj->category_srl) {
        $category_list = $this->getCategoryList($obj->module_srl);
        if(!$category_list[$obj->category_srl]) $obj->category_srl = 0;
      }

      // 태그 처리
      $oTag = getModule('tag');
      $obj->tags = $oTag->insertTag($obj->module_srl, $obj->document_srl, $obj->tags);

      // 글 입력
      $obj->readed_count = 0;
      $obj->update_order = $obj->list_order = $obj->document_srl * -1;
      if($obj->password) $obj->password = md5($obj->password);

      // 공지사항일 경우 list_order에 무지막지한 값;;을 입력
      if($obj->is_notice=='Y') $obj->list_order = $this->notice_list_order;

      // DB에 입력
      $output = $oDB->executeQuery('document.insertDocument', $obj);

      if(!$output->toBool()) return $output;

      // 성공하였을 경우 category_srl이 있으면 카테고리 update
      if($obj->category_srl) $this->updateCategoryCount($obj->category_srl);

      // return
      $this->addGrant($obj->document_srl);
      $output->add('document_srl',$obj->document_srl);
      $output->add('category_srl',$obj->category_srl);
      return $output;
    }/*}}}*/

    // public object updateDocument($source_obj, $obj)/*{{{*/
    // 문서 수정
    function updateDocument($source_obj, $obj) {
      // 카테고리가 변경되었으면 검사후 없는 카테고리면 0으로 세팅
      if($source_obj->category_srl!=$obj->category_srl) {
        $category_list = $this->getCategoryList($obj->module_srl);
        if(!$category_list[$obj->category_srl]) $obj->category_srl = 0;
      }

      // 태그 처리
      $oTag = getModule('tag');
      $obj->tags = $oTag->insertTag($obj->module_srl, $obj->document_srl, $obj->tags);

      // 수정
      $oDB = &DB::getInstance();
      $obj->update_order = $oDB->getNextSequence() * -1;

      // 공지사항일 경우 list_order에 무지막지한 값을, 그렇지 않으면 document_srl*-1값을
      if($obj->is_notice=='Y') $obj->list_order = $this->notice_list_order;
      else $obj->list_order = $obj->document_srl*-1;

      if($obj->password) $obj->password = md5($obj->password);

      // DB에 입력
      $output = $oDB->executeQuery('document.updateDocument', $obj);

      if(!$output->toBool()) return $output;

      // 성공하였을 경우 category_srl이 있으면 카테고리 update
      if($source_obj->category_srl!=$obj->category_srl) {
        if($source_obj->category_srl) $this->updateCategoryCount($source_obj->category_srl);
        if($obj->category_srl) $this->updateCategoryCount($obj->category_srl);
      }

      $output->add('document_srl',$obj->document_srl);
      return $output;
    }/*}}}*/

    // public object deleteDocument($obj)/*{{{*/
    // 문서 삭제
    function deleteDocument($obj) {
      // 변수 세팅
      $document_srl = $obj->document_srl;
      $category_srl = $obj->category_srl;

      // 기존 문서가 있는지 확인
      $document = $this->getDocument($document_srl);
      if($document->document_srl != $document_srl) return false;

      // 권한이 있는지 확인
      if(!$document->is_granted) return new Output(-1, 'msg_not_permitted');

      // 글 삭제
      $oDB = &DB::getInstance();
      $args->document_srl = $document_srl;
      $output = $oDB->executeQuery('document.deleteDocument', $args);
      if(!$output->toBool()) return $output;

      // 댓글 삭제
      $oComment = getModule('comment');
      $output = $oComment->deleteComments($document_srl);

      // 엮인글 삭제
      $oTrackback = getModule('trackback');
      $output = $oTrackback->deleteTrackbacks($document_srl);

      // 태그 삭제
      $oTag = getModule('tag');
      $oTag->deleteTag($document_srl);

      // 첨부 파일 삭제
      if($document->uploaded_count) $this->deleteFiles($document->module_srl, $document_srl);

      // 카테고리가 있으면 카테고리 정보 변경
      if($document->category_srl) $this->updateCategoryCount($document->category_srl);

      return $output;
    }/*}}}*/

    // public object deleteModuleDocument($module_srl) /*{{{*/
    function deleteModuleDocument($module_srl) {
      $args->module_srl = $module_srl;
      $oDB = &DB::getInstance();
      $output = $oDB->executeQuery('document.deleteModuleDocument', $args);
      return $output;
    }/*}}}*/

    // public object getDocument($document_srl)/*{{{*/
    // 문서 가져오기
    function getDocument($document_srl) {
      // DB에서 가져옴
      $oDB = &DB::getInstance();
      $args->document_srl = $document_srl;
      $output = $oDB->executeQuery('document.getDocument', $args);
      $document = $output->data;

      // 이 문서에 대한 권한이 있는지 확인
      if($this->isGranted($document->document_srl)) {
        $document->is_granted = true;
      } elseif($document->member_srl) {
        $oMember = getModule('member');
        $member_srl = $oMember->getMemberSrl();
        if($member_srl && $member_srl ==$document->member_srl) $document->is_granted = true;
      } 
      return $document;
    }/*}}}*/

    // public object getDocuments($document_srl_list)/*{{{*/
    // 여러개의 문서들을 가져옴 (페이징 아님)
    function getDocuments($document_srl_list) {
      if(is_array($document_srl_list)) $document_srls = implode(',',$document_srl_list);

      // DB에서 가져옴
      $oDB = &DB::getInstance();
      $args->document_srls = $document_srls;
      $output = $oDB->executeQuery('document.getDocuments', $args);
      $document_list = $output->data;
      if(!$document_list) return;

      // 권한 체크
      $oMember = getModule('member');
      $member_srl = $oMember->getMemberSrl();

      $document_count = count($document_list);
      for($i=0;$i<$document_count;$i++) {
        $document = $document_list[$i];

        $is_granted = false;
        if($this->isGranted($document->document_srl)) {
          $is_granted = true;
        } elseif($member_srl && $member_srl == $document->member_srl) {
          $is_granted = true;
        } 
        $document_list[$i]->is_granted = $is_granted;
      }
      return $document_list;
    }/*}}}*/

    // public object getDocumentCount($module_srl, $search_obj = NULL)/*{{{*/
    // module_srl에 해당하는 문서의 전체 갯수를 가져옴
    function getDocumentCount($module_srl, $search_obj = NULL) {
      $oDB = &DB::getInstance();

      $args->module_srl = $module_srl;
      $args->s_title = $search_obj->s_title;
      $args->s_content = $search_obj->s_content;
      $args->s_user_name = $search_obj->s_user_name;
      $args->s_member_srl = $search_obj->s_member_srl;
      $args->s_ipaddress = $search_obj->s_ipaddress;
      $args->s_regdate = $search_obj->s_regdate;
      $output = $oDB->executeQuery('document.getDocumentCount', $args);
      $total_count = $output->data->count;
      return (int)$total_count;
    }/*}}}*/

    // public object getDocumentList($module_srl, $sort_index='list_order', $page=1, $list_order=20, $page_count=10, $search_obj = NULL)/*{{{*/
    // module_srl값을 가지는 문서의 목록을 가져옴
    function getDocumentList($module_srl, $sort_index = 'list_order', $page = 1, $list_count = 20, $page_count = 10, $search_obj = NULL) {
      $args->module_srl = $module_srl;
      $args->s_title = $search_obj->s_title;
      $args->s_content = $search_obj->s_content;
      $args->s_user_name = $search_obj->s_user_name;
      $args->s_member_srl = $search_obj->s_member_srl;
      $args->s_ipaddress = $search_obj->s_ipaddress;
      $args->s_regdate = $search_obj->s_regdate;
      $args->category_srl = $search_obj->category_srl;

      $args->sort_index = $sort_index;
      $args->page = $page;
      $args->list_count = $list_count;
      $args->page_count = $page_count;
      $oDB = &DB::getInstance();
      $output = $oDB->executeQuery('document.getDocumentList', $args);

      if(!count($output->data)) return $output;

      // 권한 체크
      $oMember = getModule('member');
      $member_srl = $oMember->getMemberSrl();

      foreach($output->data as $key => $document) {
        $is_granted = false;
        if($this->isGranted($document->document_srl)) $is_granted = true;
        elseif($member_srl && $member_srl == $document->member_srl) $is_granted = true;
        $output->data[$key]->is_granted = $is_granted;
      }
      return $output;
    }/*}}}*/

    // public object getDocumentPage($document_srl, $module_srl, $list_count)/*{{{*/
    // 해당 document의 page 가져오기, module_srl이 없으면 전체에서..
    function getDocumentPage($document_srl, $module_srl=0, $list_count) {
      $oDB = &DB::getInstance();
      $args->document_srl = $document_srl;
      $args->module_srl = $module_srl;
      $output = $oDB->executeQuery('document.getDocumentPage', $args);
      $count = $output->data->count;
      $page = (int)(($count-1)/$list_count)+1;
      return $page;
    }/*}}}*/

    // public object updateReadedCount($document_srl)/*{{{*/
    // 해당 document의 조회수 증가
    function updateReadedCount($document_srl) {
      if($_SESSION['readed_document'][$document_srl]) return false;
      $oDB = &DB::getInstance();
      $args->document_srl = $document_srl;
      $output = $oDB->executeQuery('document.updateReadedCount', $args);
      return $_SESSION['readed_document'][$document_srl] = true;
    }/*}}}*/

    // public object updateVotedCount($document_srl)/*{{{*/
    // 해당 document의 추천수 증가
    function updateVotedCount($document_srl) {
      if($_SESSION['voted_document'][$document_srl]) return new Output(-1, 'failed_voted');
      $oDB = &DB::getInstance();
      $args->document_srl = $document_srl;
      $output = $oDB->executeQuery('document.updateVotedCount', $args);
      $_SESSION['voted_document'][$document_srl] = true;
      return new Output(0, 'success_voted');
    }/*}}}*/

    // public object updateCommentCount($document_srl, $comment_count)/*{{{*/
    // 해당 document의 댓글 수 증가
    function updateCommentCount($document_srl, $comment_count) {
      $oDB = &DB::getInstance();
      $args->document_srl = $document_srl;
      $args->comment_count = $comment_count;
      $output = $oDB->executeQuery('document.updateCommentCount', $args);
      return new Output();
    }/*}}}*/

    // public object updateTrackbackCount($document_srl, $trackback_count)/*{{{*/
    // 해당 document의 엮인글 수증가
    function updateTrackbackCount($document_srl, $trackback_count) {
      $oDB = &DB::getInstance();
      $args->document_srl = $document_srl;
      $args->trackback_count = $trackback_count;
      $output = $oDB->executeQuery('document.updateTrackbackCount', $args);
      return new Output();
    }/*}}}*/

    // 카테고리 관리
    // public object getCategory($category_srl) /*{{{*/
    function getCategory($category_srl) {
      $args->category_srl = $category_srl;
      $oDB = &DB::getInstance();
      $output = $oDB->executeQuery('document.getCategory', $args);
      return $output->data;
    }/*}}}*/

    // public object getCategoryList($module_srl) /*{{{*/
    function getCategoryList($module_srl) {
      $args->module_srl = $module_srl;
      $args->sort_index = 'list_order';
      $oDB = &DB::getInstance();
      $output = $oDB->executeQuery('document.getCategoryList', $args);
      $category_list = $output->data;
      if(!$category_list) return NULL;
      if(!is_array($category_list)) $category_list = array($category_list);
      $category_count = count($category_list);
      for($i=0;$i<$category_count;$i++) {
        $category_srl = $category_list[$i]->category_srl;
        $list[$category_srl] = $category_list[$i];
      }
      return $list;
    }/*}}}*/

    // public object insertCategory($module_srl, $title) /*{{{*/
    function insertCategory($module_srl, $title) {
      $oDB = &DB::getInstance();
      $args->list_order = $args->category_srl = $oDB->getNextSequence();
      $args->module_srl = $module_srl;
      $args->title = $title;
      $args->document_count = 0;
      return $oDB->executeQuery('document.insertCategory', $args);
    }/*}}}*/

    // public object updateCategory($args) /*{{{*/
    function updateCategory($args) {
      $oDB = &DB::getInstance();
      return $oDB->executeQuery('document.updateCategory', $args);
    }/*}}}*/

    // public object updateCategoryCount($category_srl, $document_count = 0) /*{{{*/
    function updateCategoryCount($category_srl, $document_count = 0) {
      if(!$document_count) $document_count = $this->getCategoryDocumentCount($category_srl);
      $args->category_srl = $category_srl;
      $args->document_count = $document_count;
      $oDB = &DB::getInstance();
      return $oDB->executeQuery('document.updateCategoryCount', $args);
    }/*}}}*/

    // public int getCategoryDocumentCount($category_srl) /*{{{*/
    function getCategoryDocumentCount($category_srl) {
      $args->category_srl = $category_srl;
      $oDB = &DB::getInstance();
      $output = $oDB->executeQuery('document.getCategoryDocumentCount', $args);
      return (int)$output->data->count;
    }/*}}}*/

    // public object deleteCategory($category_srl) /*{{{*/
    function deleteCategory($category_srl) {
      $args->category_srl = $category_srl;
      $oDB = &DB::getInstance();

      // 카테고리 정보를 삭제
      $output = $oDB->executeQuery('document.deleteCategory', $args);
      if(!$output->toBool()) return $output;

      // 현 카테고리 값을 가지는 문서들의 category_srl을 0 으로 세팅
      unset($args);
      $args->target_category_srl = 0;
      $args->source_category_srl = $category_srl;
      $output = $oDB->executeQuery('document.updateDocumentCategory', $args);
      return $output;
    }/*}}}*/

    // public object deleteModuleCategory($module_srl) /*{{{*/
    function deleteModuleCategory($module_srl) {
      $args->module_srl = $module_srl;
      $oDB = &DB::getInstance();
      $output = $oDB->executeQuery('document.deleteModuleCategory', $args);
      return $output;
    }/*}}}*/

    // public object moveCategoryUp($category_srl) /*{{{*/
    function moveCategoryUp($category_srl) {
      // 선택된 카테고리의 정보를 구한다
      $oDB = &DB::getInstance();
      $args->category_srl = $category_srl;
      $output = $oDB->executeQuery('document.getCategory', $args);
      $category = $output->data;
      $list_order = $category->list_order;
      $module_srl = $category->module_srl;

      // 전체 카테고리 목록을 구한다
      $category_list = $this->getCategoryList($module_srl);
      $category_srl_list = array_keys($category_list);
      if(count($category_srl_list)<2) return new Output();

      $prev_category = NULL;
      foreach($category_list as $key => $val) {
        if($key==$category_srl) break;
        $prev_category = $val;
      }

      // 이전 카테고리가 없으면 그냥 return
      if(!$prev_category) return new Output(-1,Context::getLang('msg_category_not_moved'));

      // 선택한 카테고리가 가장 위의 카테고리이면 그냥 return
      if($category_srl_list[0]==$category_srl) return new Output(-1,Context::getLang('msg_category_not_moved'));

      // 선택한 카테고리의 정보
      $cur_args->category_srl = $category_srl;
      $cur_args->list_order = $prev_category->list_order;
      $cur_args->title = $category->title;
      $this->updateCategory($cur_args);

      // 대상 카테고리의 정보
      $prev_args->category_srl = $prev_category->category_srl;
      $prev_args->list_order = $list_order;
      $prev_args->title = $prev_category->title;
      $this->updateCategory($prev_args);

      return new Output();
    }/*}}}*/

    // public object moveCategoryDown($category_srl) /*{{{*/
    function moveCategoryDown($category_srl) {
      // 선택된 카테고리의 정보를 구한다
      $oDB = &DB::getInstance();
      $args->category_srl = $category_srl;
      $output = $oDB->executeQuery('document.getCategory', $args);
      $category = $output->data;
      $list_order = $category->list_order;
      $module_srl = $category->module_srl;

      // 전체 카테고리 목록을 구한다
      $category_list = $this->getCategoryList($module_srl);
      $category_srl_list = array_keys($category_list);
      if(count($category_srl_list)<2) return new Output();

      for($i=0;$i<count($category_srl_list);$i++) {
        if($category_srl_list[$i]==$category_srl) break;
      }

      $next_category_srl = $category_srl_list[$i+1];
      if(!$category_list[$next_category_srl]) return new Output(-1,Context::getLang('msg_category_not_moved'));
      $next_category = $category_list[$next_category_srl];

      // 선택한 카테고리의 정보
      $cur_args->category_srl = $category_srl;
      $cur_args->list_order = $next_category->list_order;
      $cur_args->title = $category->title;
      $this->updateCategory($cur_args);

      // 대상 카테고리의 정보
      $next_args->category_srl = $next_category->category_srl;
      $next_args->list_order = $list_order;
      $next_args->title = $next_category->title;
      $this->updateCategory($next_args);

      return new Output();
    }/*}}}*/

    // 파일 관리
    // public int getFilesCount($document_srl) /*{{{*/
    function getFilesCount($document_srl) {
      $oDB = &DB::getInstance();
      $args->document_srl = $document_srl;
      $output = $oDB->executeQuery('document.getFilesCount', $args);
      return (int)$output->data->count;
    }/*}}}*/

    // public object getFile($file_srl) /*{{{*/
    function getFile($file_srl) {
      $oDB = &DB::getInstance();
      $args->file_srl = $file_srl;
      $output = $oDB->executeQuery('document.getFile', $args);
      return $output->data;
    }/*}}}*/

    // public object getFiles($document_srl) /*{{{*/
    function getFiles($document_srl) {
      $oDB = &DB::getInstance();
      $args->document_srl = $document_srl;
      $args->sort_index = 'file_srl';
      $output = $oDB->executeQuery('document.getFiles', $args);
      $file_list = $output->data;
      if($file_list && !is_array($file_list)) $file_list = array($file_list);
      for($i=0;$i<count($file_list);$i++) {
        $direct_download = $file_list[$i]->direct_download;
        if($direct_download!='Y') continue;
        $uploaded_filename = Context::getRequestUri().substr($file_list[$i]->uploaded_filename,2);
        $file_list[$i]->uploaded_filename = $uploaded_filename;
      }
      return $file_list;
    }/*}}}*/

    // public object insertFile($module_srl, $document_sr) /*{{{*/
    function insertFile($module_srl, $document_srl) {
      $oDB = &DB::getInstance();

      $file_info = Context::get('file');

      // 정상적으로 업로드된 파일이 아니면 오류 출력
      if(!is_uploaded_file($file_info['tmp_name'])) return false;

      // 이미지인지 기타 파일인지 체크하여 upload path 지정
      if(eregi("\.(jpg|jpeg|gif|png|wmv|mpg|mpeg|avi|swf|flv|mp3|asaf|wav|asx|midi)$", $file_info['name'])) {
        $path = sprintf("./files/attach/images/%s/%s/", $module_srl,$document_srl);
        $filename = $path.$file_info['name'];
        $direct_download = 'Y';
      } else {
        $path = sprintf("./files/attach/binaries/%s/%s/", $module_srl, $document_srl);
        $filename = $path.md5(crypt(rand(1000000,900000), rand(0,100)));
        $direct_download = 'N';
      }

      // 디렉토리 생성
      if(!FileHandler::makeDir($path)) return false;

      // 파일 이동
      if(!move_uploaded_file($file_info['tmp_name'], $filename)) return false;

      // 사용자 정보를 구함
      $oMember = getModule('member');
      $member_srl = $oMember->getMemberSrl();

      // 파일 정보를 정리
      $oDB = &DB::getInstance();
      $args->file_srl = $oDB->getNextSequence();
      $args->document_srl = $document_srl;
      $args->module_srl = $module_srl;
      $args->direct_download = $direct_download;
      $args->source_filename = $file_info['name'];
      $args->uploaded_filename = $filename;
      $args->file_size = filesize($filename);
      $args->comment = NULL;
      $args->member_srl = $member_srl;
      $args->sid = md5($args->source_filename);

      $output = $oDB->executeQuery('document.insertFile', $args);
      if(!$output->toBool()) return $output;
      $output->add('file_srl', $args->file_srl);
      $output->add('file_size', $args->file_size);
      $output->add('source_filename', $args->source_filename);
      return $output;
    }/*}}}*/

    // public object deleteFile($file_srl) /*{{{*/
    function deleteFile($file_srl) {
      $oDB = &DB::getInstance();

      // 파일 정보를 가져옴
      $args->file_srl = $file_srl;
      $output = $oDB->executeQuery('document.getFile', $args);
      if(!$output->toBool()) return $output;
      $file_info = $output->data;
      if(!$file_info) return new Output(-1, 'file_not_founded');

      $source_filename = $output->data->source_filename;
      $uploaded_filename = $output->data->uploaded_filename;

      // DB에서 삭제
      $output = $oDB->executeQuery('document.deleteFile', $args);
      if(!$output->toBool()) return $output;

      // 삭제 성공하면 파일 삭제
      unlink($uploaded_filename);

      return $output;
    }/*}}}*/

    // public object deleteFiles($module_srl, $document_srl) /*{{{*/
    function deleteFiles($module_srl, $document_srl) {
      // DB에서 삭제
      $oDB = &DB::getInstance();
      $args->document_srl = $document_srl;
      $output = $oDB->executeQuery('document.deleteFiles', $args);
      if(!$output->toBool()) return $output;

      // 실제 파일 삭제
      $path[0] = sprintf("./files/attach/images/%s/%s/", $module_srl, $document_srl);
      $path[1] = sprintf("./files/attach/binaries/%s/%s/", $module_srl, $document_srl);

      FileHandler::removeDir($path[0]);
      FileHandler::removeDir($path[1]);

      return $output;
    }/*}}}*/

    // public object deleteModuleFiles($module_srl) /*{{{*/
    function deleteModuleFiles($module_srl) {
      // DB에서 삭제
      $oDB = &DB::getInstance();
      $args->module_srl = $module_srl;
      $output = $oDB->executeQuery('document.deleteModuleFiles', $args);
      if(!$output->toBool()) return $output;

      // 실제 파일 삭제
      $path[0] = sprintf("./files/attach/images/%s/", $module_srl);
      $path[1] = sprintf("./files/attach/binaries/%s/", $module_srl);
      FileHandler::removeDir($path[0]);
      FileHandler::removeDir($path[1]);

      return $output;
    }/*}}}*/

    // 기타 기능
    // public string transContent($content) {/*{{{*/
    // 내용 관리
    // 내용의 플러그인이나 기타 기능에 대한 code를 실제 code로 변경
    function transContent($content) {
      // 멀티미디어 코드의 변환
      $content = preg_replace_callback('!<img([^\>]*)editor_multimedia([^\>]*?)>!is', array('Document','_transMultimedia'), $content);

      // <br> 코드 변환
      $content = str_replace(array("<BR>","<br>","<Br>"),"<br />", $content);

      // <img ...> 코드를 <img ... /> 코드로 변환
      $content = preg_replace('!<img(.*?)(\/){0,1}>!is','<img\\1 />', $content);

      return $content;
    }/*}}}*/

    // public string _transMultimedia($matches)/*{{{*/
    // <img ... class="multimedia" ..> 로 되어 있는 코드를 변경
    function _transMultimedia($matches) {
      preg_match("/style\=(\"|'){0,1}([^\"\']+)(\"|'){0,1}/i",$matches[0], $buff);
      $style = str_replace("\"","'",$buff[0]);
      preg_match("/alt\=\"{0,1}([^\"]+)\"{0,1}/i",$matches[0], $buff);
      $opt = explode('|@|',$buff[1]);
      if(count($opt)<1) return $matches[0];

      for($i=0;$i<count($opt);$i++) {
        $pos = strpos($opt[$i],"=");
        $cmd = substr($opt[$i],0,$pos);
        $val = substr($opt[$i],$pos+1);
        $obj->{$cmd} = $val;
      }

      return sprintf("<script type=\"text/javascript\">document.writeln(displayMultimedia(\"%s\", \"%s\", \"%s\"));</script>", $obj->type, $obj->src, $style);
    }/*}}}*/
  }
?>
