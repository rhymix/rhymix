<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

/**
 * filter class traslate xml content into javascript code
 *
 * it convert xml code into js file and save the result as a cache file
 * @code
 * <pre>{
 * <filter name="name of javascript funcion" act="action name" confirm_msg_code="message string to be prompted when submitting the form" >
 *  <form> <-- code to validate data in the form
 *    <node target="name" required="true" minlength="1" maxlength="5" filter="email,userid,alpha,number" equalto="target" />
 *  </form>
 * <parameter> "- A form of key = val combination of items to js array return, act required
 *    <param name="key" target="target" />
 *  </parameter>
 * <response callback_func="specifying the name of js function to callback" > "- Result to get by sending ajax to the server
 * <tag name="error" /> <- get the result of error name
 *  </response>
 * </filter>
 * }</pre>
 *
 * @detail
 * <pre>{
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
 * target = target_name: get the value of the target form element
 *
 * - response
 *  tag = key : name of variable that will contain the result of the execution
 * }</pre>
 * @class XmlJsFilter
 * @author NAVER (developers@xpressengine.com)
 * @package /classes/xml
 * @version 0.2
 */
class XmlJsFilter extends XeXmlParser
{

	/**
	 * version
	 * @var string
	 */
	var $version = '0.2.5';

	/**
	 * compiled javascript cache path
	 * @var string
	 */
	var $compiled_path = './files/cache/js_filter_compiled/'; // / directory path for compiled cache file
	/**
	 * Target xml file
	 * @var string
	 */
	var $xml_file = NULL;

	/**
	 * Compiled js file
	 * @var string
	 */
	var $js_file = NULL; // / 

	/**
	 * constructor
	 * @param string $path
	 * @param string $xml_file
	 * @return void
	 */

	function __construct($path, $xml_file)
	{
		if(substr($path, -1) !== '/')
		{
			$path .= '/';
		}
		$this->xml_file = sprintf("%s%s", $path, $xml_file);
		$this->js_file = $this->_getCompiledFileName($this->xml_file);
	}

	/**
	 * Compile a xml_file only when a corresponding js file does not exists or is outdated
	 * @return void Returns NULL regardless of the success of failure of the operation
	 */
	function compile()
	{
		if(!file_exists($this->xml_file))
		{
			return;
		}
		if(!file_exists($this->js_file))
		{
			$this->_compile();
		}
		else if(filemtime($this->xml_file) > filemtime($this->js_file))
		{
			$this->_compile();
		}
		Context::loadFile(array($this->js_file, 'body', '', null));
	}

