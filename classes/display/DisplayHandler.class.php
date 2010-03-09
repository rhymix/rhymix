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
            if(Context::get('xeVirtualRequestMethod')=='xml') $output = $this->_toVirtualXmlDoc($oModule);
            else if(Context::getRequestMethod() == 'XMLRPC') $output = $this->_toXmlDoc($oModule);
            else if(Context::getRequestMethod() == 'JSON') $output = $this->_toJSON($oModule);
            else $output = $this->_toHTMLDoc($oModule);

            // HTML 출력 요청일 경우 레이아웃 컴파일과 더블어 완성된 코드를 제공
            if(Context::getResponseMethod()=="HTML") {

                // 관리자 모드일 경우 #xeAdmin id를 가지는 div 추가
                if(Context::get('module')!='admin' && strpos(Context::get('act'),'Admin')>0) $output = '<div id="xeAdmin">'.$output.'</div>';

                // 내용을 content라는 변수로 설정 (layout에서 {$output}에서 대체됨) 
                Context::set('content', $output);

                // 레이아웃을 컴파일
                $oTemplate = &TemplateHandler::getInstance();

                // layout이라는 변수가 none으로 설정되면 기본 레이아웃으로 변경
                if(Context::get('layout') != 'none') {
                    if(__DEBUG__==3) $start = getMicroTime();

                    $layout_path = $oModule->getLayoutPath();

                    $layout_file = $oModule->getLayoutFile();
                    $edited_layout_file = $oModule->getEditedLayoutFile();

                    // 현재 요청된 레이아웃 정보를 구함
                    $oLayoutModel = &getModel('layout');
                    $current_module_info = Context::get('current_module_info');
                    $layout_srl = $current_module_info->layout_srl;

                    // 레이아웃과 연결되어 있으면 레이아웃 컴파일
                    if($layout_srl > 0){
                        $layout_info = Context::get('layout_info');

                        // faceoff 레이아웃일 경우 별도 처리
                        if($layout_info && $layout_info->type == 'faceoff') {
                            $oLayoutModel->doActivateFaceOff($layout_info);
                            Context::set('layout_info', $layout_info);
                        }

                        // 관리자 레이아웃 수정화면에서 변경된 CSS가 있는지 조사
                        $edited_layout_css = $oLayoutModel->getUserLayoutCss($layout_srl);

                        if(file_exists($edited_layout_css)) Context::addCSSFile($edited_layout_css,true,'all','',100);
                    }
                    if(!$layout_path) $layout_path = "./common/tpl";
                    if(!$layout_file) $layout_file = "default_layout";
                    $output = $oTemplate->compile($layout_path, $layout_file, $edited_layout_file);

                    if(__DEBUG__==3) $GLOBALS['__layout_compile_elapsed__'] = getMicroTime()-$start;


                    if(preg_match('/MSIE/i',$_SERVER['HTTP_USER_AGENT']) && (Context::get("_use_ssl")=='optional'||Context::get("_use_ssl")=="always")) {
                        Context::addHtmlFooter('<iframe id="xeTmpIframe" name="xeTmpIframe" style="width:1px;height:1px;position:absolute;top:-2px;left:-2px;"></iframe>');
                    }
                }

            }

            // 출력하기 전에 trigger 호출 (before)
            ModuleHandler::triggerCall('display', 'before', $output);

            // 애드온 실행
            $called_position = 'before_display_content';
            $oAddonController = &getController('addon');
            $addon_file = $oAddonController->getCacheFilePath();
            @include($addon_file);

            // HTML 출력일 경우 최종적으로 common layout을 씌워서 출력
            if(Context::getResponseMethod()=="HTML") {
                if(__DEBUG__==3) $start = getMicroTime();

                // body 내의 <style ..></style>를 header로 이동
                $output = preg_replace_callback('!<style(.*?)<\/style>!is', array($this,'moveStyleToHeader'), $output);

                // 메타 파일 변경 (캐싱기능등으로 인해 위젯등에서 <!--Meta:경로--> 태그를 content에 넣는 경우가 있음
                $output = preg_replace_callback('/<!--Meta:([a-z0-9\_\/\.\@]+)-->/is', array($this,'transMeta'), $output);

                // rewrite module 사용시 생기는 상대경로에 대한 처리를 함
                if(Context::isAllowRewrite()) {
                    $url = parse_url(Context::getRequestUri());
                    $real_path = $url['path'];

                    $pattern = '/src=("|\'){1}(\.\/)?(files\/attach|files\/cache|files\/faceOff|files\/member_extra_info|modules|common|widgets|widgetstyle|layouts|addons)\/([^"\']+)\.(jpg|jpeg|png|gif)("|\'){1}/s';
                    $output = preg_replace($pattern, 'src=$1'.$real_path.'$3/$4.$5$6', $output);

					$pattern = '/href=("|\'){1}(\?[^"\']+)/s';
					$output = preg_replace($pattern, 'href=$1'.$real_path.'$2', $output);

                    if(Context::get('vid')) {
                        $pattern = '/\/'.Context::get('vid').'\?([^=]+)=/is';
                        $output = preg_replace($pattern, '/?$1=', $output);
                    }
                }

                // 간혹 background-image에 url(none) 때문에 request가 한번 더 일어나는 경우가 생기는 것을 방지
                $output = preg_replace('/url\((["\']?)none(["\']?)\)/is', 'none', $output);

                if(__DEBUG__==3) $GLOBALS['__trans_content_elapsed__'] = getMicroTime()-$start;

                // 불필요한 정보 제거
                $output = preg_replace('/member\_\-([0-9]+)/s','member_0',$output);

                // 최종 레이아웃 변환
                Context::set('content', $output);
                $output = $oTemplate->compile('./common/tpl', 'common_layout');

                // 사용자 정의 언어 변환
                $oModuleController = &getController('module');
                $oModuleController->replaceDefinedLangCode($output);
            }

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
        * @brief add given .css or .js file names in widget code to Context       
        * @param[in] $oModule the module object
        **/
        function transMeta($matches) {
            if(substr($matches[1],'-4')=='.css') Context::addCSSFile($matches[1]);
            elseif(substr($matches[1],'-3')=='.js') Context::addJSFile($matches[1]);
        }

        /**
        * @brief add html style code extracted from html body to Context, which will be
        * printed inside <header></header> later.
        * @param[in] $oModule the module object
        **/
        function moveStyleToHeader($matches) {
            Context::addHtmlHeader($matches[0]);
        }

       /**
        * @brief produce JSON compliant cotent given a module object.
        * @param[in] $oModule the module object
        **/
        function _toJSON(&$oModule) {
            $variables = $oModule->getVariables();
            $variables['error'] = $oModule->getError();
            $variables['message'] = $oModule->getMessage();
            $json = preg_replace("(\r\n|\n)",'\n',json_encode2($variables));
            return $json;
        }

        /**
        * @brief Produce virtualXML compliant content given a module object.\n
        * @param[in] $oModule the module object
        **/
        function _toVirtualXmlDoc(&$oModule) {
            $error = $oModule->getError();
            $message = $oModule->getMessage();
            $redirect_url = $oModule->get('redirect_url');
            $request_uri = Context::get('xeRequestURI');
            $request_url = Context::get('xeVirtualRequestUrl');
            if(substr($request_url,-1)!='/') $request_url .= '/';

            if($error === 0) {
                if($message != 'success') $output->message = $message;
                if($redirect_url) $output->url = $redirect_url;
                else $output->url = $request_uri;
            } else {
                if($message != 'fail') $output->message = $message;
            }

            $html = '<script type="text/javascript">'."\n";
            if($output->message) $html .= 'alert("'.$output->message.'");'."\n";
            if($output->url) {
                $url = preg_replace('/#(.+)$/i','',$output->url);
                $html .= 'self.location.href = "'.$request_url.'common/tpl/redirect.html?redirect_url='.urlencode($url).'";'."\n";
            }
            $html .= '</script>'."\n";
            return $html;
        }

        /**
        * @brief Produce XML compliant content given a module object.\n
        * @param[in] $oModule the module object
        **/
        function _toXmlDoc(&$oModule) {
            $variables = $oModule->getVariables();

            $xmlDoc  = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<response>\n";
            $xmlDoc .= sprintf("<error>%s</error>\n",$oModule->getError());
            $xmlDoc .= sprintf("<message>%s</message>\n",str_replace(array('<','>','&'),array('&lt;','&gt;','&amp;'),$oModule->getMessage()));

            $xmlDoc .= $this->_makeXmlDoc($variables);

            $xmlDoc .= "</response>";

            return $xmlDoc;
        }

       /**
        * @brief produce XML code given variable object\n
        * @param[in] $oModule the module object
        **/
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
        * @brief Produce HTML compliant content given a module object.\n
        * @param[in] $oModule the module object
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
