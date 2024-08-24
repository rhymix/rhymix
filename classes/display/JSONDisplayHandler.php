<?php

class JSONDisplayHandler
{

	/**
	 * Produce JSON compliant content given a module object.\n
	 * @param ModuleObject $oModule the module object
	 * @return string
	 */
	public function toDoc($oModule)
	{
		$variables = $oModule->getVariables();
		$variables['error'] = $oModule->getError();
		$variables['message'] = $oModule->getMessage();

		self::_convertCompat($variables, Context::getRequestMethod());
		$result = json_encode($variables) . "\n";
		if (json_last_error() != \JSON_ERROR_NONE)
		{
			trigger_error('JSON encoding error: ' . json_last_error_msg(), E_USER_WARNING);
			return json_encode([
				'error' => -1,
				'message' => 'JSON encoding error',
			]) . "\n";
		}
		return $result;
	}

	/**
	 * Convert arrays in a format that is compatible with XE.
	 *
	 * @param array $array
	 * @param string $compat_type
	 * @return array
	 */
	protected static function _convertCompat(&$array, $compat_type = 'JSON')
	{
		foreach ($array as $key => &$value)
		{
			if (is_object($value))
			{
				$value = get_object_vars($value);
			}
			if (is_array($value))
			{
				self::_convertCompat($value, $compat_type);
				if (self::_isNumericArray($value))
				{
					if ($compat_type === 'XMLRPC')
					{
						$value = array('item' => array_values($value));
					}
					else
					{
						$value = array_values($value);
					}
				}
			}
		}
	}

	/**
	 * Check if an array only has numeric keys.
	 *
	 * @param array $array
	 * @return bool
	 */
	protected static function _isNumericArray($array)
	{
		if (!is_array($array) || !count($array))
		{
			return false;
		}
		foreach ($array as $key => $value)
		{
			if (!is_numeric($key))
			{
				return false;
			}
		}
		return true;
	}
}
