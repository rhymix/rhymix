<?php
    /**
     * @class  pluginController
     * @author zero (zero@nzeo.com)
     * @brief  plugin 모듈의 Controller class
     **/

    class pluginController extends plugin {

        /**
         * @brief 초기화
         **/
        function init() {
        }

        /**
         * @brief 플러그인의 생성된 코드를 return
         **/
        function procPluginGenerateCode() {
            // 변수 정리
            $vars = Context::getRequestVars();
            $plugin = $vars->selected_plugin;

            unset($vars->module);
            unset($vars->act);
            unset($vars->selected_plugin);

            $attribute = array();
            if($vars) {
                foreach($vars as $key => $val) {
                    if(strpos($val,'|@|')>0) $val = str_replace('|@|',',',$val);
                    $attribute[] = sprintf('%s="%s"', $key, str_replace('"','\"',$val));
                }
            }

            $blank_img_path = "./common/tpl/images/blank.gif";
            $plugin_code = sprintf('<img src="%s" class="zbxe_plugin_output" plugin="%s" %s style="width:100px;height:100px;"/>', $blank_img_path, $plugin, implode(' ',$attribute));

            // 코드 출력
            $this->add('plugin_code', $plugin_code);
        }

        /**
         * @brief 선택된 플러그인 - 스킨의 컬러셋을 return
         **/
        function procPluginGetColorsetList() {
            $plugin = Context::get('selected_plugin');
            $skin = Context::get('skin');

            $path = sprintf('./plugins/%s/', $plugin);
            $oModuleModel = &getModel('module');
            $skin_info = $oModuleModel->loadSkinInfo($path, $skin);

            for($i=0;$i<count($skin_info->colorset);$i++) {
                $colorset = sprintf('%s|@|%s', $skin_info->colorset[$i]->name, $skin_info->colorset[$i]->title);
                $colorset_list[] = $colorset;
            }

            if(count($colorset_list)) $colorsets = implode("\n", $colorset_list);
            $this->add('colorset_list', $colorsets);
        }

    }
?>
