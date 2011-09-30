<?php
    /**
     * @class TemplateHandler
     * @author NHN (developers@xpressengine.com)
     * @brief template compiler
     * @version 0.1
     * @remarks It compiles template file by using regular expression into php
     *          code, and XE caches compiled code for further uses
     **/

    class TemplateHandler extends Handler {

        var $compiled_path = './files/cache/template_compiled/'; ///< path of compiled caches files

        var $path = null; ///< target directory
        var $filename = null; ///< target filename
        var $file = null; ///< target file (fullpath)
		var $xe_path = null;  ///< XpressEngine base path
		var $web_path = null; ///< tpl file web path
		var $compiled_file = null; ///< tpl file web path
		var $buff = null; ///< tpl file web path

		var $handler_mtime = 0;

        /**
         * @brief returns TemplateHandler's singleton object
         * @return TemplateHandler instance
         **/
        function &getInstance() {
            if(__DEBUG__==3 ) {
                if(!isset($GLOBALS['__TemplateHandlerCalled__'])) $GLOBALS['__TemplateHandlerCalled__']=1;
                else $GLOBALS['__TemplateHandlerCalled__']++;
            }

            if(!$GLOBALS['__TemplateHandler__']) {
                $GLOBALS['__TemplateHandler__'] = new TemplateHandler();
            }
            return $GLOBALS['__TemplateHandler__'];
        }

		/**
		 * @brief set variables for template compile
		 **/
		function init($tpl_path, $tpl_filename, $tpl_file) {
            // verify arguments
            if(substr($tpl_path,-1)!='/') $tpl_path .= '/';
			if(!file_exists($tpl_path.$tpl_filename)&&file_exists($tpl_path.$tpl_filename.'.html')) $tpl_filename .= '.html';

            // create tpl_file variable
            if(!$tpl_file) $tpl_file = $tpl_path.$tpl_filename;

			// set template file infos.
			$info = pathinfo($tpl_file);
			//$this->path = preg_replace('/^\.\//','',$info['dirname']).'/';
			$this->path = $tpl_path;
			$this->filename = $tpl_filename;
			$this->file = $tpl_file;

			$this->xe_path = preg_replace('/([^\.^\/]+)\.php$/i','',$_SERVER['SCRIPT_NAME']);
			$this->web_path = $this->xe_path.str_replace(_XE_PATH_,'',$this->path);

			// get compiled file name
			$this->compiled_file = sprintf('%s%s.compiled.php',$this->compiled_path, md5($this->file . __ZBXE_VERSION__));

			// compare various file's modified time for check changed
			$this->handler_mtime = filemtime(_XE_PATH_.'classes/template/TemplateHandler.class.php');

			$this->buff = null;
		}

        /**
         * @brief compiles specified tpl file and execution result in Context into resultant content
         * @param[in] $tpl_path path of the directory containing target template file
         * @param[in] $tpl_filename target template file's name
         * @param[in] $tpl_file if specified use it as template file's full path
         * @return Returns compiled result in case of success, NULL otherwise
         */
        function compile($tpl_path, $tpl_filename, $tpl_file = '') {
            // store the starting time for debug information
            if(__DEBUG__==3 ) $start = getMicroTime();

			// initiation
			$this->init($tpl_path, $tpl_filename, $tpl_file);

            // if target file does not exist exit
            if(!$this->file || !file_exists($this->file)) return sprintf('Err : "%s" template file does not exists.', $this->file);

            $source_template_mtime = filemtime($this->file);
			$latest_mtime = $source_template_mtime>$this->handler_mtime?$source_template_mtime:$this->handler_mtime;

			// cache controll
			$oCacheHandler = &CacheHandler::getInstance('template');

			// get cached buff
			if($oCacheHandler->isSupport()){
				$cache_key = 'template:'.$this->file;
				$this->buff = $oCacheHandler->get($cache_key, $latest_mtime);
			} else {
				if(file_exists($this->compiled_file) && filemtime($this->compiled_file)>$latest_mtime) {
					$this->buff = FileHandler::readFile($this->compiled_file);
				}
			}

			if(!$this->buff) {
				$this->parse();
				if($oCacheHandler->isSupport()) $oCacheHandler->put($cache_key, $this->buff);
				else FileHandler::writeFile($this->compiled_file, $this->buff);
			}

			$output = $this->_fetch();

			// store the ending time for debug information
            if(__DEBUG__==3 ) $GLOBALS['__template_elapsed__'] += getMicroTime() - $start;

            return $output;
        }

        /**
         * @brief compile specified file and immediately return
         * @param[in] $tpl_path path of the directory containing target template file
         * @param[in] $tpl_filename target template file's name
         * @return Returns compiled content in case of success or NULL in case of failure
         **/
        function compileDirect($tpl_path, $tpl_filename) {
			$this->init($tpl_path, $tpl_filename, null);

            // if target file does not exist exit
            if(!$this->file || !file_exists($this->file)) {
				Context::close();
				printf('"%s" template file is not exists.', $this->file);
				exit();
			}

            $this->parse();
			return $this->buff;
        }

        /**
         * @brief compile a template file specified in $tpl_file and
         * @pre files specified by $tpl_file exists.
         * @param[in] $tpl_file path of tpl file
         * @param[in] $compiled_tpl_file if specified, write compiled result into the file
         * @return compiled result in case of success or NULL in case of error
         **/
        function parse() {
			if(!file_exists($this->file)) return;

            // read tpl file
            $buff = FileHandler::readFile($this->file);

			// replace value of src in img/input/script tag
			$buff = preg_replace_callback('/<(img|input|script)([^>]*)src="([^"]*?)"/is', array($this, '_replacePath'), $buff);

			// replace the loop template syntax
			$buff = $this->_replaceLoop($buff);

			// |replace the cond template syntax
			$buff = $this->_replaceCond($buff);

			// replace the cond template syntax
			$buff = preg_replace_callback("/<\/?(\w+)((\s+\w+(\s*=\s*(?:\".*?\"|'.*?'|[^'\">\s]+))?)+\s*|\s*)\/?>/i", array($this, '_replacePipeCond'), $buff);

			// replace the include tags
			$buff = preg_replace_callback('!<include ([^>]+)>!is', array($this, '_replaceInclude'), $buff);

			// replace unload/load tags
			$buff = preg_replace_callback('!<(unload|load) ([^>]+)>!is', array($this, '_replaceLoad'), $buff);

			// replace block which is a virtual tag
			$buff = preg_replace('/<block([ ]*)>|<\/block>/is','',$buff);

            // replace include <!--#include($filename)-->
            $buff = preg_replace_callback('!<\!--#include\(([^\)]*?)\)-->!is', array($this, '_compileIncludeToCode'), $buff);

            // replace <!--@, -->
            $buff = preg_replace_callback('!<\!--@(.*?)-->!is', array($this, '_compileFuncToCode'), $buff);

            // remove comments <!--// ~ -->
            $buff = preg_replace('!(\n?)( *?)<\!--//(.*?)-->!is', '', $buff);

            // import xml filter/ css/ js/ files <!--%import("filename"[,optimized=true|false][,media="media"][,targetie="lt IE 6|IE 7|gte IE 8|..."])--> (media is applied to only css)
            $buff = preg_replace_callback('!<\!--%import\(\"([^\"]*?)\"(,optimized\=(true|false))?(,media\=\"([^\"]*)\")?(,targetie=\"([^\"]*)\")?(,index=\"([^\"]*)\")?(,type=\"([^\"]*)\")?\)-->!is', array($this, '_compileImportCode'), $buff);

            // unload css/ js <!--%unload("filename"[,optimized=true|false][,media="media"][,targetie="lt IE 6|IE 7|gte IE 8|..."])--> (media is applied to only css)
            $buff = preg_replace_callback('!<\!--%unload\(\"([^\"]*?)\"(,optimized\=(true|false))?(,media\=\"([^\"]*)\")?(,targetie=\"([^\"]*)\")?\)-->!is', array($this, '_compileUnloadCode'), $buff);

            // javascript plugin import
            $buff = preg_replace_callback('!<\!--%load_js_plugin\(\"([^\"]*?)\"\)-->!is', array($this, '_compileLoadJavascriptPlugin'), $buff);

			// form auto generation
			$buff = preg_replace_callback('/(<form(?:<\?php.+?\?>|[^<>]+)*?>)(.*?)(<\/form>)/is', array($this, '_compileFormAuthGeneration'), $buff);

            // replace variables
            $buff = preg_replace_callback('/\{[^@^ ]([^\{\}\n]+)\}/i', array($this, '_compileVarToContext'), $buff);

			// replace PHP variable types(converts characters like $ into shared context)
			$buff = $this->_replaceVarInPHP($buff);

            // replace parts not displaying results
            $buff = preg_replace_callback('/\{\@([^\{\}]+)\}/i', array($this, '_compileVarToSilenceExecute'), $buff);

            // prevent from calling directly before writing into file
            $this->buff = '<?php if(!defined("__ZBXE__")) exit();?>'.$buff;
        }

		/**
		 * @brief 1. remove ruleset from form tag
		 * 2. add hidden tag with ruleset value
		 * 3. if empty default hidden tag, generate hidden tag (ex:mid, vid, act...)
		 * 4. generate return url, return url use in server side validator
		 **/
		function _compileFormAuthGeneration($matches)
		{
			// form ruleset attribute move to hidden tag
			if($matches[1])
			{
				preg_match('/ruleset="([^"]*?)"/is', $matches[1], $m);
				if($m[0])
				{
					$matches[1] = preg_replace('/'.$m[0].'/i', '', $matches[1]);
					$matches[2] = '<input type="hidden" name="ruleset" value="'.$m[1].'" />'.$matches[2];

					if (strpos($m[1],'@') !== false){
						$path = str_replace('@', '', $m[1]);
						$validator   = new Validator("./files/ruleset/{$path}.xml");
						$validator->setCacheDir('files/cache');
						$matches[1]  = '<?php Context::addJsFile("'.$validator->getJsPath().'") ?'.'>'.$matches[1];
					}else if(preg_match('@(?:^|\.?/)(modules/[\w-]+)@', $this->path, $mm)) {
						$module_path = $mm[1];
						$validator   = new Validator("{$module_path}/ruleset/{$m[1]}.xml");
						$validator->setCacheDir('files/cache');
						$matches[1]  = '<?php Context::addJsFile("'.$validator->getJsPath().'") ?'.'>'.$matches[1];
					}
				}
			}

			// if not exists default hidden tag, generate hidden tag
			preg_match_all('/<input[^>]* name="(act|mid|vid)"/is', $matches[2], $m2);
			$checkVar = array('act', 'mid', 'vid');
			$resultArray = array_diff($checkVar, $m2[1]);
			if(is_array($resultArray))
			{
				$generatedHidden = '';
				foreach($resultArray AS $key=>$value)
				{
					$generatedHidden .= '<input type="hidden" name="'.$value.'" value="{$'.$value.'}">';
				}
				$matches[2] = $generatedHidden.$matches[2];
			}

			// return url generate
			preg_match('/<input[^>]*name="error_return_url"[^>]*>/is', $matches[2], $m3);
			if(!$m3[0]) $matches[2] = '<input type="hidden" name="error_return_url" value="{getRequestUriByServerEnviroment()}" />'.$matches[2];

			$matches[0] = '';
			return implode($matches);
		}

        /**
         * @brief fetch using ob_* function
         * @param[in] $compiled_tpl_file path of compiled template file
         * @param[in] $buff if buff is not null, eval it instead of including compiled template file
         * @param[in] $tpl_path set context's tpl path
         * @return result string
         **/
        function _fetch() {
			if(!$this->buff) return;

            $__Context = &$GLOBALS['__Context__'];
            $__Context->tpl_path = $this->path;

            if($_SESSION['is_logged']) $__Context->logged_info = Context::get('logged_info');

            ob_start();
			$eval_str = "?>".$this->buff;
			eval($eval_str);
            return ob_get_clean();
        }

        /**
         * @brief change image path
         * @pre $matches is an array containg three elements
         * @param[in] $matches match
         * @return changed result
         **/
		function _replacePath($matches)
		{
			preg_match_all('/src="([^"]*?)"/is', $matches[0], $m);
			for($i=0,$c=count($m[0]);$i<$c;$i++) {
				$path = trim($m[1][$i]);
				if(substr($path,0,1)=='/' || substr($path,0,1)=='{' || strpos($path,'://')!==false) continue;
				if(substr($path,0,2)=='./') $path = substr($path,2);
				$target = $this->web_path.$path;
				while(strpos($target,'/../')!==false)
				{
					$target = preg_replace('/\/([^\/]+)\/\.\.\//','/',$target);
				}
				$target = str_replace('/./','/',$target);
				$matches[0] = str_replace($m[0][$i], 'src="'.$target.'"', $matches[0]);
			}
			return $matches[0];
		}

		/**
		 * @brief replace loop syntax
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

				if(!preg_match('/ loop="([^"]+)"/is',$tag)) {
					print "<strong>Invalid XpressEngine Template Syntax</strong><br/>";
					print "File : ".$this->file."<br/>";
					print "Code : ".htmlspecialchars($tag);
					exit();
				}

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
						if(substr_count($tag, '<'.$tag_name) == substr_count($tag,'</'.$tag_name)) break;
					}
				}

				$buff = $pre.$tag_head.$tag.$tag_tail.$next;
			}
			return $buff;
		}

		/**
		 * @brief replace pipe cond and |cond=
		 **/
		function _replacePipeCond($matches)
		{
			if(strpos($matches[0],'|cond')!==false) {
				while(strpos($matches[0],'|cond="')!==false) {
					if(preg_match('/ (\w+)=\"([^\"]+)\"\|cond=\"([^\"]+)\"/is', $matches[0], $m))
						$matches[0] = str_replace($m[0], sprintf('<?php if(%s) {?> %s="%s"<?php }?>', $m[3], $m[1], $m[2]), $matches[0]);
				}
			}

			return $matches[0];
		}

		/**
		 * @brief replace cond syntax
		 **/
		function _replaceCond($buff)
		{
			while(false !== ($pos = strpos($buff, ' cond="')))
			{
				$pre = substr($buff,0,$pos);
				$next = substr($buff,$pos);

				$pre_pos = strrpos($pre, '<');

				$isClosedTagUse = true;
				preg_match('/<(\/|[!DOCTYPE]|[a-z])/i',$next,$m);
				// if not use closed tag, find simple closed tag
				if(!$m[0]) {
					$isClosedTagUse = false;
					preg_match('/[^->]\/?>/',$next,$m);
				}
				if(!$m[0]) return $buff;
				if($isClosedTagUse) $next_pos = strpos($next, $m[0]);
				else $next_pos = strpos($next, $m[0])+2;

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

				if(!preg_match('/ cond="([^"]+)"/is',$tag)) {
					print "<strong>Invalid XpressEngine Template Syntax</strong><br/>";
					print "File : ".$this->file."<br/>";
					print "Code : ".htmlspecialchars($tag);
					exit();
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

						if(substr_count($tag, '<'.$tag_name) == substr_count($tag,'</'.$tag_name)) break;
					}
					$buff = $pre.$tag_head.$tag.$tag_tail.$next;
				}
			}

			return $buff;
		}

		/**
		 * @brief replace include tags which include other template files
		 **/
		function _replaceInclude($matches)
		{
			if(!preg_match('/target=\"([^\"]+)\"/is',$matches[0], $m)) {
				print '"target" attribute missing in "'.htmlspecialchars($matches[0]);
				exit();
			}

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
				$path = $this->path.substr($target,0,$pos);
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
		 * @brief replace load tags
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

			$web_path = $this->web_path;
			$base_path = $this->path;

			$target = $attrs['target'];
            if(!preg_match('/^(http|https)/i',$target))
            {
                if(substr($target,0,2)=='./') $target = substr($target,2);
                //if(substr($target,0,1)!='/') $target = $web_path.$target;
            }

			if(!$attrs['index']) $attrs['index'] = 'null';
			if($attrs['type']!='body') $attrs['type'] = 'head';

            // if target ends with lang, load language pack
            if(substr($target, -4)=='lang') {
                if(substr($target,0,2)=='./') $target = substr($target, 2);
                $lang_dir = $base_path.$target;
                if(is_dir($lang_dir)) $output = sprintf('<?php Context::loadLang("%s"); ?>', $lang_dir);

			// otherwise try to load xml, css, js file
			} else {
				if(substr($target,0,1)!='/') $source_filename = $base_path.$target;
				else $source_filename = $target;
				$source_filename = str_replace(array('/./','//'),'/',$source_filename);

				// get filename and path
				$tmp_arr = explode("/",$source_filename);
				$filename = array_pop($tmp_arr);

				//$base_path = implode("/",$tmp_arr)."/";

				// get the ext
				$tmp_arr = explode(".",$filename);
				$ext = strtolower(array_pop($tmp_arr));

				$output = '<?php '.
							'$_load_filename = \'' . preg_replace('/\{([^@^ ][^\{\}\n]+)\}/i', "'.\\1.'", $filename) . '\';'.
							'$_load_source_filename = \'' . preg_replace('/\{([^@^ ][^\{\}\n]+)\}/i', "'.\\1.'", $source_filename) . '\';';
				foreach($attrs as $key => $val)
				{
					$output .= '$_load_attrs[\''.$key.'\'] = \'' . preg_replace('/\{([^@^ ][^\{\}\n]+)\}/i', "'.\\1.'", $val) . '\';';
				}
				$output .= '?>';

				// according to ext., import the file
				switch($ext) {
					// xml js filter
					case 'xml' :
							if(preg_match('/^(http|https)/i',$source_filename)) return;
							// create an instance of XmlJSFilter class, then create js and handle Context::addJsFile
							$output .= sprintf(
								'<?php%s'.
								'require_once("./classes/xml/XmlJsFilter.class.php");%s'.
								'$oXmlFilter = new XmlJSFilter("%s","%s");%s'.
								'$oXmlFilter->compile();%s'.
								'?>%s',
								"\n",
								"\n",
								dirname($base_path . $attrs['target']).'/',
								$filename,
								"\n",
								"\n",
								"\n"
								);
						break;
					// css file
					case 'css' :
							if($type == 'unload') {
								$output = sprintf("<?php Context::unloadFile('%s', '%s', '%s'); ?>", $source_filename, $attrs['targetie'], $attrs['media']);
							} else {
								$meta_file = $source_filename;
								$output .= '<?php Context::loadFile(array($_load_source_filename, $_load_attrs[\'media\'], $_load_attrs[\'targetie\'], $_load_attrs[\'index\']), $_load_attrs[\'usecdn\'], $_load_attrs[\'cdnprefix\'], $_load_attrs[\'cdnversion\']);?>';
							}
						break;
					// js file
					case 'js' :
							if($type == 'unload') {
								$output = sprintf("<?php Context::unloadFile('%s', '%s'); ?>", $source_filename, $attrs['targetie']);
							} else {
								$meta_file = $source_filename;
								$output .= '<?php Context::loadFile(array($_load_source_filename, $_load_attrs[\'type\'], $_load_attrs[\'targetie\'], $_load_attrs[\'index\']), $_load_attrs[\'usecdn\'], $_load_attrs[\'cdnprefix\'], $_load_attrs[\'cdnversion\']);?>';
							}
						break;
				}
			}

			$output .= '<?php unset($_load_attrs); ?>';
			if($meta_file) $output = '<!--#Meta:'.$meta_file.'-->'.$output;
			return $output;
		}

		/**
		 * @brief replace PHP variables of $ character
		 **/
		function _replaceVarInPHP($buff) {
			$head = $tail = '';
			while(false !== $pos = strpos($buff, '<?php'))
			{
				$head .= substr($buff,0,$pos);
				$buff = substr($buff,$pos);
				$pos = strpos($buff,'?>');
				$body = substr($buff,0,$pos+2);
				$head .= preg_replace_callback('/(.?)\$(\w+[a-z0-9\_\-\[\]\'\"]+)/is',array($this, '_replaceVarString'), $body);

				$buff = substr($buff,$pos+2);
			}
			return $head.$buff;
		}


		/**
		 * @brief if class::$variable_name in php5, replace the function not to use context
		 **/
		function _replaceVarString($matches)
		{
			if($matches[1]==':') return $matches[0];
			if(substr($matches[2],0,1)=='_') return $matches[0];
			return $matches[1].'$__Context->'.$matches[2];
		}

        /**
         * @brief replace <!--#include $path--> with php code
         * @param[in] $matches match
         * @return replaced result
         **/
        function _compileIncludeToCode($matches) {
            // if target string to include contains variables handle them
            $arg = str_replace(array('"','\''), '', $matches[1]);
            if(!$arg) return;

            $tmp_arr = explode("/", $arg);
            for($i=0;$i<count($tmp_arr);$i++) {
                $item1 = trim($tmp_arr[$i]);
                if($item1=='.'||substr($item1,-5)=='.html') continue;

                $tmp2_arr = explode(".",$item1);
                for($j=0;$j<count($tmp2_arr);$j++) {
                    $item = trim($tmp2_arr[$j]);
                    if(substr($item,0,1)=='$') $item = Context::get(substr($item,1));
                    $tmp2_arr[$j] = $item;
                }
                $tmp_arr[$i] = implode(".",$tmp2_arr);
            }
            $arg = implode("/",$tmp_arr);
            if(substr($arg,0,2)=='./') $arg = substr($arg,2);

            // step1: check files in the template directory
            //$source_filename = sprintf("%s/%s", dirname($this->file), $arg);
			$path = substr($this->path,-1)=='/'?substr($this->path,0,-1):$this->path;
            $source_filename = sprintf("%s/%s", $path, $arg);

            // step2: check path from root
            if(!file_exists($source_filename)) $source_filename = './'.$arg;
            if(!file_exists($source_filename)) return;

            // split into path and filename
            $tmp_arr = explode('/', $source_filename);
            $filename = array_pop($tmp_arr);
            $path = implode('/', $tmp_arr).'/';

            // try to include
            $output = sprintf(
                '<?php%s'.
                '$oTemplate = &TemplateHandler::getInstance();%s'.
                'print $oTemplate->compile(\'%s\',\'%s\');%s'.
                '?>%s',
                "\n",
                "\n",
                $path, $filename, "\n",
                "\n"
            );

            return $output;
        }

        /**
         * @brief replace $... variables in { } with Context::get(...)
         * @param[in] $matches match
         * @return replaced result in case of success or NULL in case of error
         **/
        function _compileVarToContext($matches) {
            $str = trim(substr($matches[0],1,strlen($matches[0])-2));
            if(!$str) return $matches[0];
            if(!in_array(substr($str,0,1),array('(','$','\'','"'))) {
                if(preg_match('/^([^\( \.]+)(\(| \.)/i',$str,$m)) {
                    $func = trim($m[1]);
                    if(strpos($func,'::')===false) {
                        if(!function_exists($func)) {
                            return $matches[0];
                        }
                    } else {
                        list($class, $method) = explode('::',$func);
                        // FIXME regardless of whether class/func name is case-sensitive, it is safe
                        // to assume names are case sensitive. We don't have compare twice.
                        if(!class_exists($class)  || !in_array($method, get_class_methods($class))) {
                            // In some environment, the name of classes and methods may be case-sensitive
                            list($class, $method) = explode('::',strtolower($func));
                            if(!class_exists($class)  || !in_array($method, get_class_methods($class))) {
                                return $matches[0];
                            }
                        }
                    }
                } else {
                    if(!defined($str)) return $matches[0];
                }
            }
            return '<?php @print('.preg_replace('/\$([a-zA-Z0-9\_\-\>]+)/i','$__Context->\\1', $str).');?>';
        }

        /**
         * @brief replace @... function in { } into print func(..)
         * @param[in] $matches match
         * @return replaced result
         **/
        function _compileVarToSilenceExecute($matches) {
            if(strtolower(trim(str_replace(array(';',' '),'', $matches[1])))=='return') return '<?php return; ?>';
            return '<?php @'.preg_replace('/\$([a-zA-Z0-9\_\-\>]+)/i','$__Context->\\1', trim($matches[1])).';?>';
        }

        /**
         * @brief replace code in <!--@, --> with php code
         * @param[in] $matches match
         * @return changed result
         **/
        function _compileFuncToCode($matches) {
            static $idx = 0;
            $code = trim($matches[1]);
            if(!$code) return;

            switch(strtolower($code)) {
                case 'else' :
                        $output = '}else{';
                    break;
                case 'end' :
                case 'endif' :
                case 'endfor' :
                case 'endforeach' :
                case 'endswitch' :
                        $output = '}';
                    break;
                case 'break' :
                        $output = 'break;';
                    break;
                case 'default' :
                        $output = 'default :';
                    break;
                case 'break@default' :
                        $output = 'break; default :';
                    break;
                default :
                        $suffix = '{';

                        if(substr($code, 0, 4) == 'else') {
                            $code = '}'.$code;
                        } elseif(substr($code, 0, 7) == 'foreach') {
                            $tmp_str = substr($code, 8);
                            $tmp_arr = explode(' ', $tmp_str);
                            $var_name = $tmp_arr[0];
                            $prefix = '$Context->__idx['.$idx.']=0;';
                            if(substr($var_name, 0, 1) == '$') {
                                $prefix .= sprintf('if(count($__Context->%s)) ', substr($var_name, 1));
                            } else {
                                $prefix .= sprintf('if(count(%s)) ', $var_name);
                            }
                            $idx++;
                            $suffix .= '$__idx['.$idx.']=($__idx['.$idx.']+1)%2; $cycle_idx = $__idx['.$idx.']+1;';
                        } elseif(substr($code, 0, 4) == 'case') {
                            $suffix = ':';
                        } elseif(substr($code, 0, 10) == 'break@case') {
                            $code = 'break; case'.substr($code, 10);
                            $suffix = ':';
                        }
                        $output = preg_replace('/\$([a-zA-Z0-9\_\-]+)/i', '$__Context->\\1', $code.$suffix);
                    break;
            }

            return sprintf('<?php %s %s ?>', $prefix, $output);
        }


        /**
         * @brief replace xe specific code, "<!--%filename-->" with appropriate php code
         * @param[in] $matches match
         * @return Returns modified result or NULL in case of error
         **/
        function _compileImportCode($matches) {
            // find xml file
            $base_path = $this->path;
            $given_file = trim($matches[1]);
            if(!$given_file) return;
            if(isset($matches[3])) $optimized = strtolower(trim($matches[3]));
            if(!$optimized) $optimized = 'true';
            if(isset($matches[5])) $media = trim($matches[5]);
            if(!$media) $media = 'all';
            if(isset($matches[7])) $targetie = trim($matches[7]);
            if(!$targetie) $targetie = '';
            else $optimized = 'false';

            if(isset($matches[9])) $index = intval($matches[9]);
			if(!$index) $index = 'null';
            if(isset($matches[11])) $type = strtolower(trim($matches[11]));
			if($type!='body') $type = 'head';

            // if given_file ends with lang, load language pack
            if(substr($given_file, -4)=='lang') {
                if(substr($given_file,0,2)=='./') $given_file = substr($given_file, 2);
                $lang_dir = $base_path.$given_file;
                if(is_dir($lang_dir)) $output = sprintf('<?php Context::loadLang("%s"); ?>', $lang_dir);

            // otherwise try to load xml, css, js file
            } else {
				if(preg_match('/^(http|https):/i',$given_file)) $source_filename = $given_file;
                elseif(substr($given_file,0,1)!='/') $source_filename = sprintf("%s%s",$base_path, $given_file);
                else $source_filename = $given_file;

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
                            if(preg_match('/^(http|\/)/i',$source_filename)) {
                                $output = sprintf('<?php Context::loadFile(array("%s", "%s", "%s", "%s")); ?>', $source_filename, $media, $targetie, $index);
                            } else {
								$meta_file = $base_path.$filename;
                                $output = sprintf('<?php Context::loadFile(array("%s%s", "%s", "%s", "%s")); ?>', $base_path, $filename, $media, $targetie, $index);
                            }
                        break;
                    // js file
                    case 'js' :
                            if(preg_match('/^(http|\/)/i',$source_filename)) {
                                $output = sprintf('<?php Context::loadFile(array("%s", "%s", "%s","%s")); ?>', $source_filename, $type, $targetie, $index);
                            } else {
								$meta_file = $base_path.$filename;
                                $output = sprintf('<?php Context::loadFile(array("%s%s", "%s", "%s", "%s")); ?>', $base_path, $filename, $type, $targetie, $index);
                            }
                        break;
                }
            }

			if($meta_file) $output = '<!--#Meta:'.$meta_file.'-->'.$output;
            return $output;
        }

        /**
         * @brief import javascript plugin
         * @param[in] $matches match
         * @return result loading the plugin
         * @remarks javascript plugin works as optimized = false
         **/
        function _compileLoadJavascriptPlugin($matches) {
            $base_path = $this->path;
            $plugin = trim($matches[1]);
            return sprintf('<?php Context::loadJavascriptPlugin("%s"); ?>', $plugin);
        }

        /**
         * @brief remove loading part of css/ js file
         * @param[in] $matches match
         * @return removed result
         **/
        function _compileUnloadCode($matches) {
            // find xml file
            $base_path = $this->path;
            $given_file = trim($matches[1]);
            if(!$given_file) return;
            if(isset($matches[3])) $optimized = strtolower(trim($matches[3]));
            if(!$optimized) $optimized = 'true';
            if(isset($matches[5])) $media = trim($matches[5]);
            if(!$media) $media = 'all';
            if(isset($matches[7])) $targetie = trim($matches[7]);
            if(!$targetie) $targetie = '';
            else $optimized = 'false';

            if(substr($given_file,0,1)!='/') $source_filename = sprintf("%s%s",$base_path, $given_file);
            else $source_filename = $given_file;

            // get path and file nam
            $tmp_arr = explode("/",$source_filename);
            $filename = array_pop($tmp_arr);

            $base_path = implode("/",$tmp_arr)."/";

            // get an ext.
            $tmp_arr = explode(".",$filename);
            $ext = strtolower(array_pop($tmp_arr));

            switch($ext) {
                // css file
                case 'css' :
                        if(preg_match('/^(http|https|\/)/i',$source_filename)) {
                            $output = sprintf('<?php Context::unloadFile("%s", "%s", "%s"); ?>', $source_filename, $targetie, $media);
                        } else {
                            $output = sprintf('<?php Context::unloadFile("%s%s", "%s", "%s"); ?>', $base_path, $filename, $targetie, $media);
                        }
                    break;
                // js file
                case 'js' :
                        if(preg_match('/^(http|https|\/)/i',$source_filename)) {
                            $output = sprintf('<?php Context::unloadFile("%s", "%s"); ?>', $source_filename, $targetie);
                        } else {
                            $output = sprintf('<?php Context::unloadFile("%s%s", "%s"); ?>', $base_path, $filename, $targetie);
                        }
                    break;
            }

            return $output;
        }
    }
?>
