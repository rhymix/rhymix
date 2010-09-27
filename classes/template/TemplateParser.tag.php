<?php
    /**
     * @class TemplateHandler
     * @author NHN (developers@xpressengine.com)
     * @brief tag syntax template compiler 
     * @version 0.1
     * @remarks It compiles template file by using regular expression into php 
     *          code, and XE caches compiled code for further uses 
     **/

	class TemplateParserTag{

		var $oTemplate = null;

		function TemplateParserTag(&$oTemplate) {

			$this->oTemplate = $oTemplate;
		}

        /**
         * @brief compile a template file with comment syntax
		 **/
		function parse($buff) {

			// loop 템플릿 문법을 변환
			$buff = $this->_replaceLoop($buff);

			// |cond 템플릿 문법을 변환
			$buff = preg_replace_callback('/<([a-z]+)([^>\|]*)\|cond=\"([^\"]+)\"([^>]*)>/is', array($this, '_replacePipeCond'), $buff);

			// cond 템플릿 문법을 변환
			$buff = $this->_replaceCond($buff);

			// include 태그의 변환
			$buff = preg_replace_callback('!<include ([^>]+)>!is', array($this, '_replaceInclude'), $buff);


			// unload/ load 태그의 변환
			$buff = preg_replace_callback('!<(unload|load) ([^>]+)>!is', array($this, '_replaceLoad'), $buff);

			// 가상 태그인 block의 변환
			$buff = preg_replace('/<block([ ]*)>|<\/block>/is','',$buff);

			// 컴파일 결과물 return
			return $buff;
		}

		/**
		 * @brief 경로 변경
		 **/

		/**
		 * @brief loop 문법의 변환
		 **/
		function _replaceLoop($buff)
		{
			while(false !== $pos = strpos($buff, ' loop="'))
			{
				$pre = substr($buff,0,$pos);
				$next = substr($buff,$pos);

				$pre_pos = strrpos($pre, '<');

				preg_match('/^ loop="([^"]+)"/i',$next,$m);
				$tag = substr($next,0,strlen($m[0]));
				$next = substr($next,strlen($m[0]));
				$next_pos = strpos($next, '<');

				$tag = substr($pre, $pre_pos). $tag. substr($next, 0, $next_pos);
				$pre = substr($pre, 0, $pre_pos);
				$next  = substr($next, $next_pos);

				$tag_name = trim(substr($tag,1,strpos($tag,' ')));
				$tag_head = $tag_tail = '';

				preg_match_all('/ loop="([^"]+)"/is',$tag,$m);
				$tag = preg_replace('/ loop="([^"]+)"/is','', $tag);

				for($i=0,$c=count($m[0]);$i<$c;$i++)
				{
					$loop = $m[1][$i];
					if(false!== $fpos = strpos($loop,'=>'))
					{
						$target = trim(substr($loop,0,$fpos));
						$vars = trim(substr($loop,$fpos+2));
						if(false===strpos($vars,','))
						{
							$tag_head .= '<?php if(count('.$target.')) { foreach('.$target.' as '.$vars.') { ?>';
							$tag_tail .= '<?php } } ?>';
						}
						else
						{
							$t = explode(',',$vars);
							$tag_head .= '<?php if(count('.$target.')) { foreach('.$target.' as '.trim($t[0]).' => '.trim($t[1]).') { ?>';
							$tag_tail .= '<?php } } ?>';
						}
					}
					elseif(false!==strpos($loop,';'))
					{
						$tag_head .= '<?php for('.$loop.'){ ?>';
						$tag_tail .= '<?php } ?>';
					}
					else
					{
						$t = explode('=',$loop);
						if(count($t)==2)
						{
							$tag_head .= '<?php while('.trim($t[0]).' = '.trim($t[1]).') { ?>';
							$tag_tail .= '<?php } ?>';
						}
					}
				}

				if(substr(trim($tag),-2)!='/>') 
				{
					while(false !== $close_pos = strpos($next, '</'.$tag_name))
					{
						$tmp_buff = substr($next, 0, $close_pos+strlen('</'.$tag_name.'>'));
						$tag .= $tmp_buff;
						$next = substr($next, strlen($tmp_buff));
						if(false === strpos($tmp_buff, '<'.$tag_name)) break;
					}
				}

				for($i=0,$c=count($loop_var);$i<$c;$i++) 
				{
					$k = $loop_var[$i];
					$tag_head = str_replace($k, 'context->'.$k,$tag_head);
				}

				preg_match_all('/(.?)(\$[a-z0-9\_]+)/i',$loop,$lm);
				for($i=0,$c=count($lm[1]);$i<$c;$i++)
				{
					$h = trim($lm[1][$i]);
					$k = trim($lm[2][$i]);
					if($h==':'||$h=='>') continue;
					if($target && $k == $target) continue;
					$tag_head = str_replace($k, '$_'.substr($k,1), $tag_head);
					$tag = str_replace($k, '$_'.substr($k,1), $tag);
				}

				$buff = $pre.$tag_head.$tag.$tag_tail.$next;
			}
			return $buff;
		}

		/**
		 * @brief pipe cond, |cond= 의 변환
		 **/
		function _replacePipeCond($matches)
		{
			$tag = $matches[1];

			if(preg_match_all('/ ([^=]+)=\"([^"]+)\"/is', $matches[2], $m))
			{
				$t = array_pop($m[0]);
				return '<'.$matches[1].implode($m[0],' ').' <?php if('.$matches[3].'){?>'.$t.'<?php }?> '.$matches[4].'>';
			} 

			return $matches[0];
		}

		/**
		 * @brief cond 문법의 변환
		 **/
		function _replaceCond($buff)
		{
			while(false !== $pos = strpos($buff, ' cond="'))
			{
				$pre = substr($buff,0,$pos);
				$next = substr($buff,$pos);

				$pre_pos = strrpos($pre, '<');
				$next_pos = strpos($next, '<');

				$tag = substr($pre, $pre_pos). substr($next, 0, $next_pos);
				$pre = substr($pre, 0, $pre_pos);
				$next  = substr($next, $next_pos);
				$tag_name = trim(substr($tag,1,strpos($tag,' ')));
				$tag_head = $tag_tail = '';

				if(preg_match_all('/ cond=\"([^\"]+)"/is',$tag,$m))
				{
					for($i=0,$c=count($m[0]);$i<$c;$i++)
					{
						$tag_head .= '<?php if('.$m[1][$i].') { ?>';
						$tag_tail .= '<?php } ?>';
					}
				} 

				$tag = preg_replace('/ cond="([^"]+)"/is','', $tag);
				if(substr(trim($tag),-2)=='/>') 
				{
					$buff = $pre.$tag_head.$tag.$tag_tail.$next;
				} 
				else 
				{
					while(false !== $close_pos = strpos($next, '</'.$tag_name))
					{
						$tmp_buff = substr($next, 0, $close_pos+strlen('</'.$tag_name.'>'));
						$tag .= $tmp_buff;
						$next = substr($next, strlen($tmp_buff));
						if(false === strpos($tmp_buff, '<'.$tag_name)) break;
					}
					$buff = $pre.$tag_head.$tag.$tag_tail.$next;
				}
			}
			return $buff;
		}

		/**
		 * @brief 다른 template파일을 include하는 include tag의 변환
		 **/
		function _replaceInclude($matches) 
		{
			if(!preg_match('/target=\"([^\"]+)\"/is',$matches[0], $m)) throw new Exception('"target" attribute missing in "'.htmlspecialchars($matches[0]).'"');

			$target = $m[1];
			if(substr($target,0,1)=='/')
			{
				$target = substr($target,1);
				$pos = strrpos('/',$target);
				$filename = substr($target,$pos+1);
				$path = substr($target,0,$pos);
			} else {
				if(substr($target,0,2)=='./') $target = substr($target,2);
				$pos = strrpos('/',$target);
				$filename = substr($target,$pos);
				$path = substr($target,0,$pos);
			}

			return sprintf(
                '<?php%s'.
                '$oTemplate = &TemplateHandler::getInstance();%s'.
                'print $oTemplate->compile(\'%s\',\'%s\');%s'.
                '?>%s',
                "\n",
                "\n",
                $path, $filename, "\n",
                "\n"
            );
		}

		/**
		 * @brief load 태그의 변환
		 **/
		function _replaceLoad($matches) {
			$output = $matches[0];
			if(!preg_match_all('/ ([^=]+)=\"([^\"]+)\"/is',$matches[0], $m)) return $matches[0];

			$type = $matches[1];
			for($i=0,$c=count($m[1]);$i<$c;$i++)
			{
				if(!trim($m[1][$i])) continue;
				$attrs[trim($m[1][$i])] = trim($m[2][$i]);
			}

			if(!$attrs['target']) return $matches[0];

			$web_path = $this->oTemplate->web_path;
			$base_path = $this->oTemplate->path;

			$target = $attrs['target'];
			if(substr($target,0,2)=='./') $target = substr($target,2);
			if(!substr($target,0,1)!='/') $target = $web_path.$target;


            // if target ends with lang, load language pack
            if(substr($target, -4)=='lang') {
                if(substr($target,0,2)=='./') $target = substr($target, 2);
                $lang_dir = $base_path.$target;
                if(is_dir($lang_dir)) $output = sprintf('<?php Context::loadLang("%s"); ?>', $lang_dir);

			// otherwise try to load xml, css, js file
			} else {
				if(substr($target,0,1)!='/') $source_filename = $base_path.$target;
				else $source_filename = $target;

				// get filename and path
				$tmp_arr = explode("/",$source_filename);
				$filename = array_pop($tmp_arr);

				$base_path = implode("/",$tmp_arr)."/";

				// get the ext
				$tmp_arr = explode(".",$filename);
				$ext = strtolower(array_pop($tmp_arr));

				// according to ext., import the file
				switch($ext) {
					// xml js filter
					case 'xml' :
							// create an instance of XmlJSFilter class, then create js and handle Context::addJsFile
							$output = sprintf(
								'<?php%s'.
								'require_once("./classes/xml/XmlJsFilter.class.php");%s'.
								'$oXmlFilter = new XmlJSFilter("%s","%s");%s'.
								'$oXmlFilter->compile();%s'.
								'?>%s',
								"\n",
								"\n",
								$base_path,
								$filename,
								"\n",
								"\n",
								"\n"
								);
						break;
					// css file
					case 'css' :
							if(!preg_match('/^(http|\/)/i',$source_filename)) $source_filename = $base_path.$filename;
							if($type == 'unload') $output = '<?php Context::unloadCSSFile("'.$source_filename.'"); ?>';
							else $output = '<?php Context::addCSSFile("'.$source_filename.'"); ?>';
						break;
					// js file
					case 'js' :
							if(!preg_match('/^(http|\/)/i',$source_filename)) $source_filename = $base_path.$filename;
							if($type == 'unload') $output = '<?php Context::unloadJsFile("'.$source_filename.'"); ?>';
							else $output = '<?php Context::addJsFile("'.$source_filename.'"); ?>';
						break;
				}
			}
			return $output;
		}
	}
?>
