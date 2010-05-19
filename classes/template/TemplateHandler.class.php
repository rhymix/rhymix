<?php
    /**
     * @class TemplateHandler
     * @author zero (zero@nzeo.com)
     * @brief template compiler 
     * @version 0.1
     * @remarks It compiles template file by using regular expression into php 
     *          code, and XE caches compiled code for further uses 
     **/

    class TemplateHandler extends Handler {

        var $compiled_path = './files/cache/template_compiled/'; ///< path of compiled caches files

        var $tpl_path = ''; ///< target directory 
        var $tpl_file = ''; ///< target filename

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
         * @brief compiles specified tpl file and execution result in Context into resultant content 
         * @param[in] $tpl_path path of the directory containing target template file
         * @param[in] $tpl_filename target template file's name
         * @param[in] $tpl_file if specified use it as template file's full path 
         * @return Returns compiled result in case of success, NULL otherwise 
         */
        function compile($tpl_path, $tpl_filename, $tpl_file = '') {
            // store the starting time for debug information
            if(__DEBUG__==3 ) $start = getMicroTime();

            // verify arguments 
            if(substr($tpl_path,-1)!='/') $tpl_path .= '/';
            if(substr($tpl_filename,-5)!='.html') $tpl_filename .= '.html';

            // create tpl_file variable 
            if(!$tpl_file) $tpl_file = $tpl_path.$tpl_filename;

            // if target file does not exist return 
            if(!$tpl_file || !file_exists(FileHandler::getRealPath($tpl_file))) return;

            $this->tpl_path = preg_replace('/^\.\//','',$tpl_path);
            $this->tpl_file = $tpl_file;

			$oCacheHandler = &CacheHandler::getInstance('template');
			if($oCacheHandler->isSupport()){
				$cache_key = 'template:' . $tpl_file;
				$buff = $oCacheHandler->get($cache_key, filemtime(FileHandler::getRealPath($tpl_file)));
				if(!$buff){
					$buff = $this->_compileTplFile($tpl_file);
					$oCacheHandler->put($cache_key, $buff);
				}

				$output = $this->_fetch('', $buff, $tpl_path);
			}else{
				// get cached compiled file name
				$compiled_tpl_file = FileHandler::getRealPath($this->_getCompiledFileName($tpl_file));

				// compile 
				$buff = $this->_compile($tpl_file, $compiled_tpl_file);

				// make a result, combining Context and compiled_tpl_file
				$output = $this->_fetch($compiled_tpl_file, $buff, $tpl_path);
			}

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
            $this->tpl_path = $tpl_path;
            $this->tpl_file = $tpl_file;

            $tpl_file = $tpl_path.$tpl_filename;
            if(!file_exists($tpl_file)) return;

            return $this->_compileTplFile($tpl_file);
        }

        /**
         * @brief compile a template file only if a cached template file does not exist or it is outdated. 
         * @param[in] $tpl_path a file path of the target template file
         * @param[in] $compiled_tpl_file a file path of cached template file
         * @return Returns compiled template file if cached one does not exists or it is outdated, NULL otherwise
         **/
        function _compile($tpl_file, $compiled_tpl_file) {
            if(!file_exists($compiled_tpl_file)) return $this->_compileTplFile($tpl_file, $compiled_tpl_file);

            $source_ftime = filemtime(FileHandler::getRealPath($tpl_file));
            $target_ftime = filemtime($compiled_tpl_file);
            if($source_ftime>$target_ftime) return $this->_compileTplFile($tpl_file, $compiled_tpl_file);
        }

        /**
         * @brief compile a template file specified in $tpl_file and 
         * @pre files specified by $tpl_file exists.
         * @param[in] $tpl_file path of tpl file
         * @param[in] $compiled_tpl_file if specified, write compiled result into the file
         * @return compiled result in case of success or NULL in case of error 
         **/
        function _compileTplFile($tpl_file, $compiled_tpl_file = '') {

            // read tpl file 
            $buff = FileHandler::readFile($tpl_file);
            if(!$buff) return;

            // replace include <!--#include($filename)-->
            $buff = preg_replace_callback('!<\!--#include\(([^\)]*?)\)-->!is', array($this, '_compileIncludeToCode'), $buff);

            // if value of src in img/input tag starts with ./ or with filename replace the path
            $buff = preg_replace_callback('/<(img|input)([^>]*)src=[\'"]{1}(.*?)[\'"]{1}/is', array($this, '_compileImgPath'), $buff);

            // replace variables
            $buff = preg_replace_callback('/\{[^@^ ]([^\{\}\n]+)\}/i', array($this, '_compileVarToContext'), $buff);

            // replace parts not displaying results
            $buff = preg_replace_callback('/\{\@([^\{\}]+)\}/i', array($this, '_compileVarToSilenceExecute'), $buff);

            // replace <!--@, --> 
            $buff = preg_replace_callback('!<\!--@(.*?)-->!is', array($this, '_compileFuncToCode'), $buff);

            // remove comments <!--// ~ --> 
            $buff = preg_replace('!(\n?)( *?)<\!--//(.*?)-->!is', '', $buff);

            // import xml filter/ css/ js/ files <!--%import("filename"[,optimized=true|false][,media="media"][,targetie="lt IE 6|IE 7|gte IE 8|..."])--> (media is applied to only css)
            $buff = preg_replace_callback('!<\!--%import\(\"([^\"]*?)\"(,optimized\=(true|false))?(,media\=\"([^\"]*)\")?(,targetie=\"([^\"]*)\")?\)-->!is', array($this, '_compileImportCode'), $buff);

            // unload css/ js <!--%unload("filename"[,optimized=true|false][,media="media"][,targetie="lt IE 6|IE 7|gte IE 8|..."])--> (media is applied to only css)
            $buff = preg_replace_callback('!<\!--%unload\(\"([^\"]*?)\"(,optimized\=(true|false))?(,media\=\"([^\"]*)\")?(,targetie=\"([^\"]*)\")?\)-->!is', array($this, '_compileUnloadCode'), $buff);

            // javascript plugin import
            $buff = preg_replace_callback('!<\!--%load_js_plugin\(\"([^\"]*?)\"\)-->!is', array($this, '_compileLoadJavascriptPlugin'), $buff);

            // prevent from calling directly before writing into file
            $buff = sprintf('%s%s%s','<?php if(!defined("__ZBXE__")) exit();?>',"\n",$buff);

            // write compiled code into file only if $compiled_tpl_file is not NULL
            if($compiled_tpl_file) FileHandler::writeFile($compiled_tpl_file, $buff);

            return $buff;
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
         * @brief change image path
         * @pre $matches is an array containg three elements
         * @param[in] $matches match
         * @return changed result
         **/
        function _compileImgPath($matches) {
            static $real_path = null;
            $str1 = $matches[0];
            $str2 = $path = trim($matches[3]);

            if(substr($path,0,1)=='/' || substr($path,0,1)=='{' || strpos($path,'://')!==false) return $str1;
            if(substr($path,0,2)=='./') $path = substr($path,2);

            if(is_null($real_path)) {
                $url = parse_url(Context::getRequestUri());
                $real_path = $url['path'];
            }

            $target = $real_path.$this->tpl_path.$path;
            while(strpos($target,'/../')!==false) {
                $target = preg_replace('/\/([^\/]+)\/\.\.\//','/',$target);
            }
            return str_replace($str2, $target, $str1);
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
            $source_filename = sprintf("%s/%s", dirname($this->tpl_file), $arg);

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
         * @brief replace xe specific code, "<!--%filename-->" with appropriate php code 
         * @param[in] $matches match
         * @return Returns modified result or NULL in case of error
         **/
        function _compileImportCode($matches) {
            // find xml file
            $base_path = $this->tpl_path;
            $given_file = trim($matches[1]);
            if(!$given_file) return;
            if(isset($matches[3])) $optimized = strtolower(trim($matches[3]));
            if(!$optimized) $optimized = 'true';
            if(isset($matches[5])) $media = trim($matches[5]);
            if(!$media) $media = 'all';
            if(isset($matches[7])) $targetie = trim($matches[7]);
            if(!$targetie) $targetie = '';
            else $optimized = 'false';

            // if given_file ends with lang, load language pack
            if(substr($given_file, -4)=='lang') {
                if(substr($given_file,0,2)=='./') $given_file = substr($given_file, 2);
                $lang_dir = sprintf('%s%s', $this->tpl_path, $given_file);
                if(is_dir($lang_dir)) $output = sprintf('<?php Context::loadLang("%s"); ?>', $lang_dir);

            // otherwise try to load xml, css, js file
            } else {
                if(substr($given_file,0,1)!='/') $source_filename = sprintf("%s%s",$base_path, $given_file);
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
                                $output = sprintf('<?php Context::addCSSFile("%s", %s, "%s", "%s"); ?>', $source_filename, 'false', $media, $targetie);
                            } else {
                                $meta_file = sprintf('%s%s', $base_path, $filename);
                                $output = sprintf('<?php Context::addCSSFile("%s%s", %s, "%s", "%s"); ?>', $base_path, $filename, $optimized, $media, $targetie);
                            }
                        break;
                    // js file
                    case 'js' :
                            if(preg_match('/^(http|\/)/i',$source_filename)) {
                                $output = sprintf('<?php Context::addJsFile("%s", %s, "%s"); ?>', $source_filename, 'false', $targetie);
                            } else {
                                $meta_file = sprintf('%s%s', $base_path, $filename);
                                $output = sprintf('<?php Context::addJsFile("%s%s", %s, "%s"); ?>', $base_path, $filename, $optimized, $targetie);
                            }
                        break;
                }
            }

            if($meta_file) $output = '<!--Meta:'.$meta_file.'-->'.$output;
            return $output;
        }

        /**
         * @brief import javascript plugin 
         * @param[in] $matches match
         * @return result loading the plugin
         * @remarks javascript plugin works as optimized = false
         **/
        function _compileLoadJavascriptPlugin($matches) {
            $base_path = $this->tpl_path;
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
            $base_path = $this->tpl_path;
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
                        if(preg_match('/^(http|\/)/i',$source_filename)) {
                            $output = sprintf('<?php Context::unloadCSSFile("%s", %s, "%s", "%s"); ?>', $source_filename, 'false', $media, $targetie);
                        } else {
                            $meta_file = sprintf('%s%s', $base_path, $filename);
                            $output = sprintf('<?php Context::unloadCSSFile("%s%s", %s, "%s", "%s"); ?>', $base_path, $filename, $optimized, $media, $targetie);
                        }
                    break;
                // js file
                case 'js' :
                        if(preg_match('/^(http|\/)/i',$source_filename)) {
                            $output = sprintf('<?php Context::unloadJsFile("%s", %s, "%s"); ?>', $source_filename, 'false', $targetie);
                        } else {
                            $meta_file = sprintf('%s%s', $base_path, $filename);
                            $output = sprintf('<?php Context::unloadJsFile("%s%s", %s, "%s"); ?>', $base_path, $filename, $optimized, $targetie);
                        }
                    break;
            }

            return $output;
        }

        /**
         * @brief return compiled_tpl_file's name accroding to template file name
         * @param[in] $tpl_file template file name
         * @return compiled template file's name
         **/
        function _getCompiledFileName($tpl_file) {
            return sprintf('%s%s.compiled.php',$this->compiled_path, md5($tpl_file));
        }

        /**
         * @brief fetch using ob_* function 
         * @param[in] $compiled_tpl_file path of compiled template file
         * @param[in] $buff if buff is not null, eval it instead of including compiled template file
         * @param[in] $tpl_path set context's tpl path
         * @return result string
         **/
        function _fetch($compiled_tpl_file, $buff = NULL, $tpl_path = '') {
            $__Context = &$GLOBALS['__Context__'];
            $__Context->tpl_path = $tpl_path;

            if($_SESSION['is_logged']) $__Context->logged_info = $_SESSION['logged_info'];

            ob_start();

            if($buff) {
                $eval_str = "?>".$buff;
                eval($eval_str);
            } else {
                include($compiled_tpl_file);
            }

            return ob_get_clean();
        }
    }
?>
