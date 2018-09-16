<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

class UploadFileFilter
{
	/**
	 * Generic checker
	 * 
	 * @param string $file
	 * @param string $filename
	 * @return bool
	 */
	public static function check($file, $filename = null)
	{
		// Return error if the file is not uploaded.
		if (!$file || !file_exists($file) || !is_uploaded_file($file))
		{
			return false;
		}
		
		// Return error if the file size is zero.
		if (!filesize($file))
		{
			return false;
		}
		
		// Get the extension.
		$ext = $filename ? strtolower(substr(strrchr($filename, '.'), 1)) : '';
		
		// Check SVG files.
		if ($ext === 'svg' && !self::_checkSVG($file))
		{
			return false;
		}
		
		// Return true if everything is OK.
		return true;
	}
	
	/**
	 * Check SVG file for XSS or SSRF vulnerabilities (#1088, #1089)
	 *
	 * @param string $file
	 * @return bool
	 */
	protected static function _checkSVG($file)
	{
		$content = file_get_contents($file);
		
		if (preg_match('/xlink:href\s*=\s*"(?!data:)/i', $content))
		{
			return false;
		}
		
		if (preg_match('/<script/i', $content))
		{
			return false;
		}
		
		return true;
	}
}

/* End of file : UploadFileFilter.class.php */
/* Location: ./classes/security/UploadFileFilter.class.php */
