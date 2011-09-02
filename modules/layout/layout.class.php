<?php
    /**
     * @class  layout
     * @author NHN (developers@xpressengine.com)
     * @brief high class of the layout module 
     **/

    class layout extends ModuleObject {

        /**
         * @brief Implement if additional tasks are necessary when installing
         **/
        function moduleInstall() {
            // Create a directory to be used in the layout
            FileHandler::makeDir('./files/cache/layout');

            return new Object();
        }

        /**
         * @brief a method to check if successfully installed
         **/
        function checkUpdate() {
            $oDB = &DB::getInstance();
            // 2009. 02. 11 Add site_srl to layout table
            if(!$oDB->isColumnExists('layouts', 'site_srl')) return true;
            // 2009. 02. 26 Move the previous layout for faceoff
            $files = FileHandler::readDir('./files/cache/layout');
            for($i=0,$c=count($files);$i<$c;$i++) {
                $filename = $files[$i];
                if(preg_match('/([0-9]+)\.html/i',$filename)) return true;
            }

			if(!$oDB->isColumnExists('layouts', 'layout_type')) return true;

            return false;
        }

        /**
         * @brief Execute update
         **/
        function moduleUpdate() {
            $oDB = &DB::getInstance();
            // 2009. 02. 11 Add site_srl to menu table
            if(!$oDB->isColumnExists('layouts', 'site_srl')) {
                $oDB->addColumn('layouts','site_srl','number',11,0,true);
            }
            // 2009. 02. 26 Move the previous layout for faceoff
            $oLayoutModel = &getModel('layout');
            $files = FileHandler::readDir('./files/cache/layout');
            for($i=0,$c=count($files);$i<$c;$i++) {
                $filename = $files[$i];
                if(!preg_match('/([0-9]+)\.html/i',$filename,$match)) continue;
                $layout_srl = $match[1];
                if(!$layout_srl) continue;
                $path = $oLayoutModel->getUserLayoutPath($layout_srl);
                if(!is_dir($path)) FileHandler::makeDir($path);
                FileHandler::copyFile('./files/cache/layout/'.$filename, $path.'layout.html');
                @unlink('./files/cache/layout/'.$filename);
            }

			if(!$oDB->isColumnExists('layouts', 'layout_type')) {
                $oDB->addColumn('layouts','layout_type','char',1,'P',true);
			}

            return new Object(0, 'success_updated');
        }


        /**
         * @brief Re-generate the cache file
         **/
        function recompileCache() {
            $path = './files/cache/layout';
            if(!is_dir($path)) {
                FileHandler::makeDir($path);
                return;
            }
        }
    }
?>
