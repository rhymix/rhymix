<?php

    class adminAdminModel extends admin
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
                return new Object(-1,'msg_ftp_invalid_auth_info');
            }

            $sftp = ssh2_sftp($connection);
            $curpwd = "ssh2.sftp://$sftp".$this->pwd;
            $dh = @opendir($curpwd);
			if(!$dh) return new Object(-1, 'msg_ftp_invalid_path');
            $list = array();
            while(($file = readdir($dh)) !== false) {
                if(is_dir($curpwd.$file))
                {
                    $file .= "/";
                }
                else
                {
                    continue;
                }
                $list[] = $file;
            }
            closedir($dh);
            $this->add('list', $list);
        }

        function getAdminFTPList()
        {
            set_time_limit(5);
            require_once(_XE_PATH_.'libs/ftp.class.php');
            $ftp_info =  Context::getRequestVars();
            if(!$ftp_info->ftp_user || !$ftp_info->ftp_password) 
            {
                return new Object(-1, 'msg_ftp_invalid_auth_info');
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

            $oFtp = new ftp();
            if($oFtp->ftp_connect($ftp_info->ftp_host, $ftp_info->ftp_port)){
				if($oFtp->ftp_login($ftp_info->ftp_user, $ftp_info->ftp_password)) {
					$_list = $oFtp->ftp_rawlist($this->pwd);
					$oFtp->ftp_quit();
				}
                else
                {
                    return new Object(-1,'msg_ftp_invalid_auth_info');
                }
			}
            $list = array();

			if($_list){
                foreach($_list as $k => $v){
					$src = null;
					$src->data = $v;
					$res = Context::convertEncoding($src);
					$v = $res->data;
                    if(strpos($v,'d') === 0 || strpos($v, '<DIR>')) $list[] = substr(strrchr($v,' '),1) . '/';
                }
            }
            $this->add('list', $list);
        }
    }
?>
