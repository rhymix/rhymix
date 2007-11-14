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

            $filemtime= filemtime($cache_file);
            if($filemtime + $cache*60 < time()) return;

            $output = FileHandler::readFile($cache_file);
            return $output;
        }

        /**
         * @brief 캐시 파일 생성
         **/
        function writeCache($widget_sequence, $output) {
            $cache_path = './files/cache/widget_cache/';
            $cache_file = sprintf('%s%d.%s.cache', $cache_path, $widget_sequence, Context::getLangType());
            FileHandler::writeFile($cache_file, $output);
        }

        /**
         * @brief 위젯을 찾아서 실행하고 결과를 출력
         * <div widget='위젯'...></div> 태그 사용 templateHandler에서 WidgetHandler::execute()를 실행하는 코드로 대체하게 된다
         *
         * $include_info가 true일 경우 css 코드와 위젯핸들링을 위한 코드까지 포함하도록 한다
         **/
        function execute($widget, $args, $include_info = false) {
            // 디버그를 위한 위젯 실행 시간 저장
            if(__DEBUG__==3) $start = getMicroTime();

            // widget중 widgetContent 는 page 모듈에 종속적인 위젯으로 직접 page.admin.controller.php를 호출하여 처리를 해야 함 (차후 정리 필요)
            if($widget == 'widgetContent') {
                $style = $args->style;
                $body = base64_decode($args->body);
                $widget_margin_left = $args->widget_margin_left;
                $widget_margin_right = $args->widget_margin_right;
                $widget_margin_top = $args->widget_margin_top;
                $widget_margin_bottom = $args->widget_margin_bottom;
                if($include_info) {
                    $oPageAdminController = &getAdminController('page');
                    $tpl = $oPageAdminController->transEditorContent($body, $args);
                } else {
                    $tpl = sprintf('<div style="overflow:hidden;%s"><div style="margin:%s %s %s %s;">%s</div></div>', $style, $widget_margin_top, $widget_margin_right, $widget_margin_bottom, $widget_margin_left, $body);
                }
                return $tpl;
            }

            // 설치된 위젯들에 대한 처리
            if(!is_dir(sprintf('./widgets/%s/',$widget))) return;

            $cache_path = './files/cache/widget_cache/';
            if(!is_dir($cache_path)) FileHandler::makeDir($cache_path);

            // $widget의 객체를 받음 
            $oWidget = WidgetHandler::getObject($widget);
            if(!$oWidget) return;

            // 위젯 실행
            $html = $oWidget->proc($args);

            // 위젯 output을 생성하기 위한 변수 설정
            $margin_top = $args->widget_margin_top;
            $margin_bottom = $args->widget_margin_bottom;
            $margin_left = $args->widget_margin_left;
            $margin_right = $args->widget_margin_right;

            $inner_style = sprintf("margin:%dpx %dpx %dpx %dpx !important; padding:none !important;", $margin_top, $margin_right, $margin_bottom, $margin_left);

            /**
             * 출력을 위해 위젯 내용을 div로 꾸밈
             **/
            // 서비스에 사용하기 위해 위젯 정보를 포함하지 않을 경우
            if(!$include_info) {
                $output = sprintf('<div style="overflow:hidden;%s;"><div style="%s">%s</div></div>', $args->style, $inner_style, $html);

                // 위젯 sequence가 있고 위젯의 캐싱을 지정하였고 위젯정보를 담지 않도록 하였을 경우 캐시 파일을 저장
                if($args->widget_sequence && $args->widget_cache) WidgetHandler::writeCache($args->widget_sequence, $output);

            // 에디팅등에 사용하기 위한 목적으로 위젯 정보를 포함할 경우
            } else {
                // args 정리
                $attribute = array();
                if($args) {
                    foreach($args as $key => $val) {
                        if($key == 'class' || $key == 'style') continue;
                        if(strpos($val,'|@|')>0) {
                            $val = str_replace('|@|',',',$val);
                        }
                        $attribute[] = sprintf('%s="%s"', $key, str_replace('"','\"',$val));
                    }
                }

                // 결과물에 있는 css Meta 목록을 구해와서 해당 css를 아예 읽어버림
                require_once("./classes/optimizer/Optimizer.class.php");
                $oOptimizer = new Optimizer();
                preg_match_all('!<\!\-\-Meta:([^\-]*?)\-\->!is', $html, $matches);
                $css_header = null;
                for($i=0;$i<count($matches[1]);$i++) {
                    $css_file = $matches[1][$i];
                    $buff = FileHandler::readFile($css_file);
                    $css_header .= $oOptimizer->replaceCssPath($css_file, $buff)."\n";
                }

                if(!$html) $html = '&nbsp;';
                $output = sprintf(
                        '<style type="text/css">%s</style>'.
                        '<div class="widgetOutput" style="%s" widget="%s" %s >'.
                            '<div class="widgetSetup"></div>'.
                            '<div class="widgetSize"></div>'.
                            '<div class="widgetRemove"></div>'.
                            '<div class="widgetResize"></div>'.
                            '<div class="widgetResizeLeft"></div>'.
                            '<div class="widgetBorder">'.
                                '<div style="%s">'.
                                    '%s'.
                                '</div><div class="clear"></div>'.
                            '</div>'.
                        '</div>', 
                        $css_header, 
                        $args->style, $widget, implode(' ',$attribute), 
                        $inner_style, 
                        $html
                );
            }

            // 위젯 결과물 생성 시간을 debug 정보에 추가
            if(__DEBUG__==3) $GLOBALS['__widget_excute_elapsed__'] += getMicroTime() - $start;

            // 결과 return
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
