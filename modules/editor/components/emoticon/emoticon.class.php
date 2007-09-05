<?php
    /**
     * @class  emoticon
     * @author zero (zero@nzeo.com)
     * @brief  이모티콘 이미지 연결 컴포넌트 
     **/

    class emoticon extends EditorHandler { 

        // upload_target_srl 는 에디터에서 필수로 달고 다녀야 함....
        var $upload_target_srl = 0;
        var $component_path = '';

        /**
         * @brief upload_target_srl과 컴포넌트의 경로를 받음
         **/
        function emoticon($upload_target_srl, $component_path) {
            $this->upload_target_srl = $upload_target_srl;
            $this->component_path = $component_path;
        }

		/**
		* @brief 재귀적으로 이모티콘이 될 법한 파일들을 하위 디렉토리까지 전부 검색한다. 8,000개까지는 테스트 해봤는데 스택오버프로우를 일으킬지 어떨지는 잘 모르겠음.(2007.9.6, 베니)
		**/
		function getEmoticons($search_path, $remove_str = '') {
			if(substr($search_path,-1)!='/') $search_path .= '/';
			
			if($handle = opendir($search_path)) {
				if($remove_str == '') $remove_str = $search_path;
				$path = $search_path;
				while(false !== ($file = readdir($handle))){
					if($file == "." || $file == "..") continue;
					if(is_dir($path.$file)){
						$dirs[] = $path.$file."/";
					} else if(is_file($path.$file) && preg_match("/.(jpg|jpeg|gif|png|bmp)\b/i", $file)) {
						// 심볼릭 링크가 무한루프에 빠지게 만들지 않을까해서 file인지 체크
						// 이미지 파일이 아닌 파일이 포함되어 문제를 일으킬 것을 대비하여 regex로 파일 확장자 체크
						$files[] = substr($path.$file, strlen($remove_str));
					}
				}
				closedir($handle);

				foreach($dirs as $dir){
					$files_from_sub = $this->getEmoticons($dir, $remove_str);
					$files = array_merge($files, $files_from_sub);
				}
				return $files;
			}
			return array();
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

			$emoticon_list = $this->getEmoticons($tpl_path.'/images');
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
