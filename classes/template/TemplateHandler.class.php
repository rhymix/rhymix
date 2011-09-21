<?php
    /**
     * @class TemplateHandler
     * @author NHN (developers@xpressengine.com)
     * @brief template compiler
     * @version 0.1
     * @remarks It compiles template file by using regular expression into php
     *          code, and XE caches compiled code for further uses
     **/

    class TemplateHandler {

        var $compiled_path = './files/cache/template_compiled/'; ///< path of compiled caches files

        var $path = null; ///< target directory
        var $filename = null; ///< target filename
        var $file = null; ///< target file (fullpath)
		var $xe_path = null;  ///< XpressEngine base path
		var $web_path = null; ///< tpl file web path
		var $compiled_file = null; ///< tpl file web path

		var $handler_mtime = 0;

		function TemplateHandler()
		{
			$this->xe_path  = rtrim(preg_replace('/([^\.^\/]+)\.php$/i','',$_SERVER['SCRIPT_NAME']),'/');
		}

        /**
         * @brief returns TemplateHandler's singleton object
         * @return TemplateHandler instance
         **/
		function &getInstance()
		{
			static $oTemplate = null;

            if(__DEBUG__==3 ) {
                if(!isset($GLOBALS['__TemplateHandlerCalled__'])) $GLOBALS['__TemplateHandlerCalled__']=1;
                else $GLOBALS['__TemplateHandlerCalled__']++;
            }

			if(!$oTemplate) $oTemplate = new TemplateHandler();

            return $oTemplate;
        }

		/**
		 * @brief set variables for template compile
		 **/
		function init($tpl_path, $tpl_filename, $tpl_file='')
		{
            // verify arguments
            if(substr($tpl_path,-1)!='/') $tpl_path .= '/';
			if(!file_exists($tpl_path.$tpl_filename)&&file_exists($tpl_path.$tpl_filename.'.html')) $tpl_filename .= '.html';

            // create tpl_file variable
            if(!$tpl_file) $tpl_file = $tpl_path.$tpl_filename;

			// set template file infos.
			$this->path = $tpl_path;
			$this->filename = $tpl_filename;
			$this->file = $tpl_file;

			$this->web_path = $this->xe_path.preg_replace('/^'.preg_quote(_XE_PATH_,'/').'/','',$this->path);

			// get compiled file name
			$hash = md5($this->file . __ZBXE_VERSION__);
			$this->compiled_file = "{$this->compiled_path}{$hash}.compiled.php";

			// compare various file's modified time for check changed
			$this->handler_mtime = filemtime(__FILE__);
		}

        /**
         * @brief compiles specified tpl file and execution result in Context into resultant content
         * @param[in] $tpl_path path of the directory containing target template file
         * @param[in] $tpl_filename target template file's name
         * @param[in] $tpl_file if specified use it as template file's full path
         * @return Returns compiled result in case of success, NULL otherwise
         */
        function compile($tpl_path, $tpl_filename, $tpl_file='') {
			$buff = '';

            // store the starting time for debug information
            if(__DEBUG__==3 ) $start = getMicroTime();

			// initiation
			$this->init($tpl_path, $tpl_filename, $tpl_file);

            // if target file does not exist exit
            if(!$this->file || !file_exists($this->file)) return "Err : '{$this->file}' template file does not exists.";

            $source_template_mtime = filemtime($this->file);
			$latest_mtime = $source_template_mtime>$this->handler_mtime?$source_template_mtime:$this->handler_mtime;

			// cache control
			$oCacheHandler = &CacheHandler::getInstance('template');

			// get cached buff
			if($oCacheHandler->isSupport()){
				$cache_key = 'template:'.$this->file;
				$buff = $oCacheHandler->get($cache_key, $latest_mtime);
			} else {
				if(is_readable($this->compiled_file) && filemtime($this->compiled_file)>$latest_mtime && filesize($this->compiled_file)) {
					$buff = 'file://'.$this->compiled_file;
				}
			}

			if(!$buff) {
				$buff = $this->parse();
				if($oCacheHandler->isSupport()) $oCacheHandler->put($cache_key, $buff);
				else FileHandler::writeFile($this->compiled_file, $buff);
			}

			$output = $this->_fetch($buff);

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
				exit("Cannot find the template file: '{$this->file}'");
			}

			return $this->parse();
        }

        /**
         * @brief compile a template file specified in $tpl_file and
         * @pre files specified by $tpl_file exists.
         * @param[in] $tpl_file path of tpl file
         * @param[in] $compiled_tpl_file if specified, write compiled result into the file
         * @return compiled result in case of success or NULL in case of error
         **/
        function parse($buff=null) {
			if(is_null($buff)) {
				if(!is_readable($this->file)) return;

				// read tpl file
				$buff = FileHandler::readFile($this->file);
			}

			// replace value of src in img/input/script tag
			$buff = preg_replace_callback('/<(img|input|script)([^>]*)src="([^"]*?)"/is', array($this, '_replacePath'), $buff);

			// replace loop and cond template syntax
			$buff = $this->_parseInline($buff);

			// include, unload/load, import
 			$buff = preg_replace_callback('/<(include|(?:un)?load)( .+?)>|<!--(%import|%load_js_plugin|%unload|#include)\("([^"]+)"(.*?)\)-->|<!--@([a-z@]+)(.*?)-->|{@(.+?)}|{([^@ \\\\][^\n\r\{\}]+?)}/s', array($this, '_parseResource'), $buff);

			// remove block which is a virtual tag and remove comments
			$buff = preg_replace('@</?block\s*>|\s?<!--//(.*?)-->@is','',$buff);

			// form auto generation
			$buff = preg_replace_callback('/(<form(?:<\?php.+?\?>|[^<>]+)*?>)(.*?)(<\/form>)/is', array($this, '_compileFormAuthGeneration'), $buff);

            // prevent from calling directly before writing into file
            $buff = '<?php if(!defined("__XE__"))exit;?>'.$buff;

			return $buff;
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
        function _fetch($buff) {
			if(!$buff) return;

            $__Context = &$GLOBALS['__Context__'];
            $__Context->tpl_path = $this->path;

			if($_SESSION['is_logged']) {
				$__Context->logged_info = Context::get('logged_info');
			}

            ob_start();
			if(substr($buff, 0, 7) == 'file://') {
				include substr($buff, 7);
			} else {
				$eval_str = "?>".$buff;
				eval($eval_str);
			}

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

		function _parseInline($buff)
		{
			if(preg_match_all('/<([a-zA-Z0-9]+)[^>]*?(?:[ \|]cond| loop)="/s', $buff, $matches) === false) return $buff;

			$tags = array_unique($matches[1]);
			$tags = implode('|',array_unique($matches[1]));
			$split_regex = '@(<(?:/(?:'.$tags.')|(?:'.$tags.')(?:[^>]*(?:cond|loop)="[^"]+")*)[^>]*>)@s';

			$nodes = preg_split($split_regex, $buff, -1, PREG_SPLIT_DELIM_CAPTURE);

			// list of self closing tags
			$self_closing = explode(',', 'area,base,basefont,br,hr,input,img,link,meta,param,frame,col');

			for($idx=1,$node_len=count($nodes); $idx < $node_len; $idx+=2) {
				if(!($node=$nodes[$idx])) continue;

				if(preg_match_all('@\s(loop|cond)="([^"]+)"@', $node, $matches)) {
					$closing = 0;

					// process opening tag
					foreach($matches[1] as $n=>$stmt) {
						$expr = $matches[2][$n];
						$expr = $this->_replaceVar($expr);
						$closing++;

						switch($stmt) {
						case 'cond':
							$nodes[$idx-1] .= "<?php if({$expr}){ ?>";
							break;
						case 'loop':
							if(!preg_match('@^(?:(.+?)=>(.+?)(?:,(.+?))?|(.*?;.*?;.*?)|(.+?)\s*=\s*(.+?))$@', $expr, $expr_m)) break;
							if($expr_m[1]) {
								if($expr_m[3]) $expr_m[2] .= '=>'.$expr_m[3];
								$nodes[$idx-1] .= "<?php if({$expr_m[1]}&&count({$expr_m[1]}))foreach({$expr_m[1]} as {$expr_m[2]}){ ?>";
							}elseif($expr_m[4]) {
								$nodes[$idx-1] .= "<?php for({$expr_m[4]}){ ?>";
							}elseif($expr_m[5]) {
								$nodes[$idx-1] .= "<?php while({$expr_m[5]}={$expr_m[6]}){ ?>";
							}
							break;
						}
					}
					$node = preg_replace('@\s(loop|cond)="([^"]+)"@', '', $node);

					// this tag
					$tag = substr($node, 1, strpos($node, ' ')-1);

					// find closing tag
					$close_php = '<?php '.str_repeat('}', $closing).' ?>';
					if($node{1} == '!' || substr($node,-2,1) == '/' || in_array($tag, $self_closing)) { //  self closing tag
						$nodes[$idx+1] = $close_php.$nodes[$idx+1];
					} else {
						$depth = 1;
						for($i=$idx+2; $i < $node_len; $i+=2) {
							$nd = $nodes[$i];
							if(strpos($nd, $tag.' ') === 1) {
								$depth++;
							} elseif(strpos($nd, '/'.$tag) === 1) {
								$depth--;
								if(!$depth) {
									$nodes[$i-1] .= $nodes[$i].$close_php;
									$nodes[$i] = '';
									break;
								}
							}
						}
					}
				}

				if(strpos($node, '|cond="') !== false) {
 					$node = preg_replace('@(\s[\w:]+="[^"]+?")\|cond="(.+?)"@s', '<?php if($2){ ?>$1<?php } ?>', $node);
					$node = $this->_replaceVar($node);
				}

				if($nodes[$idx] != $node) $nodes[$idx] = $node;
			}

			$buff = implode('', $nodes);

			return $buff;
		}

		function _parseResource($m)
		{
			// {$var} or {func(...)}
			if($m[9]) 
			{
				return '<?php echo '.$this->_replaceVar($m[9]).' ?>';
			}

			// {@ ... }
			if($m[8])
			{
				return '<?php '.$this->_replaceVar($m[8]).' ?>';
			}

			// <load ...> or <unload ...> or <!--%import ...--> or <!--%unload ...-->
			if($m[1]=='load'||$m[1]=='unload'||$m[3]=='%import'||$m[3]=='%unload')
			{
				$attr = array();
				if($m[1]) {
					if(!preg_match_all('@ (\w+)="([^"]+)"@', $m[2], $mm)) return $m[0];
					foreach($mm[1] as $idx=>$name) {
						$attr[$name] = $mm[2][$idx];
					}
					$cmd = $m[1];
				} else {
					if(preg_match_all('@,(\w+)="([^"]+)"@', $m[5], $mm)) {
						foreach($mm[1] as $idx=>$name) {
							$attr[$name] = $mm[2][$idx];
						}
					}
					$attr['target'] = $m[4];
					$cmd = substr($m[3], 1);
					if($cmd == 'import') $cmd = 'load';
				}

				if(!preg_match('@^\.?/@',$attr['target'])) $attr['target'] = './'.$attr['target'];

				$isRemoteFile = !!preg_match('@^https?://@i', $attr['target']);

				$metafile = '';
				$pathinfo = pathinfo($attr['target']);
				$relativeDir = $this->_getRelativeDir($pathinfo['dirname']);

				if(!$isRemoteFile) $attr['target'] = $relativeDir.'/'.$pathinfo['basename'];

				switch($pathinfo['extension'])
				{
					case 'xml':
						if($isRemoteFile || $cmd != 'load') return '';
						// language file?
						if($pathinfo['basename'] == 'lang.xml' && substr($pathinfo['dirname'],-5) == '/lang') {
							$result = "<?php Context::loadLang('{$relativeDir}'); ?>";
						} else {
							$result = "<?php require_once('./classes/xml/XmlJsFilter.class.php');\$__xmlFilter = new XmlJsFilter('{$relativeDir}','{$pathinfo["basename"]}');\$__xmlFilter->compile(); ?>";
						}
						break;
					case 'js':
						if($cmd == 'unload') {
							$result = "<?php Context::unloadFile('{$attr['target']}','{$attr['targetie']}');";
						} else {
							$result = "<?php \$__tmp=array('{$attr['target']}','{$attr['media']}','{$attr['targetie']}','{$attr['index']}','{$attr['usecdn']}','{$attr['cdnprefix']}','{$attr['cdnversion']}');Context::loadFile(\$__tmp);unset(\$__tmp); ?>";
						}
						break;
					case 'css':
						if($cmd == 'unload') {
							$result = "<?php Context::unloadFile('{$attr['target']}','{$attr['targetie']}','{$attr['media']}');";
						} else {
							$metafile = $attr['target'];
							$result = "<?php \$__tmp=array('{$attr['target']}','{$attr['type']}','{$attr['targetie']}','{$attr['index']}','{$attr['usecdn']}','{$attr['cdnprefix']}','{$attr['cdnversion']}');Context::loadFile(\$__tmp);unset(\$__tmp); ?>";
						}
						break;
				}

				if($metafile) $result = "<!--#Meta:{$metafile}-->".$result;

				return $result;
			}
			
			// <!--#include--> or <include ..>
			if($m[1]=='include' || $m[3]=='#include')
			{
				if(!$this->file) return '';
				if($m[1]) {
					if(!preg_match('@target="(.+?)"@', $m[2], $mm)) return '';
					$file = $mm[1];
				} else {
					$file = $m[4];
				}
				$pathinfo = pathinfo($file);
				$fileDir  = $this->_getRelativeDir($pathinfo['dirname']);

				if(!$fileDir) return '';

				return "<?php echo TemplateHandler::getInstance()->compile('{$fileDir}','{$pathinfo['basename']}') ?>";
			}
			
			// <!--%load_js_plugin-->
			if($m[3]=='%load_js_plugin')
			{
				$plugin = $this->_replaceVar($m[4]);
				if(strpos($plugin, '$__Context') === false) $plugin = "'{$plugin}'";
				return "<?php Context::loadJavascriptPlugin({$plugin}); ?>";
			}
			
			// <!--@..--> such as <!--@if($cond)-->, <!--@else-->, <!--@end-->
			if($m[6])
			{
				if(!preg_match('/^(?:(if|switch|for|foreach|while)|(end(?:if|switch|for|foreach|while)?)|(else(?:if)?)|(break@)?(case|default)|(break))$/', $m[6], $mm)) return '';
				if($mm[1]) {
					$precheck = '';
					if($mm[1] == 'foreach') {
						$var = preg_replace('/^\s*\(\s*(.+?) .*$/', '$1', $m[7]);
						$precheck = "if({$var}&&count({$var}))";
					}
					return '<?php '.$this->_replaceVar($precheck.$m[6].$m[7]).'{ ?>';
				}
				if($mm[2]) return "<?php } ?>";
				if($mm[3]) return "<?php }{$m[6]}".$this->_replaceVar($m[7])."{ ?>";
				if($mm[5]) return "<?php ".($mm[4]?'break;':'')."{$m[6]} ".trim($m[7],'()').": ?>";
				if($mm[6]) return "<?php break; ?>";
				return '';
			}

			return $m[0];
		}

		function _getRelativeDir($path)
		{
			$fileDir   = dirname(realpath($this->file));
			if($path{0} != '/') $path = realpath($fileDir.'/'.$path);

			$path = preg_replace('/^'.preg_quote(_XE_PATH_,'/').'/', '', $path);
			$path = ltrim($path, '/');

			return $path;
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

			if($meta_file) $output = '<!--#Meta:'.$meta_file.'-->'.$output;
			return $output;
		}

		/**
		 * @brief replace PHP variables of $ character
		 **/
		function _replaceVar($php) {
			if(!$php) return '';
			return preg_replace('@(?<!::|\\\\)\$([a-z]|_[a-z0-9])@i', '\$__Context->$1', $php);
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
