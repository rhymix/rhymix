<?php
    /**
     * @class  widgetController
     * @author NHN (developers@xpressengine.com)
     * @brief  widget 모듈의 Controller class
     **/

    class widgetController extends widget {

        // 위젯을 결과물이 아닌 수정/삭제등을 하기 위한 곳에서 사용하기 위한 flag 
        // layout_javascript_mode 는 모든 결과물까지 포함하여 javascript mode로 변환시킴
        var $javascript_mode = false;
        var $layout_javascript_mode = false;

        // 위젯 캐시 파일이 생성되는 곳
        var $cache_path = './files/cache/widget_cache/';

        /**
         * @brief 초기화
         **/
        function init() {
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

        /**
         * @brief 위젯의 생성된 코드를 return
         **/
        function procWidgetGenerateCode() {
            $widget = Context::get('selected_widget');
            if(!$widget) return new Object(-1,'msg_invalid_request');
            if(!Context::get('skin')) return new Object(-1,Context::getLang('msg_widget_skin_is_null'));

            $attribute = $this->arrangeWidgetVars($widget, Context::getRequestVars(), $vars);

            $widget_code = sprintf('<img class="zbxe_widget_output" widget="%s" %s />', $widget, implode(' ',$attribute));

            // 코드 출력
            $this->add('widget_code', $widget_code);
        }

        /**
         * @brief 페이지 수정시 위젯 코드의 생성 요청
         **/
        function procWidgetGenerateCodeInPage() {
            $widget = Context::get('selected_widget');
            if(!$widget) return new Object(-1,'msg_invalid_request');

            if(!in_array($widget,array('widgetBox','widgetContent')) && !Context::get('skin')) return new Object(-1,Context::getLang('msg_widget_skin_is_null'));

            $attribute = $this->arrangeWidgetVars($widget, Context::getRequestVars(), $vars);

            // 결과물을 구함
            $widget_code = $this->execute($widget, $vars, true, false);

            $this->add('widget_code', $widget_code);
        }

        /**
         * @brief 위젯스타일에 이미지 업로드
         **/
        function procWidgetStyleExtraImageUpload(){
            $attribute = $this->arrangeWidgetVars($widget, Context::getRequestVars(), $vars);

            $this->setLayoutPath('./common/tpl');
            $this->setLayoutFile('default_layout.html');
            $this->setTemplatePath($this->module_path.'tpl');
            $this->setTemplateFile("top_refresh.html");
        }

        /**
         * @brief 컨텐츠 위젯 추가
         **/
        function procWidgetInsertDocument() {

            // 변수 구함
            $module_srl = Context::get('module_srl');
            $document_srl = Context::get('document_srl');
            $content = Context::get('content');
            $editor_sequence = Context::get('editor_sequence');

            $err = 0;
            $oLayoutModel = &getModel('layout');
            $layout_info = $oLayoutModel->getLayout($module_srl);
            if(!$layout_info || $layout_info->type != 'faceoff') $err++;

            // 대상 페이지 모듈 정보 구함
            $oModuleModel = &getModel('module');
            $page_info = $oModuleModel->getModuleInfoByModuleSrl($module_srl);
            if(!$page_info->module_srl || $page_info->module != 'page') $err++;

            if($err > 1) return new Object(-1,'msg_invalid_request');

            // 권한 체크
            $is_logged = Context::get('is_logged');
            $logged_info = Context::get('logged_info');
            $user_group = $logged_info->group_list;
            $is_admin = false;
            if(count($user_group)&&count($page_info->grants['manager'])) {
                $manager_group = $page_info->grants['manager'];
                foreach($user_group as $group_srl => $group_info) {
                    if(in_array($group_srl, $manager_group)) $is_admin = true;
                }
            }
            if(!$is_admin && !$is_logged && $logged_info->is_admin != 'Y' && !$oModuleModel->isSiteAdmin($logged_info) && !(is_array($page_info->admin_id) && in_array($logged_infoi->user_id, $page_info->admin_id))) return new Object(-1,'msg_not_permitted');


            // 글 입력
            $oDocumentModel = &getModel('document');
            $oDocumentController = &getController('document');

            $obj->module_srl = $module_srl;
            $obj->content = $content;
            $obj->document_srl = $document_srl;

            $oDocument = $oDocumentModel->getDocument($obj->document_srl, true);
            if($oDocument->isExists() && $oDocument->document_srl == $obj->document_srl) {
                $output = $oDocumentController->updateDocument($oDocument, $obj);
            } else {
                $output = $oDocumentController->insertDocument($obj);
                $obj->document_srl = $output->get('document_srl');
            }

            // 오류 발생시 멈춤
            if(!$output->toBool()) return $output;

            // 결과를 리턴
            $this->add('document_srl', $obj->document_srl);
        }

        /**
         * @brief 컨텐츠 위젯 복사
         **/
        function procWidgetCopyDocument() {
            // 변수 구함
            $document_srl = Context::get('document_srl');

            $oDocumentModel = &getModel('document');
            $oDocumentController = &getController('document');
            $oDocumentAdminController = &getAdminController('document');

            $oDocument = $oDocumentModel->getDocument($document_srl, true);
            if(!$oDocument->isExists()) return new Object(-1,'msg_invalid_request');
            $module_srl = $oDocument->get('module_srl');

            // 대상 페이지 모듈 정보 구함
            $oModuleModel = &getModel('module');
            $page_info = $oModuleModel->getModuleInfoByModuleSrl($module_srl);
            if(!$page_info->module_srl || $page_info->module != 'page') return new Object(-1,'msg_invalid_request');

            // 권한 체크
            $is_logged = Context::get('is_logged');
            $logged_info = Context::get('logged_info');
            $user_group = $logged_info->group_list;
            $is_admin = false;
            if(count($user_group)&&count($page_info->grants['manager'])) {
                $manager_group = $page_info->grants['manager'];
                foreach($user_group as $group_srl => $group_info) {
                    if(in_array($group_srl, $manager_group)) $is_admin = true;
                }
            }
            if(!$is_admin && !$is_logged && $logged_info->is_admin != 'Y' && !$oModuleModel->isSiteAdmin($logged_info) && !(is_array($page_info->admin_id) && in_array($logged_infoi->user_id, $page_info->admin_id))) return new Object(-1,'msg_not_permitted');

            $output = $oDocumentAdminController->copyDocumentModule(array($oDocument->get('document_srl')), $oDocument->get('module_srl'),0);
            if(!$output->toBool()) return $output;

            // 결과를 리턴
            $copied_srls = $output->get('copied_srls');
            $this->add('document_srl', $copied_srls[$oDocument->get('document_srl')]);
        }

        /**
         * @brief 위젯 삭제
         **/
        function procWidgetDeleteDocument() {
            // 변수 구함
            $document_srl = Context::get('document_srl');

            $oDocumentModel = &getModel('document');
            $oDocumentController = &getController('document');

            $oDocument = $oDocumentModel->getDocument($document_srl, true);
            if(!$oDocument->isExists()) return new Object();
            $module_srl = $oDocument->get('module_srl');

            // 대상 페이지 모듈 정보 구함
            $oModuleModel = &getModel('module');
            $page_info = $oModuleModel->getModuleInfoByModuleSrl($module_srl);
            if(!$page_info->module_srl || $page_info->module != 'page') return new Object(-1,'msg_invalid_request');

            // 권한 체크
            $is_logged = Context::get('is_logged');
            $logged_info = Context::get('logged_info');
            $user_group = $logged_info->group_list;
            $is_admin = false;
            if(count($user_group)&&count($page_info->grants['manager'])) {
                $manager_group = $page_info->grants['manager'];
                foreach($user_group as $group_srl => $group_info) {
                    if(in_array($group_srl, $manager_group)) $is_admin = true;
                }
            }
            if(!$is_admin && !$is_logged && $logged_info->is_admin != 'Y' && !$oModuleModel->isSiteAdmin($logged_info) && !(is_array($page_info->admin_id) && in_array($logged_infoi->user_id, $page_info->admin_id))) return new Object(-1,'msg_not_permitted');

            $output = $oDocumentController->deleteDocument($oDocument->get('document_srl'), true);
            if(!$output->toBool()) return $output;
        }

        /**
         * @brief 위젯 코드를 Javascript로 수정/드래그등을 하기 위한 Javascript 수정 모드로 변환
         **/
        function setWidgetCodeInJavascriptMode() {
            $this->layout_javascript_mode = true;
        }

        /**
         * @brief 위젯 코드를 컴파일하여 내용을 출력하는 trigger
         * display::before 에서 호출됨
         **/
        function triggerWidgetCompile(&$content) {
            if(Context::getResponseMethod()!='HTML') return new Object();
            $content = $this->transWidgetCode($content, $this->layout_javascript_mode);
            return new Object();
        }

        /**
         * @breif 특정 content의 위젯 태그들을 변환하여 return
         **/
        function transWidgetCode($content, $javascript_mode = false) {
            // 사용자 정의 언어 변경
            $oModuleController = &getController('module');
            $oModuleController->replaceDefinedLangCode($content);

            // 편집 정보 포함 여부 체크
            $this->javascript_mode = $javascript_mode;

            // 박스 위젯 코드 변경
            $content = preg_replace_callback('!<div([^\>]*)widget=([^\>]*?)\><div><div>((<img.*?>)*)!is', array($this,'transWidgetBox'), $content);

            // 내용 위젯 코드 벼경
            $content = preg_replace_callback('!<img([^\>]*)widget=([^\>]*?)\>!is', array($this,'transWidget'), $content);

            return $content;
        }

        /**
         * @brief 위젯 코드를 실제 코드로 변경
         **/
        function transWidget($matches) {
            $buff = trim($matches[0]);

            $oXmlParser = new XmlParser();
            $xml_doc = $oXmlParser->parse(trim($buff));

            if($xml_doc->img) $vars = $xml_doc->img->attrs;
            else $vars = $xml_doc->attrs;

            $widget = $vars->widget;
            if(!$widget) return $match[0];
            unset($vars->widget);

            return $this->execute($widget, $vars, $this->javascript_mode);
        }

        /**
         * @brief 위젯 박스를 실제 코드로 변경
         **/
        function transWidgetBox($matches) {
            $buff = preg_replace('/<div><div>(.*)$/i','</div>',$matches[0]);
            $oXmlParser = new XmlParser();
            $xml_doc = $oXmlParser->parse($buff);

            $vars = $xml_doc->div->attrs;
            $widget = $vars->widget;
            if(!$widget) return $matches[0];
            unset($vars->widget);

            $vars->widgetbox_content = $matches[3];
            return $this->execute($widget, $vars, $this->javascript_mode);
        }

        /**
         * @brief 특정 content내의 위젯을 다시 생성
         * 페이지모듈등에서 위젯 캐시파일 재생성시 사용
         **/
        function recompileWidget($content) {
            // 언어 종류 가져옴
            $lang_list = Context::get('lang_supported');

            // 위젯 캐시 sequence 를 가져옴
            preg_match_all('!<img([^\>]*)widget=([^\>]*?)\>!is', $content, $matches);

            $oXmlParser = new XmlParser();

            $cnt = count($matches[1]);
            for($i=0;$i<$cnt;$i++) {
                $buff = $matches[0][$i];
                $xml_doc = $oXmlParser->parse(trim($buff));

                $args = $xml_doc->img->attrs;
                if(!$args) continue;

                // 캐싱하지 않을 경우 패스
                $widget = $args->widget;
                $sequence = $args->widget_sequence;
                $cache = $args->widget_cache;
                if(!$sequence || !$cache) continue;

                if(count($args)) {
                    foreach($args as $k => $v) $args->{$k} = urldecode($v);
                }

                // 언어별로 위젯 캐시 파일이 있을 경우 재생성
                foreach($lang_list as $lang_type => $val) {
                    $cache_file = sprintf('%s%d.%s.cache', $this->cache_path, $sequence, $lang_type);
                    if(!file_exists($cache_file)) continue;
                    $this->getCache($widget, $args, $lang_type, true);
                }
            }
        }

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
                $oWidget = $this->getWidgetObject($widget);
                if(!$oWidget || !method_exists($oWidget, 'proc')) return;
                return $oWidget->proc($args);
            }

            /**
             * 캐시 번호와 캐시값이 설정되어 있으면 캐시 파일을 불러오도록 함
             **/
            if(!is_dir($this->cache_path)) FileHandler::makeDir($this->cache_path);

            // 캐시파일명을 구함
            $cache_file = sprintf('%s%d.%s.cache', $this->cache_path, $widget_sequence, $lang_type);

            // 캐시 파일이 존재하면 해당 파일의 유효성 검사
            if(!$ignore_cache && file_exists($cache_file)) {
                $filemtime = filemtime($cache_file);

                // 수정 시간을 비교해서 캐싱중이어야 하거나 widget.controller.php 파일보다 나중에 만들어 졌다면 캐시값을 return
                if($filemtime + $widget_cache * 60 > time() && $filemtime > filemtime(_XE_PATH_.'modules/widget/widget.controller.php')) {
                    return FileHandler::readFile($cache_file);
                }
            }

            // cache 파일의 mtime 갱신하고 캐시 갱신
            touch($cache_file);

            $oWidget = $this->getWidgetObject($widget);
            if(!$oWidget || !method_exists($oWidget,'proc')) return;

            $widget_content = $oWidget->proc($args);
            FileHandler::writeFile($cache_file, $widget_content);

            return $widget_content;
        }

        /**
         * @brief 위젯이름과 인자를 받아서 결과를 생성하고 결과 리턴
         * 태그 사용 templateHandler에서 $this->execute()를 실행하는 코드로 대체하게 된다
         *
         * $javascript_mode가 true일 경우 페이지 수정시 위젯 핸들링을 위한 코드까지 포함함
         **/
        function execute($widget, $args, $javascript_mode = false, $escaped = true) {
            // 디버그를 위한 위젯 실행 시간 저장
            if(__DEBUG__==3) $start = getMicroTime();

            // args값에서 urldecode를 해줌
            $object_vars = get_object_vars($args);
            if(count($object_vars)) {
                foreach($object_vars as $key => $val) {
                    if(in_array($key, array('widgetbox_content','body','class','style','widget_sequence','widget','widget_padding_left','widget_padding_top','widget_padding_bottom','widget_padding_right','widgetstyle','document_srl'))) continue;
                    if($escaped) $args->{$key} = utf8RawUrlDecode($val);
                }
            }

            /**
             * 위젯이 widgetContent/ widgetBox가 아니라면 내용을 구함
             **/
            $widget_content = '';
            if($widget != 'widgetContent' && $widget != 'widgetBox') {
                if(!is_dir(sprintf(_XE_PATH_.'widgets/%s/',$widget))) return;

                // 위젯의 내용을 담을 변수
                $widget_content = $this->getCache($widget, $args);
            }

            if($widget == 'widgetBox'){
                $widgetbox_content = $args->widgetbox_content;
            }

            /**
             * 관리자가 지정한 위젯의 style을 구함
             **/

            // 가끔 잘못된 코드인 background-image:url(none)이 들어 있을 수가 있는데 이럴 경우 none에 대한 url을 요청하므로 무조건 제거함
            $style = preg_replace('/url\((.+)(\/?)none\)/is','', $args->style);

            // 내부 여백을 둔 것을 구해서 style문으로 미리 변경해 놓음
            $widget_padding_left = $args->widget_padding_left;
            $widget_padding_right = $args->widget_padding_right;
            $widget_padding_top = $args->widget_padding_top;
            $widget_padding_bottom = $args->widget_padding_bottom;
            $inner_style = sprintf("padding:%dpx %dpx %dpx %dpx !important; padding:none !important;", $widget_padding_top, $widget_padding_right, $widget_padding_bottom, $widget_padding_left);

            /**
             * 위젯 출력물을 구함
             **/

            $widget_content_header = '';
            $widget_content_body = '';
            $widget_content_footer = '';

            // 일반 페이지 호출일 경우 지정된 스타일만 꾸면서 바로 return 함
            if(!$javascript_mode) {
                if($args->id) $args->id = ' id="'.$args->id.'" ';
                switch($widget) {
                    // 내용 직접 추가일 경우 
                    case 'widgetContent' :
                            if($args->document_srl) {
                                $oDocumentModel = &getModel('document');
                                $oDocument = $oDocumentModel->getDocument($args->document_srl);
                                $body = $oDocument->getContent(false,false,false, false);
                            } else {
                                $body = base64_decode($args->body);
                            }

                            // 에디터컴포넌트 변경
                            $oEditorController = &getController('editor');
                            $body = $oEditorController->transComponent($body);

                            $widget_content_header = sprintf('<div %sstyle="overflow:hidden;%s"><div style="%s">', $args->id, $style,  $inner_style);
                            $widget_content_body = $body;
                            $widget_content_footer = '</div></div>';

                        break;

                    // 위젯 박스일 경우
                    case 'widgetBox' :
                            $widget_content_header = sprintf('<div %sstyle="overflow:hidden;%s;"><div style="%s"><div>', $args->id, $style,  $inner_style);
                            $widget_content_body = $widgetbox_content;

                        break;

                    // 일반 위젯일 경우
                    default :
                            $widget_content_header = sprintf('<div %sstyle="overflow:hidden;%s">',$args->id,$style);
                            $widget_content_body = sprintf('<div style="*zoom:1;%s">%s</div>', $inner_style,$widget_content);
                            $widget_content_footer = '</div>';
                        break;
                }

            // 페이지 수정시에 호출되었을 경우 위젯 핸들링을 위한 코드 추가
            } else {
                switch($widget) {
                    // 내용 직접 추가일 경우 
                    case 'widgetContent' :
                            if($args->document_srl) {
                                $oDocumentModel = &getModel('document');
                                $oDocument = $oDocumentModel->getDocument($args->document_srl);
                                $body = $oDocument->getContent(false,false,false);
                            } else {
                                $body = base64_decode($args->body);
                            }

                            // args 정리
                            $attribute = array();
                            if($args) {
                                foreach($args as $key => $val) {
                                    if(in_array($key, array('class','style','widget_padding_top','widget_padding_right','widget_padding_bottom','widget_padding_left','widget','widgetstyle','document_srl'))) continue;
                                    if(strpos($val,'|@|')>0) $val = str_replace('|@|',',',$val);
                                    $attribute[] = sprintf('%s="%s"', $key, str_replace('"','\"',$val));
                                }
                            }

                            $oWidgetController = &getController('widget');

                            $widget_content_header = sprintf(
                                '<div class="widgetOutput" widgetstyle="%s" style="%s" widget_padding_left="%s" widget_padding_right="%s" widget_padding_top="%s" widget_padding_bottom="%s" widget="widgetContent" document_srl="%d" %s>'.
                                    '<div class="widgetResize"></div>'.
                                    '<div class="widgetResizeLeft"></div>'.
                                    '<div class="widgetBorder">'.
                                        '<div style="%s">',$args->widgetstyle,
                                $style,
                                $args->widget_padding_left, $args->widget_padding_right, $args->widget_padding_top, $args->widget_padding_bottom,
                                $args->document_srl,
                                implode(' ',$attribute),
                                $inner_style);

                            $widget_content_body = $body;
                            $widget_content_footer = sprintf('</div><div class="clear"></div>'.
                                    '</div>'.
                                    '<div class="widgetContent" style="display:none;width:1px;height:1px;overflow:hidden;">%s</div>'.
                                '</div>',base64_encode($body));

                        break;

                    // 위젯 박스일 경우
                    case 'widgetBox' :

                            // args 정리
                            $attribute = array();
                            if($args) {
                                foreach($args as $key => $val) {
                                    if(in_array($key, array('class','style','widget_padding_top','widget_padding_right','widget_padding_bottom','widget_padding_left','widget','widgetstyle','document_srl'))) continue;
                                    if(strpos($val,'|@|')>0) $val = str_replace('|@|',',',$val);
                                    $attribute[] = sprintf('%s="%s"', $key, str_replace('"','\"',$val));
                                }
                            }

                            $widget_content_header = sprintf(
                                '<div class="widgetOutput" widgetstyle="%s" widget="widgetBox" style="%s;" widget_padding_top="%s" widget_padding_right="%s" widget_padding_bottom="%s" widget_padding_left="%s" %s >'.
                                    '<div class="widgetBoxResize"></div>'.
                                    '<div class="widgetBoxResizeLeft"></div>'.
                                    '<div class="widgetBoxBorder"><div class="nullWidget" style="%s">',$args->widgetstyle,$style, $widget_padding_top, $widget_padding_right, $widget_padding_bottom, $widget_padding_left,implode(' ',$attribute),$inner_style);

                            $widget_content_body = $widgetbox_content;

                        break;

                    // 일반 위젯일 경우
                    default :
                            // args 정리
                            $attribute = array();
                            if($args) {
                                foreach($args as $key => $val) {
                                    if(in_array($key, array('class','style','widget_padding_top','widget_padding_right','widget_padding_bottom','widget_padding_left','widget'))) continue;
                                    if(strlen($val)==0) continue;
                                    if(strpos($val,'|@|')>0) $val = str_replace('|@|',',',$val);
                                    $attribute[] = sprintf('%s="%s"', $key, str_replace('"','\"',$val));
                                }
                            }

                            $widget_content_header = sprintf('<div class="widgetOutput" widgetstyle="%s" style="%s" widget_padding_top="%s" widget_padding_right="%s" widget_padding_bottom="%s" widget_padding_left="%s" widget="%s" %s >'.
                                        '<div class="widgetResize"></div>'.
                                        '<div class="widgetResizeLeft"></div>'.
                                        '<div class="widgetBorder">',$args->widgetstyle,$style, 
                                    $widget_padding_top, $widget_padding_right, $widget_padding_bottom, $widget_padding_left, 
                                    $widget, implode(' ',$attribute));

                            $widget_content_body = sprintf('<div style="%s">%s</div><div class="clear"></div>',$inner_style, $widget_content);

                            $widget_content_footer = '</div></div>';

                        break;
                }
            }


            // 위젯 스타일을 컴파일 한다.
            if($args->widgetstyle) $widget_content_body = $this->compileWidgetStyle($args->widgetstyle,$widget, $widget_content_body, $args, $javascript_mode);

            $output = $widget_content_header . $widget_content_body . $widget_content_footer;

            // 위젯 결과물 생성 시간을 debug 정보에 추가
            if(__DEBUG__==3) $GLOBALS['__widget_excute_elapsed__'] += getMicroTime() - $start;

            // 결과 return
            return $output;
        }

        /**
         * @brief 위젯 객체를 return
         **/
        function getWidgetObject($widget) {
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


        function compileWidgetStyle($widgetStyle,$widget,$widget_content_body, $args, $javascript_mode){
            if(!$widgetStyle) return $widget_content_body;

            $oWidgetModel = &getModel('widget');

            // 위젯 스타일의 extra_var를 가져와 묶는다
            $widgetstyle_info = $oWidgetModel->getWidgetStyleInfo($widgetStyle);
            if(!$widgetstyle_info) return $widget_content_body;

            $widgetstyle_extar_var_key = get_object_vars($widgetstyle_info);
            if(count($widgetstyle_extar_var_key['extra_var'])){
                foreach($widgetstyle_extar_var_key['extra_var'] as $key => $val){
                    $widgetstyle_extar_var->{$key} =  $args->{$key};
                }
            }
            Context::set('widgetstyle_extar_var', $widgetstyle_extar_var);

            if($javascript_mode && $widget=='widgetBox'){
                Context::set('widget_content', '<div class="widget_inner">'.$widget_content_body.'</div>');
            }else{
                Context::set('widget_content', $widget_content_body);
            }

            // 컴파일
            $widgetstyle_path = $oWidgetModel->getWidgetStylePath($widgetStyle);
            $oTemplate = &TemplateHandler::getInstance();
            $tpl = $oTemplate->compile($widgetstyle_path, 'widgetstyle');

            return $tpl;
        }

        /**
         * @brief request 변수와 위젯 정보를 통해 변수 정렬
         **/
        function arrangeWidgetVars($widget, $request_vars, &$vars) {
            $oWidgetModel = &getModel('widget');
            $widget_info = $oWidgetModel->getWidgetInfo($widget);

            $widget = $vars->selected_widget;
            $vars->widgetstyle = $request_vars->widgetstyle;

            $vars->skin = trim($request_vars->skin);
            $vars->colorset = trim($request_vars->colorset);
            $vars->widget_sequence = (int)($request_vars->widget_sequence);
            $vars->widget_cache = (int)($request_vars->widget_cache);
            $vars->style = trim($request_vars->style);
            $vars->widget_padding_left = trim($request_vars->widget_padding_left);
            $vars->widget_padding_right = trim($request_vars->widget_padding_right);
            $vars->widget_padding_top = trim($request_vars->widget_padding_top);
            $vars->widget_padding_bottom = trim($request_vars->widget_padding_bottom);
            $vars->document_srl= trim($request_vars->document_srl);


            if(count($widget_info->extra_var)) {
                foreach($widget_info->extra_var as $key=>$val) {
                    $vars->{$key} = trim($request_vars->{$key});
                }
            }

            // 위젯 스타일이 있는 경우
            if($request_vars->widgetstyle){
                $widgetStyle_info = $oWidgetModel->getWidgetStyleInfo($request_vars->widgetstyle);
                if(count($widgetStyle_info->extra_var)) {
                    foreach($widgetStyle_info->extra_var as $key=>$val) {
                        if($val->type =='color' || $val->type =='text' || $val->type =='select' || $val->type =='filebox'){
                            $vars->{$key} = trim($request_vars->{$key});
                        }
                    }
                }
            }

            if($vars->widget_sequence) {
                $cache_file = sprintf('%s%d.%s.cache', $this->cache_path, $vars->widget_sequence, Context::getLangType());
                FileHandler::removeFile($cache_file);
            }

            if($vars->widget_cache>0) $vars->widget_sequence = getNextSequence();

            $attribute = array();
            foreach($vars as $key => $val) {
                if(!$val) {
                    unset($vars->{$key});
                    continue;
                }
                if(strpos($val,'|@|') > 0) $val = str_replace('|@|', ',', $val);
                $vars->{$key} = htmlspecialchars(Context::convertEncodingStr($val));
                $attribute[] = sprintf('%s="%s"', $key, Context::convertEncodingStr($val));
            }

            return $attribute;
        }
    }
?>
