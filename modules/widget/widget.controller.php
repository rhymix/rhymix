<?php
    /**
     * @class  widgetController
     * @author zero (zero@nzeo.com)
     * @brief  widget 모듈의 Controller class
     **/

    class widgetController extends widget {

        /**
         * @brief 초기화
         **/
        function init() {
        }

        /**
         * @brief 위젯의 생성된 코드를 return
         **/
        function procWidgetGenerateCode() {
            // 변수 정리
            $vars = Context::getRequestVars();
            $widget = $vars->selected_widget;

            $blank_img_path = Context::getRequestUri()."common/tpl/images/widget_bg.jpg";

            unset($vars->module);
            unset($vars->act);
            unset($vars->selected_widget);

            if($vars->widget_sequence) {
                $cache_path = './files/cache/widget_cache/';
                $cache_file = sprintf('%s%d.%s.cache', $cache_path, $vars->widget_sequence, Context::getLangType());
                @unlink($cache_file);
            }

            $vars->widget_sequence = getNextSequence();
            if(!$vars->widget_cache) $vars->widget_cache = 0;

            $attribute = array();
            if($vars) {
                foreach($vars as $key => $val) {
                    if(strpos($val,'|@|')>0) $val = str_replace('|@|',',',$val);
                    $attribute[] = sprintf('%s="%s"', $key, str_replace('"','\"',$val));
                }
            }

            if($vars->widget_fix_width == 'Y') {
                $widget_width_type = strtolower($vars->widget_width_type);
                if(!$widget_width_type||!in_array($widget_width_type,array("px","%"))) $widget_width_type = "px";

                $style .= sprintf("%s:%s%s;", "width", trim($vars->widget_width), $widget_width_type);

                if($vars->widget_position) $style .= sprintf("%s:%s;", "float", trim($vars->widget_position));
                else $style .= "float:left;";
                $widget_code = sprintf('<img src="%s" height="100" class="zbxe_widget_output" widget="%s" %s style="%s" />', $blank_img_path, $widget, implode(' ',$attribute), $style);
            } else {
                $style = "clear:both;";
                $widget_code = sprintf('<img width="%s" height="100" src="%s" class="zbxe_widget_output" style="%s" widget="%s" %s />', "100%", $blank_img_path, $style, $widget, implode(' ',$attribute));
            }

            $cache_path = './files/cache/widget_cache/';
            $cache_file = sprintf('%s%d.%s.cache', $cache_path, $vars->widget_sequence, Context::getLangType());
            @unlink($cache_file);

            // 코드 출력
            $this->add('widget_code', $widget_code);
        }

        /**
         * @brief 선택된 위젯 - 스킨의 컬러셋을 return
         **/
        function procWidgetGetColorsetList() {
            $widget = Context::get('selected_widget');
            $skin = Context::get('skin');

            $path = sprintf('./widgets/%s/', $widget);
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
