<?php
    /**
    * @class XmlJsFilter
    * @author zero (zero@nzeo.com)
    * @brief filter xml문서를 해석하여 js파일로 만듬
    *
    * filter xml은 tpl 파일과 같은 위치에 있어야 함
    *           <filter id='js function 이름'>
    *            <field> <-- 폼 항목의 체크
    *              <item target="name" required="true" minlength="1" maxlength="5" 
    *                    filter="email,userid,alpha,number" reualto="target" />
    *            </field>
    *            <parameter> <-- 폼 항목을 조합하여 key=val 의 js array로 return, act는 필수
    *              <item param="key" value="target" concat="'@',target2..." />
    *            </parameter>
    *            <response> <-- 서버에 ajax로 전송하여 받을 결과값
    *              <item name="error" /> <-- error이름의 결과값을 받겠다는 것
    *            </response>
    *           </filter>
    *
    *           - field
    *             target = 폼 element의 이름
    *             required = true/ false 꼭 있어야 하는지에 대한 체크
    *             minlength, maxlength = 최소/최대 길이
    *             filter = javascript로 체크하기 위한 체크 필터
    *                      email : email의 형식 ( aaa.aaa@aaa.com)
    *                      userid : 영문+숫자+_, 첫 글자는 영문, 소문자
    *                      alpha : 영문값만 허용
    *                      number : 숫자만 허용
    *             equalto = target , 현재 폼과 지정 target의 값이 동일해야 함
    *           - parameter
    *             param = key : key를 이름으로 가지고 value의 값을 가지는 array 값 생성
    *             value = target : target form element의 값을 가져옴
    *             concat = str1,str2,target2... : 값들의 string 또는 form element value를 연결
    *            - response
    *              name = key : return받을 결과값의 변수명
    **/

    class XmlJsFilter {
        var $compiled_path = './files/js_filter_compiled/'; ///< 컴파일된 캐시 파일이 놓일 위치
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
            if(filectime($this->xml_file)>filectime($this->js_file)) $this->_compile();
            $this->_compile();
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
            $oXml = new XmlParser();
            $xml_obj = $oXml->parse($buff);

            // XmlJsFilter는 filter_id, field, parameter 3개의 데이터를 핸들링
            $filter_id = $xml_obj->filter->attrs->id;
            $confirm_msg_code = $xml_obj->filter->attrs->confirm_msg_code;
            $field_item = $xml_obj->filter->field->item;
            $parameter_item = $xml_obj->filter->parameter->item;
            $response_item = $xml_obj->filter->response->item;

            // 언어 입력을 위한 사용되는 필드 조사
            $target_list = array();

            // js function 을 만들기 시작
            $js_doc  = sprintf("function %s(fo_obj, callback_user_func) {\n", $filter_id);
            $js_doc .= "\tvar oFilter = new XmlJsFilter(fo_obj, callback_user_func);\n";

            // field, 즉 체크항목의 script 생성
            $field_cnt = count($field_item);
            if($field_cnt) {
                foreach($field_item as $key =>$field_obj) {
                    $attrs = $field_obj->attrs;
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
                }
            }

            // 데이터를 만들기 위한 parameter script 생성
            $parameter_cnt = count($parameter_item);
            if($parameter_cnt) {
                foreach($parameter_item as $key =>$parameter_obj) {
                    $attrs = $parameter_obj->attrs;
                    $param = trim($attrs->param);
                    $target = trim($attrs->target);
                    if(!$param || !$target) continue;
                    $target = htmlentities($target,ENT_QUOTES);
                    $js_doc .= sprintf(
                    "\toFilter.addParameterItem(\"%s\",\"%s\");\n",
                    $param, $target
                    );
                    if(!in_array($param, $target_list)) $target_list[] = $param;
                }
            }

            // response script 생성
            $response_cnt = count($response_item);
            for($i=0;$i<$response_cnt;$i++) {
                $attrs = $response_item[$i]->attrs;
                $name = $attrs->name;
                $js_doc .= sprintf("\toFilter.addResponseItem(\"%s\");\n", $name);
            }

            if($confirm_msg_code) $js_doc .= sprintf("\treturn oFilter.proc(\"%s\");\n",str_replace('"','\"',$lang->{$confirm_msg_code}));
            else $js_doc .= sprintf("\treturn oFilter.proc();\n");
            $js_doc .= "}\n";

            // form 필드 lang 값을 기록
            $target_cnt = count($target_list);
            for($i=0;$i<$target_cnt;$i++) {
                $target = $target_list[$i];
                $js_doc .= sprintf("alertMsg[\"%s\"] = \"%s\"\n", $target, str_replace("\"","\\\"",$lang->{$target}));
            }

            // 에러 메세지를 기록
            foreach($lang->filter as $key => $val) {
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
