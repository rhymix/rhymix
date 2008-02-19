<?php
    /**
     * @class  cc_license
     * @author zero <zero@zeroboard.com>
     * @brief  CCL 출력 에디터 컴포넌트
     **/

    class cc_license extends EditorHandler {

        // editor_sequence 는 에디터에서 필수로 달고 다녀야 함
        var $editor_sequence = 0;
        var $component_path = '';

        /**
         * @brief editor_sequence과 컴포넌트의 경로를 받음
         **/
        function cc_license($editor_sequence, $component_path) {
            $this->editor_sequence = $editor_sequence;
            $this->component_path = $component_path;
        }

        /**
         * @brief popup window요청시 popup window에 출력할 내용을 추가하면 된다
         **/
        function getPopupContent() {
            // 템플릿을 미리 컴파일해서 컴파일된 소스를 return
            $tpl_path = $this->component_path.'tpl';
            $tpl_file = 'popup.html';

            Context::set("tpl_path", $tpl_path);

            $oTemplate = &TemplateHandler::getInstance();
            return $oTemplate->compile($tpl_path, $tpl_file);
        }

        /**
         * @brief 에디터 컴포넌트가 별도의 고유 코드를 이용한다면 그 코드를 html로 변경하여 주는 method
         *
         * 이미지나 멀티미디어, 설문등 고유 코드가 필요한 에디터 컴포넌트는 고유코드를 내용에 추가하고 나서
         * DocumentModule::transContent() 에서 해당 컴포넌트의 transHtml() method를 호출하여 고유코드를 html로 변경
         **/
        function transHTML($xml_obj) {
            // 지정된 옵션을 구함
            $ccl_title = $xml_obj->attrs->ccl_title;
            $ccl_use_mark = $xml_obj->attrs->ccl_use_mark;
            $ccl_allow_commercial = $xml_obj->attrs->ccl_allow_commercial;
            $ccl_allow_modification = $xml_obj->attrs->ccl_allow_modification;

            // 가로/ 세로 크기를 구함
            preg_match_all('/(width|height)([^[:digit:]]+)([^;^"^\']*)/i',$xml_obj->attrs->style,$matches);
            $width = trim($matches[3][0]);
            if(!$width) $width = "90%";
            $height = trim($matches[3][1]);
            if(!$height) $height = "50";

            // 언어파일을 읽음
            Context::loadLang($this->component_path.'/lang');
            $default_title = Context::getLang('ccl_default_title');
            if(!$ccl_title) $ccl_title = $default_title;

            $default_message = Context::getLang('ccl_default_message');

            $option = Context::getLang('ccl_options');

            // 영리 이용 체크
            if($ccl_allow_commercial == 'N') $opt1 = '-nc';
            else $opt1 = '';

            // 수정 표시 체크
            if($ccl_allow_modification == 'N') $opt2 = '-nd';
            elseif($ccl_allow_modification == 'SA') $opt2 = '-sa';
            else $opt2 = '';

            // 버전
            $version = '/3.0';

            // 언어에 따른 설정
            $lang_type = Context::getLangType();
            if($lang_type != 'en') $lang_file = 'deed.'.strtolower($lang_file);

            // 마크 이용시
            $ccl_image = '';
            if($ccl_use_mark == "Y") {
                $ccl_image = sprintf('
                        <a rel="license" href="http://creativecommons.org/licenses/by%s%s%s" onclick="window.open(this.href); return false;"><img src="http://i.creativecommons.org/l/by%s%s%s/88x31.png" alt="Creative Commons License" style="margin-bottom:5px;border:0;" /></a><br />',
                        $opt1, $opt2, $version,
                        $opt1, $opt2, $version
                );
            }

            // 결과물 생성
            $text = $ccl_image . sprintf($default_message, $opt1, $opt2, $version, '', $ccl_title, $option['ccl_allow_commercial'][$ccl_allow_commercial], $option['ccl_allow_modification'][$ccl_allow_modification], $version);

            $style = sprintf('<style type="text/css">.cc_license { clear:both; margin:20px auto 20px auto; padding:8px; width:%s;border:1px solid #c0c0c0; color:#808080; text-align:center; } .cc_license legend { font-weight:bold; } .cc_license a { color:#404040; text-decoration:none; } .cc_license a:hover { text-decoration:underline; </style>', $width);

            $output = sprintf('%s<fieldset class="cc_license"><legend>%s</legend>%s</fieldset>', $style, $ccl_title, $text);

            return $output;
        }
    }

?>
