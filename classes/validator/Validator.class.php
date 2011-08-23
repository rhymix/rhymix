<?php
/**
 * Validator class
 */
class Validator
{
	var $_cache_dir = '';
	var $_last_error;
	var $_xml_ruleset = null;
	var $_rules;
	var $_filters;
	var $_has_mb_func;
	var $_version = '1.0';
	var $_xml_path = '';

	/**
	 * @constructor
	 */
	function Validator($xml_path=''){
		$this->_rules   = array();
		$this->_filters = array();
		$this->_xml_ruleset = null;

		if($xml_path) $this->load($xml_path);
		
		// predefined rules
		$this->addRule(array(
			'email'        => '/^[\w-]+(\.[\w-]+)*@[\w-]+(\.[\w-]+)+$/',
			'userid'       => '/^[a-z]+[\w-]*[a-z0-9_]+$/i',
			'url'          => '/^(https?|ftp|mms):\/\/[0-9a-z-]+(\.[_0-9a-z-]+)+(:\d+)?/',
			'alpha'        => '/^[a-z]*$/i',
			'alpha_number' => '/^[a-z][a-z0-9_]*$/i',
			'number'       => '/^(?:[1-9]\\d*|0)$/'
		));

		$this->_has_mb_func = is_callable('mb_strlen');
		$this->setCacheDir('./files/cache');
	}

	/**
	 * Load a xml file
	 * @param[in] string $xml_path A file name to be loaded
	 */
	function load($xml_path) {
		$this->_xml_ruleset = null;

		$xml_path = realpath($xml_path);
		if(!is_readable($xml_path)) return false;

		$parser = new XmlParser();
		$xml = $parser->loadXmlFile($xml_path);
		if(!isset($xml->ruleset) || !isset($xml->ruleset->fields) || !isset($xml->ruleset->fields->field)) return false;

		// custom rules
		if(isset($xml->ruleset->customrules) && isset($xml->ruleset->customrules->rule)) {
			$customrules = $xml->ruleset->customrules->rule;
			if(!is_array($customrules)) $customrules = array($customrules);

			$rules = array();
			foreach($customrules as $rule) {
				if(!isset($rule->attrs) || !isset($rule->attrs->name)) continue;

				$rule = (array)$rule->attrs;
				$name = $rule['name'];
				unset($rule['name']);

				$rules[$name] = $rule;
			}
			if(count($rules)) $this->addRule($rules);
		}

		// filters
		$fields = $xml->ruleset->fields->field;
		if(!is_array($fields)) $fields = array($fields);

		$filters = array();
		foreach($fields as $field) {
			$name   = '';
			$filter = array();

			if(!isset($field->attrs) || !isset($field->attrs->name)) continue;
			$filter = (array)$field->attrs;

			$name = $filter['name'];
			unset($filter['name']);

			// conditional statement
			if(isset($field->if)) {
				$if = $field->if;
				if(!is_array($if)) $if = array($if);
				foreach($if as $idx=>$cond) {
					$if[$idx] = (array)$cond->attrs;
				}
				$filter['if'] = $if;
			}

			$filters[$name] = $filter;
		}

		$this->_xml_ruleset = $xml->ruleset;
		$this->_filters  = $filters;
		$this->_xml_path = $xml_path;

		return true;
	}

	/**
	 * Set root cache directory
	 * @param[in] string $cache_dir Root cache directory
	 */
	function setCacheDir($cache_dir){
		if(is_dir($cache_dir)) {
			$this->_cache_dir = preg_replace('@/$@', '', $cache_dir);
		}
	}

