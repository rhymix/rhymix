<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

class UploadFileFilter
{
	private static $_block_list = array ('exec', 'system', 'passthru', 'show_source', 'phpinfo', 'fopen', 'file_get_contents', 'file_put_contents', 'fwrite', 'proc_open', 'popen');

	public function check($file)
	{
		// TODO: 기능개선후 enable

		return TRUE; // disable
		if (! $file || ! FileHandler::exists($file)) return TRUE;
		return self::_check ( $file );
	}

	private function _check($file)
	{
		if (! ($fp = fopen ( $file, 'r' ))) return FALSE;

		$has_php_tag = FALSE;

		while ( ! feof ( $fp ) )
		{
			$content = fread ( $fp, 8192 );
			if (FALSE === $has_php_tag) $has_php_tag = strpos ( $content, '<?' );
			foreach ( self::$_block_list as $v )
			{
				if (FALSE !== $has_php_tag && FALSE !== strpos ( $content, $v ))
				{
					fclose ( $fp );
					return FALSE;
				}
			}
		}

		fclose ( $fp );

		return TRUE;
	}
}

/* End of file : UploadFileFilter.class.php */
/* Location: ./classes/security/UploadFileFilter.class.php */
