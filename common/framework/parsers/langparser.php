<?php

namespace Rhymix\Framework\Parsers;

use Rhymix\Framework\Lang;
use Rhymix\Framework\Storage;

/**
 * Lang parser class for XE compatibility.
 */
class LangParser
{
	/**
	 * Convert a directory of old language files to the Rhymix format.
	 * 
	 * @param string $dir
	 * @param array $xml_langs When converting XML to PHP, only convert these languages. (Optional)
	 * @return void
	 */
	public static function convertDirectory($dir, $xml_langs = array())
	{
		if (file_exists("$dir/lang.xml"))
		{
			$langs = count($xml_langs) ? $xml_langs : array_keys(Lang::getSupportedList());
			foreach ($langs as $lang)
			{
				self::compileXMLtoPHP("$dir/lang.xml", $lang === 'ja' ? 'jp' : $lang, "$dir/$lang.php");
			}
		}
		else
		{
			$files = glob($dir . '/*.lang.php');
			foreach ($files as $filename)
			{
				$new_filename = preg_replace('/\.lang\.php$/', '.php', str_replace('jp.lang', 'ja.lang', $filename));
				Storage::move($filename, $new_filename);
			}
		}
	}
	
	/**
	 * Compile XE-compatible XML lang files into PHP.
	 * 
	 * @param string $filename
	 * @param string $language
	 * @return string|false
	 */
	public static function compileXMLtoPHP($filename, $language, $output_filename = null)
	{
		// Check if the cache file already exists.
		if ($output_filename === null)
		{
			$output_filename = \RX_BASEDIR . 'files/cache/lang/' . md5($filename) . '.' . $language . '.php';
			if (file_exists($output_filename) && filemtime($output_filename) > filemtime($filename))
			{
				return $output_filename;
			}
		}
		
		// Load the XML lang file.
		$xml = simplexml_load_string(Storage::read($filename));
		if ($xml === false)
		{
			Storage::write($output_filename, '');
			return false;
		}
		
		// Convert XML to a PHP array.
		$lang = array();
		self::_toArray($xml, $lang, $language);
		unset($xml);
		
		// Save the array as a cache file.
		$buff = "<?php\n// $filename\n";
		foreach ($lang as $key => $value)
		{
			if (is_array($value))
			{
				foreach ($value as $subkey => $subvalue)
				{
					if (is_array($subvalue))
					{
						foreach ($subvalue as $subsubkey => $subsubvalue)
						{
							$buff .= '$lang->' . $key . "['$subkey']['$subsubkey']" . ' = ' . var_export($subsubvalue, true) . ";\n";
						}
					}
					else
					{
						$buff .= '$lang->' . $key . "['$subkey']" . ' = ' . var_export($subvalue, true) . ";\n";
					}
				}
			}
			else
			{
				$buff .= '$lang->' . $key . ' = ' . var_export($value, true) . ";\n";
			}
		}
		Storage::write($output_filename, $buff);
		return $output_filename;
	}
	
	/**
	 * XML to array conversion callback.
	 * 
	 * @param array $items
	 * @return void
	 */
	protected static function _toArray($items, &$lang, $language)
	{
		foreach ($items as $item)
		{
			$name = strval($item['name']);
			if (@count($item->item))
			{
				$lang[$name] = array();
				self::_toArray($item->item, $lang[$name], $language);
			}
			else
			{
				foreach ($item->value as $value)
				{
					$attribs = $value->attributes('xml', true);
					if (strval($attribs['lang']) === $language)
					{
						$lang[$name] = strval($value);
						break;
					}
				}
			}
		}
	}
}
