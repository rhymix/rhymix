<?php
    /**
     * @class  pluginModel
     * @author zero (zero@nzeo.com)
     * @version 0.1
     * @brief  plugin 모듈의 Model class
     **/

    class pluginModel extends plugin {

        /**
         * @brief 초기화
         **/
        function init() {
        }

        /**
         * @brief 플러그인의 경로를 구함
         * 기본으로는 ./plugins에 있지만 웹관리기능으로 다운로드시 ./files/plugins에 존재함
         **/
        function getPluginPath($plugin_name) {
            $class_path = sprintf('./files/plugins/%s/', $plugin_name);
            if(is_dir($class_path)) return $class_path;

            $class_path = sprintf('./plugins/%s/', $plugin_name);
            if(is_dir($class_path)) return $class_path; 

            return "";
        }

        /**
         * @brief 플러그인의 종류와 정보를 구함
         * 다운로드되어 있는 플러그인의 종류 (생성과 다른 의미)
         **/
        function getDownloadedPluginList() {
            // 다운받은 플러그인과 설치된 플러그인의 목록을 구함
            $downloaded_list = FileHandler::readDir('./files/plugins');
            $installed_list = FileHandler::readDir('./plugins');
            $searched_list = array_merge($downloaded_list, $installed_list);
            $searched_count = count($searched_list);
            if(!$searched_count) return;

            // 찾아진 플러그인 목록을 loop돌면서 필요한 정보를 간추려 return
            for($i=0;$i<$searched_count;$i++) {
                // 플러그인의 이름
                $plugin = $searched_list[$i];

                // 해당 플러그인의 정보를 구함
                $plugin_info = $this->getPluginInfo($plugin);

                $list[] = $plugin_info;
            }
            return $list;
        }

        /**
         * @brief 모듈의 conf/info.xml 을 읽어서 정보를 구함
         * 이것 역시 캐싱을 통해서 xml parsing 시간을 줄인다.. 
         **/
        function getPluginInfo($plugin) {
            // 요청된 모듈의 경로를 구한다. 없으면 return
            $plugin_path = $this->getPluginPath($plugin);
            if(!$plugin_path) return;

            // 현재 선택된 모듈의 스킨의 정보 xml 파일을 읽음
            $xml_file = sprintf("%sconf/info.xml", $plugin_path);
            if(!file_exists($xml_file)) return;

            // cache 파일을 비교하여 문제 없으면 include하고 $plugin_info 변수를 return
            $cache_file = sprintf('./files/cache/plugin/%s.cache.php', $plugin);
            if(file_exists($cache_file)&&filectime($cache_file)>filectime($xml_file)) {
                include $cache_file;
                return $plugin_info;
            }

            // cache 파일이 없으면 xml parsing하고 변수화 한 후에 캐시 파일에 쓰고 변수 바로 return
            $oXmlParser = new XmlParser();
            $tmp_xml_obj = $oXmlParser->loadXmlFile($xml_file);
            $xml_obj = $tmp_xml_obj->plugin;
            if(!$xml_obj) return;

            $buff = '';

            // 플러그인의 제목, 버전
            $buff .= sprintf('$plugin_info->plugin = "%s";', $plugin);
            $buff .= sprintf('$plugin_info->path = "%s";', $plugin_path);
            $buff .= sprintf('$plugin_info->title = "%s";', $xml_obj->title->body);
            $buff .= sprintf('$plugin_info->version = "%s";', $xml_obj->attrs->version);
            $buff .= sprintf('$plugin_info->plugin_srl = $plugin_srl;');
            $buff .= sprintf('$plugin_info->plugin_title = $plugin_title;');

            // 작성자 정보
            $buff .= sprintf('$plugin_info->author->name = "%s";', $xml_obj->author->name->body);
            $buff .= sprintf('$plugin_info->author->email_address = "%s";', $xml_obj->author->attrs->email_address);
            $buff .= sprintf('$plugin_info->author->homepage = "%s";', $xml_obj->author->attrs->link);
            $buff .= sprintf('$plugin_info->author->date = "%s";', $xml_obj->author->attrs->date);
            $buff .= sprintf('$plugin_info->author->description = "%s";', $xml_obj->author->description->body);

            // 추가 변수 (템플릿에서 사용할 제작자 정의 변수)
            if(!is_array($xml_obj->extra_vars->var)) $extra_vars[] = $xml_obj->extra_vars->var;
            else $extra_vars = $xml_obj->extra_vars->var;
            $extra_var_count = count($extra_vars);

            $buff .= sprintf('$plugin_info->extra_var_count = "%s";', $extra_var_count);
            for($i=0;$i<$extra_var_count;$i++) {
                unset($var);
                unset($options);
                $var = $extra_vars[$i];

                $buff .= sprintf('$plugin_info->extra_var->%s->name = "%s";', $var->attrs->id, $var->name->body);
                $buff .= sprintf('$plugin_info->extra_var->%s->type = "%s";', $var->attrs->id, $var->type->body);
                $buff .= sprintf('$plugin_info->extra_var->%s->value = $vars->%s;', $var->attrs->id, $var->attrs->id);
                $buff .= sprintf('$plugin_info->extra_var->%s->description = "%s";', $var->attrs->id, str_replace('"','\"',$var->description->body));

                $options = $var->options;
                if(!$options) continue;

                if(!is_array($options)) $options = array($options);
                $options_count = count($options);
                for($j=0;$j<$options_count;$j++) {
                    $buff .= sprintf('$plugin_info->extra_var->%s->options["%s"] = "%s";', $var->attrs->id, $options[$j]->value->body, $options[$j]->name->body);
                }

            }

            $buff = '<?php if(!__ZB5__) exit(); '.$buff.' ?>';
            FileHandler::writeFile($cache_file, $buff);

            if(file_exists($cache_file)) include $cache_file;
            return $plugin_info;
        }

    }
?>