	/**
	 * Validate the fields. If the fields aren't passed, validation will be execute on the Context variables.
	 * @param[in] (optional) array $fields Target fields. The keys of the array represents field's name, its values represents field's value.
	 * @return bool True if it is valid, FALSE otherwise.
	 */
	function validate($fields_=array()){
		if(is_array($fields_)) {
			$fields = $fields_;
		} else {
			$args   = array_keys($this->_filters);
			$fields = (array)Context::getRequestVars();
		}

		if(!is_array($fields) || !count($fields)) return true;

		$filter_default = array(
			'required'  => 'false',
			'default'   => '',
			'modifiers' => array(),
			'length'    => 0,
			'equalto'   => 0,
			'rule'      => 0,
			'if'        => array()
		);

		$fields = array_map('trim', $fields);

		foreach($this->_filters as $key=>$filter) {
			$exists = array_key_exists($key, $fields);
			$filter = array_merge($filter_default, $filter);
			$value  = $exists ? $fields[$key] : null;

			// conditional statement
			foreach($filter['if'] as $cond) {
				if(!isset($cond['test']) || !isset($cond['attr'])) continue;

				$func_body = preg_replace('/\\$(\w+)/', '$c[\'$1\']', $cond['test']);
				$func = create_function('$c', "return !!({$func_body});");

				if($func($fields)) $filter[$cond['attr']] = $cond['value'];
			}

			// attr : default
			if(!$value && strlen($default=trim($filter['default']))) {
				$value = $default;
				if(is_null($fields_)) Context::set($key, $value);
				else $fields_[$key] = $value;
			}
			$value_len = strlen($value);

			// attr : modifier
			if(is_string($modifiers=$filter['modifiers'])) $modifiers = explode(',', trim($modifiers));

			// attr : required
			if($filter['required'] === 'true' && !$value_len) return $this->error($key, 'isnull');

			// if the field wasn't passed, ignore this value
			if(!$exists && !$value_len) continue;

			// attr : length
			if($length=$filter['length']){
				list($min, $max) = explode(':', trim($length));
				$is_min_b = (substr($min, -1) === 'b');
				$is_max_b = (substr($max, -1) === 'b');
				list($min, $max) = array((int)$min, (int)$max);

				$strbytes = strlen($value);
				if(!$is_min_b || !$is_max_b){
					$strlength = $this->_has_mb_func?mb_strlen($value,'utf-8'):$this->mbStrLen($value);
				}

				if(($min && $min > ($is_min_b?$strbytes:$strlength)) || ($max && $max < ($is_max_b?$strbytes:$strlength))) return $this->error($key, 'outofrange');
			}

			// equalto
			if($equalto=$filter['equalto']){
				if(!array_key_exists($equalto, $fields) || trim($fields[$equalto]) !== $value) return $this->error($key, 'equalto');
			}

			// rules
			if($rules=$filter['rule']){
				$rules = explode(',', $rules);
				foreach($rules as $rule) {
					$result = $this->applyRule($rule, $value);
					// apply the 'not' modifier
					if(in_array('not', $modifiers)) $result = !$result;
					if(!$result) return $this->error($key, 'invalid_'.$rule);
				}
			}
		}

		return true;
	}

	/**
	 * Log an error
	 * @param[in] $msg error message
	 * @return always false
	 */
	function error($field, $msg){
		$lang_filter = Context::getLang('filter');
		$msg = isset($lang_filter->{$msg})?$lang_filter->{$msg}:$lang_filter->invalid;
		$msg = sprintf($msg, Context::getLang($field));

		$this->_last_error = array('field'=>$field, 'msg'=>$msg);

		return false;
	}

	/**
	 * Returns the last error infomation including a field name and an error message.
	 * @return array The last error infomation
	 */
	function getLastError(){
		return $this->_last_error;
	}

	/**
	 * Add a new rule
	 * @param[in] string $name rule name
	 * @param[in] mixed $rule
	 */
	function addRule($name, $rule=''){
		if(is_array($name)) $args = $name;
		else $args = array($name=>$rule);

		foreach($args as $name=>$rule){
			if(!$rule) continue;
			if(is_string($rule)) $rule = array('type'=>'regex', 'test'=>$rule);

			if($rule['type'] == 'enum') {
				$delim = isset($rule['delim'])?$rule['delim']:',';
				$rule['test'] = explode($delim, $rule['test']);
			}

			$this->_rules[$name] = $rule;
		}
	}

	/**
	 * Remove a rule
	 * @param[in] string $name rule name
	 */
	function removeRule($name){
		unset($this->_rules[$name]);
	}

	function addFilter($name, $filter='') {
		if(is_array($name)) $args = $name;
		else $args = array($name=>$filter);

		foreach($args as $name=>$filter) {
			if(!$filter) continue;

			if(isset($filter['if'])) {
				if(is_array($filter['if']) && count($filter['if'])) {
					$key = key($filter['if']);
					if(!is_int($key)) $filter['if'] = array($filter['if']);
				} else {
					unset($filter['if']);
				}
			}
			
			$this->_filters[$name] = $filter;
		}
	}

	function removeFilter($name) {
		unset($this->_filters[$name]);
	}

