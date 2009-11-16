<?php

    class adminAdminModel extends admin
    {
        var $pwd;

        function getSFTPList()
        {
            $ftp_info =  Context::getFTPInfo();
            $connection = ssh2_connect('localhost', $ftp_info->ftp_port);
            if(!ssh2_auth_password($connection, $ftp_info->ftp_user, $ftp_info->ftp_password))
            {
                return new Object(-1,'msg_ftp_invalid_auth_info');
            }

            $sftp = ssh2_sftp($connection);
            $curpwd = "ssh2.sftp://$sftp".$this->pwd;
            $dh = opendir($curpwd);
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
            $ftp_info =  Context::getFTPInfo();
            $this->pwd = Context::get('pwd');

            if($ftp_info->sftp == 'Y')
            {
                return $this->getSFTPList();
            }

            $oFtp = new ftp();
            if($oFtp->ftp_connect('localhost', $ftp_info->ftp_port)){
				if($oFtp->ftp_login($ftp_info->ftp_user, $ftp_info->ftp_password)) {
					$_list = $oFtp->ftp_rawlist($this->pwd);
					$oFtp->ftp_quit();
				}
			}
            $list = array();
            if(count($_list) == 0 || !$_list[0]) {
                $oFtp = new ftp();
                if($oFtp->ftp_connect($_SERVER['SERVER_NAME'], $ftp_info->ftp_port)){
					if($oFtp->ftp_login($ftp_info->ftp_user, $ftp_info->ftp_password)) {
						$_list = $oFtp->ftp_rawlist($this->pwd);
						$oFtp->ftp_quit();
					}
				}
            }

			if($_list){
                foreach($_list as $k => $v){
                    if(strpos($v,'d') === 0) $list[] = substr(strrchr($v,' '),1) . '/';
                }
            }
            $this->add('list', $list);
        }
    }
?>
