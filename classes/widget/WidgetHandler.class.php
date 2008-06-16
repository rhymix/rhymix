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
        function getCache($widget, $args, $lang_type = null, $ignore_cache = false) {
            // 지정된 언어가 없으면 현재 언어 지정
            if(!$lang_type) $lang_type = Context::getLangType();

            // widget, 캐시 번호와 캐시값이 설정되어 있는지 확인
            $widget_sequence = $args->widget_sequence;
            $widget_cache = $args->widget_cache;

            /**
             * 캐시 번호와 캐시 값이 아예 없으면 바로 데이터를 추출해서 리턴
             **/
            if(!$ignore_cache && (!$widget_cache || !$widget_sequence)) {
                $oWidget = WidgetHandler::getObject($widget);
                if(!$oWidget) return;

                return $oWidget->proc($args);
            }

            /**
             * 캐시 번호와 캐시값이 설정되어 있으면 캐시 파일을 불러오도록 함
             **/
            
            // 캐시 디렉토리가 없으면 생성
            $cache_path = './files/cache/widget_cache/';
            if(!is_dir($cache_path)) FileHandler::makeDir($cache_path);

            // 캐시파일명을 구함
            $cache_file = sprintf('%s%d.%s.cache', $cache_path, $widget_sequence, $lang_type);

            // 캐시 Lock 파일을 구함
            $lock_file = sprintf('%s%d.%s.lock', $cache_path, $widget_sequence, $lang_type);

            // 캐시 파일이 존재하면 해당 파일의 유효성 검사 (lock파일이 있을 경우 유효성 검사하지 않음)
            if(!$ignore_cache && file_exists($cache_file)) {
                $filemtime = filemtime($cache_file);

                // 수정 시간을 비교해서 캐싱중이어야 하거나 WidgetHandler.class.php 파일보다 나중에 만들어 졌다면 캐시값을 return
                if(file_exists($lock_file) || ($filemtime + $widget_cache*60 > time() && $filemtime > filemtime('./classes/widget/WidgetHandler.class.php'))) {
                    return FileHandler::readFile($cache_file);
                }
            }

            // lock 파일 생성
            FileHandler::writeFile($lock_file, '');

            // 캐시 파일을 갱신하여야 할 경우 lock파일을 만들고 캐시 생성
            $oWidget = WidgetHandler::getObject($widget);
            if(!$oWidget) return;

            $widget_content = $oWidget->proc($args);
            FileHandler::writeFile($cache_file, $widget_content);

            // lock 파일 제거
            @unlink($lock_file);

            return $widget_content;
        }

        /**
         * @brief 위젯이름과 인자를 받아서 결과를 생성하고 결과 리턴
         * 태그 사용 templateHandler에서 WidgetHandler::execute()를 실행하는 코드로 대체하게 된다
         *
         * $include_info가 true일 경우 페이지 수정시 위젯 핸들링을 위한 코드까지 포함함
         **/
        function execute($widget, $args, $include_info = false) {
            // 디버그를 위한 위젯 실행 시간 저장
            if(__DEBUG__==3) $start = getMicroTime();

            // args값에서 urldecode를 해줌
            $object_vars = get_object_vars($args);
            if(count($object_vars)) {
                foreach($object_vars as $key => $val) {
                    if(in_array($key, array('body','class','style','widget_sequence','widget','widget_padding_left','widget_padding_top','widget_padding_bottom','widget_padding_right'))) continue;
                    $args->{$key} = utf8RawUrlDecode($val);
                }
            }

            /**
             * 위젯이 widgetContent/ widgetBox가 아니라면 내용을 구함
             **/
            if($widget != 'widgetContent' && $widget != 'widgetBox') {
                if(!is_dir(sprintf('./widgets/%s/',$widget))) return;

                // 위젯의 내용을 담을 변수
                $widget_content = WidgetHandler::getCache($widget, $args);
            }

            /**
             * 관리자가 지정한 위젯의 style을 구함
             **/
            // 가끔 잘못된 코드인 background-image:url(none)이 들어 있을 수가 있는데 이럴 경우 none에 대한 url을 요청하므로 무조건 제거함
            $style = preg_replace('/background\-image: url\(none\)/is','', $args->style);

            // 내부 여백을 둔 것을 구해서 style문으로 미리 변경해 놓음
            $widget_padding_left = $args->widget_padding_left;
            $widget_padding_right = $args->widget_padding_right;
            $widget_padding_top = $args->widget_padding_top;
            $widget_padding_bottom = $args->widget_padding_bottom;
            $inner_style = sprintf("padding:%dpx %dpx %dpx %dpx !important; padding:none !important;", $widget_padding_top, $widget_padding_right, $widget_padding_bottom, $widget_padding_left);

            /**
             * 위젯 출력물을 구함
             **/
            // 일반 페이지 호출일 경우 지정된 스타일만 꾸면서 바로 return 함
            if(!$include_info) {
                switch($widget) {
                    // 내용 직접 추가일 경우 
                    case 'widgetContent' :
                            $body = base64_decode($args->body);
                            $output = sprintf('<div style="overflow:hidden;%s"><div style="%s">%s</div></div>', $style, $inner_style, $body);
                        break;

                    // 위젯 박스일 경우
                    case 'widgetBox' :
                            $output = sprintf('<div style="overflow:hidden;%s;"><div style="%s"><div>', $style, $inner_style);
                        break;

                    // 일반 위젯일 경우
                    default :
                            $output = sprintf('<div style="overflow:hidden;%s;"><div style="%s">%s</div></div>', $style, $inner_style, $widget_content);
                        break;
                }

            // 페이지 수정시에 호출되었을 경우 위젯 핸들링을 위한 코드 추가
            } else {
                switch($widget) {
                    // 내용 직접 추가일 경우 
                    case 'widgetContent' :
                            $body = base64_decode($args->body);
                            $oWidgetController = &getController('widget');

                            $output = sprintf(
                                '<div class="widgetOutput" style="%s" widget_padding_left="%s" widget_padding_right="%s" widget_padding_top="%s" widget_padding_bottom="%s" widget="widgetContent">'.
                                    '<div class="widgetSetup"></div>'.
                                    '<div class="widgetCopy"></div>'.
                                    '<div class="widgetSize"></div>'.
                                    '<div class="widgetRemove"></div>'.
                                    '<div class="widgetResize"></div>'.
                                    '<div class="widgetResizeLeft"></div>'.
                                    '<div class="widgetBorder">'.
                                        '<div style="%s">'.
                                            '%s'.
                                        '</div><div class="clear"></div>'.
                                    '</div>'.
                                    '<div class="widgetContent" style="display:none;width:1px;height:1px;overflow:hidden;">%s</div>'.
                                '</div>',
                                $style,
                                $args->widget_padding_left, $args->widget_padding_right, $args->widget_padding_top, $args->widget_padding_bottom,
                                $inner_style,
                                $body,
                                base64_encode($body)
                            );
                        break;

                    // 위젯 박스일 경우
                    case 'widgetBox' :
                            $output = sprintf(
                                '<div class="widgetOutput" widget="widgetBox" style="%s;" widget_padding_top="%s" widget_padding_right="%s" widget_padding_bottom="%s" widget_padding_left="%s">'.
                                    '<div class="widgetBoxCopy"></div>'.
                                    '<div class="widgetBoxSize"></div>'.
                                    '<div class="widgetBoxRemove"></div>'.
                                    '<div class="widgetBoxResize"></div>'.
                                    '<div class="widgetBoxResizeLeft"></div>'.
                                    '<div class="widgetBoxBorder">'.
                                        '<div class="nullWidget" style="%s">', 
                                    $style, $widget_padding_top, $widget_padding_right, $widget_padding_bottom, $widget_padding_left, $inner_style);
                        break;

                    // 일반 위젯일 경우
                    default :
                            // args 정리
                            $attribute = array();
                            if($args) {
                                foreach($args as $key => $val) {
                                    if(in_array($key, array('class','style','widget_padding_top','widget_padding_right','widget_padding_bottom','widget_padding_left','widget'))) continue;
                                    if(strpos($val,'|@|')>0) $val = str_replace('|@|',',',$val);
                                    $attribute[] = sprintf('%s="%s"', $key, str_replace('"','\"',$val));
                                }
                            }

                            $output = sprintf(
                                    '<div class="widgetOutput" style="%s" widget_padding_top="%s" widget_padding_right="%s" widget_padding_bottom="%s" widget_padding_left="%s" widget="%s" %s >'.
                                        '<div class="widgetSetup"></div>'.
                                        '<div class="widgetCopy"></div>'.
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
                                    $style, 
                                    $widget_padding_top, $widget_padding_right, $widget_padding_bottom, $widget_padding_left, 
                                    $widget, implode(' ',$attribute), 
                                    $inner_style, 
                                    $widget_content
                            );
                        break;
                }
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
