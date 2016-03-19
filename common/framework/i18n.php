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
	 * Get the list of all countries.
	 * 
	 * @param int $sort_by
	 * @return array
	 */
	public static function listCountries($sort_by = self::SORT_NAME_ENGLISH)
	{
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
		
		return $result;
	}
}
