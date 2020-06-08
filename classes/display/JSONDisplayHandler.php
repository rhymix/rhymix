<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

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
		return json_encode($variables);
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
/* End of file JSONDisplayHandler.class.php */
/* Location: ./classes/display/JSONDisplayHandler.class.php */
