<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */
class installModel extends install
{
	var $pwd;

	function getSFTPList()
	{
		$ftp_info =  Context::getRequestVars();
		if(!$ftp_info->ftp_host)
		{
			$ftp_info->ftp_host = "127.0.0.1";
		}

		$connection = ssh2_connect($ftp_info->ftp_host, $ftp_info->ftp_port);
		if(!ssh2_auth_password($connection, $ftp_info->ftp_user, $ftp_info->ftp_password))
		{
			return $this->setError('msg_ftp_invalid_auth_info');
		}

		$sftp = ssh2_sftp($connection);
		$curpwd = "ssh2.sftp://$sftp".$this->pwd;
		$dh = @opendir($curpwd);
		if(!$dh) return $this->setError('msg_ftp_invalid_path');

		$list = array();
		while(($file = readdir($dh)) !== false)
		{
			if(!is_dir($curpwd.$file)) continue;
			$list[] = $file . "/";
		}
		closedir($dh);
		$this->add('list', $list);
	}

	function getInstallFTPList()
	{
		if(!($ftp_info = Context::getRequestVars()) || !$ftp_info->ftp_user || !$ftp_info->ftp_password) 
		{
			return $this->setError('msg_ftp_invalid_auth_info');
		}
		$this->pwd = $ftp_info->ftp_root_path;
		if(!$ftp_info->ftp_host)
		{
			$ftp_info->ftp_host = "127.0.0.1";
		}

		if($ftp_info->sftp == 'Y')
		{
			return $this->getSFTPList();
		}

		$_list = NULL;
		if(function_exists('ftp_connect'))
		{
			$connection = ftp_connect($ftp_info->ftp_host, $ftp_info->ftp_port);
			if(!$connection) return $this->setError(sprintf(lang('msg_ftp_not_connected'), 'host'));
			if(! @ftp_login($connection, $ftp_info->ftp_user, $ftp_info->ftp_password))
			{
				ftp_close($connection);	
				return $this->setError('msg_ftp_invalid_auth_info');
			}

			if($ftp_info->ftp_pasv != "N") 
			{
				ftp_pasv($connection, true);
			}

			$_list = ftp_rawlist($connection, $this->pwd);
			ftp_close($connection);	
		}
		else
		{
			$oFtp = new ftp();
			if($oFtp->ftp_connect($ftp_info->ftp_host, $ftp_info->ftp_port))
			{
				if($oFtp->ftp_login($ftp_info->ftp_user, $ftp_info->ftp_password))
				{
					$_list = $oFtp->ftp_rawlist($this->pwd);
					$oFtp->ftp_quit();
				}
				else
				{
					$oFtp->ftp_quit();
					return $this->setError('msg_ftp_invalid_auth_info');
				}
			}
		}

		$list = array();
		if($_list)
		{
			foreach($_list as $k => $v)
			{
				$src = new stdClass(); 
				$src->data = $v;
				$res = Context::convertEncoding($src);
				$v = $res->data;
				if(strpos($v,'d') === 0 || strpos($v, '<DIR>')) $list[] = substr(strrchr($v,' '),1) . '/';
			}
		}
		$this->add('list', $list);
	}
}
/* End of file install.model.php */
/* Location: ./modules/install/install.model.php */
