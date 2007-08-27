<?php
    /**
     * @class WidgetHandler
     * @author zero (zero@nzeo.com)
     * @brief 위젯의 실행을 담당
     **/

    class WidgetHandler {

        var $widget_path = '';

        /**
         * @brief 위젯 캐시 처리
         **/
        function getCache($sequence, $cache) {
            if(!$sequence || !$cache) return;

            $cache_path = './files/cache/widget_cache/';
            if(!is_dir($cache_path)) {
                FileHandler::makeDir($cache_path);
                return;
            }

            $cache_file = sprintf('%s%d.%s.cache', $cache_path, $sequence, Context::getLangType());
            if(!file_exists($cache_file)) return;

            $filectime = filectime($cache_file);
            if($filectime + $cache*60 < time()) return;

            $output = FileHandler::readFile($cache_file);
            return $output;
        }

        /**
         * @brief 위젯을 찾아서 실행하고 결과를 출력
         * <div widget='위젯'...></div> 태그 사용 templateHandler에서 WidgetHandler::execute()를 실행하는 코드로 대체하게 된다
         **/
        function execute($widget, $args) {
            // 디버그를 위한 위젯 실행 시간 저장
            if(__DEBUG__==3) $start = getMicroTime();

            if(!is_dir(sprintf('./widgets/%s/',$widget))) return;

            // $widget의 객체를 받음 
            $oWidget = WidgetHandler::getObject($widget);

            // 위젯 실행
            if($oWidget) {
                $output = $oWidget->proc($args);
            }

            if($args->widget_fix_width == 'Y') {
                $widget_width_type = strtolower($args->widget_width_type);
                if(!$widget_width_type||!in_array($widget_width_type,array("px","%"))) $widget_width_type = "px";


                if($widget_width_type == "px") {

                    $style = "overflow:hidden;";
                    $style .= sprintf("%s:%s%s;", "width", $args->widget_width - $args->widget_margin_right - $args->widget_margin_left, $widget_width_type);
                    $style .= sprintf("margin-top:%dpx;margin-bottom:%dpx;", $args->widget_margin_top, $args->widget_margin_bottom);
                    $inner_style = sprintf("margin-left:%dpx;margin-right:%dpx;", $args->widget_margin_left, $args->widget_margin_right);

                    if($args->widget_position) {
                        $style .= sprintf("%s:%s;", "float", $args->widget_position);
                        $output = sprintf('<div style="%s"><div style="%s">%s</div></div>',$style, $inner_style, $output);
                    } else {
                        $style  .= "float:left;";
                        $output = sprintf('<div class="clear"></div><div style="%s"><div style="%s">%s</div></div>',$style, $inner_style, $output);
                    }

                } else {

                    $style = sprintf("padding:0;overflow:hidden;%s:%s%s;", "width", $args->widget_width, $widget_width_type);

                    $output = sprintf('<div style="margin:%dpx %dpx %dpx %dpx;">%s</div>', $args->widget_margin_top, $args->widget_margin_right,$args->widget_margin_bottom,$args->widget_margin_left, $output);

                    if($args->widget_position) {
                        $style .= sprintf("%s:%s;", "float", $args->widget_position);
                        $output = sprintf('<div style="%s">%s</div>',$style, $output);
                    } else {
                        $style  .= "float:left;";
                        $output = sprintf('<div class="clear"></div><div style="%s">%s</div>',$style, $output);
                    }
                }

            } else {
                $output = sprintf('<div style="margin:%dpx %dpx %dpx %dpx;padding:0;clear:both;">%s</div>', $args->widget_margin_top, $args->widget_margin_right,$args->widget_margin_bottom,$args->widget_margin_left, $output);
            }

            if(__DEBUG__==3) $GLOBALS['__widget_excute_elapsed__'] += getMicroTime() - $start;

            if($args->widget_sequence && $args->widget_cache) {
                $cache_path = './files/cache/widget_cache/';
                $cache_file = sprintf('%s%d.%s.cache', $cache_path, $args->widget_sequence, Context::getLangType());

                FileHandler::writeFile($cache_file, $output);
            }

            return $output;
        }

        /**
         * @brief 위젯 객체를 return
         **/
        function getObject($widget) {
            if(!$GLOBALS['_xe_loaded_widgets_'][$widget]) {
                // 일단 위젯의 위치를 찾음
                $oWidgetModel = &getModel('widget');
                $path = $oWidgetModel->getWidgetPath($widget);

                // 위젯 클래스 파일을 찾고 없으면 에러 출력 (html output)
                $class_file = sprintf('%s%s.class.php', $path, $widget);
                if(!file_exists($class_file)) return sprintf(Context::getLang('msg_widget_is_not_exists'), $widget);

                // 위젯 클래스를 include
                require_once($class_file);
            
                // 객체 생성
                $eval_str = sprintf('$oWidget = new %s();', $widget);
                @eval($eval_str);
                if(!is_object($oWidget)) return sprintf(Context::getLang('msg_widget_object_is_null'), $widget);

                if(!method_exists($oWidget, 'proc')) return sprintf(Context::getLang('msg_widget_proc_is_null'), $widget);

                $oWidget->widget_path = $path;

                $GLOBALS['_xe_loaded_widgets_'][$widget] = $oWidget;
            }
            return $GLOBALS['_xe_loaded_widgets_'][$widget];
        }

    }
?>
