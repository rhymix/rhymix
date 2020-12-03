<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

/**
 * A class to handle extra variables used in posts, member and others
 *
 * @author NAVER (developers@xpressengine.com)
 */
class ExtraVar
{

	/**
	 * sequence of module
	 * @var int
	 */
	var $module_srl = null;

	/**
	 * Current module's Set of ExtraItem
	 * @var ExtraItem[]
	 */
	var $keys = array();

	/**
	 * Get instance of ExtraVar (singleton)
	 *
	 * @param int $module_srl Sequence of module
	 * @return ExtraVar
	 */
	public static function getInstance($module_srl)
	{
		return new ExtraVar($module_srl);
	}

	/**
	 * Constructor
	 *
	 * @param int $module_srl Sequence of module
	 * @return void
	 */
	function __construct($module_srl)
	{
		$this->module_srl = $module_srl;
	}

	/**
	 * Register a key of extra variable
	 * 
	 * @param object[] $extra_keys Array of extra variable. A value of array is object that contains module_srl, idx, name, default, desc, is_required, search, value, eid.
	 * @return void
	 */
	function setExtraVarKeys($extra_keys)
	{
		if(!is_array($extra_keys) || count($extra_keys) < 1)
		{
			return;
		}

		foreach($extra_keys as $val)
		{
			$obj = new ExtraItem($val->module_srl, $val->idx, $val->name, $val->type, $val->default, $val->desc, $val->is_required, $val->search, $val->value, $val->eid);
			$this->keys[$val->idx] = $obj;
		}
	}

	/**
	 * Returns an array of ExtraItem
	 *
	 * @return ExtraItem[]
	 */
	function getExtraVars()
	{
		return $this->keys;
	}

}

/**
 * Each value of the extra vars
 *
 * @author NAVER (developers@xpressengine.com)
 */
class ExtraItem
{

	/**
	 * Sequence of module
	 * @var int
	 */
	var $module_srl = 0;

	/**
	 * Index of extra variable
	 * @var int
	 */
	var $idx = 0;

	/**
	 * Name of extra variable
	 * @var string
	 */
	var $name = 0;

	/**
	 * Type of extra variable
	 * @var string text, homepage, email_address, tel, textarea, checkbox, date, select, radio, kr_zip
	 */
	var $type = 'text';

	/**
	 * Default values
	 * @var string[]
	 */
	var $default = null;

	/**
	 * Description
	 * @var string
	 */
	var $desc = '';

	/**
	 * Whether required or not requred this extra variable
	 * @var string Y, N
	 */
	var $is_required = 'N';

	/**
	 * Whether can or can not search this extra variable
	 * @var string Y, N
	 */
	var $search = 'N';

	/**
	 * Value
	 * @var string
	 */
	var $value = null;

	/**
	 * Unique id of extra variable in module
	 * @var string
	 */
	var $eid = '';

	/**
	 * Constructor
	 *
	 * @param int $module_srl Sequence of module
	 * @param int $idx Index of extra variable
	 * @param string $type Type of extra variable. text, homepage, email_address, tel, textarea, checkbox, date, sleect, radio, kr_zip
	 * @param string[] $default Default values
	 * @param string $desc Description
	 * @param string $is_required Whether required or not requred this extra variable. Y, N
	 * @param string $search Whether can or can not search this extra variable
	 * @param string $value Value
	 * @param string $eid Unique id of extra variable in module
	 * @return void
	 */
	function __construct($module_srl, $idx, $name, $type = 'text', $default = null, $desc = '', $is_required = 'N', $search = 'N', $value = null, $eid = '')
	{
		if(!$idx)
		{
			return;
		}

		$this->module_srl = $module_srl;
		$this->idx = $idx;
		$this->name = $name;
		$this->type = $type;
		$this->default = $default;
		$this->desc = $desc;
		$this->is_required = $is_required;
		$this->search = $search;
		$this->value = $value;
		$this->eid = $eid;
	}

	/**
	 * Sets Value
	 *
	 * @param string $value The value to set
	 * @return void
	 */
	function setValue($value)
	{
		$this->value = $value;
	}

