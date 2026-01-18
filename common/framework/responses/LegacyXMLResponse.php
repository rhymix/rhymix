<?php

namespace Rhymix\Framework\Responses;

use Rhymix\Framework\AbstractResponse;

/**
 * The legacy XML response class.
 *
 * This format was used by XE 1.x for XMLRPC.
 */
class LegacyXMLResponse extends AbstractResponse
{
	/**
	 * Override the default content type.
	 */
	protected string $_content_type = 'text/xml';
	protected string $_charset = 'UTF-8';

	/**
	 * Render the full response.
	 *
	 * @return iterable
	 */
	public function render(): iterable
	{
		yield '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
		yield "<response>\n";
		yield sprintf("<error>%s</error>\n", escape($this->_vars['error'] ?? '0'));
		yield sprintf("<message>%s</message>\n", escape($this->_vars['message'] ?? 'success'));
		foreach (self::_makeXML($this->_vars) as $line)
		{
			yield $line;
		}
		yield "</response>\n";
	}

	/**
	 * Encode an array as XE 1.x-compatible XML.
	 *
	 * @param array $vars
	 * @return iterable
	 */
	protected static function _makeXML(array $vars): iterable
	{
		foreach ($vars as $key => $val)
		{
			if (in_array($key, ['error', 'message']))
			{
				continue;
			}
			elseif (is_numeric($key))
			{
				$key = 'item';
			}
			else
			{
				$key = escape($key);
			}

			if (is_scalar($val) || is_null($val))
			{
				yield sprintf("<%s>%s</%s>\n", $key, escape($val ?? ''), $key);
			}
			else
			{
				$val = is_array($val) ? $val : get_object_vars($val);
				yield "<$key>\n";
				foreach (self::_makeXML($val) as $line)
				{
					yield $line;
				}
				yield "</$key>\n";
			}
		}
	}
}
