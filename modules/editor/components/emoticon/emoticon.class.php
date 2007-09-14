<?php
    /**
     * @class  emoticon
     * @author zero (zero@nzeo.com)
     * @brief  이모티콘 이미지 연결 컴포넌트 
     **/

    class emoticon extends EditorHandler { 

        // editor_sequence 는 에디터에서 필수로 달고 다녀야 함....
        var $editor_sequence = 0;
        var $component_path = '';

        /**
         * @brief editor_sequence과 컴포넌트의 경로를 받음
         **/
        function emoticon($editor_sequence, $component_path) {
            $this->editor_sequence = $editor_sequence;
            $this->component_path = $component_path;
        }

		/**
		* @brief 재귀적으로 이모티콘이 될 법한 파일들을 하위 디렉토리까지 전부 검색한다. 8,000개까지는 테스트 해봤는데 스택오버프로우를 일으킬지 어떨지는 잘 모르겠음.(2007.9.6, 베니)
		**/
		function getEmoticons($path, $source_path) {
            $path = ereg_replace('\/$','',$path);
            $output = array();

            $oDir = dir($path);
            while($file = $oDir->read()) {
                if(in_array($file, array('.','..'))) continue;

                $new_path = $path.'/'.$file;

                if(is_dir($new_path)) {
                    $sub_output = $this->getEmoticons($new_path, $source_path);
                    if(is_array($sub_output) && count($sub_output)) $output = array_merge($output, $sub_output);
                }

                if(eregi('(jpg|jpeg|gif|png)$',$new_path)) $output[] = str_replace($source_path.'/', '', $new_path);
            }

            $oDir->close();

            return $output;
		}
		
        /**
         * @brief popup window요청시 popup window에 출력할 내용을 추가하면 된다
         **/
        function getPopupContent() {
            // 템플릿을 미리 컴파일해서 컴파일된 소스를 return
            $tpl_path = $this->component_path.'tpl';
            $tpl_file = 'popup.html';

            Context::set("tpl_path", $tpl_path);

            // 이모티콘을 모두 가져옴

            $emoticon_path = sprintf('%s%s/images',eregi_replace('^\.\/','',$this->component_path),'tpl','images');
			$emoticon_list = $this->getEmoticons($emoticon_path, $emoticon_path);
            Context::set('emoticon_list', $emoticon_list);

            $oTemplate = &TemplateHandler::getInstance();
            return $oTemplate->compile($tpl_path, $tpl_file);
        }

		/**
		* @brief 이모티콘의 경로 문제 해결을 하기 위해 추가하였다. (2007.9.6 베니)
		**/
		function transHTML($xml_obj) {
            $src = $xml_obj->attrs->src;
            $alt = $xml_obj->attrs->alt;

            if(!$alt) {
                $tmp_arr = explode('/',$src);
                $alt = array_pop($tmp_arr);
            }

            $src = str_replace(array('&','"'), array('&amp;','&qout;'), $src);
            if(!$alt) $alt = $src;

            $attr_output = array();
            $attr_output = array("src=\"".$src."\"");

            if($alt) {
                $attr_output[] = "alt=\"".$alt."\"";
            }
            if(eregi("\.png$",$src)) $attr_output[] = "class=\"iePngFix\"";

            $code = sprintf("<img %s style=\"border:0px\" />", implode(" ",$attr_output));

            return $code;
        }
    }
?>
