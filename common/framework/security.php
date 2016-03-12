<?php

namespace Rhymix\Framework;

/**
 * The security class.
 */
class Security
{
	/**
	 * Sanitize a variable.
	 * 
	 * @param string $input
	 * @param string $type
	 * @return string|false
	 */
	public static function sanitize($input, $type)
	{
		switch ($type)
		{
			// Escape HTML special characters.
			case 'escape':
				if (!detectUTF8($input)) return false;
				return escape($input);
			
			// Strip all HTML tags.
			case 'strip':
				if (!detectUTF8($input)) return false;
				return escape(strip_tags($input));
			
			// Clean up HTML content to prevent XSS attacks.
			case 'html':
				if (!detectUTF8($input)) return false;
				return Security\HTMLFilter::clean($input);
			
			// Clean up the input to be used as a safe filename.
			case 'filename':
				if (!detectUTF8($input)) return false;
				return Security\FilenameFilter::clean($input);
			
			// Unknown filters return false.
			default: return false;
		}
	}
	
	/**
	 * Check if the current request seems to be a CSRF attack.
	 * 
	 * This method returns true if the request seems to be innocent,
	 * and false if it seems to be a CSRF attack.
	 * 
	 * @param string $referer (optional)
	 * @return bool
	 */
	public static function checkCSRF($referer = null)
	{
		if ($_SERVER['REQUEST_METHOD'] !== 'POST')
		{
			return false;
		}
		
		if (!$referer)
		{
			$referer = strval($_SERVER['HTTP_REFERER']);
			if ($referer === '')
			{
				return true;
			}
		}
		
		return URL::isInternalURL($referer);
	}
	
	/**
	 * Check if the current request seems to be an XEE attack.
	 * 
	 * This method returns true if the request seems to be innocent,
	 * and false if it seems to be an XEE attack.
	 * This is the opposite of XE's Security::detectXEE() method.
	 * 
	 * @param string $xml (optional)
	 * @return bool
	 */
	public static function checkXEE($xml = null)
	{
		// Stop if there is no XML content.
		if (!$xml)
		{
			return true;
		}
		
		// Reject entity tags.
		if (strpos($xml, '<!ENTITY') !== false)
		{
			return false;
		}
		
		// Check if there is no content after the xml tag.
		$header = preg_replace('/<\?xml.*?\?'.'>/s', '', substr($xml, 0, 100), 1);
		if (($xml = trim(substr_replace($xml, $header, 0, 100))) === '')
		{
			return false;
		}
		
		// Check if there is no content after the DTD.
		$header = preg_replace('/^<!DOCTYPE[^>]*+>/i', '', substr($xml, 0, 200), 1);
		if (($xml = trim(substr_replace($xml, $header, 0, 200))) === '')
		{
			return false;
		}
		
		// Check that the root tag is valid.
		if (!preg_match('/^<(methodCall|methodResponse|fault)/', $xml))
		{
			return false;
		}
		
		return true;
	}
}
