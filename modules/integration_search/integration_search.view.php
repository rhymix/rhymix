<?php
    /**
     * @class  integration_searchView
     * @author zero (zero@nzeo.com)
     * @brief  integration_search module의 view class
     *
     * 통합검색 출력
     *
     **/

    class integration_searchView extends integration_search {

        var $target_mid = array();
        var $skin = 'default';

        /**
         * @brief 초기화
         **/
        function init() {
            // 설정 정보를 받아옴 (module model 객체를 이용)
            $oModuleModel = &getModel('module');
            $config = $oModuleModel->getModuleConfig('integration_search');
            if(!$config->skin) $config->skin = 'default';

            $this->target_mid = $config->target_mid;
            if(!$this->target_mid) $this->target_mid = array();

            $this->skin = $config->skin;
            $this->module_info = unserialize($config->skin_vars);
            Context::set('module_info', $this->module_info);

            $this->setTemplatePath($this->module_path."/skins/".$this->skin."/");
        }

        /**
         * @brief 통합 검색 출력
         **/
        function IS() {
            // 권한 체크
            if(!$this->grant->access) return new Object(-1,'msg_not_permitted');

            // 검색이 가능한 목록을 구하기 위해 전체 목록을 구해옴
            $oModuleModel = &getModel('module');
            $site_module_info = Context::get('site_module_info');
            if($site_module_info->site_srl) {
                $args->site_srl = (int)$site_module_info->site_srl;
                $module_list = $oModuleModel->getMidList($args);
                foreach($module_list as $mid => $val) {
                    $mid_list[$val->module_srl] = $val;
                    $module_srl_list[] = $val->module_srl;
                }
            } else {
                // 대상 모듈을 정리함
                $module_list = $oModuleModel->getMidList($args);
                $module_srl_list = array();
                foreach($module_list as $mid => $val) {
                    $mid_list[$val->module_srl] = $val;
                    if(count($this->target_mid) && !in_array($mid, $this->target_mid)) continue;
                    $module_srl_list[] = $val->module_srl;
                }
            }

            // 검색어 변수 설정
            $is_keyword = Context::get('is_keyword');

            // 페이지 변수 설정
            $page = (int)Context::get('page');
            if(!$page) $page = 1;

            // 검색탭에 따른 검색
            $where = Context::get('where');

            $oFile = &getClass('file');

            // integration search model객체 생성
            if($is_keyword) {
                $oIS = &getModel('integration_search');
                switch($where) {
                    case 'document' :
                            $search_target = Context::get('search_target');
                            if(!in_array($search_target, array('title','content','title_content','tag'))) $search_target = 'title';
                            Context::set('search_target', $search_target);

                            $output = $oIS->getDocuments($module_srl_list, $search_target, $is_keyword, $page, 10);
                            Context::set('output', $output);
                            $this->setTemplateFile("document", $page);
                        break;
                    case 'comment' :
                            $output = $oIS->getComments($module_srl_list, $is_keyword, $page, 10);
                            Context::set('output', $output);
                            $this->setTemplateFile("comment", $page);
                        break;
                    case 'trackback' :
                            $search_target = Context::get('search_target');
                            if(!in_array($search_target, array('title','url','blog_name','excerpt'))) $search_target = 'title';
                            Context::set('search_target', $search_target);

                            $output = $oIS->getTrackbacks($module_srl_list, $search_target, $is_keyword, $page, 10);
                            Context::set('output', $output);
                            $this->setTemplateFile("trackback", $page);
                        break;
                    case 'multimedia' :
                            $output = $oIS->getImages($module_srl_list, $is_keyword, $page,20);
                            Context::set('output', $output);
                            $this->setTemplateFile("multimedia", $page);
                        break;
                    case 'file' :
                            $output = $oIS->getFiles($module_srl_list, $is_keyword, $page, 20);
                            Context::set('output', $output);
                            $this->setTemplateFile("file", $page);
                        break;
                    default :
                            $output['document'] = $oIS->getDocuments($module_srl_list, 'title', $is_keyword, $page, 5);
                            $output['comment'] = $oIS->getComments($module_srl_list, $is_keyword, $page, 5);
                            $output['trackback'] = $oIS->getTrackbacks($module_srl_list, 'title', $is_keyword, $page, 5);
                            $output['multimedia'] = $oIS->getImages($module_srl_list, $is_keyword, $page, 5);
                            $output['file'] = $oIS->getFiles($module_srl_list, $is_keyword, $page, 5);
                            Context::set('search_result', $output);
                            $this->setTemplateFile("index", $page);
                        break;
                }
            } else {
                $this->setTemplateFile("no_keywords");
            }
        }
    }
?>