	/**
	 * Returns a given value converted based on its type
	 *
	 * @param string $type Type of variable
	 * @param string $value Value
	 * @return string Returns a converted value
	 */
	function _getTypeValue($type, $value)
	{
		$value = trim($value);
		if(!isset($value))
		{
			return;
		}

		switch($type)
		{
			case 'homepage' :
				if($value && !preg_match('/^([a-z]+):\/\//i', $value))
				{
					$value = 'http://' . $value;
				}
				return escape($value, false);

			case 'tel' :
				if(is_array($value))
				{
					$values = $value;
				}
				elseif(strpos($value, '|@|') !== FALSE)
				{
					$values = explode('|@|', $value);
				}
				elseif(strpos($value, ',') !== FALSE)
				{
					$values = explode(',', $value);
				}
				else
				{
					$values = array($value);
				}

				$values = array_values($values);
				for($i = 0, $c = count($values); $i < $c; $i++)
				{
					$values[$i] = trim(escape($values[$i], false));
				}
				return $values;

			case 'tel_intl' :
				if(is_array($value))
				{
					$values = $value;
				}
				elseif(strpos($value, '|@|') !== FALSE)
				{
					$values = explode('|@|', $value);
				}
				elseif(strpos($value, ',') !== FALSE)
				{
					$values = explode(',', $value);
				}
				else
				{
					$values = array($value);
				}

				$values = array_values($values);
				for($i = 0, $c = count($values); $i < $c; $i++)
				{
					$values[$i] = trim(escape($values[$i], false));
				}
				return $values;
			case 'country':
			case 'language':
			case 'timezone':
			case 'checkbox' :
			case 'radio' :
			case 'select' :
				if(is_array($value))
				{
					$values = $value;
				}
				elseif(strpos($value, '|@|') !== FALSE)
				{
					$values = explode('|@|', $value);
				}
				elseif(strpos($value, ',') !== FALSE)
				{
					$values = explode(',', $value);
				}
				else
				{
					$values = array($value);
				}

				$values = array_values($values);
				for($i = 0, $c = count($values); $i < $c; $i++)
				{
					$values[$i] = trim(escape($values[$i], false));
				}
				return $values;

			case 'kr_zip' :
				if(is_array($value))
				{
					$values = $value;
				}
				elseif(strpos($value, '|@|') !== false)
				{
					$values = explode('|@|', $value);
				}
				else
				{
					$values = array($value);
				}

				$values = array_values($values);
				for($i = 0, $c = count($values); $i < $c; $i++)
				{
					$values[$i] = trim(escape($values[$i], false));
				}
				return $values;

			//case 'date' :
			//case 'email_address' :
			//case 'text' :
			//case 'textarea' :
			//case 'password' :
			default :
				return escape($value, false);
		}
	}

	/**
	 * Returns a value for HTML
	 *
	 * @return string Returns filtered value
	 */
	function getValue()
	{
		return $this->_getTypeValue($this->type, $this->value);
	}

	/**
	 * Returns a value for HTML
	 *
	 * @return string Returns a value expressed in HTML.
	 */
	function getValueHTML()
	{
		$value = $this->_getTypeValue($this->type, $this->value);

		switch($this->type)
		{
			case 'homepage' :
				return ($value) ? (sprintf('<a href="%s" target="_blank">%s</a>', $value, strlen($value) > 60 ? substr($value, 0, 40) . '...' . substr($value, -10) : $value)) : "";

			case 'email_address' :
				return ($value) ? sprintf('<a href="mailto:%s">%s</a>', $value, $value) : "";

			case 'tel' :
				return $value ? implode('-', $value) : '';
			case 'tel_intl' :
				$country_number = $value[0];
				$array_slice = array_slice($value, 1);
				$phone_number = implode('-', $array_slice);
				return $value ? "+{$country_number}){$phone_number}": '';
			case 'country':
				$country_info = Rhymix\Framework\i18n::listCountries()[$value[0]];
				$lang_type = Context::get('lang_type');
				$country_name = $lang_type === 'ko' ? $country_info->name_korean : $country_info->name_english;
				return $country_name;
			case 'textarea' :
				return nl2br($value);
				
			case 'date' :
				return zdate($value, "Y-m-d");

			case 'language':
				return Rhymix\Framework\Lang::getSupportedList()[$value[0]]['name'];
				
			case 'timezone':
				return Rhymix\Framework\DateTime::getTimezoneList()[$value[0]];
			case 'checkbox' :
			case 'select' :
			case 'radio' :
				if(is_array($value))
				{
					return implode(',', $value);
				}
				return $value;

			case 'kr_zip' :
				if(is_array($value))
				{
					return implode(' ', $value);
				}
				return $value;

			// case 'text' :
			// case 'password' :
			default :
				return $value;
		}
	}

