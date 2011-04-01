<?php
	/**
	 * @class XmlJsFilter
	 * @author NHN (developers@xpressengine.com)
	 * @brief filter class traslate xml content into javascript code
     * @version 0.2
	 *
	 * it convert xml code into js file and save the result as a cache file
     * @code 
     * {   
	 * <filter name="name of javascript funcion" act="action name" confirm_msg_code="message string to be prompted when submitting the form" >
	 *  <form> <-- code to validate data in the form
	 *    <node target="name" required="true" minlength="1" maxlength="5" filter="email,userid,alpha,number" equalto="target" />
	 *  </form>
	 *  <parameter> <-- 폼 항목을 조합하여 key=val 의 js array로 return, act는 필수
	 *    <param name="key" target="target" />
	 *  </parameter>
	 *  <response callback_func="callback 받게 될 js function 이름 지정" > <-- 서버에 ajax로 전송하여 받을 결과값
	 *    <tag name="error" /> <-- error이름의 결과값을 받겠다는 것
	 *  </response>
	 * </filter>
     * }
     *
	 * @detail {
     * - syntax description of <form> node
	 *  target = name of for element
	 *  required = flag indicating whether a field is mandatory or not
	 *  minlength, maxlength = mininum, maxinum length of string allowed for the field
	 *  filter = name of filter to be used for javascript validation. Following is the description of filter available
	 *      1) email : validate the confirmance of the value against an email format
	 *      2) userid : validate the confirmance of the value against the format of user id. (combination of number[0-9],alphabet(lower case) and '_', underscore starting with an alphatic character)
	 *      3) alpha : check if the value is consists of alphabatic characters.
	 *      4) number : check if the value is consists of numerical digits
	 *      5) equalto = target : indicate that values in the form should be equal to those in target
	 *      6) pattern_id/regex pattern/[i] : check the value using custom regular expression.
	 *
	 * - parameter - param
	 *  name = key : indicate that a new array, 'key' will be created and a value will be assigned to it
	 *  target = target_name : target form element의 값을 가져옴
	 *
	 * - response
	 *  tag = key : name of variable that will contain the result of the execution
     *  }
	 **/
                
	class XmlJsFilter extends XmlParser {
        var $version = '0.2.5';
		var $compiled_path = './files/cache/js_filter_compiled/'; ///< 컴파일된 캐시 파일이 놓일 위치
		var $xml_file = NULL; ///< 대상 xml 파일
		var $js_file = NULL; ///< 컴파일된 js 파일

		/**
		 * @brief constructor
		 **/
		function XmlJsFilter($path, $xml_file) {
			if(substr($path,-1)!=='/') $path .= '/';
			$this->xml_file = sprintf("%s%s",$path, $xml_file);
			$this->js_file = $this->_getCompiledFileName($this->xml_file);
		}

		/**
		 * @brief compile a xml_file only when a corresponding js file does not exists or is outdated
         * @return Returns NULL regardless of the success of failure of the operation
         **/
		function compile() {
			if(!file_exists($this->xml_file)) return;
			if(!file_exists($this->js_file)) $this->_compile();
			else if(filemtime($this->xml_file)>filemtime($this->js_file)) $this->_compile();
			Context::addJsFile($this->js_file, false, '',null,'body');
		}

		/**
		 * @brief compile a xml_file into js_file
		 **/
		function _compile() {
			global $lang;

			// xml 파일을 읽음
			$buff = FileHandler::readFile($this->xml_file);

			// xml parsing
			$xml_obj = parent::parse($buff);

			$attrs = $xml_obj->filter->attrs;
			$rules = $xml_obj->filter->rules;

			// XmlJsFilter는 filter_name, field, parameter 3개의 데이터를 핸들링
			$filter_name       = $attrs->name;
			$confirm_msg_code  = $attrs->confirm_msg_code;
			$module            = $attrs->module;
			$act               = $attrs->act;
			$extend_filter     = $attrs->extend_filter;
			

			$field_node = $xml_obj->filter->form->node;
			if($field_node && !is_array($field_node)) $field_node = array($field_node);

			$parameter_param = $xml_obj->filter->parameter->param;
			if($parameter_param && !is_array($parameter_param)) $parameter_param = array($parameter_param);

			$response_tag = $xml_obj->filter->response->tag;
			if($response_tag && !is_array($response_tag)) $response_tag = array($response_tag);

			// extend_filter가 있을 경우 해당 method를 호출하여 결과를 받음
			if($extend_filter) {

				// extend_filter가 있을 경우 캐시 사용을 못하도록 js 캐시 파일명을 변경
				$this->js_file .= '.nocache.js';

				// extend_filter는 module.method 로 지칭되어 이를 분리
				list($module_name, $method) = explode('.',$extend_filter);

				// 모듈 이름과 method가 있을 경우 진행
				if($module_name&&$method) {
					// 해당 module의 model 객체를 받음
					$oExtendFilter = &getModel($module_name);

					// method가 존재하면 실행
					if(method_exists($oExtendFilter, $method)) {
						// 결과를 받음
						$extend_filter_list  = $oExtendFilter->{$method}(true);
						$extend_filter_count = count($extend_filter_list);

						// 결과에서 lang값을 이용 문서 변수에 적용
						for($i=0; $i < $extend_filter_count; $i++) {
							$name = $extend_filter_list[$i]->name;
							$lang_value = $extend_filter_list[$i]->lang;
							if($lang_value) $lang->{$name} = $lang_value;
						}
					}

				}
			}

			// 언어 입력을 위한 사용되는 필드 조사
			$target_list      = array();
			$target_type_list = array();

			// javascript contents
			$js_rules       = array();
			$js_messages    = array();

			$fields = array();

			// create custom rule
			if ($rules && $rules->rule) {
				if (!is_array($rules->rule)) $rules->rule = array($rules->rule);
				foreach($rules->rule as $r) {
					if ($r->attrs->type == 'regex') {
						$js_rules[] = "v.cast('ADD_RULE', ['{$r->attrs->name}', {$r->body}]);";
					}
				}
			}

			// field, 즉 체크항목의 script 생성
			$node_count = count($field_node);
			if($node_count) {
				foreach($field_node as $key =>$node) {
					$attrs  = $node->attrs;
					$target = trim($attrs->target);

					if(!$target) continue;

					$rule    = trim($attrs->rule?$attrs->rule:$attrs->filter);
					$equalto = trim($attrs->equalto);

					$field = array();

					if($attrs->required == 'true') $field[] = 'required:true';
					if($attrs->minlength > 0)      $field[] = 'minlength:'.$attrs->minlength;
					if($attrs->maxlength > 0)      $field[] = 'maxlength:'.$attrs->maxlength;
					if($equalto) $field[] = "equalto:'{$attrs->equalto}'";
					if($rule)    $field[] = "rule:'{$rule}'";

					$fields[] = "'{$target}': {".implode(',', $field)."}";

					if(!in_array($target, $target_list)) $target_list[] = $target;
					if(!$target_type_list[$target]) $target_type_list[$target] = $filter;
				}
			}

			// extend_filter_item 체크
			$rule_types = array('homepage'=>'homepage', 'email_address'=>'email');
			
			for($i=0;$i<$extend_filter_count;$i++) {
				$filter_item = $extend_filter_list[$i];
				$target      = trim($filter_item->name);

				if(!$target) continue;

				// extend filter item의 type으로 rule을 구함
				$type  = $filter_item->type;
				$rule  = $rule_types[$type]?$rule_types[$type]:'';
				$required = ($filter_item->required == 'true');

				$field = array();
				if($required) $field[] = 'required:true';
				if($rule)     $field[] = "rule:'{$rule}'";
				$fields[] = "\t\t'{$target}' : {".implode(',', $field)."}";

				if(!in_array($target, $target_list)) $target_list[] = $target;
				if(!$target_type_list[$target]) $target_type_list[$target] = $type;
			}

			// 데이터를 만들기 위한 parameter script 생성
			$rename_params   = array();
			$parameter_count = count($parameter_param);
			if($parameter_count) {
				// 기본 필터 내용의 parameter로 구성
				foreach($parameter_param as $key =>$param) {
					$attrs  = $param->attrs;
					$name   = trim($attrs->name);
					$target = trim($attrs->target);

					//if($name && $target && ($name != $target)) $js_doc[] = "\t\tparams['{$name}'] = params['{$target}']; delete params['{$target}'];";
					if($name && $target && ($name != $target))  $rename_params[] = "'{$target}':'{$name}'";
					if($name && !in_array($name, $target_list)) $target_list[] = $name;
				}

				// extend_filter_item 체크
				for($i=0;$i<$extend_filter_count;$i++) {
					$filter_item = $extend_filter_list[$i];
					$target = $name = trim($filter_item->name);
					if(!$name || !$target) continue;

					if(!in_array($name, $target_list)) $target_list[] = $name;
				}
			}

			// response script 생성
			$response_count = count($response_tag);
			$responses = array();
			for($i=0;$i<$response_count;$i++) {
				$attrs = $response_tag[$i]->attrs;
				$name = $attrs->name;
				$responses[] = "'{$name}'";
			}

			// lang : form field description
			$target_count = count($target_list);
			for($i=0;$i<$target_count;$i++) {
				$target = $target_list[$i];
				if(!$lang->{$target}) $lang->{$target} = $target;
				$js_messages[] = sprintf("v.cast('ADD_MESSAGE',['%s','%s']);", $target, addslashes($lang->{$target}));
			}

			// target type을 기록
			/*
			$target_type_count = count($target_type_list);
			if($target_type_count) {
				foreach($target_type_list as $target => $type) {
					//$js_doc .= sprintf("target_type_list[\"%s\"] = \"%s\";\n", $target, $type);
				}
			}
			*/

			// lang : error message
			foreach($lang->filter as $key => $val) {
				if(!$val) $val = $key;
				$js_messages[] = sprintf("v.cast('ADD_MESSAGE',['%s','%s']);", $key, $val);
			}

			$callback_func = $xml_obj->filter->response->attrs->callback_func;
			if(!$callback_func) $callback_func = "filterAlertMessage";

			$confirm_msg = '';
			if ($confirm_msg_code) $confirm_msg = $lang->{$confirm_msg_code};
			
			$jsdoc   = array();
			$jsdoc[] = "function {$filter_name}(form){ return legacy_filter('{$filter_name}', form, '{$module}', '{$act}', {$callback_func}, [".implode(',', $responses)."], '".addslashes($confirm_msg)."', {".implode(',', $rename_params)."}) };";
			$jsdoc[] = '(function($){';
			$jsdoc[] = "\tvar v=xe.getApp('validator')[0];if(!v)return false;";
			$jsdoc[] = "\t".'v.cast("ADD_FILTER", ["'.$filter_name.'", {'.implode(',', $fields).'}]);';
			$jsdoc[] = "\t".implode("\n\t", $js_rules);
			$jsdoc[] = "\t".implode("\n\t", $js_messages);
			$jsdoc[] = '})(jQuery);';
			$jsdoc   = implode("\n", $jsdoc);

			// js파일 생성
			FileHandler::writeFile($this->js_file, $jsdoc);
		}

		/**
		 * @brief return a file name of js file corresponding to the xml file
		 **/
		function _getCompiledFileName($xml_file) {
			return sprintf('%s%s.%s.compiled.js',$this->compiled_path, md5($this->version.$xml_file),Context::getLangType());
		}
	}
?>
