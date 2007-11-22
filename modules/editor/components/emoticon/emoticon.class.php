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
        var $emoticon_path = '';

        /**
         * @brief editor_sequence과 컴포넌트의 경로를 받음
         **/
        function emoticon($editor_sequence, $component_path) {
            $this->editor_sequence = $editor_sequence;
            $this->component_path = $component_path;
            $this->emoticon_path = sprintf('%s%s/images',eregi_replace('^\.\/','',$this->component_path),'tpl','images');
        }

        /**
         * @brief 이모티콘 파일 목록을 리턴
         **/
        function getEmoticonList() {
            $emoticon = Context::get('emoticon');
            if(!$emoticon || !eregi("^([a-z0-9\_]+)$",$emoticon)) return new Object(-1,'msg_invalid_request');

            $list = $this->getEmoticons($emoticon);

            $this->add('emoticons', implode("\n",$list));
        }

		/**
		* @brief 재귀적으로 이모티콘이 될 법한 파일들을 하위 디렉토리까지 전부 검색한다. 8,000개까지는 테스트 해봤는데 스택오버프로우를 일으킬지 어떨지는 잘 모르겠음.(2007.9.6, 베니)
		**/
		function getEmoticons($path) {
            $emoticon_path = sprintf("%s/%s", $this->emoticon_path, $path);
            $output = array();

            $oDir = dir($emoticon_path);
            while($file = $oDir->read()) {
                if(substr($file,0,1)=='.') continue;
                if(eregi('\.(jpg|jpeg|gif|png)$',$file)) $output[] = sprintf("%s/%s", $path, str_replace($this->emoticon_path,'',$file));
            }
            $oDir->close();
            return $output;
		}
		
        /**
         * @brief popup window요청시 popup window에 출력할 내용을 추가하면 된다
         **/
        function getPopupContent() {
            // 이모티콘 디렉토리 목록을 가져옴
            $emoticon_list = FileHandler::readDir($this->emoticon_path);
            Context::set('emoticon_list', $emoticon_list);

            // 첫번째 이모티콘 디렉토리의 이미지 파일을 구함
            $emoticons = $this->getEmoticons($emoticon_list[0]);
            Context::set('emoticons', $emoticons);

            // 템플릿을 미리 컴파일해서 컴파일된 소스를 return
            $tpl_path = $this->component_path.'tpl';
            $tpl_file = 'popup.html';

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
