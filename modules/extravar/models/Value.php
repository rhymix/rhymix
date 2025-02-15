<?php

namespace Rhymix\Modules\Extravar\Models;

use BaseObject;
use Context;
use FileController;
use FileHandler;
use FileModel;
use ModuleModel;
use Rhymix\Framework\DateTime;
use Rhymix\Framework\i18n;
use Rhymix\Framework\Lang;
use Rhymix\Framework\Template;
use Rhymix\Framework\Storage;

class Value
{
	/**
	 * Public properties for compatibility with legacy ExtraItem class.
	 */
	public $module_srl = 0;
	public $idx = 0;
	public $eid = '';
	public $input_id = '';
	public $input_name = '';
	public $parent_type = 'document';
	public $type = 'text';
	public $value = null;
	public $name = '';
	public $desc = '';
	public $default = null;
	public $options = null;
	public $is_required = 'N';
	public $is_strict = 'N';
	public $is_disabled = 'N';
	public $is_readonly = 'N';
	public $search = 'N';
	public $style = null;

	/**
	 * Skin path cache.
	 */
	protected static $_skin_path = null;

	/**
	 * Temporary ID cache.
	 */
	protected static $_temp_id = 1;

	/**
	 * List of types where the value should be interpreted as an array.
	 */
	public const ARRAY_TYPES = [
		'tel' => true,
		'tel_v2' => true,
		'tel_intl' => true,
		'tel_intl_v2' => true,
		'checkbox' => true,
		'radio' => true,
		'select' => true,
		'kr_zip' => true,
	];

	/**
	 * List of types that can have options.
	 */
	public const OPTION_TYPES = [
		'checkbox' => true,
		'radio' => true,
		'select' => true,
	];

	/**
	 * Constructor for compatibility with legacy ExtraItem class.
	 *
	 * @param int $module_srl
	 * @param int $idx
	 * @param string $name
	 * @param string $type
	 * @param mixed $default
	 * @param string $desc
	 * @param string $is_required (Y, N)
	 * @param string $search (Y, N)
	 * @param string $value
	 * @param string $eid
	 * @param string $parent_type
	 * @param string $is_strict
	 * @param string $options
	 */
	function __construct(int $module_srl, int $idx, string $name, string $type = 'text', $default = null, $desc = '', $is_required = 'N', $search = 'N', $value = null, $eid = '', $parent_type = 'document', $is_strict = '', $options = null)
	{
		if (!$idx)
		{
			return;
		}

		$this->module_srl = $module_srl;
		$this->idx = $idx;
		$this->eid = $eid;
		$this->type = $type;
		$this->parent_type = $parent_type;
		$this->value = $value;
		$this->name = $name;
		$this->desc = $desc;
		$this->default = $default;
		$this->options = $options ? json_decode($options) : null;
		$this->is_required = $is_required;
		$this->is_strict = $is_strict;
		$this->search = $search;
	}

	/**
	 * Set the raw value.
	 *
	 * @param mixed $value
	 * @return void
	 */
	public function setValue($value): void
	{
		$this->value = $value;
	}

	/**
	 * Check if this extra variable has a value.
	 *
	 * @return bool
	 */
	public function hasValue(): bool
	{
		$value = self::_getTypeValue($this->type, $this->value);
		if ($value === null || $value === '' || (is_array($value) && !count($value)))
		{
			return false;
		}
		else
		{
			return true;
		}
	}

	/**
	 * Get the raw value.
	 *
	 * @return string|array|null
	 */
	public function getValue()
	{
		return self::_getTypeValue($this->type, $this->value);
	}

	/**
	 * Get the value formatted as HTML.
	 *
	 * @return string
	 */
	public function getValueHTML(): string
	{
		return self::_getTypeValueHTML($this->type, $this->value);
	}

	/**
	 * Get the HTML form for an extra input field.
	 *
	 * @return string
	 */
	public function getFormHTML(): string
	{
		$template = new Template(self::_getSkinPath(), 'form.blade.php');
		$template->setVars([
			'definition' => $this,
			'parent_type' => $this->parent_type,
			'type' => $this->type,
			'value' => self::_getTypeValue($this->type, $this->value),
			'default' => self::_getTypeValue($this->type, $this->default),
			'input_name' => $this->parent_type === 'document' ? ('extra_vars' . $this->idx) : ($this->input_name ?: $this->eid),
			'input_id' => $this->input_id ?: '',
		]);
		return $template->compile();
	}

