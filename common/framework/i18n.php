<?php

namespace Rhymix\Framework;

/**
 * The i18n (internationalization) class.
 */
class i18n
{
	/**
	 * Constants for sorting.
	 */
	const SORT_CODE_2 = 2;
	const SORT_CODE_3 = 3;
	const SORT_CODE_NUMERIC = 4;
	const SORT_CCTLD = 5;
	const SORT_NAME_ENGLISH = 6;
	const SORT_NAME_KOREAN = 7;
	const SORT_NAME_NATIVE = 8;
	
	/**
	 * Local cache.
	 */
	protected static $_countries = array();
	
	/**
	 * Get the list of all countries.
	 * 
	 * @param int $sort_by
	 * @return array
	 */
	public static function listCountries($sort_by = self::SORT_NAME_ENGLISH)
	{
		if (isset(self::$_countries[$sort_by]))
		{
			return self::$_countries[$sort_by];
		}
		
		$countries = (include \RX_BASEDIR . 'common/defaults/countries.php');
		$result = array();
		
		foreach ($countries as $country)
		{
			$result[$country['iso_3166_1_alpha3']] = (object)$country;
		}
		
		switch ($sort_by)
		{
			case self::SORT_CODE_2:
				uasort($result, function($a, $b) {
					return strcmp($a->iso_3166_1_alpha2, $b->iso_3166_1_alpha2);
				});
				break;
			case self::SORT_CODE_3:
				uasort($result, function($a, $b) {
					return strcmp($a->iso_3166_1_alpha3, $b->iso_3166_1_alpha3);
				});
				break;
			case self::SORT_CODE_NUMERIC:
				uasort($result, function($a, $b) {
					return strcmp($a->iso_3166_1_numeric, $b->iso_3166_1_numeric);
				});
				break;
			case self::SORT_CCTLD:
				uasort($result, function($a, $b) {
					return strcmp($a->cctld, $b->cctld);
				});
				break;
			case self::SORT_NAME_ENGLISH:
				uasort($result, function($a, $b) {
					return strcmp($a->name_english, $b->name_english);
				});
				break;
			case self::SORT_NAME_KOREAN:
				uasort($result, function($a, $b) {
					return strcmp($a->name_korean, $b->name_korean);
				});
				break;
			case self::SORT_NAME_NATIVE:
				uasort($result, function($a, $b) {
					return strcmp($a->name_native, $b->name_native);
				});
				break;
		}
		
		self::$_countries[$sort_by] = $result;
		return $result;
	}
	
	/**
	 * Get the calling code from a country code (either ISO-3166-1 alpha2 or alpha3).
	 * 
	 * This function returns null if a matching country is not found.
	 * 
	 * @param $code Country code
	 * @return string|null
	 */
	public static function getCallingCodeByCountryCode($code)
	{
		$countries = self::listCountries();
		if (strlen($code) === 3)
		{
			return (isset($countries[$code]) && $countries[$code]->calling_code) ? $countries[$code]->calling_code : null;
		}
		else
		{
			foreach ($countries as $country)
			{
				if ($country->iso_3166_1_alpha2 === $code)
				{
					return $country->calling_code;
				}
			}
		}
		
		return null;
	}
	
	/**
	 * Get the country code (either ISO-3166-1 alpha2 or alpha3) from a calling code.
	 * 
	 * This function may return the wrong country if two or more countries share a calling code.
	 * This function returns null if a matching country is not found.
	 * 
	 * @param $code Calling code
	 * @return string|null
	 */
	public static function getCountryCodeByCallingCode($code, $type = 3)
	{
		$countries = self::listCountries();
		$code = preg_replace('/[^0-9]/', '', $code);
		foreach ($countries as $country)
		{
			if (preg_replace('/[^0-9]/', '', $country->calling_code) === $code)
			{
				return $type == 3 ? $country->iso_3166_1_alpha3 : $country->iso_3166_1_alpha2;
			}
		}
		
		return null;
	}
	/**
	 * Format a phone number with country code.
	 * 
	 * @param string $phone_number
	 * @param string $phone_country
	 * @param bool $pretty (optional)
	 * @return string
	 */
	public static function formatPhoneNumber($phone_number, $phone_country, $pretty = true)
	{
		if (!is_numeric($phone_country))
		{
			$phone_country = self::getCallingCodeByCountryCode($phone_country);
		}
		
		if ($pretty)
		{
			if ($phone_country == 82)
			{
				$pretty_phone_number = Korea::formatPhoneNumber($phone_number);
			}
			elseif ($phone_country == 1)
			{
				$digits = preg_replace('/[^0-9]/', '', $phone_number);
				$pretty_phone_number = substr($digits, 0, 3) . '-' . substr($digits, 3, 3) . '-' . substr($digits, 6);
			}
			else
			{
				$pretty_phone_number = preg_replace('/[^0-9-]/', '', $phone_number);
			}
			return sprintf('(+%s) %s', $phone_country, $pretty_phone_number);
		}
		else
		{
			if (!in_array(strval($phone_country), array('39', '378', '379')))
			{
				$phone_number = preg_replace('/^0/', '', $phone_number);
			}
			return sprintf('+%s', preg_replace('/[^0-9]/', '', $phone_country . $phone_number));
		}
	}
}
