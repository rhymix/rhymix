<?php
    /**
    * @class DisplayHandler
    * @author zero (zero@nzeo.com)
    * @brief 데이터 출력을 위한 class (XML/HTML 데이터를 구분하여 출력)
    *
    * Response Method에 따라서 html or xml 출력방법을 결정한다
    * xml : oModule의 variables를 simple xml 로 출력
    * html : oModule의 template/variables로 html을 만들고 contents_html로 처리
    *        widget이나 layout의 html과 연동하여 출력
    **/

    class DisplayHandler extends Handler {

        var $content_size = 0; ///< 출력하는 컨텐츠의 사이즈

        var $gz_enabled = false; ///< gzip 압축하여 컨텐츠 호출할 것인지에 대한 flag변수

        /**
         * @brief 모듈객체를 받아서 content 출력
         **/
        function printContent(&$oModule) {

            // gzip encoding 지원 여부 체크
            $this->gz_enabled = Context::isGzEnabled();

            // header 출력
            $this->_printHeader();
            // request method에 따른 처리
            if(Context::getRequestMethod() == 'XMLRPC') $content = $this->_toXmlDoc($oModule);
            else if(Context::getRequestMethod() == 'JSON') $content = $this->_toJSON($oModule);
            else $content = $this->_toHTMLDoc($oModule);

            // 요청방식에 따라 출력을 별도로
            if(Context::getResponseMethod()=="HTML") {

                Context::set('content', $content);

                // 레이아웃을 컴파일
                if(__DEBUG__==3) $start = getMicroTime();
                $oTemplate = &TemplateHandler::getInstance();

                if(Context::get('layout') != 'none') {
                    $layout_path = $oModule->getLayoutPath();
                    $layout_file = $oModule->getLayoutFile();
                    $edited_layout_file = $oModule->getEditedLayoutFile();
                }
                if(!$layout_path) $layout_path = './common/tpl/';
                if(!$layout_file) $layout_file = 'default_layout.html';
                $zbxe_final_content = $oTemplate->compile($layout_path, $layout_file, $edited_layout_file);

                if(__DEBUG__==3) $GLOBALS['__layout_compile_elapsed__'] = getMicroTime()-$start;


                // 각 위젯, 에디터 컴포넌트의 코드 변경
                if(__DEBUG__==3) $start = getMicroTime();

                $oContext = &Context::getInstance();
                $zbxe_final_content= $oContext->transContent($zbxe_final_content);

                if(__DEBUG__==3) $GLOBALS['__trans_widget_editor_elapsed__'] = getMicroTime()-$start;

                // 최종 결과를 common_layout에 넣어버림
                Context::set('zbxe_final_content', $zbxe_final_content);
                $output = $oTemplate->compile('./common/tpl', 'common_layout');

            } else {

                $output = $content;

            }

            // 애드온 실행
            $called_position = 'before_display_content';
            @include("./files/cache/activated_addons.cache.php");

            $this->content_size = strlen($output);

            // 컨텐츠 출력
            $this->display($output);
        }

        /**
         * @brief 최종 결과물의 출력
         **/
        function display($content) {
            $content .= $this->_debugOutput();

            // 출력하기 전에 trigger 호출 (after)
            ModuleHandler::triggerCall('display', 'after', $content);

            if($this->gz_enabled) print ob_gzhandler($content, 5);
            else print $content;
        }

        /**
         * @brief RequestMethod가 JSON이면 JSON 데이터로 컨텐츠 생성
         **/
        function _toJSON(&$oModule) {
            $variables = $oModule->getVariables();
            $variables['error'] = $oModule->getError();
            $variables['message'] = $oModule->getMessage();
            //if(function_exists('json_encode')) return json_encode($variables);
            //else return json_encode2($variables);
            $json = str_replace("\r\n",'\n',json_encode2($variables));
            return $json;
        }


        /**
         * @brief RequestMethod가 XML이면 XML 데이터로 컨텐츠 생성
         **/
        function _toXmlDoc(&$oModule) {
            $variables = $oModule->getVariables();

            $xmlDoc  = "<response>\n";
            $xmlDoc .= sprintf("<error>%s</error>\n",$oModule->getError());
            $xmlDoc .= sprintf("<message>%s</message>\n",str_replace(array('<','>','&'),array('&lt;','&gt;','&amp;'),$oModule->getMessage()));

            $xmlDoc .= $this->_makeXmlDoc($variables);

            $xmlDoc .= "</response>";

            return $xmlDoc;
        }

        function _makeXmlDoc($obj) {
            if(!count($obj)) return;

            $xmlDoc = '';

            foreach($obj as $key => $val) {
                if(is_numeric($key)) $key = 'item';

                if(is_string($val)) $xmlDoc .= sprintf('<%s><![CDATA[%s]]></%s>%s', $key, $val, $key,"\n");
                else if(!is_array($val) && !is_object($val)) $xmlDoc .= sprintf('<%s>%s</%s>%s', $key, $val, $key,"\n");
                else $xmlDoc .= sprintf('<%s>%s%s</%s>%s',$key, "\n", $this->_makeXmlDoc($val), $key, "\n");
            }

            return $xmlDoc;
        }

        /**
         * @brief RequestMethod가 XML이 아니면 html 컨텐츠 생성
         **/
        function _toHTMLDoc(&$oModule) {
            // template handler 객체 생성
            $oTemplate = &TemplateHandler::getInstance();

            // module tpl 변환
            $template_path = $oModule->getTemplatePath();
            $tpl_file = $oModule->getTemplateFile();
            return $oTemplate->compile($template_path, $tpl_file);
        }

        /**
         * @brief content size return
         **/
        function getContentSize() {
            return $this->content_size;
        }

        /**
         * @brief 디버그 모드일 경우 디버깅 메시지 출력
         *
         * __DEBUG__ 값이 1 이상이면 __DEBUG_OUTPUT__ 값에 따라 메시지 출력.
         * __DEBUG__를 세팅하고, __DEBUG_OUTPUT__ == 0 이면
         * tail -f ./files/_debug_message.php로 하여 console로 확인하면 편리함
         **/
        function _debugOutput() {
            if(!__DEBUG__) return;

            $end = getMicroTime();

            // Firebug 콘솔 출력
            if(__DEBUG_OUTPUT__ == 2 && version_compare(PHP_VERSION, '5.2.0', '>=')) {
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
                                    $this->getContentSize().' byte'
                                )
                            )
                        ),
                        'TABLE'
                    );
                    $firephp->fb(
                        array('Elapsed time >>> Total : '.sprintf('%0.5f sec', $end - __StartTime__),
                            array(array('DB queries', 'class file load', 'Template compile', 'XmlParse compile', 'PHP', 'Widgets', 'Trans widget&editor'),
                                array(
                                    sprintf('%0.5f sec', $GLOBALS['__db_elapsed_time__']),
                                    sprintf('%0.5f sec', $GLOBALS['__elapsed_class_load__']),
                                    sprintf('%0.5f sec (%d called)', $GLOBALS['__template_elapsed__'], $GLOBALS['__TemplateHandlerCalled__']),
                                    sprintf('%0.5f sec', $GLOBALS['__xmlparse_elapsed__']),
                                    sprintf('%0.5f sec', $end-__StartTime__-$GLOBALS['__template_elapsed__']-$GLOBALS['__xmlparse_elapsed__']-$GLOBALS['__db_elapsed_time__']-$GLOBALS['__elapsed_class_load__']),
                                    sprintf('%0.5f sec', $GLOBALS['__widget_excute_elapsed__']),
                                    sprintf('%0.5f sec', $GLOBALS['__trans_widget_editor_elapsed__'])
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
                // debug string 작성 시작
                $buff  = "** Debug at ".date('Y-m-d H:i:s').str_repeat('*', 60)."\n";

                // 전체 실행 시간 출력, Request/Response info 출력
                if(__DEBUG__ & 2) {
                    // Request/Response 정보 작성
                    $buff .= "\n- Request/ Response info\n";
                    $buff .= sprintf("\tRequest URI \t\t\t: %s:%s%s%s%s\n", $_SERVER['SERVER_NAME'], $_SERVER['SERVER_PORT'], $_SERVER['PHP_SELF'], $_SERVER['QUERY_STRING']?'?':'', $_SERVER['QUERY_STRING']);
                    $buff .= sprintf("\tRequest method \t\t\t: %s\n", $_SERVER['REQUEST_METHOD']);
                    $buff .= sprintf("\tResponse method \t\t: %s\n", Context::getResponseMethod());
                    $buff .= sprintf("\tResponse contents size\t\t: %d byte\n", $this->getContentSize());

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
                    $buff .= sprintf("\n\tTrans widget&editor elapsed time: %0.5f sec\n", $GLOBALS['__trans_widget_editor_elapsed__']);
                }

                // DB 로그 작성
                if(__DEBUG__ & 4) {
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
                if(__DEBUG_OUTPUT__ == 1 && Context::getResponseMethod() == 'HTML') {
                    if(__DEBUG_PROTECT__ == 1 && __DEBUG_PROTECT_IP__ != $_SERVER['REMOTE_ADDR']) {
                        $buff = 'The IP address is not allowed. Change the value of __DEBUG_PROTECT_IP__ into your IP address in config/config.user.inc.php or config/config.inc.php';
                    }
                    return "<!--\r\n".$buff."\r\n-->";
                }

                // 파일에 출력
                if(__DEBUG_OUTPUT__ == 0) {
                    $debug_file = _XE_PATH_.'files/_debug_message.php';
                    $debug_output = sprintf("[%s %s:%d]\n%s\n", date('Y-m-d H:i:s'), $file_name, $line_num, print_r($debug_output, true));

                    if($display_option === true) $debug_output = str_repeat('=', 40)."\n".$debug_output.str_repeat('-', 40);
                    $debug_output = "\n<?php\n/*".$debug_output."*/\n?>\n";

                    if(@!$fp = fopen($debug_file, 'a')) return;
                    fwrite($fp, $debug_output);
                    fclose($fp);
                }
            }
        }


        /**
         * @brief RequestMethod에 맞춰 헤더 출력
         ***/
        function _printHeader() {
            if($this->gz_enabled) header("Content-Encoding: gzip");
            if(Context::getResponseMethod() == 'JSON') return $this->_printJSONHeader();
            else if(Context::getResponseMethod() != 'HTML') return $this->_printXMLHeader();
            else return $this->_printHTMLHeader();
        }

        /**
         * @brief xml header 출력 (utf8 고정)
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
         * @brief html header 출력 (utf8 고정)
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
         * @brief JSON header 출력 (utf8 고정)
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
