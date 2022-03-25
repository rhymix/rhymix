<?php

namespace Rhymix\Framework;

/**
 * The MIME class.
 */
class MIME
{
	/**
	 * Get the MIME type of a file, detected by its content.
	 * 
	 * This method returns the MIME type of a file, or false on error.
	 * 
	 * @param string $filename
	 * @return array|false
	 */
	public static function getContentType($filename)
	{
		$filename = rtrim($filename, '/\\');
		if (Storage::exists($filename) && @is_file($filename) && @is_readable($filename))
		{
			if (function_exists('mime_content_type'))
			{
				return @mime_content_type($filename) ?: false;
			}
			elseif (($image = @getimagesize($filename)) && $image['mime'])
			{
				return $image['mime'];
			}
			else
			{
				return self::getTypeByFilename($filename);
			}
		}
		else
		{
			return false;
		}
	}
	
	/**
	 * Get the MIME type for the given extension.
	 * 
	 * @param string $extension
	 * @return string
	 */
	public static function getTypeByExtension($extension)
	{
		$extension = strtolower($extension);
		return array_key_exists($extension, self::$_types) ? self::$_types[$extension][0] : self::$_default;
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
		return array_key_exists($extension, self::$_types) ? self::$_types[$extension][0] : self::$_default;
	}
	
	/**
	 * Get the most common extension for the given MIME type.
	 * 
	 * @param string $type
	 * @return string|false
	 */
	public static function getExtensionByType($type)
	{
		foreach (self::$_types as $extension => $mimes)
		{
			foreach ($mimes as $mime)
			{
				if (!strncasecmp($type, $mime, strlen($type))) return $extension;
			}
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
		'html' => ['text/html'],
		'htm' => ['text/html'],
		'shtml' => ['text/html'],
		'txt' => ['text/plain'],
		'text' => ['text/plain'],
		'log' => ['text/plain'],
		'md' => ['text/markdown'],
		'markdown' => ['text/markdown'],
		'rtf' => ['text/rtf'],
		'xml' => ['text/xml'],
		'xsl' => ['text/xml'],
		'css' => ['text/css'],
		'csv' => ['text/csv'],
		
		// Binary document formats.
		'doc' => ['application/msword'],
		'dot' => ['application/msword'],
		'xls' => ['application/vnd.ms-excel'],
		'ppt' => ['application/vnd.ms-powerpoint'],
		'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
		'dotx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
		'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
		'pptx' => ['application/vnd.openxmlformats-officedocument.presentationml.presentation'],
		'odt' => ['application/vnd.oasis.opendocument.text'],
		'ods' => ['application/vnd.oasis.opendocument.spreadsheet'],
		'odp' => ['application/vnd.oasis.opendocument.presentation'],
		'odg' => ['application/vnd.oasis.opendocument.graphics'],
		'odb' => ['application/vnd.oasis.opendocument.database'],
		'pdf' => ['application/pdf'],
		'dvi' => ['application/x-dvi'],
		
		// Images.
		'bmp' => ['image/bmp'],
		'gif' => ['image/gif'],
		'jpg' => ['image/jpeg'],
		'jpeg' => ['image/jpeg'],
		'jpe' => ['image/jpeg'],
		'png' => ['image/png'],
		'webp' => ['image/webp'],
		'svg' => ['image/svg+xml'],
		'tiff' => ['image/tiff'],
		'tif' => ['image/tiff'],
		'ico' => ['image/x-icon'],
		
		// Audio.
		'mid' => ['audio/midi'],
		'midi' => ['audio/midi'],
		'mp3' => ['audio/mpeg'],
		'mpga' => ['audio/mpeg'],
		'mp2' => ['audio/mpeg'],
		'ogg' => ['audio/ogg'],
		'wav' => ['audio/wav', 'audio/x-wav'],
		'flac' => ['audio/flac'],
		'aac' => ['audio/aac', 'audio/aacp', 'audio/x-hx-aac-adts'],
		'aif' => ['audio/x-aiff'],
		'aiff' => ['audio/x-aiff'],
		'ra' => ['audio/x-realaudio'],
		'm4a' => ['audio/x-m4a'],
		
		// Video.
		'avi' => ['video/x-msvideo'],
		'flv' => ['video/x-flv'],
		'mpg' => ['video/mpeg'],
		'mpeg' => ['video/mpeg'],
		'mpe' => ['video/mpeg'],
		'mp4' => ['video/mp4', 'audio/mp4'],
		'webm' => ['video/webm', 'audio/webm'],
		'ogv' => ['video/ogg'],
		'mov' => ['video/quicktime'],
		'moov' => ['video/quicktime'],
		'qt' => ['video/quicktime'],
		'movie' => ['video/x-sgi-movie'],
		'rv' => ['video/vnd.rn-realvideo'],
		'mkv' => ['video/x-matroska'],
		'wmv' => ['video/x-ms-asf'],
		'wma' => ['video/x-ms-asf'],
		'asf' => ['video/x-ms-asf'],
		'm4v' => ['video/x-m4v'],
		
		// Other multimedia file formats.
		'psd' => ['application/x-photoshop'],
		'swf' => ['application/x-shockwave-flash'],
		'ai' => ['application/postscript'],
		'eps' => ['application/postscript'],
		'ps' => ['application/postscript'],
		'mif' => ['application/vnd.mif'],
		'xul' => ['application/vnd.mozilla.xul+xml'],
		
		// Source code formats.
		'phps' => ['application/x-httpd-php-source'],
		'js' => ['application/x-javascript'],
		
		// Archives.
		'bz2' => ['application/x-bzip'],
		'gz' => ['application/x-gzip'],
		'tar' => ['application/x-tar'],
		'tgz' => ['application/x-tar'],
		'gtar' => ['application/x-gtar'],
		'rar' => ['application/x-rar-compressed'],
		'zip' => ['application/x-zip'],
		
		// Executables and packages.
		'apk' => ['application/vnd.android.package-archive'],
		'pkg' => ['application/x-newton-compatible-pkg'],
		'exe' => ['application/vnd.microsoft.portable-executable'],
		'msi' => ['application/x-msdownload'],
		
		// RFC822 email message.
		'eml' => ['message/rfc822'],
	);
}
