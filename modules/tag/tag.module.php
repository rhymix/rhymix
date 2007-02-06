<?php
  /**
   * @file   : modules/tag/tag.module.php
   * @author : zero <zero@nzeo.com>
   * @desc   : 기본 모듈중의 하나인 tag module
   *           Module class에서 상속을 받아서 사용
   *           action 의 경우 disp/proc 2가지만 존재하며 이는 action명세서에 
   *           미리 기록을 하여야 함
   **/

  class tag extends Module {

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
     * (ex: $this->module_path = "./modules/install/";
     **/

    // 초기화
    function init() {/*{{{*/
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
     * procXXXX : 처리를 위한 method, output에는 tag, tag가 지정되어야 한다
     **/

    /**
     * 여기부터는 이 모듈과 관련된 라이브러리 개념의 method들
     **/

    // public string insertTag($module_srl, $document_srl, $tags)/*{{{*/
    // 태그 입력
    function insertTag($module_srl, $document_srl, $tags) {

      // 해당 글의 tags를 모두 삭제
      $this->deleteTag($document_srl);

      if(!$tags) return;

      // tags변수 정리
      $tmp_tag_list = explode(',', $tags);
      $tag_count = count($tmp_tag_list);
      for($i=0;$i<$tag_count;$i++) {
        $tag = trim($tmp_tag_list[$i]); 
        if(!$tag) continue;
        $tag_list[] = $tag;
      }
      if(!count($tag_list)) return;

      // 다시 태그를 입력
      $oDB = &DB::getInstance();

      $args->module_srl = $module_srl;
      $args->document_srl = $document_srl;
      $tag_count = count($tag_list);
      for($i=0;$i<$tag_count;$i++) {
        $args->tag = $tag_list[$i];
        $oDB->executeQuery('tag.insertTag', $args);
      }

      return implode(',',$tag_list);
    }/*}}}*/

    // public boolean deleteTag($document_srl)/*{{{*/
    // 특정 문서의 태그 삭제
    function deleteTag($document_srl) {
      $oDB = &DB::getInstance();
      $args->document_srl = $document_srl;
      return $oDB->executeQuery('tag.deleteTag', $args);
    }/*}}}*/

    // public boolean deleteModuleTags($module_sr)/*{{{*/
    // 특정 모듈의 태그 삭제
    function deleteModuleTags($module_srl) {
      // 삭제
      $oDB = &DB::getInstance();
      $args->module_srl = $module_srl;
      return $oDB->executeQuery('tag.deleteModuleTags', $args);
    }/*}}}*/
  }
?>
