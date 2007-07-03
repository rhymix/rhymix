<?php
    /**
     * @class XmlJsFilter
     * @author zero (zero@nzeo.com)
     * @brief filter xml문서를 해석하여 js파일로 만듬
     * @version 0.1
     *
     * xml filter 파일은 js script로 컴파일 되어 캐싱됨
     * 
     * <filter name="js function 이름" act="서버에 요청할 action 이름" confirm_msg_code="submit시에 prompt로 물어볼 메세지의 코드" >
     *   <form> <-- 폼 항목의 체크
     *     <node target="name" required="true" minlength="1" maxlength="5" filter="email,userid,alpha,number" equalto="target" />
     *   </form>
     *   <parameter> <-- 폼 항목을 조합하여 key=val 의 js array로 return, act는 필수
     *     <param name="key" target="target" />
     *   </parameter>
     *   <response callback_func="callback 받게 될 js function 이름 지정" > <-- 서버에 ajax로 전송하여 받을 결과값
     *     <tag name="error" /> <-- error이름의 결과값을 받겠다는 것
     *   </response>
     * </filter>
     * 
     * - form - node
     *   target = 폼 element의 이름
     *   required = true/ false 꼭 있어야 하는지에 대한 체크
     *   minlength, maxlength = 최소/최대 길이
     *   filter = javascript로 체크하기 위한 체크 필터
     *   email : email의 형식 ( aaa.aaa@aaa.com)
     *   userid : 영문+숫자+_, 첫 글자는 영문, 소문자
     *   alpha : 영문값만 허용
     *   number : 숫자만 허용
     *   equalto = target , 현재 폼과 지정 target의 값이 동일해야 함
     * 
     * - parameter - param
     *   name = key : key를 이름으로 가지고 value의 값을 가지는 array 값 생성
     *   target = target_name : target form element의 값을 가져옴
     * 
     * - response
     *   tag = key : return받을 결과값의 변수명
     **/

    class XmlJsFilter extends XmlParser {
        var $compiled_path = './files/cache/js_filter_compiled/'; ///< 컴파일된 캐시 파일이 놓일 위치
        var $xml_file = NULL; ///< 대상 xml 파일
        var $js_file = NULL; ///< 컴파일된 js 파일

        /**
         * @brief constructor
         **/
        function XmlJsFilter($path, $xml_file) {
            $this->xml_file = sprintf("%s%s",$path, $xml_file);
            $this->js_file = $this->_getCompiledFileName($this->xml_file);
        }

        /**
         * @brief 원 xml파일과 compiled된js파일의 시간 비교 및 유무 비교등을 처리
         **/
        function compile() {
            if(!file_exists($this->xml_file)) return;
            if(!file_exists($this->js_file)) $this->_compile();
            else if(filectime($this->xml_file)>filectime($this->js_file)) $this->_compile();
            Context::addJsFile($this->js_file);
        }

        /**
         * @brief 실제 xml_file을 컴파일하여 js_file을 생성
         **/
        function _compile() {
            global $lang;

            // xml 파일을 읽음
            $buff = FileHandler::readFile($this->xml_file);

            // xml parsing
            $xml_obj = parent::parse($buff);

            // XmlJsFilter는 filter_name, field, parameter 3개의 데이터를 핸들링
            $filter_name = $xml_obj->filter->attrs->name;
            $confirm_msg_code = $xml_obj->filter->attrs->confirm_msg_code;
            $module = $xml_obj->filter->attrs->module;
            $act = $xml_obj->filter->attrs->act;
            $extend_filter = $xml_obj->filter->attrs->extend_filter;

            $field_node = $xml_obj->filter->form->node;
            if($field_node && !is_array($field_node)) $field_node = array($field_node);

            $parameter_param = $xml_obj->filter->parameter->param;
            if($parameter_param && !is_array($parameter_param)) $parameter_param = array($parameter_param);

            $response_tag = $xml_obj->filter->response->tag;
            if($response_tag && !is_array($response_tag)) $response_tag = array($response_tag);

            // extend_filter가 있을 경우 해당 method를 호출하여 결과를 받음
            if($extend_filter) {

                // extend_filter는 module.method 로 지칭되어 이를 분리
                list($module_name, $method) = explode('.',$extend_filter);

                // 모듈 이름과 method가 있을 경우 진행
                if($module_name&&$method) {
                    // 해당 module의 model 객체를 받음
                    $oExtendFilter = &getModel($module_name);

                    // method가 존재하면 실행
                    if(method_exists($oExtendFilter, $method)) {
                        // 결과를 받음
                        //$extend_filter_list = call_user_method($method, $oExtendFilter, true);
                        //$extend_filter_list = call_user_func(array($oExtendFilter, $method));
                        $extend_filter_list = $oExtendFilter->{$method}();
                        $extend_filter_count = count($extend_filter_list);

                        // 결과에서 lang값을 이용 문서 변수에 적용
                        for($i=0;$i<$extend_filter_count;$i++) {
                            $name = $extend_filter_list[$i]->name;
                            $lang_value = $extend_filter_list[$i]->lang;
                            if($lang_value) $lang->{$name} = $lang_value;
                        }
                    } 

                }
            }

            $callback_func = $xml_obj->filter->response->attrs->callback_func;
            if(!$callback_func) $callback_func = "filterAlertMessage";

            // 언어 입력을 위한 사용되는 필드 조사
            $target_list = array();
            $target_type_list = array();

            // js function 을 만들기 시작
            $js_doc  = sprintf("function %s(fo_obj) {\n", $filter_name);
            $js_doc .= sprintf("\tvar oFilter = new XmlJsFilter(fo_obj, \"%s\", \"%s\", %s);\n", $module, $act, $callback_func);

            // field, 즉 체크항목의 script 생성
            $node_count = count($field_node);
            if($node_count) {
                foreach($field_node as $key =>$node) {
                    $attrs = $node->attrs;
                    $target = trim($attrs->target);
                    if(!$target) continue;
                    $required = $attrs->required=='true'?'true':'false';
                    $minlength = $attrs->minlength>0?$attrs->minlength:'0';
                    $maxlength = $attrs->maxlength>0?$attrs->maxlength:'0';
                    $equalto = trim($attrs->equalto);
                    $filter = $attrs->filter;

                    $js_doc .= sprintf(
                        "\toFilter.addFieldItem(\"%s\",%s,%s,%s,\"%s\",\"%s\");\n",
                        $target, $required, $minlength, $maxlength, $equalto, $filter
                    );

                    if(!in_array($target, $target_list)) $target_list[] = $target;
                    if(!$target_type_list[$target]) $target_type_list[$target] = $filter;
                }
            }

            // extend_filter_item 체크
            for($i=0;$i<$extend_filter_count;$i++) {
                $filter_item = $extend_filter_list[$i];
                $target = trim($filter_item->name);
                if(!$target) continue;
                $type = $filter_item->type;
                $required = $filter_item->required?'true':'false';

                // extend filter item의 type으로 filter를 구함
                switch($type) {
                    case 'homepage' :
                            $filter = 'homepage';
                        break;
                    case 'email_address' :
                            $filter = 'email';
                        break;
                    default :
                            $filter = '';
                        break;
                }

                $js_doc .= sprintf(
                    "\toFilter.addFieldItem(\"%s\",%s,%s,%s,\"%s\",\"%s\");\n",
                    $target, $required, 0, 0, '', $filter
                );

                if(!in_array($target, $target_list)) $target_list[] = $target;
                if(!$target_type_list[$target]) $target_type_list[$target] = $type;

            }

            // 데이터를 만들기 위한 parameter script 생성
            $parameter_count = count($parameter_param);
            if($parameter_count) {
                // 기본 필터 내용의 parameter로 구성
                foreach($parameter_param as $key =>$param) {
                    $attrs = $param->attrs;
                    $name = trim($attrs->name);
                    $target = trim($attrs->target);
                    if(!$name || !$target) continue;
                    $target = htmlentities($target,ENT_QUOTES);

                    $js_doc .= sprintf(
                        "\toFilter.addParameterItem(\"%s\",\"%s\");\n",
                        $name, $target
                    );
                    if(!in_array($name, $target_list)) $target_list[] = $name;
                }

                // extend_filter_item 체크
                for($i=0;$i<$extend_filter_count;$i++) {
                    $filter_item = $extend_filter_list[$i];
                    $target = $name = trim($filter_item->name);
                    if(!$name || !$target) continue;
                    $target = htmlentities($target,ENT_QUOTES);

                    $js_doc .= sprintf(
                        "\toFilter.addParameterItem(\"%s\",\"%s\");\n",
                        $name, $target
                    );
                    if(!in_array($name, $target_list)) $target_list[] = $name;
                }
            }

            // response script 생성
            $response_count = count($response_tag);
            for($i=0;$i<$response_count;$i++) {
                $attrs = $response_tag[$i]->attrs;
                $name = $attrs->name;
                $js_doc .= sprintf("\toFilter.addResponseItem(\"%s\");\n", $name);
            }

            if($confirm_msg_code) $js_doc .= sprintf("\treturn oFilter.proc(\"%s\");\n",str_replace('"','\"',$lang->{$confirm_msg_code}));
            else $js_doc .= sprintf("\treturn oFilter.proc();\n");
            $js_doc .= "}\n";

            // form 필드 lang 값을 기록
            $target_count = count($target_list);
            for($i=0;$i<$target_count;$i++) {
                $target = $target_list[$i];
                if(!$lang->{$target}) $lang->{$target} = $target;
                $js_doc .= sprintf("alertMsg[\"%s\"] = \"%s\";\n", $target, str_replace("\"","\\\"",$lang->{$target}));
            }

            // target type을 기록
            $target_type_count = count($target_type_list);
            if($target_type_count) {
                foreach($target_type_list as $target => $type) {
                    $js_doc .= sprintf("target_type_list[\"%s\"] = \"%s\";\n", $target, $type);
                }
            }

            // 에러 메세지를 기록
            foreach($lang->filter as $key => $val) {
                if(!$val) $val = $key;
                $js_doc .= sprintf("alertMsg[\"%s\"] = \"%s\";\n", $key, str_replace("\"","\\\"",$val));
            }

            // js파일 생성
            FileHandler::writeFile($this->js_file, $js_doc);
        }

        /**
         * @brief $xml_file로 compiled_xml_file이름을 return
         **/
        function _getCompiledFileName($xml_file) {
            return sprintf('%s%s.%s.compiled.js',$this->compiled_path, md5($xml_file),Context::getLangType());
        }
    }
?>