	/**
	 * Get the default value.
	 *
	 * @return mixed
	 */
	public function getDefaultValue()
	{
		if (!$this->canHaveOptions())
		{
			return $this->default;
		}
		elseif (is_array($this->options))
		{
			return $this->default;
		}
		else
		{
			return null;
		}
	}

	/**
	 * Get options specified by the administrator.
	 *
	 * @return array
	 */
	public function getOptions(): array
	{
		if (!$this->canHaveOptions())
		{
			return $this->options ?? [];
		}

		if (is_array($this->options))
		{
			return $this->options;
		}
		elseif ($this->default)
		{
			return is_array($this->default) ? $this->default : explode(',', $this->default);
		}
		else
		{
			return [];
		}
	}

	/**
	 * Check if the current value can have options.
	 *
	 * @return bool
	 */
	public function canHaveOptions(): bool
	{
		return isset(self::OPTION_TYPES[$this->type]);
	}

	/**
	 * Check if the current value is an array type.
	 *
	 * @return bool
	 */
	public function isArrayType(): bool
	{
		return isset(self::ARRAY_TYPES[$this->type]);
	}

	/**
	 * Validate a value.
	 *
	 * @param mixed $value
	 * @param mixed $old_value
	 * @return BaseObject
	 */
	public function validate($value, $old_value = null): BaseObject
	{
		// Take legacy encoding into consideration.
		if (is_array($value))
		{
			$is_array = true;
			$values = $value;
		}
		elseif (str_contains($value, '|@|'))
		{
			$is_array = true;
			$values = explode('|@|', $value);
		}
		else
		{
			$is_array = false;
			$values = [$value];
		}

		// Check if a required value is empty.
		if ($this->is_required === 'Y')
		{
			if ($this->type === 'file' && !$value && $old_value)
			{
				$value = $old_value;
				$values = (array)$old_value;
			}
			if ($is_array && trim(implode('', $values)) === '')
			{
				return new BaseObject(-1, sprintf(lang('common.filter.isnull'), Context::replaceUserLang($this->name)));
			}
			if (!$is_array && trim(strval($value)) === '')
			{
				return new BaseObject(-1, sprintf(lang('common.filter.isnull'), Context::replaceUserLang($this->name)));
			}
		}

		// Check if a strict value is not one of the specified options.
		if ($this->is_strict === 'Y' && $value)
		{
			if ($this->canHaveOptions())
			{
				$options = $this->getOptions();
				foreach ($values as $v)
				{
					if (!in_array($v, $options))
					{
						return new BaseObject(-1, sprintf(lang('common.filter.equalto'), Context::replaceUserLang($this->name)));
					}
				}
			}
			elseif ($this->isArrayType())
			{
				if (!$is_array)
				{
					return new BaseObject(-1, sprintf(lang('common.filter.equalto'), Context::replaceUserLang($this->name)));
				}
			}
		}

		return new BaseObject;
	}

	/**
	 * Upload a file.
	 *
	 * @param array $file
	 * @param int $target_srl
	 * @param string $target_type
	 * @return BaseObject
	 */
	public function uploadFile(array $file, int $target_srl, string $target_type): BaseObject
	{
		$oFileController = FileController::getInstance();
		$output = $oFileController->insertFile($file, $this->module_srl, $target_srl);
		if ($output->toBool())
		{
			$oFileController->setFilesValid($target_srl, "ev:$target_type", $output->get('file_srl'));
		}
		return $output;
	}

	/**
	 * Get the next temporary ID.
	 *
	 * @return string
	 */
	public static function getNextTempID(): string
	{
		return sprintf('rx_tempid_%d', self::$_temp_id++);
	}