	/**
	 * Find whether the field is valid with the rule
	 * @param[in] string $name rule name
	 * @param[in] string $value a value to be validated
	 * @return bool TRUE if the field is valid, FALSE otherwise.
	 */
	function applyRule($name, $value){
		$rule = $this->_rules[$name];

		switch($rule['type']) {
			case 'regex':
				return (preg_match($rule['test'], $value) > 0);
			case 'enum':
				return in_array($value, $rule['test']);
			case 'expr':
				if(!$rule['func_test']) {
					$rule['func_test'] = create_function('$a', 'return ('.preg_replace('/\$\$/', '$a', $rule['test']).');');
				}
				return $rule['func_test']($value);
		}

		return true;
	}

	/**
	 * Return 
	 */
	function mbStrLen($str){
		$arr = count_chars($str);
		for($i=0x80; $i < 0xc0; $i++) {
			unset($arr[$i]);
		}
		return array_sum($arr);
	}

	/**
	 * Returns compiled javascript file path. The path begins from XE root directory.
	 * @return string Compiled JavaScript file path
	 */
	function getJsPath(){
		if(!$this->_cache_dir) return false;

		$dir = $this->_cache_dir.'/ruleset';
		if(!is_dir($dir) && !mkdir($dir)) return false;
		if(!$this->_xml_path) return false;

		$filepath = $dir.'/'.md5($this->_version.' '.$this->_xml_path).'.js';
		if(is_readable($filepath) && filemtime($filepath) > filemtime($this->_xml_path)) return $filepath;

		$content = $this->_compile2js();
		if($content === false) return false;

		if(is_callable('file_put_contents')) {
			@file_put_contents($filepath, $content);
		} else {
			$fp = @fopen($filepath, 'w');
			if(is_resource($fp)) {
				fwrite($fp, $content);
				fclose($fp);
			}
		}

		return $filepath;
	}

	/**
	 * Compile a ruleset to a javascript file
	 * @private
	 */
	function _compile2js() {
		$ruleset = basename($this->_xml_path,'.xml');
		$content = array();

		if(preg_match('@(^|/)files/ruleset/\w+\.xml$@i')) $ruleset = '@'.$ruleset;

		// custom rulesets
		foreach($this->_rules as $name=>$rule) {
			if(strpos('email,userid,url,alpha,alpha_number,number,', $name.',') !== false) continue;
			switch($rule['type']) {
				case 'regex':
					$content[] = "v.cast('ADD_RULE', ['{$name}', {$rule['test']}]);";
					break;
				case 'enum':
					$enums = '"'.implode('","', $rule['test']).'"';
					$content[] = "v.cast('ADD_RULE', ['{$name}', function($$){ return ($.inArray($$,[{$enums}]) > -1); }]);";
					break;
				case 'expr':
					$content[] = "v.cast('ADD_RULE', ['{$name}', function($$){ return ({$rule['test']}); }]);";
					break;
			}
		}

		// filters
		foreach($this->_filters as $name=>$filter) {
			$field = array();

			if($filter['required'] == 'true') $field[] = 'required:true';
			if($filter['rule'])     $field[] = "rule:'{$filter['rule']}'";
			if($filter['default'])  $field[] = "default:'{$filter['default']}'";
			if($filter['modifier']) $field[] = "modifier:'{$filter['modifier']}'";
			if($filter['length']) {
				list($min, $max) = explode(':', $filter['length']);
				if($min) $field[] = "minlength:'{$min}'";
				if($max) $field[] = "maxlength:'{$max}'";
			}
			if($filter['if']) {
				$ifs = array();
				if(!isset($filter['if'][0])) $filter['if'] = array($filter['if']);
				foreach($filter['if'] as $if) {
					$ifs[] = "{test:'".addslashes($if['test'])."', attr:'{$if['attr']}', value:'".addslashes($if['value'])."'}";
				}
				$field[] = "'if':[".implode(',', $ifs)."]";
			}
			if(count($field)) {
				$field = '{'.implode(',', $field).'}';
				$content[] = "'{$name}':{$field}";
			}
		}

		if(count($content)) {
			$content = implode(',', $content);

			return "(function($,v){\nv=xe.getApp('validator')[0];if(!v)return;\nv.cast('ADD_FILTER',['{$ruleset}', {{$content}}]);})(jQuery);";
		} else {
			return '';
		}
	}
}

/* End of file Validator.class.php */
/* Location: ./classes/validator/Validator.class.php */
