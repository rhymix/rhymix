<?php
    /**
     * @class  moduleModel
     * @author zero (zero@nzeo.com)
     * @brief  module 모듈의 Model class
     **/

    class moduleModel extends module {

        /**
         * @brief 초기화
         **/
        function init() {
        }

        /**
         * @brief document_srl로 모듈의 정보르 구함
         * 
         * 이 경우는 캐시파일을 이용할 수가 없음
         **/
        function getModuleInfoByDocumentSrl($document_srl) {
            // DB 객체 생성
            $oDB = &DB::getInstance();

            // 데이터를 DB에서 가져옴
            $args->document_srl = $document_srl;
            $output = $oDB->executeQuery('module.getModuleInfoByDocument', $args);

            return $this->arrangeModuleInfo($output->data);
        }

        /**
         * @brief mid로 모듈의 정보를 구함
         **/
        function getModuleInfoByMid($mid='') {
            // DB 객체 생성
            $oDB = &DB::getInstance();

            // $mid값이 인자로 주어질 경우 $mid로 모듈의 정보를 구함
            if($mid) {
                $args->mid = $mid;
                $output = $oDB->executeQuery('module.getMidInfo', $args);
            }

            // 모듈의 정보가 없다면($mid가 잘못이거나 없었을 경우) 기본 모듈을 가져옴
            if(!$output->data) {
                $output = $oDB->executeQuery('module.getDefaultMidInfo');
            }
            $module_info = $this->arrangeModuleInfo($output->data);

            return $module_info;
        }

        /**
         * @brief module_srl에 해당하는 모듈의 정보를 구함
         **/
        function getModuleInfoByModuleSrl($module_srl) {
            // db객체 생성
            $oDB = &DB::getInstance();

            // 데이터를 가져옴
            $args->module_srl = $module_srl;
            $output = $oDB->executeQuery('module.getMidInfo', $args);
            if(!$output->data) return;

            $module_info = $this->arrangeModuleInfo($output->data);

            return $module_info;
        }

        /**
         * @brief DB에서 가져온 원 모듈 정보에서 grant, extraVar등의 정리
         **/
        function arrangeModuleInfo($source_module_info) {
            if(!$source_module_info || !is_object($source_module_info) ) return;

            // serialize되어 있는 변수들 추출
            $extra_vars = $source_module_info->extra_vars;
            $skin_vars = $source_module_info->skin_vars;
            $grants = $source_module_info->grants;
            $admin_id = $source_module_info->admin_id;

            unset($source_module_info->extra_vars);
            unset($source_module_info->skin_vars);
            unset($source_module_info->grants);
            unset($source_module_info->admin_id);

            $module_info = clone($source_module_info);

            // extra_vars의 정리
            if($extra_vars) {
                $extra_vars = unserialize($extra_vars);
                foreach($extra_vars as $key => $val) if(!$module_info->{$key}) $module_info->{$key} = $val;
            }

            // skin_vars의 정리
            if($skin_vars) {
                $skin_vars = unserialize($skin_vars);
                foreach($skin_vars as $key => $val) if(!$module_info->{$key}) $module_info->{$key} = $val;
            }

            // 권한의 정리
            if($grants) $module_info->grants = unserialize($grants);

            // 관리자 아이디의 정리
            if($admin_id) $module_info->admin_id = explode(',',$admin_id);
            else $module_info->admin_id = array();

            return $module_info;
        }

        /**
         * @brief act 값에 의한 forward 값을 구함
         **/
        function getActionForward($act) {
            $args->act = $act;
            $output = executeQuery('module.getActionForward',$args);
            return $output->data;
        }

        /**
         * @brief DB에 생성된 mid목록을 구해옴
         **/
        function getMidList() {
            $oDB = &DB::getInstance();
            $output = $oDB->executeQuery('module.getMidList');
            if(!$output->toBool()) return $output;

            $list = $output->data;
            if(!$list) return;
            if(!is_array($list)) $list = array($list);
            foreach($list as $val) {
                $mid_list[$val->mid] = $val;
            }
            return $mid_list;
        }

        /**
         * @brief mid 목록에 대응하는 module_srl을 배열로 return
         **/
        function getModuleSrlByMid($mid) {
            if(is_array($mid)) $mid = "'".implode("','",$mid)."'";
            $oDB = &DB::getInstance();
            $args->mid = $mid;
            $output = $oDB->executeQuery('module.getModuleSrlByMid', $args);
            if(!$output->toBool()) return $output;

            $list = $output->data;
            if(!$list) return;
            if(!is_array($list)) $list = array($list);
            foreach($list as $key => $val) $module_srl_list[] = $val->module_srl;
            return $module_srl_list;
        }

        /**
         * @brief 주어진 곳의 스킨 목록을 구함 
         * 스킨과 skin.xml 파일을 분석 정리한 결과를 return
         **/
        function getSkins($path) {
            $skin_path = sprintf("%s/skins/", $path);
            $list = FileHandler::readDir($skin_path);
            if(!count($list)) return;

            $oXmlParser = new XmlParser();

            foreach($list as $skin_name) {
                unset($skin_info);
                $skin_info = $this->loadSkinInfo($path, $skin_name);
                if(!$skin_info) $skin_info->title = $skin_name;

                $skin_list[$skin_name] = $skin_info;
            }

            return $skin_list;
        }


        /**
         * @brief 특정 위치의 특정 스킨의 정보를 구해옴
         **/
        function loadSkinInfo($path, $skin) {

            // 모듈의 스킨의 정보 xml 파일을 읽음
            $skin_xml_file = sprintf("%sskins/%s/skin.xml", $path, $skin);
            if(!file_exists($skin_xml_file)) return;

            // XmlParser 객체 생성
            $oXmlParser = new XmlParser();
            $xml_obj = $oXmlParser->loadXmlFile($skin_xml_file);

            // 스킨 정보가 없으면 return
            if(!$xml_obj) return;

            // 스킨이름
            $skin_info->title = $xml_obj->title->body;

            // 작성자 정보
            $skin_info->maker->name = $xml_obj->maker->name->body;
            $skin_info->maker->email_address = $xml_obj->maker->attrs->email_address;
            $skin_info->maker->homepage = $xml_obj->maker->attrs->link;
            $skin_info->maker->date = $xml_obj->maker->attrs->date;
            $skin_info->maker->description = $xml_obj->maker->description->body;

            // colorset
            if(!is_array($xml_obj->colorset->color)) $colorset[] = $xml_obj->colorset->color;
            else $colorset = $xml_obj->colorset->color;

            if($colorset[0]->attrs->name) {
                foreach($colorset as $color) {
                    $name = $color->attrs->name;
                    $title = $color->title->body;
                    $screenshot = $color->attrs->src;
                    if($screenshot && file_exists($screenshot)) $screenshot = sprintf("%sskins/%s/%s", $path, $skin, $screenshot);
                    else $screenshot = "";

                    unset($obj);
                    $obj->name = $name;
                    $obj->title = $title;
                    $obj->screenshot = $screenshot;
                    $skin_info->colorset[] = $obj;
                }
            }

            // 스킨에서 사용되는 변수들
            if(!is_array($xml_obj->extra_vars->var)) $extra_vars[] = $xml_obj->extra_vars->var;
            else $extra_vars = $xml_obj->extra_vars->var;

            if($extra_vars[0]->attrs->name) {
                foreach($extra_vars as $var) {
                        $name = $var->attrs->name;
                        $type = $var->attrs->type;
                        $title = $var->title->body;
                        $description = $var->description->body;
                        if($var->default) {
                            unset($default);
                            if(is_array($var->default)) {
                                for($i=0;$i<count($var->default);$i++) $default[] = $var->default[$i]->body;
                            } else {
                                $default = $var->default->body;
                        }
                    }

                    $width = $var->attrs->width;
                    $height = $var->attrs->height;

                    unset($obj);
                    $obj->title = $title;
                    $obj->description = $description;
                    $obj->name = $name;
                    $obj->type = $type;
                    $obj->default = $default;
                    $obj->width = $width;
                    $obj->height = $height;

                    $skin_info->extra_vars[] = $obj;
                }
            }

            return $skin_info;
        }

        /**
         * @brief module의 conf/module.xml 을 통해 grant(권한) 및 action 데이터를 return
         *
         * module.xml 파일의 경우 파싱하는데 시간이 걸리기에 캐싱을 한다...
         * 캐싱을 할때 바로 include 할 수 있도록 역시 코드까지 추가하여 캐싱을 한다.
         * 이게 퍼포먼스 상으로는 좋은데 어떤 부정적인 결과를 유도할지는 잘 모르겠...
         **/
        function getModuleActionXml($module) {
            // 요청된 모듈의 경로를 구한다. 없으면 return
            $class_path = ModuleHandler::getModulePath($module);
            if(!$class_path) return;

            // 해당 경로에 module.xml 파일이 있는지 체크한다. 없으면 return
            $xml_file = sprintf("%sconf/module.xml", $class_path);
            if(!file_exists($xml_file)) return;

            // 캐시된 파일이 있는지 확인
            $cache_file = sprintf("./files/cache/module_info/%s.php", $module);

            // 캐시 파일이 없거나 캐시 파일이 xml 파일보다 오래되었으면 내용 다시 갱신
            if(!file_exists($cache_file) || filectime($cache_file)<filectime($xml_file)) {

                $buff = ""; ///< 캐시 파일에 쓸 buff 변수 설정

                $xml_obj = XmlParser::loadXmlFile($xml_file); ///< xml 파일을 읽어서 xml object로 변환

                if(!count($xml_obj->module)) return; ///< xml 내용중에 module 태그가 없다면 오류;;

                $grants = $xml_obj->module->grants->grant; ///< 권한 정보 (없는 경우도 있음)
                $actions = $xml_obj->module->actions->action; ///< action list (필수)

                $default_index = $admin_index = '';

                // 권한 정보의 정리
                if($grants) {
                    if(is_array($grants)) $grant_list = $grants;
                    else $grant_list[] = $grants;

                    foreach($grant_list as $grant) {
                        $name = $grant->attrs->name;
                        $default = $grant->attrs->default?$grant->attrs->default:'guest';
                        $title = $grant->title->body;

                        $info->grant->{$name}->title = $title;
                        $info->grant->{$name}->default = $default;

                        $buff .= sprintf('$info->grant->%s->title=\'%s\';', $name, $title);
                        $buff .= sprintf('$info->grant->%s->default=\'%s\';', $name, $default);
                    }
                }

                // actions 정리
                if($actions) {
                    if(is_array($actions)) $action_list = $actions;
                    else $action_list[] = $actions;

                    foreach($action_list as $action) {
                        $name = $action->attrs->name;

                        $type = $action->attrs->type;
                        $grant = $action->attrs->grant?$action->attrs->grant:'guest';
                        $standalone = $action->attrs->standalone=='true'?'true':'false';

                        $index = $action->attrs->index;
                        $admin_index = $action->attrs->admin_index;

                        $output->action->{$name}->type = $type;
                        $output->action->{$name}->grant = $grant;
                        $output->action->{$name}->standalone= $standalone;

                        $info->action->{$name}->type = $type;
                        $info->action->{$name}->grant = $grant;
                        $info->action->{$name}->standalone = $standalone=='true'?true:false;

                        $buff .= sprintf('$info->action->%s->type=\'%s\';', $name, $type);
                        $buff .= sprintf('$info->action->%s->grant=\'%s\';', $name, $grant);
                        $buff .= sprintf('$info->action->%s->standalone=%s;', $name, $standalone);

                        if($index=='true') {
                            $default_index_act = $name;
                            $info->default_index_act = $name;
                        }
                        if($admin_index=='true') {
                            $admin_index_act = $name;
                            $info->admin_index_act = $name;
                        }
                    }
                }
                $buff = sprintf('<?php if(!__ZBXE__) exit();$info->default_index_act = \'%s\';$info->admin_index_act = \'%s\';%s?>', $default_index_act, $admin_index_act, $buff);

                FileHandler::writeFile($cache_file, $buff);

                return $info;
            }

            include $cache_file; 

            return $info;
        }

        /**
         * @brief 특정 모듈의 설정 정보 return
         * 캐시된 설정 정보가 없으면 만들 후 캐시하고 return
         **/
        function getModuleConfig($module) {
            $cache_file = sprintf('./files/cache/module_info/%s.config.php',$module);

            if(!file_exists($cache_file)) {
                // DB 객체 생성
                $oDB = &DB::getInstance();
                $args->module = $module;
                $output = $oDB->executeQuery('module.getModuleConfig', $args);

                $config = base64_encode($output->data->config);

                $buff = sprintf('<?php if(!__ZBXE__) exit(); $config = "%s"; ?>', $config);

                //FileHandler::writeFile($cache_file, $buff);
            }

            if(!$config && file_exists($cache_file)) @include($cache_file);

            return unserialize(base64_decode($config));
        }


        /**
         * @brief 모듈의 conf/info.xml 을 읽어서 정보를 구함
         **/
        function getModuleInfoXml($module) {
            // 요청된 모듈의 경로를 구한다. 없으면 return
            $module_path = ModuleHandler::getModulePath($module);
            if(!$module_path) return;

            // 현재 선택된 모듈의 스킨의 정보 xml 파일을 읽음
            $xml_file = sprintf("%s/conf/info.xml", $module_path);
            if(!file_exists($xml_file)) return;

            $oXmlParser = new XmlParser();
            $tmp_xml_obj = $oXmlParser->loadXmlFile($xml_file);
            $xml_obj = $tmp_xml_obj->module;

            if(!$xml_obj) return;

            $info->title = $xml_obj->title->body;

            // 작성자 정보
            $module_info->title = $xml_obj->title->body;
            $module_info->version = $xml_obj->attrs->version;
            $module_info->author->name = $xml_obj->author->name->body;
            $module_info->author->email_address = $xml_obj->author->attrs->email_address;
            $module_info->author->homepage = $xml_obj->author->attrs->link;
            $module_info->author->date = $xml_obj->author->attrs->date;
            $module_info->author->description = $xml_obj->author->description->body;

            // history 
            if(!is_array($xml_obj->history->author)) $history[] = $xml_obj->history->author;
            else $history = $xml_obj->history->author;

            foreach($history as $item) {
                unset($obj);
                $obj->name = $item->name->body;
                $obj->email_address = $item->attrs->email_address;
                $obj->homepage = $item->attrs->link;
                $obj->date = $item->attrs->date;
                $obj->description = $item->description->body;
                $module_info->history[] = $obj;
            }

            // action 정보를 얻어서 admin_index를 추가
            $action_info = $this->getModuleActionXml($module);
            $module_info->admin_index_act = $action_info->admin_index_act;

            return $module_info;
        }

        /**
         * @brief 모듈 카테고리의 목록을 구함
         **/
        function getModuleCategories() {
            // DB 객체 생성
            $oDB = &DB::getInstance();

            // 데이터를 DB에서 가져옴
            $output = $oDB->executeQuery('module.getModuleCategories');
            if(!$output->toBool()) return $output;
            $list = $output->data;
            if(!$list) return;
            if(!is_array($list)) $list = array($list);

            foreach($list as $val) {
                $category_list[$val->module_category_srl] = $val;
            }
            return $category_list;
        }

        /**
         * @brief 특정 모듈 카테고리의 내용을 구함
         **/
        function getModuleCategory($module_category_srl) {
            // DB 객체 생성
            $oDB = &DB::getInstance();

            // 데이터를 DB에서 가져옴
            $args->module_category_srl = $module_category_srl;
            $output = $oDB->executeQuery('module.getModuleCategory', $args);
            if(!$output->toBool()) return $output;
            return $output->data;
        }

        /**
         * @brief 모듈의 종류와 정보를 구함
         **/
        function getModuleList() {
            // DB 객체 생성
            $oDB = &DB::getInstance();

            // 다운받은 모듈과 설치된 모듈의 목록을 구함
            $searched_list = FileHandler::readDir('./modules');
            $searched_count = count($searched_list);
            if(!$searched_count) return;

            for($i=0;$i<$searched_count;$i++) {
                // 모듈의 이름
                $module_name = $searched_list[$i];

                $path = ModuleHandler::getModulePath($module_name);

                // schemas내의 테이블 생성 xml파일수를 구함
                $tmp_files = FileHandler::readDir($path."schemas");
                $table_count = count($tmp_files);

                // 테이블이 설치되어 있는지 체크
                $created_table_count = 0;
                for($j=0;$j<count($tmp_files);$j++) {
                    list($table_name) = explode(".",$tmp_files[$j]);
                    if($oDB->isTableExists($table_name)) $created_table_count ++;
                }

                // 해당 모듈의 정보를 구함
                $info = $this->getModuleInfoXml($module_name);
                unset($obj);

                $info->module = $module_name;
                $info->created_table_count = $created_table_count;
                $info->table_count = $table_count;
                $info->path = $path;
                $info->admin_index_act = $info->admin_index_act;

                $list[] = $info;
            }
            return $list;
        }

    }
?>
