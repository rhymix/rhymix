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
            if(substr($tpl_filename,-5)!='.html') $tpl_filename .= '.html';

            // create tpl_file variable 
            if(!$tpl_file) $tpl_file = $tpl_path.$tpl_filename;

			// set template file infos.
			$info = pathinfo($tpl_file);
			$this->path = preg_replace('/^\.\//','',$info['dirname']).'/';
			$this->filename = $info['basename'];
			$this->file = $this->path.$this->filename;

			$this->xe_path = preg_replace('/([^\.^\/]+)\.php$/i','',$_SERVER['SCRIPT_NAME']);
			$this->web_path = $this->xe_path.str_replace(_XE_PATH_,'',$this->path);

			// get compiled file name
			$this->compiled_file = sprintf('%s%s.compiled.php',$this->compiled_path, md5($this->file));

			// compare various file's modified time for check changed
			$_handler = filemtime(_XE_PATH_.'classes/template/TemplateHandler.class.php');
			if($this->handler_mtime<$_handler) $this->handler_mtime = $_handler;
			$_comment = filemtime(_XE_PATH_.'classes/template/TemplateParser.comment.php');
			if($this->handler_mtime<$_comment) $this->handler_mtime = $_comment;
			$_tag = filemtime(_XE_PATH_.'classes/template/TemplateParser.tag.php');
			if($this->handler_mtime<$_tag) $this->handler_mtime = $_tag;

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
            if(!$this->file || !file_exists($this->file)) {
				Context::close();
				printf('"%s" template file is not exists.', $this->file);
				exit();
			}

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

            return $this->parse();
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

			// load template parser
			require_once(_XE_PATH_.'classes/template/TemplateParser.comment.php');
			require_once(_XE_PATH_.'classes/template/TemplateParser.tag.php');

            // read tpl file 
            $buff = FileHandler::readFile($this->file);

			// replace value of src in img/input/script tag
			$buff = preg_replace_callback('/<(img|input|script)([^>]*)src="([^"]*?)"/is', array($this, '_replacePath'), $buff);

			// replace template syntax to php script syntax
			$oCommentParser = new TemplateParserComment($this);
			$buff = $oCommentParser->parse($buff);

			$oTagParser = new TemplateParserTag($this);
			$buff = $oTagParser->parse($buff);

            // prevent from calling directly before writing into file
            $this->buff = '<?php if(!defined("__ZBXE__")) exit();?>'.$buff;
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

            if($_SESSION['is_logged']) $__Context->logged_info = $_SESSION['logged_info'];

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
		private function _replacePath($matches) 
		{
			$path = trim($matches[3]);

			if(substr($path,0,1)=='/' || substr($path,0,1)=='{' || strpos($path,'://')!==false) return $matches[0];

			if(substr($path,0,2)=='./') $path = substr($path,2);
			$target = $this->web_path.$path;
			while(strpos($target,'/../')!==false) 
			{
				$target = preg_replace('/\/([^\/]+)\/\.\.\//','/',$target);
			}
			return '<'.$matches[1].$matches[2].'src="'.$target.'"';
		}
    }
?>
