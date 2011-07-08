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

			if (!$ftp_info->ftp_port || !is_numeric ($ftp_info->ftp_port)) {
				$ftp_info->ftp_port = "21";
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

		function getEnv($type='WORKING') {

			 $skip = array(
					 	'ext' => array('pcre','json','hash','dom','session','spl','standard','date','ctype','tokenizer','apache2handler','filter','posix','reflection','pdo')
						,'module' => array('addon','admin','autoinstall', 'comment', 'communication', 'counter', 'document', 'editor', 'file', 'importer', 'install', 'integration_search', 'layout', 'member', 'menu', 'message', 'module', 'opage', 'page', 'point', 'poll', 'rss', 'session', 'spamfilter', 'tag',  'trackback', 'trash', 'widget')
						,'addon' => array('autolink', 'blogapi', 'captcha', 'counter', 'member_communication', 'member_extra_info', 'mobile', 'openid_delegation_id', 'point_level_icon', 'resize_image' )
					);

			$info = array();
			$info['type'] = ($type !='INSTALL' ? 'WORKING' : 'INSTALL');
			$info['location'] = _XE_LOCATION_;
			$info['package'] = _XE_PACKAGE_;
			$info['host'] = $db_type->default_url ? $db_type->default_url : getFullUrl();
			$info['app'] = $_SERVER['SERVER_SOFTWARE'];
			$info['php'] = phpversion();

			$db_info = Context::getDBInfo();
			$info['db_type'] = $db_info->db_type;
			$info['use_rewrite'] = $db_info->use_rewrite;
			$info['use_db_session'] = $db_info->use_db_session == 'Y' ?'Y':'N';
			$info['use_ssl'] = $db_info->use_ssl;

			$info['phpext'] = '';
			foreach (get_loaded_extensions() as $ext) { 
				$ext = strtolower($ext);
				if(in_array($ext, $skip['ext'])) continue;
				$info['phpext'] .= '|'. $ext; 
			}
			$info['phpext'] = substr($info['phpext'],1);

			$info['module'] = '';
			$oModuleModel = &getModel('module');
			$module_list = $oModuleModel->getModuleList();
			foreach($module_list as $module){
				if(in_array($module->module, $skip['module'])) continue; 
				$info['module']  .= '|'.$module->module;
			}
			$info['module'] = substr($info['module'],1);

			$info['addon'] = '';
			$oAddonAdminModel = &getAdminModel('addon');
			$addon_list = $oAddonAdminModel->getAddonList();
			foreach($addon_list as $addon){
				if(in_array($addon->addon, $skip['addon'])) continue; 
				$info['addon'] .= '|'.$addon->addon;
			}
			$info['addon'] = substr($info['addon'],1);

			$param = '';
			foreach($info as $k => $v){
				if($v) $param .= sprintf('&%s=%s',$k,urlencode($v));
			}
			$param = substr($param, 1);

			return $param;
		}
	}