	/**
	 * Get the normalized value.
	 *
	 * This method is public for compatibility with legacy ExtraItem class.
	 * It should not be called from outside of this class.
	 *
	 * @param string $type
	 * @param string|array $value
	 * @return string|array|null
	 */
	public static function _getTypeValue(string $type, $value)
	{
		// Return if the value is empty.
		if (is_array($value))
		{
			if (!count($value))
			{
				return;
			}
		}
		else
		{
			$value = trim(strval($value ?? ''));
			if ($value === '')
			{
				return;
			}
		}

		// Process array types.
		if (isset(self::ARRAY_TYPES[$type]))
		{
			if (is_array($value))
			{
				$values = $value;
			}
			elseif (preg_match('/^[\[\{].*[\]\}]$/', $value))
			{
				$values = json_decode($value, true);
				if (!is_array($values))
				{
					$values = [];
				}
			}
			elseif (str_contains($value, '|@|'))
			{
				$values = explode('|@|', $value);
			}
			/*
			elseif (str_contains($value, ',') && $type !== 'kr_zip')
			{
				$values = explode(',', $value);
			}
			*/
			else
			{
				$values = [$value];
			}

			return array_map(function($str) {
				return trim(escape($str, false));
			}, array_values($values));
		}

		// Process the URL type.
		if ($type === 'homepage' || $type === 'url')
		{
			if ($value && !preg_match('!^[a-z]+://!i', $value))
			{
				$value = 'http://' . $value;
			}
			return escape($value, false);
		}

		// Process the file upload type.
		if ($type === 'file')
		{
			return $value ? intval($value) : null;
		}

		// Escape and return all other types.
		return escape($value, false);
	}

	/**
	 * Get the normalized value in HTML format.
	 *
	 * @param string $type
	 * @param string|array $value
	 * @return string
	 */
	protected static function _getTypeValueHTML(string $type, $value): string
	{
		// Return if the value is empty.
		$value = self::_getTypeValue($type, $value);
		if ($value === null || $value === '' || (is_array($value) && !count($value)))
		{
			return '';
		}

		// Apply formatting appropriate for each type.
		switch ($type)
		{
			case 'textarea':
				return nl2br($value);
			case 'select':
			case 'radio':
			case 'checkbox':
				return is_array($value) ? implode(', ', $value) : $value;
			case 'tel':
			case 'tel_v2':
				return is_array($value) ? implode('-', $value) : $value;
			case 'tel_intl':
			case 'tel_intl_v2':
				if (is_array($value) && count($value))
				{
					$country_code = $value[0];
					$phone_number = array_slice((array)$value, 1);
					if (count($phone_number) && ctype_alpha(end($phone_number)))
					{
						array_pop($phone_number);
					}
					return sprintf('(+%d) %s', $country_code, implode('-', $phone_number));
				}
				else
				{
					return '';
				}
			case 'homepage':
			case 'url':
				$display = mb_strlen($value, 'UTF-8') > 60 ? mb_substr($value, 0, 40, 'UTF-8') . '...' . mb_substr($value, -10, 10, 'UTF-8') : $value;
				return sprintf('<a href="%s" target="_blank">%s</a>', $value, $display);
			case 'email_address':
			case 'email':
				return sprintf('<a href="mailto:%s">%s</a>', $value, $value);
			case 'kr_zip':
				return is_array($value) ? trim(implode(' ', $value)) : $value;
			case 'country':
				$country = i18n::listCountries()[$value] ?? '';
				if ($country)
				{
					$lang_type = \Context::getLangType();
					return $lang_type === 'ko' ? $country->name_korean : $country->name_english;
				}
				else
				{
					return '';
				}
			case 'language':
				return Lang::getSupportedList()[$value]['name'] ?? '';
			case 'date':
				return sprintf('%s-%s-%s', substr($value, 0, 4), substr($value, 4, 2), substr($value, 6, 2));
			case 'timezone':
				return DateTime::getTimezoneList()[$value] ?? '';
			case 'file':
				if ($value)
				{
					$file = FileModel::getFile($value);
					if ($file)
					{
						return sprintf('<span><a href="%s">%s</a> (%s)</span>', \RX_BASEURL . ltrim($file->download_url, './'), $file->source_filename, FileHandler::filesize($file->file_size));
					}
					else
					{
						return '';
					}
				}
				else
				{
					return '';
				}
			default:
				return $value;
		}
	}

	/**
	 * Get the currently configured skin path.
	 *
	 * @return string
	 */
	protected static function _getSkinPath(): string
	{
		if (self::$_skin_path !== null)
		{
			return self::$_skin_path;
		}

		$config = ModuleModel::getModuleConfig('extravar') ?: new \stdClass;
		$skin = $config->skin ?? 'default';
		if (Storage::isDirectory(\RX_BASEDIR . 'modules/extravar/skins/' . $skin))
		{
			self::$_skin_path = \RX_BASEDIR . 'modules/extravar/skins/' . $skin;
		}
		else
		{
			self::$_skin_path = \RX_BASEDIR . 'modules/extravar/skins/default';
		}

		return self::$_skin_path;
	}
}
