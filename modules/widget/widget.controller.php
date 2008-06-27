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
            unset($vars->body);

            if($vars->widget_sequence) {
                $cache_path = './files/cache/widget_cache/';
                $cache_file = sprintf('%s%d.%s.cache', $cache_path, $vars->widget_sequence, Context::getLangType());
                FileHandler::removeFile($cache_file);
            }

            if($vars->widget_cache>0) $vars->widget_sequence = getNextSequence();

            $attribute = array();
            if($vars) {
                foreach($vars as $key => $val) {
                    if(!$val) continue;
                    if(strpos($val,'|@|') > 0) $val = str_replace('|@|', ',', $val);
                    $val = htmlspecialchars($val);
                    $attribute[] = sprintf('%s="%s"', $key, $val);
                }
            }

            $widget_code = sprintf('<img class="zbxe_widget_output" widget="%s" %s />', $widget, implode(' ',$attribute));

            $cache_path = './files/cache/widget_cache/';
            $cache_file = sprintf('%s%d.%s.cache', $cache_path, $vars->widget_sequence, Context::getLangType());
            FileHandler::removeFile($cache_file);

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
            unset($vars->act);
            unset($vars->body);
            unset($vars->selected_widget);

            if($vars->widget_sequence) {
                $cache_path = './files/cache/widget_cache/';
                $cache_file = sprintf('%s%d.%s.cache', $cache_path, $vars->widget_sequence, Context::getLangType());
                FileHandler::removeFile($cache_file);
            }

            if($vars->widget_cache>0) $vars->widget_sequence = getNextSequence();

            // args 정리
            $attribute = array();
            if($vars) {
                foreach($vars as $key => $val) {
                    if(!$val) continue;
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
         * @breif 특정 content의 위젯 태그들을 변환하여 return
         **/
        function transWidgetCode($content, $include_info = false) {

            $this->include_info = $include_info;

            // 내용중 위젯을 또다시 구함 (기존 버전에서 페이지 수정해 놓은것과의 호환을 위해서)
            $content = preg_replace_callback('!<img([^\>]*)widget=([^\>]*?)\>!is', array($this,'transWidget'), $content);

            // 박스 위젯을 다시 구함
            $content = preg_replace_callback('!<div([^\>]*)widget=([^\>]*?)\><div><div>!is', array($this,'transWidgetBox'), $content);

            return $content;
        }

        /**
         * @brief 위젯 코드를 실제 php코드로 변경
         **/
        function transWidget($matches) {
            $buff = trim($matches[0]);

            $oXmlParser = new XmlParser();
            $xml_doc = $oXmlParser->parse(trim($buff));

            if($xml_doc->img) $vars = $xml_doc->img->attrs;
            else $vars = $xml_doc->attrs;

            if(!$vars->widget) return "";

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
         * @brief 특정 content내의 위젯을 다시 생성
         **/
        function recompileWidget($content) {
            // 언어 종류 가져옴
            $lang_list = Context::get('lang_supported');

            // 위젯 캐시 sequence 를 가져옴
            preg_match_all('!<img([^\>]*)widget=([^\>]*?)\>!is', $content, $matches);

            $cache_path = './files/cache/widget_cache/';

            $oWidget = new WidgetHandler();
            $oXmlParser = new XmlParser();

            for($i=0;$i<count($matches[1]);$i++) {
                $buff = urldecode($matches[0][$i]);
                $xml_doc = $oXmlParser->parse(trim($buff));

                $args = $xml_doc->img->attrs;
                if(!$args) continue;

                // 캐싱하지 않을 경우 패스
                $widget = $args->widget;
                $sequence = $args->widget_sequence;
                $cache = $args->widget_cache;
                if(!$sequence || !$cache) continue;

                // 언어별로 위젯 캐시 파일이 있을 경우 재생성
                foreach($lang_list as $lang_type => $val) {
                    $cache_file = sprintf('%s%d.%s.cache', $cache_path, $sequence, $lang_type);
                    if(!file_exists($cache_file)) continue;

                    $oWidget->getCache($widget, $args, $lang_type, true);
                }
            }

        }

    }
?>