	/**
	 * compile a xml_file into js_file
	 * @return void
	 */
	function _compile()
	{
		global $lang;

		// read xml file
		$buff = FileHandler::readFile($this->xml_file);

		// xml parsing
		$xml_obj = parent::parse($buff);

		$attrs = $xml_obj->filter->attrs;
		$rules = $xml_obj->filter->rules;

		// XmlJsFilter handles three data; filter_name, field, and parameter
		$filter_name = $attrs->name;
		$confirm_msg_code = $attrs->confirm_msg_code ?? null;
		$module = $attrs->module;
		$act = $attrs->act;
		$extend_filter = $attrs->extend_filter ?? null;

		$field_node = $xml_obj->filter->form->node ?? null;
		if($field_node && !is_array($field_node))
		{
			$field_node = array($field_node);
		}

		$parameter_param = $xml_obj->filter->parameter->param ?? null;
		if($parameter_param && !is_array($parameter_param))
		{
			$parameter_param = array($parameter_param);
		}

		$response_tag = $xml_obj->filter->response->tag ?? null;
		if($response_tag && !is_array($response_tag))
		{
			$response_tag = array($response_tag);
		}

		// If extend_filter exists, result returned by calling the method
		$extend_filter_count = 0;
		if($extend_filter)
		{
			// If extend_filter exists, it changes the name of cache not to use cache
			$this->js_file .= '.nocache.js';

			// Separate the extend_filter
			list($module_name, $method) = explode('.', $extend_filter);

			// contibue if both module_name and methos exist.
			if($module_name && $method)
			{
				// get model object of the module
				$oExtendFilter = getModel($module_name);

				// execute if method exists
				if(method_exists($oExtendFilter, $method))
				{
					// get the result
					$extend_filter_list = $oExtendFilter->{$method}(TRUE);
					$extend_filter_count = count($extend_filter_list);

					// apply lang_value from the result to the variable
					for($i = 0; $i < $extend_filter_count; $i++)
					{
						$name = $extend_filter_list[$i]->name;
						$lang_value = $extend_filter_list[$i]->lang;
						if($lang_value)
						{
							$lang->{$name} = $lang_value;
						}
					}
				}
			}
		}

		// search the field to be used for entering language
		$target_list = array();
		$target_type_list = array();

		// javascript contents
		$js_rules = array();
		$js_messages = array();

		$fields = array();

		// create custom rule
		if($rules && $rules->rule)
		{
			if(!is_array($rules->rule))
			{
				$rules->rule = array($rules->rule);
			}
			foreach($rules->rule as $r)
			{
				if($r->attrs->type == 'regex')
				{
					$js_rules[] = "v.cast('ADD_RULE', ['{$r->attrs->name}', {$r->body}]);";
				}
			}
		}

		// generates a field, which is a script of the checked item
		$node_count = countobj($field_node);
		if($node_count)
		{
			foreach($field_node as $key => $node)
			{
				$attrs = $node->attrs;
				$target = trim($attrs->target);

				if(!$target)
				{
					continue;
				}

				$rule = trim($attrs->rule ? $attrs->rule : $attrs->filter);
				$equalto = trim($attrs->equalto);

				$field = array();

				if($attrs->required == 'true')
				{
					$field[] = 'required:true';
				}
				if($attrs->minlength > 0)
				{
					$field[] = 'minlength:' . $attrs->minlength;
				}
				if($attrs->maxlength > 0)
				{
					$field[] = 'maxlength:' . $attrs->maxlength;
				}
				if($equalto)
				{
					$field[] = "equalto:'{$attrs->equalto}'";
				}
				if($rule)
				{
					$field[] = "rule:'{$rule}'";
				}

				$fields[] = "'{$target}': {" . implode(',', $field) . "}";

				if(!in_array($target, $target_list))
				{
					$target_list[] = $target;
				}
				if(!isset($target_type_list[$target]))
				{
					$target_type_list[$target] = $filter ?? null;
				}
			}
		}

		// Check extend_filter_item
		$rule_types = array('homepage' => 'homepage', 'email_address' => 'email');

		for($i = 0; $i < $extend_filter_count; $i++)
		{
			$filter_item = $extend_filter_list[$i];
			$target = trim($filter_item->name);

			if(!$target)
			{
				continue;
			}

			// get the filter from the type of extend filter item
			$type = $filter_item->type;
			$rule = $rule_types[$type] ? $rule_types[$type] : '';
			$required = ($filter_item->required == 'true');

			$field = array();
			if($required)
			{
				$field[] = 'required:true';
			}
			if($rule)
			{
				$field[] = "rule:'{$rule}'";
			}
			$fields[] = "\t\t'{$target}' : {" . implode(',', $field) . "}";

			if(!in_array($target, $target_list))
			{
				$target_list[] = $target;
			}
			if(!$target_type_list[$target])
			{
				$target_type_list[$target] = $type;
			}
		}

		// generates parameter script to create dbata
		$rename_params = array();
		$parameter_count = countobj($parameter_param);
		if($parameter_count)
		{
			// contains parameter of the default filter contents
			foreach($parameter_param as $key => $param)
			{
				$attrs = $param->attrs;
				$name = trim($attrs->name);
				$target = trim($attrs->target);

				//if($name && $target && ($name != $target)) $js_doc[] = "\t\tparams['{$name}'] = params['{$target}']; delete params['{$target}'];";
				if($name && $target && ($name != $target))
				{
					$rename_params[] = "'{$target}':'{$name}'";
				}
				if($name && !in_array($name, $target_list))
				{
					$target_list[] = $name;
				}
			}

			// Check extend_filter_item
			for($i = 0; $i < $extend_filter_count; $i++)
			{
				$filter_item = $extend_filter_list[$i];
				$target = $name = trim($filter_item->name);
				if(!$name || !$target)
				{
					continue;
				}

				if(!in_array($name, $target_list))
				{
					$target_list[] = $name;
				}
			}
		}

		// generates the response script
		$responses = array();
		foreach ($response_tag ?: [] as $val)
		{
			$name = $val->attrs->name;
			$responses[] = "'{$name}'";
		}

		// writes lang values of the form field
		foreach ($target_list ?: [] as $target)
		{
			if(!$lang->{$target})
			{
				$lang->{$target} = $target;
			}
			$text = preg_replace('@\r?\n@', '\\n', addslashes($lang->{$target}));
			$js_messages[] = "v.cast('ADD_MESSAGE',['{$target}','{$text}']);";
		}

		// writes the target type
		/*
		  $target_type_count = count($target_type_list);
		  if($target_type_count) {
		  foreach($target_type_list as $target => $type) {
		  //$js_doc .= sprintf("target_type_list[\"%s\"] = \"%s\";\n", $target, $type);
		  }
		  }
		 */

		// writes error messages
		foreach($lang->filter as $key => $val)
		{
			if(!$val)
			{
				$val = $key;
			}
			$val = preg_replace('@\r?\n@', '\\n', addslashes($val));
			$js_messages[] = sprintf("v.cast('ADD_MESSAGE',['%s','%s']);", $key, $val);
		}

		$callback_func = $xml_obj->filter->response->attrs->callback_func;
		if(!$callback_func)
		{
			$callback_func = "filterAlertMessage";
		}

		$confirm_msg = '';
		if($confirm_msg_code)
		{
			$confirm_msg = $lang->{$confirm_msg_code};
		}

		$jsdoc = array();
		$jsdoc[] = "function {$filter_name}(form){ return legacy_filter('{$filter_name}', form, '{$module}', '{$act}', {$callback_func}, [" . implode(',', $responses) . "], '" . addslashes($confirm_msg) . "', {" . implode(',', $rename_params) . "}) };";
		$jsdoc[] = '(function($){';
		$jsdoc[] = "\tvar v=xe.getApp('validator')[0];if(!v)return false;";
		$jsdoc[] = "\t" . 'v.cast("ADD_FILTER", ["' . $filter_name . '", {' . implode(',', $fields) . '}]);';
		$jsdoc[] = "\t" . implode("\n\t", $js_rules);
		$jsdoc[] = "\t" . implode("\n\t", $js_messages);
		$jsdoc[] = '})(jQuery);';
		$jsdoc = implode("\n", $jsdoc);

		// generates the js file
		FileHandler::writeFile($this->js_file, $jsdoc);
	}

	/**
	 * return a file name of js file corresponding to the xml file
	 * @param string $xml_file
	 * @return string
	 */
	function _getCompiledFileName($xml_file)
	{
		return sprintf('%s%s.%s.compiled.js', $this->compiled_path, md5($this->version . $xml_file), Context::getLangType());
	}

}
/* End of file XmlJsFilter.class.php */
/* Location: ./classes/xml/XmlJsFilter.class.php */
