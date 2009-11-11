<?php
    /**
     * @class  autoinstallAdminController
     * @author sol (sol@ngleader.com)
     * @brief  autoinstall 모듈의 admin controller class
     **/

    class ModuleInstaller {
        var $package = null;
		var $base_url = 'http://download.xpressengine.com/';
		var $temp_dir = './files/cache/autoinstall/';
        var $target_path;
        var $download_file;
        var $url;
        var $download_path;

        function ModuleInstaller(&$package)
        {
            $this->package =& $package;
        }

        function _download()
        {
            if($this->package->path == ".")
            {
                $this->download_file = $this->temp_dir."xe.tar";
                $this->target_path = ""; 
                $this->download_path = $this->temp_dir;
            }
            else
            {
                $subpath = substr($this->package->path,2);
                $this->download_file = $this->temp_dir.$subpath.".tar";
                $subpatharr = explode("/", $subpath);
                array_pop($subpatharr);
                $this->download_path = $this->temp_dir.implode("/", $subpatharr);
                $this->target_path = implode("/", $subpatharr);
            }

            $postdata = array();
            $postdata["path"] = $this->package->path;
            $postdata["module"] = "resourceapi";
            $postdata["act"] = "procResourceapiDownload";
            $buff = FileHandler::getRemoteResource($this->base_url, null, 3, "POST", "application/x-www-form-urlencoded; charset=utf-8", array(), array(), $postdata);
            FileHandler::writeFile($this->download_file, $buff);
        }

        function install()
        {
            $this->_download();
            $file_list = $this->_unPack();
            $this->_copyDir($file_list);

            FileHandler::removeDir($this->temp_dir);
            return;
        }

		function _unPack(){
            require_once(_XE_PATH_.'libs/tar.class.php');

            $oTar = new tar();
            $oTar->openTAR($this->download_file);

			$_files = $oTar->files;
            $file_list = array();
            foreach($_files as $key => $info) {
                FileHandler::writeFile($this->download_path."/".$info['name'], $info['file']);
                $file_list[] = $info['name'];
            }
            return $file_list;
		}

		function _copyDir(&$file_list){
            $ftp_info =  Context::getFTPInfo();
            if(!$ftp_info->ftp_user || !$ftp_info->ftp_password) return new Object(-1,'msg_ftp_invalid_auth_info');

            require_once(_XE_PATH_.'libs/ftp.class.php');

            $oFtp = new ftp();
            if(!$oFtp->ftp_connect('localhost', $ftp_info->ftp_port)) return new Object(-1,'msg_ftp_not_connected');
            if(!$oFtp->ftp_login($ftp_info->ftp_user, $ftp_info->ftp_password)) {
                $oFtp->ftp_quit();
                return new Object(-1,'msg_ftp_invalid_auth_info');
            }

            $_list = $oFtp->ftp_rawlist($ftp_config->ftp_root_path);
            if(count($_list) == 0 || !$_list[0]) {
                $oFtp->ftp_quit();
                $oFtp = new ftp();
                if(!$oFtp->ftp_connect($_SERVER['SERVER_NAME'], $ftp_info->ftp_port)) return new Object(-1,'msg_ftp_not_connected');
                if(!$oFtp->ftp_login($ftp_info->ftp_user, $ftp_info->ftp_password)) {
                    $oFtp->ftp_quit();
                    return new Object(-1,'msg_ftp_invalid_auth_info');
                }
            }

            $ftp_config = Context::getFTPInfo();
            $target_dir = $ftp_config->ftp_root_path.$this->target_path;

            foreach($file_list as $k => $file){
                $org_file = $file;
                if($this->package->path == ".") 
                {
                    $file = substr($file,3);
                }
                $path = FileHandler::getRealPath("./".$this->target_path."/".$file);
                $path_list = explode('/', dirname($this->target_path."/".$file));

                $real_path = "./";
                $ftp_path = $ftp_config->ftp_root_path;

                for($i=0;$i<count($path_list);$i++)
                {
                    if($path_list=="") continue;
                    $real_path .= $path_list[$i]."/";
                    $ftp_path .= $path_list[$i]."/";
                    if(!file_exists(FileHandler::getRealPath($real_path)))
                    {
                        $oFtp->ftp_mkdir($ftp_path);
                        $oFtp->ftp_site("CHMOD 755 ".$path);
                    }
                }
                $oFtp->ftp_put($target_dir .'/'. $file, FileHandler::getRealPath($this->download_path."/".$org_file));
            } 
            $oFtp->ftp_quit();

            return new Object();
		}

    }

    class autoinstallAdminController extends autoinstall {

        /**
         * @brief 초기화
         **/
        function init() {
        }

        function checkFileCheckSum($file, $checksum){
            $local_checksum = md5_file(FileHandler::getRealPath($file));
            return ($local_checksum === $checksum);
        }

        function _cleanDownloaded($obj){
            FileHandler::removeDir($obj->download_path);
        }

        function procAutoinstallAdminUpdateinfo()
        {
            $oModel = &getModel('autoinstall');
            $item = $oModel->getLatestPackage();
            if($item)
            {
                $params["updatedate"] = $item->updatedate;
            }

            $params["act"] = "getResourceapiUpdate";
            $body = XmlGenerater::generate($params);
            $buff = FileHandler::getRemoteResource($this->uri, $body, 3, "POST", "application/xml");
            $xml = new XmlParser();
            $xmlDoc = $xml->parse($buff);
            $this->updateCategory($xmlDoc);
            $this->updatePackages($xmlDoc);
            $this->checkInstalled();

            $this->setMessage("success_updated");
        }

        function checkInstalled()
        {
            executeQuery("autoinstall.deleteInstalledPackage");
            $oModel =& getModel('autoinstall');
            $packages = $oModel->getPackages();
            foreach($packages as $package)
            {
                $real_path = FileHandler::getRealPath($package->path);
                if(!file_exists($real_path)) {
                    continue;
                }

                if($package->path == ".")
                {
                    $type = "core";
                    $version = __ZBXE_VERSION__; 
                }
                else
                {
                    $path_array = explode("/", $package->path);
                    $target_name = array_pop($path_array);
                    $type = substr(array_pop($path_array), 0, -1);
                    switch($type)
                    {
                        case "module":
                            case "addon":
                            case "layout":
                            case "widget":
                            $config_file = "/conf/info.xml";
                        break;
                        case "component":
                            $config_file = "/info.xml";
                        break;
                        case "skin":    
                            case "widgetstyle":
                            $config_file = "/skin.xml";
                        break;

                        default:
                        continue;
                    }
                    $xml = new XmlParser();
                    $xmlDoc = $xml->loadXmlFile($real_path.$config_file);
                    if(!$xmlDoc) continue;
                    $version = $xmlDoc->{$type}->version->body;
                }

                $args = null;
                $args->package_srl = $package->package_srl;
                $args->version = $package->version;
                $args->current_version = $version;
                if(version_compare($args->version, $args->current_version, ">"))
                {
                    $args->need_update="Y";
                }
                else
                {
                    $args->need_update="N";
                }

                $output = executeQuery("autoinstall.insertInstalledPackage", $args);
            }
        }

        function procAutoinstallAdminPackageinstall()
        {
            $package_srls = Context::get('package_srl');
            $oModel =& getModel('autoinstall');
            $packages = explode(',', $package_srls);
            foreach($packages as $package_srl)
            {
                $package = $oModel->getPackage($package_srl);
                $oModuleInstaller = new ModuleInstaller($package);
                $oModuleInstaller->install();
            }
            $this->setMessage('success_installed');
        }

        function updatePackages(&$xmlDoc)
        {
            $oModel =& getModel('autoinstall');
            if(!$xmlDoc->response->packages->item) return;
            if(!is_array($xmlDoc->response->packages->item))
            {
                $xmlDoc->response->packages->item = array($xmlDoc->response->packages->item);
            }
            $targets = array('package_srl', 'updatedate', 'latest_item_srl', 'path', 'version', 'category_srl');
            foreach($xmlDoc->response->packages->item as $item)
            {
                $args = null;
                foreach($targets as $target)
                {
                    $args->{$target} = $item->{$target}->body;
                }
                if($oModel->getPackage($args->package_srl))
                {
                    executeQuery("autoinstall.updatePackage", $args);
                }
                else
                {
                    executeQuery("autoinstall.insertPackage", $args);
                }
            }
        }

        function updateCategory(&$xmlDoc)
        {
            executeQuery("autoinstall.deleteCategory", $args);
            $oModel =& getModel('autoinstall');
            if(!is_array($xmlDoc->response->categorylist->item))
            {
                $xmlDoc->response->categorylist->item = array($xmlDoc->response->categorylist->item);
            }
            foreach($xmlDoc->response->categorylist->item as $item)
            {
                $args = null;
                $args->category_srl = $item->category_srl->body;
                $args->parent_srl = $item->parent_srl->body;
                $args->title = $item->title->body;
                executeQuery("autoinstall.insertCategory", $args);
            }
        }
    }
?>
