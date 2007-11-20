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
         * @brief 페이지 수정시 위젯 코드의 생성 요청
         **/
        function procWidgetGenerateCodeInPage() {
            // 먼저 정상적인 widget 코드를 구함
            $this->procWidgetGenerateCode();
            $widget_code = $this->get('widget_code');

            // 변수 정리
            $vars = Context::getRequestVars();
            $widget = $vars->selected_widget;
            unset($vars->module);
            unset($vars->body);
            unset($vars->act);
            unset($vars->selected_widget);

            if($vars->widget_sequence) {
                $cache_path = './files/cache/widget_cache/';
                $cache_file = sprintf('%s%d.%s.cache', $cache_path, $vars->widget_sequence, Context::getLangType());
                @unlink($cache_file);
            }
            $vars->widget_sequence = getNextSequence();
            if(!$vars->widget_cache) $vars->widget_cache = 0;

            // args 정리
            $attribute = array();
            if($vars) {
                foreach($vars as $key => $val) {
                    if(strpos($val,'|@|')>0) {
                        $val = str_replace('|@|',',',$val);
                        $vars->{$key} = $val;
                    }
                    $attribute[] = sprintf('%s="%s"', $key, str_replace('"','\"',$val));
                }
            }

            // 결과물을 구함
            $oWidgetHandler = new WidgetHandler();
            $widget_code = $oWidgetHandler->execute($widget, $vars, true);

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

        /**
         * @brief 직접 내용 입력된 위젯의 처리
         **/
        function procWidgetAddContent() {
            $content = Context::get('content');
            $args = Context::getRequestVars('style','widget_padding_left','widget_padding_right','widget_padding_bottom','widget_padding_top');

            $tpl = $this->transEditorContent($content, $args);

            $this->add('tpl', $tpl);
        }

        /**
         * @breif 특정 content의 위젯 태그들을 변환하여 return
         **/
        function transWidgetCode($content, $include_info = false) {
            $this->include_info = $include_info;

            // 내용중 widget이 아닌 것들을 일단 분리
            /*
            $none_widget_code = preg_replace('!<img([^\>]*)widget=([^\>]*?)\>!is', '', $content);
            $oPageAdminController = &getAdminController('page');
            if(trim($none_widget_code)) {
                $args->style = "float:left;overflow:hidden;padding:none;padding:none";
                $args->widget_padding_left = $args->widget_padding_top = $args->widget_padding_right = $args->widget_padding_bottom = 0;
                $none_widget_content = $oPageAdminController->transEditorContent($none_widget_code, $args);
            }
            */

            // 내용중 위젯을 또다시 구함 (기존 버전에서 페이지 수정해 놓은것과의 호환을 위해서)
            $content = preg_replace_callback('!<img([^\>]*)widget=([^\>]*?)\>!is', array($this,'transWidget'), $content);

            // 박스 위젯을 다시 구함
            $content = preg_replace_callback('!<div([^\>]*)widget=([^\>]*?)\><div><div>!is', array($this,'transWidgetBox'), $content);

            // include_info, 즉 위젯의 수정일 경우 css와 js파일을 추가해 주고 위젯 수정용 레이어도 추가함
            if($this->include_info) {
                Context::addJsFile("./modules/widget/tpl/js/widget.js");
                Context::addCSSFile("./modules/widget/tpl/css/widget.css");

                $oTemplate = &TemplateHandler::getInstance();
                $tpl = $oTemplate->compile($this->module_path.'tpl', 'widget_layer');
                $content .= $tpl;

            }

            return $content;
        }

        /**
         * @brief 위젯 코드를 실제 php코드로 변경
         **/
        function transWidget($matches) {
            $oContext = &Context::getInstance();
            $buff = trim($matches[0]);
            $buff = preg_replace_callback('/([^=^"^ ]*)=([^ ^>]*)/i', array($oContext, _fixQuotation), $buff);
            $buff = str_replace("&","&amp;",$buff);

            $oXmlParser = new XmlParser();
            $xml_doc = $oXmlParser->parse(trim($buff));

            if($xml_doc->img) $vars = $xml_doc->img->attrs;
            else $vars = $xml_doc->attrs;

            if(!$vars->widget) return "";

            // 캐시 체크
            $widget_sequence = $vars->widget_sequence;
            $widget_cache = $vars->widget_cache;
            if($widget_cache && $widget_sequence && !$this->include_info)  {
                $output = WidgetHandler::getCache($widget_sequence, $widget_cache);
                if($output) return $output;
            }

            // 위젯의 이름을 구함
            $widget = $vars->widget;
            unset($vars->widget);

            return WidgetHandler::execute($widget, $vars, $this->include_info);
        }

        /**
         * @brief 위젯 박스를 실제 php코드로 변경
         **/
        function transWidgetBox($matches) {
            $buff = preg_replace('/<div><div>$/i','</div>',$matches[0]);

            $oXmlParser = new XmlParser();
            $xml_doc = $oXmlParser->parse($buff);

            $vars = $xml_doc->div->attrs;
            $widget = $vars->widget;
            unset($vars->widget);

            // 위젯의 이름을 구함
            if(!$widget) return $matches[0];
            return WidgetHandler::execute($widget, $vars, $this->include_info);
        }

        /**
         * @brief 에디터에서 생성한 컨텐츠를 페이지 수정시 사용할 수 있도록 코드 생성
         **/
        function transEditorContent($content, $args = null) {
            // 에디터의 내용을 변환하여 visual한 영역과 원본 소스를 가지고 있는 code로 분리
            $code = $content;

            $oContext = &Context::getInstance();
            $content = preg_replace_callback('!<div([^\>]*)editor_component=([^\>]*)>(.*?)\<\/div\>!is', array($oContext,'transEditorComponent'), $content);
            $content = preg_replace_callback('!<img([^\>]*)editor_component=([^\>]*?)\>!is', array($oContext,'transEditorComponent'), $content);

            // 결과물에 있는 css Meta 목록을 구해와서 해당 css를 아예 읽어버림
            require_once("./classes/optimizer/Optimizer.class.php");
            $oOptimizer = new Optimizer();
            preg_match_all('!<\!\-\-Meta:([^\-]*?)\-\->!is', $content, $matches);
            $css_header = null;
            for($i=0;$i<count($matches[1]);$i++) {
                $css_file = $matches[1][$i];
                $buff = FileHandler::readFile($css_file);
                $css_header .= $oOptimizer->replaceCssPath($css_file, $buff)."\n";
            }

            $tpl = sprintf(
                    '<style type="text/css">%s</style>'.
                    '<div class="widgetOutput" style="%s" widget_padding_left="%s" widget_padding_right="%s" widget_padding_top="%s" widget_padding_bottom="%s" widget="widgetContent">'.
                        '<div class="widgetSetup"></div>'.
                        '<div class="widgetCopy"></div>'.
                        '<div class="widgetSize"></div>'.
                        '<div class="widgetRemove"></div>'.
                        '<div class="widgetResize"></div>'.
                        '<div class="widgetResizeLeft"></div>'.
                        '<div class="widgetBorder">'.
                            '<div style="padding:%s %s %s %s;">'.
                                '%s'.
                            '</div><div class="clear"></div>'.
                        '</div>'.
                        '<div class="widgetContent" style="display:none;width:1px;height:1px;overflow:hidden;">%s</div>'.
                    '</div>',
                    $css_header,
                    $args->style,
                    $args->widget_padding_left, $args->widget_padding_right, $args->widget_padding_top, $args->widget_padding_bottom,
                    $args->widget_padding_top, $args->widget_padding_right, $args->widget_padding_bottom, $args->widget_padding_left,
                    $content,
                    base64_encode($code)
            );

            return $tpl;
        }

    }
?>
