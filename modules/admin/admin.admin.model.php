<?php

    class adminAdminModel extends admin
    {
        function getAdminFTPList()
        {
            set_time_limit(5);
            require_once(_XE_PATH_.'libs/ftp.class.php');
            $ftp_info =  Context::getFTPInfo();
            $pwd = Context::get('pwd');

            $oFtp = new ftp();
            if($oFtp->ftp_connect('localhost', $ftp_info->ftp_port)){
				if($oFtp->ftp_login($ftp_info->ftp_user, $ftp_info->ftp_password)) {
					$_list = $oFtp->ftp_rawlist($pwd);
					$oFtp->ftp_quit();
				}
			}
            $list = array();
            if(count($_list) == 0 || !$_list[0]) {
                $oFtp = new ftp();
                if($oFtp->ftp_connect($_SERVER['SERVER_NAME'], $ftp_info->ftp_port)){
					if($oFtp->ftp_login($ftp_info->ftp_user, $ftp_info->ftp_password)) {
						$_list = $oFtp->ftp_rawlist($pwd);
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
