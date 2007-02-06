<?php
  /**
   * @file   : classes/module/ModuleHandler.class.php
   * @author : zero <zero@nzeo.com>
   * @desc   : mid의 값으로 모듈을 찾고 관련 정보들을 세팅
   **/

  class ModuleHandler { 

    // mid와 해당 mid의 설정 값
    var $mid = NULL;
    var $module_info = NULL;

    // 모듈과 해당 모듈의 instance
    var $module = NULL;
    var $oModule = NULL;

    // public void ModuleHandler()/*{{{*/
    function ModuleHandler() {

      // 설치가 안되어 있다면 설치를 위한 준비
      if(!Context::isInstalled()) return $this->_prepareInstall();

      // 설치가 되어 있다면 요청받은 mid에 해당하는 모듈 instance 생성
      // mid가 없이 document_srl만 있다면 document_srl로 mid를 찾음
      $mid = Context::get('mid');
      $document_srl = Context::get('document_srl');

      // document_srl만 있다면 mid를 구해옴
      if(!$mid && $document_srl) {
        $module_info = module_manager::getModuleInfoByDocument($document_srl);
        if($module_info) $mid = $module_info->mid;
      }

      // mid 값에 대한 모듈 정보를 추출
      if(!$module_info) $module_info = module_manager::getModuleInfo($mid);

      // 모듈 정보에서 module 이름을 구해움
      $module = $module_info->module;

      $this->mid = $module_info->mid;
      $this->module_info = $module_info;

      Context::set('module', $module);
      Context::set('mid', $this->mid, true);
      Context::set('module_srl', $this->module_info->module_srl, true);

      // 만약 모듈이 없다면 오류 출력
      if(!$module) return $this->_moduleIsNotExists();

      $this->oModule = getModule($module);
      $this->module = $module;
    }/*}}}*/

    // private void _prepareInstall()/*{{{*/
    // 설치를 하기 위해서 mid, module등을 강제 지정
    function _prepareInstall() {
      // module로 install 모듈을 지정
      $this->module = 'install';
      Context::set('mid', NULL);
      Context::set('module', $this->module);

      // module_manager 호출
      $this->oModule = getModule($this->module);
    }/*}}}*/

    // private void _moduleIsNotExists()/*{{{*/
    // 아무런 설정이 되어 있지 않다면 오류 표시
    function _moduleIsNotExists() {
      $this->module = 'message';
      Context::set('mid', NULL);
      Context::set('module', $this->module);

      $this->oModule = getModule($this->module);

      Context::set('error', -1);
      Context::set('message', Context::getLang('msg_mid_not_exists'));
    }/*}}}*/

    // public object proc()/*{{{*/
    function proc() {
      $this->oModule->moduleInit($this->module_info);
      $this->oModule->proc();
      return $this->oModule;
    }/*}}}*/
  }
?>
