<?php
    /**
    * @class DisplayHandler
    * @author zero (zero@nzeo.com)
    * @brief DisplayHandler is responsible for displaying the execution result. \n 
    *  Depending on the request type, it can display either HTML or XML content.\n
    *  Xml content is simple xml presentation of variables in oModule while html content
    *   is the combination of the variables of oModue and template files/.
    **/

    class DisplayHandler extends Handler {

        var $content_size = 0; ///< 출력하는 컨텐츠의 사이즈

        var $gz_enabled = false; ///< gzip 압축하여 컨텐츠 호출할 것인지에 대한 flag변수
		var $handler = null;

        /**
         * @brief print either html or xml content given oModule object 
         * @remark addon execution and the trigger execution are included within this method, which
         * might create inflexibility for the fine grained caching
        * @param[in] $oModule the module object
        **/
        function printContent(&$oModule) {

            // gzip encoding 지원 여부 체크
            if(
                (defined('__OB_GZHANDLER_ENABLE__') && __OB_GZHANDLER_ENABLE__ == 1) &&
                strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip')!==false &&
                function_exists('ob_gzhandler') &&
                extension_loaded('zlib')
            ) $this->gz_enabled = true;

            // request method에 따른 컨텐츠 결과물 추출 
            if(Context::get('xeVirtualRequestMethod')=='xml') {
				require_once("./classes/display/VirtualXMLDisplayHandler.php");
				$handler = new VirtualXMLDisplayHandler();
			}
            else if(Context::getRequestMethod() == 'XMLRPC') {
				require_once("./classes/display/XMLDisplayHandler.php");
				$handler = new XMLDisplayHandler();
				if(strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') !== false) $this->gz_enabled = false;
			}
            else if(Context::getRequestMethod() == 'JSON') {
				require_once("./classes/display/JSONDisplayHandler.php");
				$handler = new JSONDisplayHandler();
			}
            else {
				require_once("./classes/display/HTMLDisplayHandler.php");
				$handler = new HTMLDisplayHandler();
			}

			$output = $handler->toDoc($oModule);

            // 출력하기 전에 trigger 호출 (before)
            ModuleHandler::triggerCall('display', 'before', $output);

            // 애드온 실행
            $called_position = 'before_display_content';
            $oAddonController = &getController('addon');
            $addon_file = $oAddonController->getCacheFilePath(Mobile::isFromMobilePhone()?"mobile":"pc");
            @include($addon_file);

			if(method_exists($handler, "prepareToPrint")) $handler->prepareToPrint($output);

            // header 출력
            if($this->gz_enabled) header("Content-Encoding: gzip");
			if(Context::getResponseMethod() == 'JSON') $this->_printJSONHeader();
			else if(Context::getResponseMethod() != 'HTML') $this->_printXMLHeader();
			else $this->_printHTMLHeader();

            // debugOutput 출력
            $this->content_size = strlen($output);
            $output .= $this->_debugOutput();

            // 결과물 직접 출력
            if($this->gz_enabled) print ob_gzhandler($output, 5);
            else print $output;

            // 출력 후 trigger 호출 (after)
            ModuleHandler::triggerCall('display', 'after', $content);
        }


        /**
         * @brief Print debugging message to designated output source depending on the value set to __DEBUG_OUTPUT_. \n
         *  This method only functions when __DEBUG__ variable is set to 1.
         *  __DEBUG_OUTPUT__ == 0, messages are written in ./files/_debug_message.php
         **/
        function _debugOutput() {
            if(!__DEBUG__) return;

            $end = getMicroTime();

            // Firebug 콘솔 출력
            if(__DEBUG_OUTPUT__ == 2 && version_compare(PHP_VERSION, '6.0.0') === -1) {
                static $firephp;
                if(!isset($firephp)) $firephp = FirePHP::getInstance(true);

                if(__DEBUG_PROTECT__ == 1 && __DEBUG_PROTECT_IP__ != $_SERVER['REMOTE_ADDR']) {
                    $firephp->fb('Change the value of __DEBUG_PROTECT_IP__ into your IP address in config/config.user.inc.php or config/config.inc.php', 'The IP address is not allowed.');
                    return;
                }

                // 전체 실행 시간 출력, Request/Response info 출력
                if(__DEBUG__ & 2) {
                    $firephp->fb(
                        array('Request / Response info >>> '.$_SERVER['REQUEST_METHOD'].' / '.Context::getResponseMethod(),
                            array(
                                array('Request URI', 'Request method', 'Response method', 'Response contents size'),
                                array(
                                    sprintf("%s:%s%s%s%s", $_SERVER['SERVER_NAME'], $_SERVER['SERVER_PORT'], $_SERVER['PHP_SELF'], $_SERVER['QUERY_STRING']?'?':'', $_SERVER['QUERY_STRING']),
                                    $_SERVER['REQUEST_METHOD'],
                                    Context::getResponseMethod(),
                                    $this->content_size.' byte'
                                )
                            )
                        ),
                        'TABLE'
                    );
                    $firephp->fb(
                        array('Elapsed time >>> Total : '.sprintf('%0.5f sec', $end - __StartTime__),
                            array(array('DB queries', 'class file load', 'Template compile', 'XmlParse compile', 'PHP', 'Widgets', 'Trans Content'),
                                array(
                                    sprintf('%0.5f sec', $GLOBALS['__db_elapsed_time__']),
                                    sprintf('%0.5f sec', $GLOBALS['__elapsed_class_load__']),
                                    sprintf('%0.5f sec (%d called)', $GLOBALS['__template_elapsed__'], $GLOBALS['__TemplateHandlerCalled__']),
                                    sprintf('%0.5f sec', $GLOBALS['__xmlparse_elapsed__']),
                                    sprintf('%0.5f sec', $end-__StartTime__-$GLOBALS['__template_elapsed__']-$GLOBALS['__xmlparse_elapsed__']-$GLOBALS['__db_elapsed_time__']-$GLOBALS['__elapsed_class_load__']),
                                    sprintf('%0.5f sec', $GLOBALS['__widget_excute_elapsed__']),
                                    sprintf('%0.5f sec', $GLOBALS['__trans_content_elapsed__'])
                                )
                            )
                        ),
                        'TABLE'
                    );
                }

                // DB 쿼리 내역 출력
                if((__DEBUG__ & 4) && $GLOBALS['__db_queries__']) {
                    $queries_output = array(array('Query', 'Elapsed time', 'Result'));
                    foreach($GLOBALS['__db_queries__'] as $query) {
                        array_push($queries_output, array($query['query'], sprintf('%0.5f', $query['elapsed_time']), $query['result']));
                    }
                    $firephp->fb(
                        array(
                            'DB Queries >>> '.count($GLOBALS['__db_queries__']).' Queries, '.sprintf('%0.5f sec', $GLOBALS['__db_elapsed_time__']),
                            $queries_output
                        ),
                        'TABLE'
                    );
                }


            // 파일 및 HTML 주석으로 출력
            } else {

                // 전체 실행 시간 출력, Request/Response info 출력
                if(__DEBUG__ & 2) {
                    if(__DEBUG_PROTECT__ == 1 && __DEBUG_PROTECT_IP__ != $_SERVER['REMOTE_ADDR']) {
                        return;
                    }

                    // Request/Response 정보 작성
                    $buff .= "\n- Request/ Response info\n";
                    $buff .= sprintf("\tRequest URI \t\t\t: %s:%s%s%s%s\n", $_SERVER['SERVER_NAME'], $_SERVER['SERVER_PORT'], $_SERVER['PHP_SELF'], $_SERVER['QUERY_STRING']?'?':'', $_SERVER['QUERY_STRING']);
                    $buff .= sprintf("\tRequest method \t\t\t: %s\n", $_SERVER['REQUEST_METHOD']);
                    $buff .= sprintf("\tResponse method \t\t: %s\n", Context::getResponseMethod());
                    $buff .= sprintf("\tResponse contents size\t\t: %d byte\n", $this->content_size);

                    // 전체 실행 시간
                    $buff .= sprintf("\n- Total elapsed time : %0.5f sec\n", $end-__StartTime__);

                    $buff .= sprintf("\tclass file load elapsed time \t: %0.5f sec\n", $GLOBALS['__elapsed_class_load__']);
                    $buff .= sprintf("\tTemplate compile elapsed time\t: %0.5f sec (%d called)\n", $GLOBALS['__template_elapsed__'], $GLOBALS['__TemplateHandlerCalled__']);
                    $buff .= sprintf("\tXmlParse compile elapsed time\t: %0.5f sec\n", $GLOBALS['__xmlparse_elapsed__']);
                    $buff .= sprintf("\tPHP elapsed time \t\t: %0.5f sec\n", $end-__StartTime__-$GLOBALS['__template_elapsed__']-$GLOBALS['__xmlparse_elapsed__']-$GLOBALS['__db_elapsed_time__']-$GLOBALS['__elapsed_class_load__']);

                    // 위젯 실행 시간 작성
                    $buff .= sprintf("\n\tWidgets elapsed time \t\t: %0.5f sec", $GLOBALS['__widget_excute_elapsed__']);

                    // 레이아웃 실행 시간
                    $buff .= sprintf("\n\tLayout compile elapsed time \t: %0.5f sec", $GLOBALS['__layout_compile_elapsed__']);

                    // 위젯, 에디터 컴포넌트 치환 시간
                    $buff .= sprintf("\n\tTrans Content \t\t\t: %0.5f sec\n", $GLOBALS['__trans_content_elapsed__']);
                }

                // DB 로그 작성
                if(__DEBUG__ & 4) {
                    if(__DEBUG_PROTECT__ == 1 && __DEBUG_PROTECT_IP__ != $_SERVER['REMOTE_ADDR']) {
                        return;
                    }

                    if($GLOBALS['__db_queries__']) {
                        $buff .= sprintf("\n- DB Queries : %d Queries. %0.5f sec\n", count($GLOBALS['__db_queries__']), $GLOBALS['__db_elapsed_time__']);
                        $num = 0;

                        foreach($GLOBALS['__db_queries__'] as $query) {
                            $buff .= sprintf("\t%02d. %s\n\t\t%0.6f sec. ", ++$num, $query['query'], $query['elapsed_time']);
                            if($query['result'] == 'Success') {
                                $buff .= "Query Success\n";
                            } else {
                                $buff .= sprintf("Query $s : %d\n\t\t\t   %s\n", $query['result'], $query['errno'], $query['errstr']);
                            }
                        }
                    }
                }

                // HTML 주석으로 출력
                if($buff && __DEBUG_OUTPUT__ == 1 && Context::getResponseMethod() == 'HTML') {
                    $buff = sprintf("[%s %s:%d]\n%s\n", date('Y-m-d H:i:s'), $file_name, $line_num, print_r($buff, true));

                    if(__DEBUG_PROTECT__ == 1 && __DEBUG_PROTECT_IP__ != $_SERVER['REMOTE_ADDR']) {
                        $buff = 'The IP address is not allowed. Change the value of __DEBUG_PROTECT_IP__ into your IP address in config/config.user.inc.php or config/config.inc.php';
                    }

                    return "<!--\r\n".$buff."\r\n-->";
                }

                // 파일에 출력
                if($buff && __DEBUG_OUTPUT__ == 0) {
                    $debug_file = _XE_PATH_.'files/_debug_message.php';
                    $buff = sprintf("[%s %s:%d]\n%s\n", date('Y-m-d H:i:s'), $file_name, $line_num, print_r($buff, true));

                    $buff = str_repeat('=', 40)."\n".$buff.str_repeat('-', 40);
                    $buff = "\n<?php\n/*".$buff."*/\n?>\n";

                    if(@!$fp = fopen($debug_file, 'a')) return;
                    fwrite($fp, $buff);
                    fclose($fp);
                }
            }
        }

		/**
		 * @brief print a HTTP HEADER for XML, which is encoded in UTF-8
		 **/
		function _printXMLHeader() {
			header("Content-Type: text/xml; charset=UTF-8");
			header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
			header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
			header("Cache-Control: no-store, no-cache, must-revalidate");
			header("Cache-Control: post-check=0, pre-check=0", false);
			header("Pragma: no-cache");
		}


		/**
		 * @brief print a HTTP HEADER for HTML, which is encoded in UTF-8
		 **/
		function _printHTMLHeader() {
			header("Content-Type: text/html; charset=UTF-8");
			header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
			header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
			header("Cache-Control: no-store, no-cache, must-revalidate");
			header("Cache-Control: post-check=0, pre-check=0", false);
			header("Pragma: no-cache");
		}


		/**
		 * @brief print a HTTP HEADER for JSON, which is encoded in UTF-8
		 **/
		function _printJSONHeader() {
			header("Content-Type: text/html; charset=UTF-8");
			header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
			header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
			header("Cache-Control: no-store, no-cache, must-revalidate");
			header("Cache-Control: post-check=0, pre-check=0", false);
			header("Pragma: no-cache");
		}




    }
?>
