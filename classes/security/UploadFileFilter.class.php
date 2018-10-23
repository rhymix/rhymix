<?php

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
		
		// Call Rhymix framework filter.
		return Rhymix\Framework\Filters\FileContentFilter::check($file, $filename);
	}
}

/* End of file : UploadFileFilter.class.php */
/* Location: ./classes/security/UploadFileFilter.class.php */
