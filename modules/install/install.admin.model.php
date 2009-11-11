<?
    /**
     * @class  installAdminModel
     * @author sol (ngleader@gmail.com)
     * @brief  install 모듈의 admin model class
     **/

    class installAdminModel extends install{
            
        function getFtpDir(){
            $pwd = Context::get('pwd');
            if(!$pwd) $pwd = '/';

            Context::set('pwd',$pwd);
            require_once(_XE_PATH_.'libs/ftp.class.php');

            $ftp_info =  Context::getFTPInfo();
            $oFtp = new ftp();
            if(!$oFtp->ftp_connect('localhost', $ftp_info->ftp_port)) return new Object(-1,'msg_ftp_not_connected');
            if(!$oFtp->ftp_login($ftp_info->ftp_user, $ftp_info->ftp_password)) {
                $oFtp->ftp_quit();
                return new Object(-1,'msg_ftp_invalid_auth_info');
            }

            $_list = $oFtp->ftp_rawlist($pwd);
            $oFtp->ftp_quit();
            $list = array();
            if($_list){
                foreach($_list as $k => $v){
                    if(strpos($v,'d') === 0) $list[] = substr(strrchr($v,' '),1) . '/';
                }
            }

            $this->add('list',$list);
        }
    }
?>
