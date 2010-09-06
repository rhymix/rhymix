<?php
    /**
     * @class  module
     * @author NHN (developers@xpressengine.com)
     * @brief  module 모듈의 high class
     **/

    class module extends ModuleObject {

        /**
         * @brief 설치시 추가 작업이 필요할시 구현
         **/
        function moduleInstall() {
            // action forward에 등록 (관리자 모드에서 사용하기 위함)
            $oModuleController = &getController('module');

            $oDB = &DB::getInstance();
            $oDB->addIndex("modules","idx_site_mid", array("site_srl","mid"), true);
			$oDB->addIndex('sites','unique_domain',array('domain'),true);

            // module 모듈에서 사용할 디렉토리 생성
            FileHandler::makeDir('./files/cache/module_info');
            FileHandler::makeDir('./files/cache/triggers');

            // sites 테이블에 기본 사이트 정보 입력
            $args->site_srl = 0;
            $output = $oDB->executeQuery('module.getSite', $args);
            if(!$output->data || !$output->data->index_module_srl) {
                $db_info = Context::getDBInfo();
                $domain = Context::getDefaultUrl();
                $url_info = parse_url($domain);
                $domain = $url_info['host'].( (!empty($url_info['port'])&&$url_info['port']!=80)?':'.$url_info['port']:'').$url_info['path'];
                $site_args->site_srl = 0;
                $site_args->index_module_srl  = 0;
                $site_args->domain = $domain;
                $site_args->default_language = $db_info->lang_type;

                $output = executeQuery('module.insertSite', $site_args);
                if(!$output->toBool()) return $output;
            }

            return new Object();
        }

        /**
         * @brief 설치가 이상이 없는지 체크하는 method
         **/
        function checkUpdate() {
            $oDB = &DB::getInstance();

            // 2008. 10. 27 module_part_config 테이블의 결합 인덱스 추가
            if(!$oDB->isIndexExists("module_part_config","idx_module_part_config")) return true;

            // 2008. 11. 13 modules 의 mid를 unique를 없애고 site_srl을 추가 후에 site_srl + mid unique index
            if(!$oDB->isIndexExists('modules',"idx_site_mid")) return true;

            // 모든 모듈의 권한/스킨정보를 grants 테이블로 이전시키는 업데이트
            if($oDB->isColumnExists('modules', 'grants')) return true;

            // 모든 모듈의 권한/스킨정보를 grants 테이블로 이전시키는 업데이트
            if(!$oDB->isColumnExists('sites', 'default_language')) return true;

            // extra_vars* 컬럼 제거
            for($i=1;$i<=20;$i++) {
                if($oDB->isColumnExists("documents","extra_vars".$i)) return true;
            }

            // sites 테이블에 기본 사이트 정보 입력
            $args->site_srl = 0;
            $output = $oDB->executeQuery('module.getSite', $args);
            if(!$output->data) return true;

			// sites 테이블에서 도메인이 인덱스로 걸린경우
            if($oDB->isIndexExists('sites', 'idx_domain')) return true;
			if(!$oDB->isIndexExists('sites','unique_domain')) return true;

			if(!$oDB->isColumnExists("modules", "use_mobile")) return true;
			if(!$oDB->isColumnExists("modules", "mlayout_srl")) return true;
			if(!$oDB->isColumnExists("modules", "mcontent")) return true;
			if(!$oDB->isColumnExists("modules", "mskin")) return true;

            return false;
        }

        /**
         * @brief 업데이트 실행
         **/
        function moduleUpdate() {
            $oDB = &DB::getInstance();

            // 2008. 10. 27 module_part_config 테이블의 결합 인덱스 추가하고 기존에 module_config에 몰려 있던 모든 정보를 재점검
            if(!$oDB->isIndexExists("module_part_config","idx_module_part_config")) {
                $oModuleModel = &getModel('module');
                $oModuleController = &getController('module');
                $modules = $oModuleModel->getModuleList();
                foreach($modules as $key => $module_info) {
                    $module = $module_info->module;
                    if(!in_array($module, array('point','trackback','layout','rss','file','comment','editor'))) continue;
                    $config = $oModuleModel->getModuleConfig($module);

                    $module_config = null;
                    switch($module) {
                        case 'point' :
                                $module_config = $config->module_point;
                                unset($config->module_point);
                            break;
                        case 'trackback' :
                        case 'rss' :
                        case 'file' :
                        case 'comment' :
                        case 'editor' :
                                $module_config = $config->module_config;
                                unset($config->module_config);
                                if(is_array($module_config) && count($module_config)) {
                                    foreach($module_config as $key => $val) {
                                        if(isset($module_config[$key]->module_srl)) unset($module_config[$key]->module_srl);
                                    }
                                }
                            break;
                        case 'layout' :
                                $tmp = $config->header_script;
                                if(is_array($tmp) && count($tmp)) {
                                    foreach($tmp as $k => $v) {
                                        if(!$v && !trim($v)) continue;
                                        $module_config[$k]->header_script = $v;
                                    }
                                }
                                $config = null;
                            break;

                    }

                    $oModuleController->insertModuleConfig($module, $config);

                    if(is_array($module_config) && count($module_config)) {
                        foreach($module_config as $module_srl => $module_part_config) {
                            $oModuleController->insertModulePartConfig($module,$module_srl,$module_part_config);
                        }
                    }
                }
                $oDB->addIndex("module_part_config","idx_module_part_config", array("module","module_srl"));
            }

            // 2008. 11. 13 modules 의 mid를 unique를 없애고 site_srl을 추가 후에 site_srl + mid unique index
            if(!$oDB->isIndexExists('modules',"idx_site_mid")) {
                $oDB->dropIndex("modules","unique_mid",true);
                $oDB->addColumn('modules','site_srl','number',11,0,true);
                $oDB->addIndex("modules","idx_site_mid", array("site_srl","mid"),true);
            }

            // document 확장변수의 확장을 위한 처리
            if(!$oDB->isTableExists('document_extra_vars')) $oDB->createTableByXmlFile('./modules/document/schemas/document_extra_vars.xml');

            if(!$oDB->isTableExists('document_extra_keys')) $oDB->createTableByXmlFile('./modules/document/schemas/document_extra_keys.xml');

            // 모든 모듈의 권한, 스킨정보, 확장정보, 관리자 아이디를 grants 테이블로 이전시키는 업데이트
            if($oDB->isColumnExists('modules', 'grants')) {
                $oModuleController = &getController('module');
                $oDocumentController = &getController('document');

                // 현재 시스템 언어 코드값을 가져옴
                $lang_code = Context::getLangType();

                // 모든 모듈의 module_info를 가져옴
                $output = executeQueryArray('module.getModuleInfos');
                if(count($output->data)) {
                    foreach($output->data as $module_info) {
                        // 모듈들의 권한/ 확장변수(게시글 확장 포함)/ 스킨 변수/ 최고관리권한 정보 분리
                        $module_srl = trim($module_info->module_srl);

                        // 권한 등록
                        $grants = unserialize($module_info->grants);
                        if($grants) $oModuleController->insertModuleGrants($module_srl, $grants);

                        // 스킨 변수 등록
                        $skin_vars = unserialize($module_info->skin_vars);
                        if($skin_vars) $oModuleController->insertModuleSkinVars($module_srl, $skin_vars);

                        // 최고 관리자 아이디 등록
                        $admin_id = trim($module_info->admin_id);
                        if($admin_id && $admin_id != 'Array') {
                            $admin_ids = explode(',',$admin_id);
                            if(count($admin_id)) {
                                foreach($admin_ids as $admin_id) {
                                    $oModuleController->insertAdminId($module_srl, $admin_id);
                                }
                            }
                        }

                        // 모듈별 추가 설정 저장 (기본 modules에 없던 컬럼 데이터)
                        $extra_vars = unserialize($module_info->extra_vars);
                        $document_extra_keys = null;
                        if($extra_vars->extra_vars && count($extra_vars->extra_vars)) {
                            $document_extra_keys = $extra_vars->extra_vars;
                            unset($extra_vars->extra_vars);
                        }
                        if($extra_vars) $oModuleController->insertModuleExtraVars($module_srl, $extra_vars);

                        /**
                         * 게시글 확장변수 이동 (documents모듈에서 해야 하지만 modules 테이블의 추가 변수들이 정리되기에 여기서 함)
                         **/
                        // 플래닛모듈의 경우 직접 추가 변수 입력
                        if($module_info->module == 'planet') {
                            if(!$document_extra_keys || !is_array($document_extra_keys)) $document_extra_keys = array();
                            $planet_extra_keys->name = 'postscript';
                            $planet_extra_keys->type = 'text';
                            $planet_extra_keys->is_required = 'N';
                            $planet_extra_keys->search = 'N';
                            $planet_extra_keys->default = '';
                            $planet_extra_keys->desc = '';
                            $document_extra_keys[20] = $planet_extra_keys;
                        }

                        // 게시글 확장변수 키 등록
                        if(count($document_extra_keys)) {
                            foreach($document_extra_keys as $var_idx => $val) {
                                $oDocumentController->insertDocumentExtraKey($module_srl, $var_idx, $val->name, $val->type, $val->is_required, $val->search, $val->default, $val->desc, 'extra_vars'.$var_idx);
                            }

                            // 2009-04-14 #17923809 게시물 100개의 확장 변수만 이전되는 문제점 수정
                            $oDocumentModel = &getModel('document');
                            $total_count = $oDocumentModel->getDocumentCount($module_srl);

                            if ($total_count > 0) {
                                $per_page = 100;
                                $total_pages = (int) (($total_count - 1) / $per_page) + 1;

                                // 확장변수가 존재하면 확장변수 가져오기
                                $doc_args = null;
                                $doc_args->module_srl = $module_srl;
                                $doc_args->list_count = $per_page;
                                $doc_args->sort_index = 'list_order';
                                $doc_args->order_type = 'asc';

                                for ($doc_args->page = 1; $doc_args->page <= $total_pages; $doc_args->page++) {
                                    $output = executeQueryArray('document.getDocumentList', $doc_args);

                                    if ($output->toBool() && $output->data && count($output->data)) {
                                        foreach ($output->data as $document) {
                                            if (!$document) continue;
                                            foreach ($document as $key => $var) {
                                                if (strpos($key, 'extra_vars') !== 0 || !trim($var) || $var == 'N;') continue;
                                                $var_idx = str_replace('extra_vars','',$key);
                                                $oDocumentController->insertDocumentExtraVar($module_srl, $document->document_srl, $var_idx, $var, 'extra_vars'.$var_idx, $lang_code);
                                            }
                                        }
                                    }
                                } // for total_pages
                            } // if count
                        }

                        // 해당 모듈들의 추가 변수들 제거
                        $module_info->grant = null;
                        $module_info->extra_vars = null;
                        $module_info->skin_vars = null;
                        $module_info->admin_id = null;
                        executeQuery('module.updateModule', $module_info);
                    }
                }

                // 각종 column drop
                $oDB->dropColumn('modules','grants');
                $oDB->dropColumn('modules','admin_id');
                $oDB->dropColumn('modules','skin_vars');
                $oDB->dropColumn('modules','extra_vars');
            }

            // 모든 모듈의 권한/스킨정보를 grants 테이블로 이전시키는 업데이트
            if(!$oDB->isColumnExists('sites', 'default_language')) {
                $oDB->addColumn('sites','default_language','varchar',255,0,false);
            }

            // extra_vars* 컬럼 제거
            for($i=1;$i<=20;$i++) {
                if(!$oDB->isColumnExists("documents","extra_vars".$i)) continue;
                $oDB->dropColumn('documents','extra_vars'.$i);
            }

            // sites 테이블에 기본 사이트 정보 입력
            $args->site_srl = 0;
            $output = $oDB->executeQuery('module.getSite', $args);
            if(!$output->data) {
                // 기본 mid, 언어 구함
                $mid_output = $oDB->executeQuery('module.getDefaultMidInfo', $args);
                $db_info = Context::getDBInfo();
                $domain = Context::getDefaultUrl();
                $url_info = parse_url($domain);
                $domain = $url_info['host'].( (!empty($url_info['port'])&&$url_info['port']!=80)?':'.$url_info['port']:'').$url_info['path'];
                $site_args->site_srl = 0;
                $site_args->index_module_srl  = $mid_output->data->module_srl;
                $site_args->domain = $domain;
                $site_args->default_language = $db_info->lang_type;

				$output = executeQuery('module.insertSite', $site_args);
				if(!$output->toBool()) return $output;
			}

            if($oDB->isIndexExists('sites','idx_domain')){
                $oDB->dropIndex('sites','idx_domain');
            }
            if(!$oDB->isIndexExists('sites','unique_domain')){
                $this->updateForUniqueSiteDomain();
                $oDB->addIndex('sites','unique_domain',array('domain'),true);
            }

			if(!$oDB->isColumnExists("modules", "use_mobile")) {
				$oDB->addColumn('modules','use_mobile','char',1,'N');
			}
			if(!$oDB->isColumnExists("modules", "mlayout_srl")) {
				$oDB->addColumn('modules','mlayout_srl','number',11, 0);
			}
			if(!$oDB->isColumnExists("modules", "mcontent")) {
				$oDB->addColumn('modules','mcontent','bigtext');
			}
			if(!$oDB->isColumnExists("modules", "mskin")) {
				$oDB->addColumn('modules','mskin','varchar',250);
			}

            return new Object(0, 'success_updated');
        }

        function updateForUniqueSiteDomain()
        {
            $output = executeQueryArray("module.getNonuniqueDomains");
            if(!$output->data) return;
            foreach($output->data as $data)
            {
                if($data->count == 1) continue;
                $domain = $data->domain;
                $args = null;
                $args->domain = $domain;
                $output2 = executeQueryArray("module.getSiteByDomain", $args);
                $bFirst = true;
                foreach($output2->data as $site)
                {
                    if($bFirst) 
                    {
                        $bFirst = false;
                        continue;
                    }
                    $domain .= "_";
                    $args = null;
                    $args->domain = $domain;
                    $args->site_srl = $site->site_srl;
                    $output3 = executeQuery("module.updateSite", $args);
                }
            }
        }

        /**
         * @brief 캐시 파일 재생성
         **/
        function recompileCache() {
            // 모듈 정보 캐시 파일 모두 삭제
            FileHandler::removeFilesInDir("./files/cache/module_info");

            // 트리거 정보가 있는 파일 모두 삭제
            FileHandler::removeFilesInDir("./files/cache/triggers");

            // DB캐시 파일을 모두 삭제
            FileHandler::removeFilesInDir("./files/cache/db");

            // 기타 캐시 삭제
            FileHandler::removeDir("./files/cache/tmp");
        }
    }
?>
