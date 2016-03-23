<?php

namespace Rhymix\Framework;

/**
 * The MIME class.
 */
class MIME
{
	/**
	 * Get the MIME type for the given extension.
	 * 
	 * @param string $extension
	 * @return string
	 */
	public static function getTypeByExtension($extension)
	{
		$extension = strtolower($extension);
		return array_key_exists($extension, self::$_types) ? self::$_types[$extension] : self::$_default;
	}
	
	/**
	 * Get the MIME type for the given filename.
	 * 
	 * @param string $filename
	 * @return string
	 */
	public static function getTypeByFilename($filename)
	{
		$extension = strrchr($filename, '.');
		if ($extension === false) return self::$_default;
		$extension = strtolower(substr($extension, 1));
		return array_key_exists($extension, self::$_types) ? self::$_types[$extension] : self::$_default;
	}
	
	/**
	 * Get the most common extension for the given MIME type.
	 * 
	 * @param string $type
	 * @return string|false
	 */
	public static function getExtensionByType($type)
	{
		foreach (self::$_types as $extension => $mime)
		{
			if (!strncasecmp($type, $mime, strlen($type))) return $extension;
		}
		return false;
	}
	
	/**
	 * The default MIME type for unknown extensions.
	 */
	protected static $_default = 'application/octet-stream';
	
	/**
	 * The list of known MIME types.
	 */
	protected static $_types = array(
		
		// Text-based document formats.
		'html' => 'text/html',
		'htm' => 'text/html',
		'shtml' => 'text/html',
		'txt' => 'text/plain',
		'text' => 'text/plain',
		'log' => 'text/plain',
		'md' => 'text/markdown',
		'markdown' => 'text/markdown',
		'rtf' => 'text/rtf',
		'xml' => 'text/xml',
		'xsl' => 'text/xml',
		'css' => 'text/css',
		'csv' => 'text/csv',
		
		// Binary document formats.
		'doc' => 'application/msword',
		'dot' => 'application/msword',
		'xls' => 'application/vnd.ms-excel',
		'ppt' => 'application/vnd.ms-powerpoint',
		'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
		'dotx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
		'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
		'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
		'odt' => 'application/vnd.oasis.opendocument.text',
		'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
		'odp' => 'application/vnd.oasis.opendocument.presentation',
		'odg' => 'application/vnd.oasis.opendocument.graphics',
		'odb' => 'application/vnd.oasis.opendocument.database',
		'pdf' => 'application/pdf',
		
		// Images.
		'bmp' => 'image/bmp',
		'gif' => 'image/gif',
		'jpg' => 'image/jpeg',
		'jpeg' => 'image/jpeg',
		'jpe' => 'image/jpeg',
		'png' => 'image/png',
		'svg' => 'image/svg+xml',
		'tiff' => 'image/tiff',
		'tif' => 'image/tiff',
		'ico' => 'image/vnd.microsoft.icon',
		
		// Audio.
		'mid' => 'audio/midi',
		'midi' => 'audio/midi',
		'mpga' => 'audio/mpeg',
		'mp2' => 'audio/mpeg',
		'mp3' => 'audio/mpeg',
		'aif' => 'audio/x-aiff',
		'aiff' => 'audio/x-aiff',
		'ra' => 'audio/x-realaudio',
		'wav' => 'audio/x-wav',
		'ogg' => 'audio/ogg',
		
		// Video.
		'avi' => 'video/x-msvideo',
		'flv' => 'video/x-flv',
		'mpeg' => 'video/mpeg',
		'mpg' => 'video/mpeg',
		'mpe' => 'video/mpeg',
		'mp4' => 'video/mpeg',
		'qt' => 'video/quicktime',
		'mov' => 'video/quicktime',
		'movie' => 'video/x-sgi-movie',
		'rv' => 'video/vnd.rn-realvideo',
		'dvi' => 'application/x-dvi',
		
		// Other multimedia file formats.
		'psd' => 'application/x-photoshop',
		'swf' => 'application/x-shockwave-flash',
		'ai' => 'application/postscript',
		'eps' => 'application/postscript',
		'ps' => 'application/postscript',
		'mif' => 'application/vnd.mif',
		'xul' => 'application/vnd.mozilla.xul+xml',
		
		// Source code formats.
		'phps' => 'application/x-httpd-php-source',
		'js' => 'application/x-javascript',
		
		// Archives.
		'bz2' => 'application/x-bzip',
		'gz' => 'application/x-gzip',
		'tar' => 'application/x-tar',
		'tgz' => 'application/x-tar',
		'gtar' => 'application/x-gtar',
		'rar' => 'application/x-rar-compressed',
		'zip' => 'application/x-zip',
		
		// RFC822 email message.
		'eml' => 'message/rfc822',
	);
}