	/**
	 * Returns a form based on its type
	 *
	 * @return string Returns a form html.
	 */
	function getFormHTML()
	{
		static $id_num = 1000;

		$type = $this->type;
		$name = $this->name;
		$value = $this->_getTypeValue($this->type, $this->value);
		$default = $this->_getTypeValue($this->type, $this->default);
		$column_name = 'extra_vars' . $this->idx;
		$tmp_id = $column_name . '-' . $id_num++;

		$buff = array();
		switch($type)
		{
			// Homepage
			case 'homepage' :
				$buff[] = '<input type="text" name="' . $column_name . '" value="' . $value . '" class="homepage" />';
				break;
			// Email Address
			case 'email_address' :
				$buff[] = '<input type="text" name="' . $column_name . '" value="' . $value . '" class="email_address" />';
				break;
			// Phone Number
			case 'tel' :
				$buff[] = '<input type="text" name="' . $column_name . '[]" value="' . $value[0] . '" size="4" maxlength="4" class="tel" />';
				$buff[] = '<input type="text" name="' . $column_name . '[]" value="' . $value[1] . '" size="4" maxlength="4" class="tel" />';
				$buff[] = '<input type="text" name="' . $column_name . '[]" value="' . $value[2] . '" size="4" maxlength="4" class="tel" />';
				break;
			// Select Country Number
			case 'tel_intl' :
				$lang_type = Context::get('lang_type');
				$country_list = Rhymix\Framework\i18n::listCountries($lang_type === 'ko' ? Rhymix\Framework\i18n::SORT_NAME_KOREAN : Rhymix\Framework\i18n::SORT_NAME_ENGLISH);
				$buff[] = '<select name="' . $column_name . '" class="select">';
				foreach($country_list as $country_info)
				{
					if($country_info->calling_code)
					{
						$selected = '';
						if(strval($value[0]) !== '' && $country_info->calling_code == $value[0])
						{
							$selected = ' selected="selected"';
						}
						// 3항식 사용시 따로 변수로 뽑아야 뒤의 스트링 만드는것의 중복된 코드가 줄어듬
						$country_name = $lang_type === 'ko' ? $country_info->name_korean : $country_info->name_english;
						$string = $country_name  . "(+{$country_info->calling_code})";
						$buff[] = '  <option value="' . $country_info->calling_code . '" ' . $selected . '>' . $string . '</option>';
					}
				}
				$buff[] = '</select>';
				$buff[] = '<input type="text" name="' . $column_name . '[]" value="' . $value[1] . '" size="4" maxlength="4" class="tel" />';
				$buff[] = '<input type="text" name="' . $column_name . '[]" value="' . $value[2] . '" size="4" maxlength="4" class="tel" />';
				$buff[] = '<input type="text" name="' . $column_name . '[]" value="' . $value[3] . '" size="4" maxlength="4" class="tel" />';
				break;
			// Select Country
			case 'country':
				$lang_type = Context::get('lang_type');
				$country_list = Rhymix\Framework\i18n::listCountries($lang_type === 'ko' ? Rhymix\Framework\i18n::SORT_NAME_KOREAN : Rhymix\Framework\i18n::SORT_NAME_ENGLISH);
				$buff[] = '<select name="' . $column_name . '" class="select">';
				foreach($country_list as $country_info)
				{
					$selected = '';
					if (strval($value[0]) !== '' && $country_info->iso_3166_1_alpha3 == $value[0])
					{
						$selected = ' selected="selected"';
					}
					// 3항식 사용시 따로 변수로 뽑아야 뒤의 스트링 만드는것의 중복된 코드가 줄어듬
					$country_name = $lang_type === 'ko' ? $country_info->name_korean : $country_info->name_english;
					$string = $country_name;
					$buff[] = '  <option value="' . $country_info->iso_3166_1_alpha3 . '" ' . $selected . '>' . $string . '</option>';
				}
				$buff[] = '</select>';
				break;
			// Select language
			case 'language':
				$enable_language = Rhymix\Framework\Config::get('locale.enabled_lang');
				$supported_lang = Rhymix\Framework\Lang::getSupportedList();
				$buff[] = '<select name="' . $column_name . '" class="select">';
				foreach ($enable_language as $lang_type)
				{
					$selected = '';
					if (strval($value[0]) !== '' && $lang_type == $value[0])
					{
						$selected = ' selected="selected"';
					}
					
					$buff[] = '  <option value="' . $lang_type . '" ' . $selected . '>' . $supported_lang[$lang_type]['name'] . '</option>';
				}
				$buff[] = '</select>';
				break;
			// Select timezone
			case 'timezone':
				$timezone_list = Rhymix\Framework\DateTime::getTimezoneList();
				$buff[] = '<select name="' . $column_name . '" class="select">';
				foreach ($timezone_list as $key => $time_name)
				{
					$selected = '';
					if (strval($value[0]) !== '' && $key == $value[0])
					{
						$selected = ' selected="selected"';
					}
					$buff[] = '  <option value="' . $key . '" ' . $selected . '>' . $time_name . '</option>';
				}
				break;
			// textarea
			case 'textarea' :
				$buff[] = '<textarea name="' . $column_name . '" rows="8" cols="42">' . $value . '</textarea>';
				break;
			// multiple choice
			case 'checkbox' :
				$buff[] = '<ul>';
				foreach($default as $v)
				{
					$checked = '';
					if(strval($value) !== '' && in_array(trim($v), $value))
					{
						$checked = ' checked="checked"';
					}

					// Temporary ID for labeling
					$tmp_id = $column_name . '-' . $id_num++;

					$buff[] ='  <li><input type="checkbox" name="' . $column_name . '[]" id="' . $tmp_id . '" value="' . escape($v, false) . '" ' . $checked . ' /><label for="' . $tmp_id . '">' . $v . '</label></li>';
				}
				$buff[] = '</ul>';
				break;
			// single choice
			case 'select' :
				$buff[] = '<select name="' . $column_name . '" class="select">';
				foreach($default as $v)
				{
					$selected = '';
					if(strval($value) !== '' && in_array(trim($v), $value))
					{
						$selected = ' selected="selected"';
					}
					$buff[] = '  <option value="' . $v . '" ' . $selected . '>' . $v . '</option>';
				}
				$buff[] = '</select>';
				break;
			// radio
			case 'radio' :
				$buff[] = '<ul>';
				foreach($default as $v)
				{
					$checked = '';
					if(strval($value) !== '' && in_array(trim($v), $value))
					{
						$checked = ' checked="checked"';
					}

					// Temporary ID for labeling
					$tmp_id = $column_name . '-' . $id_num++;

					$buff[] = '<li><input type="radio" name="' . $column_name . '" id="' . $tmp_id . '" ' . $checked . ' value="' . $v . '"  class="radio" /><label for="' . $tmp_id . '">' . $v . '</label></li>';
				}
				$buff[] = '</ul>';
				break;
			// date
			case 'date' :
				// datepicker javascript plugin load
				Context::loadJavascriptPlugin('ui.datepicker');

				$buff[] = '<input type="hidden" name="' . $column_name . '" value="' . $value . '" />'; 
				$buff[] =	'<input type="text" id="date_' . $column_name . '" value="' . zdate($value, 'Y-m-d') . '" class="date" />';
				$buff[] =	'<input type="button" value="' . lang('cmd_delete') . '" class="btn" id="dateRemover_' . $column_name . '" />';
				$buff[] =	'<script type="text/javascript">';
				$buff[] = '//<![CDATA[';
				$buff[] =	'(function($){';
				$buff[] =	'$(function(){';
				$buff[] =	'  var option = { dateFormat: "yy-mm-dd", changeMonth:true, changeYear:true, gotoCurrent:false, yearRange:\'-100:+10\', onSelect:function(){';
				$buff[] =	'    $(this).prev(\'input[type="hidden"]\').val(this.value.replace(/-/g,""))}';
				$buff[] =	'  };';
				$buff[] =	'  $.extend(option,$.datepicker.regional[\'' . Context::getLangType() . '\']);';
				$buff[] =	'  $("#date_' . $column_name . '").datepicker(option);';
				$buff[] =	'  $("#dateRemover_' . $column_name . '").click(function(){';
				$buff[] =	'    $(this).siblings("input").val("");';
				$buff[] =	'    return false;';
				$buff[] =	'  })';
				$buff[] =	'});';
				$buff[] =	'})(jQuery);';
				$buff[] = '//]]>';
				$buff[] = '</script>';
				break;
			// address
			case "kr_zip" :
				if(($oKrzipModel = getModel('krzip')) && method_exists($oKrzipModel , 'getKrzipCodeSearchHtml' ))
				{
					$buff[] =  $oKrzipModel->getKrzipCodeSearchHtml($column_name, $value);
				}
				break;
			// Password
			case "password" :
				$buff[] =' <input type="password" name="' . $column_name . '" value="' . ($value ? $value : $default) . '" class="password" />';
				break;
			// General text
			default :
				$buff[] =' <input type="text" name="' . $column_name . '" value="' . ($value ? $value : $default) . '" class="text" />';
		}
		if($this->desc)
		{
			$oModuleController = getController('module');
			$oModuleController->replaceDefinedLangCode($this->desc);
			$buff[] = '<p>' . escape($this->desc, false) . '</p>';
		}
		
		return join("\n", $buff);
	}

}
/* End of file ExtraVar.class.php */
/* Location: ./classes/extravar/ExtraVar.class.php */
